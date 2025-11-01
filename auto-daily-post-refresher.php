<?php
/**
 * Plugin Name: Bulk Daily DateTime
 * Plugin URI:  https://github.com/Muminur/bulk-daily-datetime
 * Description: Automatically updates post publication dates daily to keep content fresh. Based on Bulk Datetime Change plugin but with automated scheduling.
 * Version:     2.0.0
 * Author:      Md Muminur Rahman
 * Author URI:  https://alternativechoice.org
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bulk-daily-datetime
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 *
 * @package Auto Daily Post Refresher
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
if ( ! defined( 'ADPR_VERSION' ) ) {
	define( 'ADPR_VERSION', '2.0.0' );
}
if ( ! defined( 'ADPR_PLUGIN_DIR' ) ) {
	define( 'ADPR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'ADPR_PLUGIN_URL' ) ) {
	define( 'ADPR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'ADPR_PLUGIN_BASENAME' ) ) {
	define( 'ADPR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Load required files.
 */
function adpr_load_files() {
	// Load core functionality class.
	if ( ! class_exists( 'AutoDailyPostRefresher' ) ) {
		$core_file = ADPR_PLUGIN_DIR . 'lib/class-autodailypostrefresher.php';
		if ( file_exists( $core_file ) ) {
			require_once $core_file;
		}
	}

	// Load admin interface class in admin only.
	if ( is_admin() ) {
		if ( ! class_exists( 'AutoDailyPostRefresherAdmin' ) ) {
			$admin_file = ADPR_PLUGIN_DIR . 'lib/class-autodailypostrefresheradmin.php';
			if ( file_exists( $admin_file ) ) {
				require_once $admin_file;
			}
		}

		// Load posts list table class.
		if ( ! class_exists( 'ADPR_Posts_List_Table' ) ) {
			$list_file = ADPR_PLUGIN_DIR . 'lib/class-adpr-posts-list-table.php';
			if ( file_exists( $list_file ) ) {
				require_once $list_file;
			}
		}
	}
}

/**
 * Initialize the plugin.
 * Load files first, then initialize classes.
 */
function adpr_init_plugin() {
	// Load all class files first
	adpr_load_files();

	// Initialize core functionality.
	if ( class_exists( 'AutoDailyPostRefresher' ) ) {
		$GLOBALS['adpr_core'] = new AutoDailyPostRefresher();
	}

	// Initialize admin interface.
	if ( is_admin() && class_exists( 'AutoDailyPostRefresherAdmin' ) ) {
		$GLOBALS['adpr_admin'] = new AutoDailyPostRefresherAdmin( $GLOBALS['adpr_core'] );
	}
}
// Hook early to ensure admin_menu hook registration happens in time
add_action( 'init', 'adpr_init_plugin', 1 );

/**
 * Plugin activation callback.
 */
function adpr_activate_plugin() {
	// Initialize default settings.
	$default_settings = array(
		'enabled'                 => false,
		'update_time'             => '03:00',
		'post_types'              => array( 'post' ),
		'update_publication_date' => true,
		'update_modified_date'    => false,
		'batch_size'              => 50,
		'last_run'                => '',
		'total_updates'           => 0,
	);

	// Only add defaults if settings don't exist.
	if ( ! get_option( 'adpr_settings' ) ) {
		add_option( 'adpr_settings', $default_settings );
	}

	// Initialize empty log.
	if ( ! get_option( 'adpr_update_log' ) ) {
		add_option( 'adpr_update_log', array() );
	}

	// Set activation flag.
	update_option( 'adpr_activated', time() );

	// Schedule cron job.
	if ( ! wp_next_scheduled( 'adpr_daily_update' ) ) {
		wp_schedule_event( time() + 86400, 'daily', 'adpr_daily_update' );
	}

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'adpr_activate_plugin' );

/**
 * Plugin deactivation callback.
 */
function adpr_deactivate_plugin() {
	// Remove scheduled cron job.
	$timestamp = wp_next_scheduled( 'adpr_daily_update' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'adpr_daily_update' );
	}

	// Clear any transients.
	delete_transient( 'adpr_cron_running' );

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'adpr_deactivate_plugin' );
