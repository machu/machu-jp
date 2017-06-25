<?php
define('PLUGIN_USERINFO_DIR', '/var/www/wolfbbs.jp/var/jinroh/userinfo');

function plugin_userinfo_convert()
{
	global $vars;

  $page = $vars['page'];
	$file = PLUGIN_USERINFO_DIR . '/' . md5($vars['page']) . '.txt';
  #$body = '<div style="text-align: right; font-size: 0.8em">';
  #$body .= '<a href="http://static.wolfbbs.jp/userinfo/?wikiname=' . rawurlencode($page) . '">[¿Ô¿”§Ú ‘Ω∏§π§Î]</a>';
  #$body .= '</div>';
  $body .= convert_html(file($file));
	return $body;
}
?>
