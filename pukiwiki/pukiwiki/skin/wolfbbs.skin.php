<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
// Simple Skin
//
//
if (!defined('DATA_DIR')) { exit; }
header('Cache-control: no-cache');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=EUC-JP');
echo '<?xml version="1.0" encoding="EUC-JP"?>';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja">
<head>
 <meta http-equiv="content-type" content="application/xhtml+xml; charset=EUC-JP" />
 <meta http-equiv="content-style-type" content="text/css" />
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo $link_rss ?>">
<?php if (!$is_read) { ?>
 <meta name="robots" content="NOINDEX,NOFOLLOW" />
<?php } ?>

 <title><?php echo "$page_title - $title" ?></title>
 <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css" type="text/css" media="screen" />
 <link rel="stylesheet" href="/skin/wolfbbs.20170105.css" type="text/css" media="screen" />
 <link rel="contents" title="最近更新されたページ" href="./?RecentChanges" />
 <link rel="help" title="使い方" href="./?%A5%D8%A5%EB%A5%D7" />
 <link rel="search" title="検索" href="./?cmd=search" />
 <?php echo $head_tag ?>
 <script type="text/javascript" src="http://wolfbbs.jp/skin/prototype.js"></script>
 <script type="text/javascript" src="http://wolfbbs.jp/skin/reverselink.js"></script>
  <script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
  </script>
  <script type="text/javascript">
  _uacct = "UA-54310-2";
  urchinTracker();
  </script>
</head>
<body>

<nav id="navigator" class="navbar navbar-default" >
<div class="container-fluid">
  <!-- Brand and toggle get grouped for better mobile display -->
  <div class="navbar-header">
    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse-1" aria-expanded="false">
      <span class="sr-only">Toggle navigation</span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
      <span class="icon-bar"></span>
    </button>
    <a class="navbar-brand" href="<?php echo $modifierlink ?>">人狼BBSまとめサイト</a>
  </div>
  <!-- Collect the nav links, forms, and other content for toggling -->
  <div class="collapse navbar-collapse" id="navbar-collapse-1">
    <form class="navbar-form navbar-left" role="search" action="http://google.com/cse">
      <div class="form-group">
        <input type="hidden" name="cx" value="016311926812905813966:_0y84nz0hxm" />
        <input type="hidden" name="ie" value="EUC-JP" />
        <div class="input-group">
          <input type="text" class="form-control" placeholder="検索" name="q" />
          <span class="input-group-btn">
            <button type="submit" class="btn btn-primary">Search</button>
          </span>
        </div>
      </div>
    </form>
    <ul class="nav navbar-nav">
      <?php if($is_page) { ?>
        <?php  if($is_read) { ?>
          <li><a href="<?php echo $link_edit ?>" rel="nofollow"><img src="http://wolfbbs.jp/image/edit.png" width="20" height="20" alt="Edit" title="ページを編集する" />ページを編集する</a></li>
          <li><a href="<?php echo $link_diff ?>" rel="nofollow"><img src="http://wolfbbs.jp/image/diff.png" width="20" height="20" alt="Diff" title="編集箇所をみる" />編集箇所をみる</a></li>
        <?php } ?>
        <li><a href="<?php echo $link_backup ?>" rel="nofollow"><img src="http://wolfbbs.jp/image/backup.png" width="20" height="20" alf="Backup" title="ページのバックアップ" />ページのバックアップ</a></li>
      <?php } ?>
      <li><a href="./?RecentChanges"><img src="http://wolfbbs.jp/image/recentchanges.png" alt="Recent" title="最近更新されたページ" />最近更新されたページ</a></li>
      <li><a href="%A5%D8%A5%EB%A5%D7.html"><img src="http://wolfbbs.jp/image/help.png" alt="Help" title="ヘルプ" />ヘルプ</a></li>
    </ul>
  </div>

</div>
</nav>

<div id="header">
  <h1 class="title">
    <a href="<?php echo $modifierlink ?>">
    <?php echo $page_title ?></a> -
    <?php echo $title ?>
  </h1>
  <?php if ($lastmodified) { ?>
  <div id="lastmodified">
    <a href="<?php echo $link_backup ?>" title="以前のバージョンを見る">
    Last-modified: <?php echo date("Y-m-d H:i",$fmt)?></a>
  </div>
  <?php } ?>
</div>

<div id="innerContainer" class="container-fluid">
  <div class="row">
    <div class="col-sm-8">
      <div id="body">
        <?php echo $body ?>
        <?php if ($notes) { ?>
        <div id="note">
          <?php echo $notes ?>
        </div>
        <?php } ?>
        <?php if($is_page) { ?>
         <?php if($is_read) { ?>
          <div id="related">ReverseLink: <button id="revlink_button" onclick="reverse_link()">ReverseLink を取得する</button></div>
          <form name="reverselink">
          <input type="hidden" id="pagename" name="pagename" value="<?php echo $r_page ?>" />
          </form>
         <?php } ?>
        <?php } ?>
      </div>
    </div>

    <div class="col-sm-4">
      <div id="menubar">
       <h2 style="display: none">メニュー</h2>
       <div id="searchcontrol"></div>
       <h3>サイトメニュー</h3>
       <?php if (exist_plugin_convert('menu')) { ?>
        <?php echo do_plugin_convert('menu') ?>
       <?php } ?>
       </div>
     </div>
  </div>
</div>


<div id="footer">
  Powered by <a href="http://sourceforge.jp/projects/pukiwiki/">PukiWiki <?php echo S_VERSION ?></a> on PHP <?php echo PHP_VERSION ?>
  HTML convert time to <?php echo $taketime ?> sec. <!-- for debug -->
  <p class="copylight"><a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/2.0/jp/deed.ja"><img alt="クリエイティブ・コモンズ・ライセンス" border="0" src="http://creativecommons.org/images/public/somerights20.gif" /></a>
  このサイト内のテキスト（画像を除く）は、<a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/2.0/jp/deed.ja">クリエイティブ・コモンズ・ライセンス</a>の下でライセンスされています。(参照: <a href="<?php echo $script ?>?ServicePolicy">ServicePolicy</a>)</p>
</div>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
</body>
</html>
