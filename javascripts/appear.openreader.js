$(function() {
  var $appeared = $('#appeared');
  var $disappeared = $('#disappeared');

  $('section h3').appear();
  $('#force').on('click', function() {
    $.force_appear();
  });

  $(document.body).on('appear', 'section h3', function(e, $affected) {
    // this code is executed for each appeared element
    //$(this).yellowFade();
    var id = $(this).attr('id');
    console.log("appeared item: " + id);

    $appeared.empty();
    $affected.each(function() {
      $appeared.append(this.innerHTML+"\n");
    })
  });

  $(document.body).on('disappear', 'section h3', function(e, $affected) {
    // this code is executed for each disappeared element

    var id = $(this).attr('id');
    console.log("disappeared item: " + id);

    $.ajax(
     {
      type: "POST",
      url: "json.php",
      data: JSON.stringify({ "jsonrpc": "2.0","update": "read-status", "value": id }),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      success: function(data){},
      failure: function(errMsg) {}
     }
    );

    $disappeared.empty();
    $affected.each(function() {
      $disappeared.append(this.innerHTML+"\n");
    })
  });

});
