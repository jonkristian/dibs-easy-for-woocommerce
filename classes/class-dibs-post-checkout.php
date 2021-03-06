<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Post_Checkout {

	public $manage_orders;

	public function __construct() {
		$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		$this->manage_orders = $dibs_settings['dibs_manage_orders'];
		if ( 'yes' == $this->manage_orders ) {
			add_action( 'woocommerce_order_status_completed', array( $this, 'dibs_order_completed' ) );
			add_action( 'woocommerce_order_status_cancelled', array( $this, 'dibs_order_canceled' ) );
		}
	}

	public function dibs_order_completed( $order_id ) {
		//Get the order information
		$order = new DIBS_Get_WC_Cart();
		$body  = $order->get_order_cart( $order_id );

		//Check if dibs was used to make the order
		$gateway_used = get_post_meta( $order_id, '_payment_method', true );
		if ( 'dibs_easy' === $gateway_used ) {

			//Get paymentID from order meta and set endpoint
			$payment_id = get_post_meta( $order_id, '_dibs_payment_id' )[0];

			// Add the suffix to the endpoint
			$endpoint_suffix = 'payments/' . $payment_id . '/charges';

			// Make the request
			$request = new DIBS_Requests();
			$request = $request->make_request( 'POST', $body, $endpoint_suffix );

			// Error handling
			$wc_order = wc_get_order( $order_id );
			if ( null != $request ) {
				if ( array_key_exists( 'chargeId', $request ) ) { // Payment success
					$wc_order->add_order_note( sprintf( __( 'Payment made in DIBS with charge ID %s', 'dibs-easy-for-woocommerce' ), $request->chargeId ) );

					update_post_meta( $order_id, '_dibs_charge_id', $request->chargeId );
				} elseif ( array_key_exists( 'errors', $request ) ) { // Response with errors
					if ( array_key_exists( 'instance', $request->errors ) && 'cannot be null' === $request->errors->instance[0] ) { // If return is empty
						$this->charge_failed( $wc_order, true );
					}
					if ( array_key_exists( 'amount', $request->errors ) && 'Amount must be greater than 0' === $request->errors->amount[0] ) { // If total amount equals 0
						$message = 'Total amount equal 0';
						$this->charge_failed( $wc_order, true, $message );
					}
					if ( array_key_exists( 'amount', $request->errors ) && 'Amount dosen\'t match sum of orderitems' === $request->errors->amount[0] ) { // If the total amount does not equal the line items total
						$message = 'Order total amount does not match the order items';
						$this->charge_failed( $wc_order, true, $message );
					}
				} elseif ( array_key_exists( 'code', $request ) && '1001' == $request->code ) { // Response with error code for overcharged order
					$message = 'Payment overcharged';
					$this->charge_failed( $wc_order, false, $message );
				} else {
					$this->charge_failed( $wc_order );
				}
			} else {
				$this->charge_failed( $order );
			}
		} // End if().
	}

	public function dibs_order_canceled( $order_id ) {
		//Get the order information
		$order = new DIBS_Get_WC_Cart();
		$body  = $order->get_order_cart( $order_id );

		//Check if dibs was used to make the order
		$gateway_used = get_post_meta( $order_id, '_payment_method', true );
		if ( 'dibs_easy' === $gateway_used ) {

			//Get paymentID from order meta and set endpoint
			$payment_id = get_post_meta( $order_id, '_dibs_payment_id' )[0];

			// Add the suffix to the endpoint
			$endpoint_suffix = 'payments/' . $payment_id . '/cancels';

			// Make the request
			$request = new DIBS_Requests();
			$request = $request->make_request( 'POST', $body, $endpoint_suffix );

			$wc_order = wc_get_order( $order_id );

			if ( null === $request ) {
				$wc_order->add_order_note( sprintf( __( 'Order has been canceled in DIBS', 'dibs-easy-for-woocommerce' ) ) );
			} else {
				$wc_order->add_order_note( sprintf( __( 'There was a problem canceling the order in DIBS', 'dibs-easy-for-woocommerce' ) ) );
			}
		}
	}

	// Function to handle a failed order
	public function charge_failed( $order, $fail = true, $message = 'Payment failed in DIBS' ) {
		$order->add_order_note( sprintf( __( 'DIBS Error: %s', 'dibs-easy-for-woocommerce' ), $message ) );
		if ( true === $fail ) {
			$order->update_status( 'processing' );
			$order->save();
		}
	}
}
$dibs_post_checkout = new DIBS_Post_Checkout();
