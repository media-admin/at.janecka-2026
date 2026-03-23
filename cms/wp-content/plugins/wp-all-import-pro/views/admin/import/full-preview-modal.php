<?php
// Full-screen preview modal for Step 3
?>

<div id="wpai-full-preview-modal" class="wpai-full-preview-modal" style="display: none;">
	<div class="wpai-full-preview-overlay">
		<div class="wpai-full-preview-container">
			<!-- Top Bar with Navigation and Close -->
			<div class="wpai-full-preview-header">
				<div class="navigation">
					<a href="#" class="previous_element wpai-preview-prev">&nbsp;</a>
					<strong><input type="text" value="1" name="tagno" class="tagno" id="wpai-preview-record-number"/></strong><span class="out_of"> of <strong class="pmxi_count" id="wpai-preview-total-count"><?php echo isset(PMXI_Plugin::$session->count) ? PMXI_Plugin::$session->count : 0; ?></strong></span>
					<a href="#" class="next_element wpai-preview-next">&nbsp;</a>
				</div>
				<button type="button" class="wpai-full-preview-close" title="<?php esc_attr_e('Close Preview', 'wp-all-import-pro'); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>

			<!-- Tab Navigation -->
			<div class="wpai-full-preview-tabs">
				<button type="button" class="wpai-preview-tab active" data-tab="admin">
					<span class="dashicons dashicons-admin-post"></span>
					<?php _e('WP Admin View', 'wp-all-import-pro'); ?>
				</button>
				<button type="button" class="wpai-preview-tab" data-tab="frontend">
					<span class="dashicons dashicons-visibility"></span>
					<?php _e('Frontend View', 'wp-all-import-pro'); ?>
				</button>
				<button type="button" class="wpai-preview-tab" data-tab="settings">
					<span class="dashicons dashicons-admin-settings"></span>
					<?php _e('Preview Settings', 'wp-all-import-pro'); ?>
				</button>
			</div>

			<!-- Tab Content -->
			<div class="wpai-full-preview-content">
				<!-- Admin View Tab -->
				<div class="wpai-preview-tab-content active" data-tab-content="admin">
					<!-- Error/Notice Container (for admin view) -->
					<div id="wpai-preview-notices" style="display: none; position: relative; z-index: 10;"></div>

					<div class="wpai-preview-iframe-wrapper">
						<div class="wpai-iframe-loading">
							<span class="wpai-spinner"></span>
							<p class="wpai-loading-text">Loading preview...</p>
							<p class="wpai-preload-progress" style="display: none; margin-top: 8px; color: #666; font-size: 13px;"></p>
						</div>
						<iframe id="wpai-preview-admin-iframe" frameborder="0" style="display: none;"></iframe>
					</div>
				</div>

				<!-- Frontend View Tab -->
				<div class="wpai-preview-tab-content" data-tab-content="frontend">
					<!-- Error/Notice Container (for frontend view) -->
					<div id="wpai-preview-notices-frontend" style="display: none; position: relative; z-index: 10;"></div>

					<div class="wpai-preview-iframe-wrapper">
						<div class="wpai-iframe-loading">
							<span class="wpai-spinner"></span>
							<p class="wpai-loading-text">Loading preview...</p>
							<p class="wpai-preload-progress" style="display: none; margin-top: 8px; color: #666; font-size: 13px;"></p>
						</div>
						<iframe id="wpai-preview-frontend-iframe" frameborder="0" style="display: none;"></iframe>
					</div>
				</div>

				<!-- Settings Tab -->
				<div class="wpai-preview-tab-content" data-tab-content="settings">
					<div class="wpai-preview-settings-container">
						<?php
						// Load preview settings dynamically via AJAX
						?>
						<div id="wpai-preview-settings-content">
							<p style="text-align: center; padding: 40px; color: #666;">
								<?php _e('Loading preview settings...', 'wp-all-import-pro'); ?>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.wpai-full-preview-modal {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 999999;
	background: rgba(0, 0, 0, 0.7);
}

.wpai-full-preview-overlay {
	width: 100%;
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20px;
	box-sizing: border-box;
}

.wpai-full-preview-container {
	background: #fff;
	width: 95vw;
	height: 95vh;
	max-width: 1920px;
	max-height: 1080px;
	border-radius: 8px;
	box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
	display: flex;
	flex-direction: column;
	overflow: hidden;
	box-sizing: border-box;
}

.wpai-full-preview-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 15px 20px;
	background: #f8f9fa;
	border-bottom: 1px solid #ddd;
}

/* Use existing navigation styles from tag.php */
.wpai-full-preview-header .navigation {
	flex: 1;
	text-align: center;
}

/* Navigation arrows and links */
.wpai-full-preview-header .navigation a,
.wpai-full-preview-header .navigation span {
	font-weight: bold;
	padding: 0 12px;
	text-decoration: none;
	height: 25px;
	display: inline-block;
}

.wpai-full-preview-header .navigation span.out_of {
	color: #777;
	margin-left: 5px;
	padding: 0;
}

.wpai-full-preview-header .navigation .previous_element {
	background: url('<?php echo PMXI_Plugin::ROOT_URL; ?>/static/img/ui_4.0/left_btn.png') 5% 0 no-repeat;
	margin-top: 4px;
}

.wpai-full-preview-header .navigation .next_element {
	background: url('<?php echo PMXI_Plugin::ROOT_URL; ?>/static/img/ui_4.0/right_btn.png') 95% 0 no-repeat;
	margin-top: 4px;
}

/* Ensure input styling matches the template page */
.wpai-full-preview-header input[type="text"][name="tagno"] {
	margin-left: 5px;
	padding: 3px;
	width: 40px;
	text-align: center;
	border: 1px solid #ddd;
	border-radius: 3px;
}

.wpai-full-preview-close {
	background: transparent;
	border: none;
	cursor: pointer;
	padding: 5px;
	color: #666;
	transition: color 0.2s;
}

.wpai-full-preview-close:hover {
	color: #d63638;
}

.wpai-full-preview-close .dashicons {
	font-size: 24px;
	width: 24px;
	height: 24px;
}

.wpai-full-preview-tabs {
	display: flex;
	background: #f8f9fa;
	border-bottom: 1px solid #ddd;
	padding: 0 20px;
}

.wpai-preview-tab {
	background: transparent;
	border: none;
	padding: 12px 20px;
	cursor: pointer;
	display: flex;
	align-items: center;
	gap: 8px;
	color: #666;
	font-weight: 500;
	border-bottom: 3px solid transparent;
	transition: all 0.2s;
}

.wpai-preview-tab:hover {
	color: #2271b1;
	background: rgba(34, 113, 177, 0.05);
}

.wpai-preview-tab.active {
	color: #2271b1;
	border-bottom-color: #2271b1;
	background: #fff;
}

.wpai-preview-tab .dashicons {
	font-size: 18px;
	width: 18px;
	height: 18px;
}

.wpai-full-preview-content {
	flex: 1;
	position: relative;
	overflow: hidden;
	background: #fff;
	min-height: 0;
	box-sizing: border-box;
}

.wpai-preview-tab-content {
	display: none;
	width: 100%;
	height: 100%;
	position: relative;
	overflow: hidden;
}

.wpai-preview-tab-content.active {
	display: block;
}

.wpai-preview-iframe-wrapper {
	width: 100%;
	height: 100%;
	overflow: auto;
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	box-sizing: border-box;
}

.wpai-preview-tab-content iframe {
	width: 100%;
	height: 100%;
	border: none;
	display: block;
	box-sizing: border-box;
}

.wpai-iframe-loading {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	background: #f0f0f1;
	z-index: 1;
}

.wpai-iframe-loading p {
	margin: 15px 0 0 0;
	color: #50575e;
	font-size: 14px;
}

.wpai-iframe-loading .wpai-spinner {
	display: inline-block;
	width: 20px;
	height: 20px;
	border: 3px solid #f0f0f1;
	border-top-color: #2271b1;
	border-radius: 50%;
	animation: wpai-spin 0.6s linear infinite;
}

@keyframes wpai-spin {
	to { transform: rotate(360deg); }
}

.wpai-preview-settings-container {
	width: 100%;
	height: 100%;
	overflow-y: auto;
	padding: 20px;
}

/* Validation error state for unique key field */
.wpai-validation-error {
	border-color: #dc3232 !important;
	box-shadow: 0 0 0 1px #dc3232 !important;
	animation: wpai-shake 0.3s;
}

@keyframes wpai-shake {
	0%, 100% { transform: translateX(0); }
	25% { transform: translateX(-5px); }
	75% { transform: translateX(5px); }
}

</style>

<script>
jQuery(document).ready(function($) {
	var fullPreviewModal = {
		currentRecord: 1,
		totalRecords: 0,
		previewPostId: null,
		previewEditUrl: null,
		previewViewUrl: null,
		previewCache: {},
		createdPostIds: [],
		importId: null,
		importType: '',
		previewPostStatus: 'draft',
		previewPostStatusInitialized: false,
		recordsToPreload: 10,
		previewUniqueKey: '',
		previewUniqueKeyInitialized: false,
		previewSessionId: null,

		modalIsOpen: false,
		pendingCleanupPostIds: [],
		isCleaningUp: false,

		// Standard preloading state (works for all import types)
		preloadQueue: [],
		currentlyProcessing: null,
		processingStates: {},
		preloadTriggerOffset: 4,
		isPreloading: false,
		userRequestedRecord: null,
		highestPreloadedBatch: 0,

		isProductImport: false,
		initialPreloadComplete: false,
		initialPreloadInProgress: false,
		waitingForInitialPreload: false,

		init: function() {
			this.bindEvents();
			this.getImportId();
			this.initializeTotalRecords();
			this.setupHeartbeat();
		},

		getImportId: function() {
			var urlParams = new URLSearchParams(window.location.search);
			this.importId = urlParams.get('id');
		},

		initializeTotalRecords: function() {
			var totalFromDOM = parseInt($('#wpai-preview-total-count').text());
			if (!isNaN(totalFromDOM)) {
				this.totalRecords = totalFromDOM;
			}

			if (!this.importId) {
				var formIdField = $('input[name="id"]').val() || $('input[name="import_id"]').val();
				if (formIdField) {
					this.importId = formIdField;
				}
			}

			// Watch for changes to the tag list count (when filters are applied)
			this.watchTagListCount();

			return this.importId;
		},

		/**
		 * Watch for changes to the tag list count and update totalRecords
		 * This handles when filters are applied on the template page
		 */
		watchTagListCount: function() {
			var self = this;

			// Use MutationObserver to watch for changes to .pmxi_count
			var observer = new MutationObserver(function(mutations) {
				mutations.forEach(function(mutation) {
					if (mutation.type === 'childList' || mutation.type === 'characterData') {
						var newCount = parseInt($('.pmxi_count').first().text());
						// Allow updating to 0 to handle "no records after filters" case
						if (!isNaN(newCount) && newCount !== self.totalRecords) {
							self.totalRecords = newCount;
							self.updateNavigationButtons();

							// Reset current record if it's now beyond the new total
							if (newCount > 0 && self.currentRecord > newCount) {
								self.currentRecord = newCount;
								$('#wpai-preview-record-number').val(self.currentRecord);
							}

							// Clear cache and preload queue since filters changed the data
							self.previewCache = {};
							self.preloadQueue = [];
							self.processingStates = {};
							self.isPreloading = false;
							self.currentlyProcessing = null;
						}
					}
				});
			});

			// Observe the tag list for changes
			var tagList = $('.tag').get(0);
			if (tagList) {
				observer.observe(tagList, {
					childList: true,
					subtree: true,
					characterData: true,
					characterDataOldValue: true
				});
			}
		},

		/**
		 * Hide the sandbox/try demo banner inside preview iframes
		 * This banner is an artifact of the /try demo environment and should not appear in previews
		 *
		 * The banner CSS adds specific spacing rules that need to be overridden:
		 * - html.wp-toolbar { padding-top: 102px; } (banner 70px + admin bar 32px)
		 * - body.admin-bar #wpadminbar { top: 70px !important; }
		 * - body:not(.is-fullscreen-mode) .edit-post-layout { top: 102px; }
		 * - body.is-fullscreen-mode .edit-post-layout { top: 70px; }
		 * - .woocommerce-layout__header { padding-top: 70px; }
		 * - body.woocommerce-admin-page #wpbody { margin-top: 60px !important; }
		 */
		hideSandboxBannerInIframe: function(iframe) {
			try {
				// Access the iframe's document
				var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

				if (iframeDoc) {
					// Find the sandbox banner
					var banner = iframeDoc.getElementById('sandboxtry-global-top-banner');

					// Only apply fixes if the banner actually exists
					// This prevents breaking real sites that don't have the sandbox banner
					if (banner) {
						// Remove the banner entirely
						if (banner.parentNode) {
							banner.parentNode.removeChild(banner);
						}

						// Inject CSS to override all sandbox banner spacing rules
						var style = iframeDoc.createElement('style');
						style.textContent =
							'/* Hide sandbox banner and remove all spacing added by sandbox CSS */' +
							'#sandboxtry-global-top-banner { display: none !important; }' +
							// Reset HTML padding (normally 102px for banner + admin bar, or 70px for banner only)
							'html.wp-toolbar { padding-top: 32px !important; }' + // Keep 32px for admin bar on frontend
							'html.wp-admin.wp-toolbar { padding-top: 0 !important; }' + // Remove all padding in admin
							// Reset admin bar position (normally pushed down 70px for banner)
							'body.admin-bar #wpadminbar { top: 0 !important; }' + // Admin bar at top (normal position)
							// Reset block editor layout position (normally 102px or 70px depending on fullscreen)
							'body:not(.is-fullscreen-mode) .edit-post-layout { top: 32px !important; }' +
							'body.is-fullscreen-mode .edit-post-layout { top: 0 !important; }' +
							// Reset WooCommerce header padding (normally 70px for banner)
							'.woocommerce-layout__header { padding-top: 0 !important; }' +
							// Reset WooCommerce body margin (normally 60px for banner)
							'body.woocommerce-admin-page #wpbody { margin-top: 0 !important; }' +
							// Additional resets for common WordPress admin elements
							'body { padding-top: 0 !important; margin-top: 0 !important; }' +
							'html { margin-top: 0 !important; }' +
							'#wpcontent { padding-top: 0 !important; margin-top: 0 !important; }' +
							'#wpbody { padding-top: 0 !important; }';

						// Append to head if it exists, otherwise to body
						if (iframeDoc.head) {
							iframeDoc.head.appendChild(style);
						} else if (iframeDoc.body) {
							iframeDoc.body.appendChild(style);
						}
					}
				}
			} catch (e) {
				// Silently fail if cross-origin restrictions prevent access
				// This shouldn't happen since the iframe content is from the same WordPress site
				// but we handle it gracefully just in case
			}
		},

		/**
		 * Disable WordPress's "unsaved changes" warning in the preview iframe
		 *
		 * WordPress shows a browser alert when users try to navigate away from admin pages
		 * with unsaved changes. This is triggered by a beforeunload event listener.
		 *
		 * In the preview context, this alert is not relevant because:
		 * 1. Preview records are temporary and changes don't need to be saved
		 * 2. The alert disrupts the preview workflow
		 * 3. Users expect to freely navigate between preview records without warnings
		 *
		 * This function disables the beforeunload event in the iframe by:
		 * 1. Removing all existing beforeunload event listeners
		 * 2. Preventing new beforeunload listeners from being added
		 * 3. Disabling WordPress's autosave functionality that triggers the warning
		 */


		bindEvents: function() {
			var self = this;

			// Attach iframe load handlers
			this.attachIframeLoadHandlers();

			$('#wpai-full-preview-btn').on('click', function(e) {
				e.preventDefault();
				self.openModal();
			});

			$('.wpai-full-preview-close').on('click', function() {
				self.closeModal();
			});

			$('.wpai-full-preview-overlay').on('click', function(e) {
				if (e.target === this) {
					self.closeModal();
				}
			});

			$(document).on('keydown.wpai-preview', function(e) {
				if (e.key === 'Escape' || e.keyCode === 27) {
					if ($('#wpai-full-preview-modal').is(':visible')) {
						self.closeModal();
					}
				}
			});

			$(window).on('beforeunload', function() {
				if (self.previewSessionId) {
					self.deletePreviewSessionSync();
				} else if (self.createdPostIds.length > 0) {
					self.deletePreviewPostsSync();
				}
			});

			$(window).on('pagehide', function() {
				if (self.previewSessionId) {
					self.deletePreviewSessionSync();
				} else if (self.createdPostIds.length > 0) {
					self.deletePreviewPostsSync();
				}
			});

			$('.wpai-preview-tab').on('click', function() {
				var tab = $(this).data('tab');
				self.switchTab(tab);
			});

			$('.wpai-preview-prev').on('click', function(e) {
				e.preventDefault();
				if ($(this).is('a')) {
					self.navigateRecord(-1);
				}
			});

			$('.wpai-preview-next').on('click', function(e) {
				e.preventDefault();
				if ($(this).is('a')) {
					self.navigateRecord(1);
				}
			});

			$('#wpai-preview-record-number').on('change', function() {
				var recordNum = parseInt($(this).val());
				if (recordNum > 0) {
					if (recordNum < 1) {
						recordNum = 1;
					}
					if (self.totalRecords > 0 && recordNum > self.totalRecords) {
						recordNum = self.totalRecords;
					}

					// Immediately remove and recreate iframes to clear old content instantly
					// This prevents confusing display of old content while record number changes
					var $adminContainer = $('#wpai-preview-admin-iframe').parent();
					var $frontendContainer = $('#wpai-preview-frontend-iframe').parent();

					// Remove old iframes
					$('#wpai-preview-admin-iframe').remove();
					$('#wpai-preview-frontend-iframe').remove();

					// Create new blank iframes
					$adminContainer.append('<iframe id="wpai-preview-admin-iframe" frameborder="0" style="display: none;"></iframe>');
					$frontendContainer.append('<iframe id="wpai-preview-frontend-iframe" frameborder="0" style="display: none;"></iframe>');

					// Re-attach load event handlers to new iframes
					self.attachIframeLoadHandlers();

					// Show loading indicators
					$('.wpai-iframe-loading').show();
					$('.wpai-loading-text').text('Loading preview...');

					self.currentRecord = recordNum;
					$(this).val(recordNum);
					self.updateNavigationButtons();

					self.userRequestedRecord = recordNum;

					// If currently on settings tab, switch to admin view
					var currentTab = $('.wpai-preview-tab.active').data('tab');
					if (currentTab === 'settings') {
						self.switchTab('admin');
					}

					self.checkAndLoadNextBatch();

					self.loadPreview();
				}
			});
		},
		
		openModal: function() {
			var self = this;

			// If cleanup is in progress, wait for it to complete before opening
			if (self.isCleaningUp) {
				// Show loading state while waiting
				$('#wpai-full-preview-modal').show();
				$('#wpai-preview-admin-iframe').hide();
				$('#wpai-preview-frontend-iframe').hide();
				$('#wpai-preview-admin-iframe').siblings('.wpai-iframe-loading').show();
				$('.wpai-loading-text').text('Preparing preview...');

				// Check every 100ms if cleanup is complete
				var checkCleanup = setInterval(function() {
					if (!self.isCleaningUp) {
						clearInterval(checkCleanup);
						// Cleanup complete, proceed with opening
						self.proceedWithOpen();
					}
				}, 100);
				return;
			}

			self.proceedWithOpen();
		},

		proceedWithOpen: function() {
			var self = this;

			self.modalIsOpen = true;
			self.pendingCleanupPostIds = [];
			self.sessionTimedOut = false; // Track if session timed out

			// Generate and register a preview session immediately when modal opens
			// This ensures the session exists before any heartbeats are sent
			if (!self.previewSessionId) {
				self.previewSessionId = self.generateSessionId();
				self.registerPreviewSession(self.previewSessionId);
			}

			// Clear all previous content and notices
			$('#wpai-preview-cleanup-notice').remove();
			$('#wpai-preview-cleanup-overlay').remove();
			$('#wpai-preview-notices').html('').hide();
			$('#wpai-preview-notices-frontend').html('').hide();

			// Clear iframes and show loading state
			$('#wpai-preview-admin-iframe').attr('src', 'about:blank').hide();
			$('#wpai-preview-frontend-iframe').attr('src', 'about:blank').hide();
			$('#wpai-preview-admin-iframe').siblings('.wpai-iframe-loading').show();
			$('#wpai-preview-frontend-iframe').siblings('.wpai-iframe-loading').show();

			// Reset loading text to default
			$('.wpai-loading-text').text('Loading preview...');
			$('.wpai-preload-progress').hide();

			// Always start on the admin view tab when opening modal
			this.switchTab('admin');

			// Update total records from DOM in case filters were applied
			var totalFromDOM = parseInt($('.pmxi_count').first().text());
			if (!isNaN(totalFromDOM)) {
				self.totalRecords = totalFromDOM;
			}

			// Set current record based on total (0 if no records, 1 otherwise)
			self.currentRecord = self.totalRecords > 0 ? 1 : 0;
			$('#wpai-preview-record-number').val(self.currentRecord);

			// Reset all preload state flags to ensure clean start
			self.initialPreloadComplete = false;
			self.initialPreloadInProgress = false;
			self.waitingForInitialPreload = false;
			self.isPreloading = false;
			self.currentlyProcessing = null;
			self.userRequestedRecord = null;
			self.highestPreloadedBatch = 0;

			// Clear processing states and queues
			self.preloadQueue = [];
			self.processingStates = {};

			$('#wpai-full-preview-modal').fadeIn(200);
			this.updateNavigationButtons();

			// Load settings first to detect variable product imports
			// Then start preview after both settings load AND cleanup completes
			this.loadSettings(function() {
				// Settings loaded, now cleanup and start preview
				if (self.previewSessionId) {
					self.cleanupPreviousSession(function() {
						self.startPreviewAfterCleanup();
					});
				} else {
					self.startPreviewAfterCleanup();
				}
			});
		},

		startPreviewAfterCleanup: function() {
			var self = this;

			// Load the first preview
			self.loadPreview();
		},

		attachIframeLoadHandlers: function() {
			var self = this;

			$('#wpai-preview-admin-iframe').off('load').on('load', function() {
				if ($(this).attr('src') && $(this).attr('src') !== 'about:blank') {
					// Hide sandbox banner inside iframe (wrapped in try-catch to ensure loading indicator always hides)
					try {
						self.hideSandboxBannerInIframe(this);
					} catch (e) {
						// Silently fail - don't let banner hiding errors prevent iframe from showing
					}

					// Hide loading indicator now that iframe is fully loaded
					// Note: iframe is already visible (shown immediately when src was set)
					$(this).siblings('.wpai-iframe-loading').hide();
				}
			});

			$('#wpai-preview-frontend-iframe').off('load').on('load', function() {
				if ($(this).attr('src') && $(this).attr('src') !== 'about:blank') {
					// Hide sandbox banner inside iframe (wrapped in try-catch to ensure loading indicator always hides)
					try {
						self.hideSandboxBannerInIframe(this);
					} catch (e) {
						// Silently fail - don't let banner hiding errors prevent iframe from showing
					}

					// Hide loading indicator now that iframe is fully loaded
					// Note: iframe is already visible (shown immediately when src was set)
					$(this).siblings('.wpai-iframe-loading').hide();
				}
			});
		},

		cleanupPreviousSession: function(callback) {
			var self = this;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpai_delete_preview_session',
					security: window.wp_all_import_security,
					preview_session_id: self.previewSessionId
				},
				success: function(response) {
					self.previewSessionId = null;
					if (callback) callback();
				},
				error: function(xhr, status, error) {
					self.previewSessionId = null;
					if (callback) callback();
				}
			});
		},
		
		closeModal: function() {
			var self = this;

			self.modalIsOpen = false;


			self.preloadQueue = [];
			self.processingStates = {};
			self.isPreloading = false;
			self.currentlyProcessing = null;
			self.userRequestedRecord = null;
			self.highestPreloadedBatch = 0;

			// Clear all preview content from iframes
			$('#wpai-preview-admin-iframe').attr('src', 'about:blank').hide();
			$('#wpai-preview-frontend-iframe').attr('src', 'about:blank').hide();

			// Show loading state in iframes to prevent flash of old content on reopen
			$('#wpai-preview-admin-iframe').siblings('.wpai-iframe-loading').show();
			$('#wpai-preview-frontend-iframe').siblings('.wpai-iframe-loading').show();

			// Reset loading text to default
			$('.wpai-loading-text').text('Loading preview...');
			$('.wpai-preload-progress').hide();

			// Clear all status messages
			$('#wpai-preview-notices').html('').hide();
			$('#wpai-preview-notices-frontend').html('').hide();
			$('#wpai-preview-cleanup-notice').remove();
			$('#wpai-preview-cleanup-overlay').remove();

			// Reset to default tab (admin view)
			self.switchTab('admin');

			$('.wpai-preview-navigation').show();

			$(document).off('keydown.wpai-preview');

			// Close modal immediately
			$('#wpai-full-preview-modal').fadeOut(200);

			// Run cleanup in background after modal closes
			self.runBackgroundCleanup();
		},

		runBackgroundCleanup: function() {
			var self = this;

			// Set cleanup flag to prevent race conditions
			self.isCleaningUp = true;

			// Delay cleanup by 1.5 seconds as before
			setTimeout(function() {
				if (self.previewSessionId) {
					self.deleteAllPreviewRecordsForSession(function() {
						if (self.pendingCleanupPostIds.length > 0) {
							self.deleteSpecificPreviewPosts(self.pendingCleanupPostIds, function() {
								self.finalizeBackgroundCleanup();
							});
						} else {
							self.finalizeBackgroundCleanup();
						}
					});
				} else if (self.createdPostIds.length > 0 || self.pendingCleanupPostIds.length > 0) {
					var allPostIds = self.createdPostIds.concat(self.pendingCleanupPostIds);
					allPostIds = allPostIds.filter(function(id, index, arr) {
						return arr.indexOf(id) === index;
					});

					self.deleteSpecificPreviewPosts(allPostIds, function() {
						self.finalizeBackgroundCleanup();
					});
				} else {
					self.finalizeBackgroundCleanup();
				}
			}, 1500);
		},

		finalizeBackgroundCleanup: function() {
			var self = this;

			// Clear all state
			self.previewCache = {};
			self.createdPostIds = [];
			self.pendingCleanupPostIds = [];
			self.currentRecord = 1;
			$('#wpai-preview-record-number').val(1);

			// Clear cleanup flag
			self.isCleaningUp = false;
		},
		
		switchTab: function(tab) {
			$('.wpai-preview-tab').removeClass('active');
			$('.wpai-preview-tab[data-tab="' + tab + '"]').addClass('active');
			
			$('.wpai-preview-tab-content').removeClass('active');
			$('.wpai-preview-tab-content[data-tab-content="' + tab + '"]').addClass('active');
		},
		
		navigateRecord: function(direction) {
			// Immediately remove and recreate iframes to clear old content instantly
			// This prevents confusing display of old content while record number changes
			var $adminContainer = $('#wpai-preview-admin-iframe').parent();
			var $frontendContainer = $('#wpai-preview-frontend-iframe').parent();

			// Remove old iframes
			$('#wpai-preview-admin-iframe').remove();
			$('#wpai-preview-frontend-iframe').remove();

			// Create new blank iframes
			$adminContainer.append('<iframe id="wpai-preview-admin-iframe" frameborder="0" style="display: none;"></iframe>');
			$frontendContainer.append('<iframe id="wpai-preview-frontend-iframe" frameborder="0" style="display: none;"></iframe>');

			// Re-attach load event handlers to new iframes
			this.attachIframeLoadHandlers();

			// Show loading indicators
			$('.wpai-iframe-loading').show();
			$('.wpai-loading-text').text('Loading preview...');

			this.currentRecord += direction;

			if (this.currentRecord < 1) {
				this.currentRecord = 1;
			}
			if (this.totalRecords > 0 && this.currentRecord > this.totalRecords) {
				this.currentRecord = this.totalRecords;
			}

			$('#wpai-preview-record-number').val(this.currentRecord);
			this.updateNavigationButtons();

			this.userRequestedRecord = this.currentRecord;

			// If currently on settings tab, switch to admin view
			var currentTab = $('.wpai-preview-tab.active').data('tab');
			if (currentTab === 'settings') {
				this.switchTab('admin');
			}

			this.checkAndLoadNextBatch();

			this.loadPreview();
		},

		updateNavigationButtons: function() {
			// Always update the total count display (even if 0)
			$('#wpai-preview-total-count').text(this.totalRecords);

			// If no records, disable all navigation
			if (this.totalRecords === 0) {
				$('.wpai-preview-prev').replaceWith('<span class="previous_element wpai-preview-prev">&nbsp;</span>');
				$('.wpai-preview-next').replaceWith('<span class="next_element wpai-preview-next">&nbsp;</span>');
				$('#wpai-preview-record-number').prop('disabled', true).attr('max', 0);
				return;
			}

			// Enable the input if it was disabled
			$('#wpai-preview-record-number').prop('disabled', false);

			// Disable prev button if at record 1
			if (this.currentRecord <= 1) {
				$('.wpai-preview-prev').replaceWith('<span class="previous_element wpai-preview-prev">&nbsp;</span>');
			} else {
				if ($('.wpai-preview-prev').is('span')) {
					$('.wpai-preview-prev').replaceWith('<a href="#" class="previous_element wpai-preview-prev">&nbsp;</a>');
				}
			}

			// Disable next button if at last record
			if (this.currentRecord >= this.totalRecords) {
				$('.wpai-preview-next').replaceWith('<span class="next_element wpai-preview-next">&nbsp;</span>');
			} else {
				if ($('.wpai-preview-next').is('span')) {
					$('.wpai-preview-next').replaceWith('<a href="#" class="next_element wpai-preview-next">&nbsp;</a>');
				}
			}

			var self = this;
			$('.wpai-preview-prev').off('click').on('click', function(e) {
				e.preventDefault();
				if ($(this).is('a')) {
					self.navigateRecord(-1);
				}
			});
			$('.wpai-preview-next').off('click').on('click', function(e) {
				e.preventDefault();
				if ($(this).is('a')) {
					self.navigateRecord(1);
				}
			});

			// Set max attribute for input
			$('#wpai-preview-record-number').attr('max', this.totalRecords);
		},

		disableNavigation: function() {
			$('.wpai-preview-prev').replaceWith('<span class="previous_element wpai-preview-prev">&nbsp;</span>');
			$('.wpai-preview-next').replaceWith('<span class="next_element wpai-preview-next">&nbsp;</span>');

			$('#wpai-preview-record-number').prop('disabled', true);
		},

		enableNavigation: function() {
			this.updateNavigationButtons();

			// Only enable input if there are records to navigate
			if (this.totalRecords > 0) {
				$('#wpai-preview-record-number').prop('disabled', false);
			}
		},

		loadPreview: function() {
			var self = this;
			var requestedRecord = self.currentRecord;

			// Always clear notices at the start of loading a new preview
			$('#wpai-preview-notices').hide().html('');
			$('#wpai-preview-notices-frontend').hide().html('');

			// Check if we already have a cached preview for this record
			if (self.previewCache[self.currentRecord]) {
				var cached = self.previewCache[self.currentRecord];

				// Check if this was a skipped record
				if (cached.was_skipped) {
					self.showSkippedNotice(cached.skip_reason || 'Record was skipped');
					// Re-enable navigation for skipped records
					self.enableNavigation();
					return;
				}

				self.previewPostId = cached.post_id;
				self.previewEditUrl = cached.edit_url;
				self.previewViewUrl = cached.view_url;

				// For cached records, load the iframes with the cached URLs
				// Note: Iframes are already fresh (recreated in navigateRecord)

				// Ensure loading indicators are visible
				$('.wpai-iframe-loading').show();

				// Reset loading text (in case it was changed during preload)
				$('.wpai-loading-text').text('Loading preview...');
				$('.wpai-preload-progress').hide();

				// Add preview mode indicator (no cache-busting for cached records)
				// This allows browser to cache the iframe content
				var addPreviewParam = function(url){
					if (!url) return url;
					var sep = url.indexOf('?') === -1 ? '?' : '&';
					return url + sep + 'wpai_preview=1';
				};

				// Set iframe sources with preview indicator (browser caching enabled)
				// Keep loading indicator visible, iframe will be shown when it starts loading
				if (cached.edit_url) {
					var adminUrl = addPreviewParam(cached.edit_url);

					// Set src and wait a brief moment for browser to start loading
					$('#wpai-preview-admin-iframe').attr('src', adminUrl);

					// Show iframe after very brief delay (allows browser to start rendering)
					// Hide loading indicator so user sees progressive rendering
					setTimeout(function() {
						$('#wpai-preview-admin-iframe').show();
						$('#wpai-preview-admin-iframe').siblings('.wpai-iframe-loading').hide();
					}, 100);
				}
				if (cached.view_url) {
					var frontendUrl = addPreviewParam(cached.view_url);

					// Set src and wait a brief moment for browser to start loading
					$('#wpai-preview-frontend-iframe').attr('src', frontendUrl);

					// Show iframe after very brief delay (allows browser to start rendering)
					// Hide loading indicator so user sees progressive rendering
					setTimeout(function() {
						$('#wpai-preview-frontend-iframe').show();
						$('#wpai-preview-frontend-iframe').siblings('.wpai-iframe-loading').hide();
					}, 100);
				}

				// Re-enable navigation since cached record is loaded
				self.enableNavigation();

				return;
			}

			self.disableNavigation();

			$('#wpai-preview-admin-iframe').show().siblings('.wpai-iframe-loading').show();
			$('#wpai-preview-frontend-iframe').show().siblings('.wpai-iframe-loading').show();

			// Sync TinyMCE editors before collecting form data
			// This ensures drag-and-drop content in visual editors is captured
			if (typeof tinyMCE != 'undefined') {
				tinyMCE.triggerSave(false, false);
			}

			$('.custom_type[rel=tax_mapping]').each(function(){
				var values = new Array();
				$(this).find('.form-field').each(function(){
					if ($(this).find('.mapping_to').val() != "") {
						var skey = $(this).find('.mapping_from').val();
						if ('' != skey){
							var obj = {};
							obj[skey] = $(this).find('.mapping_to').val();
							values.push(obj);
						}
					}
				});
				$(this).find('input[name^=tax_mapping]').val(window.JSON.stringify(values));
			});

			var $uniqueKeyInput = $('.wpai-preview-unique-key');
			if ($uniqueKeyInput.length > 0) {
				var uniqueKeyValue = $uniqueKeyInput.val();
				if (uniqueKeyValue && uniqueKeyValue.trim() !== '') {
					$('#wpai-preview-unique-key-hidden').val(uniqueKeyValue);
				} else {
					$('#wpai-preview-unique-key-hidden').val('');
				}
			}

			var formData = new FormData($('.wpallimport-template')[0]);

			var $form = $('.wpallimport-template');
			var checkboxArrays = ['in_variations', 'is_visible', 'is_taxonomy', 'variable_in_variations', 'variable_is_visible', 'variable_is_taxonomy'];

			checkboxArrays.forEach(function(name) {
				var $checkboxes = $form.find('input[name="' + name + '[]"]');
				if ($checkboxes.length > 0) {
					formData.delete(name + '[]');

					$checkboxes.each(function() {
						formData.append(name + '[]', $(this).is(':checked') ? '1' : '0');
					});
				}
			});

			formData.append('action', 'wpai_run_preview_with_progress');
			formData.append('security', window.wp_all_import_security);
			formData.append('preview_mode', 'specific');
			formData.append('specific_record', requestedRecord);

			formData.append('post_status', this.previewPostStatus);

			if (self.importId) {
				formData.append('import_id', self.importId);
			}

			if (self.previewSessionId) {
				formData.append('preview_session_id', self.previewSessionId);
			}



			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				dataType: 'json',
				success: function(response) {
					if (!self.modalIsOpen) {
						if (response.post_id) {
							self.pendingCleanupPostIds.push(response.post_id);
						}
						if (response.post_ids && Array.isArray(response.post_ids)) {
							self.pendingCleanupPostIds = self.pendingCleanupPostIds.concat(response.post_ids);
						}
						return;
					}

					self.enableNavigation();

					if (response.preview_session_id && !self.previewSessionId) {
						self.previewSessionId = response.preview_session_id;
					}

					if (response.total_records && response.total_records > 0) {
						self.totalRecords = response.total_records;
						self.updateNavigationButtons();
					}

					if (response && response.was_skipped) {
						self.previewCache[requestedRecord] = {
							was_skipped: true,
							skip_reason: response.skip_reason || 'Record was skipped'
						};

						self.showSkippedNotice(response.skip_reason || 'Record was skipped');

						// Start preloading after first successful preview (only for record 1)
						if (self.currentRecord === 1 && !self.isPreloading && self.totalRecords > 1) {
							setTimeout(function() {
								self.startPreloading();
							}, 1000);
						}
					} else if (response && response.edit_url) {
						self.previewPostId = response.post_id;
						self.previewEditUrl = response.edit_url;
						self.previewViewUrl = response.view_url || '';

						// Cache this preview
						self.previewCache[requestedRecord] = {
							post_id: response.post_id,
							edit_url: response.edit_url,
							view_url: response.view_url
						};

						// Track this post ID for deletion on close
						if (response.post_id && self.createdPostIds.indexOf(response.post_id) === -1) {
							self.createdPostIds.push(response.post_id);
						}

						// Also track multiple post IDs if returned
						if (response.post_ids && Array.isArray(response.post_ids)) {
							response.post_ids.forEach(function(id) {
								if (self.createdPostIds.indexOf(id) === -1) {
									self.createdPostIds.push(id);
								}
							});
						}

						// Iframes are already fresh (recreated in navigateRecord)
						// Ensure loading indicators are visible
						$('.wpai-iframe-loading').show();

						// Only update visible if this response matches the requested record
						if (self.currentRecord === requestedRecord) {
							var addCacheBuster = function(url){
								if (!url) return url;
								var sep = url.indexOf('?') === -1 ? '?' : '&';
								return url + sep + 'wpai_preview=1&wpai_reload=' + Date.now();
							};
							var adminUrl = addCacheBuster(response.edit_url);
							var viewUrl = addCacheBuster(response.view_url || '');

							if (adminUrl) {
								// Set src and wait a brief moment for browser to start loading
								$('#wpai-preview-admin-iframe').attr('src', adminUrl);

								// Show iframe after very brief delay (allows browser to start rendering)
								// Hide loading indicator so user sees progressive rendering
								setTimeout(function() {
									$('#wpai-preview-admin-iframe').show();
									$('#wpai-preview-admin-iframe').siblings('.wpai-iframe-loading').hide();
								}, 100);
							}

							if (viewUrl) {
								// Set src and wait a brief moment for browser to start loading
								$('#wpai-preview-frontend-iframe').attr('src', viewUrl);

								// Show iframe after very brief delay (allows browser to start rendering)
								// Hide loading indicator so user sees progressive rendering
								setTimeout(function() {
									$('#wpai-preview-frontend-iframe').show();
									$('#wpai-preview-frontend-iframe').siblings('.wpai-iframe-loading').hide();
								}, 100);
							}
						}

						// Start preloading after first successful preview (only for record 1)
						if (self.currentRecord === 1 && !self.isPreloading && self.totalRecords > 1) {
							setTimeout(function() {
								self.startPreloading();
							}, 1000);
						}
					} else {
						var errorMsg = 'Preview failed';
						if (response && response.message) {
							errorMsg = response.message;
						} else if (response && response.data && response.data.message) {
							errorMsg = response.data.message;
						}
						alert(errorMsg);
					}
				},
				error: function(xhr, status, error) {
					// Re-enable navigation after error
					self.enableNavigation();

					// Try to parse error message and validation errors from response
					var errorMsg = 'An error occurred: ' + error;
					var validationErrors = [];

					try {
						var response = JSON.parse(xhr.responseText);

						// Update total records count if provided (even on error)
						if (response.total_records && response.total_records > 0) {
							self.totalRecords = response.total_records;
							self.updateNavigationButtons();
						}

						// Extract error message
						if (response.data && response.data.message) {
							errorMsg = response.data.message;
						} else if (response.message) {
							errorMsg = response.message;
						} else if (response.log) {
							// Show the log if available
							errorMsg = 'Preview failed. Check console for details.';
						}

						// Extract validation errors array
						if (response.data && response.data.errors && Array.isArray(response.data.errors)) {
							validationErrors = response.data.errors;
						}
					} catch (e) {
						// Not JSON, use default error
					}

					// Display error in WP All Import style
					self.showValidationError(errorMsg, validationErrors);
				}
			});
		},

		showValidationError: function(errorMsg, validationErrors) {
			var self = this;

			// Hide loading indicators
			$('.wpai-iframe-loading').hide();

			// Hide navigation controls when there's an error
			$('.wpai-preview-navigation').hide();

			// Hide iframes
			$('#wpai-preview-admin-iframe').hide();
			$('#wpai-preview-frontend-iframe').hide();

			// Build error notice HTML in WP All Import style
			var iconUrl = '<?php echo esc_js(WP_ALL_IMPORT_ROOT_URL); ?>/static/img/ui_4.0/exclamation.png';
			var errorHtml = '<div class="rad4 first-step-errors" style="display: block; border: 1px solid #ddd; margin: 20px; padding: 12px 0; background: #fff; box-shadow: none;">';
			errorHtml += '<div class="wpallimport-notify-wrapper">';
			errorHtml += '<div class="error-headers exclamation" style="background: url(\'' + iconUrl + '\') 0 50% no-repeat; padding-left: 80px; margin: 20px 40px;">';
			errorHtml += '<h3 style="color: #425f9a; margin-bottom: 0; margin-top: 3px; font-size: 22px; line-height: 28px;">Preview Error</h3>';
			errorHtml += '<h4 style="color: #777; margin-top: 5px; font-size: 20px;">' + errorMsg + '</h4>';

			// Add validation errors list if present
			if (validationErrors && validationErrors.length > 0) {
				errorHtml += '<div style="margin-top: 15px;">';
				errorHtml += '<ul style="list-style: disc; margin-left: 20px; color: #777; font-size: 16px; line-height: 1.6;">';
				for (var i = 0; i < validationErrors.length; i++) {
					errorHtml += '<li>' + validationErrors[i] + '</li>';
				}
				errorHtml += '</ul>';
				errorHtml += '</div>';
			}

			errorHtml += '</div>'; // .error-headers
			errorHtml += '</div>'; // .wpallimport-notify-wrapper
			errorHtml += '<div style="text-align: center; margin-top: 20px;">';
			errorHtml += '<a href="#" class="button button-secondary wpai-dismiss-error">Dismiss</a>';
			errorHtml += '</div>';
			errorHtml += '</div>';

			// Show the notice container and add the error HTML (both admin and frontend)
			$('#wpai-preview-notices').html(errorHtml).show();
			$('#wpai-preview-notices-frontend').html(errorHtml).show();

			// Attach click handler to dismiss button
			$('.wpai-dismiss-error').off('click').on('click', function(e) {
				e.preventDefault();
				// Trigger the modal close button click
				$('.wpai-full-preview-close').trigger('click');
			});
		},

		showSkippedNotice: function(skipReason) {
			var self = this;

			// Hide loading indicators
			$('.wpai-iframe-loading').hide();

			// Hide iframes
			$('#wpai-preview-admin-iframe').hide();
			$('#wpai-preview-frontend-iframe').hide();

			// Build skipped notice HTML in WP All Import style
			var iconUrl = '<?php echo esc_js(WP_ALL_IMPORT_ROOT_URL); ?>/static/img/ui_4.0/info.png';
			var noticeHtml = '<div class="rad4 first-step-errors" style="display: block; border: 1px solid #ddd; margin: 20px; padding: 12px 0; background: #fff; box-shadow: none;">';
			noticeHtml += '<div class="wpallimport-notify-wrapper">';
			noticeHtml += '<div class="error-headers exclamation" style="background: url(\'' + iconUrl + '\') 0 50% no-repeat; padding-left: 80px; margin: 20px 40px;">';
			noticeHtml += '<h3 style="color: #425f9a; margin-bottom: 0; margin-top: 3px; font-size: 22px; line-height: 28px;">Record Skipped</h3>';
			noticeHtml += '<h4 style="color: #777; margin-top: 5px; font-size: 20px;">' + skipReason + '</h4>';
			noticeHtml += '<p style="color: #777; margin-top: 10px; font-size: 16px;">You can navigate to other records using the navigation controls above.</p>';
			noticeHtml += '</div>'; // .error-headers
			noticeHtml += '</div>'; // .wpallimport-notify-wrapper
			noticeHtml += '</div>';

			// Show the notice container and add the notice HTML (both admin and frontend)
			$('#wpai-preview-notices').html(noticeHtml).show();
			$('#wpai-preview-notices-frontend').html(noticeHtml).show();
		},

		loadFieldList: function() {
			var self = this;

			// Copy the field structure from the existing tag sidebar on the template page
			var $existingFields = $('.tag .wpallimport-xml').first();

			if ($existingFields.length > 0) {
				// Clone the field structure
				var $clonedFields = $existingFields.clone();
				$('#wpai-preview-field-container').html($clonedFields);

				// Initialize draggable functionality on the cloned elements
				if (typeof $.fn.xml === 'function') {
					$clonedFields.xml({dragable: true});
				}

				// Make the unique identifier input droppable
				if (typeof wpaiMakeDroppable === 'function') {
					wpaiMakeDroppable();
				}
			} else {
				$('#wpai-preview-field-container').html('<p style="color: #d63638; text-align: center; padding: 20px;">No fields available. Please ensure you are on the template page.</p>');
			}
		},

		loadSettings: function(callback) {
			var self = this;

			var urlParams = new URLSearchParams(window.location.search);
			var importId = urlParams.get('id') || 0;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpai_load_preview_settings',
					security: window.wp_all_import_security,
					import_id: importId
				},
				success: function(response) {
					if (response.success && response.data.html) {
						$('#wpai-preview-settings-content').html(response.data.html);

						if (response.data.post_type) {
							self.importType = response.data.post_type;

							var noFrontendTypes = [
								'import_users',
								'shop_customer',
								'taxonomies',
								'comments',
								'woo_reviews',
								'gf_entries',
								'shop_order'
							];

							if (noFrontendTypes.indexOf(self.importType) !== -1) {
								$('.wpai-preview-tab[data-tab="frontend"]')
									.addClass('disabled')
									.css({
										'opacity': '0.5',
										'cursor': 'not-allowed',
										'pointer-events': 'none'
									});
							}
						}

						$('#wpai-preview-settings-content').find('.wpallimport-collapsed-header').off('click').on('click', function() {
							$(this).parent().toggleClass('closed');
						});

						$('#wpai-preview-post-status').off('change').on('change', function() {
							self.previewPostStatus = $(this).val();
							self.previewPostStatusInitialized = true;
						});

						var $postStatus = $('#wpai-preview-post-status');
						if ($postStatus.length) {
							if (self.previewPostStatusInitialized) {
								$postStatus.val(self.previewPostStatus);
							} else {
								self.previewPostStatus = $postStatus.val();
							}
						}

					// Handle records to preload setting
					$('.wpai-preview-records-to-preload').off('change').on('change', function() {
						var value = parseInt($(this).val());
						if (value >= 1 && value <= 100) {
							self.recordsToPreload = value;
						} else {
							$(this).val(self.recordsToPreload);
						}
					});

					var $recordsToPreload = $('.wpai-preview-records-to-preload');
					if ($recordsToPreload.length) {
						$recordsToPreload.val(self.recordsToPreload);
					}

					var $uniqueKeyInput = $('.wpai-preview-unique-key');

					$uniqueKeyInput.off('input change keyup blur').on('input change keyup blur', function() {
						self.previewUniqueKey = $(this).val();
						self.previewUniqueKeyInitialized = true;
					});

					var $uniqueKey = $('.wpai-preview-unique-key');
					if ($uniqueKey.length) {
						if (self.previewUniqueKeyInitialized) {
							$uniqueKey.val(self.previewUniqueKey);
						} else {
							self.previewUniqueKey = $uniqueKey.val();
						}
					}

					$('#wpai-auto-detect-unique-key').off('click').on('click', function(e) {
						e.preventDefault();
						self.autoDetectUniqueKey();
					});

					$('#wpai-refresh-preview-btn').off('click').on('click', function() {
						self.refreshPreview();
					});

					$('.wpai-toggle-field-list').off('click').on('click', function(e) {
						e.preventDefault();
						var $btn = $(this);
						var $sidebar = $('.wpai-field-list-sidebar');
						var state = $btn.data('state');

						if (state === 'closed') {
							if ($('#wpai-preview-field-container').find('.wpallimport-xml').length === 0) {
								self.loadFieldList();
							}

							$sidebar.slideDown(300);
							$btn.text('<?php _e('Close Field List', 'wp-all-import-pro'); ?>').data('state', 'open');
						} else {
							// Hide sidebar
							$sidebar.slideUp(300);
							$btn.text('<?php _e('Show Available Fields', 'wp-all-import-pro'); ?>').data('state', 'closed');
						}
					});
					} else {
						var errorMsg = 'Failed to load settings';
						if (response && response.data && response.data.message) {
							errorMsg = response.data.message;
						}
						$('#wpai-preview-settings-content').html('<p style="color: #d63638; text-align: center; padding: 40px;">' + errorMsg + '</p>');
					}

					// Call callback after settings are loaded (success or failure)
					if (callback && typeof callback === 'function') {
						callback();
					}
				},
				error: function(xhr, status, error) {
					$('#wpai-preview-settings-content').html('<p style="color: #d63638; text-align: center; padding: 40px;">An error occurred while loading settings.</p>');

					// Call callback even on error so preview doesn't hang
					if (callback && typeof callback === 'function') {
						callback();
					}
				}
			});
		},

		/**
		 * Delete all preview records for the current session
		 * This is more comprehensive than deletePreviewPosts as it deletes ALL records
		 * from the preview import, not just the ones we tracked in createdPostIds.
		 * This prevents the bug where rapid navigation causes some records to not be tracked.
		 */
		deleteAllPreviewRecordsForSession: function(callback) {
			var self = this;

			if (!self.previewSessionId) {
				if (callback) callback();
				return;
			}

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpai_delete_preview_session',
					security: window.wp_all_import_security,
					preview_session_id: self.previewSessionId
				},
				success: function(response) {
					if (callback) callback();
				},
				error: function(xhr, status, error) {
					// Even on error, call callback to allow modal to close
					if (callback) callback();
				}
			});
		},

		deletePreviewPosts: function(callback) {
			var self = this;

			if (self.createdPostIds.length === 0) {
				if (callback) callback();
				return;
			}

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpai_delete_preview_posts',
					security: window.wp_all_import_security,
					post_ids: self.createdPostIds
				},
				success: function(response) {
					if (callback) callback();
				},
				error: function(xhr, status, error) {
					if (callback) callback();
				}
			});
		},

		/**
		 * Delete specific preview posts by ID array
		 * Used to clean up late-arriving AJAX responses after modal close
		 */
		deleteSpecificPreviewPosts: function(postIds, callback) {
			var self = this;

			if (!postIds || postIds.length === 0) {
				if (callback) callback();
				return;
			}

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpai_delete_preview_posts',
					security: window.wp_all_import_security,
					post_ids: postIds
				},
				success: function(response) {
					if (callback) callback();
				},
				error: function(xhr, status, error) {
					if (callback) callback();
				}
			});
		},

		/**
		 * Synchronous cleanup for page unload - deletes all preview records for session
		 */
		deletePreviewSessionSync: function() {
			var self = this;

			if (!self.previewSessionId) {
				return;
			}

			// Use navigator.sendBeacon for reliable cleanup during page unload
			if (navigator.sendBeacon) {
				var formData = new FormData();
				formData.append('action', 'wpai_delete_preview_session');
				formData.append('security', window.wp_all_import_security);
				formData.append('preview_session_id', self.previewSessionId);

				// Send the beacon - returns true if queued successfully
				navigator.sendBeacon(ajaxurl, formData);
			} else {
				// Fallback: Try fetch with keepalive for older browsers
				try {
					var formData = new FormData();
					formData.append('action', 'wpai_delete_preview_session');
					formData.append('security', window.wp_all_import_security);
					formData.append('preview_session_id', self.previewSessionId);

					fetch(ajaxurl, {
						method: 'POST',
						body: formData,
						keepalive: true
					});
				} catch (e) {
					// Silently fail - cleanup will happen via transient expiry
				}
			}

			// Clear tracking
			self.previewSessionId = null;
			self.createdPostIds = [];
			self.previewCache = {};
		},

		deletePreviewPostsSync: function() {
			var self = this;

			if (self.createdPostIds.length === 0) {
				return;
			}

			if (navigator.sendBeacon) {
				var formData = new FormData();
				formData.append('action', 'wpai_delete_preview_posts');
				formData.append('security', window.wp_all_import_security);

				for (var i = 0; i < self.createdPostIds.length; i++) {
					formData.append('post_ids[]', self.createdPostIds[i]);
				}

				navigator.sendBeacon(ajaxurl, formData);
			} else {
				try {
					var formData = new FormData();
					formData.append('action', 'wpai_delete_preview_posts');
					formData.append('security', window.wp_all_import_security);
					for (var i = 0; i < self.createdPostIds.length; i++) {
						formData.append('post_ids[]', self.createdPostIds[i]);
					}

					fetch(ajaxurl, {
						method: 'POST',
						body: formData,
						keepalive: true
					});
				} catch (e) {
				}
			}

			self.createdPostIds = [];
			self.previewCache = {};
		},

		showSettingsValidationError: function(errorMsg) {
			$('#wpai-settings-validation-error').remove();

			var iconUrl = '<?php echo esc_js(WP_ALL_IMPORT_ROOT_URL); ?>/static/img/ui_4.0/exclamation.png';
			var errorHtml = '<div id="wpai-settings-validation-error" class="rad4 first-step-errors" style="display: block; border: 1px solid #ddd; margin: 20px auto; padding: 12px 0; background: #fff; box-shadow: none; max-width: 800px;">';
			errorHtml += '<div class="wpallimport-notify-wrapper">';
			errorHtml += '<div class="error-headers exclamation" style="background: url(\'' + iconUrl + '\') 0 50% no-repeat; padding-left: 80px; margin: 20px 40px;">';
			errorHtml += '<h3 style="color: #425f9a; margin-bottom: 0; margin-top: 3px; font-size: 22px; line-height: 28px;">Validation Error</h3>';
			errorHtml += '<h4 style="color: #777; margin-top: 5px; font-size: 20px;">' + errorMsg + '</h4>';
			errorHtml += '</div>';
			errorHtml += '</div>';
			errorHtml += '<div style="text-align: center; margin-top: 20px;">';
			errorHtml += '<a href="#" class="button button-secondary wpai-dismiss-settings-error">Dismiss</a>';
			errorHtml += '</div>';
			errorHtml += '</div>';

			$('#wpai-preview-settings-content').prepend(errorHtml);

			$('#wpai-settings-validation-error').off('click', '.wpai-dismiss-settings-error').on('click', '.wpai-dismiss-settings-error', function(e) {
				e.preventDefault();
				$('#wpai-settings-validation-error').fadeOut(200, function() {
					$(this).remove();
				});
			});

			// Scroll to top of settings to show the error
			$('.wpai-preview-settings-container').scrollTop(0);
		},

		/**
		 * Clear validation error from settings tab
		 */
		clearSettingsValidationError: function() {
			$('#wpai-settings-validation-error').fadeOut(200, function() {
				$(this).remove();
			});
		},

		/**
		 * Auto-detect unique key for preview
		 * Uses the field data already loaded on the page
		 */
		autoDetectUniqueKey: function() {
			var self = this;

			// Show loading state
			var $btn = $('#wpai-auto-detect-unique-key');
			var originalText = $btn.text();
			$btn.text('<?php _e('Detecting...', 'wp-all-import-pro'); ?>').css('opacity', '0.6');

			// Get the auto-detected unique key from the data attribute or hidden input
			// This is set by the server when loading preview settings via AJAX
			var autoDetectedKey = $btn.data('auto-detected-key') || $('#wpai-tmp-unique-key').val() || '';

			if (autoDetectedKey) {
				// Update the unique key field
				$('.wpai-preview-unique-key').val(autoDetectedKey).trigger('change');

				// Clear any validation errors
				self.clearSettingsValidationError();

				// Show success message briefly
				$btn.text('<?php _e('Detected!', 'wp-all-import-pro'); ?>');
				setTimeout(function() {
					$btn.text(originalText).css('opacity', '1');
				}, 1500);
			} else {
				// No auto-detected key available
				alert('<?php _e('Could not auto-detect a unique key. Please manually specify a unique identifier using the available fields.', 'wp-all-import-pro'); ?>');
				$btn.text(originalText).css('opacity', '1');
			}
		},

		/**
		 * Refresh preview with updated settings
		 * Deletes all existing preview records and regenerates from scratch
		 */
		refreshPreview: function() {
			var self = this;

			// Validate unique identifier is not empty
			var $uniqueKey = $('.wpai-preview-unique-key');
			var uniqueKeyValue = $uniqueKey.val().trim();

			if (uniqueKeyValue === '') {
				// Highlight the unique key field
				$uniqueKey.addClass('wpai-validation-error');
				setTimeout(function() {
					$uniqueKey.removeClass('wpai-validation-error');
				}, 3000);

				// Switch to settings tab to show the error
				self.switchTab('settings');

				// Show validation error notice on settings tab
				self.showSettingsValidationError('<?php echo esc_js(__("Unique ID is currently empty and must be set. If you are not sure what to use as a Unique ID, click Auto-detect.", "wp-all-import-pro")); ?>');

				return; // Don't proceed with refresh
			}

			// Clear any validation errors before proceeding
			self.clearSettingsValidationError();

			// Add disabled state to button
			$('#wpai-refresh-preview-btn').addClass('wpai-refreshing');

			// Use comprehensive session-based cleanup if available
			var cleanupFunction = self.previewSessionId
				? function(callback) { self.deleteAllPreviewRecordsForSession(callback); }
				: function(callback) { self.deletePreviewPosts(callback); };

			// Delete all existing preview records
			cleanupFunction(function() {
				// Clear all state
				self.previewCache = {};
				self.createdPostIds = [];
				self.processingStates = {};
				self.preloadQueue = [];
				self.currentlyProcessing = null;
				self.isPreloading = false;
                self.highestPreloadedBatch = 0;

				// Clear session ID so we get a fresh one
				self.previewSessionId = null;

				// Reset to record 1
				self.currentRecord = 1;
				$('#wpai-preview-record-number').val(1);
				self.updateNavigationButtons();

				// Switch to WP Admin View tab and trigger appropriate flow
				self.switchTab('admin');

				// Load the preview
				self.loadPreview();

				// Re-enable button after preview loads
				setTimeout(function() {
					$('#wpai-refresh-preview-btn').removeClass('wpai-refreshing');
				}, 2000);
			});
		},

		/**
		 * Start preloading records in the background
		 * Loads batch 1 (records 1-10 by default, configurable via recordsToPreload)
		 */
		startPreloading: function() {
			var self = this;

			// Don't start if already preloading
			if (self.isPreloading) {
				return;
			}

			// Load first batch (records 1-10 by default)
			self.loadBatch(1);
		},

		/**
		 * Load a specific batch of records
		 * @param {number} batchNumber - Batch number (1 = records 1-10, 2 = records 11-20, etc.)
		 */
		loadBatch: function(batchNumber) {
			var self = this;

			// Don't load if we've already queued this batch
			if (batchNumber <= self.highestPreloadedBatch) {
				return;
			}

			// Calculate record range for this batch
			var startRecord = ((batchNumber - 1) * self.recordsToPreload) + 1;
			var endRecord = Math.min(batchNumber * self.recordsToPreload, self.totalRecords);

			// Don't load if start is beyond total records
			if (startRecord > self.totalRecords) {
				return;
			}

			// Add records to queue
			for (var i = startRecord; i <= endRecord; i++) {
				// Skip if already cached or queued
				if (self.previewCache[i] || self.processingStates[i]) {
					continue;
				}

				self.preloadQueue.push(i);
				self.processingStates[i] = 'queued';
			}

			// Update highest batch loaded
			self.highestPreloadedBatch = batchNumber;

			// Start processing if not already running
			if (!self.isPreloading) {
				self.isPreloading = true;
				self.processNextInQueue();
			}
		},

		/**
		 * Check if we need to load the next batch based on current record
		 * Triggers when user reaches within 4 records of the end of current batch
		 */
		checkAndLoadNextBatch: function() {
			var self = this;

			// Calculate which batch the current record belongs to
			var currentBatch = Math.ceil(self.currentRecord / self.recordsToPreload);

			// Calculate how far into the current batch we are
			var positionInBatch = self.currentRecord - ((currentBatch - 1) * self.recordsToPreload);

			// If we're within trigger offset of the end of the batch, load next batch
			if (positionInBatch >= (self.recordsToPreload - self.preloadTriggerOffset)) {
				var nextBatch = currentBatch + 1;
				self.loadBatch(nextBatch);
			}
		},

		/**
		 * Process the next record in the preload queue
		 */
		processNextInQueue: function() {
			var self = this;

			// Check if user requested a specific record (priority)
			if (self.userRequestedRecord !== null) {
				var requestedRecord = self.userRequestedRecord;
				self.userRequestedRecord = null;

				// If this record isn't complete, process it immediately
				if (!self.previewCache[requestedRecord]) {
					self.currentlyProcessing = requestedRecord;
					self.processingStates[requestedRecord] = 'processing';

					// Remove from queue if it's there
					var queueIndex = self.preloadQueue.indexOf(requestedRecord);
					if (queueIndex > -1) {
						self.preloadQueue.splice(queueIndex, 1);
					}

					self.loadPreviewForRecord(requestedRecord, function(success) {
						self.currentlyProcessing = null;
						if (success) {
							self.processingStates[requestedRecord] = 'complete';
						} else {
							self.processingStates[requestedRecord] = 'error';
						}

						// Continue with queue after priority request
						setTimeout(function() {
							self.processNextInQueue();
						}, 500);
					});
					return;
				}
			}

			// No more records to preload
			if (self.preloadQueue.length === 0) {
				self.isPreloading = false;
				self.currentlyProcessing = null;
				return;
			}

			// Get next record from queue
			var nextRecord = self.preloadQueue.shift();

			// Skip if already cached
			if (self.previewCache[nextRecord]) {
				self.processingStates[nextRecord] = 'complete';
				self.processNextInQueue();
				return;
			}

			// Process this record
			self.currentlyProcessing = nextRecord;
			self.processingStates[nextRecord] = 'processing';

			self.loadPreviewForRecord(nextRecord, function(success) {
				self.currentlyProcessing = null;
				if (success) {
					self.processingStates[nextRecord] = 'complete';
				} else {
					self.processingStates[nextRecord] = 'error';
				}

				// Update progress if initial preload is in progress
				if (self.initialPreloadInProgress) {
					self.updatePreloadProgress();
				}

				// Process next record after a short delay
				setTimeout(function() {
					self.processNextInQueue();
				}, 500);
			});
		},

		/**
		 * Load preview for a specific record number (used by preloading)
		 * @param {number} recordNum - Record number to load
		 * @param {function} callback - Callback function(success)
		 */
		loadPreviewForRecord: function(recordNum, callback) {
			var self = this;

			// Sync TinyMCE editors before collecting form data
			if (typeof tinyMCE != 'undefined') {
				tinyMCE.triggerSave(false, false);
			}

			// Serialize taxonomy mappings before collecting form data
			$('.custom_type[rel=tax_mapping]').each(function(){
				var values = new Array();
				$(this).find('.form-field').each(function(){
					if ($(this).find('.mapping_to').val() != "") {
						var skey = $(this).find('.mapping_from').val();
						if ('' != skey){
							var obj = {};
							obj[skey] = $(this).find('.mapping_to').val();
							values.push(obj);
						}
					}
				});
				$(this).find('input[name^=tax_mapping]').val(window.JSON.stringify(values));
			});

			// Update hidden unique key field with current value from settings tab
			var $uniqueKeyInput = $('.wpai-preview-unique-key');
			if ($uniqueKeyInput.length > 0) {
				var uniqueKeyValue = $uniqueKeyInput.val();
				if (uniqueKeyValue && uniqueKeyValue.trim() !== '') {
					$('#wpai-preview-unique-key-hidden').val(uniqueKeyValue);
				} else {
					$('#wpai-preview-unique-key-hidden').val('');
				}
			}

			// Get form data
			var formData = new FormData($('.wpallimport-template')[0]);

			// Ensure unchecked checkboxes are included in FormData
			var $form = $('.wpallimport-template');
			var checkboxArrays = ['in_variations', 'is_visible', 'is_taxonomy', 'variable_in_variations', 'variable_is_visible', 'variable_is_taxonomy'];

			checkboxArrays.forEach(function(name) {
				var $checkboxes = $form.find('input[name="' + name + '[]"]');
				if ($checkboxes.length > 0) {
					formData.delete(name + '[]');
					$checkboxes.each(function() {
						formData.append(name + '[]', $(this).is(':checked') ? '1' : '0');
					});
				}
			});

			formData.append('action', 'wpai_run_preview_with_progress');
			formData.append('security', window.wp_all_import_security);
			formData.append('preview_mode', 'specific');
			formData.append('specific_record', recordNum);
			formData.append('post_status', this.previewPostStatus);

			if (self.importId) {
				formData.append('import_id', self.importId);
			}

			if (self.previewSessionId) {
				formData.append('preview_session_id', self.previewSessionId);
			}

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				dataType: 'json',
				timeout: 300000, // 5 minute timeout
				success: function(response) {
					// If modal is closed, track these post IDs for cleanup but don't cache
					if (!self.modalIsOpen) {
						if (response.post_id) {
							self.pendingCleanupPostIds.push(response.post_id);
						}
						if (response.post_ids && Array.isArray(response.post_ids)) {
							self.pendingCleanupPostIds = self.pendingCleanupPostIds.concat(response.post_ids);
						}
						if (callback) callback(false); // Don't count as success since modal is closed
						return;
					}

					// Store session ID from first response
					if (response.preview_session_id && !self.previewSessionId) {
						self.previewSessionId = response.preview_session_id;
					}

					// Check if record was skipped
					if (response && response.was_skipped) {
						// Cache this as a skipped record
						self.previewCache[recordNum] = {
							was_skipped: true,
							skip_reason: response.skip_reason || 'Record was skipped',
							status: 'complete'
						};
						if (callback) callback(true); // Still count as success for preloading
					} else if (response.post_id && response.edit_url) {
						// Only edit_url is required - view_url may be empty for some import types
						// Cache this preview
						self.previewCache[recordNum] = {
							post_id: response.post_id,
							edit_url: response.edit_url,
							view_url: response.view_url || '',
							status: 'complete'
						};

						// Track created post ID
						if (self.createdPostIds.indexOf(response.post_id) === -1) {
							self.createdPostIds.push(response.post_id);
						}

						// Track multiple post IDs if returned
						if (response.post_ids && Array.isArray(response.post_ids)) {
							response.post_ids.forEach(function(id) {
								if (self.createdPostIds.indexOf(id) === -1) {
									self.createdPostIds.push(id);
								}
							});
						}

						if (callback) callback(true);
					} else {
						// Response doesn't have expected data - likely an error
						// If this is during initial preload, show the error and stop
						if (self.initialPreloadInProgress) {
							// Stop the preload process
							self.initialPreloadInProgress = false;
							self.waitingForInitialPreload = false;
							self.isPreloading = false;

							// Re-enable navigation
							self.enableNavigation();

							// Extract error message
							var errorMsg = 'Preview failed';
							var validationErrors = [];

							if (response && response.message) {
								errorMsg = response.message;
							} else if (response && response.data && response.data.message) {
								errorMsg = response.data.message;
							}

							if (response && response.data && response.data.errors && Array.isArray(response.data.errors)) {
								validationErrors = response.data.errors;
							}

							// Display the error
							self.showValidationError(errorMsg, validationErrors);
						}

						if (callback) callback(false);
					}
				},
				error: function(xhr, status, error) {
					// If this is during initial preload, we need to show the error and stop
					if (self.initialPreloadInProgress) {
						// Stop the preload process
						self.initialPreloadInProgress = false;
						self.waitingForInitialPreload = false;
						self.isPreloading = false;

						// Re-enable navigation
						self.enableNavigation();

						// Try to parse and display the error
						var errorMsg = 'An error occurred: ' + error;
						var validationErrors = [];

						try {
							var response = JSON.parse(xhr.responseText);
							if (response.data && response.data.message) {
								errorMsg = response.data.message;
							} else if (response.message) {
								errorMsg = response.message;
							}

							if (response.data && response.data.errors && Array.isArray(response.data.errors)) {
								validationErrors = response.data.errors;
							}
						} catch (e) {
							// Not JSON, use default error
						}

						// Display the error
						self.showValidationError(errorMsg, validationErrors);
					}

					if (callback) callback(false);
				}
			});
		},

		/**
		 * Generate a random session ID
		 */
		generateSessionId: function() {
			var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
			var sessionId = '';
			for (var i = 0; i < 32; i++) {
				sessionId += chars.charAt(Math.floor(Math.random() * chars.length));
			}
			return sessionId;
		},

		/**
		 * Register a preview session on the server
		 * This creates the session transient so heartbeat can keep it alive
		 */
		registerPreviewSession: function(sessionId) {
			jQuery.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wpai_register_preview_session',
					session_id: sessionId,
					security: '<?php echo wp_create_nonce('wpai_preview_session'); ?>'
				}
			});
		},

		/**
		 * Setup WordPress heartbeat to keep preview sessions alive
		 * This prevents active preview sessions from expiring while users are on the template page
		 * Uses the official WordPress heartbeat API
		 */
		setupHeartbeat: function() {
			var self = this;

			// Use the official WordPress heartbeat API via wp.hooks
			if (typeof wp !== 'undefined' && typeof wp.hooks !== 'undefined') {
				// Remove any existing handlers first to prevent duplicates
				wp.hooks.removeAction('heartbeat.send', 'wpai-preview');
				wp.hooks.removeAction('heartbeat.tick', 'wpai-preview');

				// Add heartbeat.send hook to include session ID with every heartbeat
				wp.hooks.addAction('heartbeat.send', 'wpai-preview', function(data) {
					var sessionId = fullPreviewModal.previewSessionId;
					if (sessionId) {
						data.wpai_preview_session_id = sessionId;
					}
				});

				// Add heartbeat.tick hook to handle responses
				wp.hooks.addAction('heartbeat.tick', 'wpai-preview', function(response) {
					// Check if session has expired while modal is still open
					if (response.wpai_preview_session_alive === false && fullPreviewModal.modalIsOpen) {
						// Session expired - set flag and close modal
						// The cleanup notice will show the timeout message
						fullPreviewModal.sessionTimedOut = true;
						fullPreviewModal.closeModal();
					}
				});
			}
		}
	};

	fullPreviewModal.init();
});
</script>

