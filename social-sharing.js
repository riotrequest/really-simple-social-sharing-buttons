/* Progressive enhancement: ordinary sharing links work without JavaScript. */
(() => {
    'use strict';

    document.querySelectorAll('[data-rssb]').forEach((group) => {
        group.querySelectorAll('[data-rssb-action]').forEach((button) => {
            button.hidden = button.dataset.rssbAction === 'native' &&
                !(window.isSecureContext && typeof navigator.share === 'function');
        });
        group.hidden = !group.querySelector('.rssb-button:not([hidden])');
    });

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-rssb-action]');
        if (!button || button.disabled) return;
        const group = button.closest('[data-rssb]');
        const status = group.querySelector('.rssb-status');
        const fallback = group.querySelector('.rssb-copy-fallback');
        status.textContent = '';
        fallback.hidden = true;
        button.disabled = true;

        try {
            if (button.dataset.rssbAction === 'copy') {
                await navigator.clipboard.writeText(group.dataset.url);
                status.textContent = group.dataset.copied;
                button.classList.add('is-copied');
                window.setTimeout(() => button.classList.remove('is-copied'), 2000);
            } else {
                await navigator.share({ title: group.dataset.title, url: group.dataset.url });
            }
        } catch (error) {
            if (button.dataset.rssbAction === 'copy') {
                status.textContent = group.dataset.copyHelp;
                fallback.hidden = false;
                fallback.focus();
                fallback.select();
            } else if (error.name !== 'AbortError') {
                status.textContent = group.dataset.shareError;
            }
        } finally {
            button.disabled = false;
        }
    });
})();
