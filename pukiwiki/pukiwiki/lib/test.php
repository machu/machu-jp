<?
require('../pukiwiki.ini.php');
require('file.php');
require('func.php');
define('LOCALZONE', date('Z'));                                                 
define('UTIME', time() - LOCALZONE);                                            
define('MUTIME', getmicrotime());                                               

echo LOCALZONE . "\n";
echo ZONETIME . "\n";
echo UTIME . "\n";
echo get_date('Y/m/d H:i + O', UTIME) . "\n";
echo get_date('Y/m/d H:i', strtotime('yesterday', UTIME)) . "\n";                                                             
echo date('Y/m/d H:i', UTIME) . "\n";
echo date('Y/m/d H:i', UTIME) . "\n";
echo date('Y/m/d H:i', strtotime('yesterday', UTIME)) . "\n";                                                             
echo date('Y/m/d H:i', strtotime('yesterday', time())) . "\n";                                                             
echo time() . "\n";

echo get_filetime('SandBox') . "\n";
echo date('Y/m/d H:i', filemtime('test.php') - LOCALZONE) . "\n";
