<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_Emails {

	public static function send_confirmation( $post_id ) {
		$get = function ( $key ) use ( $post_id ) {
			return get_post_meta( $post_id, '_mrb_' . $key, true );
		};
		$s = MR_Settings::get_settings();

		$name  = $get( 'name' );
		$email = $get( 'email' );
		$date  = $get( 'appointment_date' );
		$time  = $get( 'appointment_time' );
		$ref   = $get( 'payment_reference' );

		$when = date_i18n( 'l, j F Y', strtotime( $date ) ) . ' ' . __( 'at', 'mr-booking' ) . ' ' . date_i18n( 'g:i a', strtotime( $time ) );

		// To the client.
		$subject = __( 'Your consultation is confirmed — Manage & Resolve', 'mr-booking' );
		$body  = "Hi {$name},\n\n";
		$body .= "Thank you — your dispute enquiry has been received and your consultation is confirmed for:\n\n";
		$body .= "{$when}\n\n";
		$body .= "Everything you have shared with us is treated with complete confidentiality. Our Principal will review your submission ahead of the call.\n\n";
		$body .= "Booking reference: {$ref}\n\n";
		$body .= "If you need to reschedule, just reply to this email.\n\n";
		$body .= "— Manage & Resolve\n";

		wp_mail( $email, $subject, $body );

		// To the admin.
		$admin_subject = sprintf( __( 'New paid consultation booking — %s', 'mr-booking' ), $name );
		$admin_body  = "New dispute consultation booked and paid.\n\n";
		$admin_body .= "Name: {$name}\nEmail: {$email}\nPhone: {$get('phone')}\nCompany: {$get('company')}\n";
		$admin_body .= "Other party: {$get('other_party')}\nNature of dispute: {$get('nature')}\nPreferred method: {$get('method')}\n";
		$admin_body .= "Estimated value: {$get('dispute_value')}\nUrgency: {$get('urgency')}\nHow they found us: {$get('source')}\n\n";
		$admin_body .= "Appointment: {$when}\nPayment reference: {$ref}\nAmount paid: " . MR_Payment::format_amount( $get( 'amount_paid' ), $get( 'currency' ) ) . "\n\n";
		$admin_body .= "Details:\n{$get('details')}\n\n";
		$admin_body .= 'View in wp-admin: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' );

		wp_mail( $s['notify_email'], $admin_subject, $admin_body );
	}
}
