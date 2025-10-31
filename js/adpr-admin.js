/**
 * Auto Daily Post Refresher - Admin JavaScript
 *
 * @package Auto Daily Post Refresher
 */

// CRITICAL: Immediate log to verify file is loaded AT ALL
console.log('========================================');
console.log('ADPR JAVASCRIPT FILE LOADED!');
console.log('This proves the JS file is executing');
console.log('========================================');

(function($) {
	'use strict';

	// Another log inside the IIFE
	console.log('ADPR: Inside jQuery wrapper, jQuery version:', $.fn.jquery);

	/**
	 * Main Admin Object
	 */
	const ADPRAdmin = {
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.initToggles();
		},

		/**
		 * Add log entry to on-screen activity log
		 */
		addLogEntry: function(message, type) {
			const logDiv = document.getElementById('adpr-activity-log');
			if (logDiv) {
				const time = new Date().toLocaleTimeString();
				let color = 'black';
				let icon = '•';

				switch(type) {
					case 'error':
						color = 'red';
						icon = '❌';
						break;
					case 'success':
						color = 'green';
						icon = '✅';
						break;
					case 'warning':
						color = 'orange';
						icon = '⚠️';
						break;
					case 'info':
						color = 'blue';
						icon = '🔵';
						break;
				}

				const entry = document.createElement('div');
				entry.className = 'log-entry';
				entry.style.cssText = 'padding:5px 0;border-bottom:1px solid #e9ecef;color:' + color;
				entry.innerHTML = '[' + time + '] ' + icon + ' ' + message;

				logDiv.appendChild(entry);
				logDiv.scrollTop = logDiv.scrollHeight;
			}
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			// Toggle switches
			$(document).on('change', '.adpr-toggle-input', this.handleToggle);

			// Bulk actions
			$('#doaction, #doaction2').on('click', this.handleBulkAction);

			// Select all/none buttons
			$('#adpr-select-all').on('click', this.selectAll);
			$('#adpr-select-none').on('click', this.selectNone);
			$('#adpr-select-filtered').on('click', this.selectFiltered);

			// Manual trigger
			$('#adpr-manual-trigger-btn').on('click', this.handleManualTrigger);
			$('#adpr-cancel-trigger').on('click', this.cancelManualTrigger);

			// Export logs
			$('#adpr-export-logs').on('click', this.exportLogs);

			// Clear logs
			$('#adpr-clear-logs').on('click', this.clearLogs);
		},

		/**
		 * Initialize toggle switches
		 */
		initToggles: function() {
			$('.adpr-toggle-input').each(function() {
				const $toggle = $(this);
				const postId = $toggle.data('post-id');

				// Add tooltip
				$toggle.closest('.adpr-toggle-switch').attr('title',
					$toggle.prop('checked') ?
						'Auto-update enabled' :
						'Auto-update disabled'
				);
			});
		},

		/**
		 * Handle toggle switch change
		 */
		handleToggle: function(e) {
			const $toggle = $(this);
			const postId = $toggle.data('post-id');
			const enabled = $toggle.prop('checked');

			// Disable toggle during request
			$toggle.prop('disabled', true);
			$toggle.closest('tr').addClass('adpr-updating');

			$.ajax({
				url: adprAdmin.ajaxurl,
				type: 'POST',
				data: {
					action: 'adpr_toggle_post',
					nonce: adprAdmin.nonce,
					post_id: postId,
					enabled: enabled.toString()
				},
				success: function(response) {
					if (response.success) {
						$toggle.closest('tr').removeClass('adpr-updating').addClass('adpr-updated');

						// Update tooltip
						$toggle.closest('.adpr-toggle-switch').attr('title',
							enabled ? 'Auto-update enabled' : 'Auto-update disabled'
						);

						// Show brief success indicator
						setTimeout(function() {
							$toggle.closest('tr').removeClass('adpr-updated');
						}, 1000);
					} else {
						// Revert toggle on error
						$toggle.prop('checked', !enabled);
						ADPRAdmin.showNotice(response.data.message || adprAdmin.strings.error, 'error');
					}
				},
				error: function() {
					// Revert toggle on error
					$toggle.prop('checked', !enabled);
					ADPRAdmin.showNotice(adprAdmin.strings.error, 'error');
				},
				complete: function() {
					$toggle.prop('disabled', false);
					$toggle.closest('tr').removeClass('adpr-updating');
				}
			});
		},

		/**
		 * Handle bulk action
		 */
		handleBulkAction: function(e) {
			const $form = $(this).closest('form');
			const action = $form.find('select[name="action"]').val() ||
			              $form.find('select[name="action2"]').val();

			if (!action || action === '-1') {
				return true;
			}

			if (action !== 'enable' && action !== 'disable') {
				return true;
			}

			e.preventDefault();

			const $checkboxes = $form.find('input[name="post_ids[]"]:checked');

			if ($checkboxes.length === 0) {
				ADPRAdmin.showNotice('Please select at least one post.', 'warning');
				return false;
			}

			const postIds = [];
			$checkboxes.each(function() {
				postIds.push($(this).val());
			});

			// Disable form during request
			$form.find('input, select, button').prop('disabled', true);

			// Show loading
			const $button = $(this);
			const originalText = $button.text();
			$button.html(adprAdmin.strings.processing + ' <span class="adpr-loading"></span>');

			$.ajax({
				url: adprAdmin.ajaxurl,
				type: 'POST',
				data: {
					action: 'adpr_bulk_action',
					nonce: adprAdmin.nonce,
					post_ids: postIds,
					bulk_action: action
				},
				success: function(response) {
					if (response.success) {
						ADPRAdmin.showNotice(response.data.message, 'success');

						// Reload page to show updated toggles
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						ADPRAdmin.showNotice(response.data.message || adprAdmin.strings.error, 'error');
						$form.find('input, select, button').prop('disabled', false);
						$button.text(originalText);
					}
				},
				error: function() {
					ADPRAdmin.showNotice(adprAdmin.strings.error, 'error');
					$form.find('input, select, button').prop('disabled', false);
					$button.text(originalText);
				}
			});

			return false;
		},

		/**
		 * Select all checkboxes
		 */
		selectAll: function(e) {
			e.preventDefault();
			$('input[name="post_ids[]"]').prop('checked', true);
			ADPRAdmin.showNotice('All posts selected.', 'info');
		},

		/**
		 * Deselect all checkboxes
		 */
		selectNone: function(e) {
			e.preventDefault();
			$('input[name="post_ids[]"]').prop('checked', false);
			ADPRAdmin.showNotice('All posts deselected.', 'info');
		},

		/**
		 * Select filtered posts (currently visible)
		 */
		selectFiltered: function(e) {
			e.preventDefault();
			const $visible = $('input[name="post_ids[]"]:visible');
			$visible.prop('checked', true);
			ADPRAdmin.showNotice($visible.length + ' posts selected.', 'info');
		},

		/**
		 * Handle manual trigger
		 */
		handleManualTrigger: function(e) {
			e.preventDefault();

			// CRITICAL DEBUG: Log everything
			console.log('========================================');
			console.log('ADPR MANUAL TRIGGER: Button clicked!');
			console.log('ADPR: adprAdmin object:', adprAdmin);
			console.log('ADPR: ajaxurl:', adprAdmin.ajaxurl);
			console.log('ADPR: nonce:', adprAdmin.nonce);
			console.log('========================================');

			// Add to on-screen log
			ADPRAdmin.addLogEntry('Manual trigger button clicked', 'info');

			const dryRun = $('#adpr-dry-run').prop('checked');
			const confirmMsg = dryRun ?
				'Are you sure you want to run a dry run?' :
				adprAdmin.strings.confirm_manual;

			if (!confirm(confirmMsg)) {
				console.log('ADPR: User cancelled confirmation');
				ADPRAdmin.addLogEntry('User cancelled operation', 'warning');
				return;
			}

			ADPRAdmin.addLogEntry('User confirmed, preparing AJAX request...', 'info');

			const $button = $(this);
			const originalText = $button.text();

			// Show progress
			$button.prop('disabled', true).text(adprAdmin.strings.processing);
			$('#adpr-manual-progress').show();
			$('#adpr-manual-result').hide();

			// Simulate progress (actual progress would require more complex implementation)
			let progress = 0;
			const progressInterval = setInterval(function() {
				progress += 10;
				if (progress <= 90) {
					$('.adpr-progress-fill').css('width', progress + '%');
					$('.adpr-progress-text').text(progress + '%');
				}
			}, 200);

			const ajaxData = {
				action: 'adpr_manual_trigger',
				nonce: adprAdmin.nonce,
				dry_run: dryRun.toString()
			};

			console.log('ADPR: Sending AJAX request...');
			console.log('ADPR: AJAX data:', ajaxData);

			ADPRAdmin.addLogEntry('Sending AJAX request to: ' + adprAdmin.ajaxurl, 'info');
			ADPRAdmin.addLogEntry('Action: adpr_manual_trigger, Dry run: ' + dryRun, 'info');

			$.ajax({
				url: adprAdmin.ajaxurl,
				type: 'POST',
				data: ajaxData,
				beforeSend: function(xhr) {
					console.log('ADPR: AJAX beforeSend triggered');
					ADPRAdmin.addLogEntry('AJAX request sent, waiting for server response...', 'info');
				},
				success: function(response) {
					console.log('ADPR: AJAX success!');
					console.log('ADPR: Response:', response);

					ADPRAdmin.addLogEntry('✅ AJAX Success! Server responded.', 'success');
					ADPRAdmin.addLogEntry('Response: ' + JSON.stringify(response.data), 'info');

					clearInterval(progressInterval);

					// Complete progress
					$('.adpr-progress-fill').css('width', '100%');
					$('.adpr-progress-text').text('100%');

					setTimeout(function() {
						$('#adpr-manual-progress').hide();

						if (response.success) {
							const count = response.data.count || 0;
							const message = response.data.message || adprAdmin.strings.success;

							ADPRAdmin.addLogEntry('✅ SUCCESS: ' + message, 'success');
							ADPRAdmin.addLogEntry('Posts updated: ' + count, 'success');

							// Add log messages if available
							if (response.data.log && Array.isArray(response.data.log)) {
								response.data.log.forEach(function(logMsg) {
									ADPRAdmin.addLogEntry(logMsg, 'info');
								});
							}

							$('#adpr-result-content').html(
								'<div class="adpr-result-success">' +
								'<strong>✓ Success!</strong><br>' +
								message +
								'</div>'
							);

							// Show admin notice
							ADPRAdmin.showNotice(message, 'success');

							// Update status info
							if (!dryRun) {
								const now = new Date().toLocaleString();
								$('#adpr-last-manual').text(now);

								// Refresh posts count if available
								if (count > 0) {
									setTimeout(function() {
										location.reload();
									}, 2000);
								}
							}
						} else {
							const message = response.data ? response.data.message : adprAdmin.strings.error;

							ADPRAdmin.addLogEntry('❌ ERROR: ' + message, 'error');

							// Add log messages if available
							if (response.data && response.data.log && Array.isArray(response.data.log)) {
								response.data.log.forEach(function(logMsg) {
									ADPRAdmin.addLogEntry(logMsg, 'warning');
								});
							}

							$('#adpr-result-content').html(
								'<div class="adpr-result-error">' +
								'<strong>✗ Error</strong><br>' +
								message +
								'</div>'
							);

							// Show admin notice
							ADPRAdmin.showNotice(message, 'error');
						}

						$('#adpr-manual-result').show();
						$button.prop('disabled', false).text(originalText);

						// Reset progress
						$('.adpr-progress-fill').css('width', '0%');
						$('.adpr-progress-text').text('0%');
					}, 500);
				},
				error: function(xhr, status, error) {
					console.log('ADPR: AJAX ERROR!');
					console.log('ADPR: XHR:', xhr);
					console.log('ADPR: Status:', status);
					console.log('ADPR: Error:', error);
					console.log('ADPR: Response text:', xhr.responseText);

					ADPRAdmin.addLogEntry('❌ AJAX ERROR: ' + error, 'error');
					ADPRAdmin.addLogEntry('Status: ' + status, 'error');
					ADPRAdmin.addLogEntry('Response: ' + xhr.responseText.substring(0, 200), 'error');

					clearInterval(progressInterval);
					$('#adpr-manual-progress').hide();
					$('#adpr-result-content').html(
						'<div class="adpr-result-error">' +
						adprAdmin.strings.error +
						'<br><small>Check browser console for details</small>' +
						'</div>'
					);
					$('#adpr-manual-result').show();
					$button.prop('disabled', false).text(originalText);
				}
			});
		},

		/**
		 * Cancel manual trigger
		 */
		cancelManualTrigger: function(e) {
			e.preventDefault();
			// Note: Actual cancellation would require backend support
			$('#adpr-manual-progress').hide();
			$('#adpr-manual-trigger-btn').prop('disabled', false);
			ADPRAdmin.showNotice('Operation cancelled.', 'info');
		},

		/**
		 * Export logs to CSV
		 */
		exportLogs: function(e) {
			e.preventDefault();

			// Create form and submit to trigger download
			const form = document.createElement('form');
			form.method = 'POST';
			form.action = adprAdmin.ajaxurl;

			const fields = {
				action: 'adpr_export_logs',
				nonce: adprAdmin.nonce
			};

			for (const key in fields) {
				if (fields.hasOwnProperty(key)) {
					const input = document.createElement('input');
					input.type = 'hidden';
					input.name = key;
					input.value = fields[key];
					form.appendChild(input);
				}
			}

			document.body.appendChild(form);
			form.submit();
			document.body.removeChild(form);

			ADPRAdmin.showNotice('Export started...', 'success');
		},

		/**
		 * Clear all logs
		 */
		clearLogs: function(e) {
			e.preventDefault();

			if (!confirm(adprAdmin.strings.confirm_clear_logs)) {
				return;
			}

			const $button = $(this);
			const originalText = $button.text();
			$button.prop('disabled', true).text(adprAdmin.strings.processing);

			$.ajax({
				url: adprAdmin.ajaxurl,
				type: 'POST',
				data: {
					action: 'adpr_clear_logs',
					nonce: adprAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						ADPRAdmin.showNotice(response.data.message, 'success');

						// Reload page
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						ADPRAdmin.showNotice(response.data.message || adprAdmin.strings.error, 'error');
						$button.prop('disabled', false).text(originalText);
					}
				},
				error: function() {
					ADPRAdmin.showNotice(adprAdmin.strings.error, 'error');
					$button.prop('disabled', false).text(originalText);
				}
			});
		},

		/**
		 * Show admin notice
		 */
		showNotice: function(message, type) {
			type = type || 'info';

			const noticeClass = 'notice notice-' + type + ' is-dismissible adpr-notice';
			const $notice = $('<div class="' + noticeClass + '"><p>' + message + '</p></div>');

			// Insert after h1
			$('.wrap h1').first().after($notice);

			// Make dismissible
			$notice.on('click', '.notice-dismiss', function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			});

			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);

			// Add dismiss button if WordPress didn't add it
			if (!$notice.find('.notice-dismiss').length) {
				$notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
			}
		},

		/**
		 * Utility: Get URL parameter
		 */
		getUrlParameter: function(name) {
			name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
			const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
			const results = regex.exec(location.search);
			return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
		}
	};

	/**
	 * Settings Page Specific
	 */
	const ADPRSettings = {
		init: function() {
			this.bindEvents();
			this.validateForm();
		},

		bindEvents: function() {
			// Validate time input
			$('#adpr_update_time').on('change', this.validateTime);

			// Validate batch size
			$('#adpr_batch_size').on('change', this.validateBatchSize);

			// Post type changes
			$('input[name="adpr_post_types[]"]').on('change', this.checkPostTypes);
		},

		validateTime: function() {
			const $input = $(this);
			const value = $input.val();

			if (!value) {
				$input.val('03:00');
				ADPRAdmin.showNotice('Invalid time. Reset to default (03:00).', 'warning');
			}
		},

		validateBatchSize: function() {
			const $input = $(this);
			let value = parseInt($input.val());

			if (isNaN(value) || value < 1) {
				value = 1;
			} else if (value > 1000) {
				value = 1000;
			}

			$input.val(value);
		},

		checkPostTypes: function() {
			const checked = $('input[name="adpr_post_types[]"]:checked').length;

			if (checked === 0) {
				ADPRAdmin.showNotice('Please select at least one post type.', 'warning');
				$(this).prop('checked', true);
			}
		},

		validateForm: function() {
			$('form').on('submit', function(e) {
				const postTypes = $('input[name="adpr_post_types[]"]:checked').length;

				if (postTypes === 0) {
					e.preventDefault();
					ADPRAdmin.showNotice('Please select at least one post type.', 'error');
					return false;
				}

				return true;
			});
		}
	};

	/**
	 * Logs Page Specific
	 */
	const ADPRLogs = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			// Enhanced search
			$('.adpr-search-form').on('submit', this.handleSearch);
		},

		handleSearch: function(e) {
			const searchValue = $(this).find('input[name="s"]').val().trim();

			if (searchValue === '') {
				return true;
			}

			// Add loading indicator
			$(this).find('button[type="submit"]').html(
				'<span class="adpr-loading"></span> Searching...'
			);

			return true;
		}
	};

	/**
	 * Keyboard Shortcuts
	 */
	const ADPRKeyboard = {
		init: function() {
			this.bindShortcuts();
			this.createModal();
		},

		bindShortcuts: function() {
			$(document).on('keydown', function(e) {
				// Ignore if typing in input fields
				if ($(e.target).is('input, textarea, select')) {
					return;
				}

				const key = e.key.toLowerCase();

				switch(key) {
					case '?':
						e.preventDefault();
						ADPRKeyboard.showModal();
						break;
					case 's':
						if ($('input[name="adpr_save_settings"]').length) {
							e.preventDefault();
							$('form').first().submit();
						}
						break;
					case 'r':
						e.preventDefault();
						location.reload();
						break;
					case 't':
						if ($('#adpr-manual-trigger-btn').length) {
							e.preventDefault();
							$('#adpr-manual-trigger-btn').click();
						}
						break;
					case 'a':
						if ($('#adpr-select-all').length) {
							e.preventDefault();
							$('#adpr-select-all').click();
						}
						break;
					case 'n':
						if ($('#adpr-select-none').length) {
							e.preventDefault();
							$('#adpr-select-none').click();
						}
						break;
					case 'u':
						if ($('#adpr-undo-btn').length) {
							e.preventDefault();
							$('#adpr-undo-btn').click();
						}
						break;
				}
			});
		},

		createModal: function() {
			const modalHTML = `
				<div class="adpr-shortcuts-modal-overlay" id="adpr-shortcuts-overlay"></div>
				<div class="adpr-shortcuts-modal" id="adpr-shortcuts-modal" role="dialog" aria-labelledby="adpr-shortcuts-title" aria-modal="true">
					<button class="adpr-shortcuts-close" aria-label="Close">&times;</button>
					<h2 id="adpr-shortcuts-title">` + adprAdmin.strings.shortcuts_title + `</h2>
					<table>
						<thead>
							<tr>
								<th>Shortcut</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><kbd>?</kbd></td>
								<td>Show this help</td>
							</tr>
							<tr>
								<td><kbd>s</kbd></td>
								<td>Save settings (Settings page)</td>
							</tr>
							<tr>
								<td><kbd>r</kbd></td>
								<td>Refresh page</td>
							</tr>
							<tr>
								<td><kbd>t</kbd></td>
								<td>Trigger manual update (Manual Trigger page)</td>
							</tr>
							<tr>
								<td><kbd>a</kbd></td>
								<td>Select all posts (Post Selector)</td>
							</tr>
							<tr>
								<td><kbd>n</kbd></td>
								<td>Select none (Post Selector)</td>
							</tr>
							<tr>
								<td><kbd>u</kbd></td>
								<td>Undo last action</td>
							</tr>
							<tr>
								<td><kbd>Esc</kbd></td>
								<td>Close this dialog</td>
							</tr>
						</tbody>
					</table>
				</div>
			`;
			$('body').append(modalHTML);

			// Bind close events
			$('#adpr-shortcuts-overlay, .adpr-shortcuts-close').on('click', function() {
				ADPRKeyboard.hideModal();
			});

			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' && $('#adpr-shortcuts-modal').hasClass('active')) {
					ADPRKeyboard.hideModal();
				}
			});
		},

		showModal: function() {
			$('#adpr-shortcuts-modal, #adpr-shortcuts-overlay').addClass('active');
			$('#adpr-shortcuts-modal').find('.adpr-shortcuts-close').focus();
		},

		hideModal: function() {
			$('#adpr-shortcuts-modal, #adpr-shortcuts-overlay').removeClass('active');
		}
	};

	/**
	 * Undo Functionality
	 */
	const ADPRUndo = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			$(document).on('click', '#adpr-undo-btn', this.handleUndo);
		},

		handleUndo: function(e) {
			e.preventDefault();

			if (!confirm(adprAdmin.strings.confirm_undo)) {
				return;
			}

			const $button = $(this);
			const originalText = $button.text();
			$button.prop('disabled', true).text(adprAdmin.strings.processing);

			$.ajax({
				url: adprAdmin.ajaxurl,
				type: 'POST',
				data: {
					action: 'adpr_undo_action',
					nonce: adprAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						ADPRAdmin.showNotice(response.data.message, 'success');

						// Remove undo notice
						$('.adpr-undo-notice').fadeOut(function() {
							$(this).remove();
						});

						// Reload page after brief delay
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						ADPRAdmin.showNotice(response.data.message || adprAdmin.strings.error, 'error');
						$button.prop('disabled', false).text(originalText);
					}
				},
				error: function() {
					ADPRAdmin.showNotice(adprAdmin.strings.error, 'error');
					$button.prop('disabled', false).text(originalText);
				}
			});
		}
	};

	/**
	 * Tooltips
	 */
	const ADPRTooltips = {
		init: function() {
			this.enhanceTooltips();
		},

		enhanceTooltips: function() {
			// Add tooltips to settings labels
			$('label[for="adpr_enabled"]').addClass('adpr-has-tooltip')
				.attr('data-tooltip', 'Master switch to enable/disable all automatic updates');

			$('label[for="adpr_update_time"]').addClass('adpr-has-tooltip')
				.attr('data-tooltip', 'Daily time when updates will run (24-hour format)');

			$('label[for="adpr_batch_size"]').addClass('adpr-has-tooltip')
				.attr('data-tooltip', 'Number of posts to process per batch - lower is safer');

			// Add tooltips to action buttons
			$('#adpr-export-logs').attr('title', 'Download all logs as CSV file');
			$('#adpr-clear-logs').attr('title', 'Permanently delete all log entries');
			$('#adpr-manual-trigger-btn').attr('title', 'Run update now instead of waiting for cron');
		}
	};

	/**
	 * Accessibility
	 */
	const ADPRAccessibility = {
		init: function() {
			this.addAriaLabels();
			this.addLiveRegion();
			this.enhanceFocus();
		},

		addAriaLabels: function() {
			// Add ARIA labels to toggle switches
			$('.adpr-toggle-input').each(function() {
				const $toggle = $(this);
				const postId = $toggle.data('post-id');
				const postTitle = $toggle.closest('tr').find('.column-title a').text();
				$toggle.attr('aria-label', 'Toggle auto-update for: ' + postTitle);
			});

			// Add ARIA labels to bulk action controls
			$('select[name="action"]').attr('aria-label', 'Select bulk action');
			$('select[name="action2"]').attr('aria-label', 'Select bulk action');

			// Add ARIA labels to filters
			$('#filter-by-type').attr('aria-label', 'Filter by post type');
			$('#filter-by-category').attr('aria-label', 'Filter by category');
			$('#filter-by-author').attr('aria-label', 'Filter by author');
			$('#filter-by-status').attr('aria-label', 'Filter by update status');

			// Add ARIA labels to buttons
			$('#adpr-select-all').attr('aria-label', 'Select all posts in current view');
			$('#adpr-select-none').attr('aria-label', 'Deselect all posts');
			$('#adpr-select-filtered').attr('aria-label', 'Select all filtered posts');
		},

		addLiveRegion: function() {
			// Create ARIA live region for dynamic updates
			if ($('#adpr-aria-live').length === 0) {
				$('body').append('<div id="adpr-aria-live" class="adpr-aria-live" role="status" aria-live="polite" aria-atomic="true"></div>');
			}
		},

		enhanceFocus: function() {
			// Ensure proper focus management
			$(document).on('click', '.notice-dismiss', function() {
				// Return focus to main content after dismissing notice
				$('h1').first().focus();
			});
		},

		announce: function(message) {
			$('#adpr-aria-live').text(message);
			setTimeout(function() {
				$('#adpr-aria-live').text('');
			}, 1000);
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		// Initialize main admin functionality
		ADPRAdmin.init();

		// Initialize keyboard shortcuts
		ADPRKeyboard.init();

		// Initialize undo functionality
		ADPRUndo.init();

		// Initialize tooltips
		ADPRTooltips.init();

		// Initialize accessibility features
		ADPRAccessibility.init();

		// Initialize page-specific functionality
		if ($('body').hasClass('post-refresher_page_adpr-settings') ||
		    $('.wrap').find('#adpr_enabled').length) {
			ADPRSettings.init();
		}

		if ($('body').hasClass('post-refresher_page_adpr-logs')) {
			ADPRLogs.init();
		}

		// Check for success messages in URL
		const updated = ADPRAdmin.getUrlParameter('updated');
		if (updated) {
			ADPRAdmin.showNotice(
				updated + ' post(s) updated successfully.',
				'success'
			);
		}

		// Announce keyboard shortcuts availability
		if (typeof adprAdmin !== 'undefined' && adprAdmin.strings.shortcuts_help) {
			console.info(adprAdmin.strings.shortcuts_help);
		}
	});

})(jQuery);
