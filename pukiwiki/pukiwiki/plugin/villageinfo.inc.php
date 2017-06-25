<?php

define('PLUGIN_VILLAGEINFO_FILE', '/var/www/wolfbbs.jp/var/jinroh/villageinfo.csv');
define('PLUGIN_VILLAGEINFO_SHORT_FILE', '/var/www/wolfbbs.jp/var/jinroh/villageinfo_s.csv');

function plugin_villageinfo_action()
{
	global $script, $vars, $get, $post, $menubar, $_msg_include_restrict;

	$file = PLUGIN_VILLAGEINFO_FILE;
	$body = convert_html(file($file));
	// return $body;
	return array(
		'msg'  => '勝敗結果 (全ての村)',
		'body' => $body
	);

}

function plugin_villageinfo_convert()
{
	global $script;

	// $foot = "<p><a href=\"$script?cmd=villageinfo\">(全ての村を表示)</a></p>";
	$file = PLUGIN_VILLAGEINFO_SHORT_FILE;
	// return convert_html(file($file)) . $foot;
	return convert_html(file($file));
}
?>
