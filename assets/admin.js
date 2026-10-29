/* global jQuery, wp, offlineModeAdmin */
( function ( $ ) {
	$( function () {
		$( '.om-color' ).wpColorPicker();

		$( '.om-image-field' ).each( function () {
			var $field = $( this );
			var $input = $field.find( 'input[type="hidden"]' );
			var $preview = $field.find( '.om-image-preview' );
			var $remove = $field.find( '.om-image-remove' );
			var frame;

			$field.find( '.om-image-select' ).on( 'click', function () {
				if ( ! frame ) {
					frame = wp.media( {
						title: offlineModeAdmin.chooseImage,
						button: { text: offlineModeAdmin.useImage },
						library: { type: 'image' },
						multiple: false
					} );
					frame.on( 'select', function () {
						var image = frame.state().get( 'selection' ).first().toJSON();
						var url = image.sizes && image.sizes.medium ? image.sizes.medium.url : image.url;
						$input.val( image.id );
						$preview.empty().append( $( '<img alt="">' ).attr( 'src', url ) );
						$remove.prop( 'hidden', false );
					} );
				}
				frame.open();
			} );

			$remove.on( 'click', function () {
				$input.val( '0' );
				$preview.empty();
				$remove.prop( 'hidden', true );
			} );
		} );
	} );
}( jQuery ) );
