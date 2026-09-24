<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_Ajax {

	public static function init() {
		add_action( 'wp_ajax_mrb_create_hold', array( __CLASS__, 'create_hold' ) );
		add_action( 'wp_ajax_nopriv_mrb_create_hold', array( __CLASS__, 'create_hold' ) );

		add_action( 'wp_ajax_mrb_verify_payment', array( __CLASS__, 'verify_payment' ) );
		add_action( 'wp_ajax_nopriv_mrb_verify_payment', array( __CLASS__, 'verify_payment' ) );
	}

	protected static function check_nonce() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'mrb_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed — please refresh the page and try again.', 'mr-booking' ) ), 400 );
		}
	}

	/** Step 1 + 2 submit: validate the dispute form and reserve the chosen slot. */
	public static function create_hold() {
		self::check_nonce();

		$required = array( 'name', 'email', 'phone', 'other_party', 'nature', 'details', 'appointment_date', 'appointment_time' );
		foreach ( $required as $field ) {
			if ( empty( $_POST[ $field ] ) ) {
				wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'mr-booking' ) ), 422 );
			}
		}

		$email = sanitize_email( wp_unslash( $_POST['email'] ) );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'mr-booking' ) ), 422 );
		}

		$details = wp_strip_all_tags( wp_unslash( $_POST['details'] ) );
		if ( mb_strlen( trim( $details ) ) < 100 ) {
			wp_send_json_error( array( 'message' => __( 'Please tell us a bit more about the dispute (at least 100 characters).', 'mr-booking' ) ), 422 );
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_POST['appointment_date'] ) || ! preg_match( '/^\d{2}:\d{2}$/', $_POST['appointment_time'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please choose a valid appointment time.', 'mr-booking' ) ), 422 );
		}

		$data = array( 'email' => $email );
		foreach ( array( 'name', 'phone', 'company', 'other_party', 'nature', 'method', 'dispute_value', 'urgency', 'source', 'appointment_date', 'appointment_time' ) as $f ) {
			$data[ $f ] = isset( $_POST[ $f ] ) ? sanitize_text_field( wp_unslash( $_POST[ $f ] ) ) : '';
		}
		$data['details'] = sanitize_textarea_field( $details );

		$result = MR_Booking::create_hold( $data );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 409 );
		}

		$s = MR_Settings::get_settings();

		wp_send_json_success( array(
			'booking_id'  => $result['post_id'],
			'reference'   => $result['reference'],
			'amount_kobo' => (int) round( (float) $s['fee_amount'] * 100 ),
			'currency'    => $s['currency'],
		) );
	}

	/** Step 3: verify the Paystack transaction server-side before confirming the booking. */
	public static function verify_payment() {
		self::check_nonce();

		$post_id   = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		$reference = isset( $_POST['reference'] ) ? sanitize_text_field( wp_unslash( $_POST['reference'] ) ) : '';

		if ( ! $post_id || ! $reference || get_post_type( $post_id ) !== MR_CPT::POST_TYPE ) {
			wp_send_json_error( array( 'message' => __( 'Invalid booking.', 'mr-booking' ) ), 400 );
		}

		// The reference paid must be the exact one issued for THIS booking — prevents reusing
		// a reference from a different, unrelated successful payment.
		$expected = get_post_meta( $post_id, '_mrb_expected_reference', true );
		if ( ! $expected || ! hash_equals( $expected, $reference ) ) {
			wp_send_json_error( array( 'message' => __( 'This payment reference does not match this booking.', 'mr-booking' ) ), 400 );
		}

		if ( get_post_status( $post_id ) === 'publish' ) {
			// Already confirmed (e.g. duplicate callback) — treat as success, no double emails.
			wp_send_json_success( array( 'message' => __( 'Booking confirmed.', 'mr-booking' ) ) );
		}

		$tx = MR_Payment::verify_transaction( $reference );
		if ( is_wp_error( $tx ) ) {
			wp_send_json_error( array( 'message' => $tx->get_error_message() ), 402 );
		}

		$s = MR_Settings::get_settings();
		$expected_kobo = (int) round( (float) $s['fee_amount'] * 100 );
		if ( (int) $tx['amount'] < $expected_kobo ) {
			wp_send_json_error( array( 'message' => __( 'The amount paid did not match the consultation fee.', 'mr-booking' ) ), 402 );
		}

		$amount_major = $tx['amount'] / 100;
		MR_Booking::mark_paid( $post_id, $tx['reference'], $amount_major, $tx['currency'] );
		MR_Emails::send_confirmation( $post_id );

		wp_send_json_success( array( 'message' => __( 'Booking confirmed.', 'mr-booking' ) ) );
	}
}
