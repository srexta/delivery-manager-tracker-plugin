// dmtp-notes-copy.js
(function($) {
    $(document).ready(function() {
        // Copy Planning Notes
        $(document).on('click', '.dmtp-copy-planning-btn', function() {
            var planning = $('#dmtp-planning-note-content').text() || '';
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(planning).then(function() {
                    var msg = $('.dmtp-copy-planning-msg');
                    if (msg.length) {
                        msg.show();
                        setTimeout(function(){ msg.fadeOut(300); }, 1500);
                    }
                }, function() {
                    window.prompt('Copy to clipboard: Ctrl+C, Enter', planning);
                });
            } else {
                window.prompt('Copy to clipboard: Ctrl+C, Enter', planning);
            }
        });
        // Copy Retrospective Notes
        $(document).on('click', '.dmtp-copy-retro-btn', function() {
            var retro = $('#dmtp-retro-note-content').text() || '';
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(retro).then(function() {
                    var msg = $('.dmtp-copy-retro-msg');
                    if (msg.length) {
                        msg.show();
                        setTimeout(function(){ msg.fadeOut(300); }, 1500);
                    }
                }, function() {
                    window.prompt('Copy to clipboard: Ctrl+C, Enter', retro);
                });
            } else {
                window.prompt('Copy to clipboard: Ctrl+C, Enter', retro);
            }
        });
    });
})(jQuery); 