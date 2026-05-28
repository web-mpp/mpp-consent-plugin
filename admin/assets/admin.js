/* global wpcsAdmin, jQuery */
(function ($) {
	'use strict';

	$(function () {
		$('#wpcs-run-scan').on('click', function () {
			const $btn    = $(this);
			const $status = $('#wpcs-scan-status');

			$btn.prop('disabled', true);
			$status.text('Running scan…');

			$.post(
				wpcsAdmin.restUrl + '/scan',
				{ nonce: wpcsAdmin.nonce },
				function (res) {
					$status.text('Scan scheduled (job: ' + res.job_id + '). Reload in a moment.');
				}
			).fail(function () {
				$status.text('Scan failed — check browser console.');
			}).always(function () {
				$btn.prop('disabled', false);
			});
		});
	});
}(jQuery));
