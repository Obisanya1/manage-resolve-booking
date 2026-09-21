<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MR_Settings {

	const OPTION_KEY = 'mrb_settings';

	public static function defaults() {
		return array(
			'paystack_public_key' => '',
			'paystack_secret_key' => '',
			'currency'            => 'NGN',
			'fee_amount'          => '25000',
			'notify_email'        => get_option( 'admin_email' ),
			'business_days'       => array( 'mon', 'tue', 'wed', 'thu', 'fri' ),
			'start_time'          => '09:00',
			'end_time'            => '17:00',
			'slot_minutes'        => '45',
			'days_ahead'          => '14',
			'hold_minutes'        => '15',
		);
	}

	public static function get_settings() {
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $saved, self::defaults() );
	}

	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=' . MR_CPT::POST_TYPE,
			__( 'Booking Settings', 'mr-booking' ),
			__( 'Settings', 'mr-booking' ),
			'manage_options',
			'mrb-settings',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_settings() {
		register_setting( 'mrb_settings_group', self::OPTION_KEY, array( __CLASS__, 'sanitize' ) );
	}

	public static function sanitize( $input ) {
		$out = array();
		$out['paystack_public_key'] = sanitize_text_field( $input['paystack_public_key'] ?? '' );
		$out['paystack_secret_key'] = sanitize_text_field( $input['paystack_secret_key'] ?? '' );
		$out['currency']            = sanitize_text_field( $input['currency'] ?? 'NGN' );
		$out['fee_amount']          = (string) max( 0, floatval( $input['fee_amount'] ?? 0 ) );
		$out['notify_email']        = sanitize_email( $input['notify_email'] ?? '' );
		$allowed_days               = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
		$out['business_days']       = array_values( array_intersect( $allowed_days, array_map( 'sanitize_key', (array) ( $input['business_days'] ?? array() ) ) ) );
		$out['start_time']          = preg_match( '/^\d{2}:\d{2}$/', $input['start_time'] ?? '' ) ? $input['start_time'] : '09:00';
		$out['end_time']            = preg_match( '/^\d{2}:\d{2}$/', $input['end_time'] ?? '' ) ? $input['end_time'] : '17:00';
		$out['slot_minutes']        = (string) max( 15, absint( $input['slot_minutes'] ?? 45 ) );
		$out['days_ahead']          = (string) max( 1, absint( $input['days_ahead'] ?? 14 ) );
		$out['hold_minutes']        = (string) max( 5, absint( $input['hold_minutes'] ?? 15 ) );
		return $out;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$s = self::get_settings();
		$days = array( 'mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Dispute Booking — Settings', 'mr-booking' ); ?></h1>
			<p><?php esc_html_e( 'Configure the consultation fee, Paystack keys, and available booking hours used by the [mr_dispute_booking] shortcode.', 'mr-booking' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'mrb_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="mrb_public"><?php esc_html_e( 'Paystack Public Key', 'mr-booking' ); ?></label></th>
						<td><input type="text" id="mrb_public" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[paystack_public_key]" value="<?php echo esc_attr( $s['paystack_public_key'] ); ?>" placeholder="pk_live_..."></td>
					</tr>
					<tr>
						<th><label for="mrb_secret"><?php esc_html_e( 'Paystack Secret Key', 'mr-booking' ); ?></label></th>
						<td>
							<input type="password" id="mrb_secret" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[paystack_secret_key]" value="<?php echo esc_attr( $s['paystack_secret_key'] ); ?>" placeholder="sk_live_...">
							<p class="description"><?php esc_html_e( 'Used only server-side to verify payments — never sent to the browser.', 'mr-booking' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="mrb_currency"><?php esc_html_e( 'Currency', 'mr-booking' ); ?></label></th>
						<td>
							<select id="mrb_currency" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[currency]">
								<?php foreach ( array( 'NGN', 'USD', 'GHS', 'ZAR', 'KES' ) as $c ) : ?>
									<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $s['currency'], $c ); ?>><?php echo esc_html( $c ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Must match a currency enabled on your Paystack account.', 'mr-booking' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="mrb_fee"><?php esc_html_e( 'Consultation Fee', 'mr-booking' ); ?></label></th>
						<td>
							<input type="number" step="0.01" min="0" id="mrb_fee" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[fee_amount]" value="<?php echo esc_attr( $s['fee_amount'] ); ?>">
							<p class="description"><?php esc_html_e( 'Charged when a client books their consultation slot.', 'mr-booking' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="mrb_notify"><?php esc_html_e( 'Notify Email', 'mr-booking' ); ?></label></th>
						<td>
							<input type="email" id="mrb_notify" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[notify_email]" value="<?php echo esc_attr( $s['notify_email'] ); ?>">
							<p class="description"><?php esc_html_e( 'Where new paid bookings are sent — e.g. disputes@manageandresolve.com.', 'mr-booking' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Business Days', 'mr-booking' ); ?></th>
						<td>
							<?php foreach ( $days as $key => $label ) : ?>
								<label style="margin-right:16px;display:inline-block;">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[business_days][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $s['business_days'], true ) ); ?>>
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th><label for="mrb_start"><?php esc_html_e( 'Business Hours', 'mr-booking' ); ?></label></th>
						<td>
							<input type="time" id="mrb_start" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[start_time]" value="<?php echo esc_attr( $s['start_time'] ); ?>">
							<?php esc_html_e( 'to', 'mr-booking' ); ?>
							<input type="time" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[end_time]" value="<?php echo esc_attr( $s['end_time'] ); ?>">
						</td>
					</tr>
					<tr>
						<th><label for="mrb_slot"><?php esc_html_e( 'Slot Length (minutes)', 'mr-booking' ); ?></label></th>
						<td><input type="number" min="15" step="15" id="mrb_slot" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[slot_minutes]" value="<?php echo esc_attr( $s['slot_minutes'] ); ?>"></td>
					</tr>
					<tr>
						<th><label for="mrb_ahead"><?php esc_html_e( 'Days Ahead to Show', 'mr-booking' ); ?></label></th>
						<td><input type="number" min="1" max="60" id="mrb_ahead" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[days_ahead]" value="<?php echo esc_attr( $s['days_ahead'] ); ?>"></td>
					</tr>
					<tr>
						<th><label for="mrb_hold"><?php esc_html_e( 'Slot Hold Time (minutes)', 'mr-booking' ); ?></label></th>
						<td>
							<input type="number" min="5" max="60" id="mrb_hold" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[hold_minutes]" value="<?php echo esc_attr( $s['hold_minutes'] ); ?>">
							<p class="description"><?php esc_html_e( 'How long a chosen slot is reserved while the client is on the payment step, before it releases back to the calendar.', 'mr-booking' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Shortcode', 'mr-booking' ); ?></h2>
			<p><?php esc_html_e( 'Add this to your Submit a Dispute page:', 'mr-booking' ); ?> <code>[mr_dispute_booking]</code></p>
		</div>
		<?php
	}
}
