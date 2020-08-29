<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class GWS_WC_Gateway extends GWS_Payment_Gateway {

    public function __construct()
    {
        $this->id = 'gatewayservices';
        $this->has_fields = false;
        $this->order_button_text = __('Proceed to GWS', 'woocommerce');
        $this->medthod_title = __('Gateway Services Redirect', 'woocommerce');
        $this->method_description = __('GatewayServices Redirect', 'woocommerce');
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->testmode = $this->get_option('testmode');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->merchant_id = $this->get_option('merchant_id');
        $this->terminal_id = $this->get_option('terminal_id');
        $this->private_key = $this->get_option('private_key');
        $this->api_password = $this->get_option('api_password');
        $this->transaction_type = $this->get_option('transaction_type');

        if ($this->testmode == 'no') {
            $this->process_url = 'https://gateway-services.com/acquiring.php';
        } else {
            $this->process_url = 'https://test.gateway-services.com/acquiring.php';
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_gwsprocessing', array($this, 'finalize_order'), 0);

        // this is a special API hook which fires webhook method
        add_action( 'woocommerce_receipt_' . $this->id, array($this,'pay_for_order'), 0);

        if(isset($_GET['order']) && !isset($_GET['gws_trans']))
        {
            $this->receipt_page($_GET['order']);
        }
        else if(isset($_GET['gws_trans']))
        {
            $this->finalize_order();
        }
    }

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = require( GWS_WC_PLUGIN_PATH . '/includes/admin/gws-settings.php' );
	}

    public function process_payment($order_id)
    {
        $order = new WC_Order($order_id);
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url( $on_checkout = true )
        );
    }

    public function pay_for_order( $order_id ) {
        ob_start();

        $order = new WC_Order( $order_id );
        // glue item description
        try {
            foreach ($order->get_items() as $item) {
                $productId = $item['product_id'];
                $productInstance = wc_get_product($productId);
                $productShortDescription[] = $productInstance->get_short_description();
            }
            $productShortDescription = mb_substr(implode(", ", $productShortDescription), 0, 50, 'UTF-8');
            
        } catch (Exception $e) {
            // TODO: catch me
            $productShortDescription = 'No description';
        } finally {
            if (mb_strlen($productShortDescription, 'UTF-8') < 6) {
                $productShortDescription = 'No description';
            }
        }

        $TransactionId = intval("11" . rand(1, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9));
        $ApiPassword_encrypt = hash('sha256', $this->api_password);

        $return_url = add_query_arg('order', $order->get_id(), add_query_arg('key', $order->get_order_key(), $order->get_checkout_order_received_url()));
        list($return_url,$query) = explode('?',$return_url);
        $vars = explode('&',$query);

        $xmlReq = '<?xml version="1.0" encoding="UTF-8" ?>
        <TransactionRequest>
            <Language>ENG</Language>
            <Credentials>
                <MerchantId>' . $this->merchant_id . '</MerchantId>
                <TerminalId>' . $this->terminal_id . '</TerminalId>
                <TerminalPassword>' . $ApiPassword_encrypt . '</TerminalPassword>
            </Credentials>
            <TransactionType>' . ($this->transaction_type != '' ? $this->transaction_type : 'LP101') . '</TransactionType>
            <TransactionId>' . $TransactionId . '</TransactionId>
            <ReturnUrl page="' . $return_url . '">
                <Param>
                    <Key>gws_trans</Key>
                    <Value>' . $TransactionId . '</Value>
                </Param>';
        if (!empty($vars)) {
            foreach ($vars as $var) {
                $v = explode('=', $var);
                $xmlReq .= '
                <Param>
                    <Key>' . $v[0] . '</Key>
                    <Value>' . $v[1] . '</Value>
                </Param>';
            }
        }
        $xmlReq .= '
            </ReturnUrl>
            <CurrencyCode>' . get_woocommerce_currency() . '</CurrencyCode>
            <TotalAmount>' . number_format($order->get_total(), 2, '', '') . '</TotalAmount>
            <ProductDescription>' . $productShortDescription . '</ProductDescription >
            <CustomerDetails>
                <FirstName>' . $order->get_billing_first_name() . '</FirstName>
                <LastName>' . $order->get_billing_last_name() . '</LastName>
                <CustomerIP>' . WC_GWS_Helper::get_ip() . '</CustomerIP>
                <Phone>' . $order->get_billing_phone() . '</Phone>
                <Email>' . $order->get_billing_email() . '</Email>
                <Street>' . $order->get_billing_address_1() . '</Street>
                <City>' . $order->get_billing_city() . '</City>
                <Region>' . $order->get_billing_state() . '</Region>
                <Country>' . $order->get_billing_country() . '</Country>
                <Zip>' . $order->get_billing_postcode() . '</Zip>
            </CustomerDetails>
        </TransactionRequest>';

        GWS_WC_Logger::log("DEBUG: Request is: " . PHP_EOL . $xmlReq);
        GWS_WC_Logger::log("DEBUG: Return url is: " . $return_url);

        // Add signature
        $signature_key = trim($this->private_key . $this->api_password . $TransactionId);
        $signature = base64_encode(hash_hmac("sha256", trim($xmlReq), $signature_key, True));
        $encodedMessage = base64_encode($xmlReq);
        // form post params
        $params = array(
            'version' => '1.0',
            'encodedMessage' => $encodedMessage,
            'signature' => $signature
        );

        GWS_WC_Logger::log("DEBUG: Configuring GWS_PARAMS: " . PHP_EOL . print_r($params, TRUE));
        update_post_meta($order->get_id(), 'GWS_PARAMS', $params);
        GWS_WC_Logger::log("DEBUG: Configuring GWS_TRANS_ID: " . PHP_EOL . $TransactionId);
        update_post_meta($order->get_id(), 'GWS_TRANS_ID', $TransactionId);

        GWS_WC_Logger::log("DEBUG: Configuring GWS_TRANS_ID: " . PHP_EOL . $TransactionId);
        $order->add_order_note( __( 'Order placed and user redirected.', 'txtdomain' ) );
        ?>
        
        <form class="my-gws-checkout" method="POST" action="<?php echo $this->process_url; ?>">
            <input type="hidden" name="version" value="1.0"/>
            <input type="hidden" name="encodedMessage" value="<?php echo $encodedMessage; ?>">
            <input type="hidden" name="signature" value="<?php echo $signature; ?>">
            <button id="my-gws-pay" type="submit">PAY</button>
        </form>

        <?php
        return ob_get_flush();
    }

    function checkSignature($data) {
        $result = false;

		if (!$data || !isset($data['gws_trans']) || !isset($data['signature']) || !isset($data['encodedMessage'])) {
			return false;
		}

		// Get signature key
		$signature_key = trim($this->private_key . $this->api_password . $data['gws_trans']);
		// Get decoded message
		$decodedMessage=base64_decode($data['encodedMessage']);
		// Compute signature
		$computedSignature = base64_encode(hash_hmac("sha256", $decodedMessage, $signature_key, True));
		// Validate signature
		if($computedSignature == $data['signature']) {
            GWS_WC_Logger::log("QA : Signature verified successfully");
			$result = simplexml_load_string($decodedMessage);
			return $result;
		} else { 
            GWS_WC_Logger::log("QA : Invalid signature.");
			return false;
		}
		
		return $result;
    }

    function finalize_order() {
        global $woocommerce;

        if (isset($_GET['gws_trans']) && $_GET['gws_trans'] != '' && isset($_GET['order']) && $_GET['order'] != '') {
            $error = '';
            $order = new WC_Order((int)$_GET['order']);

            // Merge request
            $request_merged = array_merge($_GET, $_POST);
            // Check signature
            $result = $this->checkSignature($request_merged);
            // Log debug
            GWS_WC_Logger::log("DEBUG: Answer: " . PHP_EOL . (string)$result);

            // if result
            if ($result) {
                // And if it's approved
                if ((string)$result->PaymentStatus == 'APPROVED') {
                    // Add order note
                    $order->add_order_note(__('Gateway Services Processing successful', 'woocommerce'));
                    GWS_WC_Logger::log("INFO: Gateway Services Processing successful");
        
                    // Update some metas
                    update_post_meta($order->get_id(), 'Transaction ID', (string)$result->TransactionId);
                    update_post_meta($order->get_id(), 'CustomerId', (string)$result->CustomerId);
        
                    // Make Order Completed
                    $order->payment_complete();
        
                    // Remove cart
                    $woocommerce->cart->empty_cart();
        
                    // Get redirect url
                    $redirect_url = $order->get_checkout_order_received_url();
                    GWS_WC_Logger::log("DEBUG: Redirecting to: $redirect_url");

                    // Redirecting
                    wp_redirect( $redirect_url );
                    exit();
                } elseif ((string)$result->PaymentStatus != 'APPROVED') {
                    $error = __('Payment Error: ' . (string)$result->Description . ' (' . (string)$result->Code . ')', 'woothemes');
                    GWS_WC_Logger::log("ERROR: Unknown Payment Error " . (string)$result->Description . " (" . (string)$result->Code . ")");
                }
            } else {
                $error = __('Unknown Payment Error, please try again', 'woothemes');
                GWS_WC_Logger::log("ERROR: Unknown Payment Error");
            }
        }

        // final redirect
        wp_redirect(add_query_arg('gws_error', urlencode($error), get_permalink(woocommerce_get_page_id('checkout'))));
    }
}