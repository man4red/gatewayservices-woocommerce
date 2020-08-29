<?php
/*
 * Plugin Name: Woocommerce GatewayServices Redirect
 * Plugin URI: https://gateway-services.com/
 * Description: Woocommerce payment module for use with https://gateway-services.com
 * Version: 1.0.5
 * WC requires at least: 3.0
 * WC tested up to: 4.3
 * Text Domain: woocommerce-gateway-services
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required minimums and constants
 */
define( 'GWS_WC_VERSION', '1.0.5' );
define( 'GWS_WC_MIN_PHP_VER', '5.6.0' );
define( 'GWS_WC_MIN_WC_VER', '3.0' );
define( 'GWS_WC_FUTURE_MIN_WC_VER', '3.0' );
define( 'GWS_WC_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

function woocommerce_gws_missing_wc_notice() {
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'GatewayServices requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-gateway-services' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

function woocommerce_gws_missing_wc_gateway_notice() {
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'GatewayServices requires WooCommerce (gateway) to be installed and active. You can download %s here.', 'woocommerce-gateway-services' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

function woocommerce_gws_wc_not_supported() {
	/* translators: $1. Minimum WooCommerce version. $2. Current WooCommerce version. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'GatewayServices requires WooCommerce %1$s or greater to be installed and active. WooCommerce %2$s is no longer supported.', 'woocommerce-gateway-services' ), GWS_WC_MIN_WC_VER, WC_VERSION ) . '</strong></p></div>';
}

add_action('plugins_loaded', 'gws_wc_init');

function gws_wc_init()
{
    if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_gws_missing_wc_notice' );
		return;
    }
    
	if ( version_compare( WC_VERSION, GWS_WC_MIN_WC_VER, '<' ) ) {
		add_action( 'admin_notices', 'woocommerce_gws_wc_not_supported' );
		return;
	}

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		add_action( 'admin_notices', 'woocommerce_gws_missing_wc_gateway_notice' );
		return;
    }

	if ( ! class_exists( 'GWS_WC' ) ) :

		class GWS_WC {

			/**
			 * @var Singleton The reference the *Singleton* instance of this class
			 */
			private static $instance;

			/**
			 * Returns the *Singleton* instance of this class.
			 *
			 * @return Singleton The *Singleton* instance.
			 */
			public static function get_instance() {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}
				return self::$instance;
			}

			/**
			 * Private clone method to prevent cloning of the instance of the
			 * *Singleton* instance.
			 *
			 * @return void
			 */
			private function __clone() {}

			/**
			 * Private unserialize method to prevent unserializing of the *Singleton*
			 * instance.
			 *
			 * @return void
			 */
			private function __wakeup() {}

			/**
			 * Protected constructor to prevent creating a new instance of the
			 * *Singleton* via the `new` operator from outside of this class.
			 */
			private function __construct() {
				add_action( 'admin_init', array( $this, 'install' ) );
				$this->init();
			}

			/**
			 * Init the plugin after plugins_loaded so environment variables are set.
			 *
			 * @since 1.0.0
             * @version 1.0.4
			 */
			public function init() {
                require_once dirname( __FILE__ ) . '/includes/class-gws-wc-helper.php';
                require_once dirname( __FILE__ ) . '/includes/class-gws-wc-logger.php';
                require_once dirname( __FILE__ ) . '/abstracts/abstract-gws-payment-gateway.php';
                require_once dirname( __FILE__ ) . '/includes/class-gws-wc-gateway.php';
                

				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );

				if ( version_compare( WC_VERSION, '3.4', '<' ) ) {
					add_filter( 'woocommerce_get_sections_checkout', array( $this, 'filter_gateway_order_admin' ) );
				}
			}

			/**
			 * Updates the plugin version in db
			 *
			 * @since 1.0.0
             * @version 1.0.4
			 */
			public function update_plugin_version() {
				delete_option( 'gws_wc_version' );
				update_option( 'gws_wc_version', GWS_WC_VERSION );
			}

			/**
			 * Handles upgrade routines.
			 *
			 * @since 1.0.0
             * @version 1.0.4
			 */
			public function install() {
				if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
					return;
				}

				if ( ! defined( 'IFRAME_REQUEST' ) && ( GWS_WC_VERSION !== get_option( 'gws_wc_version' ) ) ) {
					do_action( 'woocommerce_stripe_updated' );

					if ( ! defined( 'GWS_WC_INSTALLING' ) ) {
						define( 'GWS_WC_INSTALLING', true );
					}

					$this->update_plugin_version();
				}
			}

			/**
			 * Add the gateways to WooCommerce.
			 *
			 * @since 1.0.0
             * @version 1.0.4
			 */
			public function add_gateways( $methods ) {
                $methods[] = 'GWS_WC_Gateway';
				return $methods;
			}
		}

		GWS_WC::get_instance();
	endif;
}