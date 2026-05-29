/* global wpcsAdmin, jQuery */
(function ($) {
	'use strict';

	const categoryBadgeClass = {
		essential:   'wpcs-badge-essential',
		statistics:  'wpcs-badge-statistics',
		marketing:   'wpcs-badge-marketing',
		preferences: 'wpcs-badge-preferences',
	};

	function buildCookieRows(cookies) {
		if (!cookies || cookies.length === 0) {
			return '<tr><td colspan="5">No cookies found.</td></tr>';
		}
		return cookies.map(function (c) {
			const cat      = c.category || '';
			const badgeCls = categoryBadgeClass[cat] || '';
			return '<tr>' +
				'<td><code>' + escHtml(c.cookie_name) + '</code></td>' +
				'<td>' + escHtml(c.provider) + '</td>' +
				'<td><span class="wpcs-badge ' + badgeCls + '">' + escHtml(cat) + '</span></td>' +
				'<td>' + escHtml(c.duration) + '</td>' +
				'<td>' + escHtml(c.source) + '</td>' +
				'</tr>';
		}).join('');
	}

	function escHtml(str) {
		return String(str || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	$(function () {

		// Run cookie scan
		$('#wpcs-run-scan').on('click', function () {
			const $btn    = $(this);
			const $status = $('#wpcs-scan-status');
			const $wrap   = $('#wpcs-cookies-wrap');

			$btn.prop('disabled', true).text('Scanning…');
			$status.text('');

			$.ajax({
				url:    wpcsAdmin.restUrl + '/scan',
				method: 'POST',
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', wpcsAdmin.restNonce);
				},
				success: function (res) {
					$status.text('Found ' + res.count + ' cookie' + (res.count !== 1 ? 's' : '') + '.');
					$('#wpcs-last-scan-text').text('Last scanned: just now');

					// If there's already a table, update its tbody
					if ($('#wpcs-cookies-table').length) {
						$('#wpcs-cookies-tbody').html(buildCookieRows(res.cookies));
					} else {
						// First scan — build the full table
						$('#wpcs-cookies-empty').remove();
						$wrap.html(
							'<table class="widefat fixed striped" id="wpcs-cookies-table">' +
							'<thead><tr>' +
							'<th>Cookie Name</th><th>Provider</th><th>Category</th><th>Duration</th><th>Source</th>' +
							'</tr></thead>' +
							'<tbody id="wpcs-cookies-tbody">' + buildCookieRows(res.cookies) + '</tbody>' +
							'</table>'
						);
					}
				},
				error: function (xhr) {
					const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unknown error';
					$status.text('Scan failed: ' + msg);
				},
				complete: function () {
					$btn.prop('disabled', false).text('Run Scan Now');
				},
			});
		});

	});
}(jQuery));
