<?php
$metakeys = array(
	'start_date',
	'start_time',
	'end_date',
	'end_time',
	'venue',
	'venue_url',
	'venue_address',
);

foreach ( $metakeys as $key ) {
	$meta[ $key ] = esc_html( get_post_meta( get_the_ID(), $key, true) );
}

// don't run strtotime over and over
$start = strtotime( $meta['start_date'] );
$end = strtotime( $meta['end_date'] );

if ( empty( $meta['end_date'] ) || $meta['end_date'] === $meta['start_date'] ) {
	$date = date('F j, Y', $start );

	// time is set
	if ( ! empty( $meta['start_time'] ) ) {
		$time = new DateTime( $meta['start_time'] );

		$date .= ' at ' . $time->format('g:iA');
	}
}

// end date set
else {
	if ( date( 'Y', $end ) === date( 'Y', $start ) ) {
		$year = date( 'Y', $end );

		if ( date( 'm', $end ) == date( 'm', $start ) ) {
			$month = date( 'F', $end );

			$date = $month . ' ' . date( 'j', $start) . '&ndash;' . date( 'j', $end ) . ", $year";
		}

		// not same month
		else {
			$date = date( 'F j', $start ) . ' &ndash; ' . date( 'F j', $end ) . ", $year";
		}
	}

	// not same year
	else {
		$date = date( 'F j, Y', $start ) . ' &ndash; ' . date( 'F j, Y', $end );
	}
}

// venue URL is set
if ( ! empty( $meta['venue_url'] ) ) {
	$venue = '<a href="' . esc_url( $meta['venue_url'] ) . '">' . $meta['venue'] . '</a>';
}
else {
	$venue = $meta['venue'];
}
?>

<p><?php echo $date; ?><br />
<em><?php echo $venue; ?></em></p>