<?php
/**
 * Uninstall Script for Auto Daily Post Refresher
 *
 * Fired when the plugin is uninstalled. Cleans up all plugin data including:
 * - Plugin options
 * - Post meta data
 * - Scheduled cron events
 * - Transients
 *
 * @package Auto Daily Post Refresher
 * @since   1.0.0
 */

// Exit if accessed directly or if not uninstalling.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/**
 * Clean up plugin data for single site or multisite.
 */
if ( ! is_multisite() ) {
	// Single site cleanup.
	adpr_cleanup_plugin_data();
} else {
	// Multisite cleanup.
	$blog_ids         = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
	$original_blog_id = get_current_blog_id();

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		adpr_cleanup_plugin_data();
	}

	switch_to_blog( $original_blog_id );
}

/**
 * Clean up all plugin data.
 *
 * Removes options, post meta, cron events, and transients created by the plugin.
 *
 * @since 1.0.0
 */
function adpr_cleanup_plugin_data() {
	global $wpdb;

	// Remove plugin options.
	delete_option( 'adpr_settings' );
	delete_option( 'adpr_update_log' );
	delete_option( 'adpr_cron_status' );
	delete_option( 'adpr_activated' );
	delete_option( 'adpr_version' );

	// Remove all post meta created by the plugin.
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_adpr_%'" );

	// Remove scheduled cron events.
	$timestamp = wp_next_scheduled( 'adpr_daily_update' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'adpr_daily_update' );
	}

	// Clear all plugin transients.
	delete_transient( 'adpr_cron_running' );
	delete_transient( 'adpr_last_update' );
	delete_transient( 'adpr_update_stats' );

	// Remove all transients with adpr prefix (site-wide).
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_adpr_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_adpr_%'" );

	// Note: We do NOT remove the actual post dates that were changed.
	// Those are permanent changes to the posts and should remain.
	// If users want to revert dates, they should do so before uninstalling.
}
