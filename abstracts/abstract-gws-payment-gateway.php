<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Files.FileName

/**
 * Abstract class that will be inherited by all payment methods.
 *
 * @extends WC_Payment_Gateway
 *
 * @since 1.0.0
 * @version 1.0.3
 */
abstract class GWS_Payment_Gateway extends WC_Payment_Gateway {

}