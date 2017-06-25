<?php
// PukiWiki - Yet another WikiWikiWeb clone
// Copyright (C) 2006 teanan / PukiWiki Developers Team
//
// �褯�����٤ä�ʸ�������褷�Ƥߤ�ƥ���
// License: GPL v2 or (at your option) any later version

define('PLUGIN_HOPCHAR_FONT_NO',		20);
define('PLUGIN_HOPCHAR_COLOR_RANGE',	100);		// ʸ����
define('PLUGIN_HOPCHAR_CHAR_SIZE',		20);		// 1ʸ���Υ�����
define('PLUGIN_HOPCHAR_MAX_NUMBER',		10000);		// ������ + 1

// ����ơ��֥�Υե�����̾
define('PLUGIN_HOPCHAR_RANDOM_FILE', 'hopchar_rand.dat');
// ����ο� (�����ͤ��ѹ����������ɽ�򹹿����뤳��)
define('PLUGIN_HOPCHAR_RANDOM_NUM',  100);

// �طʲ���
define('PLUGIN_HOPCHAR_BGIMAGE', IMAGE_DIR . 'captcha.png');

function plugin_hopchar_action()
{
	global $vars, $post;
	global $_msg_invalidpass;

	$pcmd = isset($vars['pcmd'])? $vars['pcmd'] : '';

	$title = '���ɽ����';
	$error_message = '';

	switch ($pcmd) {
	case 'disp':		// ǧ���Ѳ����ν���
		return plugin_hopchar_disp();

	case 'update':		// ���ɽ�ι���
		if (pkwk_login($post['adminpass'])) {
			plugin_hopchar_update_table();
			$body = '���ɽ�򹹿����ޤ�����';
			return array('msg'=>$title, 'body'=>$body);
		} else {
			$error_message = $_msg_invalidpass;
		}
		break;
	}

	$script = get_script_uri();

	if ($error_message!='') {
		// ���顼��å�������Ĵ����
		$error_message = <<<EOD
<div>
 <strong>$error_message</strong>
</div>
EOD;
	}

	// ���ɽ�򹹿�����
	$body = <<<EOD
<h2>$title</h2>
$error_message
<blockquote>
ʸ�������˻��Ѥ������ɽ�򹹿����ޤ���<br />
�����ԥѥ���ɤ����Ϥ��ֹ����ץܥ���򲡤��Ƥ���������
</blockquote>
<div>
<form method="POST" action="$script">
  <input type="hidden" name="plugin" value="hopchar" />
  <input type="hidden" name="pcmd" value="update" />
  <label for="_p_hopchar_adminpass">�ѥ����:</label>
  <input type="password" name="adminpass" id="_p_hopchar_adminpass" size="20" value="" />
  <input type="submit" value=" ���� " />
</form>
</div>
EOD;
	return array('msg'=>$title, 'body'=>$body);
}

// ǧ���Ѳ������������ƽ��Ϥ���
function plugin_hopchar_disp()
{
	global $vars;

	$num  = isset($vars['n'])? $vars['n'] : 0;
	$sno  = isset($vars['s'])? $vars['s'] : 0;
	$hash = isset($vars['h'])? $vars['h'] : '';
	$salt = plugin_hopchar_get_salt($sno);

	// URL�����󸡽�
	if ($hash != md5($num . $sno . $salt)) {
		// �����󤵤�Ƥ���Τ��ͤ򱳤ˤ���
		$num  = rand(0, PLUGIN_HOPCHAR_MAX_NUMBER - 1);
		$salt = rand(0, PLUGIN_HOPCHAR_MAX_NUMBER - 1);
	}
	$num = (PLUGIN_HOPCHAR_MAX_NUMBER + $num - $salt) % PLUGIN_HOPCHAR_MAX_NUMBER;

	$text = sprintf('%04d', $num);
	$textlen = strlen($text);

	$baseim = ImageCreateTruecolor ($textlen * PLUGIN_HOPCHAR_CHAR_SIZE * 15 / 10, PLUGIN_HOPCHAR_CHAR_SIZE * 2)
		or die ("Cannot Initialize new GD image stream");

	$bgcolor = ImageColorAllocate ($baseim, 255, 255, 255);
	ImageColorTransparent($baseim, $bgcolor);
	ImageFill($baseim, 0, 0, $bgcolor);


	$ymax = 0;
	$xx = 0;
	for($i = 0; $i < $textlen; $i++) {
		$chr = substr($text, $i, 1);
		$im = plugin_hopchar_get_char_image($chr, PLUGIN_HOPCHAR_CHAR_SIZE);
		$ix = ImageSX($im);
		$iy = ImageSY($im);
		$yy = rand(0,8);
		ImageCopy($baseim, $im, $xx, $yy, 0, 0, $ix, $iy);
		ImageDestroy($im);
		$xx += $ix;
		$btm = $yy + $iy;
		$ymax = ($ymax < $btm)? $btm : $ymax;
	}

	// �طʲ���
	$outim = ImageCreateFromPng(PLUGIN_HOPCHAR_BGIMAGE)
		or die ("Cannot Initialize new GD image stream");

	ImageCopyReSized($outim, $baseim,
		0, 0,
		0, 0,
		ImageSX($outim), ImageSY($outim),
		$xx, $ymax
	);
	ImageDestroy($baseim);

	header ("Content-type: image/png");
	ImagePng($outim);
	ImageDestroy($outim);
	exit;
}

// �ʤ��ä���ʸ������
function plugin_hopchar_get_char_image($chr, $size)
{
	$fwidth  = ImageFontWidth(PLUGIN_HOPCHAR_FONT_NO);
	$fheight = ImageFontHeight(PLUGIN_HOPCHAR_FONT_NO);

	// ʸ�������ѥ��᡼��
	$chrimg = ImageCreateTruecolor ($fwidth, $fheight)
	    or die ("Cannot Initialize new GD image stream");

	// �ط��ɤ�Ĥ֤�
	$bg_color = ImageColorAllocate ($chrimg, 255, 255, 255);
	ImageFill($chrimg, 0, 0, $bg_color);

	// ʸ���ο������(�Ť�ˤ��Ƥ���)
	if(PLUGIN_HOPCHAR_COLOR_RANGE > 0) {
		$text_r = rand(0, PLUGIN_HOPCHAR_COLOR_RANGE);
		$text_g = rand(0, PLUGIN_HOPCHAR_COLOR_RANGE);
		$text_b = rand(0, PLUGIN_HOPCHAR_COLOR_RANGE);
	} else {
		$text_r = 0;
		$text_g = 0;
		$text_b = 0;
	}
	$text_color = ImageColorAllocate ($chrimg, $text_r, $text_g, $text_b);

	// �ƥ����Ȥ��ɲä���
	$degrees = rand(0, 120) - 60;
	ImageChar($chrimg, PLUGIN_HOPCHAR_FONT_NO, 0, 0, $chr, $text_color);

	$retim = ImageCreateTruecolor ($size, $size)
	    or die ("Cannot Initialize new GD image stream");

	// ʸ������ꤵ�줿�������˿�ĥ����
	ImageCopyResized($retim, $chrimg,
		0, 0,
		0, 0,
		$size, $size,
		$fwidth, $fheight
	);
	ImageDestroy($chrimg);

	// ʸ�����ž������
	$degrees = rand(0, 120) - 60;
	$retim = ImageRotate($retim, $degrees, $bg_color);

	$bg_color = ImageColorAllocate ($retim, 255, 255, 255);
	ImageColorTransparent($retim, $bg_color);

	// ��������Ȥ�
//	ImageTrueColorToPalette($retim, FALSE, 2);

	return $retim;
}

// ����μ���
function plugin_hopchar_get_salt($sno)
{
	$file = realpath(CACHE_DIR) . '/' . PLUGIN_HOPCHAR_RANDOM_FILE;

	// �ե����뤬¸�ߤ��ʤ��ä������ɽ����������
	if (! file_exists($file))
		plugin_hopchar_update_table();

	$r_table = file($file);

	if (! isset($r_table[$sno]))
		die_message('Invalid random table.');

	return $r_table[$sno];
}

// ���ɽ�ι���
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

// �����ֹ�β�����󥯤���������
function plugin_hopchar_get_img($num)
{
	$sno = rand(0, PLUGIN_HOPCHAR_RANDOM_NUM - 1);
	$salt = plugin_hopchar_get_salt($sno);

	$num = ($num + $salt) % PLUGIN_HOPCHAR_MAX_NUMBER;

	// URL�����󸡽���
	$hash = md5($num . $sno . $salt);

	$script = get_script_uri();
	return "<img src=\"$script?plugin=hopchar&pcmd=disp&n=$num&s=$sno&h=$hash\" />";
}

// ���
function plugin_hopchar_inline()
{
	// Ŭ������������
	$num = rand(0, PLUGIN_HOPCHAR_MAX_NUMBER - 1);
	return plugin_hopchar_get_img($num);
}
?>
