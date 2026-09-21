<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_Payment {

	public static function format_amount( $amount, $currency ) {
		$symbols = array( 'NGN' => '₦', 'USD' => '$', 'GHS' => '₵', 'ZAR' => 'R', 'KES' => 'KSh' );
		$symbol = $symbols[ $currency ] ?? ( $currency . ' ' );
		return $symbol . number_format( (float) $amount, 2 );
	}

	/**
	 * Verify a Paystack transaction reference server-side.
	 * Returns the transaction data array on success, WP_Error on failure.
	 */
	public static function verify_transaction( $reference ) {
		$s = MR_Settings::get_settings();
		$secret = $s['paystack_secret_key'];

		if ( empty( $secret ) ) {
			return new WP_Error( 'no_key', __( 'Paystack secret key is not configured in Booking Settings.', 'mr-booking' ) );
		}

		$response = wp_remote_get( 'https://api.paystack.co/transaction/verify/' . rawurlencode( $reference ), array(
			'headers' => array( 'Authorization' => 'Bearer ' . $secret ),
			'timeout' => 20,
		) );

		if ( is_wp_error( $response ) ) return $response;

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 || empty( $body['status'] ) ) {
			return new WP_Error( 'verify_failed', $body['message'] ?? __( 'Could not verify payment with Paystack.', 'mr-booking' ) );
		}

		$tx = $body['data'];
		if ( ! isset( $tx['status'] ) || $tx['status'] !== 'success' ) {
			return new WP_Error( 'not_successful', __( 'Payment was not successful.', 'mr-booking' ) );
		}

		return $tx; // Includes amount (in kobo/cents), currency, reference, customer, etc.
	}
}
