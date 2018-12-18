<?php
/**
 * Load code specific to Gutenberg blocks which are not tied to a module.
 * This file is unusual, and is not an actual `module` as such.
 * It is included in ./module-extras.php
 *
 */

jetpack_register_block(
	'map',
	array(
		'render_callback' => 'jetpack_map_block_load_assets',
	)
);

jetpack_register_block( 'vr' );

/**
 * Map block registration/dependency declaration.
 *
 * @param array  $attr - Array containing the map block attributes.
 * @param string $content - String containing the map block content.
 *
 * @return string
 */
function jetpack_map_block_load_assets( $attr, $content ) {
	$dependencies = array(
		'lodash',
		'wp-element',
		'wp-i18n',
	);

	$api_key = Jetpack_Options::get_option( 'mapbox_api_key' );

	Jetpack_Gutenberg::load_assets_as_required( 'map', $dependencies );
	return preg_replace( '/<div /', '<div data-api-key="'. esc_attr( $api_key ) .'" ', $content, 1 );
}



function jetpack_business_hours_init() {
	jetpack_register_block(
		'business-hours',
		array( 'render_callback' => 'jetpack_business_hours_render' )
	);
}

function jetpack_business_hours_render( $attributes, $content ) {
	global $wp_locale;

	if ( empty( $attributes['hours'] ) || ! is_array( $attributes['hours'] ) ) {
		return $content;
	}

	$start_of_week = (int) get_option( 'start_of_week', 0 );
	$time_format = get_option( 'time_format' );
	$today = current_time( 'D' );
	$content = '<dl class="business-hours built-by-php">';

	$days = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );

	if ( $start_of_week ) {
		$chunk1 = array_slice( $attributes['hours'], 0, $start_of_week );
		$chunk2 = array_slice( $attributes['hours'], $start_of_week );
		$attributes['hours'] = array_merge( $chunk2, $chunk1 );
	}

	foreach ( $attributes['hours'] as $day => $hours ) {
		$opening = strtotime( $hours['opening'] );
		$closing = strtotime( $hours['closing'] );

		$content .= '<dt class="' . esc_attr( $day ) . '">' . $wp_locale->get_weekday( array_search( $day, $days ) ) . '</dt>';
		$content .= '<dd class="' . esc_attr( $day ) . '">';
		if ( $hours['opening'] && $hours['closing'] ) {
			$content .= date( $time_format, $opening );
			$content .= '&nbsp;&mdash;&nbsp;';
			$content .= date( $time_format, $closing );

			if ( $today === $day ) {
				$now = strtotime( current_time( 'H:i' ) );
				if ( $now < $opening ) {
					$content .= '<br />';
					$content .= esc_html( sprintf( __( 'Opening in %s', 'random-blocks' ), human_time_diff( $now, $opening ) ) );
				} elseif ( $now >= $opening && $now < $closing ) {
					$content .= '<br />';
					$content .= esc_html( sprintf( __( 'Closing in %s', 'random-blocks' ), human_time_diff( $now, $closing ) ) );
				}
			}
		} else {
			$content .= esc_html__( 'CLOSED', 'random-blocks' );
		}
		$content .= '</dd>';
	}

	$content .= '</dl>';

	return $content;
}
add_action( 'init', 'jetpack_business_hours_init' );
