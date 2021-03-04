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
		'menu_icon' => plugin_dir_url(__FILE__) .'images/calendar_16.png',
		'menu_position' => 3,
		'rewrite' => array('slug' => 'event', 'with_front' => false),
		'has_archive' => 'events',
		'query_var' => false,
		'supports' => array('title', 'editor', 'comments', 'revisions'),
		'register_meta_box_cb' => 'hhs_event_cpt_add_metaboxes',
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

/* Sorts the movies. */
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

/**
 * Metaboxes: date
 */

function hhs_event_cpt_add_metaboxes() {
  add_meta_box('hhs_event_metabox', 'More Information', 'hhs_event_metabox', 'events', 'side', 'core');
  add_action( 'admin_enqueue_scripts', 'hhs_event_admin_scripts' );
}

function hhs_event_admin_scripts() {
	wp_enqueue_script( 'jqueryui-datepicker', plugin_dir_url(__FILE__) .'datepicker/jquery-ui-datepicker.js', array( 'jquery', 'jquery-ui-core' ) );
	wp_enqueue_style('jqueryui-datepicker-css', plugin_dir_url(__FILE__) .'datepicker/jquery-ui-datepicker.css' );
}

function hhs_event_metabox() {
	global $post, $wp_locale;

	wp_nonce_field( plugin_basename( __FILE__ ), 'hhs_eventmeta_nonce' );

	$start_date = get_post_meta($post->ID, 'start_date', true);
	$start_time = get_post_meta($post->ID, 'start_time', true);
	$end_date = get_post_meta($post->ID, 'end_date', true);
	$end_time = get_post_meta($post->ID, 'end_time', true);
	//$timezone = get_post_meta($post->ID, 'timezone', true);
	$venue = get_post_meta($post->ID, 'venue', true);
	$venue_url = get_post_meta($post->ID, 'venue_url', true);
	$venue_address = get_post_meta($post->ID, 'venue_address', true);

	// todo: when you get the timezone display to work, put it into the metabox

	// timezone list
	/*$zonelist = array(
		'Kwajalein' => '(GMT-12:00) International Date Line West',
		'Pacific/Midway' => '(GMT-11:00) Midway Island',
		'Pacific/Samoa' => '(GMT-11:00) Samoa',
		'Pacific/Honolulu' => '(GMT-10:00) Hawaii',
		'America/Anchorage' => '(GMT-09:00) Alaska',
		'America/Los_Angeles' => '(GMT-08:00) Pacific Time (US &amp; Canada)',
		'America/Tijuana' => '(GMT-08:00) Tijuana, Baja California',
		'America/Denver' => '(GMT-07:00) Mountain Time (US &amp; Canada)',
		'America/Chihuahua' => '(GMT-07:00) Chihuahua',
		'America/Mazatlan' => '(GMT-07:00) Mazatlan',
		'America/Phoenix' => '(GMT-07:00) Arizona',
		'America/Regina' => '(GMT-06:00) Saskatchewan',
		'America/Tegucigalpa' => '(GMT-06:00) Central America',
		'America/Chicago' => '(GMT-06:00) Central Time (US &amp; Canada)',
		'America/Mexico_City' => '(GMT-06:00) Mexico City',
		'America/Monterrey' => '(GMT-06:00) Monterrey',
		'America/New_York' => '(GMT-05:00) Eastern Time (US &amp; Canada)',
		'America/Bogota' => '(GMT-05:00) Bogota',
		'America/Lima' => '(GMT-05:00) Lima',
		'America/Rio_Branco' => '(GMT-05:00) Rio Branco',
		'America/Indiana/Indianapolis' => '(GMT-05:00) Indiana (East)',
		'America/Caracas' => '(GMT-04:30) Caracas',
		'America/Halifax' => '(GMT-04:00) Atlantic Time (Canada)',
		'America/Manaus' => '(GMT-04:00) Manaus',
		'America/Santiago' => '(GMT-04:00) Santiago',
		'America/La_Paz' => '(GMT-04:00) La Paz',
		'America/St_Johns' => '(GMT-03:30) Newfoundland',
		'America/Argentina/Buenos_Aires' => '(GMT-03:00) Georgetown',
		'America/Sao_Paulo' => '(GMT-03:00) Brasilia',
		'America/Godthab' => '(GMT-03:00) Greenland',
		'America/Montevideo' => '(GMT-03:00) Montevideo',
		'Atlantic/South_Georgia' => '(GMT-02:00) Mid-Atlantic',
		'Atlantic/Azores' => '(GMT-01:00) Azores',
		'Atlantic/Cape_Verde' => '(GMT-01:00) Cape Verde Is.',
		'Europe/Dublin' => '(GMT) Dublin',
		'Europe/Lisbon' => '(GMT) Lisbon',
		'Europe/London' => '(GMT) London',
		'Africa/Monrovia' => '(GMT) Monrovia',
		'Atlantic/Reykjavik' => '(GMT) Reykjavik',
		'Africa/Casablanca' => '(GMT) Casablanca',
		'Europe/Belgrade' => '(GMT+01:00) Belgrade',
		'Europe/Bratislava' => '(GMT+01:00) Bratislava',
		'Europe/Budapest' => '(GMT+01:00) Budapest',
		'Europe/Ljubljana' => '(GMT+01:00) Ljubljana',
		'Europe/Prague' => '(GMT+01:00) Prague',
		'Europe/Sarajevo' => '(GMT+01:00) Sarajevo',
		'Europe/Skopje' => '(GMT+01:00) Skopje',
		'Europe/Warsaw' => '(GMT+01:00) Warsaw',
		'Europe/Zagreb' => '(GMT+01:00) Zagreb',
		'Europe/Brussels' => '(GMT+01:00) Brussels',
		'Europe/Copenhagen' => '(GMT+01:00) Copenhagen',
		'Europe/Madrid' => '(GMT+01:00) Madrid',
		'Europe/Paris' => '(GMT+01:00) Paris',
		'Africa/Algiers' => '(GMT+01:00) West Central Africa',
		'Europe/Amsterdam' => '(GMT+01:00) Amsterdam',
		'Europe/Berlin' => '(GMT+01:00) Berlin',
		'Europe/Rome' => '(GMT+01:00) Rome',
		'Europe/Stockholm' => '(GMT+01:00) Stockholm',
		'Europe/Vienna' => '(GMT+01:00) Vienna',
		'Europe/Minsk' => '(GMT+02:00) Minsk',
		'Africa/Cairo' => '(GMT+02:00) Cairo',
		'Europe/Helsinki' => '(GMT+02:00) Helsinki',
		'Europe/Riga' => '(GMT+02:00) Riga',
		'Europe/Sofia' => '(GMT+02:00) Sofia',
		'Europe/Tallinn' => '(GMT+02:00) Tallinn',
		'Europe/Vilnius' => '(GMT+02:00) Vilnius',
		'Europe/Athens' => '(GMT+02:00) Athens',
		'Europe/Bucharest' => '(GMT+02:00) Bucharest',
		'Europe/Istanbul' => '(GMT+02:00) Istanbul',
		'Asia/Jerusalem' => '(GMT+02:00) Jerusalem',
		'Asia/Amman' => '(GMT+02:00) Amman',
		'Asia/Beirut' => '(GMT+02:00) Beirut',
		'Africa/Windhoek' => '(GMT+02:00) Windhoek',
		'Africa/Harare' => '(GMT+02:00) Harare',
		'Asia/Kuwait' => '(GMT+03:00) Kuwait',
		'Asia/Riyadh' => '(GMT+03:00) Riyadh',
		'Asia/Baghdad' => '(GMT+03:00) Baghdad',
		'Africa/Nairobi' => '(GMT+03:00) Nairobi',
		'Asia/Tbilisi' => '(GMT+03:00) Tbilisi',
		'Europe/Moscow' => '(GMT+03:00) Moscow',
		'Europe/Volgograd' => '(GMT+03:00) Volgograd',
		'Asia/Tehran' => '(GMT+03:30) Tehran',
		'Asia/Muscat' => '(GMT+04:00) Muscat',
		'Asia/Baku' => '(GMT+04:00) Baku',
		'Asia/Yerevan' => '(GMT+04:00) Yerevan',
		'Asia/Yekaterinburg' => '(GMT+05:00) Ekaterinburg',
		'Asia/Karachi' => '(GMT+05:00) Karachi',
		'Asia/Tashkent' => '(GMT+05:00) Tashkent',
		'Asia/Kolkata' => '(GMT+05:30) Calcutta',
		'Asia/Colombo' => '(GMT+05:30) Sri Jayawardenepura',
		'Asia/Katmandu' => '(GMT+05:45) Kathmandu',
		'Asia/Dhaka' => '(GMT+06:00) Dhaka',
		'Asia/Almaty' => '(GMT+06:00) Almaty',
		'Asia/Novosibirsk' => '(GMT+06:00) Novosibirsk',
		'Asia/Rangoon' => '(GMT+06:30) Yangon (Rangoon)',
		'Asia/Krasnoyarsk' => '(GMT+07:00) Krasnoyarsk',
		'Asia/Bangkok' => '(GMT+07:00) Bangkok',
		'Asia/Jakarta' => '(GMT+07:00) Jakarta',
		'Asia/Brunei' => '(GMT+08:00) Beijing',
		'Asia/Chongqing' => '(GMT+08:00) Chongqing',
		'Asia/Hong_Kong' => '(GMT+08:00) Hong Kong',
		'Asia/Urumqi' => '(GMT+08:00) Urumqi',
		'Asia/Irkutsk' => '(GMT+08:00) Irkutsk',
		'Asia/Ulaanbaatar' => '(GMT+08:00) Ulaan Bataar',
		'Asia/Kuala_Lumpur' => '(GMT+08:00) Kuala Lumpur',
		'Asia/Singapore' => '(GMT+08:00) Singapore',
		'Asia/Taipei' => '(GMT+08:00) Taipei',
		'Australia/Perth' => '(GMT+08:00) Perth',
		'Asia/Seoul' => '(GMT+09:00) Seoul',
		'Asia/Tokyo' => '(GMT+09:00) Tokyo',
		'Asia/Yakutsk' => '(GMT+09:00) Yakutsk',
		'Australia/Darwin' => '(GMT+09:30) Darwin',
		'Australia/Adelaide' => '(GMT+09:30) Adelaide',
		'Australia/Canberra' => '(GMT+10:00) Canberra',
		'Australia/Melbourne' => '(GMT+10:00) Melbourne',
		'Australia/Sydney' => '(GMT+10:00) Sydney',
		'Australia/Brisbane' => '(GMT+10:00) Brisbane',
		'Australia/Hobart' => '(GMT+10:00) Hobart',
		'Asia/Vladivostok' => '(GMT+10:00) Vladivostok',
		'Pacific/Guam' => '(GMT+10:00) Guam',
		'Pacific/Port_Moresby' => '(GMT+10:00) Port Moresby',
		'Asia/Magadan' => '(GMT+11:00) Magadan',
		'Pacific/Fiji' => '(GMT+12:00) Fiji',
		'Asia/Kamchatka' => '(GMT+12:00) Kamchatka',
		'Pacific/Auckland' => '(GMT+12:00) Auckland',
		'Pacific/Tongatapu' => '(GMT+13:00) Nukualofa'
	);*/

	echo '<p><strong>Start</strong></p>
	<p><label for="start_date">Date:</label> <input type="text" class="datepicker" name="start_date" value="' . esc_attr( $start_date ) . '" size="10" /><br />
		<label for="start_time">Time:</label> <input type="text" name="start_time" value="' . esc_attr( $start_time ) . '" size="8" /></p>';
	echo '<p><strong>End</strong></p>
	<p><label for="end_date">Date:</label> <input type="text" class="datepicker" name="end_date" value="' . esc_attr( $end_date ) . '" size="10" /><br />
		<label for="end_time">Time:</label> <input type="text" name="end_time" value="' . esc_attr( $end_time ) . '" size="8" /></p>';
	/*echo '<p><label for="timezone">Timezone:</label> <select name="timezone">';
	foreach($zonelist as $key => $value) {
		echo '	<option value="' . $key . '">' . $value . '</option>' . "\n";
	}
	echo '</select></p>';*/
	echo '<p><strong>Location</strong></p>
	<p><label for="venue">Venue:</label> <input type="text" name="venue" value="' . esc_attr( $venue ) . '" class="widefat" /></p>
	<p><label for="venue_url">Venue URL:</label> <input type="text" name="venue_url" value="' . esc_attr( $venue_url ) . '" class="widefat" /></p>
	<p><label for="venue_address">Address:</label> <input type="text" name="venue_address" value="' . esc_attr( $venue_address ) . '" class="widefat" /></p>';

	echo "<script type=\"text/javascript\">
	jQuery(document).ready(function(){
		jQuery('.datepicker').datepicker({
			dateFormat : 'yy-mm-dd'
		});
	});
	</script>";
}

// Save data from meta box
add_action('save_post', 'hhs_event_metabox_save');
function hhs_event_metabox_save($post_id) {
  // verify nonce
  if ( ! isset( $_POST['hhs_eventmeta_nonce'] ) || ! wp_verify_nonce( $_POST['hhs_eventmeta_nonce'], plugin_basename( __FILE__ ) ) )
	return;

  // check autosave
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
	return;

  // check permissions
  if (!current_user_can('edit_post', $post_id))
	return;

  $metakeys = array(
	'start_date',
	'start_time',
	'end_date',
	'end_time',
	//'timezone',
	'venue',
	'venue_url',
	'venue_address',
  );

  foreach ( $metakeys as $metakey ) {
	$old = get_post_meta( $post_id, $metakey, true );
	$new = null;

	if ( ! empty( $_POST[ $metakey ] ) ) {
		$new = wp_filter_post_kses( $_POST[$metakey] );
		update_post_meta( $post_id, $metakey, $new );
	} elseif ( ! empty( $old ) ) {
		delete_post_meta($post_id, $metakey, $old );
	}
  }
}
