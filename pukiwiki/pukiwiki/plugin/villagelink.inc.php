<?php

function plugin_villagelink_convert()
{
  global $vars;
  $body = '';
  /*
  if ($vars['page'] == 'C821Â¼') {
    $ad = '
    <script type="text/javascript"><!--
    google_ad_client = "pub-7969419198588736";
    google_ad_width = 468;
    google_ad_height = 60;
    google_ad_format = "468x60_as";
    google_ad_type = "text_image";
    google_ad_channel ="1455637492";
    google_color_border = "FFFFFF";
    google_color_bg = "FFFFFF";
    google_color_link = "003399";
    google_color_url = "FF6600";
    google_color_text = "333333";
    //--></script>
    <script type="text/javascript"
    src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
    </script>
    ';
  }
  */
  if (preg_match("/^([A-Z])(\d+)/", $vars[page], $result) == 1) {
    $logs = array('¿Í', 'Ïµ', 'Êè', 'Á´');
    foreach($logs as $log) {
      $body = $body . "[[¡Ú" . $log . "¡Û>" . $result[1] . "¹ñ-" . $log . ":" . $result[2] . "]] ";
    }
  }
  $body = convert_html($body);
  return $ad . $body;
}
?>
