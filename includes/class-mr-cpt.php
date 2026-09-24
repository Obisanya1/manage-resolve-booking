<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Every dispute submission + booking is stored as one post of this type.
 * post_status: 'pending' = slot held, awaiting payment. 'publish' = paid & confirmed.
 */
class MR_CPT {

	const POST_TYPE = 'mr_booking';

	public static function register() {
		register_post_type( self::POST_TYPE, array(
			'labels' => array(
				'name'          => __( 'Dispute Bookings', 'mr-booking' ),
				'singular_name' => __( 'Dispute Booking', 'mr-booking' ),
				'all_items'     => __( 'All Bookings', 'mr-booking' ),
				'view_item'     => __( 'View Booking', 'mr-booking' ),
				'search_items'  => __( 'Search Bookings', 'mr-booking' ),
				'not_found'     => __( 'No bookings yet', 'mr-booking' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-hourglass',
			'menu_position'   => 26,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		) );

		self::register_meta();
	}

	public static function register_meta() {
		$fields = array(
			'name', 'email', 'phone', 'company', 'other_party', 'nature',
			'method', 'details', 'dispute_value', 'urgency', 'source',
			'appointment_date', 'appointment_time',
			'payment_status', 'payment_reference', 'expected_reference',
			'amount_paid', 'currency', 'hold_expires',
		);
		foreach ( $fields as $field ) {
			register_post_meta( self::POST_TYPE, '_mrb_' . $field, array(
				'show_in_rest' => false,
				'single'       => true,
				'type'         => 'string',
			) );
		}
	}

	public static function activate() {
		self::register();
		flush_rewrite_rules();
	}
}
