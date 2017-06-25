// http://kawa.at.webry.info/200511/article_9.html
// for Safari
function get_response_text(req) {
  var text = req.responseText;
  if ( navigator.appVersion.indexOf( "KHTML" ) > -1 ) {
    var esc = escape( text );
    if ( esc.indexOf("%u") < 0 && esc.indexOf("%") > -1 ) {
      text = decodeURIComponent( esc );
    }
  }
  return text;
}


function reverse_link() {
  url = 'reverselink/reverselink.php?' + $F('pagename');
  options = {
    requestHeaders: ['If-Modified-Since', 'Thu, 01 Jun 2002 00:00:00 GMT'],
		onComplete: function(req) {
      $('related').innerHTML = get_response_text(req);
		}
	};
  new Ajax.Request(url, options);
	$('revlink_button').disabled = 'true';
}
