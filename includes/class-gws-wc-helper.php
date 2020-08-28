<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides static methods as helpers.
 *
 * @since 1.0.2
 */
class WC_GWS_Helper {

	public static function get_ip()
	{
		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key)
		{
			if (array_key_exists($key, $_SERVER) === true)
			{
				foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip)
				{
					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false)
					{
						return $ip;
					} else {
						return '127.0.0.1';
					}
				}
			}
		}
	}


}