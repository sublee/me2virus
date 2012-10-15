<?php
require_once dirname(__FILE__).'/../lib/me2pheungp/Me2.php';
require_once dirname(__FILE__).'/db.php';

$messages = include dirname(__FILE__).'/messages.php';

try {
    $debug = isset($_GET['debug']) && $_GET['debug'] == 'true';

    if ($initial = @$_POST['initial'] == 'yes') {
        $name = trim(@$_POST['user']);
        $userkey = @$_POST['userkey'];
        $appkey = @$_POST['appkey'];
        $icon = @$_POST['icon'];
        $agreed = @$_POST['agreed'] == 'yes';
        $body = stripslashes(@$_POST['body']);
        $parenturl = null;

        if (!$userkey) {
            echo '사용자키를 입력해주세요.';
            return;
        } else if (!$appkey) {
            echo 'App키를 입력해주세요.';
            return;
        } else if (!$icon) {
            echo '아이콘 주소를 입력해주세요.';
            return;
        } else if (!$agreed) {
            echo 'App키 등록에 동의해주세요.';
            return;
        } else if (!$body) {
            echo '본문을 작성해주세요.';
            return;
        }
    } else {
        header('Content-type: application/json');

        $name = trim(@$_GET['user']);
        $userkey = base64_decode(@$_GET['userkey']);
        $parenturl = @$_GET['parent'];
        $parent = db::post($parenturl);

        $appkey = db::appkey($parenturl);
        $icon = db::icon($parenturl);

        $i = array_rand($messages);
        $body = sprintf($messages[$i], $parent->name, $parenturl);
    }

    Me2Api::$applicationKey = $appkey;

    $user = new Me2AuthenticatedUser($name, $userkey);

    if (!$initial) {
        if (!$parent) {
            throw new Exception(
                '이 me2Virus는 감염 능력이 없는 변이체입니다.', 22
            );
        } else if (preg_match("{/{$name}/\d{4}/\d{2}/\d{2}}", $parenturl)) {
            throw new Exception(
                'me2Virus는 자신으로부터 감염되지 않습니다.', 1
            );
        } else if (db::rejected($name)) {
            throw new Exception(
                'me2Virus가 감염거부 의사를 존중했습니다.', 2
            );
        }
    }

    /*
    $parentpost = Me2Post::fromUrl($parenturl);
    $tags = $parentpost->tags->__toString();
    if (stripos($tags, 'me2virus') === false) {
        $tags = 'me2Virus '.$tags;
    }
    $tags = explode(' ', $tags);*/
    $tags = 'me2Virus';

    $types = array('me2virus');
    $type = $types[array_rand($types)];

    if (!$debug) $post = $user->post($body, $tags, 1, new Me2Callback(
        'http://me2.subl.ee/me2virus/', $icon, $type
    ));
    db::infect($post->url, $parenturl);

    if ($initial) {
        db::initial($post->url, $appkey, $icon);
        header("Location: {$post->url}");
        return;
    }

    throw new Exception('me2Virus에 감염 되었습니다.');
} catch (Exception $e) {
    $errno = (int) $e->getCode();
    $msg = $e->getMessage();
    if ($e instanceof Me2Exception and !$errno) {
        $trans = array(
            '올바르지 않은 조작입니다.'
                => array('이 me2Virus는 금일 감염 한도를 초과했습니다.', 21),
            'Missing application key'
                => array('이 me2Virus는 감염 능력을 잃었습니다.', 20)
        );
        if (array_key_exists($msg, $trans)) {
            $trans = $trans[$msg];
            $msg = $trans[0];
            $errno = $trans[1];
        }
    }
    echo json_encode(array('error' => $errno, 'message' => $msg));
}

