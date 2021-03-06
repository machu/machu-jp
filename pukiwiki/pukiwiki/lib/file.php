<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: file.php,v 1.46 2006/01/12 01:00:51 teanan Exp $
// Copyright (C)
//   2002-2005 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// File related functions

// Get source(wiki text) data of the page
function get_source($page = NULL, $lock = TRUE)
{
	$array = array();

	if (is_page($page)) {
		$path  = get_filename($page);

		if ($lock) {
			$fp = @fopen($path, 'r');
			if ($fp == FALSE) return $array;
			flock($fp, LOCK_SH);
		}

		// Removing line-feeds: Because file() doesn't remove them.
		$array = str_replace("\r", '', file($path));

		if ($lock) {
			flock($fp, LOCK_UN);
			@fclose($fp);
		}
	}

	return $array;
}

// Get last-modified filetime of the page
function get_filetime($page)
{
	return is_page($page) ? filemtime(get_filename($page)) - LOCALZONE : 0;
}

// Get physical file name of the page
function get_filename($page)
{
	return DATA_DIR . encode($page) . '.txt';
}

// Put a data(wiki text) into a physical file(diff, backup, text)
function page_write($page, $postdata, $notimestamp = FALSE)
{
	global $trackback;

	if (PKWK_READONLY) return; // Do nothing

	$postdata = make_str_rules($postdata);

	// Create and write diff
	$oldpostdata = is_page($page) ? join('', get_source($page)) : '';
	$diffdata    = do_diff($oldpostdata, $postdata);
	file_write(DIFF_DIR, $page, $diffdata);

	// Create backup
	make_backup($page, $postdata == ''); // Is $postdata null?

	// Create wiki text
	file_write(DATA_DIR, $page, $postdata, $notimestamp);

	if ($trackback) {
		// TrackBack Ping
		$_diff = explode("\n", $diffdata);
		$plus  = join("\n", preg_replace('/^\+/', '', preg_grep('/^\+/', $_diff)));
		$minus = join("\n", preg_replace('/^-/',  '', preg_grep('/^-/',  $_diff)));
		tb_send($page, $plus, $minus);
	}

	links_update($page);
}

// Modify original text with user-defined / system-defined rules
function make_str_rules($source)
{
	global $str_rules, $fixed_heading_anchor;

	$lines = explode("\n", $source);
	$count = count($lines);

	$modify    = TRUE;
	$multiline = 0;
	$matches   = array();
	for ($i = 0; $i < $count; $i++) {
		$line = & $lines[$i]; // Modify directly

		// Ignore null string and preformatted texts
		if ($line == '' || $line{0} == ' ' || $line{0} == "\t") continue;

		// Modify this line?
		if ($modify) {
			if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
			    $multiline == 0 &&
			    preg_match('/#[^{]*(\{\{+)\s*$/', $line, $matches)) {
			    	// Multiline convert plugin start
				$modify    = FALSE;
				$multiline = strlen($matches[1]); // Set specific number
			}
		} else {
			if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
			    $multiline != 0 &&
			    preg_match('/^\}{' . $multiline . '}\s*$/', $line)) {
			    	// Multiline convert plugin end
				$modify    = TRUE;
				$multiline = 0;
			}
		}
		if ($modify === FALSE) continue;

		// Replace with $str_rules
		foreach ($str_rules as $pattern => $replacement)
			$line = preg_replace('/' . $pattern . '/', $replacement, $line);
		
		// Adding fixed anchor into headings
		if ($fixed_heading_anchor &&
		    preg_match('/^(\*{1,3}.*?)(?:\[#([A-Za-z][\w-]*)\]\s*)?$/', $line, $matches) &&
		    (! isset($matches[2]) || $matches[2] == '')) {
			// Generate unique id
			$anchor = generate_fixed_heading_anchor_id($matches[1]);
			$line = rtrim($matches[1]) . ' [#' . $anchor . ']';
		}
	}

	// Multiline part has no stopper
	if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
	    $modify === FALSE && $multiline != 0)
		$lines[] = str_repeat('}', $multiline);

	return implode("\n", $lines);
}

// Generate ID
function generate_fixed_heading_anchor_id($seed)
{
	// A random alphabetic letter + 7 letters of random strings from md()
	return chr(mt_rand(ord('a'), ord('z'))) .
		substr(md5(uniqid(substr($seed, 0, 100), TRUE)),
		mt_rand(0, 24), 7);
}

// Output to a file
function file_write($dir, $page, $str, $notimestamp = FALSE)
{
	global $update_exec, $_msg_invalidiwn, $notify, $notify_diff_only, $notify_subject;
	global $whatsdeleted, $maxshow_deleted;

	if (PKWK_READONLY) return; // Do nothing

	if (! is_pagename($page))
		die_message(str_replace('$1', htmlsc($page),
		            str_replace('$2', 'WikiName', $_msg_invalidiwn)));

	$page      = strip_bracket($page);
	$file      = $dir . encode($page) . '.txt';
	$timestamp = FALSE;

	if ($str === '') {
		if ($dir == DATA_DIR && file_exists($file)) {
			// File deletion
			unlink($file);
			add_recent($page, $whatsdeleted, '', $maxshow_deleted); // RecentDeleted
		}
	} else {
		// File replacement (Edit)
		$str = rtrim(preg_replace('/' . "\r" . '/', '', $str)) . "\n";

		if ($notimestamp && file_exists($file))
			$timestamp = filemtime($file) - LOCALZONE;

		$fp = fopen($file, 'a') or die('fopen() failed: ' .
			htmlsc(basename($dir) . '/' . encode($page) . '.txt') .	
			'<br />' . "\n" .
			'Maybe permission is not writable or filename is too long');
		set_file_buffer($fp, 0);

		flock($fp, LOCK_EX);

		// Write
		ftruncate($fp, 0);
		rewind($fp);
		fputs($fp, $str);

		flock($fp, LOCK_UN);

		fclose($fp);

		if ($timestamp) pkwk_touch_file($file, $timestamp + LOCALZONE);
	}

	// Clear is_page() cache
	is_page($page, TRUE);

	if (! $timestamp && $dir == DATA_DIR)
		put_lastmodified($page);

	// Execute $update_exec here
	if ($update_exec && $dir == DATA_DIR)
		system($update_exec . ' > /dev/null &');

	if ($notify && $dir == DIFF_DIR) {
		if ($notify_diff_only) $str = preg_replace('/^[^-+].*\n/m', '', $str);

		$footer['ACTION'] = 'Page update';
		$footer['PAGE']   = & $page;
		$footer['URI']    = get_script_uri() . '?' . rawurlencode($page);
		$footer['USER_AGENT']  = TRUE;
		$footer['REMOTE_ADDR'] = TRUE;

		pkwk_mail_notify($notify_subject, $str, $footer) or
			die('pkwk_mail_notify(): Failed');
	}
}

// Update RecentDeleted
function add_recent($page, $recentpage, $subject = '', $limit = 0)
{
	if (PKWK_READONLY || $limit == 0 || $page == '' || $recentpage == '' ||
	    check_non_list($page)) return;

	// Load
	$lines = $matches = array();
	foreach (get_source($recentpage) as $line)
		if (preg_match('/^-(.+) - (\[\[.+\]\])$/', $line, $matches))
			$lines[$matches[2]] = $line;

	$_page = '[[' . $page . ']]';

	// Remove a report about the same page
	if (isset($lines[$_page])) unset($lines[$_page]);

	// Add
	array_unshift($lines, '-' . format_date(UTIME) . ' - ' . $_page .
		htmlsc($subject) . "\n");

	// Get latest $limit reports
	$lines = array_splice($lines, 0, $limit);

	// Update
	$fp = fopen(get_filename($recentpage), 'w') or
		die_message('Cannot write page file ' .
		htmlsc($recentpage) .
		'<br />Maybe permission is not writable or filename is too long');
	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);
	rewind($fp);
	fputs($fp, '#freeze'    . "\n");
	fputs($fp, '#norelated' . "\n"); // :)
	fputs($fp, join('', $lines));
	flock($fp, LOCK_UN);
	fclose($fp);
}

// Update RecentChanges
function put_lastmodified($editPage)
{
	global $maxshow, $whatsnew, $autolink, $vars;

	if (PKWK_READONLY) return; // Do nothing

  $recentFile = CACHE_DIR . 'recent.dat';
	$recent_pages = array();
  $recent_flag = file_exists($recentFile);
  if($recent_flag) {
    $fp = @fopen($recentFile, 'r');
    $wouldblock = true;
    if ($fp && flock($fp, LOCK_EX + LOCK_NB, $wouldblock) && ! $wouldblock) {
      $lines = file($recentFile);
      $lines = str_replace("\n", '', $lines);
      foreach ($lines as $line) {
        if (empty($line)) {
          break;
        }
        list($getTime, $getPage) = explode("\t", $line);
        if ($getPage != $whatsnew && ! check_non_list($getPage)) {
          $recent_pages[$getPage] = $getTime;
        }
      }
      if (is_page($editPage) && ! check_non_list($editPage)) {
        clearstatcache();
        $recent_pages[$editPage] = get_filetime($editPage);
      } elseif (isset($recent_pages[$editPage])) {
        unset($recent_pages[$editPage]);
      }
    } else {
      $recent_flag = false;
    }
  }
  if(! $recent_flag) {
    $pages = get_existpages();
    foreach($pages as $page)
      if ($page != $whatsnew && ! check_non_list($page))
        $recent_pages[$page] = get_filetime($page);
  }

  // Sort decending order of last-modification date
  arsort($recent_pages, SORT_NUMERIC);

  if (isset($fp)) {
    flock($fp, LOCK_UN);
  }

	// Create recent.dat (for recent.inc.php)
	$fp = fopen(CACHE_DIR . 'recent.dat', 'w') or
		die_message('Cannot write cache file ' .
		CACHE_DIR . 'recent.dat' .
		'<br />Maybe permission is not writable or filename is too long');

	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);
	rewind($fp);
	foreach ($recent_pages as $page=>$time)
		fputs($fp, $time . "\t" . $page . "\n");
	flock($fp, LOCK_UN);
	fclose($fp);

	// Create RecentChanges
	$fp = fopen(get_filename($whatsnew), 'w') or
		die_message('Cannot write page file ' .
		htmlsc($whatsnew) .
		'<br />Maybe permission is not writable or filename is too long');

	set_file_buffer($fp, 0);
	flock($fp, LOCK_EX);
	rewind($fp);

	// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
	$tmp_array = array_keys($recent_pages); // with array_splice()

	foreach (array_splice($tmp_array, 0, $maxshow) as $page) {
		$time      = $recent_pages[$page];
		$s_lastmod = htmlsc(format_date($time));
		$s_page    = htmlsc($page);
		fputs($fp, '-' . $s_lastmod . ' - [[' . $s_page . ']]' . "\n");
	}
	fputs($fp, '#norelated' . "\n"); // :)
	flock($fp, LOCK_UN);
	fclose($fp);

	// For AutoLink
	if ($autolink) {
		list($pattern, $pattern_a, $forceignorelist) =
			get_autolink_pattern($pages);

		$fp = fopen(CACHE_DIR . 'autolink.dat', 'w') or
			die_message('Cannot write autolink file ' .
			CACHE_DIR . '/autolink.dat' .
			'<br />Maybe permission is not writable');
		set_file_buffer($fp, 0);
		flock($fp, LOCK_EX);
		rewind($fp);
		fputs($fp, $pattern   . "\n");
		fputs($fp, $pattern_a . "\n");
		fputs($fp, join("\t", $forceignorelist) . "\n");
		flock($fp, LOCK_UN);
		fclose($fp);
	}
}

// Get elapsed date of the pate
function get_pg_passage($page, $sw = TRUE)
{
	global $show_passage;
	if (! $show_passage) return '';

	$time = get_filetime($page);
	$pg_passage = ($time != 0) ? get_passage($time) : '';

	return $sw ? '<small>' . $pg_passage . '</small>' : ' ' . $pg_passage;
}

// Last-Modified header
function header_lastmod($page = NULL)
{
	global $lastmod;

	if ($lastmod && is_page($page)) {
		pkwk_headers_sent();
    header('ETag: "' . get_filetime($page) . '"');
		header('Last-Modified: ' .
			date('D, d M Y H:i:s', get_filetime($page)) . ' GMT');
	}
}

// Get a page list of this wiki
function get_existpages($dir = DATA_DIR, $ext = '.txt')
{
	$aryret = array();

	$pattern = '((?:[0-9A-F]{2})+)';
	if ($ext != '') $ext = preg_quote($ext, '/');
	$pattern = '/^' . $pattern . $ext . '$/';

	$dp = @opendir($dir) or
		die_message($dir . ' is not found or not readable.');
	$matches = array();
	while ($file = readdir($dp))
		if (preg_match($pattern, $file, $matches))
			$aryret[$file] = decode($matches[1]);
	closedir($dp);

	return $aryret;
}

// Get PageReading(pronounce-annotated) data in an array()
function get_readings()
{
	global $pagereading_enable, $pagereading_kanji2kana_converter;
	global $pagereading_kanji2kana_encoding, $pagereading_chasen_path;
	global $pagereading_kakasi_path, $pagereading_config_page;
	global $pagereading_config_dict;

	$pages = get_existpages();

	$readings = array();
	foreach ($pages as $page) 
		$readings[$page] = '';

	$deletedPage = FALSE;
	$matches = array();
	foreach (get_source($pagereading_config_page) as $line) {
		$line = chop($line);
		if(preg_match('/^-\[\[([^]]+)\]\]\s+(.+)$/', $line, $matches)) {
			if(isset($readings[$matches[1]])) {
				// This page is not clear how to be pronounced
				$readings[$matches[1]] = $matches[2];
			} else {
				// This page seems deleted
				$deletedPage = TRUE;
			}
		}
	}

	// If enabled ChaSen/KAKASI execution
	if($pagereading_enable) {

		// Check there's non-clear-pronouncing page
		$unknownPage = FALSE;
		foreach ($readings as $page => $reading) {
			if($reading == '') {
				$unknownPage = TRUE;
				break;
			}
		}

		// Execute ChaSen/KAKASI, and get annotation
		if($unknownPage) {
			switch(strtolower($pagereading_kanji2kana_converter)) {
			case 'chasen':
				if(! file_exists($pagereading_chasen_path))
					die_message('ChaSen not found: ' . $pagereading_chasen_path);

				$tmpfname = tempnam(realpath(CACHE_DIR), 'PageReading');
				$fp = fopen($tmpfname, 'w') or
					die_message('Cannot write temporary file "' . $tmpfname . '".' . "\n");
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;
					fputs($fp, mb_convert_encoding($page . "\n",
						$pagereading_kanji2kana_encoding, SOURCE_ENCODING));
				}
				fclose($fp);

				$chasen = "$pagereading_chasen_path -F %y $tmpfname";
				$fp     = popen($chasen, 'r');
				if($fp === FALSE) {
					unlink($tmpfname);
					die_message('ChaSen execution failed: ' . $chasen);
				}
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;

					$line = fgets($fp);
					$line = mb_convert_encoding($line, SOURCE_ENCODING,
						$pagereading_kanji2kana_encoding);
					$line = chop($line);
					$readings[$page] = $line;
				}
				pclose($fp);

				unlink($tmpfname) or
					die_message('Temporary file can not be removed: ' . $tmpfname);
				break;

			case 'kakasi':	/*FALLTHROUGH*/
			case 'kakashi':
				if(! file_exists($pagereading_kakasi_path))
					die_message('KAKASI not found: ' . $pagereading_kakasi_path);

				$tmpfname = tempnam(realpath(CACHE_DIR), 'PageReading');
				$fp       = fopen($tmpfname, 'w') or
					die_message('Cannot write temporary file "' . $tmpfname . '".' . "\n");
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;
					fputs($fp, mb_convert_encoding($page . "\n",
						$pagereading_kanji2kana_encoding, SOURCE_ENCODING));
				}
				fclose($fp);

				$kakasi = "$pagereading_kakasi_path -kK -HK -JK < $tmpfname";
				$fp     = popen($kakasi, 'r');
				if($fp === FALSE) {
					unlink($tmpfname);
					die_message('KAKASI execution failed: ' . $kakasi);
				}

				foreach ($readings as $page => $reading) {
					if($reading != '') continue;

					$line = fgets($fp);
					$line = mb_convert_encoding($line, SOURCE_ENCODING,
						$pagereading_kanji2kana_encoding);
					$line = chop($line);
					$readings[$page] = $line;
				}
				pclose($fp);

				unlink($tmpfname) or
					die_message('Temporary file can not be removed: ' . $tmpfname);
				break;

			case 'none':
				$patterns = $replacements = $matches = array();
				foreach (get_source($pagereading_config_dict) as $line) {
					$line = chop($line);
					if(preg_match('|^ /([^/]+)/,\s*(.+)$|', $line, $matches)) {
						$patterns[]     = $matches[1];
						$replacements[] = $matches[2];
					}
				}
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;

					$readings[$page] = $page;
					foreach ($patterns as $no => $pattern)
						$readings[$page] = mb_convert_kana(mb_ereg_replace($pattern,
							$replacements[$no], $readings[$page]), 'aKCV');
				}
				break;

			default:
				die_message('Unknown kanji-kana converter: ' . $pagereading_kanji2kana_converter . '.');
				break;
			}
		}

		if($unknownPage || $deletedPage) {

			asort($readings); // Sort by pronouncing(alphabetical/reading) order
			$body = '';
			foreach ($readings as $page => $reading)
				$body .= '-[[' . $page . ']] ' . $reading . "\n";

			page_write($pagereading_config_page, $body);
		}
	}

	// Pages that are not prounouncing-clear, return pagenames of themselves
	foreach ($pages as $page) {
		if($readings[$page] == '')
			$readings[$page] = $page;
	}

	return $readings;
}

// Get a list of encoded files (must specify a directory and a suffix)
function get_existfiles($dir, $ext)
{
	$pattern = '/^(?:[0-9A-F]{2})+' . preg_quote($ext, '/') . '$/';
	$aryret = array();
	$dp = @opendir($dir) or die_message($dir . ' is not found or not readable.');
	while ($file = readdir($dp))
		if (preg_match($pattern, $file))
			$aryret[] = $dir . $file;
	closedir($dp);
	return $aryret;
}

// Get a list of related pages of the page
function links_get_related($page)
{
	global $vars, $related;
	static $links = array();

	if (isset($links[$page])) return $links[$page];

	// If possible, merge related pages generated by make_link()
  $links[$page] = array();

	// Get repated pages from DB
	$links[$page] += links_get_related_db($vars['page']);

	return $links[$page];
}

// _If needed_, re-create the file to change/correct ownership into PHP's
// NOTE: Not works for Windows
function pkwk_chown($filename, $preserve_time = TRUE)
{
	static $php_uid; // PHP's UID

	if (! isset($php_uid)) {
		if (extension_loaded('posix')) {
			$php_uid = posix_getuid(); // Unix
		} else {
			$php_uid = 0; // Windows
		}
	}

	// Lock for pkwk_chown()
	$lockfile = CACHE_DIR . 'pkwk_chown.lock';
	$flock = fopen($lockfile, 'a') or
		die('pkwk_chown(): fopen() failed for: CACHEDIR/' .
			basename(htmlsc($lockfile)));
	flock($flock, LOCK_EX) or die('pkwk_chown(): flock() failed for lock');

	// Check owner
	$stat = stat($filename) or
		die('pkwk_chown(): stat() failed for: '  . basename(htmlsc($filename)));
	if ($stat[4] === $php_uid) {
		// NOTE: Windows always here
		$result = TRUE; // Seems the same UID. Nothing to do
	} else {
		$tmp = $filename . '.' . getmypid() . '.tmp';

		// Lock source $filename to avoid file corruption
		// NOTE: Not 'r+'. Don't check write permission here
		$ffile = fopen($filename, 'r') or
			die('pkwk_chown(): fopen() failed for: ' .
				basename(htmlsc($filename)));

		// Try to chown by re-creating files
		// NOTE:
		//   * touch() before copy() is for 'rw-r--r--' instead of 'rwxr-xr-x' (with umask 022).
		//   * (PHP 4 < PHP 4.2.0) touch() with the third argument is not implemented and retuns NULL and Warn.
		//   * @unlink() before rename() is for Windows but here's for Unix only
		flock($ffile, LOCK_EX) or die('pkwk_chown(): flock() failed');
		$result = touch($tmp) && copy($filename, $tmp) &&
			($preserve_time ? (touch($tmp, $stat[9], $stat[8]) || touch($tmp, $stat[9])) : TRUE) &&
			rename($tmp, $filename);
		flock($ffile, LOCK_UN) or die('pkwk_chown(): flock() failed');

		fclose($ffile) or die('pkwk_chown(): fclose() failed');

		if ($result === FALSE) @unlink($tmp);
	}

	// Unlock for pkwk_chown()
	flock($flock, LOCK_UN) or die('pkwk_chown(): flock() failed for lock');
	fclose($flock) or die('pkwk_chown(): fclose() failed for lock');

	return $result;
}

// touch() with trying pkwk_chown()
function pkwk_touch_file($filename, $time = FALSE, $atime = FALSE)
{
	// Is the owner incorrected and unable to correct?
	if (! file_exists($filename) || pkwk_chown($filename)) {
		if ($time === FALSE) {
			$result = touch($filename);
		} else if ($atime === FALSE) {
			$result = touch($filename, $time);
		} else {
			$result = touch($filename, $time, $atime);
		}
		return $result;
	} else {
		die('pkwk_touch_file(): Invalid UID and (not writable for the directory or not a flie): ' .
			htmlsc(basename($filename)));
	}
}
?>
