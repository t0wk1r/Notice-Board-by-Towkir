<?php

/*
	Plugin Name: Notice Board by Towkir
	Description: A simple notice board plugin to display notices and special announcements in your WordPress site.
	Plugin URI: https://github.com/t0wk1r/Notice-Board-by-Towkir
	Author: Md Abu Sayed Towkir
	Author URI: http://towkir.info
	Version: 1.0
	License: GPLv2 or later
	Text Domain: notice_board_by_towkir
*/

/*
    Copyright (C) 2021  Md Abu Sayed Towkir  towkir29@gmail.com
*/

/**
* Registers a new post type
* @uses $wp_post_types Inserts new post type object into the list
*
* @param string  Post type key, must not exceed 20 characters
* @param array|string  See optional args description above.
* @return object|WP_Error the registered post type object, or an error object
*/

define('towkir_DIR_URL', plugin_dir_url( __FILE__ ));

function towkir_flush_rewrite(){
	towkir_create_notice();

	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'towkir_flush_rewrite' );


function towkir_create_notice() {

	$labels = array(
		'name'                => __( 'Notices', 'notice_board_by_towkir' ),
		'singular_name'       => __( 'Notice', 'notice_board_by_towkir' ),
		'add_new'             => _x( 'Add New Notice', 'notice_board_by_towkir', 'notice_board_by_towkir' ),
		'add_new_item'        => __( 'Add New Notice', 'notice_board_by_towkir' ),
		'edit_item'           => __( 'Edit Notice', 'notice_board_by_towkir' ),
		'new_item'            => __( 'New Notice', 'notice_board_by_towkir' ),
		'view_item'           => __( 'View Notice', 'notice_board_by_towkir' ),
		'search_items'        => __( 'Search Notices', 'notice_board_by_towkir' ),
		'not_found'           => __( 'No Notices found', 'notice_board_by_towkir' ),
		'not_found_in_trash'  => __( 'No Notices found in Trash', 'notice_board_by_towkir' ),
		'parent_item_colon'   => __( 'Parent Notice:', 'notice_board_by_towkir' ),
		'menu_name'           => __( 'Notices', 'notice_board_by_towkir' ),
	);

	$args = array(
		'labels'                   => $labels,
		'hierarchical'        => false,
		'description'         => 'description',
		'taxonomies'          => array('towkir_notice'),
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => null,
		'menu_icon'           => null,
		'show_in_nav_menus'   => true,
		'publicly_queryable'  => true,
		'exclude_from_search' => false,
		'has_archive'         => true,
		'query_var'           => true,
		'can_export'          => true,
		'rewrite'             => true,
		'capability_type'     => 'post',
		'supports'            => array(
			'title', 'editor'
			)
	);

	register_post_type( 'notice', $args );
}

/**
 * Call towkir_create_notice function on WordPress init
 */
add_action( 'init', 'towkir_create_notice' );


/**
 * Add custom field to insert the notice url
 * @return 
 */
function towkir_notice_add_meta_box() {

	add_meta_box( 'towkir_notice_meta', 'Notice Link', 'towkir_notice_meta_box', 'notice', 'normal' );

}

/**
 * Echoes the Notice URL field onto the edit post page
 * @return
 */
function towkir_notice_meta_box() {
	global $post;

	wp_nonce_field(plugin_basename(__FILE__), 'towkir_notice_url_nonce');

	$html = '<label for="towkir_notice_url">Notice Url: </label>';
	$html .= '<input name="towkir_notice_url" id="towkir_notice_url" class="regular-text" type="url" value="';
	
	if( $notice_url = get_post_meta( $post->ID, 'towkir_notice_url', true ) ){
		$html .= $notice_url;
	}

	$html .= '" /> <a href="#" id="clear_notice_url">Clear</a>';

	echo $html;
	wp_enqueue_script( 'towkir_notice', plugin_dir_url( __FILE__ ) . 
		'js/towkir_notice_script.js', array('jquery'), '1.0.0', 1 );
}

add_action('add_meta_boxes', 'towkir_notice_add_meta_box');


/**
 * Saves notice url
 * @param  int $post_id
 * @return 
 */
function towkir_notice_save_post( $post_id ){
	// don't save anything if WP is auto saving
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return $post_id;

	// check if correct post type and that the user has correct permissions
	if (  isset($_POST['post_type']) && 'notice' == $_POST['post_type' ] ) {
		if ( ! current_user_can( 'edit_page' , $post_id ) )
			return $post_id;
	} else {
	if ( ! current_user_can( 'edit_post' , $post_id ) )
		return $post_id;
 	}
	
	// update notice url
	if( isset($_POST['towkir_notice_url']) )
		update_post_meta( $post_id, 'towkir_notice_url', sanitize_text_field( $_POST['towkir_notice_url']));
}

/**
 * Call the above function( towkir_notice_save_post ) when saving a notice
 */
add_action( 'save_post' , 'towkir_notice_save_post' );



/**
 * Notice Board Widget
 * Displays all notices through a widget
 */
class Notice_Board_Widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		// widget actual processes
		parent::__construct( false, __('Notice Board', 'notice_board_by_towkir'), 
			array('description' => __('Displays a scrolling or static list of notices', 'notice_board_by_towkir')) );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		// outputs the content of the widget
		extract( $args );

		$title     = apply_filters( 'widget_title', $instance['title'] );
		$count     = $instance['count'];
		$type      = $instance['type'];
		$direction = $instance['dir'];

		require 'inc/widget-display.php';
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		// outputs the options form on admin
		$title = isset($instance['title']) ? esc_attr( $instance['title'] ) : '';
		$count = isset($instance['count']) ? esc_attr( $instance['count'] ) : 5;
		$type  = isset($instance['type']) ? esc_attr( $instance['type'] ) : 'static';
		$dir   = isset($instance['dir']) ? esc_attr( $instance['dir'] ) : 'up';

		require( 'inc/widget-form.php' );
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['count'] = strip_tags($new_instance['count']);
		$instance['type']  = esc_attr($new_instance['type']);
		$instance['dir']   = esc_attr($new_instance['dir']);

		return $instance;
	}
}


/**
 * Register Notice Board widget on Widgets Initialization
 */
function register_Notice_Board_Widget() {
    register_widget( 'Notice_Board_Widget' );
}
add_action( 'widgets_init', 'register_Notice_Board_Widget' );

/**
 * 
 * Register Notice Board shortcode and display in front-end pages.
 * 
 */
function towkir_notice_shortcode( $atts ){
	extract(shortcode_atts(array(
			'count'=>'4',
			'format'=>'table',
			'class'=>'table'
		), $atts) );
	if(!isset($atts['class'])){
		$class='';
	}
	$args = array(
		'post_type' => 'notice',
		'post_status' => 'publish',
		'posts_per_page' => $count
		);
	$query = null;
	$html='';
	/**
	 * Run a WP Query to get all notices
	 * @var WP_Query
	 */
	$query = new WP_Query($args);
	/**
	 * Check if notices exist and display output accordingly
	 */
	if( $query->have_posts() ){
		global $post;
		
		/**
		 * Output the required notices in a table
		 */
		$html .= '<div class="towkir-notice">';
		if('table'==$format){
			if ( $class == '' ) $class = 'table';
			$html.= "<table class='$class'>";
			$html.= "<tr><th>Subject</th><th>Date Published
			</th><th>Link</th></tr>";
			
			while ( $query->have_posts() ) {
				$query->the_post();
				$html.= '<tr><td>'. get_the_title() .'</td><td>'.get_the_time( get_option('date_format') , $post->ID)

				 .'</td>';
				if ( get_post_meta( $post->ID, 'towkir_notice_url', true ) == '' ) {
					$html.= '<td> <a href="' . 
						get_permalink() . '">view</a></td>';
				}
				else {
					$html.= '<td><a href="' . get_post_meta( $post->ID, 'towkir_notice_url', true ) 
						. '">view</a></td>';
				}
				$html .= "</td>";
				
			}
			
			$html.= "</tr></table>";
		}
		elseif('list'==$format){
			$html.= "<ul class='$class'>";
			while ( $query->have_posts() ) {
				$query->the_post();
				if ( get_post_meta( $post->ID, 'towkir_notice_url', true ) == '' ) {
					$html.= '<li><a href="' . 
						get_permalink() . '">' . get_the_title() .'</a> <small>- ' . get_the_time( get_option('date_format') , $post->ID) . '</small></li>';
				}
				else {
					$html.= '<li><a href="' . get_post_meta( $post->ID, 'towkir_notice_url', true ) 
						. '">'. get_the_title() .'</a> <small>- ' . get_the_time( get_option('date_format') , $post->ID) . '</small></li>';
				}
			}
			
			$html.= "</ul>";
		}
		$html .= '</div>';
	}
	else {
		$html.= "No notices yet.";
	}

	/**
	 * Reset WP Query post data
	 */
	wp_reset_postdata();

	return $html;
	
}
/**
customer demand shortcode**/
function customer_notice_shortcode( $atts ){
	extract(shortcode_atts(array(
			'count'=>'4',
			'format'=>'table',
			'class'=>'table'
		), $atts) );
	if(!isset($atts['class'])){
		$class='';
	}
	$args = array(
		'post_type' => 'notice',
		'post_status' => 'publish',
		'posts_per_page' => $count
		);
	$query = null;
	$html='';
	/**
	 * Run a WP Query to get all notices
	 * @var WP_Query
	 */
	$query = new WP_Query($args);
	/**
	 * Check if notices exist and display output accordingly
	 */
	if( $query->have_posts() ){
		global $post;
		
		/**
		 * Output the required notices in a table
		 */
		$html .= '<div class="towkir-notice">';
		if('table'==$format){
			if ( $class == '' ) $class = 'table';
			$html.= "<table class='$class'>";
			$html.= "<tr><th>Subject</th><th>Date Published
			</th><th>Link</th></tr>";
			
			while ( $query->have_posts() ) {
				$query->the_post();
				$html.= '<tr><td>'. get_the_title() .'</td><td>'.get_the_time( get_option('date_format') , $post->ID)

				 .'</td>';
				if ( get_post_meta( $post->ID, 'towkir_notice_url', true ) == '' ) {
					$html.= '<td> <a href="' . 
						get_permalink() . '">view</a></td>';
				}
				else {
					$html.= '<td><a href="' . get_post_meta( $post->ID, 'towkir_notice_url', true ) 
						. '">view</a></td>';
				}
				$html .= "</td>";
				
			}
			
			$html.= "</tr></table>";
		}
		elseif('list'==$format){
			$html.= "<ul class='$class'>";
			while ( $query->have_posts() ) {
				$query->the_post();
				if ( get_post_meta( $post->ID, 'towkir_notice_url', true ) == '' ) {
					$html.= '<li><a href="' . 
						get_permalink() . '">' . get_the_title() .'</a> <small>- ' . get_the_time( get_option('date_format') , $post->ID) . '</small></li>';
				}
				else {
					$html.= '<li><a href="' . get_post_meta( $post->ID, 'towkir_notice_url', true ) 
						. '">'. get_the_title() .'</a> <small>- ' . get_the_time( get_option('date_format') , $post->ID) . '</small></li>';
				}
			}
			
			$html.= "</ul>";
		}
		$html .= '</div>';
	}
	else {
		$html.= "No notices yet.";
	}

	/**
	 * Reset WP Query post data
	 */
	wp_reset_postdata();

	return $html;
	
}



add_shortcode( 'notice-board', 'towkir_notice_shortcode' );
add_shortcode( 'notice-board count="100"', 'customer_notice_shortcode' );

?>
