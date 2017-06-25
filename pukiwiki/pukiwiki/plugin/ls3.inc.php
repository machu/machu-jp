<?php

function get_existpages2($dir = DATA_DIR, $ext = '.txt')
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
  #$aryret[$file] = decode($matches[1]);
    #  $aryret[$file] = $file;
    $aryret[$file] = ($matches[1]);
	closedir($dp);

	return $aryret;
}

  
function plugin_ls3_convert()
{
  $pages = get_existpages2();
  $body = count($pages);
  $body = array_pop($pages);
  $body = "C2E836B2F3C5ECCBCCC2BCB7EBB2CCCAF3B9F0";
  $body = pack('H*', (string)$body);

  return "!!!" . $body . "!!!\n";
}
?>
