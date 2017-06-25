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

var Amazon = Class.create();
Amazon.prototype = {
  initialize: function(list, url, type) {
    this.list  = $(list);
		this.type = type;
    this.url  = url;

    output = '<div id="amazon_header">';
    // output += '<div id="amazon_header_left"><span id="amazon_prev" title="前へ">&lt;&lt;</span></div>';
    output += '<div id="amazon_header_left"><img id="amazon_prev" title="前へ"alt="前へ" src="image/left.gif" /></div>';
    // output += '<div id="amazon_header_right"><span id="amazon_next" title="次へ">&gt;&gt;</span></div>';
    output += '<div id="amazon_header_right"><img id="amazon_next" title="次へ"alt="次へ" src="image/right.gif" /></div>';
    output += '<div id="amazon_header_ranking"></div>';
    output += '</div>';
    output += '<div id="amazon_body"><div id="amazon_image">image</div>';
    output += '<div id="amazon_info"><span id="amazon_name"></span><span id="amazon_author">author</span></div>';
    output += '<div id="amazon_powered"><a href="http://www.amazon.co.jp/exec/obidos/redirect?link_code=ur2&tag=wolfbbshalfmo-22&camp=247&creative=1211&path=http%3A%2F%2Fwww.amazon.co.jp"><span id="amazon_powered">powered by amazon</span></a></div>';
    this.list.innerHTML = output;

    $('amazon_prev').style.cursor = 'pointer';
    Event.observe($('amazon_prev'), 'click', this.showPrev.bindAsEventListener(this), false);
    $('amazon_next').style.cursor = 'pointer';
    Event.observe($('amazon_next'), 'click', this.showNext.bindAsEventListener(this), false);

    this.getData();
  },

  getData: function() {
    options = {
      method: 'get',
      requestHeaders: ['If-Modified-Since', 'Thu, 01 Jun 2002 00:00:00 GMT'],
      onSuccess: this.complete.bindAsEventListener(this)
    }
    new Ajax.Request(this.url, options);
  },

	complete: function(req) {
    all_items = $H(eval(get_response_text(req)));
    group_id = Math.floor(Math.random() * all_items.keys().length);
    this.group = all_items.keys()[group_id];
    this.items = all_items[this.group].reverse();
    this.showRandom();
	},

  show: function() {
    var item = this.items[this.index];
    ranking  = this.group + 'ランキング<br>' + (this.index + 1) + '位<br />';
    $('amazon_header_ranking').innerHTML = ranking;
    image  = '<a href="' + item.url + '" target="_blank">';
    image += '<img src="' + item.image + '" border="0">';
    image += '</a>';
    $('amazon_image').innerHTML = image;
    name  = '<a href="' + item.url + '" target="_blank">';
    name += item.name + '</a><br />';
    $('amazon_name').innerHTML = name;  
    author  = '<a href="' + item.url + '" target="_blank">';
    author += item.author ? item.author : item.manufacturer;
    author += '</a>';
    $('amazon_author').innerHTML = author;  
  },

  showPrev: function() {
    if(this.index < 1) {
      this.index += this.items.length;
    }
    this.index -= 1;
    this.show();
  },
  
  showNext: function() {
    this.index = (this.index + 1) % this.items.length;
    this.show();
  },

  showRandom: function() {
    this.index = Math.floor(Math.random() * this.items.length);
    this.show();
  }
};
