<?php
/**
 * Admin interface class
 *
 * @package Auto Daily Post Refresher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AutoDailyPostRefresherAdmin class
 *
 * Handles all admin interface functionality including menus, settings, and AJAX operations.
 */
class AutoDailyPostRefresherAdmin {

	/**
	 * Core plugin instance.
	 *
	 * @var AutoDailyPostRefresher
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @param AutoDailyPostRefresher|null $core Core instance to use (optional).
	 */
	public function __construct( $core = null ) {
		$this->core = $core instanceof AutoDailyPostRefresher ? $core : new AutoDailyPostRefresher();

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_adpr_toggle_post', array( $this, 'ajax_toggle_post' ) );
		add_action( 'wp_ajax_adpr_bulk_action', array( $this, 'ajax_bulk_action' ) );
		add_action( 'wp_ajax_adpr_manual_trigger', array( $this, 'ajax_manual_trigger' ) );
		add_action( 'wp_ajax_adpr_export_logs', array( $this, 'ajax_export_logs' ) );
		add_action( 'wp_ajax_adpr_clear_logs', array( $this, 'ajax_clear_logs' ) );
		add_action( 'wp_ajax_adpr_undo_action', array( $this, 'ajax_undo_action' ) );
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_admin_menu() {
		// Use edit_posts so Editors and above can access
		$capability = 'edit_posts';

		// Main menu.
		add_menu_page(
			__( 'Auto Post Refresher', 'auto-daily-post-refresher' ),
			__( 'Post Refresher', 'auto-daily-post-refresher' ),
			$capability,
			'adpr-selector',
			array( $this, 'render_selector_page' ),
			'dashicons-update',
			26
		);

		// Post Selector submenu (same as main).
		add_submenu_page(
			'adpr-selector',
			__( 'Post Selector', 'auto-daily-post-refresher' ),
			__( 'Post Selector', 'auto-daily-post-refresher' ),
			$capability,
			'adpr-selector',
			array( $this, 'render_selector_page' )
		);

		// Settings submenu.
		add_submenu_page(
			'adpr-selector',
			__( 'Settings', 'auto-daily-post-refresher' ),
			__( 'Settings', 'auto-daily-post-refresher' ),
			$capability,
			'adpr-settings',
			array( $this, 'render_settings_page' )
		);

		// Update Logs submenu.
		add_submenu_page(
			'adpr-selector',
			__( 'Update Logs', 'auto-daily-post-refresher' ),
			__( 'Update Logs', 'auto-daily-post-refresher' ),
			$capability,
			'adpr-logs',
			array( $this, 'render_logs_page' )
		);

		// Manual Trigger submenu.
		add_submenu_page(
			'adpr-selector',
			__( 'Manual Trigger', 'auto-daily-post-refresher' ),
			__( 'Manual Trigger', 'auto-daily-post-refresher' ),
			$capability,
			'adpr-manual',
			array( $this, 'render_manual_page' )
		);

		// Help & Support submenu.
		add_submenu_page(
			'adpr-selector',
			__( 'Help & Support', 'auto-daily-post-refresher' ),
			__( 'Help & Support', 'auto-daily-post-refresher' ),
			$capability,
			'adpr-help',
			array( $this, 'render_help_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'adpr-' ) === false && strpos( $hook, 'post-refresher' ) === false ) {
			return;
		}

		$css_url = ADPR_PLUGIN_URL . 'css/adpr-admin.css';
		$js_url  = ADPR_PLUGIN_URL . 'js/adpr-admin.js';

		wp_enqueue_style(
			'adpr-admin',
			$css_url,
			array(),
			ADPR_VERSION
		);

		wp_enqueue_script(
			'adpr-admin',
			$js_url,
			array( 'jquery' ),
			ADPR_VERSION,
			true
		);

		$localize_data = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'adpr_admin_nonce' ),
			'strings' => array(
				'confirm_clear_logs' => __( 'Are you sure you want to clear all logs? This cannot be undone.', 'auto-daily-post-refresher' ),
				'confirm_manual'     => __( 'Are you sure you want to trigger a manual update now?', 'auto-daily-post-refresher' ),
				'confirm_undo'       => __( 'Undo the last bulk action?', 'auto-daily-post-refresher' ),
				'processing'         => __( 'Processing...', 'auto-daily-post-refresher' ),
				'success'            => __( 'Success!', 'auto-daily-post-refresher' ),
				'error'              => __( 'Error occurred. Please try again.', 'auto-daily-post-refresher' ),
				'shortcuts_title'    => __( 'Keyboard Shortcuts', 'auto-daily-post-refresher' ),
				'shortcuts_help'     => __( 'Press ? to view keyboard shortcuts', 'auto-daily-post-refresher' ),
			),
		);

		wp_localize_script(
			'adpr-admin',
			'adprAdmin',
			$localize_data
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting( 'adpr_settings_group', 'adpr_settings', array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Raw settings input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['enabled']                 = ! empty( $input['enabled'] );
		$sanitized['update_time']             = sanitize_text_field( $input['update_time'] ?? '03:00' );
		$sanitized['post_types']              = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? array_map( 'sanitize_text_field', $input['post_types'] ) : array( 'post' );
		$sanitized['update_publication_date'] = ! empty( $input['update_publication_date'] );
		$sanitized['update_modified_date']    = ! empty( $input['update_modified_date'] );
		$sanitized['batch_size']              = absint( $input['batch_size'] ?? 50 );

		// Preserve runtime data.
		$current                       = get_option( 'adpr_settings', array() );
		$sanitized['last_run']         = $current['last_run'] ?? '';
		$sanitized['total_updates']    = $current['total_updates'] ?? 0;

		return $sanitized;
	}

	/**
	 * Render Post Selector page.
	 */
	public function render_selector_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'auto-daily-post-refresher' ) );
		}

		// Add contextual help.
		$this->add_contextual_help( 'selector' );

		if ( ! class_exists( 'ADPR_Posts_List_Table' ) ) {
			require_once ADPR_PLUGIN_DIR . 'lib/class-adpr-posts-list-table.php';
		}

		$list_table = new ADPR_Posts_List_Table();
		$list_table->prepare_items();

		// Get activity summary.
		$settings       = get_option( 'adpr_settings', array() );
		$marked_count   = $this->get_marked_posts_count();
		$logs           = get_option( 'adpr_update_log', array() );
		$recent_updates = array_slice( array_reverse( $logs ), 0, 5 );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Select Posts for Auto-Update', 'auto-daily-post-refresher' ); ?></h1>

			<div class="adpr-activity-summary">
				<div class="adpr-stats">
					<div class="adpr-stat-box">
						<span class="adpr-stat-value"><?php echo esc_html( number_format_i18n( $marked_count ) ); ?></span>
						<span class="adpr-stat-label"><?php esc_html_e( 'Posts Marked', 'auto-daily-post-refresher' ); ?></span>
					</div>
					<div class="adpr-stat-box">
						<span class="adpr-stat-value"><?php echo esc_html( number_format_i18n( $settings['total_updates'] ?? 0 ) ); ?></span>
						<span class="adpr-stat-label"><?php esc_html_e( 'Total Updates', 'auto-daily-post-refresher' ); ?></span>
					</div>
					<div class="adpr-stat-box">
						<span class="adpr-stat-value"><?php echo esc_html( count( $logs ) ); ?></span>
						<span class="adpr-stat-label"><?php esc_html_e( 'Log Entries', 'auto-daily-post-refresher' ); ?></span>
					</div>
				</div>
			</div>

			<div class="adpr-info-box">
				<p>
					<strong><?php esc_html_e( 'How it works:', 'auto-daily-post-refresher' ); ?></strong>
					<?php esc_html_e( 'Select posts below to automatically update their publication dates daily. The cron job will update selected posts at the scheduled time.', 'auto-daily-post-refresher' ); ?>
				</p>
			</div>

			<form method="post">
				<?php
				$list_table->search_box( __( 'Search Posts', 'auto-daily-post-refresher' ), 'adpr-search' );
				$list_table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'auto-daily-post-refresher' ) );
		}

		// Add contextual help.
		$this->add_contextual_help( 'settings' );

		$settings = get_option( 'adpr_settings', array() );
		$defaults = array(
			'enabled'                 => false,
			'update_time'             => '03:00',
			'post_types'              => array( 'post' ),
			'update_publication_date' => true,
			'update_modified_date'    => false,
			'batch_size'              => 50,
		);
		$settings = wp_parse_args( $settings, $defaults );

		// Handle form submission.
		if ( isset( $_POST['adpr_save_settings'] ) && check_admin_referer( 'adpr_settings_nonce' ) ) {
			$updated_settings = array(
				'enabled'                 => ! empty( $_POST['adpr_enabled'] ),
				'update_time'             => sanitize_text_field( $_POST['adpr_update_time'] ),
				'post_types'              => isset( $_POST['adpr_post_types'] ) && is_array( $_POST['adpr_post_types'] ) ? array_map( 'sanitize_text_field', $_POST['adpr_post_types'] ) : array(),
				'update_publication_date' => ! empty( $_POST['adpr_update_publication_date'] ),
				'update_modified_date'    => ! empty( $_POST['adpr_update_modified_date'] ),
				'batch_size'              => absint( $_POST['adpr_batch_size'] ),
				'email_enabled'           => ! empty( $_POST['adpr_email_enabled'] ),
				'email_on_success'        => ! empty( $_POST['adpr_email_on_success'] ),
				'email_on_error'          => ! empty( $_POST['adpr_email_on_error'] ),
				'email_address'           => sanitize_email( $_POST['adpr_email_address'] ?? get_option( 'admin_email' ) ),
				'last_run'                => $settings['last_run'] ?? '',
				'total_updates'           => $settings['total_updates'] ?? 0,
			);

			update_option( 'adpr_settings', $updated_settings );
			$settings = $updated_settings;

			// Reschedule cron if time changed.
			if ( method_exists( $this->core, 'unschedule_cron' ) && method_exists( $this->core, 'schedule_cron' ) ) {
				$this->core->unschedule_cron();
				$this->core->schedule_cron();
			}

			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully!', 'auto-daily-post-refresher' ) . '</p></div>';
		}

		$post_types     = get_post_types( array( 'public' => true ), 'objects' );
		$next_scheduled = wp_next_scheduled( 'adpr_daily_update' );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Auto Post Refresher Settings', 'auto-daily-post-refresher' ); ?></h1>

			<form method="post" action="">
				<?php wp_nonce_field( 'adpr_settings_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="adpr_enabled">
								<?php esc_html_e( 'Enable Auto-Updates', 'auto-daily-post-refresher' ); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="adpr_enabled" name="adpr_enabled" value="1" <?php checked( $settings['enabled'], true ); ?>>
								<?php esc_html_e( 'Enable automatic daily updates', 'auto-daily-post-refresher' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, selected posts will be automatically updated daily at the scheduled time.', 'auto-daily-post-refresher' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="adpr_update_time">
								<?php esc_html_e( 'Update Time', 'auto-daily-post-refresher' ); ?>
							</label>
						</th>
						<td>
							<input type="time" id="adpr_update_time" name="adpr_update_time" value="<?php echo esc_attr( $settings['update_time'] ); ?>" class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Time when automatic updates should run (server time).', 'auto-daily-post-refresher' ); ?>
								<?php if ( $next_scheduled ) : ?>
									<br><strong><?php esc_html_e( 'Next scheduled run:', 'auto-daily-post-refresher' ); ?></strong>
									<?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_scheduled ), 'Y-m-d H:i:s' ) ); ?>
								<?php endif; ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<?php esc_html_e( 'Post Types', 'auto-daily-post-refresher' ); ?>
						</th>
						<td>
							<fieldset>
								<?php foreach ( $post_types as $post_type ) : ?>
									<label>
										<input type="checkbox" name="adpr_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $settings['post_types'], true ) ); ?>>
										<?php echo esc_html( $post_type->label ); ?>
									</label><br>
								<?php endforeach; ?>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Select which post types can be auto-updated.', 'auto-daily-post-refresher' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<?php esc_html_e( 'Update Options', 'auto-daily-post-refresher' ); ?>
						</th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="adpr_update_publication_date" value="1" <?php checked( $settings['update_publication_date'], true ); ?>>
									<?php esc_html_e( 'Update publication date', 'auto-daily-post-refresher' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="adpr_update_modified_date" value="1" <?php checked( $settings['update_modified_date'], true ); ?>>
									<?php esc_html_e( 'Update modified date', 'auto-daily-post-refresher' ); ?>
								</label>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Choose which dates to update.', 'auto-daily-post-refresher' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="adpr_batch_size">
								<?php esc_html_e( 'Batch Size', 'auto-daily-post-refresher' ); ?>
							</label>
						</th>
						<td>
							<input type="number" id="adpr_batch_size" name="adpr_batch_size" value="<?php echo esc_attr( $settings['batch_size'] ); ?>" min="1" max="1000" class="small-text">
							<p class="description">
								<?php esc_html_e( 'Number of posts to process per batch. Lower numbers reduce server load but take longer.', 'auto-daily-post-refresher' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<hr>

				<h2><?php esc_html_e( 'Email Notifications (Optional)', 'auto-daily-post-refresher' ); ?></h2>
				<p><?php esc_html_e( 'Receive email notifications about update events.', 'auto-daily-post-refresher' ); ?></p>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Enable Notifications', 'auto-daily-post-refresher' ); ?>
						</th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="adpr_email_enabled" value="1" <?php checked( ! empty( $settings['email_enabled'] ) ); ?>>
									<?php esc_html_e( 'Send email notifications', 'auto-daily-post-refresher' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="adpr_email_on_success" value="1" <?php checked( ! empty( $settings['email_on_success'] ) ); ?>>
									<?php esc_html_e( 'On successful update', 'auto-daily-post-refresher' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="adpr_email_on_error" value="1" <?php checked( ! empty( $settings['email_on_error'] ) ); ?>>
									<?php esc_html_e( 'On errors', 'auto-daily-post-refresher' ); ?>
								</label>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Choose when to receive email notifications.', 'auto-daily-post-refresher' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="adpr_email_address">
								<?php esc_html_e( 'Email Address', 'auto-daily-post-refresher' ); ?>
							</label>
						</th>
						<td>
							<input type="email" id="adpr_email_address" name="adpr_email_address" value="<?php echo esc_attr( $settings['email_address'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Where to send notifications. Defaults to site admin email.', 'auto-daily-post-refresher' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Settings', 'auto-daily-post-refresher' ), 'primary', 'adpr_save_settings' ); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Status Information', 'auto-daily-post-refresher' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'System Status:', 'auto-daily-post-refresher' ); ?></th>
					<td>
						<?php if ( $settings['enabled'] ) : ?>
							<span class="adpr-status-badge adpr-status-enabled"><?php esc_html_e( 'Enabled', 'auto-daily-post-refresher' ); ?></span>
						<?php else : ?>
							<span class="adpr-status-badge adpr-status-disabled"><?php esc_html_e( 'Disabled', 'auto-daily-post-refresher' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Cron Status:', 'auto-daily-post-refresher' ); ?></th>
					<td>
						<?php if ( method_exists( $this->core, 'is_cron_scheduled' ) && $this->core->is_cron_scheduled() ) : ?>
							<span class="adpr-status-badge adpr-status-enabled"><?php esc_html_e( 'Scheduled', 'auto-daily-post-refresher' ); ?></span>
						<?php else : ?>
							<span class="adpr-status-badge adpr-status-disabled"><?php esc_html_e( 'Not Scheduled', 'auto-daily-post-refresher' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Last Run:', 'auto-daily-post-refresher' ); ?></th>
					<td>
						<?php
						if ( ! empty( $settings['last_run'] ) ) {
							echo esc_html( $settings['last_run'] );
						} else {
							esc_html_e( 'Never', 'auto-daily-post-refresher' );
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Total Updates:', 'auto-daily-post-refresher' ); ?></th>
					<td><?php echo esc_html( number_format_i18n( $settings['total_updates'] ?? 0 ) ); ?></td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Render Update Logs page.
	 */
	public function render_logs_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'auto-daily-post-refresher' ) );
		}

		// Add contextual help.
		$this->add_contextual_help( 'logs' );

		$logs       = get_option( 'adpr_update_log', array() );
		$logs       = array_reverse( $logs );
		$per_page   = 20;
		$page       = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$total      = count( $logs );
		$total_pages = ceil( $total / $per_page );
		$offset     = ( $page - 1 ) * $per_page;
		$logs_paged = array_slice( $logs, $offset, $per_page );

		// Handle search.
		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		if ( ! empty( $search ) ) {
			$logs_paged = array_filter(
				$logs,
				function( $log ) use ( $search ) {
					return stripos( $log['title'], $search ) !== false || stripos( $log['post_id'], $search ) !== false;
				}
			);
			$logs_paged = array_slice( $logs_paged, 0, $per_page );
			$total      = count( $logs_paged );
			$total_pages = ceil( $total / $per_page );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Update Logs', 'auto-daily-post-refresher' ); ?></h1>

			<div class="adpr-logs-toolbar">
				<form method="get" class="adpr-search-form">
					<input type="hidden" name="page" value="adpr-logs">
					<input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search logs...', 'auto-daily-post-refresher' ); ?>">
					<button type="submit" class="button"><?php esc_html_e( 'Search', 'auto-daily-post-refresher' ); ?></button>
				</form>

				<div class="adpr-logs-actions">
					<button type="button" id="adpr-export-logs" class="button">
						<?php esc_html_e( 'Export to CSV', 'auto-daily-post-refresher' ); ?>
					</button>
					<button type="button" id="adpr-clear-logs" class="button button-danger">
						<?php esc_html_e( 'Clear All Logs', 'auto-daily-post-refresher' ); ?>
					</button>
				</div>
			</div>

			<?php if ( empty( $logs_paged ) ) : ?>
				<p><?php esc_html_e( 'No logs found.', 'auto-daily-post-refresher' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'auto-daily-post-refresher' ); ?></th>
							<th><?php esc_html_e( 'Post ID', 'auto-daily-post-refresher' ); ?></th>
							<th><?php esc_html_e( 'Title', 'auto-daily-post-refresher' ); ?></th>
							<th><?php esc_html_e( 'Old Date', 'auto-daily-post-refresher' ); ?></th>
							<th><?php esc_html_e( 'New Date', 'auto-daily-post-refresher' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $logs_paged as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log['time'] ); ?></td>
								<td><a href="<?php echo esc_url( get_edit_post_link( $log['post_id'] ) ); ?>"><?php echo esc_html( $log['post_id'] ); ?></a></td>
								<td><?php echo esc_html( $log['title'] ); ?></td>
								<td><?php echo esc_html( $log['old_date'] ); ?></td>
								<td><?php echo esc_html( $log['new_date'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<?php
							echo paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'prev_text' => __( '&laquo;', 'auto-daily-post-refresher' ),
									'next_text' => __( '&raquo;', 'auto-daily-post-refresher' ),
									'total'     => $total_pages,
									'current'   => $page,
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render Manual Trigger page.
	 */
	public function render_manual_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'auto-daily-post-refresher' ) );
		}

		// Add contextual help.
		$this->add_contextual_help( 'manual' );

		$settings = get_option( 'adpr_settings', array() );

		// Get diagnostic information
		$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post' );
		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_adpr_auto_update_enabled',
						'value'   => 'yes',
						'compare' => '=',
					),
				),
			)
		);
		$next_scheduled = wp_next_scheduled( 'adpr_daily_update' );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Manual Trigger', 'auto-daily-post-refresher' ); ?></h1>

			<!-- LIVE DIAGNOSTICS SECTION -->
			<div class="adpr-diagnostics" style="background:#fff3cd;border:2px solid #856404;padding:20px;margin:20px 0;border-radius:5px;">
				<h2 style="margin-top:0;color:#856404;">🔍 <?php esc_html_e( 'Live Diagnostics', 'auto-daily-post-refresher' ); ?></h2>

				<!-- Settings Status -->
				<div style="background:#fff;padding:15px;margin:10px 0;border-radius:5px;">
					<h3 style="margin-top:0;"><?php esc_html_e( 'Settings Status:', 'auto-daily-post-refresher' ); ?></h3>
					<ul style="margin:0;font-family:monospace;font-size:14px;">
						<li><strong><?php esc_html_e( 'Plugin Enabled:', 'auto-daily-post-refresher' ); ?></strong>
							<?php echo ! empty( $settings['enabled'] ) ? '<span style="color:green;">✅ YES</span>' : '<span style="color:red;">❌ NO</span>'; ?>
						</li>
						<li><strong><?php esc_html_e( 'Update Publication Date:', 'auto-daily-post-refresher' ); ?></strong>
							<?php echo ! empty( $settings['update_publication_date'] ) ? '<span style="color:green;">✅ YES</span>' : '<span style="color:red;">❌ NO</span>'; ?>
						</li>
						<li><strong><?php esc_html_e( 'Update Modified Date:', 'auto-daily-post-refresher' ); ?></strong>
							<?php echo ! empty( $settings['update_modified_date'] ) ? '<span style="color:green;">✅ YES</span>' : '<span style="color:red;">❌ NO</span>'; ?>
						</li>
						<li><strong><?php esc_html_e( 'Batch Size:', 'auto-daily-post-refresher' ); ?></strong>
							<?php echo (int) ( isset( $settings['batch_size'] ) ? $settings['batch_size'] : 50 ); ?>
						</li>
						<li><strong><?php esc_html_e( 'Post Types:', 'auto-daily-post-refresher' ); ?></strong>
							<?php echo esc_html( implode( ', ', $post_types ) ); ?>
						</li>
					</ul>
				</div>

				<!-- Posts Status -->
				<div style="background:#fff;padding:15px;margin:10px 0;border-radius:5px;">
					<h3 style="margin-top:0;"><?php esc_html_e( 'Posts Marked for Update:', 'auto-daily-post-refresher' ); ?></h3>
					<p style="font-size:24px;margin:10px 0;"><strong><?php echo count( $posts ); ?></strong></p>
					<?php if ( count( $posts ) > 0 ) : ?>
						<p style="font-family:monospace;font-size:12px;">
							<strong><?php esc_html_e( 'Post IDs:', 'auto-daily-post-refresher' ); ?></strong>
							<?php
							echo esc_html( implode( ', ', array_slice( $posts, 0, 10 ) ) );
							if ( count( $posts ) > 10 ) {
								echo ' ... ' . esc_html__( 'and', 'auto-daily-post-refresher' ) . ' ' . ( count( $posts ) - 10 ) . ' ' . esc_html__( 'more', 'auto-daily-post-refresher' );
							}
							?>
						</p>
					<?php else : ?>
						<p style="color:red;font-weight:bold;">
							⚠️ <?php esc_html_e( 'NO POSTS MARKED! Go to Post Selector and toggle some posts ON.', 'auto-daily-post-refresher' ); ?>
						</p>
					<?php endif; ?>
				</div>

				<!-- Cron Status -->
				<div style="background:#fff;padding:15px;margin:10px 0;border-radius:5px;">
					<h3 style="margin-top:0;"><?php esc_html_e( 'Cron Status:', 'auto-daily-post-refresher' ); ?></h3>
					<ul style="margin:0;font-family:monospace;font-size:14px;">
						<li><strong><?php esc_html_e( 'Cron Scheduled:', 'auto-daily-post-refresher' ); ?></strong>
							<?php echo $next_scheduled ? '<span style="color:green;">✅ YES</span>' : '<span style="color:red;">❌ NO</span>'; ?>
						</li>
						<?php if ( $next_scheduled ) : ?>
							<li><strong><?php esc_html_e( 'Next Run:', 'auto-daily-post-refresher' ); ?></strong>
								<?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_scheduled ), 'Y-m-d H:i:s' ) ); ?>
							</li>
						<?php endif; ?>
						<li><strong><?php esc_html_e( 'Cron Hook Name:', 'auto-daily-post-refresher' ); ?></strong>
							<code>adpr_daily_update</code>
						</li>
					</ul>
				</div>

				<!-- Activity Log -->
				<div style="background:#fff;padding:15px;margin:10px 0;border-radius:5px;">
					<h3 style="margin-top:0;"><?php esc_html_e( 'Activity Log:', 'auto-daily-post-refresher' ); ?></h3>
					<div id="adpr-activity-log" style="background:#f8f9fa;border:1px solid #dee2e6;padding:10px;min-height:100px;max-height:400px;overflow-y:auto;font-family:monospace;font-size:12px;line-height:1.6;">
						<div class="log-entry" style="padding:5px 0;border-bottom:1px solid #e9ecef;">
							[<?php echo esc_html( current_time( 'H:i:s' ) ); ?>] <?php esc_html_e( 'Page loaded. Click "Trigger Manual Update" to see activity.', 'auto-daily-post-refresher' ); ?>
						</div>
					</div>
				</div>

				<!-- Debug Info -->
				<div style="background:#fff;padding:15px;margin:10px 0;border-radius:5px;">
					<h3 style="margin-top:0;"><?php esc_html_e( 'Debug Information:', 'auto-daily-post-refresher' ); ?></h3>
					<p style="font-family:monospace;font-size:12px;">
						<strong><?php esc_html_e( 'JavaScript Status:', 'auto-daily-post-refresher' ); ?></strong> <?php esc_html_e( 'Check browser console (F12) for JavaScript activity', 'auto-daily-post-refresher' ); ?><br>
						<strong><?php esc_html_e( 'AJAX URL:', 'auto-daily-post-refresher' ); ?></strong> <?php echo esc_html( admin_url( 'admin-ajax.php' ) ); ?><br>
						<strong><?php esc_html_e( 'WP_DEBUG:', 'auto-daily-post-refresher' ); ?></strong> <?php echo defined( 'WP_DEBUG' ) && WP_DEBUG ? '<span style="color:green;">Enabled</span>' : '<span style="color:orange;">Disabled</span>'; ?><br>
						<strong><?php esc_html_e( 'Debug Log Location:', 'auto-daily-post-refresher' ); ?></strong> <code>wp-content/debug.log</code>
					</p>
				</div>
			</div>

			<div class="adpr-info-box">
				<p>
					<strong><?php esc_html_e( 'Warning:', 'auto-daily-post-refresher' ); ?></strong>
					<?php esc_html_e( 'This will immediately update all posts marked for auto-update. Use with caution.', 'auto-daily-post-refresher' ); ?>
				</p>
			</div>

			<div class="adpr-manual-trigger-section">
				<h2><?php esc_html_e( 'Trigger Update', 'auto-daily-post-refresher' ); ?></h2>

				<p><?php esc_html_e( 'Click the button below to manually trigger an update of all selected posts.', 'auto-daily-post-refresher' ); ?></p>

				<div class="adpr-trigger-options">
					<label>
						<input type="checkbox" id="adpr-dry-run" value="1">
						<?php esc_html_e( 'Dry Run (preview only, do not update)', 'auto-daily-post-refresher' ); ?>
					</label>
				</div>

				<button type="button" id="adpr-manual-trigger-btn" class="button button-primary button-large">
					<?php esc_html_e( 'Trigger Manual Update', 'auto-daily-post-refresher' ); ?>
				</button>

				<div id="adpr-manual-progress" class="adpr-progress-container" style="display:none;">
					<h3><?php esc_html_e( 'Processing...', 'auto-daily-post-refresher' ); ?></h3>
					<div class="adpr-progress-bar">
						<div class="adpr-progress-fill" style="width:0%"></div>
					</div>
					<p class="adpr-progress-text">0%</p>
					<button type="button" id="adpr-cancel-trigger" class="button">
						<?php esc_html_e( 'Cancel', 'auto-daily-post-refresher' ); ?>
					</button>
				</div>

				<div id="adpr-manual-result" class="adpr-result-container" style="display:none;">
					<h3><?php esc_html_e( 'Results', 'auto-daily-post-refresher' ); ?></h3>
					<div id="adpr-result-content"></div>
				</div>
			</div>

			<hr>

			<div class="adpr-status-section">
				<h2><?php esc_html_e( 'Current Status', 'auto-daily-post-refresher' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'System Status:', 'auto-daily-post-refresher' ); ?></th>
						<td>
							<?php if ( ! empty( $settings['enabled'] ) ) : ?>
								<span class="adpr-status-badge adpr-status-enabled"><?php esc_html_e( 'Enabled', 'auto-daily-post-refresher' ); ?></span>
							<?php else : ?>
								<span class="adpr-status-badge adpr-status-disabled"><?php esc_html_e( 'Disabled', 'auto-daily-post-refresher' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Posts Marked for Update:', 'auto-daily-post-refresher' ); ?></th>
						<td id="adpr-posts-count"><?php echo esc_html( $this->get_marked_posts_count() ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Last Manual Run:', 'auto-daily-post-refresher' ); ?></th>
						<td id="adpr-last-manual"><?php echo esc_html( get_option( 'adpr_last_manual_run', __( 'Never', 'auto-daily-post-refresher' ) ) ); ?></td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Get count of posts marked for update.
	 *
	 * @return int Count of marked posts.
	 */
	private function get_marked_posts_count() {
		$settings   = get_option( 'adpr_settings', array() );
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

		$posts = get_posts( $args );
		return count( $posts );
	}

	/**
	 * AJAX handler for toggling individual post.
	 */
	public function ajax_toggle_post() {
		check_ajax_referer( 'adpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'auto-daily-post-refresher' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$enabled = isset( $_POST['enabled'] ) && $_POST['enabled'] === 'true';

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '========================================' );
			error_log( 'ADPR DEBUG: ajax_toggle_post() called' );
			error_log( 'ADPR DEBUG: Post ID: ' . $post_id );
			error_log( 'ADPR DEBUG: Enabled value (raw): ' . ( isset( $_POST['enabled'] ) ? $_POST['enabled'] : 'NOT SET' ) );
			error_log( 'ADPR DEBUG: Enabled value (boolean): ' . ( $enabled ? 'true' : 'false' ) );
			error_log( 'ADPR DEBUG: Will set meta to: ' . ( $enabled ? 'yes' : 'no' ) );
		}

		if ( ! $post_id ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ADPR ERROR: Invalid post ID (0)' );
				error_log( '========================================' );
			}
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'auto-daily-post-refresher' ) ) );
		}

		$meta_value = $enabled ? 'yes' : 'no';
		$result = update_post_meta( $post_id, '_adpr_auto_update_enabled', $meta_value );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ADPR DEBUG: update_post_meta() returned: ' . print_r( $result, true ) );
			// Verify it was saved
			$verify = get_post_meta( $post_id, '_adpr_auto_update_enabled', true );
			error_log( 'ADPR DEBUG: Verification read from database: ' . $verify );
			error_log( 'ADPR DEBUG: Meta save successful: ' . ( $verify === $meta_value ? 'YES' : 'NO' ) );
			error_log( '========================================' );
		}

		wp_send_json_success( array( 'message' => __( 'Post updated successfully.', 'auto-daily-post-refresher' ) ) );
	}

	/**
	 * AJAX handler for bulk actions.
	 */
	public function ajax_bulk_action() {
		check_ajax_referer( 'adpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'auto-daily-post-refresher' ) ) );
		}

		$post_ids = isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ? array_map( 'absint', $_POST['post_ids'] ) : array();
		$action   = isset( $_POST['bulk_action'] ) ? sanitize_text_field( $_POST['bulk_action'] ) : '';

		if ( empty( $post_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No posts selected.', 'auto-daily-post-refresher' ) ) );
		}

		$value = ( $action === 'enable' ) ? 'yes' : 'no';

		// Store bulk action data for undo (5 minutes expiry).
		set_transient(
			'adpr_last_bulk_action',
			array(
				'action'   => $action,
				'post_ids' => $post_ids,
				'time'     => current_time( 'timestamp' ),
			),
			300
		);

		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, '_adpr_auto_update_enabled', $value );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of posts updated */
					_n( '%d post updated successfully.', '%d posts updated successfully.', count( $post_ids ), 'auto-daily-post-refresher' ),
					count( $post_ids )
				),
			)
		);
	}

	/**
	 * AJAX handler for manual trigger.
	 */
	public function ajax_manual_trigger() {
		// Initialize log messages for UI
		$log_messages = array();
		$log_messages[] = 'Starting manual trigger...';

		check_ajax_referer( 'adpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			$log_messages[] = 'ERROR: Insufficient permissions';
			wp_send_json_error(
				array(
					'message' => __( 'Insufficient permissions.', 'auto-daily-post-refresher' ),
					'log'     => $log_messages,
				)
			);
		}

		$dry_run = isset( $_POST['dry_run'] ) && $_POST['dry_run'] === 'true';
		$log_messages[] = 'Dry run mode: ' . ( $dry_run ? 'YES' : 'NO' );

		if ( ! method_exists( $this->core, 'process_daily_update' ) ) {
			$log_messages[] = 'ERROR: Core method not found';
			wp_send_json_error(
				array(
					'message' => __( 'Core method not found. Plugin may not be properly initialized.', 'auto-daily-post-refresher' ),
					'log'     => $log_messages,
				)
			);
		}

		// Get settings for diagnostic log
		$settings = get_option( 'adpr_settings', array() );
		$log_messages[] = 'Settings loaded: Plugin ' . ( ! empty( $settings['enabled'] ) ? 'ENABLED' : 'DISABLED' );
		$log_messages[] = 'Update publication date: ' . ( ! empty( $settings['update_publication_date'] ) ? 'YES' : 'NO' );
		$log_messages[] = 'Update modified date: ' . ( ! empty( $settings['update_modified_date'] ) ? 'YES' : 'NO' );

		// For dry run, just count posts
		if ( $dry_run ) {
			$count = $this->get_marked_posts_count();
			$log_messages[] = 'Dry run completed: ' . $count . ' posts would be updated';
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %d: number of posts */
						_n( 'Dry run: %d post would be updated.', 'Dry run: %d posts would be updated.', $count, 'auto-daily-post-refresher' ),
						$count
					),
					'count'   => $count,
					'log'     => $log_messages,
				)
			);
		}

		$log_messages[] = 'Calling core->process_daily_update(true)...';

		// Execute the update with force=true to bypass enabled check
		$result = $this->core->process_daily_update( true );

		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ADPR: Manual trigger result: ' . print_r( $result, true ) );
		}

		$log_messages[] = 'Result received: ' . ( $result['success'] ? 'SUCCESS' : 'FAILED' );
		$log_messages[] = 'Message: ' . $result['message'];
		$log_messages[] = 'Posts updated: ' . $result['count'];

		// Update last manual run timestamp
		if ( $result['success'] ) {
			update_option( 'adpr_last_manual_run', current_time( 'mysql' ) );
			$log_messages[] = 'Last manual run timestamp updated';
		}

		// Return the result from process_daily_update
		if ( $result['success'] ) {
			wp_send_json_success(
				array(
					'message' => $result['message'],
					'count'   => $result['count'],
					'log'     => $log_messages,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => $result['message'],
					'count'   => $result['count'],
					'log'     => $log_messages,
				)
			);
		}
	}

	/**
	 * AJAX handler for exporting logs to CSV.
	 */
	public function ajax_export_logs() {
		check_ajax_referer( 'adpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Insufficient permissions.', 'auto-daily-post-refresher' ) );
		}

		$logs = get_option( 'adpr_update_log', array() );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=adpr-logs-' . gmdate( 'Y-m-d-His' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'Date', 'Post ID', 'Title', 'Old Date', 'New Date' ) );

		foreach ( $logs as $log ) {
			fputcsv( $output, array( $log['time'], $log['post_id'], $log['title'], $log['old_date'], $log['new_date'] ) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * AJAX handler for clearing logs.
	 */
	public function ajax_clear_logs() {
		check_ajax_referer( 'adpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'auto-daily-post-refresher' ) ) );
		}

		update_option( 'adpr_update_log', array() );

		wp_send_json_success( array( 'message' => __( 'Logs cleared successfully.', 'auto-daily-post-refresher' ) ) );
	}

	/**
	 * AJAX handler for undo action.
	 */
	public function ajax_undo_action() {
		check_ajax_referer( 'adpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'auto-daily-post-refresher' ) ) );
		}

		$undo_data = get_transient( 'adpr_last_bulk_action' );

		if ( ! $undo_data ) {
			wp_send_json_error( array( 'message' => __( 'No recent action to undo.', 'auto-daily-post-refresher' ) ) );
		}

		// Reverse the action.
		$reverse_value = ( $undo_data['action'] === 'enable' ) ? 'no' : 'yes';

		foreach ( $undo_data['post_ids'] as $post_id ) {
			update_post_meta( $post_id, '_adpr_auto_update_enabled', $reverse_value );
		}

		delete_transient( 'adpr_last_bulk_action' );

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of posts */
					_n( 'Undone: %d post restored.', 'Undone: %d posts restored.', count( $undo_data['post_ids'] ), 'auto-daily-post-refresher' ),
					count( $undo_data['post_ids'] )
				),
			)
		);
	}

	/**
	 * Add dashboard widget.
	 */
	public function add_dashboard_widget() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'adpr_dashboard_widget',
			__( 'Auto Post Refresher Status', 'auto-daily-post-refresher' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget.
	 */
	public function render_dashboard_widget() {
		$settings       = get_option( 'adpr_settings', array() );
		$enabled        = ! empty( $settings['enabled'] );
		$next_scheduled = wp_next_scheduled( 'adpr_daily_update' );
		$marked_count   = $this->get_marked_posts_count();
		$logs           = get_option( 'adpr_update_log', array() );
		$last_run       = ! empty( $settings['last_run'] ) ? $settings['last_run'] : __( 'Never', 'auto-daily-post-refresher' );

		?>
		<div class="adpr-dashboard-widget">
			<div class="adpr-stats">
				<div class="adpr-stat-box">
					<span class="adpr-stat-value"><?php echo esc_html( number_format_i18n( $marked_count ) ); ?></span>
					<span class="adpr-stat-label"><?php esc_html_e( 'Posts Marked', 'auto-daily-post-refresher' ); ?></span>
				</div>
				<div class="adpr-stat-box">
					<span class="adpr-stat-value"><?php echo esc_html( number_format_i18n( $settings['total_updates'] ?? 0 ) ); ?></span>
					<span class="adpr-stat-label"><?php esc_html_e( 'Total Updates', 'auto-daily-post-refresher' ); ?></span>
				</div>
				<div class="adpr-stat-box">
					<span class="adpr-stat-value"><?php echo esc_html( count( $logs ) ); ?></span>
					<span class="adpr-stat-label"><?php esc_html_e( 'Log Entries', 'auto-daily-post-refresher' ); ?></span>
				</div>
			</div>

			<div class="adpr-widget-status">
				<p>
					<strong><?php esc_html_e( 'Status:', 'auto-daily-post-refresher' ); ?></strong>
					<?php if ( $enabled ) : ?>
						<span class="adpr-status-badge adpr-status-enabled"><?php esc_html_e( 'Enabled', 'auto-daily-post-refresher' ); ?></span>
					<?php else : ?>
						<span class="adpr-status-badge adpr-status-disabled"><?php esc_html_e( 'Disabled', 'auto-daily-post-refresher' ); ?></span>
					<?php endif; ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Last Run:', 'auto-daily-post-refresher' ); ?></strong>
					<?php echo esc_html( $last_run ); ?>
				</p>
				<?php if ( $next_scheduled ) : ?>
					<p>
						<strong><?php esc_html_e( 'Next Run:', 'auto-daily-post-refresher' ); ?></strong>
						<?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_scheduled ), 'Y-m-d H:i:s' ) ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="adpr-widget-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=adpr-selector' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Select Posts', 'auto-daily-post-refresher' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=adpr-settings' ) ); ?>" class="button">
					<?php esc_html_e( 'Settings', 'auto-daily-post-refresher' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=adpr-manual' ) ); ?>" class="button">
					<?php esc_html_e( 'Manual Trigger', 'auto-daily-post-refresher' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Show admin notices for important events.
	 */
	public function show_admin_notices() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'adpr' ) === false ) {
			return;
		}

		// Check if undo is available.
		$undo_data = get_transient( 'adpr_last_bulk_action' );
		if ( $undo_data ) {
			?>
			<div class="notice notice-info is-dismissible adpr-undo-notice">
				<p>
					<?php
					printf(
						/* translators: %d: number of posts */
						_n( 'Last action: %d post updated.', 'Last action: %d posts updated.', count( $undo_data['post_ids'] ), 'auto-daily-post-refresher' ),
						count( $undo_data['post_ids'] )
					);
					?>
					<button type="button" id="adpr-undo-btn" class="button button-small" style="margin-left: 10px;">
						<?php esc_html_e( 'Undo', 'auto-daily-post-refresher' ); ?>
					</button>
				</p>
			</div>
			<?php
		}

		// Check if cron is not scheduled but system is enabled.
		$settings = get_option( 'adpr_settings', array() );
		if ( ! empty( $settings['enabled'] ) && ! wp_next_scheduled( 'adpr_daily_update' ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Warning:', 'auto-daily-post-refresher' ); ?></strong>
					<?php esc_html_e( 'Auto updates are enabled but the cron job is not scheduled. Please visit the Settings page to reschedule.', 'auto-daily-post-refresher' ); ?>
				</p>
			</div>
			<?php
		}

		// Check if no posts are marked but system is enabled.
		if ( ! empty( $settings['enabled'] ) && $this->get_marked_posts_count() === 0 ) {
			?>
			<div class="notice notice-info is-dismissible">
				<p>
					<?php esc_html_e( 'Auto updates are enabled but no posts are marked. Please select posts on the Post Selector page.', 'auto-daily-post-refresher' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Add contextual help tabs to admin pages.
	 *
	 * @param string $page Page identifier.
	 */
	public function add_contextual_help( $page ) {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		switch ( $page ) {
			case 'selector':
				$screen->add_help_tab(
					array(
						'id'      => 'adpr_selector_overview',
						'title'   => __( 'Overview', 'auto-daily-post-refresher' ),
						'content' => '<p>' . __( 'This page allows you to select which posts should be automatically updated daily. Use the toggle switches or bulk actions to enable/disable auto-updates for posts.', 'auto-daily-post-refresher' ) . '</p>',
					)
				);
				$screen->add_help_tab(
					array(
						'id'      => 'adpr_selector_filters',
						'title'   => __( 'Filters', 'auto-daily-post-refresher' ),
						'content' => '<p>' . __( 'Use the filters above the table to narrow down posts by type, category, author, or update status. This makes it easier to manage large numbers of posts.', 'auto-daily-post-refresher' ) . '</p>',
					)
				);
				$screen->add_help_tab(
					array(
						'id'      => 'adpr_selector_bulk',
						'title'   => __( 'Bulk Actions', 'auto-daily-post-refresher' ),
						'content' => '<p>' . __( 'Select multiple posts using checkboxes, choose a bulk action from the dropdown, and click Apply. You can enable or disable auto-updates for multiple posts at once.', 'auto-daily-post-refresher' ) . '</p>',
					)
				);
				break;

			case 'settings':
				$screen->add_help_tab(
					array(
						'id'      => 'adpr_settings_overview',
						'title'   => __( 'Overview', 'auto-daily-post-refresher' ),
						'content' => '<p>' . __( 'Configure how the Auto Post Refresher plugin operates. Enable or disable automatic updates, set the daily update time, and choose which post types to include.', 'auto-daily-post-refresher' ) . '</p>',
					)
				);
				$screen->add_help_tab(
					array(
						'id'      => 'adpr_settings_time',
						'title'   => __( 'Update Time', 'auto-daily-post-refresher' ),
						'content' => '<p>' . __( 'Set the time of day when automatic updates should run. Choose a time when your site has low traffic to minimize performance impact. The time is in server timezone.', 'auto-daily-post-refresher' ) . '</p>',
					)
				);
				$screen->add_help_tab(
					array(
						'id'      => 'adpr_settings_performance',
						'title'   => __( 'Performance', 'auto-daily-post-refresher' ),
						'content' => '<p>' . __( 'The batch size determines how many posts are processed at once. Lower numbers (20-50) are safer for shared hosting, while higher numbers (100-200) work well on dedicated servers.', 'auto-daily-post-refresher' ) . '</p>',
					)
				);
				break;

			case 'logs':
				$screen->add_help_tab(
					array(
						'id'      => 'adpr_logs_overview',
						'title'   => __( 'Overview', 'auto-daily-post-refresher' ),
						'content' => '<p>' . __( 'View a history of all post updates performed by the plugin. Each log entry shows when the update happened, which post was updated, and the old and new dates.', 'auto-daily-post-refresher' ) . '</p>',
					)
				);
				$screen->add_help_tab(
					array(
						'id'      => 'adpr_logs_export',
						'title'   => __( 'Export', 'auto-daily-post-refresher' ),
						'content' => '<p>' . __( 'Click "Export to CSV" to download all logs as a CSV file. This is useful for record-keeping or importing into spreadsheet applications for analysis.', 'auto-daily-post-refresher' ) . '</p>',
					)
				);
				break;

			case 'manual':
				$screen->add_help_tab(
					array(
						'id'      => 'adpr_manual_overview',
						'title'   => __( 'Overview', 'auto-daily-post-refresher' ),
						'content' => '<p>' . __( 'Manually trigger an immediate update of all marked posts. This is useful for testing or when you need to update posts outside the scheduled time.', 'auto-daily-post-refresher' ) . '</p>',
					)
				);
				$screen->add_help_tab(
					array(
						'id'      => 'adpr_manual_dryrun',
						'title'   => __( 'Dry Run', 'auto-daily-post-refresher' ),
						'content' => '<p>' . __( 'Enable "Dry Run" to preview how many posts would be updated without actually updating them. This is a safe way to test your configuration.', 'auto-daily-post-refresher' ) . '</p>',
					)
				);
				break;
		}

		// Add help sidebar to all pages.
		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'auto-daily-post-refresher' ) . '</strong></p>' .
			'<p><a href="' . esc_url( admin_url( 'admin.php?page=adpr-help' ) ) . '">' . __( 'Help & Support', 'auto-daily-post-refresher' ) . '</a></p>' .
			'<p><a href="https://wordpress.org/support/" target="_blank">' . __( 'WordPress Support Forums', 'auto-daily-post-refresher' ) . '</a></p>'
		);
	}

	/**
	 * Render Help & Support page.
	 */
	public function render_help_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'auto-daily-post-refresher' ) );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Help & Support', 'auto-daily-post-refresher' ); ?></h1>

			<div class="adpr-help-page">
				<div class="adpr-card">
					<h2><?php esc_html_e( 'Quick Start Guide', 'auto-daily-post-refresher' ); ?></h2>
					<ol>
						<li><strong><?php esc_html_e( 'Enable the plugin:', 'auto-daily-post-refresher' ); ?></strong> <?php esc_html_e( 'Go to Settings and check "Enable Auto-Updates".', 'auto-daily-post-refresher' ); ?></li>
						<li><strong><?php esc_html_e( 'Select posts:', 'auto-daily-post-refresher' ); ?></strong> <?php esc_html_e( 'Go to Post Selector and enable auto-updates for your desired posts using toggle switches or bulk actions.', 'auto-daily-post-refresher' ); ?></li>
						<li><strong><?php esc_html_e( 'Configure time:', 'auto-daily-post-refresher' ); ?></strong> <?php esc_html_e( 'Set the daily update time in Settings (default is 3:00 AM).', 'auto-daily-post-refresher' ); ?></li>
						<li><strong><?php esc_html_e( 'Monitor:', 'auto-daily-post-refresher' ); ?></strong> <?php esc_html_e( 'Check the Update Logs page to see when posts were updated.', 'auto-daily-post-refresher' ); ?></li>
					</ol>
				</div>

				<div class="adpr-card">
					<h2><?php esc_html_e( 'Frequently Asked Questions', 'auto-daily-post-refresher' ); ?></h2>

					<h3><?php esc_html_e( 'What does this plugin do?', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'This plugin automatically updates the publication date of selected posts every day, making them appear fresh to search engines and visitors.', 'auto-daily-post-refresher' ); ?></p>

					<h3><?php esc_html_e( 'Will this affect my SEO?', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'Yes, potentially positively. Search engines often favor recently updated content. However, use this responsibly and only on evergreen content that remains relevant.', 'auto-daily-post-refresher' ); ?></p>

					<h3><?php esc_html_e( 'Can I exclude certain posts?', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'Yes! Only posts you explicitly mark on the Post Selector page will be updated. All other posts remain untouched.', 'auto-daily-post-refresher' ); ?></p>

					<h3><?php esc_html_e( 'What if WordPress cron is not working?', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'WordPress cron requires site visits to trigger. For more reliable scheduling, consider using a real server cron job that triggers wp-cron.php regularly.', 'auto-daily-post-refresher' ); ?></p>

					<h3><?php esc_html_e( 'Can I run updates manually?', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'Yes! Use the Manual Trigger page to immediately update all marked posts. You can also use the "Dry Run" option to preview without making changes.', 'auto-daily-post-refresher' ); ?></p>

					<h3><?php esc_html_e( 'How do I undo a bulk action?', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'After performing a bulk action, you have 5 minutes to click the "Undo" button that appears in the admin notice. After that, you\'ll need to manually reverse the changes.', 'auto-daily-post-refresher' ); ?></p>

					<h3><?php esc_html_e( 'Does this work with custom post types?', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'Yes! You can select which post types to include in the Settings page. All public post types are available.', 'auto-daily-post-refresher' ); ?></p>
				</div>

				<div class="adpr-card">
					<h2><?php esc_html_e( 'Troubleshooting', 'auto-daily-post-refresher' ); ?></h2>

					<h3><?php esc_html_e( 'Posts are not updating automatically', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'Check the following:', 'auto-daily-post-refresher' ); ?></p>
					<ul>
						<li><?php esc_html_e( '1. Ensure "Enable Auto-Updates" is checked in Settings', 'auto-daily-post-refresher' ); ?></li>
						<li><?php esc_html_e( '2. Verify posts are marked for auto-update on the Post Selector page', 'auto-daily-post-refresher' ); ?></li>
						<li><?php esc_html_e( '3. Check if the cron job is scheduled (shown on Settings page)', 'auto-daily-post-refresher' ); ?></li>
						<li><?php esc_html_e( '4. Ensure your site receives regular traffic (needed for WordPress cron)', 'auto-daily-post-refresher' ); ?></li>
					</ul>

					<h3><?php esc_html_e( 'Manual trigger is not working', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'Try the following:', 'auto-daily-post-refresher' ); ?></p>
					<ul>
						<li><?php esc_html_e( '1. Check if any posts are marked for auto-update', 'auto-daily-post-refresher' ); ?></li>
						<li><?php esc_html_e( '2. Look for PHP errors in your server error log', 'auto-daily-post-refresher' ); ?></li>
						<li><?php esc_html_e( '3. Try using "Dry Run" first to test', 'auto-daily-post-refresher' ); ?></li>
						<li><?php esc_html_e( '4. Increase PHP memory limit if processing many posts', 'auto-daily-post-refresher' ); ?></li>
					</ul>

					<h3><?php esc_html_e( 'Bulk actions are not saving', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'This could be due to:', 'auto-daily-post-refresher' ); ?></p>
					<ul>
						<li><?php esc_html_e( '1. Browser JavaScript errors - check browser console', 'auto-daily-post-refresher' ); ?></li>
						<li><?php esc_html_e( '2. Plugin conflict - try disabling other plugins temporarily', 'auto-daily-post-refresher' ); ?></li>
						<li><?php esc_html_e( '3. Insufficient permissions - ensure you have "publish_posts" capability', 'auto-daily-post-refresher' ); ?></li>
					</ul>
				</div>

				<div class="adpr-card">
					<h2><?php esc_html_e( 'Use Cases & Examples', 'auto-daily-post-refresher' ); ?></h2>

					<h3><?php esc_html_e( 'Example 1: Evergreen Blog Posts', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'Select your best-performing evergreen content (guides, tutorials, reference articles) to keep them appearing fresh in search results and encouraging click-throughs.', 'auto-daily-post-refresher' ); ?></p>

					<h3><?php esc_html_e( 'Example 2: Product Pages', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'Keep product pages looking current by automatically updating their publication dates. This can help maintain search rankings for competitive products.', 'auto-daily-post-refresher' ); ?></p>

					<h3><?php esc_html_e( 'Example 3: Service Offerings', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'For businesses with ongoing services, auto-updating service pages signals to search engines that your offerings are active and current.', 'auto-daily-post-refresher' ); ?></p>

					<h3><?php esc_html_e( 'Example 4: News Archives', 'auto-daily-post-refresher' ); ?></h3>
					<p><?php esc_html_e( 'While typically you wouldn\'t update news posts, you might want to refresh "roundup" or "best of" posts that aggregate news to keep them relevant.', 'auto-daily-post-refresher' ); ?></p>
				</div>

				<div class="adpr-card">
					<h2><?php esc_html_e( 'Keyboard Shortcuts', 'auto-daily-post-refresher' ); ?></h2>
					<table class="widefat">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Shortcut', 'auto-daily-post-refresher' ); ?></th>
								<th><?php esc_html_e( 'Action', 'auto-daily-post-refresher' ); ?></th>
								<th><?php esc_html_e( 'Page', 'auto-daily-post-refresher' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><code>s</code></td>
								<td><?php esc_html_e( 'Save settings', 'auto-daily-post-refresher' ); ?></td>
								<td><?php esc_html_e( 'Settings', 'auto-daily-post-refresher' ); ?></td>
							</tr>
							<tr>
								<td><code>r</code></td>
								<td><?php esc_html_e( 'Refresh/reload page', 'auto-daily-post-refresher' ); ?></td>
								<td><?php esc_html_e( 'All pages', 'auto-daily-post-refresher' ); ?></td>
							</tr>
							<tr>
								<td><code>t</code></td>
								<td><?php esc_html_e( 'Trigger manual update', 'auto-daily-post-refresher' ); ?></td>
								<td><?php esc_html_e( 'Manual Trigger', 'auto-daily-post-refresher' ); ?></td>
							</tr>
							<tr>
								<td><code>?</code></td>
								<td><?php esc_html_e( 'Show keyboard shortcuts', 'auto-daily-post-refresher' ); ?></td>
								<td><?php esc_html_e( 'All pages', 'auto-daily-post-refresher' ); ?></td>
							</tr>
							<tr>
								<td><code>a</code></td>
								<td><?php esc_html_e( 'Select all posts', 'auto-daily-post-refresher' ); ?></td>
								<td><?php esc_html_e( 'Post Selector', 'auto-daily-post-refresher' ); ?></td>
							</tr>
							<tr>
								<td><code>n</code></td>
								<td><?php esc_html_e( 'Select none', 'auto-daily-post-refresher' ); ?></td>
								<td><?php esc_html_e( 'Post Selector', 'auto-daily-post-refresher' ); ?></td>
							</tr>
							<tr>
								<td><code>u</code></td>
								<td><?php esc_html_e( 'Undo last action', 'auto-daily-post-refresher' ); ?></td>
								<td><?php esc_html_e( 'All pages', 'auto-daily-post-refresher' ); ?></td>
							</tr>
						</tbody>
					</table>
				</div>

				<div class="adpr-card">
					<h2><?php esc_html_e( 'Support', 'auto-daily-post-refresher' ); ?></h2>
					<p><?php esc_html_e( 'Need more help? Here are your support options:', 'auto-daily-post-refresher' ); ?></p>
					<ul>
						<li><a href="https://wordpress.org/support/" target="_blank"><?php esc_html_e( 'WordPress Support Forums', 'auto-daily-post-refresher' ); ?></a></li>
						<li><a href="https://wordpress.org/support/plugin/auto-daily-post-refresher/" target="_blank"><?php esc_html_e( 'Plugin Support Forum', 'auto-daily-post-refresher' ); ?></a></li>
						<li><a href="https://wordpress.org/support/plugin/auto-daily-post-refresher/reviews/" target="_blank"><?php esc_html_e( 'Leave a Review', 'auto-daily-post-refresher' ); ?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Send email notification.
	 *
	 * @param string $type Notification type ('success' or 'error').
	 * @param array  $data Notification data.
	 */
	public function send_email_notification( $type, $data = array() ) {
		$settings = get_option( 'adpr_settings', array() );

		// Check if emails are enabled.
		if ( empty( $settings['email_enabled'] ) ) {
			return;
		}

		// Check if this type of notification is enabled.
		if ( $type === 'success' && empty( $settings['email_on_success'] ) ) {
			return;
		}
		if ( $type === 'error' && empty( $settings['email_on_error'] ) ) {
			return;
		}

		$to      = ! empty( $settings['email_address'] ) ? $settings['email_address'] : get_option( 'admin_email' );
		$subject = sprintf(
			'[%s] %s',
			get_bloginfo( 'name' ),
			$type === 'success'
				? __( 'Auto Post Refresher - Update Successful', 'auto-daily-post-refresher' )
				: __( 'Auto Post Refresher - Update Error', 'auto-daily-post-refresher' )
		);

		// Build email body.
		$body = sprintf( __( 'Auto Post Refresher Update Report', 'auto-daily-post-refresher' ) ) . "\n\n";
		$body .= sprintf( __( 'Site: %s', 'auto-daily-post-refresher' ), get_bloginfo( 'url' ) ) . "\n";
		$body .= sprintf( __( 'Date: %s', 'auto-daily-post-refresher' ), current_time( 'mysql' ) ) . "\n";
		$body .= sprintf( __( 'Status: %s', 'auto-daily-post-refresher' ), $type === 'success' ? __( 'Success', 'auto-daily-post-refresher' ) : __( 'Error', 'auto-daily-post-refresher' ) ) . "\n\n";

		if ( ! empty( $data['posts_updated'] ) ) {
			$body .= sprintf( __( 'Posts Updated: %d', 'auto-daily-post-refresher' ), $data['posts_updated'] ) . "\n";
		}

		if ( ! empty( $data['message'] ) ) {
			$body .= "\n" . __( 'Details:', 'auto-daily-post-refresher' ) . "\n";
			$body .= $data['message'] . "\n";
		}

		$body .= "\n---\n";
		$body .= __( 'This is an automated message from Auto Post Refresher plugin.', 'auto-daily-post-refresher' ) . "\n";
		$body .= sprintf( __( 'To manage settings, visit: %s', 'auto-daily-post-refresher' ), admin_url( 'admin.php?page=adpr-settings' ) );

		// Send email.
		wp_mail( $to, $subject, $body );
	}
}
