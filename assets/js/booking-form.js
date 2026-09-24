(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var wizard = document.getElementById('mrb-wizard');
		if (!wizard) return;

		var slots = [];
		try {
			slots = JSON.parse(wizard.getAttribute('data-slots') || '[]');
		} catch (e) {
			slots = [];
		}

		var state = { date: null, time: null, bookingId: null, reference: null, formData: null };

		function showPanel(step) {
			wizard.querySelectorAll('.mrb-panel').forEach(function (p) {
				p.hidden = p.getAttribute('data-panel') !== String(step);
			});
			wizard.querySelectorAll('.mrb-step-dot').forEach(function (d) {
				d.classList.toggle('is-active', d.getAttribute('data-step') === String(step));
			});
		}

		function setError(step, msg) {
			var el = wizard.querySelector('[data-error-for="' + step + '"]');
			if (el) el.textContent = msg || '';
		}

		function collectStep1() {
			var data = {};
			wizard.querySelectorAll('[data-panel="1"] [name]').forEach(function (el) {
				data[el.name] = el.value.trim();
			});
			return data;
		}

		function validateStep1(data) {
			var required = ['name', 'email', 'phone', 'other_party', 'nature', 'details'];
			for (var i = 0; i < required.length; i++) {
				if (!data[required[i]]) return 'Please fill in all required fields.';
			}
			if (data.details.length < 100) return 'Please tell us a bit more about the dispute (at least 100 characters).';
			if (!/^\S+@\S+\.\S+$/.test(data.email)) return 'Please enter a valid email address.';
			return null;
		}

		var nextTo2 = wizard.querySelector('[data-next="2"]');
		if (nextTo2) {
			nextTo2.addEventListener('click', function () {
				var data = collectStep1();
				var err = validateStep1(data);
				if (err) { setError(1, err); return; }
				setError(1, '');
				state.formData = data;
				renderDays();
				showPanel(2);
			});
		}

		function dayLabel(dateStr) {
			for (var i = 0; i < slots.length; i++) {
				if (slots[i].date === dateStr) return slots[i].label;
			}
			return dateStr;
		}

		function renderDays() {
			var wrap = document.getElementById('mrb-days');
			if (!wrap) return;
			wrap.innerHTML = '';
			slots.forEach(function (day, i) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'mrb-day';
				btn.textContent = day.label;
				btn.addEventListener('click', function () {
					wrap.querySelectorAll('.mrb-day').forEach(function (b) { b.classList.remove('is-active'); });
					btn.classList.add('is-active');
					renderTimes(day);
				});
				wrap.appendChild(btn);
				if (i === 0) {
					btn.classList.add('is-active');
					renderTimes(day);
				}
			});
		}

		function renderTimes(day) {
			var wrap = document.getElementById('mrb-times');
			var nextBtn = wizard.querySelector('[data-next="3"]');
			if (!wrap) return;
			wrap.innerHTML = '';
			state.date = day.date;
			state.time = null;
			if (nextBtn) nextBtn.disabled = true;
			day.times.forEach(function (t) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'mrb-time';
				btn.textContent = t;
				btn.addEventListener('click', function () {
					wrap.querySelectorAll('.mrb-time').forEach(function (b) { b.classList.remove('is-active'); });
					btn.classList.add('is-active');
					state.time = t;
					if (nextBtn) nextBtn.disabled = false;
				});
				wrap.appendChild(btn);
			});
		}

		var nextTo3 = wizard.querySelector('[data-next="3"]');
		if (nextTo3) {
			nextTo3.addEventListener('click', function () {
				if (!state.date || !state.time) { setError(2, 'Please choose a time.'); return; }
				setError(2, '');
				var btn = this;
				btn.disabled = true;
				var originalLabel = btn.textContent;
				btn.textContent = 'Holding your slot…';

				var payload = Object.assign({}, state.formData, {
					appointment_date: state.date,
					appointment_time: state.time,
					action: 'mrb_create_hold',
					nonce: MRB.nonce
				});

				postForm(MRB.ajax_url, payload).then(function (res) {
					btn.disabled = false;
					btn.textContent = originalLabel;
					if (!res.success) { setError(2, res.data.message); return; }
					state.bookingId = res.data.booking_id;
					state.reference = res.data.reference;

					var summary = document.getElementById('mrb-summary');
					if (summary) {
						summary.innerHTML =
							'<div class="mrb-summary-row"><strong>' + escapeHtml(state.formData.name) + '</strong></div>' +
							'<div class="mrb-summary-row">' + escapeHtml(state.formData.nature) + '</div>' +
							'<div class="mrb-summary-row">' + escapeHtml(dayLabel(state.date)) + ' at ' + escapeHtml(state.time) + '</div>';
					}
					showPanel(3);
				}).catch(function () {
					btn.disabled = false;
					btn.textContent = originalLabel;
					setError(2, 'Something went wrong — please try again.');
				});
			});
		}

		var payBtn = document.getElementById('mrb-pay');
		if (payBtn) {
			payBtn.addEventListener('click', function () {
				var btn = this;
				if (typeof PaystackPop === 'undefined') {
					setError(3, 'Payment could not load — please refresh and try again.');
					return;
				}
				setError(3, '');
				btn.disabled = true;
				var originalLabel = btn.textContent;
				btn.textContent = 'Opening payment…';

				var handler = PaystackPop.setup({
					key: MRB.paystack_key,
					email: state.formData.email,
					amount: Math.round(parseFloat(MRB.fee_amount) * 100),
					currency: MRB.currency,
					ref: state.reference,
					callback: function (response) {
						btn.textContent = 'Confirming booking…';
						postForm(MRB.ajax_url, {
							action: 'mrb_verify_payment',
							nonce: MRB.nonce,
							booking_id: state.bookingId,
							reference: response.reference
						}).then(function (res) {
							btn.disabled = false;
							if (!res.success) {
								setError(3, (res.data && res.data.message) || 'Payment could not be confirmed — please contact us with your reference: ' + response.reference);
								btn.textContent = originalLabel;
								return;
							}
							showPanel('done');
						}).catch(function () {
							btn.disabled = false;
							btn.textContent = originalLabel;
							setError(3, 'Payment succeeded but confirmation failed to load — please contact us with reference: ' + response.reference);
						});
					},
					onClose: function () {
						btn.disabled = false;
						btn.textContent = originalLabel;
					}
				});
				handler.openIframe();
			});
		}

		wizard.querySelectorAll('[data-back]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				showPanel(btn.getAttribute('data-back'));
			});
		});

		function postForm(url, data) {
			var body = new URLSearchParams();
			Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
			return fetch(url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString()
			}).then(function (r) { return r.json(); });
		}

		function escapeHtml(str) {
			var d = document.createElement('div');
			d.textContent = str == null ? '' : str;
			return d.innerHTML;
		}
	});
})();
