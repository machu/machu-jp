<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: pukiwiki.php,v 1.11 2005/09/11 05:58:33 henoheno Exp $
//
// PukiWiki 1.4.*
//  Copyright (C) 2002-2005 by PukiWiki Developers Team
//  http://pukiwiki.sourceforge.jp/
//
// PukiWiki 1.3.*
//  Copyright (C) 2002-2004 by PukiWiki Developers Team
//  http://pukiwiki.sourceforge.jp/
//
// PukiWiki 1.3 (Base)
//  Copyright (C) 2001-2002 by yu-ji <sng@factage.com>
//  http://factage.com/sng/pukiwiki/
//
// Special thanks
//  YukiWiki by Hiroshi Yuki <hyuki@hyuki.com>
//  http://www.hyuki.com/yukiwiki/
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

if (! defined('DATA_HOME')) define('DATA_HOME', '');

/////////////////////////////////////////////////
// Include subroutines

if (! defined('LIB_DIR')) define('LIB_DIR', '');

require(LIB_DIR . 'func.php');
require(LIB_DIR . 'file.php');
require(LIB_DIR . 'plugin.php');
require(LIB_DIR . 'html.php');
require(LIB_DIR . 'backup.php');

require(LIB_DIR . 'convert_html.php');
require(LIB_DIR . 'make_link.php');
require(LIB_DIR . 'diff.php');
require(LIB_DIR . 'config.php');
require(LIB_DIR . 'link.php');
require(LIB_DIR . 'auth.php');
require(LIB_DIR . 'proxy.php');
if (! extension_loaded('mbstring')) {
	require(LIB_DIR . 'mbstring.php');
}

// Defaults
$notify = $trackback = $referer = 0;

// Load *.ini.php files and init PukiWiki
require(LIB_DIR . 'init.php');

// Load optional libraries
if ($notify) {
	require(LIB_DIR . 'mail.php'); // Mail notification
}
if ($trackback || $referer) {
	// Referer functionality uses trackback functions
	// without functional reason now
	require(LIB_DIR . 'trackback.php'); // TrackBack
}

/////////////////////////////////////////////////
// Main

$retvars = array();
$is_cmd = FALSE;
if (isset($vars['cmd'])) {
	$is_cmd  = TRUE;
	$plugin = & $vars['cmd'];
} else if (isset($vars['plugin'])) {
	$plugin = & $vars['plugin'];
} else {
	$plugin = '';
}

//die_message('test');
// Spam filtering
// http://pukiwiki.sourceforge.jp/dev/?PukiWiki%2F1.4%2F%A4%C1%A4%E7%A4%C3%A4%C8%CA%D8%CD%F8%A4%CB%2FAkismet%A4%CB%A4%E8%A4%EBspam%28%A5%B9%A5%D1%A5%E0%29%CB%C9%BB%DF%B5%A1%C7%BD
/*
if (($plugin == 'comment' || $plugin == 'pcomment') && isset($vars['msg']) && $vars['msg'] != '' && $method != 'GET') {
	// SPAM chek by akismet
	require_once(LIB_DIR . 'akismet.class.php');
	// load array with comment data
	$comment = array(
		'author'       => isset($vars['name']) ? $vars['name'] : '',
		'email'        => '',
		'website'      => '',
		'body'         => $vars['msg'],
		'permalink'    => '',
		'user_ip'      => $_SERVER['REMOTE_ADDR'],
		'user_agent'   => $_SERVER['HTTP_USER_AGENT'],
	);
	// instantiate an instance of the class
	$akismet = new Akismet(get_script_uri(), 'd6345b43f4cf', $comment);

	// test for errors
	if($akismet->errorsExist()) { // returns true if any errors exist
		if($akismet->isError('AKISMET_INVALID_KEY')) {
			die_message('akismet : APIキーが不正です.');
		} elseif($akismet->isError('AKISMET_RESPONSE_FAILED')) {
			die_message('akismet : レスポンスの取得に失敗しました');
		} elseif($akismet->isError('AKISMET_SERVER_NOT_FOUND')) {
			die_message('akismet : サーバへの接続に失敗しました.');
		}
	} elseif ($akismet->isSpam()) { // returns true if Akismet thinks the comment is spam
  die_message('投稿はスパムと判断されました.' . $vars['page']);
  }
}
 */

// Plugin execution
if ($plugin != '') {
	if (exist_plugin_action($plugin)) {
		// Found and exec
		$retvars = do_plugin_action($plugin);
		if ($retvars === FALSE) exit; // Done

		if ($is_cmd) {
			$base = isset($vars['page'])  ? $vars['page']  : '';
		} else {
			$base = isset($vars['refer']) ? $vars['refer'] : '';
		}
	} else {
		// Not found
		$msg = 'plugin=' . htmlsc($plugin) .
			' is not implemented.';
		$retvars = array('msg'=>$msg,'body'=>$msg);
		$base    = & $defaultpage;
	}
}

$title = htmlsc(strip_bracket($base));
$page  = make_search($base);
if (isset($retvars['msg']) && $retvars['msg'] != '') {
	$title = str_replace('$1', $title, $retvars['msg']);
	$page  = str_replace('$1', $page,  $retvars['msg']);
}

if (isset($retvars['body']) && $retvars['body'] != '') {
	$body = & $retvars['body'];
} else {
	if ($base == '' || ! is_page($base)) {
		$base  = & $defaultpage;
		$title = htmlsc(strip_bracket($base));
		$page  = make_search($base);
	}

	$vars['cmd']  = 'read';
	$vars['page'] = & $base;

	$body  = convert_html(get_source($base));

	if ($trackback) $body .= tb_get_rdf($base); // Add TrackBack-Ping URI
	if ($referer) ref_save($base);
}

// Output
catbody($title, $page, $body);
exit;
?>
