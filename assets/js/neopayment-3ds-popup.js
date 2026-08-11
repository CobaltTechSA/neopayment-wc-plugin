jQuery(
	($) => {
		const __ = (window.wp && window.wp.i18n && window.wp.i18n.__)
			? window.wp.i18n.__
			: (s) => s;

		/**
		 * Toggle challenge UI:
		 * - false (default): open 3DS in a browser popup window.
		 * - true: iframe modal flow.
		 */
		const USE_3DS_MODAL = false;

		const MODAL_WIDTH = 560;
		const MODAL_HEIGHT = 760;
		const openedChallenges = new Set();
		let modalElements = null;
		let popupWaitElements = null;
		let iframeLoadTimer = null;
		let fallbackCountdownTimer = null;
		let processingHintTimer = null;
		let popupWatchTimer = null;
		let activeChallengeUrl = null;
		let activeChallengePopup = null;
		let frameLoadCount = 0;
		let callbackHandled = false;
		let blockPageNavigation = false;
		/** True once a popup window actually opened (not blocked). */
		let popupDidOpen = false;
		/** True when the shopper closed the popup before finishing 3DS. */
		let popupClosedByUser = false;

		/* ------------------------------------------------------------------ */
		/* Modal */
		/* ------------------------------------------------------------------ */

		function ensure3DSModal() {
			if (modalElements) {
				return modalElements;
			}
			if (!document.getElementById('neopayment-3ds-inline-styles')) {
				const style = document.createElement('style');
				style.id = 'neopayment-3ds-inline-styles';
				style.textContent = '@keyframes neopayment3dsspin { to { transform: rotate(360deg); } }';
				document.head.appendChild(style);
			}

			const overlay = document.createElement('div');
			overlay.className = 'neopayment-3ds-modal-overlay';
			overlay.style.position = 'fixed';
			overlay.style.inset = '0';
			overlay.style.display = 'none';
			overlay.style.alignItems = 'center';
			overlay.style.justifyContent = 'center';
			overlay.style.background = 'rgba(0,0,0,.65)';
			overlay.style.zIndex = '99999';
			overlay.style.padding = '16px';

			const container = document.createElement('div');
			container.className = 'neopayment-3ds-modal';
			container.setAttribute('role', 'dialog');
			container.setAttribute('aria-modal', 'true');
			container.style.width = `${MODAL_WIDTH}px`;
			container.style.maxWidth = '95vw';
			container.style.position = 'relative';
			container.style.background = '#fff';
			container.style.borderRadius = '10px';
			container.style.boxShadow = '0 16px 50px rgba(0,0,0,.35)';
			container.style.overflow = 'hidden';

			const header = document.createElement('div');
			header.className = 'neopayment-3ds-modal__header';
			header.style.padding = '14px 16px';
			header.style.fontSize = '16px';
			header.style.fontWeight = '600';
			header.style.color = '#22324a';
			header.style.borderBottom = '1px solid #e7ebf0';
			header.style.background = '#f8fafc';
			header.style.display = 'flex';
			header.style.alignItems = 'center';
			header.style.justifyContent = 'space-between';

			const title = document.createElement('span');
			title.textContent = __('Verificación de seguridad', 'neopayment-payment-gateway');

			const iframe = document.createElement('iframe');
			iframe.className = 'neopayment-3ds-modal__frame';
			iframe.setAttribute('title', __('Verificación de seguridad', 'neopayment-payment-gateway'));
			iframe.setAttribute('allow', 'payment');
			iframe.style.height = `${MODAL_HEIGHT}px`;
			iframe.style.maxHeight = '80vh';
			iframe.style.display = 'block';
			iframe.style.width = '100%';
			iframe.style.border = '0';
			iframe.style.background = '#fff';

			const processingLayer = document.createElement('div');
			processingLayer.className = 'neopayment-3ds-modal__processing';
			processingLayer.style.display = 'none';
			processingLayer.style.position = 'absolute';
			processingLayer.style.inset = '0';
			processingLayer.style.background = 'rgba(255,255,255,.95)';
			processingLayer.style.alignItems = 'center';
			processingLayer.style.justifyContent = 'center';
			processingLayer.style.flexDirection = 'column';
			processingLayer.style.gap = '10px';
			processingLayer.style.zIndex = '2';

			const processingSpinner = document.createElement('div');
			processingSpinner.style.width = '36px';
			processingSpinner.style.height = '36px';
			processingSpinner.style.border = '4px solid #d7deea';
			processingSpinner.style.borderTopColor = '#2f6fb3';
			processingSpinner.style.borderRadius = '50%';
			processingSpinner.style.animation = 'neopayment3dsspin 0.9s linear infinite';

			const processingText = document.createElement('p');
			processingText.textContent = __('Estamos verificando tu pago. Un momento, por favor…', 'neopayment-payment-gateway');
			processingText.style.margin = '0';
			processingText.style.fontSize = '14px';
			processingText.style.color = '#334155';
			processingText.style.textAlign = 'center';
			processingText.style.padding = '0 18px';

			processingLayer.appendChild(processingSpinner);
			processingLayer.appendChild(processingText);

			const status = document.createElement('div');
			status.className = 'neopayment-3ds-modal__status';
			status.style.display = 'none';
			status.style.padding = '10px 14px';
			status.style.fontSize = '13px';
			status.style.color = '#49566d';
			status.style.borderTop = '1px solid #e7ebf0';
			status.style.background = '#f8fafc';

			const footer = document.createElement('div');
			footer.className = 'neopayment-3ds-modal__footer';
			footer.style.display = 'flex';
			footer.style.justifyContent = 'space-between';
			footer.style.gap = '10px';
			footer.style.alignItems = 'center';
			footer.style.padding = '10px 14px 14px';
			footer.style.background = '#f8fafc';

			const openWindowBtn = document.createElement('button');
			openWindowBtn.type = 'button';
			openWindowBtn.className = 'neopayment-3ds-modal__open-window';
			openWindowBtn.textContent = __('Abrir en otra ventana', 'neopayment-payment-gateway');
			openWindowBtn.style.border = '1px solid #ccd5e2';
			openWindowBtn.style.background = '#fff';
			openWindowBtn.style.color = '#22324a';
			openWindowBtn.style.padding = '8px 12px';
			openWindowBtn.style.borderRadius = '6px';
			openWindowBtn.style.fontSize = '13px';
			openWindowBtn.style.cursor = 'pointer';
			openWindowBtn.style.display = 'none';

			const cancelBtn = document.createElement('button');
			cancelBtn.type = 'button';
			cancelBtn.className = 'neopayment-3ds-modal__cancel';
			cancelBtn.textContent = __('Cancelar', 'neopayment-payment-gateway');
			cancelBtn.style.border = '1px solid #ccd5e2';
			cancelBtn.style.background = '#fff';
			cancelBtn.style.color = '#22324a';
			cancelBtn.style.padding = '8px 12px';
			cancelBtn.style.borderRadius = '6px';
			cancelBtn.style.fontSize = '13px';
			cancelBtn.style.cursor = 'pointer';

			footer.appendChild(openWindowBtn);
			footer.appendChild(cancelBtn);
			header.appendChild(title);
			container.appendChild(header);
			container.appendChild(iframe);
			container.appendChild(processingLayer);
			container.appendChild(status);
			container.appendChild(footer);
			overlay.appendChild(container);
			document.body.appendChild(overlay);

			cancelBtn.addEventListener('click', () => {
				// Allow retry for the same challenge URL after a manual close.
				if (activeChallengeUrl) {
					openedChallenges.delete(activeChallengeUrl);
				}
				close3DSModal();
				window.location.reload();
			});
			openWindowBtn.addEventListener('click', () => {
				if (!activeChallengeUrl) {
					return;
				}
				window.open(activeChallengeUrl, '_blank', `width=${MODAL_WIDTH},height=${MODAL_HEIGHT}`);
			});

			modalElements = { overlay, iframe, status, processingLayer, openWindowBtn };
			return modalElements;
		}

		function showProcessingLayer(show) {
			if (!modalElements) {
				return;
			}
			modalElements.processingLayer.style.display = show ? 'flex' : 'none';
		}

		function toggleOpenWindowButton(show) {
			if (!modalElements) {
				return;
			}
			modalElements.openWindowBtn.style.display = show ? 'inline-block' : 'none';
		}

		function setModalStatus(text, show = true) {
			if (!modalElements) {
				return;
			}
			modalElements.status.textContent = text || '';
			modalElements.status.style.display = show ? 'block' : 'none';
		}

		function clearFallbackCountdown() {
			if (fallbackCountdownTimer) {
				clearInterval(fallbackCountdownTimer);
				fallbackCountdownTimer = null;
			}
		}

		function clearProcessingHint() {
			if (processingHintTimer) {
				clearTimeout(processingHintTimer);
				processingHintTimer = null;
			}
		}

		function startFallbackCountdown(seconds) {
			let remaining = seconds;
			setModalStatus(
				__('Cargando la verificación… Si tarda mucho, podrás abrirla en otra ventana en', 'neopayment-payment-gateway') +
					` ${remaining}s`
			);
			clearFallbackCountdown();
			fallbackCountdownTimer = setInterval(() => {
				remaining -= 1;
				if (remaining <= 0) {
					clearFallbackCountdown();
					return;
				}
				setModalStatus(
					__('Cargando la verificación… Si tarda mucho, podrás abrirla en otra ventana en', 'neopayment-payment-gateway') +
						` ${remaining}s`
				);
			}, 1000);
		}

		function open3DSModal(url) {
			const { overlay, iframe } = ensure3DSModal();
			activeChallengeUrl = url;
			frameLoadCount = 0;
			callbackHandled = false;
			showProcessingLayer(false);
			toggleOpenWindowButton(false);
			iframe.src = url;
			overlay.classList.add('is-open');
			overlay.style.display = 'flex';
			document.body.classList.add('neopayment-3ds-modal-open');
			blockPageNavigation = true;
			startFallbackCountdown(7);

			if (iframeLoadTimer) {
				clearTimeout(iframeLoadTimer);
			}
			iframeLoadTimer = setTimeout(() => {
				console.warn('[NEOPAYMENT-3DS] Iframe 3DS no cargó a tiempo.');
				clearFallbackCountdown();
				showProcessingLayer(false);
				toggleOpenWindowButton(true);
				setModalStatus(__('Esto está tardando más de lo habitual. Puedes abrirlo en otra ventana.', 'neopayment-payment-gateway'));
			}, 7000);

			iframe.onload = () => {
				frameLoadCount += 1;
				if (iframeLoadTimer) {
					clearTimeout(iframeLoadTimer);
					iframeLoadTimer = null;
				}
				clearFallbackCountdown();
				// First load is normally the challenge form. Next loads are usually post-submit redirects.
				if (frameLoadCount <= 1) {
					showProcessingLayer(false);
					toggleOpenWindowButton(false);
					setModalStatus('', false);
				} else if (!callbackHandled) {
					// Do not block the iframe on intermediate ACS reloads (e.g. wrong OTP retry screens).
					// Keep the challenge usable and only show a light hint.
					showProcessingLayer(false);
					toggleOpenWindowButton(false);
					setModalStatus(__('Estamos verificando tu pago. Un momento, por favor…', 'neopayment-payment-gateway'));
				}
			};

			clearProcessingHint();
		}

		function close3DSModal() {
			if (!modalElements) {
				return;
			}
			modalElements.overlay.classList.remove('is-open');
			modalElements.overlay.style.display = 'none';
			modalElements.iframe.src = 'about:blank';
			activeChallengeUrl = null;
			frameLoadCount = 0;
			callbackHandled = false;
			showProcessingLayer(false);
			toggleOpenWindowButton(false);
			clearFallbackCountdown();
			clearProcessingHint();
			setModalStatus('', false);
			document.body.classList.remove('neopayment-3ds-modal-open');
			blockPageNavigation = false;
			if (iframeLoadTimer) {
				clearTimeout(iframeLoadTimer);
				iframeLoadTimer = null;
			}
		}

		/* ------------------------------------------------------------------ */
		/* Popup flow (default)                                               */
		/* ------------------------------------------------------------------ */

		function ensurePopupWaitPanel() {
			if (popupWaitElements) {
				return popupWaitElements;
			}

			const overlay = document.createElement('div');
			overlay.className = 'neopayment-3ds-popup-wait';
			overlay.style.position = 'fixed';
			overlay.style.inset = '0';
			overlay.style.display = 'none';
			overlay.style.alignItems = 'center';
			overlay.style.justifyContent = 'center';
			overlay.style.background = 'rgba(0,0,0,.55)';
			overlay.style.zIndex = '99998';
			overlay.style.padding = '16px';

			const card = document.createElement('div');
			card.style.maxWidth = '420px';
			card.style.width = '100%';
			card.style.background = '#fff';
			card.style.borderRadius = '10px';
			card.style.boxShadow = '0 16px 50px rgba(0,0,0,.35)';
			card.style.padding = '20px 18px';
			card.style.textAlign = 'center';

			const title = document.createElement('h3');
			title.textContent = __('Verificación de seguridad', 'neopayment-payment-gateway');
			title.style.margin = '0 0 10px';
			title.style.fontSize = '18px';
			title.style.color = '#22324a';

			const text = document.createElement('p');
			text.className = 'neopayment-3ds-popup-wait__text';
			text.style.margin = '0 0 16px';
			text.style.fontSize = '14px';
			text.style.lineHeight = '1.45';
			text.style.color = '#475569';

			const actions = document.createElement('div');
			actions.style.display = 'flex';
			actions.style.gap = '10px';
			actions.style.justifyContent = 'center';
			actions.style.flexWrap = 'wrap';

			const reopenBtn = document.createElement('button');
			reopenBtn.type = 'button';
			reopenBtn.textContent = __('Intentarlo nuevamente', 'neopayment-payment-gateway');
			reopenBtn.style.border = '1px solid #2f6fb3';
			reopenBtn.style.background = '#2f6fb3';
			reopenBtn.style.color = '#fff';
			reopenBtn.style.padding = '8px 12px';
			reopenBtn.style.borderRadius = '6px';
			reopenBtn.style.fontSize = '13px';
			reopenBtn.style.cursor = 'pointer';

			const cancelBtn = document.createElement('button');
			cancelBtn.type = 'button';
			cancelBtn.textContent = __('Cancelar', 'neopayment-payment-gateway');
			cancelBtn.style.border = '1px solid #ccd5e2';
			cancelBtn.style.background = '#fff';
			cancelBtn.style.color = '#22324a';
			cancelBtn.style.padding = '8px 12px';
			cancelBtn.style.borderRadius = '6px';
			cancelBtn.style.fontSize = '13px';
			cancelBtn.style.cursor = 'pointer';

			reopenBtn.addEventListener('click', () => {
				if (!activeChallengeUrl) {
					return;
				}

				// Challenge URLs are typically one-time.
				if (popupDidOpen || popupClosedByUser) {
					closeChallengePopup(true);
					showUserMessage(
						__('Verificación incompleta', 'neopayment-payment-gateway'),
						__(
							'Cerraste la ventana de verificación. Inténtalo nuevamente para continuar.',
							'neopayment-payment-gateway'
						),
						true
					);
					return;
				}

				// Popup was blocked and never opened, safe to retry the same URL.
				start3DSChallenge(activeChallengeUrl);
			});
			cancelBtn.addEventListener('click', () => {
				if (activeChallengeUrl) {
					openedChallenges.delete(activeChallengeUrl);
				}
				closeChallengePopup(true);
				window.location.reload();
			});

			actions.appendChild(reopenBtn);
			actions.appendChild(cancelBtn);
			card.appendChild(title);
			card.appendChild(text);
			card.appendChild(actions);
			overlay.appendChild(card);
			document.body.appendChild(overlay);

			popupWaitElements = { overlay, text, reopenBtn };
			return popupWaitElements;
		}

		function showPopupWaitPanel(message, options = {}) {
			const panel = ensurePopupWaitPanel();
			panel.text.textContent = message;
			const reopenLabel = options.reopenLabel || __('Intentarlo nuevamente', 'neopayment-payment-gateway');
			panel.reopenBtn.textContent = reopenLabel;
			panel.overlay.style.display = 'flex';
			document.body.classList.add('neopayment-3ds-modal-open');
			blockPageNavigation = true;
		}

		function hidePopupWaitPanel() {
			if (!popupWaitElements) {
				return;
			}
			popupWaitElements.overlay.style.display = 'none';
			document.body.classList.remove('neopayment-3ds-modal-open');
			blockPageNavigation = false;
		}

		function clearPopupWatch() {
			if (popupWatchTimer) {
				clearInterval(popupWatchTimer);
				popupWatchTimer = null;
			}
		}

		function isPopupBlocked(popup) {
			if (!popup) {
				return true;
			}
			try {
				return popup.closed === true;
			} catch (e) {
				return true;
			}
		}

		function notifyPopupBlocked() {
			showUserMessage(
				__('No se pudo abrir la verificación', 'neopayment-payment-gateway'),
				__(
					'Tu navegador bloqueó una ventana necesaria para verificar el pago. Permite ventanas emergentes en este sitio e inténtalo nuevamente.',
					'neopayment-payment-gateway'
				),
				false
			);
			showPopupWaitPanel(
				__(
					'No pudimos abrir la verificación. Permite ventanas emergentes en tu navegador y pulsa Intentarlo nuevamente.',
					'neopayment-payment-gateway'
				)
			);
		}

		function openChallengePopup(url) {
			activeChallengeUrl = url;
			callbackHandled = false;
			popupClosedByUser = false;

			let popup = null;
			try {
				// Unique name avoids reusing a closing/closed window handle.
				popup = window.open(
					url,
					`neopayment_3ds_${Date.now()}`,
					`width=${MODAL_WIDTH},height=${MODAL_HEIGHT},scrollbars=yes,resizable=yes`
				);
			} catch (e) {
				popup = null;
			}

			activeChallengePopup = popup;

			// Some browsers return a Window even when blocked; re-check shortly after.
			window.setTimeout(() => {
				if (callbackHandled) {
					return;
				}
				if (isPopupBlocked(popup)) {
					activeChallengePopup = null;
					popupDidOpen = false;
					notifyPopupBlocked();
					return;
				}

				popupDidOpen = true;
				showPopupWaitPanel(
					__(
						'Completa la verificación en la ventana que se abrió. Mantén esta página abierta hasta terminar.',
						'neopayment-payment-gateway'
					)
				);

				clearPopupWatch();
				popupWatchTimer = window.setInterval(() => {
					if (callbackHandled) {
						clearPopupWatch();
						return;
					}
					if (isPopupBlocked(activeChallengePopup)) {
						clearPopupWatch();
						activeChallengePopup = null;
						popupClosedByUser = true;
						if (!callbackHandled) {
							showPopupWaitPanel(
								__(
									'Cerraste la ventana de verificación. Pulsa Intentarlo nuevamente para continuar.',
									'neopayment-payment-gateway'
								),
								{
									reopenLabel: __('Intentarlo nuevamente', 'neopayment-payment-gateway'),
								}
							);
						}
					}
				}, 700);
			}, 350);

			return popup;
		}

		function closeChallengePopup(forceCloseWindow = false) {
			clearPopupWatch();
			hidePopupWaitPanel();
			if (forceCloseWindow && activeChallengePopup && !activeChallengePopup.closed) {
				try {
					activeChallengePopup.close();
				} catch (e) {
					// Ignore cross-window close errors.
				}
			}
			activeChallengePopup = null;
			activeChallengeUrl = null;
			callbackHandled = false;
			blockPageNavigation = false;
			popupDidOpen = false;
			popupClosedByUser = false;
		}

		/**
		 * Single entry point for classic / blocks / order-pay challenge opening.
		 */
		function start3DSChallenge(url) {
			if (!url) {
				return;
			}
			if (USE_3DS_MODAL) {
				open3DSModal(url);
				return;
			}
			openChallengePopup(url);
		}

		function handlePopupEvents() {
			function asObject(maybeJson) {
				if (!maybeJson) {
					return null;
				}
				if (typeof maybeJson === 'string') {
					try {
						return JSON.parse(maybeJson);
					} catch (error) {
						return null;
					}
				}
				return typeof maybeJson === 'object' ? maybeJson : null;
			}

			function extractChallengePayload(argsLike) {
				const args = Array.from(argsLike || []);
				for (let i = 0; i < args.length; i += 1) {
					const candidate = asObject(args[i]);
					if (!candidate) {
						continue;
					}
					if (candidate.requires_challenge && candidate.challenge_url) {
						return candidate;
					}
					if (candidate.result && candidate.result.requires_challenge && candidate.result.challenge_url) {
						return candidate.result;
					}
					if (candidate.responseJSON?.requires_challenge && candidate.responseJSON?.challenge_url) {
						return candidate.responseJSON;
					}
					if (candidate.data?.requires_challenge && candidate.data?.challenge_url) {
						return candidate.data;
					}
				}
				return null;
			}

			function openChallengeFromPayload(payload, event = null) {
				const challengeUrl = payload?.challenge_url || '';
				if (!challengeUrl) {
					return true;
				}
				if (!openedChallenges.has(challengeUrl)) {
					openedChallenges.add(challengeUrl);
					start3DSChallenge(challengeUrl);
				}
				if (event) {
					event.preventDefault();
					event.stopImmediatePropagation();
				}
				return false;
			}

			// Classic checkout: intercept Woo successful response and prevent page reload
			// while the 3DS challenge is in progress.
			$(document.body).on('checkout_place_order_success', function (event) {
				const payload = extractChallengePayload(arguments);
				if (!payload) {
					return true;
				}
				return openChallengeFromPayload(payload, event);
			});

			// Fallback for themes/plugins that bypass checkout_place_order_success flow.
			// Includes `pay_for_order` when WooCommerce processes order-pay via AJAX.
			$(document).ajaxComplete((event, xhr, settings) => {
				const ajaxUrl = settings?.url || '';
				if (
					!ajaxUrl.includes('wc-ajax=checkout') &&
					!ajaxUrl.includes('wc-ajax=pay_for_order')
				) {
					return;
				}
				const payload = asObject(xhr?.responseText);
				if (payload?.requires_challenge && payload?.challenge_url) {
					openChallengeFromPayload(payload, event);
				}
			});

			// Intercept fetch (checkout by blocks).
			if (window.fetch) {
				const originalFetch = window.fetch;
				window.fetch = async function (input, init) {
					const response = await originalFetch(input, init);
					const url = typeof input === 'string' ? input : input.url;

					if (url.includes('/wc/store/v1/checkout')) {
						const contentType = response.headers.get('content-type') || '';
						if (contentType.includes('application/json')) {
							try {
								const json = await response.clone().json();
								handleCheckoutResponse(json);
							} catch (error) {
								console.error('[NEOPAYMENT-3DS] Error parsing fetch response:', error);
							}
						}
					}

					return response;
				};
			}
		}
		function handleCheckoutResponse(response) {
			// Classic checkout.
			if (response.requires_challenge && response.challenge_url) {
				if (openedChallenges.has(response.challenge_url)) {
					return;
				}
				openedChallenges.add(response.challenge_url);
				start3DSChallenge(response.challenge_url);
				return;
			}

			// Checkout blocks.
			if (response.payment_result?.payment_details) {
				const details = response.payment_result.payment_details.reduce(
					(acc, { key, value }) => {
						acc[key] = value;
						return acc;
					},
					{}
				);

				const challengeOn =
					details.requires_challenge === '1' ||
					details.requires_challenge === 1 ||
					details.requires_challenge === true ||
					details.requires_challenge === 'true';
				if (challengeOn && details.challenge_url) {
					if (openedChallenges.has(details.challenge_url)) {
						return;
					}
					openedChallenges.add(details.challenge_url);
					start3DSChallenge(details.challenge_url);
					return;
				}

				if (details.redirect) {
					window.location.href = details.redirect;
					return;
				}
			}

			if (response.result === 'success' && response.redirect) {
				window.location.href = response.redirect;
			}
		}
		function showUserMessage(title, text, reloadOnAcknowledge = false) {
			console.warn(`[NEOPAYMENT - 3DS] ${title}: ${text}`);
			if (typeof window.swal === 'function') {
				window
					.swal({ title, text, icon: 'warning', button: __('Entendido', 'neopayment-payment-gateway') })
					.then(() => {
						if (reloadOnAcknowledge) {
							window.location.reload();
						}
					});
			} else if (window.Swal && typeof window.Swal.fire === 'function') {
				window.Swal.fire({
					title,
					text,
					icon: 'warning',
					confirmButtonText: __('Entendido', 'neopayment-payment-gateway'),
				}).then(() => {
					if (reloadOnAcknowledge) {
						window.location.reload();
					}
				});
			} else {
				alert(`${title}\n\n${text}`);
				if (reloadOnAcknowledge) {
					window.location.reload();
				}
			}
		}
		function initMessageHandler() {
			window.addEventListener('beforeunload', (event) => {
				if (!blockPageNavigation) {
					return;
				}
				event.preventDefault();
				event.returnValue = '';
			});

			window.addEventListener(
				'message',
				(event) => {
					// Only accept completion messages from our own callback page.
					if (event.origin !== window.location.origin) {
						return;
					}
					if (!event.data?.neopayment3ds || event.data?.source !== 'neopayment_3ds_handler') {
						return;
					}

					const fromIframe =
						modalElements && event.source === modalElements.iframe.contentWindow;
					const fromPopup =
						activeChallengePopup && event.source === activeChallengePopup;
					if (!fromIframe && !fromPopup) {
						return;
					}

					callbackHandled = true;
					if (USE_3DS_MODAL) {
						close3DSModal();
					} else {
						closeChallengePopup(true);
					}

					if (event.data.neopayment3ds === 'success') {
						window.location.href = event.data.redirect_to || window.location.href;
					} else {
						console.warn('[NEOPAYMENT-3DS] Authentication failed.');
						showUserMessage(
							__('No se pudo verificar el pago', 'neopayment-payment-gateway'),
							__(
								'No pudimos completar la verificación. Inténtalo nuevamente. Si el problema continúa, contacta a tu banco.',
								'neopayment-payment-gateway'
							),
							true
						);
					}
				}
			);
		}
		/**
		 * Pay-for-order: WooCommerce does a full document redirect (not `wc-ajax=checkout`), so the JSON with
		 * `challenge_url` is never seen by JS. PHP stores the URL in order meta and passes it as `pending_challenge_url`.
		 */
		function resumePendingChallengeFromServer() {
			const raw =
				typeof window.neopayment_3DS !== 'undefined' && window.neopayment_3DS.pending_challenge_url
					? String(window.neopayment_3DS.pending_challenge_url)
					: '';
			if (!raw || !/^https?:\/\//i.test(raw)) {
				return;
			}
			if (openedChallenges.has(raw)) {
				return;
			}
			openedChallenges.add(raw);
			start3DSChallenge(raw);
			try {
				const u = new URL(window.location.href);
				if (u.searchParams.get('neopayment_open_3ds') === '1') {
					u.searchParams.delete('neopayment_open_3ds');
					const q = u.searchParams.toString();
					history.replaceState(null, '', u.pathname + (q ? `?${q}` : '') + u.hash);
				}
			} catch (e) {
				// IE / very old browsers without URL — leave query string as-is.
			}
		}

		$(document).ready(
			() => {
				handlePopupEvents();
				initMessageHandler();
				resumePendingChallengeFromServer();
			}
		);
	}
);
