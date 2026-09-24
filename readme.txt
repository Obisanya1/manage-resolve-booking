=== Manage & Resolve — Dispute Intake & Booking ===
Contributors: manageandresolve
Tags: booking, forms, paystack, dispute resolution
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later

One shortcode that runs the full "Submit a Dispute" flow: intake form → pick a consultation
time → pay the consultation fee via Paystack → booking confirmed.

== What this plugin does ==

1. **Dispute intake form** — the exact fields from the Submit a Dispute page (name, email,
   phone, company, other party, nature of dispute, preferred resolution method, dispute
   details, estimated value, urgency, how they found you).
2. **Booking calendar** — shows the next open business days/times based on your configured
   hours, and hides any slot that's already confirmed or currently on hold.
3. **Paystack payment** — client pays the consultation fee inline (no redirect away from
   your site); the payment is verified server-side (amount + reference checked against
   Paystack directly) before the booking is ever marked confirmed.
4. **Admin view** — every submission appears under "Dispute Bookings" in wp-admin, with the
   full dispute text, contact details, appointment time, and payment status/reference.
5. **Email notifications** — the client gets a confirmation email; you get a notification
   email with the full submission, sent to whatever address you set in Booking Settings.

== Installation ==

1. Zip the `manage-resolve-booking` folder (or upload it as-is via FTP) into
   `wp-content/plugins/`.
2. In wp-admin, go to **Plugins** and activate "Manage & Resolve — Dispute Intake & Booking".
3. Go to **Dispute Bookings → Settings** and fill in:
   - Your Paystack **public** and **secret** keys (Paystack dashboard → Settings → API Keys
     & Webhooks). Use the `pk_test_...` / `sk_test_...` pair first to test end-to-end, then
     switch to the live pair once you're happy.
   - The consultation fee and currency.
   - The email address that should receive new-booking notifications.
   - Business days, hours, slot length, how many days ahead to show, and how long a slot is
     held while someone is mid-payment (15 minutes by default).
4. On your **Submit a Dispute** page, add the shortcode:

   [mr_dispute_booking]

   Drop it wherever the intake form currently sits — it replaces the plain HTML form with
   the working 3-step flow (details → time → payment).

== Notes ==

- Only the Paystack **public** key ever reaches the browser. The secret key is used purely
  server-side to verify each transaction, so it's safe to keep in Booking Settings.
- A slot is only released to someone else after the hold time expires with no successful
  payment — normal WP-Cron housekeeping runs every 15 minutes to tidy up abandoned holds.
- This plugin uses `wp_mail()` for notifications. If emails aren't arriving reliably,
  that's almost always the hosting mail setup rather than the plugin — an SMTP plugin
  (e.g. WP Mail SMTP) fixes it in most cases.
- WordPress' built-in cron only runs on page visits, so on a low-traffic site the cleanup
  job may run a little late — harmless, since expired holds simply free up the slot as soon
  as it next runs.
- Updates are checked via GitHub Releases (see UPDATING.md in this folder) instead of the
  WordPress.org directory, so you'll get a normal "Update available" notice on the Plugins
  page once `MRB_UPDATE_REPO` is pointed at your repo.

== Changelog ==

= 1.2.0 =
* Added colour settings (Dispute Bookings → Settings → Colours) for buttons, day/time slots, text, fields and borders.
* Fixed day/time buttons showing white text on a white background with some themes.

= 1.1.0 =
* Added GitHub-based update checking (Plugin Update Checker) — see UPDATING.md.

= 1.0.0 =
* Initial release: dispute intake form, consultation booking calendar, Paystack payment.
