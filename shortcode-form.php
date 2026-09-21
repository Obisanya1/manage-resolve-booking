<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/** @var array $slots */
/** @var array $settings */
?>
<div class="mrb-wizard" id="mrb-wizard" data-slots='<?php echo esc_attr( wp_json_encode( $slots ) ); ?>'>

	<div class="mrb-steps">
		<span class="mrb-step-dot is-active" data-step="1"><?php esc_html_e( '1. Your Dispute', 'mr-booking' ); ?></span>
		<span class="mrb-step-dot" data-step="2"><?php esc_html_e( '2. Choose a Time', 'mr-booking' ); ?></span>
		<span class="mrb-step-dot" data-step="3"><?php esc_html_e( '3. Confirm & Pay', 'mr-booking' ); ?></span>
	</div>

	<?php if ( empty( $slots ) ) : ?>
		<p class="mrb-error" style="display:block;"><?php esc_html_e( 'No consultation slots are currently open — please contact us directly.', 'mr-booking' ); ?></p>
	<?php else : ?>

	<div class="mrb-panel" data-panel="1">
		<div class="mrb-grid">
			<div><label><?php esc_html_e( 'Your Name *', 'mr-booking' ); ?><input type="text" name="name" required></label></div>
			<div><label><?php esc_html_e( 'Email Address *', 'mr-booking' ); ?><input type="email" name="email" required></label></div>
			<div><label><?php esc_html_e( 'Phone Number *', 'mr-booking' ); ?><input type="tel" name="phone" required></label></div>
			<div><label><?php esc_html_e( 'Company / Organisation', 'mr-booking' ); ?><input type="text" name="company"></label></div>
			<div class="mrb-full"><label><?php esc_html_e( 'Other Party *', 'mr-booking' ); ?><input type="text" name="other_party" placeholder="<?php esc_attr_e( "Name or description (can be 'not yet established')", 'mr-booking' ); ?>" required></label></div>
			<div>
				<label><?php esc_html_e( 'Nature of the Dispute *', 'mr-booking' ); ?>
					<select name="nature" required>
						<option value=""><?php esc_html_e( 'Select…', 'mr-booking' ); ?></option>
						<option>Commercial Contract</option>
						<option>Maritime</option>
						<option>Construction</option>
						<option>Joint Venture / Partnership</option>
						<option>Financial</option>
						<option>Employment / Workplace</option>
						<option>Government / Regulatory</option>
						<option>Other</option>
					</select>
				</label>
			</div>
			<div>
				<label><?php esc_html_e( 'Preferred Resolution Method', 'mr-booking' ); ?>
					<select name="method">
						<option>Not Sure — Please Advise</option>
						<option>Mediation</option>
						<option>Arbitration</option>
						<option>Conciliation</option>
						<option>Negotiation Support</option>
						<option>Early Neutral Evaluation</option>
					</select>
				</label>
			</div>
			<div class="mrb-full">
				<label><?php esc_html_e( 'Tell Us About Your Dispute *', 'mr-booking' ); ?>
					<textarea name="details" rows="5" minlength="100" placeholder="<?php esc_attr_e( 'Describe the situation — what happened, how long it has been ongoing, and what outcome you are hoping to achieve.', 'mr-booking' ); ?>" required></textarea>
				</label>
				<span class="mrb-hint"><?php esc_html_e( 'Minimum 100 characters.', 'mr-booking' ); ?></span>
			</div>
			<div>
				<label><?php esc_html_e( 'Estimated Dispute Value', 'mr-booking' ); ?>
					<select name="dispute_value">
						<option>Prefer not to say</option>
						<option>Under ₦5m</option>
						<option>₦5m–₦50m</option>
						<option>₦50m–₦500m</option>
						<option>Over ₦500m</option>
						<option>Cross-border / International</option>
					</select>
				</label>
			</div>
			<div>
				<label><?php esc_html_e( 'Urgency', 'mr-booking' ); ?>
					<select name="urgency">
						<option>No immediate urgency</option>
						<option>Within 7 days</option>
						<option>Within 30 days</option>
					</select>
				</label>
			</div>
			<div class="mrb-full">
				<label><?php esc_html_e( 'How did you find us?', 'mr-booking' ); ?>
					<select name="source">
						<option>Referral</option>
						<option>Trizon Law Chambers</option>
						<option>LinkedIn</option>
						<option>Web Search</option>
						<option>Conference / Event</option>
						<option>Other</option>
					</select>
				</label>
			</div>
		</div>
		<p class="mrb-error" data-error-for="1"></p>
		<button type="button" class="mrb-btn mrb-btn-primary" data-next="2"><?php esc_html_e( 'Continue to Choose a Time →', 'mr-booking' ); ?></button>
	</div>

	<div class="mrb-panel" data-panel="2" hidden>
		<p class="mrb-lede"><?php esc_html_e( 'Pick a day and time for a confidential initial consultation with our Principal.', 'mr-booking' ); ?></p>
		<div class="mrb-days" id="mrb-days"></div>
		<div class="mrb-times" id="mrb-times"></div>
		<p class="mrb-error" data-error-for="2"></p>
		<div class="mrb-actions">
			<button type="button" class="mrb-btn mrb-btn-ghost" data-back="1"><?php esc_html_e( '← Back', 'mr-booking' ); ?></button>
			<button type="button" class="mrb-btn mrb-btn-primary" data-next="3" disabled><?php esc_html_e( 'Continue to Payment →', 'mr-booking' ); ?></button>
		</div>
	</div>

	<div class="mrb-panel" data-panel="3" hidden>
		<div class="mrb-summary" id="mrb-summary"></div>
		<p class="mrb-lede">
			<?php
			printf(
				/* translators: %s: formatted consultation fee */
				esc_html__( 'A %s consultation fee secures this slot. Everything you have shared stays confidential and is reviewed only by our Principal.', 'mr-booking' ),
				'<strong>' . esc_html( MR_Payment::format_amount( $settings['fee_amount'], $settings['currency'] ) ) . '</strong>'
			);
			?>
		</p>
		<p class="mrb-error" data-error-for="3"></p>
		<div class="mrb-actions">
			<button type="button" class="mrb-btn mrb-btn-ghost" data-back="2"><?php esc_html_e( '← Back', 'mr-booking' ); ?></button>
			<button type="button" class="mrb-btn mrb-btn-primary" id="mrb-pay"><?php esc_html_e( 'Pay & Confirm Booking →', 'mr-booking' ); ?></button>
		</div>
	</div>

	<div class="mrb-panel mrb-success" data-panel="done" hidden>
		<h3>🔒 <?php esc_html_e( 'Booking confirmed', 'mr-booking' ); ?></h3>
		<p><?php esc_html_e( 'Thank you — your consultation is booked and confirmed. A confirmation email is on its way, and our Principal will review your submission before the call.', 'mr-booking' ); ?></p>
	</div>

	<?php endif; ?>
</div>
