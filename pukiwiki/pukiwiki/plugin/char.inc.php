<?php
require_once(PLUGIN_DIR . 'ref.inc.php');
//define('PLUGIN_CHAR_IMAGE', 'http://static.wolfbbs.jp/img/char/');
//define('PLUGIN_CHAR_OLD_IMAGE', 'http://static.wolfbbs.jp/img/char_old/');
#define('PLUGIN_CHAR_IMAGE', 'http://ninjin002.x0.com/wolff/plugin_wolf/img/');
#define('PLUGIN_CHAR_OLD_IMAGE', 'http://ninjin001.x0.com/wolfe/plugin_wolf/img/');
define('PLUGIN_CHAR_IMAGE', 'http://ninjinix.x0.com/wolf/plugin_wolf/img/');
define('PLUGIN_CHAR_OLD_IMAGE', 'http://ninjinix.x0.com/wolfe/plugin_wolf/img/');

function array_remval($val, &$arr)
{
  $array_remval = $arr;
  for($x=0;$x<count($array_remval);$x++)
  {
    $i=array_search($val,$array_remval);
    if (is_numeric($i)) {
      $array_temp  = array_slice($array_remval, 0, $i );
      $array_temp2 = array_slice($array_remval, $i+1, count($array_remval)-1 );
      $array_remval = array_merge($array_temp, $array_temp2);
    }
  }
  return $array_remval;
}

function plugin_char_args($args)
{
  $char_names = array('ゲルト', 'ヴァルター', 'モーリッツ', 'ジムゾン', 'トーマス', 'ニコラス', 'ディーター', 'ペーター', 'リーザ', 'アルビン', 'カタリナ', 'オットー', 'ヨアヒム', 'パメラ', 'ヤコブ', 'レジーナ', 'フリーデル', 'エルナ', 'クララ', 'シモン');
  $ability_names = array('村人', '人狼', '占い師', '霊能者', '狂人', '狩人', '共有者', 'ハムスター人間');

  $name = $args[0];
  if (in_array($name, $char_names)) {
    $dir = in_array('old', $args) ? PLUGIN_CHAR_OLD_IMAGE : PLUGIN_CHAR_IMAGE;
    $args = array_remval('old', $args);
    $id = array_search($name, $char_names) + 1;
    $file = in_array('face', $args) ? 'face' : 'body';
    $args = array_remval('face', $args);
    $args[0] = $dir . $file . sprintf("%02d", $id) . '.jpg';
  }
  else if (in_array($name, $ability_names)) {
    $dir = PLUGIN_CHAR_OLD_IMAGE;
    $id = array_search($name, $ability_names);
    $args[0] = $dir . 'skill' . sprintf("%02d", $id) . '.jpg';
  }
  array_push($args, 'nolink');
  array_push($args, $name);
  return $args;
}

function plugin_char_inline()
{
  $args = plugin_char_args(func_get_args());
	$params = plugin_ref_body($args);

	if (isset($params['_error']) && $params['_error'] != '') {
		// Error
		return '&amp;ref(): ' . $params['_error'] . ';';
	} else {
		return $params['_body'];
	}
}

function plugin_char_convert()
{
	if (! func_num_args())
		return '<p>#ref(): Usage:' . PLUGIN_REF_USAGE . "</p>\n";

  $args = plugin_char_args(func_get_args());
	$params = plugin_ref_body($args);

	if (isset($params['_error']) && $params['_error'] != '') {
		return "<p>#ref(): {$params['_error']}</p>\n";
	}

	if ((PLUGIN_REF_WRAP_TABLE && ! $params['nowrap']) || $params['wrap']) {
		// 枠で包む
		// margin:auto
		//	Mozilla 1.x  = x (wrap,aroundが効かない)
		//	Opera 6      = o
		//	Netscape 6   = x (wrap,aroundが効かない)
		//	IE 6         = x (wrap,aroundが効かない)
		// margin:0px
		//	Mozilla 1.x  = x (wrapで寄せが効かない)
		//	Opera 6      = x (wrapで寄せが効かない)
		//	Netscape 6   = x (wrapで寄せが効かない)
		//	IE6          = o
		$margin = ($params['around'] ? '0px' : 'auto');
		$margin_align = ($params['_align'] == 'center') ? '' : ";margin-{$params['_align']}:0px";
		$params['_body'] = <<<EOD
<table class="style_table" style="margin:$margin$margin_align">
 <tr>
  <td class="style_td">{$params['_body']}</td>
 </tr>
</table>
EOD;
	}

	if ($params['around']) {
		$style = ($params['_align'] == 'right') ? 'float:right' : 'float:left';
	} else {
		$style = "text-align:{$params['_align']}";
	}

	// divで包む
	return "<div class=\"img_margin\" style=\"$style\">{$params['_body']}</div>\n";
}

