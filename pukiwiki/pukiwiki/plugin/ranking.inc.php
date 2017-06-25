<?php

define('PLUGIN_RANKING_FILE', '/var/www/wolfbbs.jp/var/jinroh/ranking.csv');

function plugin_ranking_convert()
{
	$file = PLUGIN_RANKING_FILE;
	return convert_html(file($file));
}

?>
