<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters(
	'gws_wc_gateway_settings',
	array(
		'enabled' =>        array(
			'title' => __('Enable Gateway Services Redirect', 'woocommerce'),
			'type' => 'checkbox',
			'description' => '',
			'default' => 'no'
		),
		'testmode' =>       array(
			'title' => __('Test Mode', 'woocommerce'),
			'type' => 'checkbox',
			'description' => 'Enabling Test Mode will result in accessing TEST API instead of LIVE API',
			'default' => 'yes',
			'desc_tip'    => true,
		),
		'title' =>          array(
			'title' => __('Title', 'woocommerce'),
			'type' => 'text',
			'description' => __('Payment method title that the customer will see on your website.', 'woocommerce'),
			'default' => __('Gateway Services Gateway', 'woocommerce'),
			'desc_tip' => true,
		),
		'merchant_id' =>    array(
			'title' => __('Merchant ID', 'woocommerce'),
			'type' => 'text',
			'description' => __('Your merchant ID', 'woocommerce'),
			'default' => '',
			'desc_tip'    => true,
		),
		'terminal_id'   =>    array(
			'title'         => __('Terminal ID', 'woocommerce'),
			'type'          => 'text',
			'description'   => __('Your terminal ID', 'woocommerce'),
			'default'       => '',
			'desc_tip'    => true,
		),
		'api_password'          =>   array(
			'title'         => __('API Password', 'woocommerce'),
			'type'          => 'text',
			'description'   => __('Your API password', 'woocommerce'),
			'default'       => '',
			'desc_tip'    => true,
		),
		'private_key'           =>    array(
			'title'         => __('Private Key', 'woocommerce'),
			'type'          => 'text',
			'description'   => __('Your Private Key', 'woocommerce'),
			'default'       => '',
			'desc_tip'    => true,
		),
		'transaction_type'      => array(
			'title'         => __('Transaction Type', 'woocommerce'),
			'type'          => 'text',
			'description'   => __('Transaction Type (if empty default values will be used)', 'woocommerce'),
			'default'       => '',
			'desc_tip'    => true,
		),
		'logging'           => array(
			'title'       => __( 'Logging', 'woocommerce' ),
			'label'       => __( 'Log debug messages', 'woocommerce' ),
			'type'        => 'checkbox',
			'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'woocommerce' ),
			'default'     => 'no',
			'desc_tip'    => true,
		),
	)
);
