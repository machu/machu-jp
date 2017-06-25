<?php

define('PLUGIN_CHARINFO_FILE', '/var/www/wolfbbs.jp/var/jinroh/charinfo.csv');

function plugin_charinfo_convert()
{
	global $script, $vars, $get, $post, $menubar, $_msg_include_restrict;

	$file = PLUGIN_CHARINFO_FILE;
	$body = convert_html(file($file));
	return $body;
}
?>
