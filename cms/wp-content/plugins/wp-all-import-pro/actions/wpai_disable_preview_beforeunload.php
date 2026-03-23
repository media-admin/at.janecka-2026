<?php
/**
 * Disable WordPress/Gutenberg beforeunload warnings when pages are loaded in preview iframes
 *
 * This prevents the "Leave site? Changes you made may not be saved" browser alert
 * from appearing when users navigate between preview records, close the modal, or switch tabs.
 *
 * This file is automatically loaded by the plugin's action loading mechanism.
 * The hooks are registered directly in this file rather than through a function callback.
 */

// Only register hooks if this is loaded in the WordPress environment
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Detect if the current request is for a preview iframe
 *
 * @return bool True if this is a preview iframe request
 */
function wpai_is_preview_iframe_request() {
	return isset($_GET['wpai_preview']) && $_GET['wpai_preview'] == '1';
}

/**
 * Dequeue scripts that add beforeunload handlers when in preview mode
 *
 * This runs on admin_enqueue_scripts with high priority to ensure it runs
 * after WordPress core and Gutenberg have enqueued their scripts.
 */
function wpai_disable_preview_beforeunload_scripts() {
	// Only run in admin and only for preview iframe requests
	if (!is_admin() || !wpai_is_preview_iframe_request()) {
		return;
	}

	// Dequeue autosave script (adds beforeunload for classic editor)
	wp_dequeue_script('autosave');

	// For Gutenberg/Block Editor, we need to prevent the editor from initializing
	// its change detection. We'll do this by adding inline script that disables it.
	// This must run BEFORE the editor scripts load.
}
add_action('admin_enqueue_scripts', 'wpai_disable_preview_beforeunload_scripts', 999);

/**
 * Add inline script to disable Gutenberg's beforeunload handler
 * 
 * This injects JavaScript that runs before Gutenberg initializes and prevents
 * it from adding the beforeunload event listener.
 */
function wpai_disable_gutenberg_beforeunload() {
	// Only run in admin and only for preview iframe requests
	if (!is_admin() || !wpai_is_preview_iframe_request()) {
		return;
	}

	// Check if we're on a post edit screen (where Gutenberg would load)
	$screen = get_current_screen();
	if (!$screen || $screen->base !== 'post') {
		return;
	}

	// Inject script that disables beforeunload BEFORE Gutenberg loads
	?>
	<script type="text/javascript">
	(function() {
		// Store original addEventListener
		var originalAddEventListener = window.addEventListener;

		// Override addEventListener to block beforeunload/unload events
		window.addEventListener = function(type, listener, options) {
			if (type === 'beforeunload' || type === 'unload') {
				// Silently ignore - don't add the listener
				return;
			}
			return originalAddEventListener.call(this, type, listener, options);
		};

		// Also continuously clear window.onbeforeunload
		var clearBeforeUnload = function() {
			if (window.onbeforeunload !== null) {
				window.onbeforeunload = null;
			}
		};
		setInterval(clearBeforeUnload, 100);

		// For Gutenberg: Disable editor warnings when wp.data is available
		var disableGutenbergWarnings = function() {
			if (window.wp && window.wp.data) {
				var dispatch = window.wp.data.dispatch;
				var select = window.wp.data.select;

				if (dispatch && select && dispatch('core/editor') && select('core/editor')) {
					// Lock post saving to prevent dirty state detection
					var editorDispatch = dispatch('core/editor');
					if (typeof editorDispatch.lockPostSaving === 'function') {
						editorDispatch.lockPostSaving('wpai-preview-mode');
					}

					// Subscribe to editor changes and immediately reset dirty state
					if (window.wp.data.subscribe) {
						window.wp.data.subscribe(function() {
							try {
								var editorSelect = select('core/editor');
								if (editorSelect && typeof editorSelect.isEditedPostDirty === 'function') {
									if (editorSelect.isEditedPostDirty()) {
										// Reset the dirty state by resetting blocks
										var blocks = editorSelect.getEditorBlocks ? editorSelect.getEditorBlocks() : [];
										var dispatch = window.wp.data.dispatch('core/editor');
										if (dispatch && typeof dispatch.resetEditorBlocks === 'function') {
											dispatch.resetEditorBlocks(blocks);
										}
									}
								}
							} catch(e) {
								// Silently fail
							}
						});
					}

					return true;
				}
			}
			return false;
		};

		// Try to disable Gutenberg warnings repeatedly until successful
		var attempts = 0;
		var maxAttempts = 100;
		var gutenbergInterval = setInterval(function() {
			attempts++;
			if (disableGutenbergWarnings() || attempts >= maxAttempts) {
				clearInterval(gutenbergInterval);
			}
		}, 100);
	})();
	</script>
	<?php
}
add_action('admin_head', 'wpai_disable_gutenberg_beforeunload', 1);

