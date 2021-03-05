<?php
/*
Plugin Name: HHS Events
Plugin URI: http://helenhousandi.com
Description: Custom post type for classical musician events.
Version: 1.0
Author: Helen Hou-Sandi
Author URI: http://helenhousandi.com
Min WP Version: 3.0
*/

/**
 * Define an endpoint mask constant for Hubs.
 * 2^13-1 is EP_ALL, every bit before that is already assigned.
 * We'll use 2^17, to be ideally out of reach from core collisions.
 * But this does mean that EP_ALL will not include our defined mask.
 */
define( 'EP_EVENTS', 2^17 );

function hhs_event_archive_endpoint() {
	add_rewrite_endpoint( 'archive', EP_EVENTS );
}
add_action( 'init', 'hhs_event_archive_endpoint' );

/******************************************
 * Set up custom post type and taxonomies
 ******************************************/
add_action( 'init', 'register_hhs_event_cpt', 1 );
function register_hhs_event_cpt() {

	$event_labels = array(
	'name' => _x('Events', 'post type general name'),
	'singular_name' => _x('Event', 'post type singular name'),
	'add_new' => _x('Add Event', 'event'),
	'add_new_item' => __('Add Event'),
	'edit_item' => __('Edit Event'),
	'new_item' => __('New Event'),
	'view_item' => __('View Event'),
	'search_items' => __('Search Events'),
	'not_found' =>  __('No events found'),
	'not_found_in_trash' => __('No events found in Trash'),
	'parent_item_colon' => ''
  );

	register_post_type('events', array(
		'label' => __('Events'),
		'labels' => $event_labels,
		'public' => true,
		'show_ui' => true,
		'capability_type' => 'post',
		'hierarchical' => false,
		'menu_icon' => 'dashicons-tickets-alt',
		'menu_position' => 3,
		'rewrite' => array('slug' => 'event', 'with_front' => false),
		'has_archive' => 'events',
		'query_var' => false,
		'supports' => array('title', 'editor', 'excerpt', 'revisions', 'custom-fields'),
		'show_in_rest' => true,
		'template' => [
			[ 'hhs/event-info' ],
			[ 'core/paragraph', [ 'content' => 'More event information can be added here, such as repertoire or posters. You can also remove the map below if not needed. Be sure to edit or delete this text before publishing.' ] ],
			[ 'webfactory/map' ],
		],
	));
}

/*******************************************
 * Event edit columns
 *******************************************/
add_filter( 'manage_edit-events_columns', 'hhs_event_cpt_edit_columns' );
add_action( 'manage_posts_custom_column',  'hhs_event_cpt_custom_columns' );
add_filter( 'manage_edit-events_sortable_columns', 'hhs_event_cpt_sortable_columns' );
add_action( 'load-edit.php', 'hhs_event_cpt_edit_load' );

function hhs_event_cpt_edit_columns( $columns ) {
	$columns['title'] = 'Event Title';
	unset( $columns['comments'] );
	unset( $columns['date'] );
	$columns['event-date'] = 'Event Date';
	$columns['end-date'] = 'End Date';
	$columns['start-time'] = 'Start Time';
	$columns['end-time'] = 'End Time';
	$columns['event-venue'] = 'Venue';

	return $columns;
}

function hhs_event_cpt_custom_columns( $column ) {
	$meta = get_post_custom();
	switch ( $column )
	{
		case "event-date":
			echo isset( $meta['start_date'][0] ) ? $meta['start_date'][0] : '';
			break;
		case "end-date":
			echo isset( $meta['end_date'][0] ) ? $meta['end_date'][0] : '';
			break;
		case "start-time":
			echo isset( $meta['start_time'][0] ) ? $meta['start_time'][0] : '';
			break;
		case "end-time":
			echo isset( $meta['end_time'][0] ) ? $meta['end_time'][0] : '';
			break;
		case "event-venue":
			echo isset( $meta['venue'][0] ) ? $meta['venue'][0] : '';
			break;
	}
}

function hhs_event_cpt_sortable_columns( $columns ) {
	$columns['event-date'] = 'event-date';
	return $columns;
}

function hhs_event_cpt_edit_load() {
	add_filter( 'request', 'hhs_event_cpt_sort' );
}

/* Sorts the column. */
function hhs_event_cpt_sort( $vars ) {

	/* Check if we're viewing the 'event' post type. */
	if ( isset( $vars['post_type'] ) && 'event' == $vars['post_type'] ) {

		/* Check if 'orderby' is set to 'event-date'. */
		if ( isset( $vars['orderby'] ) && 'event-date' == $vars['orderby'] ) {

			/* Merge the query vars with our custom variables. */
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => 'event-date',
					'orderby' => 'meta_value_num'
				)
			);
		}
	}

	return $vars;
}

add_action( 'init', function() {
	$keys = [
		'end_date',
		'start_date',
		'venue',
		'venue_url',
	];

	foreach( $keys as $key ) {
		register_post_meta( 'events', $key, array(
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true,
		) );
	}

	// Special prepare handling
	register_post_meta( 'events', 'start_time', array(
		'type' => 'string',
		'single' => true,
		'show_in_rest' => [
			'prepare_callback' => function( $value ) {
				$date = new DateTime( $value );
				return $date->format( 'H:i:s' );
			}
		],
	) );

	register_post_meta( 'events', 'end_time', array(
		'type' => 'string',
		'single' => true,
		'show_in_rest' => [
			'prepare_callback' => function( $value ) {
				$date = new DateTime( $value );
				return $date->format( 'H:i:s' );
			}
		],
	) );

	$script_asset = require 'build/index.asset.php';

    wp_register_script(
        'hhs-event-cpt-editor',
        plugin_dir_url( __FILE__ ) . 'build/index.js',
        $script_asset['dependencies'],
        $script_asset['version']
    );

	wp_register_style(
        'hhs-event-cpt-editor',
        plugin_dir_url( __FILE__ ) . 'build/index.css',
        [],
        filemtime( plugin_dir_path( __FILE__ ) . 'build/index.css' )
    );

	register_block_type( 'hhs/event-info', [
		'editor_script' => 'hhs-event-cpt-editor',
		'editor_style' => 'hhs-event-cpt-editor',
		'render_callback' => function() {
			ob_start();
			require 'block-render.php';
			return ob_get_clean();
		},
	]);
} );

add_filter( 'enter_title_here', function( $text, $post ) {
	if ( 'events' === get_post_type( $post ) ) {
		$text = 'Event title';
	}

	return $text;
}, 10, 2 );

add_filter( 'render_block', function( $block_content, $block ) {
	if ( is_post_type_archive( 'events' ) && 'webfactory/map' === $block['blockName'] ) {
		return '';
	} else {
		return $block_content;
	}
}, 10, 2 );
