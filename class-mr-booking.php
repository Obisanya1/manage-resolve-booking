<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_Booking {

	/**
	 * Build the list of upcoming open days + times for the frontend calendar.
	 * Returns: [ [ 'date' => 'Y-m-d', 'label' => 'Mon, 12 Oct', 'times' => ['09:00', '09:45', ...] ], ... ]
	 */
	public static function get_available_slots() {
		$s  = MR_Settings::get_settings();
		$tz = wp_timezone();
		$now = new DateTime( 'now', $tz );

		$taken = self::get_taken_slots();

		$out = array();
		$checked = 0;
		$found_days = 0;
		$max_days_ahead = (int) $s['days_ahead'];
		$safety_limit = ( $max_days_ahead * 3 ) + 21; // enough look-ahead even if business_days is sparse

		while ( $found_days < $max_days_ahead && $checked < $safety_limit ) {
			$checked++;
			$day = clone $now;
			$day->modify( "+{$checked} days" );
			$day_key = strtolower( $day->format( 'D' ) );
			$day_key = substr( $day_key, 0, 3 ); // mon, tue, wed...

			if ( ! in_array( $day_key, $s['business_days'], true ) ) continue;

			$date_str = $day->format( 'Y-m-d' );
			$times = self::generate_day_slots( $date_str, $s, $tz );

			$times = array_values( array_filter( $times, function ( $t ) use ( $date_str, $taken ) {
				return empty( $taken[ $date_str . ' ' . $t ] );
			} ) );

			if ( ! empty( $times ) ) {
				$out[] = array(
					'date'  => $date_str,
					'label' => $day->format( 'D, j M' ),
					'times' => $times,
				);
				$found_days++;
			}
		}

		return $out;
	}

	protected static function generate_day_slots( $date_str, $settings, $tz ) {
		$slots = array();
		$start = new DateTime( $date_str . ' ' . $settings['start_time'], $tz );
		$end   = new DateTime( $date_str . ' ' . $settings['end_time'], $tz );
		$minutes = max( 15, (int) $settings['slot_minutes'] );

		$cursor = clone $start;
		while ( $cursor < $end ) {
			$slot_end = clone $cursor;
			$slot_end->modify( "+{$minutes} minutes" );
			if ( $slot_end > $end ) break;
			$slots[] = $cursor->format( 'H:i' );
			$cursor->modify( "+{$minutes} minutes" );
		}
		return $slots;
	}

	/**
	 * Slots that are confirmed (publish), or currently held (pending + inside the hold window).
	 * Returns assoc array keyed "Y-m-d H:i" => true
	 */
	protected static function get_taken_slots() {
		$query = new WP_Query( array(
			'post_type'      => MR_CPT::POST_TYPE,
			'post_status'    => array( 'publish', 'pending' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array( 'key' => '_mrb_appointment_date', 'compare' => 'EXISTS' ),
			),
		) );

		$taken = array();
		$now_ts = current_time( 'timestamp' );

		foreach ( $query->posts as $post_id ) {
			$status = get_post_status( $post_id );
			$date   = get_post_meta( $post_id, '_mrb_appointment_date', true );
			$time   = get_post_meta( $post_id, '_mrb_appointment_time', true );
			if ( ! $date || ! $time ) continue;

			if ( $status === 'publish' ) {
				$taken[ $date . ' ' . $time ] = true;
				continue;
			}

			// Pending: only counts as taken while inside its hold window.
			$hold_expires = get_post_meta( $post_id, '_mrb_hold_expires', true );
			if ( $hold_expires && $now_ts < (int) $hold_expires ) {
				$taken[ $date . ' ' . $time ] = true;
			}
		}

		return $taken;
	}

	/**
	 * Create a pending "hold" booking before the client is sent to payment.
	 * Returns array( 'post_id' => int, 'reference' => string ) on success, WP_Error on failure.
	 */
	public static function create_hold( $data ) {
		$s = MR_Settings::get_settings();

		// Re-check availability server-side — never trust the client alone.
		$taken = self::get_taken_slots();
		$key = $data['appointment_date'] . ' ' . $data['appointment_time'];
		if ( ! empty( $taken[ $key ] ) ) {
			return new WP_Error( 'slot_taken', __( 'That time was just booked by someone else — please choose another.', 'mr-booking' ) );
		}

		$post_id = wp_insert_post( array(
			'post_type'   => MR_CPT::POST_TYPE,
			'post_status' => 'pending',
			'post_title'  => sprintf( '%s — %s %s', sanitize_text_field( $data['name'] ), $data['appointment_date'], $data['appointment_time'] ),
		), true );

		if ( is_wp_error( $post_id ) ) return $post_id;

		$meta_fields = array( 'name', 'email', 'phone', 'company', 'other_party', 'nature', 'method', 'details', 'dispute_value', 'urgency', 'source', 'appointment_date', 'appointment_time' );
		foreach ( $meta_fields as $f ) {
			if ( isset( $data[ $f ] ) ) {
				update_post_meta( $post_id, '_mrb_' . $f, wp_kses_post( $data[ $f ] ) );
			}
		}

		$reference = 'MRB-' . $post_id . '-' . strtoupper( wp_generate_password( 8, false, false ) );

		update_post_meta( $post_id, '_mrb_payment_status', 'awaiting_payment' );
		update_post_meta( $post_id, '_mrb_expected_reference', $reference );
		update_post_meta( $post_id, '_mrb_hold_expires', current_time( 'timestamp' ) + ( (int) $s['hold_minutes'] * 60 ) );

		return array( 'post_id' => $post_id, 'reference' => $reference );
	}

	public static function mark_paid( $post_id, $reference, $amount, $currency ) {
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
		update_post_meta( $post_id, '_mrb_payment_status', 'paid' );
		update_post_meta( $post_id, '_mrb_payment_reference', sanitize_text_field( $reference ) );
		update_post_meta( $post_id, '_mrb_amount_paid', sanitize_text_field( $amount ) );
		update_post_meta( $post_id, '_mrb_currency', sanitize_text_field( $currency ) );
		delete_post_meta( $post_id, '_mrb_hold_expires' );
	}

	/** Cron: delete stale, never-paid holds so their slots free up and the list stays tidy. */
	public static function cleanup_expired_holds() {
		$query = new WP_Query( array(
			'post_type'      => MR_CPT::POST_TYPE,
			'post_status'    => 'pending',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		$now_ts = current_time( 'timestamp' );
		foreach ( $query->posts as $post_id ) {
			$hold_expires = get_post_meta( $post_id, '_mrb_hold_expires', true );
			// One extra hour of grace in case a payment confirmation was delayed.
			if ( $hold_expires && $now_ts > ( (int) $hold_expires + HOUR_IN_SECONDS ) ) {
				wp_delete_post( $post_id, true );
			}
		}
	}
}
