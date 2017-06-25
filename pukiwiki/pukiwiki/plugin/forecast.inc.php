<?php
define("PLUGIN_VILLAGE_FORECAST_BASEURL", "http://ninjin002.x0.com/wolff/");

function plugin_forecast_convert() {
  srand(0);
  $args = func_get_args();
  $extend = intval(array_shift($args));

  $line = array();
  array_push($line, "* �ǿ���¼����");
  array_push($line, plugin_forecast_head());
  $village = plugin_forecast_latest();
  array_push($line, plugin_forecast_body($village));

  array_push($line, "* ����γ�¼����");
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
  return "|¼�ֹ�|¼��̾��|����ͽ��|��������|(24����ɽ��)|h";
}

function plugin_forecast_body($village) {
  $days = array("��", "��", "��", "��", "��", "��", "��");
  $day = $days[intval(strftime('%w', $village['date']))];
  $line = array();
  array_push($line, "");
  // ¼�ֹ�
  array_push($line,  "F" . $village['number']);
  // ¼��̾��
  array_push($line, $village['name']);
  // ����ͽ��
  $date = strftime("%m/%d ($day)", $village['date']);
  // �ǿ���¼�ξ��ϥ��ơ�������ɽ������
  if ($village['status']) {
    $count = plugin_forecast_latest_count($village['number']);
    $date .= ' ([['. $village['status'] . ':' . plugin_forecast_url($village['number']) . "]] $count/16��)";
  }
  array_push($line, $date);
  // ��������
  $ampm = strftime('%p', $village['date']) == 'AM' ? '����' : '���';
  // array_push($line, $ampm . strftime('%I��%Mʬ', $village['date']));
  array_push($line, $ampm . date(' g�� iʬ', $village['date']));
  // ���������24����ɽ����
  array_push($line, strftime('(%H:%M)', $village['date']));
  array_push($line, "");
  return implode("|", $line);
}

function plugin_forecast_url($number) {
  return PLUGIN_VILLAGE_FORECAST_BASEURL . "index.rb?vid=$number";
}

// ����¼�ξ�����������
function plugin_forecast_next($village) {
  return array(
    'number' => $village['number'] + 1,
    'name' => plugin_forecast_next_name($village['name']),
    'date' => plugin_forecast_next_date($village['date'])
  );
}

// ����¼�λ��֤��������
function plugin_forecast_next_date($now) {
  $next = $now + 7 * 60 * 60;
  $hour = intval(date('H', $next));
  $min = intval(date('i', $next));
  if ($hour == 0) {
    $next = $min == 0 ? $next + 60 * 30 : $next - 60 * 30;
  }
  return $next;
}

// �罸���¼���������
function plugin_forecast_latest() {
  $top = file(PLUGIN_VILLAGE_FORECAST_BASEURL);
  foreach ($top as $line) {
    $line = mb_convert_encoding($line, 'euc-jp', 'shift_jis');
    if (preg_match("/(�����Ԥ�)|(���ü��罸��)/", $line, $matchs)) {
      $village = array('status' => $matchs[0]);
      preg_match("/<a href[^>]+>F(\d+) ([^<]+)<\/a>/", $line, $matchs);
      $village['number'] = intval($matchs[1]);
      $village['name'] = $matchs[2];
      preg_match("/<strong>��(.+) (\d+)�� (\d+)ʬ ������<\/strong>/", $line, $matchs);
      $hour = intval($matchs[1] == "����" ? $matchs[2] : $matchs[2] + 12);
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

// ����¼�λ��ÿͿ����������
function plugin_forecast_latest_count($number) {
  $pro = file(plugin_forecast_url($number) . '&mes=all');
  $flag = false;
  $count = 0;
  foreach ($pro as $line) {
    $line = mb_convert_encoding($line, 'euc-jp', 'shift_jis');
    if ($flag && preg_match('/(\d+)����/', $line, $matchs)) {
      $count = intval($matchs[1]);
      $flag = false;
    }
    //if (preg_match('/<div class="announce"> (\d+)����/', $line, $matchs)) {
    if (preg_match('/<div class="announce">/', $line, $matchs)) {
      $flag = true;
    }
  }
  return $count;
}

// ����¼��̾�����������
function plugin_forecast_next_name($name) {
  $names = array("�����줿¼", "�������ޤ�¼", "�ä��Ԥ�¼", "��������¼", "���Ƥ�¼", "ʿ�¤�¼", "���Ĥ���¼", "�ᤷ�ߤ�¼", "������¼", "���Ϥ�¼", "����¼", "��ά��¼", "�ǲ̤Ƥ�¼", "������¼", "���դ�¼", "����Ԥ�¼", "�ǳ���¼", "�в񤤤�¼", "�դ�Ȥ�¼", "ƽ��¼", "���ɤ�줿¼", "���Ϥ�¼", "�¤餮��¼", "�Ǹ��¼", "�����ŵ���¼", "����줿¼", "���Ф�¼", "���Ϥ�¼", "�Ĥ��줿¼", "������¼", "�˴���¼", "���ΤƤ�줿¼", "��˾��¼", "΢�ڤ��¼", "���ۤ�¼", "���λߤޤ�¼", "�ն���¼", "˺���줿¼", "ë���¼", "ʿ�ޤ�¼", "������¼", "���κ�����¼");
  $index = array_search($name, $names);
  return $names[($index + 1) % count($names)];
}
// var_dump(plugin_forecast_convert());

