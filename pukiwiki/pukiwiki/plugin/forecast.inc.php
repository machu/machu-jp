<?php
define("PLUGIN_VILLAGE_FORECAST_BASEURL", "http://ninjin002.x0.com/wolff/");

function plugin_forecast_convert() {
  srand(0);
  $args = func_get_args();
  $extend = intval(array_shift($args));

  $line = array();
  array_push($line, "* 最新の村情報");
  array_push($line, plugin_forecast_head());
  $village = plugin_forecast_latest();
  array_push($line, plugin_forecast_body($village));

  array_push($line, "* 今後の開村情報");
  array_push($line, plugin_forecast_head());
  for ($i = 0; $i < 50; $i++) {
    $village = plugin_forecast_next($village);
    if (rand(0, 99) < $extend) {
      $village['date'] += 60 * 60 * 24;
    }
    array_push($line, plugin_forecast_body($village));
  }
  return convert_html(implode("\n", $line));
  // return implode("\n", $line);
}


function plugin_forecast_head() {
  return "|村番号|村の名前|開始予報|更新時刻|(24時間表記)|h";
}

function plugin_forecast_body($village) {
  $days = array("日", "月", "火", "水", "木", "金", "土");
  $day = $days[intval(strftime('%w', $village['date']))];
  $line = array();
  array_push($line, "");
  // 村番号
  array_push($line,  "F" . $village['number']);
  // 村の名前
  array_push($line, $village['name']);
  // 開始予報
  $date = strftime("%m/%d ($day)", $village['date']);
  // 最新の村の場合はステータスを表示する
  if ($village['status']) {
    $count = plugin_forecast_latest_count($village['number']);
    $date .= ' ([['. $village['status'] . ':' . plugin_forecast_url($village['number']) . "]] $count/16人)";
  }
  array_push($line, $date);
  // 更新時刻
  $ampm = strftime('%p', $village['date']) == 'AM' ? '午前' : '午後';
  // array_push($line, $ampm . strftime('%I時%M分', $village['date']));
  array_push($line, $ampm . date(' g時 i分', $village['date']));
  // 更新時刻（24時間表記）
  array_push($line, strftime('(%H:%M)', $village['date']));
  array_push($line, "");
  return implode("|", $line);
}

function plugin_forecast_url($number) {
  return PLUGIN_VILLAGE_FORECAST_BASEURL . "index.rb?vid=$number";
}

// 次の村の情報を取得する
function plugin_forecast_next($village) {
  return array(
    'number' => $village['number'] + 1,
    'name' => plugin_forecast_next_name($village['name']),
    'date' => plugin_forecast_next_date($village['date'])
  );
}

// 次の村の時間を取得する
function plugin_forecast_next_date($now) {
  $next = $now + 7 * 60 * 60;
  $hour = intval(date('H', $next));
  $min = intval(date('i', $next));
  if ($hour == 0) {
    $next = $min == 0 ? $next + 60 * 30 : $next - 60 * 30;
  }
  return $next;
}

// 募集中の村を取得する
function plugin_forecast_latest() {
  $top = file(PLUGIN_VILLAGE_FORECAST_BASEURL);
  foreach ($top as $line) {
    $line = mb_convert_encoding($line, 'euc-jp', 'shift_jis');
    if (preg_match("/(開始待ち)|(参加者募集中)/", $line, $matchs)) {
      $village = array('status' => $matchs[0]);
      preg_match("/<a href[^>]+>F(\d+) ([^<]+)<\/a>/", $line, $matchs);
      $village['number'] = intval($matchs[1]);
      $village['name'] = $matchs[2];
      preg_match("/<strong>（(.+) (\d+)時 (\d+)分 更新）<\/strong>/", $line, $matchs);
      $hour = intval($matchs[1] == "午前" ? $matchs[2] : $matchs[2] + 12);
      $min = intval($matchs[3]);
      $village['date'] = mktime($hour, $min, 0);
      if ($village['date'] < time()) {
        $village['date'] += 60 * 60 * 24;
      }
      return $village;
    }
  }
  return array();
}

// 次の村の参加人数を取得する
function plugin_forecast_latest_count($number) {
  $pro = file(plugin_forecast_url($number) . '&mes=all');
  $flag = false;
  $count = 0;
  foreach ($pro as $line) {
    $line = mb_convert_encoding($line, 'euc-jp', 'shift_jis');
    if ($flag && preg_match('/(\d+)人目/', $line, $matchs)) {
      $count = intval($matchs[1]);
      $flag = false;
    }
    //if (preg_match('/<div class="announce"> (\d+)人目/', $line, $matchs)) {
    if (preg_match('/<div class="announce">/', $line, $matchs)) {
      $flag = true;
    }
  }
  return $count;
}

// 次の村の名前を取得する
function plugin_forecast_next_name($name) {
  $names = array("隠された村", "日の沈まぬ村", "消え行く村", "怪しげな村", "山影の村", "平和な村", "嘘つきの村", "悲しみの村", "恐ろしい村", "盆地の村", "幻の村", "謀略の村", "最果ての村", "封印の村", "海辺の村", "開拓者の村", "断崖の村", "出会いの村", "ふもとの村", "峠の村", "血塗られた村", "僻地の村", "安らぎの村", "最後の村", "疑心暗鬼の村", "呪われた村", "新緑の村", "荒地の村", "残された村", "小さな村", "極寒の村", "見捨てられた村", "希望の村", "裏切りの村", "沈黙の村", "雨の止まぬ村", "辺境の村", "忘れられた村", "谷底の村", "平凡な村", "星狩りの村", "日の差さぬ村");
  $index = array_search($name, $names);
  return $names[($index + 1) % count($names)];
}
// var_dump(plugin_forecast_convert());

