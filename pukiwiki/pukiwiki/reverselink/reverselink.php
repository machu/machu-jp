<?php

//define('LOCALZONE', date('Z'));
define('CACHE_DIR', '../cache/');
define('DATA_DIR',  '../wiki/');
define('UTIME', time() - LOCALZONE);
define('CONTENT_CHARSET', 'EUC-JP');
if (! extension_loaded('mbstring')) {
  require('../lib/mbstring.php');
}


function htmlsc($string = '', $flags = ENT_COMPAT, $charset = CONTENT_CHARSET)
{
  return htmlspecialchars($string, $flags, $charset); // htmlsc()
}

function encode($key)
{
  return ($key == '') ? '' : strtoupper(bin2hex($key));
  // Equal to strtoupper(join('', unpack('H*0', $key)));
  // But PHP 4.3.10 says 'Warning: unpack(): Type H: outside of string in ...'
}

// Get last-modified filetime of the page
function get_filetime($page)
{
  // return is_page($page) ? filemtime(get_filename($page)) - LOCALZONE : 0;
  $pagename = get_filename($page);
  return file_exists($pagename) ? filemtime(get_filename($page)) - LOCALZONE : 0;
}

// Get physical file name of the page
function get_filename($page)
{
  return DATA_DIR . encode($page) . '.txt';
}

// 経過時刻文字列を作る
function get_passage($time, $paren = TRUE)
{
  static $units = array('m'=>60, 'h'=>24, 'd'=>1);

  $time = max(0, (UTIME - $time) / 60); // minutes

  foreach ($units as $unit=>$card) {
    if ($time < $card) break;
    $time /= $card;
  }
  $time = floor($time) . $unit;

  return $paren ? '(' . $time . ')' : $time;
}

// データベースから関連ページを得る
function links_get_related_db($page)
{
  $ref_name = CACHE_DIR . encode($page) . '.ref';
  if (! file_exists($ref_name)) return array();

  $times = array();
  foreach (file($ref_name) as $line) {
    list($_page) = explode("\t", rtrim($line));
    $time = get_filetime($_page);
    if($time != 0) $times[$_page] = $time;
  }
  return $times;
}

// Related pages
function make_related($page, $tag = '')
{
  global $vars, $rule_related_str, $related_str, $non_list;
  global $_ul_left_margin, $_ul_margin, $_list_pad_str;

  $script = './';
  $links = links_get_related_db($page);

  if ($tag) {
    ksort($links);
  } else {
    arsort($links);
  }

  $_links = array();
  $non_list_pattern = '/' . $non_list . '/';
  foreach ($links as $page=>$lastmod) {
    // if (preg_match($non_list_pattern, $page)) continue;

    $r_page   = rawurlencode($page);
    $s_page   = htmlsc($page);
    $passage  = get_passage($lastmod);
    /*
    if(strstr($page, '/')) {
      $_links[] = '<a ' . 'href="' . $script . '?' . $r_page . $anchor .
        '"' . $title . '>' . $s_alias . '</a>' . $al_right;
    } else {
      $_links[] = '<a ' . 'href="' . $r_page . '.html' . $anchor .
        '"' . $title . '>' . $s_alias . '</a>' . $al_right;
    }
    */

    if(strstr($page, '/')) {
      $_links[] = '<a href="' . $script . '?' . $r_page . '">' . $s_page . '</a>' . $passage;
    } else {
      $_links[] = $tag ?
        '<a href="' . $script . $r_page . '.html" title="' .
        $s_page . ' ' . $passage . '">' . $s_page . '</a>' :
        '<a href="' . $script . $r_page . '.html">' .
        $s_page . '</a>' . $passage;
      }
  }
  if (empty($_links)) return ''; // Nothing

  if ($tag == 'p') { // From the line-head
    $margin = $_ul_left_margin + $_ul_margin;
    $style  = sprintf($_list_pad_str, 1, $margin, $margin);
    $retval =  "\n" . '<ul' . $style . '>' . "\n" .
      '<li>' . join($rule_related_str, $_links) . '</li>' . "\n" .
      '</ul>' . "\n";
  } else if ($tag) {
    $retval = join($rule_related_str, $_links);
  } else {
    $retval = join($related_str, $_links);
  }

  return $retval;
}
?>
<?php
  $page = rawurldecode($_SERVER['QUERY_STRING']);
  echo 'ReverseLink: ' . mb_convert_encoding(make_related($page), 'UTF-8', 'EUC-JP');
?>
