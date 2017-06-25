<?php

function plugin_lightlist_action()
{
  global $vars;

  // if(isset($_SERVER['X-Requested-With']) && $_SERVER['X-Requested-With'] == 'XMLHttpRequest') {
  if(isset($vars['word'])) {  // FIXME
    plugin_lightlist_xmlhttp_action($vars['word']);
    exit;
  }
  $body = <<<EOD
  <p>ページ名の一部を入力してください。大文字小文字の違いも区別されます。</p>
  <div id="debug"></div>
  <form onsubmit="return false;">
    <input id="lightlist_word" type="text" name="word" value="" autocomplete="off" size="30" />
  </form>
  </p>
  <div id="lightlist"></div>
  <script type="text/javascript" src="skin/prototype.js"></script>
  <script type="text/javascript" src="skin/lightlist.js"></script>
  <script type="text/javascript">
  <!--
    Event.observe(window, 'load', function(){
      p = new PukiWikiList('lightlist_word', 'lightlist', '$script?plugin=lightlist');
    });
  // -->
  </script>
EOD;
  return array('msg' => 'ページ一覧', 'body' => $body);
}

function plugin_lightlist_xmlhttp_action($word)
{
  header('Content-type: text/javascript');

  $list = array();
  foreach (glob(DATA_DIR . '*' . encode($word) . '*.txt') as $filename) {
    $name = decode(basename($filename, '.txt'));
    # $url  = rawurlencode($name) . '.html';
    $url  = $script . '?' . rawurlencode($name);
    $line = "{'name' : '$name', 'url' : '$url'}";
    array_push($list, mb_convert_encoding($line, 'UTF-8', 'EUC-JP'));
  }
  print "([\n";
  print implode(",\n", $list) . "\n";
  print "])\n";
}
