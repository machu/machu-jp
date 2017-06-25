<?php
// PukiWiki - Yet another WikiWikiWeb clone
// Copyright (C) 2006 teanan / PukiWiki Developers Team
//
// よくある踊った文字を描画してみるテスト
// License: GPL v2 or (at your option) any later version

define('PLUGIN_HOPCHAR_FONT_NO',		20);
define('PLUGIN_HOPCHAR_CHAR_SIZE',		20);		// 1文字のサイズ
define('PLUGIN_HOPCHAR_MAX_NUMBER',		10000);		// 最大値 + 1

// 乱数テーブルのファイル名
define('PLUGIN_HOPCHAR_RANDOM_FILE', 'hopchar_rand.dat');
// 乱数の数 (この値を変更したら乱数表を更新すること)
define('PLUGIN_HOPCHAR_RANDOM_NUM',  100);

// 背景画像
define('PLUGIN_HOPCHAR_BGIMAGE', IMAGE_DIR . 'b_pukiwiki.dev.png');

function plugin_hopchar_action()
{
	global $vars, $post;
	global $_msg_invalidpass;

	$pcmd = isset($vars['pcmd'])? $vars['pcmd'] : '';

	$title = '乱数表更新';
	$error_message = '';

	switch ($pcmd) {
	case 'disp':		// 認証用画像の出力
		return plugin_hopchar_disp();

	case 'update':		// 乱数表の更新
		if (pkwk_login($post['adminpass'])) {
			plugin_hopchar_update_table();
			$body = '乱数表を更新しました。';
			return array('msg'=>$title, 'body'=>$body);
		} else {
			$error_message = $_msg_invalidpass;
		}
		break;
	}

	$script = get_script_uri();

	if ($error_message!='') {
		// エラーメッセージを強調する
		$error_message = <<<EOD
<div>
 <strong>$error_message</strong>
</div>
EOD;
	}

	// 乱数表を更新する
	$body = <<<EOD
<h2>$title</h2>
$error_message
<blockquote>
文字生成に使用する乱数表を更新します。<br />
管理者パスワードを入力し「更新」ボタンを押してください。
</blockquote>
<div>
<form method="POST" action="$script">
  <input type="hidden" name="plugin" value="hopchar" />
  <input type="hidden" name="pcmd" value="update" />
  <label for="_p_hopchar_adminpass">パスワード:</label>
  <input type="password" name="adminpass" id="_p_hopchar_adminpass" size="20" value="" />
  <input type="submit" value=" 更新 " />
</form>
</div>
EOD;
	return array('msg'=>$title, 'body'=>$body);
}

// 認証用画像を生成して出力する
function plugin_hopchar_disp()
{
	global $vars;

	$num  = isset($vars['n'])? $vars['n'] : 0;
	$sno  = isset($vars['s'])? $vars['s'] : 0;
	$hash = isset($vars['h'])? $vars['h'] : '';
	$salt = plugin_hopchar_get_salt($sno);

	// URL改ざん検出
	if ($hash != md5($num . $sno . $salt)) {
		// 改ざんされているので値を嘘にする
		$num  = rand(0, PLUGIN_HOPCHAR_MAX_NUMBER - 1);
		$salt = rand(0, PLUGIN_HOPCHAR_MAX_NUMBER - 1);
	}
	$num = (PLUGIN_HOPCHAR_MAX_NUMBER + $num - $salt) % PLUGIN_HOPCHAR_MAX_NUMBER;

	$text = sprintf('%04d', $num);
	$textlen = strlen($text);

	$baseim = imagecreatetruecolor ($textlen * PLUGIN_HOPCHAR_CHAR_SIZE * 15 / 10, PLUGIN_HOPCHAR_CHAR_SIZE * 2)
		or die ("Cannot Initialize new GD image stream");

	$bgcolor = ImageColorAllocate ($baseim, 255, 255, 255);
	imagefill($baseim, 0, 0, $bgcolor);

	imagecolortransparent($baseim, $bgcolor);

	$ymax = 0;
	$xx = 0;
	for($i = 0; $i < $textlen; $i++) {
		$chr = substr($text, $i, 1);
		$im = plugin_hopchar_get_char_image($chr, PLUGIN_HOPCHAR_CHAR_SIZE);
		$ix = imagesx($im);
		$iy = imagesy($im);
		$yy = rand(0,8);
		imagecopy($baseim, $im, $xx, $yy, 0, 0, $ix, $iy);
		imagedestroy($im);
		$xx += $ix;
		$btm = $yy + $iy;
		$ymax = ($ymax < $btm)? $btm : $ymax;
	}
	// 背景画像
	$outim = imagecreatefrompng(PLUGIN_HOPCHAR_BGIMAGE)
		or die ("Cannot Initialize new GD image stream");

	imagecopyresampled($outim, $baseim,
		0, 0,
		0, 0,
		imagesx($outim), imagesy($outim),
		$xx, $ymax
	);
	imagedestroy($baseim);

	header ("Content-type: image/png");
	imagepng($outim);
	imagedestroy($outim);
	exit;
}

// 曲がった一文字を作る
function plugin_hopchar_get_char_image($chr, $size)
{
	$fwidth  = imagefontwidth(PLUGIN_HOPCHAR_FONT_NO);
	$fheight = imagefontheight(PLUGIN_HOPCHAR_FONT_NO);

	$chrimg = @imagecreatetruecolor ($fwidth, $fheight)
	    or die ("Cannot Initialize new GD image stream");

	$bg_color = ImageColorAllocate ($chrimg, 255, 255, 255);
	imagefill($chrimg, 0, 0, $bg_color);
	imagecolortransparent($chrimg, $bg_color);

	$text_color = ImageColorAllocate ($chrimg, 0, 0, 0);
	imagechar($chrimg, PLUGIN_HOPCHAR_FONT_NO, 0, 0, $chr, $text_color);

	$retim = imagecreatetruecolor ($size, $size)
	    or die ("Cannot Initialize new GD image stream");

	imagecopyresampled($retim, $chrimg,
		0, 0,
		0, 0,
		$size, $size,
		$fwidth, $fheight
	);

	imagedestroy($chrimg);

	$degrees = rand(0, 120) - 60;
	$retim = imagerotate($retim, $degrees, $bg_color);

	// 色数を落とす
//	ImageTrueColorToPalette($retim, TRUE, 3);

	return $retim;
}

// 乱数の取得
function plugin_hopchar_get_salt($sno)
{
	$file = realpath(CACHE_DIR) . '/' . PLUGIN_HOPCHAR_RANDOM_FILE;

	// ファイルが存在しなかったら乱数表を生成する
	if (! file_exists($file))
		plugin_hopchar_update_table();

	$r_table = file($file);

	if (! isset($r_table[$sno]))
		die_message('Invalid random table.');

	return $r_table[$sno];
}

// 乱数表の更新
function plugin_hopchar_update_table()
{
	$file = realpath(CACHE_DIR) . '/' . PLUGIN_HOPCHAR_RANDOM_FILE;
	$fp = fopen($file, 'w')
		or die('hopchar.inc.php: Cannot create random table.');
	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);
	rewind($fp);
	for ($i=0; $i<PLUGIN_HOPCHAR_RANDOM_NUM; $i++)
		fputs($fp, rand(0, PLUGIN_HOPCHAR_MAX_NUMBER - 1) . "\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}

// 指定番号の画像リンクを生成する
function plugin_hopchar_get_img($num)
{
	$sno = rand(0, PLUGIN_HOPCHAR_RANDOM_NUM - 1);
	$salt = plugin_hopchar_get_salt($sno);

	$num = ($num + $salt) % PLUGIN_HOPCHAR_MAX_NUMBER;

	// URL改ざん検出用
	$hash = md5($num . $sno . $salt);

	$script = get_script_uri();
	return "<img src=\"$script?plugin=hopchar&pcmd=disp&n=$num&s=$sno&h=$hash\" />";
}

// 試験用
function plugin_hopchar_inline()
{
	// 適当に生成する
	$num = rand(0, PLUGIN_HOPCHAR_MAX_NUMBER - 1);
	return plugin_hopchar_get_img($num);
}
?>
