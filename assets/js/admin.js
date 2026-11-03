/**
 * WP Testimonial Slider - admin scripts.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		if ( $.fn.wpColorPicker ) {
			$( '.wts-color-field' ).wpColorPicker();
		}
	} );
}( jQuery ) );
