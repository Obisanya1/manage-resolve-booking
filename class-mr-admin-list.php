<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_Admin_List {

	public static function init() {
		add_filter( 'manage_' . MR_CPT::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . MR_CPT::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
	}

	public static function columns( $cols ) {
		$new = array();
		$new['cb']              = $cols['cb'];
		$new['title']           = __( 'Client', 'mr-booking' );
		$new['mrb_nature']      = __( 'Dispute', 'mr-booking' );
		$new['mrb_appointment'] = __( 'Appointment', 'mr-booking' );
		$new['mrb_payment']     = __( 'Payment', 'mr-booking' );
		$new['date']            = $cols['date'];
		return $new;
	}

	public static function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'mrb_nature':
				echo esc_html( get_post_meta( $post_id, '_mrb_nature', true ) );
				break;
			case 'mrb_appointment':
				$d = get_post_meta( $post_id, '_mrb_appointment_date', true );
				$t = get_post_meta( $post_id, '_mrb_appointment_time', true );
				if ( $d && $t ) {
					echo esc_html( date_i18n( 'j M Y', strtotime( $d ) ) . ' — ' . date_i18n( 'g:i a', strtotime( $t ) ) );
				}
				break;
			case 'mrb_payment':
				$status = get_post_meta( $post_id, '_mrb_payment_status', true );
				if ( $status === 'paid' ) {
					echo '<strong style="color:#1a7a3c;">' . esc_html__( 'Paid', 'mr-booking' ) . '</strong>';
				} else {
					echo '<span style="color:#a06a00;">' . esc_html__( 'Awaiting payment', 'mr-booking' ) . '</span>';
				}
				break;
		}
	}

	public static function add_meta_box() {
		add_meta_box( 'mrb_details', __( 'Dispute Details', 'mr-booking' ), array( __CLASS__, 'render_meta_box' ), MR_CPT::POST_TYPE, 'normal', 'high' );
	}

	public static function render_meta_box( $post ) {
		$labels = array(
			'email'              => 'Email',
			'phone'              => 'Phone',
			'company'            => 'Company / Organisation',
			'other_party'        => 'Other Party',
			'nature'             => 'Nature of Dispute',
			'method'             => 'Preferred Resolution Method',
			'dispute_value'      => 'Estimated Dispute Value',
			'urgency'            => 'Urgency',
			'source'             => 'How They Found Us',
			'appointment_date'   => 'Appointment Date',
			'appointment_time'   => 'Appointment Time',
			'payment_status'     => 'Payment Status',
			'payment_reference'  => 'Payment Reference',
			'amount_paid'        => 'Amount Paid',
			'currency'           => 'Currency',
		);
		echo '<table class="form-table"><tbody>';
		foreach ( $labels as $key => $label ) {
			$val = get_post_meta( $post->ID, '_mrb_' . $key, true );
			echo '<tr><th style="width:220px;text-align:left;">' . esc_html( $label ) . '</th><td>' . esc_html( $val ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<h4>' . esc_html__( 'Dispute Details', 'mr-booking' ) . '</h4>';
		echo '<p>' . nl2br( esc_html( get_post_meta( $post->ID, '_mrb_details', true ) ) ) . '</p>';
	}
}
