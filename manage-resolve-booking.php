<?php
/**
 * Plugin Name: Manage & Resolve — Dispute Intake & Booking
 * Plugin URI:  https://manageandresolve.com
 * Description: The "Submit a Dispute" intake form, a consultation booking calendar, and Paystack payment collection — all in one flow, for manageandresolve.com.
 * Version:     1.1.0
 * Author:      Manage & Resolve
 * Text Domain: mr-booking
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // No direct access.

define( 'MRB_VERSION', '1.1.0' );
define( 'MRB_PATH', plugin_dir_path( __FILE__ ) );
define( 'MRB_URL', plugin_dir_url( __FILE__ ) );

/**
 * ── UPDATE SOURCE — EDIT THIS ONE LINE ──
 * The GitHub repo that holds this plugin's code. Push your code here and cut a
 * "Release" on GitHub (with a version tag, e.g. v1.1.0) whenever you want the
 * update notice to appear in wp-admin. See UPDATING.md in this plugin folder
 * for the full workflow.
 */
define( 'MRB_UPDATE_REPO', 'https://github.com/Obisanya1/manage-resolve-booking/' );

require_once MRB_PATH . 'includes/class-mr-cpt.php';
require_once MRB_PATH . 'includes/class-mr-settings.php';
require_once MRB_PATH . 'includes/class-mr-booking.php';
require_once MRB_PATH . 'includes/class-mr-payment.php';
require_once MRB_PATH . 'includes/class-mr-emails.php';
require_once MRB_PATH . 'includes/class-mr-ajax.php';
require_once MRB_PATH . 'includes/class-mr-admin-list.php';
require_once MRB_PATH . 'includes/class-mr-shortcode.php';

// Third-party library — adds a native "update available" notice in wp-admin,
// sourced from GitHub Releases instead of the WordPress.org plugin directory.
require_once MRB_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';

final class MR_Booking_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		register_activation_hook( __FILE__, array( 'MR_CPT', 'activate' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );

		add_action( 'init', array( 'MR_CPT', 'register' ) );
		add_action( 'admin_menu', array( 'MR_Settings', 'add_menu' ) );
		add_action( 'admin_init', array( 'MR_Settings', 'register_settings' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		MR_Ajax::init();
		MR_Admin_List::init();
		MR_Shortcode::init();

		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
		add_action( 'mrb_cleanup_expired_holds', array( 'MR_Booking', 'cleanup_expired_holds' ) );
		if ( ! wp_next_scheduled( 'mrb_cleanup_expired_holds' ) ) {
			wp_schedule_event( time(), 'mrb_fifteen_minutes', 'mrb_cleanup_expired_holds' );
		}

		$this->init_update_checker();
	}

	/**
	 * Wires up GitHub-based updates so this shows a normal "update available"
	 * notice + Update button on the Plugins page, same as a WordPress.org plugin.
	 */
	private function init_update_checker() {
		$factory = '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory';
		if ( ! class_exists( $factory ) ) return;
		if ( strpos( MRB_UPDATE_REPO, 'Obisanya1' ) !== false ) return; // not configured yet

		$update_checker = $factory::buildUpdateChecker(
			MRB_UPDATE_REPO,
			__FILE__,
			'manage-resolve-booking'
		);

		$update_checker->setBranch( 'main' );

		// Only needed for a PRIVATE repo — uncomment and paste a GitHub personal
		// access token (GitHub → Settings → Developer settings → Personal access
		// tokens → generate one with just the "repo" scope):
		// $update_checker->setAuthentication( 'ghp_xxxxxxxxxxxxxxxxxxxx' );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'mrb_cleanup_expired_holds' );
	}

	public function add_cron_interval( $schedules ) {
		$schedules['mrb_fifteen_minutes'] = array(
			'interval' => 900,
			'display'  => __( 'Every 15 Minutes (Manage & Resolve booking cleanup)', 'mr-booking' ),
		);
		return $schedules;
	}

	/**
	 * Only load the form's CSS/JS on the page that actually contains the shortcode,
	 * so the rest of the site stays untouched.
	 */
	public function enqueue_frontend_assets() {
		if ( ! is_singular() ) return;
		global $post;
		if ( ! $post instanceof WP_Post || ! has_shortcode( $post->post_content, 'mr_dispute_booking' ) ) return;

		wp_enqueue_style( 'mrb-form', MRB_URL . 'assets/css/booking-form.css', array(), MRB_VERSION );
		wp_enqueue_script( 'mrb-paystack', 'https://js.paystack.co/v1/inline.js', array(), null, true );
		wp_enqueue_script( 'mrb-form', MRB_URL . 'assets/js/booking-form.js', array( 'mrb-paystack' ), MRB_VERSION, true );

		$settings = MR_Settings::get_settings();

		wp_localize_script( 'mrb-form', 'MRB', array(
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'mrb_nonce' ),
			'paystack_key' => $settings['paystack_public_key'],
			'currency'     => $settings['currency'],
			'fee_amount'   => (float) $settings['fee_amount'],
		) );
	}

	public function enqueue_admin_assets( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || strpos( (string) $screen->id, MR_CPT::POST_TYPE ) === false ) return;
		wp_enqueue_style( 'mrb-admin', MRB_URL . 'assets/css/admin.css', array(), MRB_VERSION );
	}
}

MR_Booking_Plugin::instance();
