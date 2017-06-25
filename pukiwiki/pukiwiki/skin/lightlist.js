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

var PukiWikiList = Class.create();
PukiWikiList.prototype = {
  initialize: function(input, list, script, options) {
		options = options || {}
    this.input = $(input);
    this.list  = $(list);
    this.script = script;
		this.frequency = (options.frequency || 0.5);
		this.input_min = (options.input_min || 2);

		this.cache = $H();
		this.lastValue = '';
    Event.observe(this.input, 'keyup', this.timerStart.bindAsEventListener(this), false);
		// keypress keyup change
  },

	search: function() {
		word = this.input.value;
		cache = this.cache;
		if(word == '' || word.length < this.input_min) {
			return;
		}
		this.lastValue = word;

		results = null;
		cache.keys().each(function(key) {
			if(word.indexOf(key) == 0) {
				results = cache[key];
				return;
			}
		});
		if(results) {
			this.showList(word, results);
		} else {
			// url = '/?cmd=lightlist2'
			url = this.script;
			// url = '/';
			options = {
				requestHeaders: ['If-Modified-Since', 'Thu, 01 Jun 2002 00:00:00 GMT'],
				parameters: 'word=' + encodeURIComponent(word),
				// parameters: '?cmd=lightlist2&word=' + encodeURIComponent(word),
				onSuccess: this.complete.bindAsEventListener(this)
			}
			new Ajax.Request(url, options);
		}
	},

	complete: function(req) {
    results = eval(get_response_text(req));
    this.cache[word] = results;
    this.showList(word, results);
  },

  showList: function(word, results) {
    // $('debug').innerHTML += 'a' + word.inspect();
		output = '<ul>';
		output += results.map(function(result) {
			if(result.name.indexOf(word) == 0) {
				return '<li><a href="' + result.url + '">' +
					result.name.escapeHTML() + '</a></li>';
			}
		}).join("\n");
		output += '</ul>';
		this.list.innerHTML = output;
	},

  timerStart: function(event) {
		if(this.input.value == this.lastValue) {
			// this.list.innerHTML += 'do nothing <br/>';
			return;
		}
    // this.list.innerHTML = event.keyCode;
		if(this.timerId) {
			// this.list.innerHTML += 'clear' + this.timerId + '<br/>';
			clearTimeout(this.timerId);
			this.timerId = 0;
		}
		this.timerId = setTimeout(this.timerEnd.bind(this), this.frequency * 1000);
    // this.list.innerHTML += this.input.value + '<br/>';
		// this.list.innerHTML += 'start ' + this.timerId + '<br/>';
  },

	timerEnd: function() {
		// this.list.innerHTML += 'stop<br/>';
		this.timerId = 0;
		this.search();
	}
}

/*
Event.observe(window, 'load', function(){
  new PukiWikiList('word', 'lightlist', './?plugin=lightlist2');
  // new PukiWikiListWords('debug');
})
*/
