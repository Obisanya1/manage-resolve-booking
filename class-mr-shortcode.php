<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_Shortcode {

	public static function init() {
		add_shortcode( 'mr_dispute_booking', array( __CLASS__, 'render' ) );
	}

	public static function render( $atts ) {
		// NOTE: assets are enqueued in MR_Booking_Plugin::enqueue_frontend_assets(), which
		// checks has_shortcode() against the current post's content. If you place this
		// shortcode via a page builder that stores content outside post_content, add a
		// matching has_shortcode()-style check there, or just wp_enqueue the 'mrb-form'
		// style/script handles unconditionally on that one page template.
		$slots    = MR_Booking::get_available_slots();
		$settings = MR_Settings::get_settings();

		ob_start();
		include MRB_PATH . 'templates/shortcode-form.php';
		return ob_get_clean();
	}
}
