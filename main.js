(function($) {

  var getOrdersBtn = $('#get_order_list');

  getOrdersBtn.on('click', function() {
    window.location = window.location.href + '&download=true'
  });

})(jQuery)