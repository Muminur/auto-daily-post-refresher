<?php
/**
 * REST API for Auto Daily Post Refresher
 *
 * Provides REST API endpoints for external integrations and programmatic access.
 * All endpoints require authentication and proper permissions.
 *
 * @package    Auto_Daily_Post_Refresher
 * @subpackage Auto_Daily_Post_Refresher/lib
 * @since      1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API endpoints for Auto Daily Post Refresher.
 *
 * @since 1.0.0
 */
class ADPR_REST_API {

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private $namespace = 'adpr/v1';

	/**
	 * Instance of AutoDailyPostRefresher
	 *
	 * @var AutoDailyPostRefresher
	 */
	private $adpr;

	/**
	 * Initialize REST API.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->adpr = new AutoDailyPostRefresher();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		// Get all posts with auto-update status
		register_rest_route( $this->namespace, '/posts', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_posts' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'status'   => array(
					'type'    => 'string',
					'enum'    => array( 'all', 'enabled', 'disabled' ),
					'default' => 'all',
				),
				'post_type' => array(
					'type'    => 'string',
					'default' => 'post',
				),
				'per_page' => array(
					'type'    => 'integer',
					'default' => 20,
					'minimum' => 1,
					'maximum' => 100,
				),
				'page' => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
			),
		) );

		// Get specific post details
		register_rest_route( $this->namespace, '/posts/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_post' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'id' => array(
					'type'     => 'integer',
					'required' => true,
				),
			),
		) );

		// Enable auto-update for post
		register_rest_route( $this->namespace, '/posts/(?P<id>\d+)/enable', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'enable_post' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'id' => array(
					'type'     => 'integer',
					'required' => true,
				),
			),
		) );

		// Disable auto-update for post
		register_rest_route( $this->namespace, '/posts/(?P<id>\d+)/disable', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'disable_post' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'id' => array(
					'type'     => 'integer',
					'required' => true,
				),
			),
		) );

		// Get plugin settings
		register_rest_route( $this->namespace, '/settings', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_settings' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Update plugin settings
		register_rest_route( $this->namespace, '/settings', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'update_settings' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'enabled' => array(
					'type' => 'boolean',
				),
				'update_time' => array(
					'type'    => 'string',
					'pattern' => '^\d{2}:\d{2}$',
				),
				'update_types' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'batch_size' => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 1000,
				),
				'update_publication_date' => array(
					'type' => 'boolean',
				),
				'update_modified_date' => array(
					'type' => 'boolean',
				),
			),
		) );

		// Get update logs
		register_rest_route( $this->namespace, '/logs', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_logs' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'per_page' => array(
					'type'    => 'integer',
					'default' => 20,
					'minimum' => 1,
					'maximum' => 100,
				),
				'page' => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'post_id' => array(
					'type' => 'integer',
				),
			),
		) );

		// Clear logs
		register_rest_route( $this->namespace, '/logs', array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => array( $this, 'clear_logs' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Manual trigger
		register_rest_route( $this->namespace, '/trigger', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'trigger_update' ),
			'permission_callback' => array( $this, 'check_permission' ),
			'args'                => array(
				'dry_run' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'batch_size' => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 1000,
				),
			),
		) );

		// Get status
		register_rest_route( $this->namespace, '/status', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_status' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
	}

	/**
	 * Check if user has permission to access API.
	 *
	 * @since 1.0.0
	 * @return bool True if user has permission, false otherwise.
	 */
	public function check_permission() {
		return current_user_can( 'publish_posts' );
	}

	/**
	 * Get posts with auto-update status.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_posts( $request ) {
		$status = $request->get_param( 'status' );
		$post_type = $request->get_param( 'post_type' );
		$per_page = $request->get_param( 'per_page' );
		$page = $request->get_param( 'page' );

		// Build query args
		$query_args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
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
		$posts_data = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$posts_data[] = $this->prepare_post_data( get_post() );
			}
		}

		wp_reset_postdata();

		// Prepare response with pagination headers
		$response = new WP_REST_Response( $posts_data, 200 );
		$response->header( 'X-WP-Total', $query->found_posts );
		$response->header( 'X-WP-TotalPages', $query->max_num_pages );

		return $response;
	}

	/**
	 * Get specific post details.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_post( $request ) {
		$post_id = $request->get_param( 'id' );
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'bulk-daily-datetime' ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response( $this->prepare_post_data( $post ), 200 );
	}

	/**
	 * Enable auto-update for post.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function enable_post( $request ) {
		$post_id = $request->get_param( 'id' );
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'bulk-daily-datetime' ),
				array( 'status' => 404 )
			);
		}

		if ( $post->post_status !== 'publish' ) {
			return new WP_Error(
				'post_not_published',
				__( 'Only published posts can be enabled for auto-update.', 'bulk-daily-datetime' ),
				array( 'status' => 400 )
			);
		}

		update_post_meta( $post_id, '_adpr_auto_update_enabled', 'yes' );

		return new WP_REST_Response(
			array(
				'message' => __( 'Auto-update enabled for post.', 'bulk-daily-datetime' ),
				'post'    => $this->prepare_post_data( $post ),
			),
			200
		);
	}

	/**
	 * Disable auto-update for post.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function disable_post( $request ) {
		$post_id = $request->get_param( 'id' );
		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'bulk-daily-datetime' ),
				array( 'status' => 404 )
			);
		}

		update_post_meta( $post_id, '_adpr_auto_update_enabled', 'no' );

		return new WP_REST_Response(
			array(
				'message' => __( 'Auto-update disabled for post.', 'bulk-daily-datetime' ),
				'post'    => $this->prepare_post_data( $post ),
			),
			200
		);
	}

	/**
	 * Get plugin settings.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_settings( $request ) {
		$settings = $this->adpr->get_settings();

		// Add additional status information
		$settings['cron_scheduled'] = (bool) wp_next_scheduled( 'adpr_daily_update' );
		$settings['next_run'] = wp_next_scheduled( 'adpr_daily_update' );

		if ( $settings['next_run'] ) {
			$settings['next_run_formatted'] = date( 'Y-m-d H:i:s', $settings['next_run'] );
		}

		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Update plugin settings.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function update_settings( $request ) {
		$settings = $this->adpr->get_settings();
		$updated = false;

		// Update only provided fields
		$fields = array(
			'enabled',
			'update_time',
			'update_types',
			'batch_size',
			'update_publication_date',
			'update_modified_date',
		);

		foreach ( $fields as $field ) {
			if ( $request->has_param( $field ) ) {
				$settings[ $field ] = $request->get_param( $field );
				$updated = true;
			}
		}

		if ( $updated ) {
			update_option( 'adpr_settings', $settings );

			// Reschedule cron if time changed
			if ( $request->has_param( 'update_time' ) || $request->has_param( 'enabled' ) ) {
				wp_clear_scheduled_hook( 'adpr_daily_update' );
				$this->adpr->schedule_cron();
			}

			return new WP_REST_Response(
				array(
					'message'  => __( 'Settings updated successfully.', 'bulk-daily-datetime' ),
					'settings' => $settings,
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'message'  => __( 'No settings were updated.', 'bulk-daily-datetime' ),
				'settings' => $settings,
			),
			200
		);
	}

	/**
	 * Get update logs.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_logs( $request ) {
		$logs = get_option( 'adpr_update_log', array() );
		$per_page = $request->get_param( 'per_page' );
		$page = $request->get_param( 'page' );
		$post_id_filter = $request->get_param( 'post_id' );

		// Filter by post ID if specified
		if ( $post_id_filter ) {
			$logs = array_filter( $logs, function( $log ) use ( $post_id_filter ) {
				return $log['post_id'] == $post_id_filter;
			} );
		}

		$total = count( $logs );
		$total_pages = ceil( $total / $per_page );

		// Paginate
		$offset = ( $page - 1 ) * $per_page;
		$logs = array_slice( $logs, $offset, $per_page );

		$response = new WP_REST_Response( $logs, 200 );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}

	/**
	 * Clear all logs.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function clear_logs( $request ) {
		$logs = get_option( 'adpr_update_log', array() );
		$count = count( $logs );

		update_option( 'adpr_update_log', array() );

		return new WP_REST_Response(
			array(
				'message' => sprintf(
					/* translators: %d: Number of log entries cleared */
					__( 'Cleared %d log entries.', 'bulk-daily-datetime' ),
					$count
				),
				'cleared' => $count,
			),
			200
		);
	}

	/**
	 * Trigger manual update.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function trigger_update( $request ) {
		$dry_run = $request->get_param( 'dry_run' );
		$batch_size = $request->get_param( 'batch_size' );

		// Get posts to update
		$posts = $this->adpr->get_posts_for_update( $batch_size );

		if ( empty( $posts ) ) {
			return new WP_REST_Response(
				array(
					'message'     => __( 'No posts found for updating.', 'bulk-daily-datetime' ),
					'posts_found' => 0,
					'updated'     => 0,
					'failed'      => 0,
				),
				200
			);
		}

		if ( $dry_run ) {
			$dry_run_data = array();

			foreach ( $posts as $post_id ) {
				$post = get_post( $post_id );
				$dry_run_data[] = array(
					'post_id'  => $post_id,
					'title'    => $post->post_title,
					'old_date' => $post->post_date,
					'new_date' => current_time( 'mysql' ),
				);
			}

			return new WP_REST_Response(
				array(
					'message'     => __( 'Dry run completed.', 'bulk-daily-datetime' ),
					'posts_found' => count( $posts ),
					'dry_run'     => true,
					'posts'       => $dry_run_data,
				),
				200
			);
		}

		// Perform actual updates
		$success = 0;
		$failed = 0;
		$updated_posts = array();

		foreach ( $posts as $post_id ) {
			$result = $this->adpr->update_post_date( $post_id );

			if ( $result ) {
				$post = get_post( $post_id );
				$updated_posts[] = array(
					'post_id' => $post_id,
					'title'   => $post->post_title,
					'date'    => $post->post_date,
				);
				$success++;
			} else {
				$failed++;
			}
		}

		return new WP_REST_Response(
			array(
				'message'     => sprintf(
					/* translators: 1: Number of successful updates, 2: Number of failed updates */
					__( 'Manual update completed. Success: %1$d, Failed: %2$d', 'bulk-daily-datetime' ),
					$success,
					$failed
				),
				'posts_found' => count( $posts ),
				'updated'     => $success,
				'failed'      => $failed,
				'posts'       => $updated_posts,
			),
			200
		);
	}

	/**
	 * Get plugin status.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_status( $request ) {
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

		$status = array(
			'plugin'      => array(
				'version'       => '1.0.0',
				'enabled'       => $settings['enabled'],
				'last_run'      => $settings['last_run'],
				'total_updates' => $settings['total_updates'],
			),
			'cron'        => array(
				'scheduled'         => (bool) $cron_scheduled,
				'next_run'          => $cron_scheduled ? date( 'Y-m-d H:i:s', $cron_scheduled ) : null,
				'next_run_timestamp' => $cron_scheduled,
			),
			'posts'       => array(
				'total_enabled' => $total_enabled,
				'post_types'    => $settings['update_types'],
			),
			'logs'        => array(
				'total_entries' => count( $logs ),
				'latest'        => ! empty( $logs ) ? $logs[0] : null,
			),
			'settings'    => array(
				'update_time'             => $settings['update_time'],
				'batch_size'              => $settings['batch_size'],
				'update_publication_date' => $settings['update_publication_date'],
				'update_modified_date'    => $settings['update_modified_date'],
			),
			'environment' => array(
				'wordpress_version' => get_bloginfo( 'version' ),
				'php_version'       => PHP_VERSION,
				'timezone'          => wp_timezone_string(),
			),
		);

		return new WP_REST_Response( $status, 200 );
	}

	/**
	 * Prepare post data for API response.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post object.
	 * @return array Prepared post data.
	 */
	private function prepare_post_data( $post ) {
		$auto_update = get_post_meta( $post->ID, '_adpr_auto_update_enabled', true );
		$last_update = get_post_meta( $post->ID, '_adpr_last_auto_update', true );
		$update_count = get_post_meta( $post->ID, '_adpr_update_count', true );

		return array(
			'id'                => $post->ID,
			'title'             => $post->post_title,
			'type'              => $post->post_type,
			'status'            => $post->post_status,
			'date'              => $post->post_date,
			'date_gmt'          => $post->post_date_gmt,
			'modified'          => $post->post_modified,
			'modified_gmt'      => $post->post_modified_gmt,
			'auto_update'       => array(
				'enabled'      => $auto_update === 'yes',
				'last_update'  => $last_update ? $last_update : null,
				'update_count' => $update_count ? absint( $update_count ) : 0,
			),
			'link'              => get_permalink( $post->ID ),
		);
	}
}

// Initialize REST API
new ADPR_REST_API();
