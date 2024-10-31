<?php
/*
Plugin Name: Responsive Bit Announcements Manager
Plugin URI: http://responsivebit.com/responsive-bit-announcements-manager
Description: Display custom-wide announcements on top of every page, with the ability to schedule messages between two dates.
Version: 1.0
Author: Responsive Bit
Author URI: http://responsivebit.com/
License: GPLv2 or later
*/

	class ResponsiveBitAnnouncements {
		
		// contructor
		function ResponsiveBitAnnouncements() {
			add_action('init', array($this,'responsiveBitCreateAnnouncementsPostType') );
			add_action('admin_menu',array($this,'responsiveBit_announcements_guide_settings') );
			add_action( 'add_meta_boxes', array($this, 'responsiveBit_announcements_add_metabox') );
			add_action( 'admin_enqueue_scripts', array($this, 'responsiveBit_backend_scripts') );
			add_action( 'save_post', array($this, 'responsiveBit_announcements_metabox_save') );
			add_action('wp_footer', array($this, 'responsiveBit_display_announcement') );
			add_action('wp_enqueue_scripts', array($this, 'responsiveBit_announcements_frontend_scripts') );
		}
		
		function responsiveBit_announcements_guide_settings() {
			add_submenu_page( 'edit.php?post_type=rb-announcements', 'Announcement\'s usage guide page', 'Announcement\'s usage guide', 'manage_options', 'rb_announcements_guide', array($this,'responsiveBit_generate_announcements_usage_guide_page') );
		}
		
		function responsiveBit_generate_announcements_usage_guide_page() {
			?>
            <center><h1>Announcement's usage Guide</h1></center>
            <h3>How to add Announcements</h3>
            <p>Simply add announcements from the admin menu on left side named Announcements. Set the starting and ending date of the announcement and type the content of the Announcement in the editor and you are good to go. Now on the date that you set Announcement will appear right on the top of every page of your site. </p>
            <h3>How to change the style/color of the background of the announcement</h3>
            <p>Simply open the responsive-bit-announcements-manager plugin folder and then goto css folder and in there open the file named <strong>announcemnts.css</strong>, in this file you can change the css according to your own needs.  </p>
            <p>To change the background color of announcements simply change the background value under #announcements and for the hover effect of cross button navigate to #announcements .wrapper .close</p>
            <h3>Important things to consider</h3>
            <p>If you close the announcement by clicking on the cross button then plugin will save the cookie on the user browser for not to show announcements for next 2 days means the cookie will expire in next 2 days.</p>
            <h3>Custom Help</h3>
            <p>If you need custom help with respect to this plugin or any other wordpress related issue, feel free to contact me. My email id is : <strong>support@responsivebit.com</strong><br />Kindly do provide your feedback for this plugin, it means alot. Enjoy this plugin.</p>
			<?php
		}
		
		function responsiveBit_announcements_filter_where( $where = '' ) {
			// ...where dates are blank
			$where .= " OR (mt1.meta_key = 'rb_announcements_start_date' AND CAST(mt1.meta_value AS CHAR) = '') OR (mt2.meta_key = 'rb_announcements_end_date' AND CAST(mt2.meta_value AS CHAR) = '')";
			return $where;
		}
		
		function responsiveBit_display_announcement() {
			global $wpdb;
			$today = date('Y-m-d');
			$args = array(
				'post_type' => 'rb-announcements',
				'posts_per_page' => 0,
				'meta_key' => 'rb_announcements_end_date',
				'orderby' => 'meta_value_num',
				'order' => 'ASC',
				'meta_query' => array(
					array(
						'key' => 'rb_announcements_start_date',
						'value' => $today,
						'compare' => '<=',
					),
					array(
						'key' => 'rb_announcements_end_date',
						'value' => $today,
						'compare' => '>=',
					)
				)
			);
			// Add a filter to do complex 'where' clauses...
			add_filter( 'posts_where', array($this, 'responsiveBit_announcements_filter_where') );
			$query = new WP_Query( $args );
			// Take the filter away again so this doesn't apply to all queries.
			remove_filter( 'posts_where', array($this, 'responsiveBit_announcements_filter_where') );
			$announcements = $query->posts;
			if($announcements) :
				?>
				<div id="announcements" class="hidden">
					<div class="wrapper">
						<a class="close" href="#" id="close"><?php _e('X', 'simple-announcements'); ?></a>
						<div class="rb_announcements_message">
							<?php
							foreach ($announcements as $announcement) {
								?>
								<?php echo do_shortcode(wpautop(($announcement->post_content))); ?>
								<?php
							}
							?>
						</div>
					</div>
				</div>
				<?php
			endif;
		}
		
		function responsiveBit_announcements_add_metabox() {
			add_meta_box( 'rb_announcements_metabox_id', 'Announcements Scheduling', array($this, 'responsiveBit_announcements_metabox_content'), 'rb-announcements', 'side', 'high' );
		}
		
		function responsiveBit_announcements_metabox_content( $post ) {
			$values = get_post_custom( $post->ID );
			$start_date = isset( $values['rb_announcements_start_date'] ) ? esc_attr( $values['rb_announcements_start_date'][0] ) : '';
			$end_date = isset( $values['rb_announcements_end_date'] ) ? esc_attr( $values['rb_announcements_end_date'][0] ) : '';
			wp_nonce_field( 'rb_announcements_metabox_nonce', 'metabox_nonce' );
			?>
			<p>
				<label for="rb_announcements_start_date">Start date</label>
				<input type="text" name="rb_announcements_start_date" id="rb_announcements_start_date" value="<?php echo $start_date; ?>" />
			</p>
			<p>
				<label for="rb_announcements_end_date">End date</label>
				<input type="text" name="rb_announcements_end_date" id="rb_announcements_end_date" value="<?php echo $end_date; ?>" />
			</p>
			<?php
		}
		
		function responsiveBit_announcements_metabox_save( $post_id ) {
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;
			if( !isset( $_POST['metabox_nonce'] ) || !wp_verify_nonce( $_POST['metabox_nonce'], 'rb_announcements_metabox_nonce' ) )
				return $post_id;
			if( !current_user_can( 'edit_post' ) )
				return $post_id;
			// Make sure data is set
			if( isset( $_POST['rb_announcements_start_date'] ) ) {
				$valid = 0;
				$old_value = get_post_meta($post_id, 'rb_announcements_start_date', true);
				if ( $_POST['rb_announcements_start_date'] != '' ) {
					$date = $_POST['rb_announcements_start_date'];
					$date = explode( '-', (string) $date );
					$valid = checkdate($date[1],$date[2],$date[0]);
				}
				if ($valid)
					update_post_meta( $post_id, 'rb_announcements_start_date', $_POST['rb_announcements_start_date'] );
				elseif (!$valid && $old_value)
					update_post_meta( $post_id, 'rb_announcements_start_date', $old_value );
				else
					update_post_meta( $post_id, 'rb_announcements_start_date', '');
			}
			if ( isset( $_POST['rb_announcements_end_date'] ) ) {
				if( $_POST['rb_announcements_start_date'] != '' ) {
					$old_value = get_post_meta($post_id, 'rb_announcements_end_date', true);
					$date = $_POST['rb_announcements_end_date'];
					$date = explode( '-', (string) $date );
					$valid = checkdate($date[1],$date[2],$date[0]);
				}
				if($valid)
					update_post_meta( $post_id, 'rb_announcements_end_date', $_POST['rb_announcements_end_date'] );
				elseif (!$valid && $old_value)
					update_post_meta( $post_id, 'rb_announcements_end_date', $old_value );
				else
					update_post_meta( $post_id, 'rb_announcements_end_date', '');
			}
		}
		
		function responsiveBit_announcements_frontend_scripts() {
			wp_enqueue_style( 'announcements-style', plugin_dir_url( __FILE__ ) . 'css/announcements.css');
			wp_enqueue_script( 'announcements', plugin_dir_url( __FILE__ ) . 'js/rb_announcements.js', array( 'jquery' ) );
			wp_enqueue_script( 'cookies', plugin_dir_url( __FILE__ ) . 'js/jquery.cookie.js', array( 'jquery' ) );
			wp_enqueue_script( 'cycle', plugin_dir_url( __FILE__ ) . 'js/jquery.cycle.lite.js', array( 'jquery' ) );
		}
		
		function responsiveBit_backend_scripts( $hook ) {
			global $post;
			if( ( !isset($post) || $post->post_type != 'rb-announcements' ))
				return;
			wp_enqueue_style( 'jquery-ui-fresh', plugin_dir_url( __FILE__ ) . '/css/jquery-ui-fresh.css');
			wp_enqueue_script( 'announcements', plugin_dir_url( __FILE__ ) . '/js/rb_announcements.js', array( 'jquery', 'jquery-ui-datepicker' ) );
		}
		
		function responsiveBitCreateAnnouncementsPostType() {
			$labels = array(
				'name' => _x('Announcements' , 'post type general name'),
				'singular_name' => _x( 'Announcements', 'post type singular name'),
				'add_new' => _x( 'Add New', 'Announcement'),
				'add_new_item' => __( 'Add New Announcement' ),
				'edit_item' => __( 'Edit Announcement' ),
				'new_item' => __( 'New Announcement' ),
				'view_item' => __( 'View Announcement' ),
				'search_items' => __( 'Search Announcements' ),
				'not_found' =>  __( 'No Announcements found' ),
				'not_found_in_trash' => __( 'No Announcements found in Trash' ),
				'parent_item_colon' => ''
			);
			$args = array(
				'labels' => $labels,
				'singular_label' => __('Announcement', 'simple-announcements'),
				'public' => true,
				'capability_type' => 'post',
				'rewrite' => false,
				'supports' => array('title', 'editor'),
			);
			register_post_type('rb-announcements', $args);
		}
		
	}
	
	$responsiveBitAnnouncements = new ResponsiveBitAnnouncements();
	
	//.....................................
	add_action('wp_dashboard_setup', 'responsiveBit_mycustom_dashboard_widgets_announcements');

    function responsiveBit_mycustom_dashboard_widgets_announcements() {
    global $wp_meta_boxes;

    wp_add_dashboard_widget('responsiveBit_custom_help_widget_announcements', 'Responsive Bit Announcement Plugin Support', 'custom_dashboard_help_announcement');
    }

    function custom_dashboard_help_announcement() {
    echo '<p><a href="http://www.responsivebit.com"><img src="'. plugins_url('contact_us.jpg', __FILE__) .'" /></a></p><p style="font-size:13px;padding-bottom: 5px;line-height: 22px;"></p><p style="font-size: 13px;padding-bottom: 5px;line-height: 22px;">For any query or any custom work contact us <a href="mailto:support@responsivebit.com">by email</a>. My email id is <strong>support@responsivebit.com</strong></p>';
    }
	//.....................................
	

?>