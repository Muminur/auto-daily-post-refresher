<?php
/**
 * WP-CLI Commands for Auto Daily Post Refresher
 *
 * Provides comprehensive command-line interface for managing the Auto Daily Post Refresher plugin.
 * Requires WP-CLI to be installed on the server.
 *
 * @package    Auto_Daily_Post_Refresher
 * @subpackage Auto_Daily_Post_Refresher/lib
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only load if WP-CLI is available
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Manage Auto Daily Post Refresher plugin via WP-CLI.
 *
 * @since 1.0.0
 */
class ADPR_CLI {

	/**
	 * Instance of AutoDailyPostRefresher
	 *
	 * @var AutoDailyPostRefresher
	 */
	private $adpr;

	/**
	 * Initialize CLI commands.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->adpr = new AutoDailyPostRefresher();
	}

	/**
	 * Display plugin status and system information.
	 *
	 * ## EXAMPLES
	 *
	 *     wp adpr status
	 *     wp adpr status --format=json
	 *
	 * @when after_wp_load
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function status( $args, $assoc_args ) {
		$settings = $this->adpr->get_settings();
		$cron_scheduled = wp_next_scheduled( 'adpr_daily_update' );

		// Get post statistics
		$enabled_posts = new WP_Query( array(
			'post_type'      => $settings['update_types'],
			'post_status'    => 'publish',
			'meta_key'       => '_adpr_auto_update_enabled',
			'meta_value'     => 'yes',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		) );

		$total_enabled = $enabled_posts->found_posts;
		wp_reset_postdata();

		// Get log statistics
		$logs = get_option( 'adpr_update_log', array() );
		$log_count = count( $logs );

		// Build status array
		$status = array(
			'Plugin Status'          => $settings['enabled'] ? 'Enabled' : 'Disabled',
			'Cron Status'            => $cron_scheduled ? 'Scheduled' : 'Not Scheduled',
			'Next Run'               => $cron_scheduled ? date( 'Y-m-d H:i:s', $cron_scheduled ) : 'N/A',
			'Update Time'            => $settings['update_time'],
			'Posts Marked'           => $total_enabled,
			'Post Types'             => implode( ', ', $settings['update_types'] ),
			'Batch Size'             => $settings['batch_size'],
			'Last Run'               => $settings['last_run'] ? $settings['last_run'] : 'Never',
			'Total Updates'          => $settings['total_updates'],
			'Log Entries'            => $log_count,
			'Update Publication Date' => $settings['update_publication_date'] ? 'Yes' : 'No',
			'Update Modified Date'   => $settings['update_modified_date'] ? 'Yes' : 'No',
		);

		// Output format
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		if ( $format === 'json' ) {
			WP_CLI::line( json_encode( $status, JSON_PRETTY_PRINT ) );
		} else {
			WP_CLI::line( WP_CLI::colorize( '%G=== Auto Daily Post Refresher Status ===%n' ) );
			WP_CLI::line( '' );

			foreach ( $status as $key => $value ) {
				WP_CLI::line( sprintf( '%-25s : %s', $key, $value ) );
			}
		}

		WP_CLI::success( 'Status retrieved successfully.' );
	}

	/**
	 * List posts with auto-update status.
	 *
	 * ## OPTIONS
	 *
	 * [--post_type=<type>]
	 * : Filter by post type (default: all enabled types).
	 *
	 * [--status=<status>]
	 * : Filter by auto-update status (enabled/disabled/all).
	 * ---
	 * default: all
	 * options:
	 *   - enabled
	 *   - disabled
	 *   - all
	 * ---
	 *
	 * [--limit=<number>]
	 * : Number of posts to show (default: 20).
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp adpr list
	 *     wp adpr list --status=enabled --format=csv
	 *     wp adpr list --post_type=post --limit=50
	 *     wp adpr list --format=count
	 *
	 * @when after_wp_load
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function list( $args, $assoc_args ) {
		$settings = $this->adpr->get_settings();

		$post_type = isset( $assoc_args['post_type'] ) ? $assoc_args['post_type'] : $settings['update_types'];
		$status = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'all';
		$limit = isset( $assoc_args['limit'] ) ? absint( $assoc_args['limit'] ) : 20;
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		// Build query args
		$query_args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Add meta query based on status
		if ( $status === 'enabled' ) {
			$query_args['meta_key'] = '_adpr_auto_update_enabled';
			$query_args['meta_value'] = 'yes';
		} elseif ( $status === 'disabled' ) {
			$query_args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_adpr_auto_update_enabled',
					'value'   => 'no',
					'compare' => '=',
				),
				array(
					'key'     => '_adpr_auto_update_enabled',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		$query = new WP_Query( $query_args );

		if ( $format === 'count' ) {
			WP_CLI::line( $query->found_posts );
			return;
		}

		$posts_data = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				$auto_update = get_post_meta( $post_id, '_adpr_auto_update_enabled', true );
				$last_update = get_post_meta( $post_id, '_adpr_last_auto_update', true );
				$update_count = get_post_meta( $post_id, '_adpr_update_count', true );

				$posts_data[] = array(
					'ID'           => $post_id,
					'Title'        => get_the_title(),
					'Type'         => get_post_type(),
					'Date'         => get_the_date( 'Y-m-d H:i:s' ),
					'Auto-Update'  => $auto_update === 'yes' ? 'Enabled' : 'Disabled',
					'Last Update'  => $last_update ? $last_update : 'Never',
					'Update Count' => $update_count ? $update_count : '0',
				);
			}
		}

		wp_reset_postdata();

		if ( empty( $posts_data ) ) {
			WP_CLI::warning( 'No posts found matching criteria.' );
			return;
		}

		WP_CLI\Utils\format_items( $format, $posts_data, array( 'ID', 'Title', 'Type', 'Date', 'Auto-Update', 'Last Update', 'Update Count' ) );
		WP_CLI::success( sprintf( 'Found %d posts.', count( $posts_data ) ) );
	}

	/**
	 * Enable auto-update for posts.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more post IDs to enable auto-update for.
	 *
	 * [--all]
	 * : Enable auto-update for all published posts.
	 *
	 * [--post_type=<type>]
	 * : Only with --all, filter by post type.
	 *
	 * ## EXAMPLES
	 *
	 *     wp adpr enable 123
	 *     wp adpr enable 123 456 789
	 *     wp adpr enable --all
	 *     wp adpr enable --all --post_type=post
	 *
	 * @when after_wp_load
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function enable( $args, $assoc_args ) {
		$settings = $this->adpr->get_settings();
		$post_ids = array();

		if ( isset( $assoc_args['all'] ) ) {
			// Enable for all posts
			$post_type = isset( $assoc_args['post_type'] ) ? $assoc_args['post_type'] : $settings['update_types'];

			$query = new WP_Query( array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			) );

			$post_ids = $query->posts;
			wp_reset_postdata();

			if ( empty( $post_ids ) ) {
				WP_CLI::error( 'No posts found to enable.' );
			}
		} else {
			// Enable specific posts
			$post_ids = array_map( 'absint', $args );

			if ( empty( $post_ids ) ) {
				WP_CLI::error( 'Please provide at least one post ID or use --all.' );
			}
		}

		$success = 0;
		$failed = 0;

		$progress = \WP_CLI\Utils\make_progress_bar( 'Enabling auto-update', count( $post_ids ) );

		foreach ( $post_ids as $post_id ) {
			if ( get_post_status( $post_id ) === 'publish' ) {
				update_post_meta( $post_id, '_adpr_auto_update_enabled', 'yes' );
				$success++;
			} else {
				$failed++;
			}
			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( sprintf( 'Enabled auto-update for %d posts. Failed: %d', $success, $failed ) );
	}

	/**
	 * Disable auto-update for posts.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more post IDs to disable auto-update for.
	 *
	 * [--all]
	 * : Disable auto-update for all posts.
	 *
	 * [--post_type=<type>]
	 * : Only with --all, filter by post type.
	 *
	 * ## EXAMPLES
	 *
	 *     wp adpr disable 123
	 *     wp adpr disable 123 456 789
	 *     wp adpr disable --all
	 *     wp adpr disable --all --post_type=post
	 *
	 * @when after_wp_load
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function disable( $args, $assoc_args ) {
		$settings = $this->adpr->get_settings();
		$post_ids = array();

		if ( isset( $assoc_args['all'] ) ) {
			// Disable for all posts with auto-update enabled
			$post_type = isset( $assoc_args['post_type'] ) ? $assoc_args['post_type'] : $settings['update_types'];

			$query = new WP_Query( array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'meta_key'       => '_adpr_auto_update_enabled',
				'meta_value'     => 'yes',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			) );

			$post_ids = $query->posts;
			wp_reset_postdata();

			if ( empty( $post_ids ) ) {
				WP_CLI::warning( 'No posts found with auto-update enabled.' );
				return;
			}
		} else {
			// Disable specific posts
			$post_ids = array_map( 'absint', $args );

			if ( empty( $post_ids ) ) {
				WP_CLI::error( 'Please provide at least one post ID or use --all.' );
			}
		}

		$success = 0;

		$progress = \WP_CLI\Utils\make_progress_bar( 'Disabling auto-update', count( $post_ids ) );

		foreach ( $post_ids as $post_id ) {
			if ( get_post( $post_id ) ) {
				update_post_meta( $post_id, '_adpr_auto_update_enabled', 'no' );
				$success++;
			}
			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( sprintf( 'Disabled auto-update for %d posts.', $success ) );
	}

	/**
	 * Manually trigger the auto-update process.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would be updated without actually updating.
	 *
	 * [--batch-size=<number>]
	 * : Override default batch size.
	 *
	 * ## EXAMPLES
	 *
	 *     wp adpr trigger
	 *     wp adpr trigger --dry-run
	 *     wp adpr trigger --batch-size=100
	 *
	 * @when after_wp_load
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function trigger( $args, $assoc_args ) {
		$dry_run = isset( $assoc_args['dry-run'] );
		$batch_size = isset( $assoc_args['batch-size'] ) ? absint( $assoc_args['batch-size'] ) : null;

		WP_CLI::line( WP_CLI::colorize( '%Y=== Starting Manual Update ===%n' ) );
		WP_CLI::line( '' );

		// Get posts to update
		$posts = $this->adpr->get_posts_for_update( $batch_size );

		if ( empty( $posts ) ) {
			WP_CLI::warning( 'No posts found for updating.' );
			return;
		}

		WP_CLI::line( sprintf( 'Found %d posts to update.', count( $posts ) ) );
		WP_CLI::line( '' );

		if ( $dry_run ) {
			WP_CLI::line( WP_CLI::colorize( '%B[DRY RUN] No actual updates will be performed.%n' ) );
			WP_CLI::line( '' );

			foreach ( $posts as $post_id ) {
				$post = get_post( $post_id );
				$old_date = $post->post_date;
				$new_date = current_time( 'mysql' );

				WP_CLI::line( sprintf(
					'Post ID %d: %s -> %s',
					$post_id,
					WP_CLI::colorize( "%R{$old_date}%n" ),
					WP_CLI::colorize( "%G{$new_date}%n" )
				) );
			}

			WP_CLI::success( sprintf( 'Dry run complete. %d posts would be updated.', count( $posts ) ) );
			return;
		}

		// Perform actual updates
		$progress = \WP_CLI\Utils\make_progress_bar( 'Updating posts', count( $posts ) );

		$success = 0;
		$failed = 0;

		foreach ( $posts as $post_id ) {
			$result = $this->adpr->update_post_date( $post_id );

			if ( $result ) {
				$success++;
			} else {
				$failed++;
			}

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::line( '' );
		WP_CLI::line( WP_CLI::colorize( '%G=== Update Summary ===%n' ) );
		WP_CLI::line( sprintf( 'Successful: %s', WP_CLI::colorize( "%G{$success}%n" ) ) );
		WP_CLI::line( sprintf( 'Failed: %s', $failed > 0 ? WP_CLI::colorize( "%R{$failed}%n" ) : '0' ) );

		if ( $success > 0 ) {
			WP_CLI::success( 'Manual update completed successfully.' );
		} else {
			WP_CLI::error( 'Manual update failed.' );
		}
	}

	/**
	 * Display update logs.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<number>]
	 * : Number of log entries to show (default: 20).
	 *
	 * [--post_id=<id>]
	 * : Filter logs by post ID.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp adpr logs
	 *     wp adpr logs --limit=50
	 *     wp adpr logs --post_id=123
	 *     wp adpr logs --format=csv
	 *
	 * @when after_wp_load
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function logs( $args, $assoc_args ) {
		$logs = get_option( 'adpr_update_log', array() );

		if ( empty( $logs ) ) {
			WP_CLI::warning( 'No log entries found.' );
			return;
		}

		$limit = isset( $assoc_args['limit'] ) ? absint( $assoc_args['limit'] ) : 20;
		$post_id_filter = isset( $assoc_args['post_id'] ) ? absint( $assoc_args['post_id'] ) : null;
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		// Filter by post ID if specified
		if ( $post_id_filter ) {
			$logs = array_filter( $logs, function( $log ) use ( $post_id_filter ) {
				return $log['post_id'] == $post_id_filter;
			} );
		}

		// Limit results
		$logs = array_slice( $logs, 0, $limit );

		if ( $format === 'count' ) {
			WP_CLI::line( count( $logs ) );
			return;
		}

		// Format logs for display
		$log_data = array();
		foreach ( $logs as $log ) {
			$log_data[] = array(
				'Date'      => $log['date'],
				'Post ID'   => $log['post_id'],
				'Title'     => $log['post_title'],
				'Old Date'  => $log['old_date'],
				'New Date'  => $log['new_date'],
			);
		}

		WP_CLI\Utils\format_items( $format, $log_data, array( 'Date', 'Post ID', 'Title', 'Old Date', 'New Date' ) );
		WP_CLI::success( sprintf( 'Displayed %d log entries.', count( $log_data ) ) );
	}

	/**
	 * Clear all update logs.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp adpr clear-logs
	 *     wp adpr clear-logs --yes
	 *
	 * @when after_wp_load
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function clear_logs( $args, $assoc_args ) {
		$logs = get_option( 'adpr_update_log', array() );
		$count = count( $logs );

		if ( $count === 0 ) {
			WP_CLI::warning( 'No log entries to clear.' );
			return;
		}

		if ( ! isset( $assoc_args['yes'] ) ) {
			WP_CLI::confirm( sprintf( 'Are you sure you want to clear %d log entries?', $count ) );
		}

		update_option( 'adpr_update_log', array() );

		WP_CLI::success( sprintf( 'Cleared %d log entries.', $count ) );
	}

	/**
	 * Export plugin data.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<path>]
	 * : Output file path (default: adpr-export-{timestamp}.json).
	 *
	 * [--format=<format>]
	 * : Export format.
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp adpr export
	 *     wp adpr export --file=backup.json
	 *     wp adpr export --format=csv --file=posts.csv
	 *
	 * @when after_wp_load
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function export( $args, $assoc_args ) {
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'json';
		$file = isset( $assoc_args['file'] ) ? $assoc_args['file'] : sprintf( 'adpr-export-%s.%s', date( 'Y-m-d-His' ), $format );

		// Get all data
		$settings = $this->adpr->get_settings();
		$logs = get_option( 'adpr_update_log', array() );

		// Get enabled posts
		$query = new WP_Query( array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'meta_key'       => '_adpr_auto_update_enabled',
			'meta_value'     => 'yes',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		) );

		$enabled_posts = $query->posts;
		wp_reset_postdata();

		$export_data = array(
			'version'       => '1.0.0',
			'export_date'   => current_time( 'mysql' ),
			'settings'      => $settings,
			'enabled_posts' => $enabled_posts,
			'logs'          => $logs,
		);

		if ( $format === 'json' ) {
			$content = json_encode( $export_data, JSON_PRETTY_PRINT );
		} else {
			// CSV format - export enabled posts only
			$content = "Post ID,Title,Type,Date,Last Update,Update Count\n";
			foreach ( $enabled_posts as $post_id ) {
				$post = get_post( $post_id );
				if ( $post ) {
					$last_update = get_post_meta( $post_id, '_adpr_last_auto_update', true );
					$update_count = get_post_meta( $post_id, '_adpr_update_count', true );

					$content .= sprintf(
						"%d,\"%s\",%s,%s,%s,%d\n",
						$post_id,
						str_replace( '"', '""', $post->post_title ),
						$post->post_type,
						$post->post_date,
						$last_update ? $last_update : 'Never',
						$update_count ? $update_count : 0
					);
				}
			}
		}

		// Write to file
		$result = file_put_contents( $file, $content );

		if ( $result === false ) {
			WP_CLI::error( sprintf( 'Failed to write to file: %s', $file ) );
		}

		WP_CLI::success( sprintf( 'Exported data to: %s (%s)', $file, size_format( $result ) ) );
	}

	/**
	 * Import plugin data from file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to import file.
	 *
	 * [--merge]
	 * : Merge with existing data instead of replacing.
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp adpr import backup.json
	 *     wp adpr import backup.json --merge
	 *     wp adpr import backup.json --yes
	 *
	 * @when after_wp_load
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function import( $args, $assoc_args ) {
		$file = $args[0];

		if ( ! file_exists( $file ) ) {
			WP_CLI::error( sprintf( 'File not found: %s', $file ) );
		}

		$content = file_get_contents( $file );
		$data = json_decode( $content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			WP_CLI::error( sprintf( 'Invalid JSON: %s', json_last_error_msg() ) );
		}

		if ( ! isset( $data['version'] ) || ! isset( $data['enabled_posts'] ) ) {
			WP_CLI::error( 'Invalid import file format.' );
		}

		if ( ! isset( $assoc_args['yes'] ) ) {
			WP_CLI::confirm( sprintf( 'Import data for %d posts?', count( $data['enabled_posts'] ) ) );
		}

		$merge = isset( $assoc_args['merge'] );

		// Import settings
		if ( isset( $data['settings'] ) && ! $merge ) {
			update_option( 'adpr_settings', $data['settings'] );
			WP_CLI::line( 'Settings imported.' );
		}

		// Import enabled posts
		$progress = \WP_CLI\Utils\make_progress_bar( 'Importing posts', count( $data['enabled_posts'] ) );

		$success = 0;
		foreach ( $data['enabled_posts'] as $post_id ) {
			if ( get_post( $post_id ) ) {
				update_post_meta( $post_id, '_adpr_auto_update_enabled', 'yes' );
				$success++;
			}
			$progress->tick();
		}

		$progress->finish();

		// Import logs
		if ( isset( $data['logs'] ) ) {
			if ( $merge ) {
				$existing_logs = get_option( 'adpr_update_log', array() );
				$merged_logs = array_merge( $existing_logs, $data['logs'] );
				update_option( 'adpr_update_log', array_slice( $merged_logs, 0, 100 ) );
			} else {
				update_option( 'adpr_update_log', $data['logs'] );
			}
			WP_CLI::line( sprintf( 'Imported %d log entries.', count( $data['logs'] ) ) );
		}

		WP_CLI::success( sprintf( 'Import complete. Enabled auto-update for %d posts.', $success ) );
	}

	/**
	 * Run system diagnostics.
	 *
	 * ## EXAMPLES
	 *
	 *     wp adpr diagnostics
	 *
	 * @when after_wp_load
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function diagnostics( $args, $assoc_args ) {
		WP_CLI::line( WP_CLI::colorize( '%G=== Auto Daily Post Refresher Diagnostics ===%n' ) );
		WP_CLI::line( '' );

		$issues = array();
		$warnings = array();

		// Check plugin status
		$settings = $this->adpr->get_settings();
		WP_CLI::line( sprintf( '%-30s : %s', 'Plugin Enabled', $settings['enabled'] ? WP_CLI::colorize( '%GYes%n' ) : WP_CLI::colorize( '%RNo%n' ) ) );

		// Check cron
		$cron_scheduled = wp_next_scheduled( 'adpr_daily_update' );
		if ( $cron_scheduled ) {
			WP_CLI::line( sprintf( '%-30s : %s', 'Cron Scheduled', WP_CLI::colorize( '%GYes%n' ) ) );
			WP_CLI::line( sprintf( '%-30s : %s', 'Next Run', date( 'Y-m-d H:i:s', $cron_scheduled ) ) );
		} else {
			WP_CLI::line( sprintf( '%-30s : %s', 'Cron Scheduled', WP_CLI::colorize( '%RNo%n' ) ) );
			$issues[] = 'Cron is not scheduled. Run: wp cron event schedule adpr_daily_update';
		}

		// Check WP_CRON
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			WP_CLI::line( sprintf( '%-30s : %s', 'WP_CRON', WP_CLI::colorize( '%YDisabled%n' ) ) );
			$warnings[] = 'WP_CRON is disabled. Make sure system cron is configured.';
		} else {
			WP_CLI::line( sprintf( '%-30s : %s', 'WP_CRON', WP_CLI::colorize( '%GEnabled%n' ) ) );
		}

		// Check post types
		WP_CLI::line( sprintf( '%-30s : %s', 'Enabled Post Types', implode( ', ', $settings['update_types'] ) ) );

		// Check for enabled posts
		$query = new WP_Query( array(
			'post_type'      => $settings['update_types'],
			'post_status'    => 'publish',
			'meta_key'       => '_adpr_auto_update_enabled',
			'meta_value'     => 'yes',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		) );

		$enabled_count = $query->found_posts;
		wp_reset_postdata();

		WP_CLI::line( sprintf( '%-30s : %d', 'Posts Marked for Update', $enabled_count ) );

		if ( $enabled_count === 0 ) {
			$warnings[] = 'No posts are marked for auto-update.';
		}

		// Check PHP version
		$php_version = PHP_VERSION;
		$min_php = '7.4';
		$php_ok = version_compare( $php_version, $min_php, '>=' );
		WP_CLI::line( sprintf( '%-30s : %s', 'PHP Version', $php_ok ? WP_CLI::colorize( "%G{$php_version}%n" ) : WP_CLI::colorize( "%R{$php_version}%n" ) ) );

		if ( ! $php_ok ) {
			$issues[] = sprintf( 'PHP version %s or higher required.', $min_php );
		}

		// Check WordPress version
		$wp_version = get_bloginfo( 'version' );
		$min_wp = '5.0';
		$wp_ok = version_compare( $wp_version, $min_wp, '>=' );
		WP_CLI::line( sprintf( '%-30s : %s', 'WordPress Version', $wp_ok ? WP_CLI::colorize( "%G{$wp_version}%n" ) : WP_CLI::colorize( "%R{$wp_version}%n" ) ) );

		if ( ! $wp_ok ) {
			$issues[] = sprintf( 'WordPress version %s or higher required.', $min_wp );
		}

		WP_CLI::line( '' );

		// Display issues
		if ( ! empty( $issues ) ) {
			WP_CLI::line( WP_CLI::colorize( '%R=== Issues Found ===%n' ) );
			foreach ( $issues as $issue ) {
				WP_CLI::line( WP_CLI::colorize( "%R✗%n {$issue}" ) );
			}
			WP_CLI::line( '' );
		}

		// Display warnings
		if ( ! empty( $warnings ) ) {
			WP_CLI::line( WP_CLI::colorize( '%Y=== Warnings ===%n' ) );
			foreach ( $warnings as $warning ) {
				WP_CLI::line( WP_CLI::colorize( "%Y⚠%n {$warning}" ) );
			}
			WP_CLI::line( '' );
		}

		if ( empty( $issues ) && empty( $warnings ) ) {
			WP_CLI::success( 'All systems operational.' );
		} elseif ( empty( $issues ) ) {
			WP_CLI::warning( 'System operational with warnings.' );
		} else {
			WP_CLI::error( 'Issues detected. Please resolve them before using the plugin.' );
		}
	}

	/**
	 * Repair and reset plugin data.
	 *
	 * ## OPTIONS
	 *
	 * [--reset-settings]
	 * : Reset all settings to defaults.
	 *
	 * [--reschedule-cron]
	 * : Reschedule the cron job.
	 *
	 * [--clear-meta]
	 * : Clear all post meta data.
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp adpr repair --reschedule-cron
	 *     wp adpr repair --reset-settings --yes
	 *     wp adpr repair --clear-meta --yes
	 *
	 * @when after_wp_load
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function repair( $args, $assoc_args ) {
		$actions = array();

		if ( isset( $assoc_args['reset-settings'] ) ) {
			$actions[] = 'Reset settings to defaults';
		}

		if ( isset( $assoc_args['reschedule-cron'] ) ) {
			$actions[] = 'Reschedule cron job';
		}

		if ( isset( $assoc_args['clear-meta'] ) ) {
			$actions[] = 'Clear all post meta data';
		}

		if ( empty( $actions ) ) {
			WP_CLI::error( 'Please specify at least one repair action (--reset-settings, --reschedule-cron, or --clear-meta).' );
		}

		if ( ! isset( $assoc_args['yes'] ) ) {
			WP_CLI::line( 'The following actions will be performed:' );
			foreach ( $actions as $action ) {
				WP_CLI::line( '  - ' . $action );
			}
			WP_CLI::line( '' );
			WP_CLI::confirm( 'Continue?' );
		}

		// Reset settings
		if ( isset( $assoc_args['reset-settings'] ) ) {
			delete_option( 'adpr_settings' );
			WP_CLI::line( WP_CLI::colorize( '%G✓%n Settings reset to defaults.' ) );
		}

		// Reschedule cron
		if ( isset( $assoc_args['reschedule-cron'] ) ) {
			wp_clear_scheduled_hook( 'adpr_daily_update' );
			$this->adpr->schedule_cron();
			WP_CLI::line( WP_CLI::colorize( '%G✓%n Cron job rescheduled.' ) );
		}

		// Clear meta
		if ( isset( $assoc_args['clear-meta'] ) ) {
			global $wpdb;

			$deleted = $wpdb->query(
				"DELETE FROM {$wpdb->postmeta}
				WHERE meta_key IN ('_adpr_auto_update_enabled', '_adpr_last_auto_update', '_adpr_update_count')"
			);

			WP_CLI::line( WP_CLI::colorize( sprintf( '%%G✓%%n Cleared meta data from %d posts.', $deleted ) ) );
		}

		WP_CLI::success( 'Repair operations completed.' );
	}
}

// Register WP-CLI commands
WP_CLI::add_command( 'adpr', 'ADPR_CLI' );
