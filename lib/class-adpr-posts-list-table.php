<?php
/**
 * Posts list table class
 *
 * @package Auto Daily Post Refresher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * ADPR_Posts_List_Table class
 *
 * Extends WP_List_Table to display posts with advanced filtering and selection.
 */
class ADPR_Posts_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'post',
				'plural'   => 'posts',
				'ajax'     => true,
			)
		);
	}

	/**
	 * Get table columns.
	 *
	 * @return array Columns array.
	 */
	public function get_columns() {
		return array(
			'cb'            => '<input type="checkbox" />',
			'title'         => __( 'Title', 'auto-daily-post-refresher' ),
			'post_type'     => __( 'Type', 'auto-daily-post-refresher' ),
			'author'        => __( 'Author', 'auto-daily-post-refresher' ),
			'categories'    => __( 'Categories', 'auto-daily-post-refresher' ),
			'date'          => __( 'Date', 'auto-daily-post-refresher' ),
			'status'        => __( 'Status', 'auto-daily-post-refresher' ),
			'auto_update'   => __( 'Auto-Update', 'auto-daily-post-refresher' ),
			'update_count'  => __( 'Updates', 'auto-daily-post-refresher' ),
			'last_updated'  => __( 'Last Auto-Update', 'auto-daily-post-refresher' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array Sortable columns.
	 */
	protected function get_sortable_columns() {
		return array(
			'title'        => array( 'title', false ),
			'post_type'    => array( 'post_type', false ),
			'author'       => array( 'author', false ),
			'date'         => array( 'date', true ),
			'update_count' => array( 'update_count', false ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array Bulk actions.
	 */
	protected function get_bulk_actions() {
		return array(
			'enable'  => __( 'Enable Auto-Update', 'auto-daily-post-refresher' ),
			'disable' => __( 'Disable Auto-Update', 'auto-daily-post-refresher' ),
		);
	}

	/**
	 * Render checkbox column.
	 *
	 * @param object $item Post object.
	 * @return string Checkbox HTML.
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="post_ids[]" value="%d" />', $item->ID );
	}

	/**
	 * Render title column.
	 *
	 * @param object $item Post object.
	 * @return string Title HTML.
	 */
	public function column_title( $item ) {
		$edit_link = get_edit_post_link( $item->ID );
		$view_link = get_permalink( $item->ID );

		$actions = array(
			'edit' => sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), __( 'Edit', 'auto-daily-post-refresher' ) ),
			'view' => sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $view_link ), __( 'View', 'auto-daily-post-refresher' ) ),
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_link ),
			esc_html( $item->post_title ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render post type column.
	 *
	 * @param object $item Post object.
	 * @return string Post type label.
	 */
	public function column_post_type( $item ) {
		$post_type_obj = get_post_type_object( $item->post_type );
		return $post_type_obj ? esc_html( $post_type_obj->labels->singular_name ) : esc_html( $item->post_type );
	}

	/**
	 * Render author column.
	 *
	 * @param object $item Post object.
	 * @return string Author name.
	 */
	public function column_author( $item ) {
		$author = get_userdata( $item->post_author );
		return $author ? esc_html( $author->display_name ) : __( 'Unknown', 'auto-daily-post-refresher' );
	}

	/**
	 * Render categories column.
	 *
	 * @param object $item Post object.
	 * @return string Categories list.
	 */
	public function column_categories( $item ) {
		$taxonomies = get_object_taxonomies( $item->post_type, 'objects' );
		$terms      = array();

		foreach ( $taxonomies as $taxonomy ) {
			if ( $taxonomy->hierarchical ) {
				$post_terms = get_the_terms( $item->ID, $taxonomy->name );
				if ( $post_terms && ! is_wp_error( $post_terms ) ) {
					foreach ( $post_terms as $term ) {
						$terms[] = esc_html( $term->name );
					}
				}
			}
		}

		return ! empty( $terms ) ? implode( ', ', $terms ) : '—';
	}

	/**
	 * Render date column.
	 *
	 * @param object $item Post object.
	 * @return string Formatted date.
	 */
	public function column_date( $item ) {
		$date = mysql2date( get_option( 'date_format' ), $item->post_date );
		$time = mysql2date( get_option( 'time_format' ), $item->post_date );
		return sprintf( '%s<br>%s', esc_html( $date ), esc_html( $time ) );
	}

	/**
	 * Render status column.
	 *
	 * @param object $item Post object.
	 * @return string Status badge.
	 */
	public function column_status( $item ) {
		$status_obj = get_post_status_object( $item->post_status );
		return $status_obj ? sprintf(
			'<span class="adpr-status-badge adpr-status-%s">%s</span>',
			esc_attr( $item->post_status ),
			esc_html( $status_obj->label )
		) : esc_html( $item->post_status );
	}

	/**
	 * Render auto-update column.
	 *
	 * @param object $item Post object.
	 * @return string Toggle switch HTML.
	 */
	public function column_auto_update( $item ) {
		$enabled = get_post_meta( $item->ID, '_adpr_auto_update_enabled', true ) === 'yes';

		return sprintf(
			'<label class="adpr-toggle-switch">
				<input type="checkbox" class="adpr-toggle-input" data-post-id="%d" %s>
				<span class="adpr-toggle-slider"></span>
			</label>',
			$item->ID,
			checked( $enabled, true, false )
		);
	}

	/**
	 * Render update count column.
	 *
	 * @param object $item Post object.
	 * @return string Update count.
	 */
	public function column_update_count( $item ) {
		$count = (int) get_post_meta( $item->ID, '_adpr_update_count', true );
		return $count > 0 ? esc_html( number_format_i18n( $count ) ) : '—';
	}

	/**
	 * Render last updated column.
	 *
	 * @param object $item Post object.
	 * @return string Last update date.
	 */
	public function column_last_updated( $item ) {
		$last_update = get_post_meta( $item->ID, '_adpr_last_auto_update', true );

		if ( ! empty( $last_update ) ) {
			$date = mysql2date( get_option( 'date_format' ), $last_update );
			$time = mysql2date( get_option( 'time_format' ), $last_update );
			return sprintf( '%s<br>%s', esc_html( $date ), esc_html( $time ) );
		}

		return '—';
	}

	/**
	 * Display extra filters above the table.
	 *
	 * @param string $which Position (top or bottom).
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$settings       = get_option( 'adpr_settings', array() );
		$allowed_types  = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post' );
		$selected_type  = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : '';
		$selected_cat   = isset( $_GET['category'] ) ? absint( $_GET['category'] ) : 0;
		$selected_author = isset( $_GET['author'] ) ? absint( $_GET['author'] ) : 0;
		$selected_status = isset( $_GET['auto_status'] ) ? sanitize_text_field( $_GET['auto_status'] ) : '';

		?>
		<div class="alignleft actions">
			<!-- Post Type Filter -->
			<select name="post_type" id="adpr-filter-type">
				<option value=""><?php esc_html_e( 'All Post Types', 'auto-daily-post-refresher' ); ?></option>
				<?php foreach ( $allowed_types as $type ) : ?>
					<?php $type_obj = get_post_type_object( $type ); ?>
					<?php if ( $type_obj ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $selected_type, $type ); ?>>
							<?php echo esc_html( $type_obj->labels->singular_name ); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>

			<!-- Category Filter -->
			<?php
			$categories = get_categories( array( 'hide_empty' => false ) );
			if ( ! empty( $categories ) ) :
				?>
				<select name="category" id="adpr-filter-category">
					<option value="0"><?php esc_html_e( 'All Categories', 'auto-daily-post-refresher' ); ?></option>
					<?php foreach ( $categories as $category ) : ?>
						<option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $selected_cat, $category->term_id ); ?>>
							<?php echo esc_html( $category->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<!-- Author Filter -->
			<?php
			$authors = get_users( array( 'who' => 'authors' ) );
			if ( ! empty( $authors ) ) :
				?>
				<select name="author" id="adpr-filter-author">
					<option value="0"><?php esc_html_e( 'All Authors', 'auto-daily-post-refresher' ); ?></option>
					<?php foreach ( $authors as $author ) : ?>
						<option value="<?php echo esc_attr( $author->ID ); ?>" <?php selected( $selected_author, $author->ID ); ?>>
							<?php echo esc_html( $author->display_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<!-- Auto-Update Status Filter -->
			<select name="auto_status" id="adpr-filter-status">
				<option value=""><?php esc_html_e( 'All Statuses', 'auto-daily-post-refresher' ); ?></option>
				<option value="enabled" <?php selected( $selected_status, 'enabled' ); ?>>
					<?php esc_html_e( 'Auto-Update Enabled', 'auto-daily-post-refresher' ); ?>
				</option>
				<option value="disabled" <?php selected( $selected_status, 'disabled' ); ?>>
					<?php esc_html_e( 'Auto-Update Disabled', 'auto-daily-post-refresher' ); ?>
				</option>
			</select>

			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'auto-daily-post-refresher' ); ?></button>
		</div>

		<div class="alignleft actions">
			<button type="button" id="adpr-select-all" class="button">
				<?php esc_html_e( 'Select All', 'auto-daily-post-refresher' ); ?>
			</button>
			<button type="button" id="adpr-select-none" class="button">
				<?php esc_html_e( 'Select None', 'auto-daily-post-refresher' ); ?>
			</button>
			<button type="button" id="adpr-select-filtered" class="button">
				<?php esc_html_e( 'Select Filtered', 'auto-daily-post-refresher' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Prepare items for display.
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'adpr_posts_per_page', 20 );
		$current_page = $this->get_pagenum();

		$settings      = get_option( 'adpr_settings', array() );
		$allowed_types = isset( $settings['post_types'] ) ? $settings['post_types'] : array( 'post' );

		// Build query args.
		$args = array(
			'post_type'      => $allowed_types,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Apply filters.
		if ( ! empty( $_GET['post_type'] ) ) {
			$args['post_type'] = sanitize_text_field( $_GET['post_type'] );
		}

		if ( ! empty( $_GET['category'] ) ) {
			$args['cat'] = absint( $_GET['category'] );
		}

		if ( ! empty( $_GET['author'] ) ) {
			$args['author'] = absint( $_GET['author'] );
		}

		if ( ! empty( $_GET['s'] ) ) {
			$args['s'] = sanitize_text_field( $_GET['s'] );
		}

		// Filter by auto-update status.
		if ( ! empty( $_GET['auto_status'] ) ) {
			$status = sanitize_text_field( $_GET['auto_status'] );
			$args['meta_query'] = array(
				array(
					'key'     => '_adpr_auto_update_enabled',
					'value'   => ( $status === 'enabled' ) ? 'yes' : 'no',
					'compare' => '=',
				),
			);
		}

		// Apply sorting.
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = sanitize_text_field( $_GET['orderby'] );
			if ( in_array( $orderby, array( 'title', 'post_type', 'author', 'date' ), true ) ) {
				$args['orderby'] = $orderby;
			} elseif ( $orderby === 'update_count' ) {
				$args['meta_key'] = '_adpr_update_count';
				$args['orderby']  = 'meta_value_num';
			}
		}

		if ( ! empty( $_GET['order'] ) ) {
			$order = strtoupper( sanitize_text_field( $_GET['order'] ) );
			if ( in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
				$args['order'] = $order;
			}
		}

		// Get posts.
		$query = new WP_Query( $args );
		$this->items = $query->posts;

		// Set pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $query->found_posts,
				'per_page'    => $per_page,
				'total_pages' => ceil( $query->found_posts / $per_page ),
			)
		);

		// Set columns.
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
	}

	/**
	 * Display when no items found.
	 */
	public function no_items() {
		esc_html_e( 'No posts found.', 'auto-daily-post-refresher' );
	}

	/**
	 * Process bulk actions.
	 */
	public function process_bulk_action() {
		if ( ! isset( $_POST['post_ids'] ) || ! is_array( $_POST['post_ids'] ) ) {
			return;
		}

		$action = $this->current_action();

		if ( ! $action || ! in_array( $action, array( 'enable', 'disable' ), true ) ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions.', 'auto-daily-post-refresher' ) );
		}

		$post_ids = array_map( 'absint', $_POST['post_ids'] );
		$value    = ( $action === 'enable' ) ? 'yes' : 'no';

		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, '_adpr_auto_update_enabled', $value );
		}

		$redirect = add_query_arg(
			array(
				'page'    => 'adpr-selector',
				'updated' => count( $post_ids ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}
}
