/**
*
*   WP Category Thumbnail
*
**/
jQuery( function() {
  jQuery( '.wpct-box' ).css( 'top', '0' );
  jQuery( '.wpct-wrap' ).hover( function() {
    jQuery( '.wpct-box' ).clearQueue();
    var img_height = jQuery( '.wpct-wrap' ).innerHeight();
    jQuery( this ).find( '.wpct-box' ).animate( {
      top : -img_height,
    } );
  }, function() {
    jQuery( '.wpct-box' ).clearQueue();
    jQuery( this ).find( '.wpct-box' ).animate( {
      top : "0px",
    } );
  } );
} );
