<?php
// $Id: countdown.inc.php,v 1.9 2006/03/06 06:20:30 nao-pon Exp $

/*
 * countdown.inc.php
 * License: GPL
 * Author: nao-pon http://hypweb.net
 * Last-Update: 2003-06-27
 *
 * カウントダウンプラグイン
 */
// 初期設定　国際化対応　ってコメントは日本語　(^^ゞ
function plugin_countdown_init() {
	if (LANG == "ja") {
		$msg['_countdown_msg'] = "%1\$sまであと%2\$d%3\$s日";
	} else {
		$msg['_countdown_msg'] = "%2\$d%3\$sday(s) to %1\$s";
	}
	set_plugin_messages($msg);
}
// インラインプラグインとしての挙動
function plugin_countdown_inline() {
	global $_msg_week;
	global $_countdown_msg;
	
	$just_day = "";
  list($y,$m,$d,$title) = func_get_args();
  //第[1-5]？曜日対応
  $my_lng_week = "|".implode("|",$_msg_week);
  $arg = array();
  if (preg_match("/(sun|mon|tue|wed|thu|fri|sat".$my_lng_week.")([1-5])?/",$d,$arg)) {
		$e_week = array("sun","mon","tue","wed","thu","fri","sat");
		if (LANG != "en") $arg[1] = str_replace($_msg_week,$e_week,$arg[1]);
		$y2=$y;
		if (!$y2) $y2=date("Y");
		$f_day = mktime(0,0,0,$m,1,$y2);
		$f_week = date("w",$f_day);
		$t_week = date("w",strtotime("last $arg[1]",$f_day));
		if ($f_week > $t_week){
			$d = 6-$f_week+$t_week;
		} else {
			$d = 1+$t_week-$f_week;
		}
		if ($arg[2]) $d += ($arg[2]-1)*7;
	}
  //毎年に対応 every または 空白
  if ($y == "" || $y == "every"){
		if ($m*100+$d == date("m")*100+date("d")) $just_day="(0)";
		$y = date("Y");
		if ($m*100+$d <= date("m")*100+date("d")){
			//今年は過ぎたので来年
			$y ++;
		}
	}
	//日付の妥当性チェック
	if (!checkdate($m,$d,$y)) return false;
	
	if ($title) {
		return sprintf($_countdown_msg,$title,plugin_countdown_day($y,$m,$d),$just_day);
	} else {
		return plugin_countdown_day($y,$m,$d).$just_day;
	}
}

// ユリウス日(JD)算出 (upkさん提供)
function plugin_countdown_date2jd($m,$d,$y,$h=0,$i=0,$s=0) {

  if( $m < 3.0 ){
    $y -= 1.0;
    $m += 12.0;
  }

  $jd  = (int)( 365.25 * $y );
  $jd += (int)( $y / 400.0 );
  $jd -= (int)( $y / 100.0 );
  $jd += (int)( 30.59 * ( $m-2.0 ) );
  $jd += 1721088;
  $jd += $d;

  $t  = $s / 3600.0;
  $t += $i /60.0;
  $t += $h;
  $t  = $t / 24.0;

  $jd += $t;
  return( $jd );
}

// ユリウス日から年月日を設定 (upkさん提供)
function plugin_countdown_jd2date($jd) {

  $x0 = (int)( $jd+68570.0);
  $x1 = (int)( $x0/36524.25 );
  $x2 = $x0 - (int)( 36524.25*$x1 + 0.75 );
  $x3 = (int)( ( $x2+1 )/365.2425 );
  $x4 = $x2 - (int)( 365.25*$x3 )+31.0;
  $x5 = (int)( (int)($x4) / 30.59 );
  $x6 = (int)( (int)($x5) / 11.0 );

  $TIME[2] = $x4 - (int)( 30.59*$x5 );
  $TIME[1] = $x5 - 12*$x6 + 2;
  $TIME[0] = 100*( $x1-49 ) + $x3 + $x6;

  // 2月30日の補正
  if($TIME[1] == 2 && $TIME[2] > 28){
    if($TIME[0] % 100 == 0 && $TIME[0] % 400 == 0){
        $TIME[2] = 29;
    }elseif($TIME[0] % 4 ==0){
        $TIME[2] = 29;
    }else{
        $TIME[2] = 28;
    }
  }

  $tm = 86400.0*( $jd - (int)( $jd ) );
  $TIME[3] = (int)( $tm/3600.0 );
  $TIME[4] = (int)( ($tm - 3600.0*$TIME[3])/60.0 );
  $TIME[5] = (int)( $tm - 3600.0*$TIME[3] - 60*$TIME[4] );

  return($TIME);
}

// 日算出
function plugin_countdown_day($y,$m,$d) {
	if (function_exists ("GregorianToJD")) {
		$today = GregorianToJD (date("m"),date("d"),date("Y"));
		$date = GregorianToJD ($m,$d,$y);
	} else {
		$today = plugin_countdown_date2jd(date("m"),date("d"),date("Y"));
		$date = plugin_countdown_date2jd($m,$d,$y);
	}
	return ($date-$today > 0)? $date-$today : 0;
}

?>