/**
 *  Get the IP address from JS.
 */

(function() {
	'use strict';
	function getIP() {
		const  formData = new FormData();
		formData.append('action', 'test_ip_get_ip');
		formData.append('nonce', test_ip_vars.nonce);

		fetch(test_ip_vars.ajax_url, {
			method: 'POST',
			body: formData
		})
		.then(function(response) {
			return response.json();
		})
		.then(function(data) {
			if (data.success && data.data.ip) {
				const hiddenFields = document.querySelectorAll('input[name="test_ip_field"]');
				hiddenFields.forEach(function(field) {
					field.value = data.data.ip;
				});
			}
		})
		.catch(function(error) {
			console.error('Failed to get IP:', error);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', getIP);
	} else {
		getIP();
	}
})();