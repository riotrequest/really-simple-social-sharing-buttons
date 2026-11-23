(function ($) {
    'use strict';

    const list = document.querySelector('.rssb-network-list');
    if (!list) return;

    function updateArrows() {
        for (const row of list.children) {
            row.querySelector('[data-rssb-move="up"]').setAttribute('aria-disabled', row === list.firstElementChild);
            row.querySelector('[data-rssb-move="down"]').setAttribute('aria-disabled', row === list.lastElementChild);
        }
    }

    function announceMove(row) {
        updateArrows();
        const position = Array.from(list.children).indexOf(row) + 1;
        wp.a11y.speak(wp.i18n.sprintf(list.dataset.positionMessage, row.dataset.channelName, position, list.children.length));
    }

    $(list).sortable({
        axis: 'y',
        handle: '.rssb-network-handle',
        placeholder: 'rssb-network-placeholder',
        forcePlaceholderSize: true,
        update(event, ui) {
            announceMove(ui.item[0]);
        },
    });

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-rssb-move]');
        if (!button || button.getAttribute('aria-disabled') === 'true') return;

        const row = button.closest('.rssb-network-option');
        if (button.dataset.rssbMove === 'up') {
            row.previousElementSibling.before(row);
        } else {
            row.nextElementSibling.after(row);
        }
        button.focus();
        announceMove(row);
    });

    updateArrows();
    document.querySelector('[data-rssb-order-help]').hidden = false;
    for (const control of list.querySelectorAll('.rssb-network-handle, .rssb-order-controls')) {
        control.hidden = false;
    }
})(jQuery);
