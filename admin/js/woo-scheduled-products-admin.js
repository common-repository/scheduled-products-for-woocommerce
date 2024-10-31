jQuery(document).ready(function($) {
  $( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', initScheduleFields );
  $( document.body ).on( 'woocommerce_variations_added', initScheduleFields );

  function initScheduleFields() {
    var datepickerArgs = {
      dateFormat: 'yy-mm-dd'
    };

    $( '.woo-scheduled-products-datepicker' ).datepicker( datepickerArgs );

    $( 'input.variable_is_scheduled' ).trigger( 'change' );
  }
  initScheduleFields();

  $(document).on('change', 'input.variable_is_scheduled', function() {
    var fieldsContainer = $( this ).closest( '.woocommerce_variable_attributes' );

    $( '.publish-fields', fieldsContainer ).toggle( $( this ).is( ':checked' ) );
  });
});
