(function() {
var debug = false;

String.prototype.base64encode = function() {
    var base64s = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                + 'abcdefghijklmnopqrstuvwxyz'
                + '0123456789+/';
    var bits, dual, i = 0, encOut = '';
    while (this.length >= i + 3) {
        bits = (this.charCodeAt(i++) & 0xff) << 16
             | (this.charCodeAt(i++) & 0xff) << 8
             | this.charCodeAt(i++) & 0xff;

        encOut += base64s.charAt((bits & 0x00fc0000) >> 18)
                + base64s.charAt((bits & 0x0003f000) >> 12)
                + base64s.charAt((bits & 0x00000fc0) >> 6)
                + base64s.charAt(bits & 0x0000003f);
    }
    if (this.length - i > 0 && this.length - i < 3) {
    dual = !!(this.length - i - 1);
    bits = ((this.charCodeAt(i ++) & 0xff) << 16)
         | (dual ? (this.charCodeAt(i) & 0xff) << 8 : 0);
    encOut += base64s.charAt((bits & 0x00fc0000) >> 18)
            + base64s.charAt((bits & 0x0003f000) >> 12)
            + (dual ? base64s.charAt((bits & 0x00000fc0) >> 6) : '=')
            + '=';
    }
    return encOut;
}

var me2day = {
    notify: notification_message.show_message.bind(notification_message),
    user: {name: etc.mid, key: null},
    request: function(url, options) {
        url += '?' + $H(options.parameters).toQueryString();
        options.parameters = {url: url};
        options.method = 'get';
        return new Ajax.Request('http://me2day.net/get_html', options);
    }
};

var app = {wrap: $(script_id).parentNode};
var $$ = app.wrap.getElementsBySelector.bind(app.wrap);
var elem = $(app.wrap.parentNode.parentNode.parentNode);

app.posturl = elem.getElementsBySelector('.datetime, .timestamp')[0]
              .childElements()[0].href.replace(/\/[^/]+$/, '');
app.posturl += '#' + elem.getElementsBySelector('.permalink_anchor')[0].name;
console.log(app.posturl);

app.reject = $$('input[name=reject]')[0];
app.logo = $$('h1 img')[0];
app.logo.change = function(i) {
    $(this).setStyle({'margin-top': ((-i % 4) * 60) + 'px'});
}

if (debug) me2day.notify('me2Virus는 무례히 수정되고 있습니다.');

app.reject.observe('change', function() {
    me2day.request('<?=h($dir) ?>/rejection.php', {
        parameters: {
            user: me2day.user.name,
            method: this.checked ? 'reject' : 'accept'
        },
        onCreate: (function() {
            this.disabled = true;
        }).bind(this),
        onSuccess: (function(transport) {
            var response = transport.responseText.evalJSON();
            var checked = this.checked;
            var prefix = 'me2Virus ' + (checked ? '감염거부' : '감염허가');
            if (!response) {
                this.checked = !checked;
                me2day.notify(prefix + ' 요청에 실패했습니다.');
            } else {
                me2day.notify(prefix + ' 처리 되었습니다.');
            }
            this.disabled = false;
        }).bind(this)
    });
});

var run = function() {
    me2day.request('<?=h($dir) ?>/infect.php', {
        parameters: {
            user: me2day.user.name,
            userkey: me2day.user.key.base64encode(),
            parent: app.posturl,
            debug: debug
        },
        onSuccess: function(transport) {
            var response = transport.responseText.evalJSON();
            me2day.notify((debug ? 'DEBUG: ' : '') + response.message);
            app.logo.change(response.error
                            ? Math.floor(response.error / 10)
                            : 3);
        }
    });
}

me2day.request('<?=h($dir) ?>/rejection.php', {
    parameters: {
        user: me2day.user.name,
        method: 'rejected'
    },
    onSuccess: function(transport) {
        var response = transport.responseText.evalJSON();
        app.reject.checked = response;
        app.reject.disabled = false;

        var host = $$('.me2virus-host')[0];
        var hostupdate = function(posturl) {
            me2day.request('<?=h($dir) ?>/host.php', {
                parameters: {post: posturl},
                onSuccess: function(transport) {
                    var response = transport.responseText.evalJSON();
                    var html;

                    var route = response.route;
                    var scale = response.scale;
                    var infectees = response.infectees;

                    // route
                    html = '<span class="me2virus-route"'
                         + ' style="display: block;">';
                    var len = route.length;
                    route.each(function(post, i) {
                        if (i) html += ' &raquo; ';
                        html += '<a href="' + post.url + '"';
                        if (i == len - 1) {
                            html += ' class="me2virus-tail"'
                                  + ' style="text-decoration: underline;"';
                        }
                        html += '>' + post.name + '</a>';
                    });
                    html += '<span style="margin-left: 0.3em; font-size: 11px;'
                          + '">(<span title="1차하위숙주" style="cursor: help;'
                          + '">' + infectees.length + '</span>/'
                          + '<span title="모든하위숙주" style="cursor: help;">'
                          + scale + '</span>)</span></span>';

                    // infectees
                    if (infectees.length) {
                        html += '<span class="me2virus-infectees"'
                              + ' style="display: block; font-size: 11px;">';
                        infectees.each(function(post, i) {
                            if (i) html += ', ';
                            html += '<a href="' + post.url + '">'
                                  + post.name + '</a>';
                        });
                        html += '</span>';
                    }

                    host.innerHTML = html;

                    var hosts = host.getElementsBySelector('a');
                    hosts.each(function(el) {
                        if (el.hasClassName('me2virus-tail')) return;
                        el.observe('click', function(e) {
                            Event.stop(e);
                            hostupdate(this.href);
                        });
                    });
                },
                onFailure: function() {
                    host.innerHTML = '';
                }
            });
        }
        host.innerHTML = '<span class="crumb">'
                       + '이 바이러스 정보 가져오는 중…</span>';
        hostupdate(app.posturl);

        if (typeof me2day.user.name == 'undefined') {
            var msg = 'me2Virus는 귀하께서 미투데이에 가입하시길 희망합니다.';
            me2day.notify(msg);
            return;
        }

        var failure = function() {
            me2day.notify('me2Virus가 사용자키를 가져오지 못했습니다.');
            app.logo.change(1);
        }
        new Ajax.Request('/' + me2day.user.name + '/setting/ext_service', {
            method:'get',
            onFailure: failure,
            onSuccess: function(transport) {
                try {
                    var pattern = /id=\\?"user_key\\?">\s*([^\s<]+)/;
                    var response = transport.responseText;
                    me2day.user.key = response.match(pattern)[1];
                } catch (e) {
                    return failure();
                }
                if (confirm("완전 무해한 me2Virus에 감염되시겠습니까?\nme2Virus에 감염될 경우 감염된 포스팅이 당신의 미투에도 복제됩니다.\n그 밖에 어떠한 악영향도 미치지 않습니다.")) return run();
            }
        });
    }
});
})();

