/**
 * WP Cookie Shield — Frontend Script
 * Vanilla ES2020, no jQuery dependency.
 */
/* global wpcSettings */

(function () {
	'use strict';

	const cfg = window.wpcSettings || {};

	// ─── ConsentStore ─────────────────────────────────────────

	class ConsentStore {
		#COOKIE_NAME = 'wpcs_consent';

		read() {
			const raw = this.#getCookie(this.#COOKIE_NAME);
			if (!raw) return null;
			try {
				return JSON.parse(atob(raw));
			} catch {
				return null;
			}
		}

		isValid() {
			const data = this.read();
			if (!data) return false;
			if (data.version !== cfg.policyVersion) return false;
			if (Date.now() / 1000 > data.expires) return false;
			return true;
		}

		getCategories() {
			const data = this.read();
			return data ? data.categories : this.#defaults();
		}

		save(categories, method) {
			const now     = Math.floor(Date.now() / 1000);
			const expires = now + (cfg.expiryDays || 365) * 86400;
			const uuid    = this.#getOrCreateUUID();

			const payload = {
				uuid,
				version:    cfg.policyVersion,
				ts:         now,
				expires,
				categories,
				method,
			};

			const encoded = btoa(JSON.stringify(payload));
			const expDate = new Date(expires * 1000).toUTCString();
			const secure  = location.protocol === 'https:' ? '; Secure' : '';

			document.cookie = `${this.#COOKIE_NAME}=${encoded}; expires=${expDate}; path=/; SameSite=Lax${secure}`;

			return { uuid, payload };
		}

		#defaults() {
			const cats = {};
			(cfg.categories || []).forEach(k => { cats[k] = k === 'essential'; });
			return cats;
		}

		#getCookie(name) {
			const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
			return match ? decodeURIComponent(match[1]) : null;
		}

		#getOrCreateUUID() {
			const data = this.read();
			if (data && data.uuid) return data.uuid;
			return crypto.randomUUID ? crypto.randomUUID() : this.#fallbackUUID();
		}

		#fallbackUUID() {
			return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
				const r = Math.random() * 16 | 0;
				return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
			});
		}
	}

	// ─── GCMHandler ───────────────────────────────────────────

	class GCMHandler {
		fireDefaults() {
			// GCM defaults are output server-side; nothing to do here on the
			// JS side unless gtag fires after DOMContentLoaded.
		}

		fireUpdate(categories) {
			if (typeof gtag !== 'function') return;

			gtag('consent', 'update', {
				analytics_storage:       categories.statistics  ? 'granted' : 'denied',
				ad_storage:              categories.marketing   ? 'granted' : 'denied',
				ad_user_data:            categories.marketing   ? 'granted' : 'denied',
				ad_personalization:      categories.marketing   ? 'granted' : 'denied',
				functionality_storage:   categories.preferences ? 'granted' : 'denied',
				personalization_storage: categories.preferences ? 'granted' : 'denied',
				security_storage:        'granted',
			});

			document.dispatchEvent(new CustomEvent('wpcs:gcm_updated', {
				detail: { categories },
			}));
		}
	}

	// ─── ScriptBlocker ────────────────────────────────────────

	class ScriptBlocker {
		release(grantedCategories) {
			document.querySelectorAll('script[type="text/plain"][data-wpcs-src]').forEach(el => {
				const cat = el.dataset.wpcsCategory;
				if (!grantedCategories[cat]) return;

				const s = document.createElement('script');
				s.src   = el.dataset.wpcsSrc;

				el.getAttributeNames()
					.filter(a => !['type', 'data-wpcs-src', 'data-wpcs-category'].includes(a))
					.forEach(a => s.setAttribute(a, el.getAttribute(a)));

				el.parentNode.replaceChild(s, el);
			});

			document.querySelectorAll('script[type="text/plain"][data-wpcs-inline]').forEach(el => {
				const cat = el.dataset.wpcsCategory;
				if (!grantedCategories[cat]) return;

				const s = document.createElement('script');
				s.textContent = el.textContent;
				el.parentNode.replaceChild(s, el);
			});
		}
	}

	// ─── ConsentLogger ────────────────────────────────────────

	class ConsentLogger {
		async log(uuid, categories, method) {
			try {
				await fetch(cfg.restUrl + '/consent', {
					method:  'POST',
					headers: { 'Content-Type': 'application/json' },
					body:    JSON.stringify({
						nonce: cfg.nonce,
						uuid,
						categories,
						method,
						version: cfg.policyVersion,
					}),
				});
			} catch {
				// Non-critical; consent is stored in cookie regardless
			}
		}
	}

	// ─── Banner ───────────────────────────────────────────────

	class Banner {
		#el;
		constructor() {
			this.#el = document.getElementById('wpcs-banner');
		}

		show() {
			if (!this.#el) return;
			this.#el.removeAttribute('hidden');
		}

		hide() {
			if (!this.#el) return;
			this.#el.setAttribute('hidden', '');
			document.dispatchEvent(new CustomEvent('wpcs:banner_hidden'));
		}
	}

	// ─── Modal ────────────────────────────────────────────────

	class Modal {
		#overlay;
		#modal;
		#openers = [];

		constructor() {
			this.#overlay = document.getElementById('wpcs-modal-overlay');
			this.#modal   = document.getElementById('wpcs-modal');
		}

		open() {
			if (!this.#overlay) return;
			this.#overlay.removeAttribute('aria-hidden');
			this.#modal.removeAttribute('hidden');
			this.#modal.focus();
			this.#trapFocus();
			document.dispatchEvent(new CustomEvent('wpcs:modal_opened'));
		}

		close() {
			if (!this.#overlay) return;
			this.#overlay.setAttribute('aria-hidden', 'true');
			this.#modal.setAttribute('hidden', '');
			document.dispatchEvent(new CustomEvent('wpcs:modal_closed'));
		}

		#trapFocus() {
			const focusable = this.#modal.querySelectorAll(
				'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
			);
			if (!focusable.length) return;

			const first = focusable[0];
			const last  = focusable[focusable.length - 1];

			this.#modal.addEventListener('keydown', e => {
				if (e.key !== 'Tab') {
					if (e.key === 'Escape') this.close();
					return;
				}
				if (e.shiftKey && document.activeElement === first) {
					e.preventDefault(); last.focus();
				} else if (!e.shiftKey && document.activeElement === last) {
					e.preventDefault(); first.focus();
				}
			}, { once: false });
		}
	}

	// ─── Boot ─────────────────────────────────────────────────

	const store   = new ConsentStore();
	const gcm     = new GCMHandler();
	const blocker = new ScriptBlocker();
	const banner  = new Banner();
	const modal   = new Modal();
	const logger  = new ConsentLogger();

	function applyConsent(categories, method) {
		const { uuid } = store.save(categories, method);
		gcm.fireUpdate(categories);
		blocker.release(categories);
		banner.hide();
		modal.close();
		logger.log(uuid, categories, method);

		document.dispatchEvent(new CustomEvent('wpcs:consent_saved', {
			detail: { categories, method },
		}));
	}

	function acceptAll() {
		const cats = {};
		(cfg.categories || []).forEach(k => { cats[k] = true; });
		applyConsent(cats, 'accept_all');
	}

	function rejectAll() {
		const cats = {};
		(cfg.categories || []).forEach(k => { cats[k] = k === 'essential'; });

		// Honour auto-deny (DNT / GPC) — already the default, but be explicit
		applyConsent(cats, 'reject_all');
	}

	function saveCustom() {
		const cats = { essential: true };

		document.querySelectorAll('.wpcs-toggle[data-category]').forEach(toggle => {
			const cat = toggle.dataset.category;
			cats[cat] = toggle.classList.contains('wpcs-toggle--on');
		});

		applyConsent(cats, 'custom');
	}

	document.addEventListener('DOMContentLoaded', () => {
		if (store.isValid()) {
			const cats = store.getCategories();
			gcm.fireUpdate(cats);
			blocker.release(cats);
		} else {
			// No valid consent — show banner regardless of how the page was served.
			// This path runs on every visitor including those served from a full-page
			// cache (WP Engine, etc.) where PHP-side checks are unreliable.
			banner.show();
			document.dispatchEvent(new CustomEvent('wpcs:banner_shown'));
		}

		// Banner buttons
		document.getElementById('wpcs-accept-all')?.addEventListener('click', acceptAll);
		document.getElementById('wpcs-reject-all')?.addEventListener('click', rejectAll);
		document.getElementById('wpcs-open-prefs')?.addEventListener('click', () => modal.open());

		// Modal buttons
		document.getElementById('wpcs-modal-accept-all')?.addEventListener('click', acceptAll);
		document.getElementById('wpcs-modal-close')?.addEventListener('click', () => modal.close());
		document.getElementById('wpcs-modal-close-btn')?.addEventListener('click', () => modal.close());
		document.getElementById('wpcs-modal-save')?.addEventListener('click', saveCustom);

		// Shortcode / theme links
		document.querySelectorAll('.wpcs-open-modal').forEach(el => {
			el.addEventListener('click', e => { e.preventDefault(); modal.open(); });
		});

		// Toggle click/keyboard
		document.querySelectorAll('.wpcs-toggle:not(.wpcs-toggle--locked)').forEach(toggle => {
			function handleToggle() {
				toggle.classList.toggle('wpcs-toggle--on');
				const on = toggle.classList.contains('wpcs-toggle--on');
				toggle.setAttribute('aria-checked', String(on));
			}
			toggle.addEventListener('click', handleToggle);
			toggle.addEventListener('keydown', e => {
				if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); handleToggle(); }
			});
		});

		// Accordion
		document.querySelectorAll('.wpcs-accordion__header').forEach(btn => {
			btn.addEventListener('click', () => {
				const body     = document.getElementById(btn.getAttribute('aria-controls'));
				if (!body) return;
				const expanded = btn.getAttribute('aria-expanded') === 'true';
				btn.setAttribute('aria-expanded', String(!expanded));
				body.hidden = expanded;
			});
		});

		// Overlay click outside modal
		document.getElementById('wpcs-modal-overlay')?.addEventListener('click', e => {
			if (e.target === e.currentTarget) modal.close();
		});
	});

	// Public API
	window.WPCookieShield = { store, banner, modal, gcm, acceptAll, rejectAll };

}());
