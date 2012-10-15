<?php
/*
    Create Table rejections (
       id Integer Primary Key,
       name Varchar(12) Not Null
    );
    Create Index rejections_name On rejections ( name );

    Create Table posts (
       id Integer Primary Key,
       parent Integer,
       name Varchar(12) Not Null,
       posted Date Not Null
    );
    Create Index posts_parent On posts ( parent );
    Create Index posts_post On posts ( name, posted );

    Create Table roots (
       id Integer Primary Key,
       post Integer Not Null,
       apikey Varchar(64) Not Null,
       icon Varchar(256) Not Null
    );
    Create Index roots_post On roots ( post );

    Create Table jsoncaches (
       id Integer Primary Key,
       post Integer Not Null,
       json Text,
       touched Boolean Not Null Default 0
    );
    Create Index jsoncaches_post On jsoncaches ( post );
*/

//class Host {
//    function __construct($name, $pare)
//}

class db {
    const PostFields = "*, 'http://me2day.net/' || name
          || strftime('/%Y/%m/%d#%H:%M:%S', posted) as url";

    static $db;

    static function connection() {
        if (!self::$db instanceof SQLiteDatabase) {
            self::$db = new SQLiteDatabase(dirname(__FILE__).'/me2virus.db');
        }
        return self::$db;
    }
    static function query($query) {
        return self::connection()->query($query);
    }
    static function begin() { return self::query('Begin;'); }
    static function commit() { return self::query('Commit;'); }
    static function rollback() { return self::query('Rollback;'); }

    protected static $cachedposts = array();

    static function parseurl($posturl) {
        if (!array_key_exists($posturl, self::$cachedposts)) {
            $pattern = '{/([^/]+)/(\d{4}/\d{2}/\d{2}#\d{2}:\d{2}:\d{2})}';
            preg_match($pattern, $posturl, $matches);
            $post['name'] = $matches[1];
            $post['posted'] = str_replace(
                array('/', '#'),
                array('-', ' '),
                $matches[2]
            );
            self::$cachedposts[$posturl] = $post;
        }
        return self::$cachedposts[$posturl];
    }

    static function fetchObjectAll($exec) {
        $rows = array();
        while($rows[] = $exec->fetchObject());
        array_pop($rows);
        return $rows;
    }

    static function rejected($name) {
        $selection = "
            Select count(*) From rejections
            Where name = '{$name}';
        ";
        return !!self::query($selection)->fetchSingle();
    }

    static function accepted($name) {
        return !self::rejected($name);
    }

    static function reject($name) {
        if (self::accepted($name)) {
            $insertion = "
                Insert Into rejections (name)
                Values ('{$name}');
            ";
            if (!self::query($insertion)) return false;
        }
        return true;
    }

    static function accept($name) {
        $deletion = "
            Delete From rejections
            Where name = '{$name}';
        ";
        return !!self::query($deletion);
    }

    static function post($posturl) {
        $post = self::parseurl($posturl);
        $selection = "
            Select ".self::PostFields."
            From posts
            Where name = '{$post['name']}'
              And posted = '{$post['posted']}';
        ";
        return self::query($selection)->fetchObject();
    }

    static function infect($posturl, $parenturl = null) {
        $post = self::parseurl($posturl);

        $parent = 'Null';
        if (!is_null($parenturl)) $parent = self::post($parenturl)->id;

        $insertion = "
            Insert Into posts (parent, name, posted)
            Values ({$parent}, '{$post['name']}', '{$post['posted']}');
        ";

        while ($parent and $parent != 'Null') {
            $touch = "
                Update jsoncaches
                Set touched = 1
                Where post = {$parent};
            ";
            self::query($touch);
            $parent = self::query("
                Select parent From posts
                Where id = {$parent};
            ")->fetchSingle();
        }

        return self::query($insertion);
    }

    static function initial($posturl, $appkey, $icon) {
        $post = self::post($posturl);
        $insertion = "
            Insert Into roots (post, apikey, icon)
            Values ({$post->id}, '{$appkey}', '{$icon}');
        ";
        return self::query($insertion);
    }

    static function route($posturl) {
        $route = array();
        do {
            if (!count($route)) {
                $post = self::post($posturl);
                if (!$post) return $route;
            } else {
                $selection = "
                    Select ".self::PostFields."
                    From posts
                    Where id = {$id};
                ";
                $post = self::query($selection)->fetchObject();
            }
            $route[] = $post;
            $id = $post->parent;
        } while ($id);
        return array_reverse($route);
    }

    static function root($posturl) {
        return current(self::route($posturl));
    }

    static function roots() {
        $selection = "
            Select posts.name As name,
                   posts.posted As posted,
                   roots.icon As icon,
                   'http://me2day.net/' || name
                   || strftime('/%Y/%m/%d#%H:%M:%S', posted) as url
            From roots, posts
            Where roots.post = posts.id;
        ";
        return self::fetchObjectAll(self::query($selection));
    }

    static function infectees($posturl) {
        $post = self::post($posturl);
        $selection = "
            Select ".self::PostFields."
            From posts
            Where parent = {$post->id};
        ";
        return self::fetchObjectAll(self::query($selection));
    }

    static function scale($posturl) {
        $post = self::post($posturl);
        $selection = "
            Select count(*) From posts
            Where parent = {$post->id};
        ";
        $count = self::query($selection)->fetchSingle();
        if ($count) {
            foreach (self::infectees($posturl) as $post) {
                $count += self::scale($post->url);
            }
            return $count;
        } else {
            return 0;
        }
    }

    static function total() {
        $selection = "Select count(*) From posts;";
        return self::query($selection)->fetchSingle();
    }

    static function appkey($posturl) {
        $id = self::root($posturl)->id;
        $selection = "
            Select apikey From roots
            Where post = {$id};
        ";
        return self::query($selection)->fetchSingle();
    }
    static function icon($posturl) {
        $id = self::root($posturl)->id;
        $selection = "
            Select icon From roots
            Where post = {$id};
        ";
        return self::query($selection)->fetchSingle();
    }

    protected static function __json($posturl) {
        $post = self::post($posturl);

        $name = $post->name;
        $posted = $post->posted;
        $scale = self::scale($posturl);

        $route = array_map(create_function('$host',
            'return $host->url;'
        ), array_slice(self::route($posturl), 0, -1));

        $infectees = array();
        foreach (self::infectees($posturl) as $infectee) {
            $infectees[] = self::__json($infectee->url);
        }

        return array(
            'url' => $posturl,
            'name' => $name,
            'posted' => $posted,
            'scale' => $scale,
            'route' => $route,
            'infectees' => $infectees
        );
    }

    static function json($posturl) {
        $url = self::parseurl($posturl);
        $selection = "
            Select jsoncaches.json as json,
                   jsoncaches.touched as touched,
                   posts.id as postid
            From jsoncaches, posts
            Where posts.name = '{$url['name']}'
              And posts.posted = '{$url['posted']}'
              And jsoncaches.post = posts.id;
        ";
        $cache = self::query($selection)->fetchObject();
        if (!$cache or $cache->touched) {
            $json = preg_replace("{'}", "''",
                json_encode(self::__json($posturl))
            );
            if (!$cache) {
                $id = self::post($posturl)->id;
                $query = "
                    Insert Into jsoncaches (post, json)
                    Values ({$id}, '{$json}');
                ";
            } else {
                $query = "
                    Update jsoncaches
                    Set json = '{$json}', touched = 0
                    Where post = {$cache->postid};
                ";
            }
            if (!self::query($query)) return false;
        } else {
            $json = $cache->json;
        }
        return $json;
    }
}

