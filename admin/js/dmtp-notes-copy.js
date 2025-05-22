// dmtp-notes-copy.js
jQuery(document).ready(function($) {
    // Initialize clipboard.js for the planning copy button, copying HTML markup
    var clipboard = new ClipboardJS('.dmtp-copy-planning-btn', {
        text: function(trigger) {
            return document.querySelector('#dmtp-planning-note-content').innerHTML;
        }
    });

    clipboard.on('success', function(e) {
        var msg = $('.dmtp-copy-planning-msg');
        if (msg.length) {
            msg.show();
            setTimeout(function(){ msg.fadeOut(300); }, 1500);
        }
        e.clearSelection();
    });

    clipboard.on('error', function(e) {
        alert('Copy failed. Please try manually.');
    });
}); 