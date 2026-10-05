let jq = null;
(function () {
	jQuery(function ($) {
		jq = $;

		/**
		 * Form selectors, in priority order: order-pay first, then classic checkout forms.
		 *
		 * @const {string}
		 */
		const PAY_FORM_SELECTOR = 'form#order_review, form.checkout, form[name="checkout"]';

		/**
		 * Resolves the active payment form jQuery object.
		 *
		 * @returns {jQuery} First matching form, or empty jQuery set.
		 */
		function getPayForm() {
			return $(PAY_FORM_SELECTOR).first();
		}

		/**
		 * Collects browser environment values expected by the gateway / 3DS2 payload.
		 *
		 * `challengeWindowSize` uses EMV code `3` (≈500×600), not pixel dimensions.
		 *
		 * @returns {Object<string, string|number>} Key-value map aligned with `neopayment_get_3ds_params()` keys.
		 */
		function collectBrowserData() {
			const javaEnabled = (typeof navigator.javaEnabled === 'function' && navigator.javaEnabled()) ? 1 : 0;
			return {
				browserJavaEnabled: javaEnabled,
				browserJavascriptEnabled: 1,
				browserLanguage: navigator.language || '',
				browserColorDepth: screen.colorDepth || '',
				browserScreenWidth: window.screen && window.screen.width ? window.screen.width : '',
				browserScreenHeight: window.screen && window.screen.height ? window.screen.height : '',
				browserTZ: new Date().getTimezoneOffset(),
				browserUserAgent: navigator.userAgent || '',
				challengeWindowSize: 3,
			};
		}

		/**
		 * Ensures hidden inputs exist on the payment form and sets their values from `collectBrowserData()`.
		 *
		 * Targets elements with class `neopayment-standard-gateway-browser` and matching `name` attributes.
		 * If inputs already exist (e.g. rendered by PHP), updates values; otherwise appends new hidden fields.
		 *
		 * @returns {void}
		 */
		function ensureBrowserData() {
			const payForm = getPayForm();
			if (!payForm.length) {
				return;
			}

			const navParams = collectBrowserData();
			Object.keys(navParams).forEach((key) => {
				const selector = `.neopayment-standard-gateway-browser[name="${key}"]`;
				const existing = payForm.find(selector);
				const value = String(navParams[key]);
				if (existing.length) {
					existing.val(value);
				} else {
					payForm.append(`<input class="neopayment-standard-gateway-browser" type="hidden" name="${key}" value="${value}" />`);
				}
			});
		}

		/**
		 * Removes Neopayment browser fingerprint hidden fields from all candidate checkout forms.
		 *
		 * Called when another payment method is selected so unrelated gateways are not polluted.
		 *
		 * @returns {void}
		 */
		function removeBrowserData() {
			$(PAY_FORM_SELECTOR).find('.neopayment-standard-gateway-browser').remove();
		}

		/**
		 * Handles payment method radio changes.
		 *
		 * @param {string} paymentMethod WooCommerce `payment_method` value (e.g. `neopayment_standard_gateway`).
		 * @returns {void}
		 */
		function onPaymentMethodChange(paymentMethod) {
			if (paymentMethod === 'neopayment_standard_gateway') {
				ensureBrowserData();
			} else {
				removeBrowserData();
			}
		}

		/**
		 * Reduces a card expiry to `MM / YY`.
		 *
		 * WooCommerce's jquery.payment formatter accepts `MM / YYYY` (up to four year digits).
		 * A complete four-digit year (paste or autofill, e.g. 2026) keeps the last two digits.
		 * A third year digit typed by hand is dropped.
		 *
		 * @param {string} value Raw expiry field value.
		 * @returns {string|null} Clamped value, or null when the value is already within MM/YY.
		 */
		function clampExpiryValue(value) {
			const raw = String(value || '');
			const parts = raw.match(/^(\d{1,2})\s*\/\s*(\d{1,4})\s*$/);
			if (parts && parts[2].length > 2) {
				const month = parts[1].padStart(2, '0');
				const year = parts[2].length === 4 ? parts[2].slice(-2) : parts[2].slice(0, 2);
				return month + ' / ' + year;
			}

			const digits = raw.replace(/\D/g, '');
			if (digits.length > 4) {
				const month = digits.slice(0, 2);
				const yearDigits = digits.slice(2);
				const year = yearDigits.length >= 4 ? yearDigits.slice(0, 4).slice(-2) : yearDigits.slice(0, 2);
				return month + ' / ' + year;
			}

			return null;
		}

		/**
		 * Stops the expiry field from accepting more than four digits (MMYY).
		 *
		 * @param {KeyboardEvent} event
		 * @returns {void}
		 */
		function blockExtraExpiryDigit(event) {
			const field = event.target;
			if (!field || !field.classList || !field.classList.contains('neopayment-card-expiry')) {
				return;
			}
			if (event.ctrlKey || event.metaKey || event.altKey) {
				return;
			}
			if (!/^\d$/.test(event.key)) {
				return;
			}

			const start = typeof field.selectionStart === 'number' ? field.selectionStart : field.value.length;
			const end = typeof field.selectionEnd === 'number' ? field.selectionEnd : field.value.length;
			const selectedDigits = field.value.slice(start, end).replace(/\D/g, '').length;
			const nextLength = field.value.replace(/\D/g, '').length - selectedDigits + 1;
			if (nextLength > 4) {
				event.preventDefault();
				event.stopPropagation();
			}
		}

		/**
		 * Binds expiry limiting after WooCommerce attaches jquery.payment,
		 * so a four-digit year cannot remain in the field.
		 *
		 * @returns {void}
		 */
		function bindExpiryLimit() {
			const $field = $('.neopayment-card-expiry');
			if (!$field.length) {
				return;
			}

			$field.off('.neopaymentExpiry');
			$field.on('paste.neopaymentExpiry', function (event) {
				const clipboard = event.originalEvent && event.originalEvent.clipboardData;
				const text = clipboard ? clipboard.getData('text') : '';
				const next = clampExpiryValue(text);
				if (next !== null) {
					event.preventDefault();
					event.stopImmediatePropagation();
					this.value = next;
				}
			});
			$field.on('input.neopaymentExpiry change.neopaymentExpiry', function () {
				const field = this;
				window.setTimeout(function () {
					const next = clampExpiryValue(field.value);
					if (next !== null && next !== field.value) {
						field.value = next;
					}
				}, 0);
			});
		}

		document.addEventListener('keydown', blockExtraExpiryDigit, true);

		$(document).ready(function () {
			$(document.body).on('wc-credit-card-form-init updated_checkout', function () {
				window.setTimeout(bindExpiryLimit, 0);
			});
			window.setTimeout(bindExpiryLimit, 0);

			$(document).on('change', 'input[type=radio][name=payment_method]', function () {
				onPaymentMethodChange($(this).val());
			});

			// Classic checkout (AJAX): Woo triggers these on `form.checkout` before serializing the request.
			$(document.body).on(
				'checkout_place_order checkout_place_order_neopayment_standard_gateway',
				'form.checkout, form[name="checkout"]',
				function () {
					ensureBrowserData();
					bindExpiryLimit();
					$('.neopayment-card-expiry').each(function () {
						const next = clampExpiryValue(this.value);
						if (next !== null) {
							this.value = next;
						}
					});
					return true;
				}
			);

			// Pay-for-order: native form POST — no `checkout_place_order`; populate fields on submit.
			$(document).on('submit', 'form#order_review', function () {
				ensureBrowserData();
				$('.neopayment-card-expiry').each(function () {
					const next = clampExpiryValue(this.value);
					if (next !== null) {
						this.value = next;
					}
				});
			});

			$(document.body).on('updated_checkout', function () {
				onPaymentMethodChange($('input[type=radio][name=payment_method]:checked').val());
			});

			onPaymentMethodChange($('input[type=radio][name=payment_method]:checked').val());
		});
	});

}());