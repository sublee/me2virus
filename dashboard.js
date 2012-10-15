document.write('<div style="clear: both;"></div>');
events = {};

(function($) {
    var form = $('.me2virus-initial');
    var icon = form.find('[name=icon]');

    form.find(':submit').attr('disabled', true);

    var imgchecker = $('<img onload="events.updateimgstate(true)"'
                         + ' onerror="events.updateimgstate(false)"'
                         + ' style="position: absolute; top: -9999px;"/>');
    imgchecker.appendTo(document.body);

    events.imgcheck = function(src) {
        imgchecker.attr('src', src);
    }
    events.updateimgstate = function(state) {
        icon[(state ? 'remove' : 'add') + 'Class']('invalid');
    }

    events.formcheck = function(form) {
        var self = $(form), limit, valid = true;
        if (!form.nodeName.match(/form/i)) {
            limit = self.context;
            self = self.parents('form');
        }
        self.find(':text, :password').each(function() {
            var self = $(this);
            self[(self.val() ? 'remove' : 'add') + 'Class']('invalid');
            if (!self.val()) valid = false;
            if (this == limit) return false;
        });

        !limit && !valid && [
            alert('빠진 항목을 채워주세요.'), self.find('.invalid:eq(0)').focus()
        ];
        return valid;
    }

    events.submittable = function(form, submittable) {
        $(form).find(':submit').attr('disabled', !submittable);
    }

    var visualize = $('.me2virus-visualize');
    var timer;
    events.inputvisualizer = function(a) {
        timer = setTimeout(function() {
            visualize.find('[name=post]')
                     .css('background-color', '#ff9')
                     .animate({backgroundColor: '#fff'})
                     .val($(a).attr('href'));
        }, 250);
    }
    events.cancelinputvisualizer = function() {
        clearTimeout(timer);
    }

    events.correctaction = function(form) {
        form = $(form);
        var action = form.attr('action');
        $.each(action.match(/({[^}]+})/g), function() {
            action = action.replace(this,
                form.find('[name=' + this.slice(1, -1) + ']')
                    .attr('disabled', true).val()
            );
        });
        form.attr('action', action);
    }
})(jQuery);
