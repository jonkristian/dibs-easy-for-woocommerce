<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Compliance with European Union's General Data Protection Regulation.
 *
 * @class    WC_Dibs_Easy_GDPR
 * @version  1.0.0
 * @package  WC_Dibs_Easy/Classes
 * @category Class
 * @author   Krokedil
 */
class WC_Dibs_Easy_GDPR {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'privacy_declarations' ) );
	}
	/**
	 * Privacy declarations.
	 *
	 * @return void
	 */
	public function privacy_declarations() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content =
				__(
					'When you place an order in the webstore with DIBS Easy as the choosen payment method, ' .
					'information about the products in the order (namne, price, quantity, SKU) is sent to DIBS. ' .
					'When the purchase is finalized DIBS sends your billing and shipping address back to the webstore. ' .
					'This data plus an unique identifier for the purchase is then stored as billing and shipping data in the order in WooCommerce.',
					'dibs-easy-for-woocommerce'
				);
			wp_add_privacy_policy_content(
				'DIBS Easy for WooCommerce',
				wp_kses_post( wpautop( $content ) )
			);
		}
	}
}
new WC_Dibs_Easy_GDPR();
