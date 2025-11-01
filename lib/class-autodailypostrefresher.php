<?php
/**
 * Core functionality class
 *
 * @package Auto Daily Post Refresher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AutoDailyPostRefresher class
 *
 * Handles cron scheduling and automatic post date updates.
 */
class AutoDailyPostRefresher {

	/**
	 * Constructor.
	 *
	 * Registers hooks for cron scheduling and processing.
	 */
	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );
		add_action( 'adpr_daily_update', array( $this, 'process_daily_update' ) );
	}

	/**
	 * Add custom cron schedule.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules.
	 */
	public function add_cron_schedule( $schedules ) {
		$schedules['adpr_daily'] = array(
			'interval' => DAY_IN_SECONDS,
			'display'  => __( 'Once Daily (ADPR)', 'bulk-daily-datetime' ),
		);
		return $schedules;
	}

	/**
	 * Schedule the daily cron job.
	 */
	public function schedule_cron() {
		if ( ! wp_next_scheduled( 'adpr_daily_update' ) ) {
			$settings = $this->get_settings();
			$time     = isset( $settings['update_time'] ) ? $settings['update_time'] : '03:00';
			list( $hour, $minute ) = explode( ':', $time );

			$timestamp = strtotime( "today {$hour}:{$minute}" );
			if ( $timestamp < time() ) {
				$timestamp = strtotime( "tomorrow {$hour}:{$minute}" );
			}

			wp_schedule_event( $timestamp, 'adpr_daily', 'adpr_daily_update' );
		}
	}

	/**
	 * Unschedule the daily cron job.
	 */
	public function unschedule_cron() {
		$timestamp = wp_next_scheduled( 'adpr_daily_update' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'adpr_daily_update' );
		}
	}

	/**
	 * Check if cron is scheduled.
	 *
	 * @return bool True if scheduled, false otherwise.
	 */
	public function is_cron_scheduled() {
		return (bool) wp_next_scheduled( 'adpr_daily_update' );
	}

	/**
	 * Process daily updates (cron callback).
	 *
	 * @param bool $force Force execution even if disabled (for manual triggers).
	 * @return array Array with 'success', 'count', and 'message' keys.
	 */
	public function process_daily_update( $force = false ) {
		$settings = $this->get_settings();

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ADPR: process_daily_update called. Force: ' . ( $force ? 'yes' : 'no' ) );
			error_log( 'ADPR: Enabled setting: ' . ( ! empty( $settings['enabled'] ) ? 'yes' : 'no' ) );
		}

		// Check if enabled (unless forced by manual trigger)
		if ( ! $force && empty( $settings['enabled'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ADPR: Stopped - plugin not enabled in settings' );
			}
			return array(
				'success' => false,
				'count'   => 0,
				'message' => __( 'Auto-updates are disabled in settings. Please enable them on the Settings page.', 'bulk-daily-datetime' ),
			);
		}

		// Check for concurrent execution
		if ( get_transient( 'adpr_cron_running' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ADPR: Stopped - another update is already running' );
			}
			return array(
				'success' => false,
				'count'   => 0,
				'message' => __( 'Another update is already running. Please wait and try again.', 'bulk-daily-datetime' ),
			);
		}

		set_transient( 'adpr_cron_running', true, HOUR_IN_SECONDS );

		$posts = $this->get_posts_for_update();
		$batch = isset( $settings['batch_size'] ) ? (int) $settings['batch_size'] : 50;
		$count = 0;

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ADPR: Found ' . count( $posts ) . ' posts marked for update' );
		}

		// Check if any posts found
		if ( empty( $posts ) ) {
			delete_transient( 'adpr_cron_running' );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ADPR: No posts marked for auto-update' );
			}
			return array(
				'success' => false,
				'count'   => 0,
				'message' => __( 'No posts are marked for auto-update. Please mark posts on the Post Selector page.', 'bulk-daily-datetime' ),
			);
		}

		foreach ( array_slice( $posts, 0, $batch ) as $post_id ) {
			if ( $this->update_post_date( $post_id ) ) {
				$count++;
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ADPR: Successfully updated ' . $count . ' posts' );
		}

		$settings['last_run']      = current_time( 'mysql' );
		$settings['total_updates'] = ( isset( $settings['total_updates'] ) ? (int) $settings['total_updates'] : 0 ) + $count;
		update_option( 'adpr_settings', $settings );

		delete_transient( 'adpr_cron_running' );

		return array(
			'success' => true,
			'count'   => $count,
			'message' => sprintf(
				/* translators: %d: number of posts updated */
				_n( '%d post updated successfully.', '%d posts updated successfully.', $count, 'bulk-daily-datetime' ),
				$count
			),
		);
	}

	/**
	 * Get posts marked for auto-update.
	 *
	 * @return array Array of post IDs.
	 */
	private function get_posts_for_update() {
		$settings   = $this->get_settings();
		$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post' );

		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => '_adpr_auto_update_enabled',
					'value' => 'yes',
				),
			),
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '========================================' );
			error_log( 'ADPR DEBUG: get_posts_for_update() called' );
			error_log( 'ADPR DEBUG: Post types to query: ' . print_r( $post_types, true ) );
			error_log( 'ADPR DEBUG: Query args: ' . print_r( $args, true ) );
		}

		$posts = get_posts( $args );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ADPR DEBUG: Found ' . count( $posts ) . ' posts marked for update' );
			if ( ! empty( $posts ) ) {
				error_log( 'ADPR DEBUG: Post IDs: ' . implode( ', ', $posts ) );
				// Check meta for first post to verify
				if ( isset( $posts[0] ) ) {
					$meta_value = get_post_meta( $posts[0], '_adpr_auto_update_enabled', true );
					error_log( 'ADPR DEBUG: First post (' . $posts[0] . ') meta value: ' . $meta_value );
				}
			} else {
				error_log( 'ADPR DEBUG: WARNING - No posts found! Checking if any posts have the meta...' );
				global $wpdb;
				$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_adpr_auto_update_enabled' AND meta_value = 'yes'" );
				error_log( 'ADPR DEBUG: Direct database count of posts with meta: ' . $count );
			}
			error_log( '========================================' );
		}

		return $posts;
	}

	/**
	 * Update a post's date.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True on success, false on failure.
	 */
	private function update_post_date( $post_id ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '----------------------------------------' );
			error_log( 'ADPR DEBUG: update_post_date() called for post ID ' . $post_id );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ADPR ERROR: Post ID ' . $post_id . ' not found in database' );
			}
			return false;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ADPR DEBUG: Post found - Title: "' . $post->post_title . '", Status: ' . $post->post_status );
		}

		$settings = $this->get_settings();
		$now      = current_time( 'mysql' );
		$now_gmt  = current_time( 'mysql', true );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ADPR DEBUG: Settings: ' . print_r( $settings, true ) );
			error_log( 'ADPR DEBUG: Current local time: ' . $now );
			error_log( 'ADPR DEBUG: Current GMT time: ' . $now_gmt );
		}

		$update_data = array( 'ID' => $post_id );

		$update_pub_date = ! empty( $settings['update_publication_date'] );
		$update_mod_date = ! empty( $settings['update_modified_date'] );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ADPR DEBUG: Update publication date setting: ' . ( $update_pub_date ? 'YES' : 'NO' ) );
			error_log( 'ADPR DEBUG: Update modified date setting: ' . ( $update_mod_date ? 'YES' : 'NO' ) );
		}

		// CRITICAL FIX: If neither is enabled, default to publication date
		if ( ! $update_pub_date && ! $update_mod_date ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ADPR WARNING: Neither update_publication_date nor update_modified_date is enabled!' );
				error_log( 'ADPR WARNING: Defaulting to update publication date to prevent no-op update' );
			}
			$update_pub_date = true;
		}

		if ( $update_pub_date ) {
			$update_data['post_date']     = $now;
			$update_data['post_date_gmt'] = $now_gmt;
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ADPR DEBUG: Will update publication date from ' . $post->post_date . ' to ' . $now );
			}
		}

		if ( $update_mod_date ) {
			$update_data['post_modified']     = $now;
			$update_data['post_modified_gmt'] = $now_gmt;
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ADPR DEBUG: Will update modified date from ' . $post->post_modified . ' to ' . $now );
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ADPR DEBUG: Final update_data array: ' . print_r( $update_data, true ) );
			error_log( 'ADPR DEBUG: Calling wp_update_post()...' );
		}

		$result = wp_update_post( $update_data, true );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ADPR DEBUG: wp_update_post() returned: ' . print_r( $result, true ) );
			error_log( 'ADPR DEBUG: is_wp_error: ' . ( is_wp_error( $result ) ? 'YES' : 'NO' ) );
			error_log( 'ADPR DEBUG: result !== 0: ' . ( $result !== 0 ? 'YES' : 'NO' ) );
		}

		if ( ! is_wp_error( $result ) && $result !== 0 ) {
			// Verify the update actually happened
			$updated_post = get_post( $post_id );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ADPR DEBUG: Post after update - Date: ' . $updated_post->post_date . ', Modified: ' . $updated_post->post_modified );
			}

			update_post_meta( $post_id, '_adpr_last_auto_update', $now );
			$count = (int) get_post_meta( $post_id, '_adpr_update_count', true );
			update_post_meta( $post_id, '_adpr_update_count', $count + 1 );

			$this->log_update( $post_id, $post->post_date, $now );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ADPR SUCCESS: Post ID ' . $post_id . ' updated successfully' );
				error_log( 'ADPR DEBUG: Update count incremented to: ' . ( $count + 1 ) );
				error_log( '----------------------------------------' );
			}

			return true;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( is_wp_error( $result ) ) {
				error_log( 'ADPR ERROR: wp_update_post failed for post ID ' . $post_id );
				error_log( 'ADPR ERROR: Error message: ' . $result->get_error_message() );
				error_log( 'ADPR ERROR: Error data: ' . print_r( $result->get_error_data(), true ) );
			} else {
				error_log( 'ADPR ERROR: wp_update_post returned 0 or false for post ID ' . $post_id );
				error_log( 'ADPR ERROR: This usually means no update was needed or permission denied' );
			}
			error_log( '----------------------------------------' );
		}

		return false;
	}

	/**
	 * Log an update.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $old_date Old date.
	 * @param string $new_date New date.
	 */
	private function log_update( $post_id, $old_date, $new_date ) {
		$log = get_option( 'adpr_update_log', array() );

		$log[] = array(
			'post_id'  => $post_id,
			'title'    => get_the_title( $post_id ),
			'old_date' => $old_date,
			'new_date' => $new_date,
			'time'     => current_time( 'mysql' ),
		);

		if ( count( $log ) > 100 ) {
			$log = array_slice( $log, -100 );
		}

		update_option( 'adpr_update_log', $log );
	}

	/**
	 * Get plugin settings.
	 *
	 * @return array Settings array.
	 */
	private function get_settings() {
		return get_option( 'adpr_settings', array(
			'enabled'                 => false,
			'update_time'             => '03:00',
			'post_types'              => array( 'post' ),
			'update_publication_date' => true,
			'update_modified_date'    => false,
			'batch_size'              => 50,
			'last_run'                => '',
			'total_updates'           => 0,
		) );
	}
}
