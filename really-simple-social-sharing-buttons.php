<?php
/**
 * Plugin Name: Really Simple Social Sharing Buttons
 * Description: Beautiful, lightweight sharing buttons. Local icons, modern networks, and no tracking scripts.
 * Version: 3.0.0
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Author: RiotRequest
 * Plugin URI: https://github.com/riotrequest/really-simple-social-sharing-buttons
 * License: GPL v2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: really-simple-social-sharing-buttons
 */

defined('ABSPATH') || exit;
define('RSSB_VERSION', '3.0.0');

/** Keep legacy option keys so upgrading never silently changes selected networks. */
function rssb_networks() {
    return array(
        'Facebook'    => array('Facebook', '#1760cf', 'facebook'),
        'Twitter'     => array('X', '#18212f', 'x'),
        'Bluesky'     => array('Bluesky', '#0866db', 'butterfly'),
        'WhatsApp'    => array('WhatsApp', '#087d41', 'chat'),
        'LinkedIn'    => array('LinkedIn', '#075ca6', 'linkedin'),
        'Reddit'      => array('Reddit', '#c43800', 'reddit'),
        'Pinterest'   => array('Pinterest', '#b60024', 'pin'),
        'Telegram'    => array('Telegram', '#076a9b', 'send'),
        'Email'       => array(__('Email', 'really-simple-social-sharing-buttons'), '#475569', 'mail'),
        'Copy'        => array(__('Copy link', 'really-simple-social-sharing-buttons'), '#475569', 'link'),
        'Native'      => array(__('More options', 'really-simple-social-sharing-buttons'), '#475569', 'share'),
        'Gab'         => array('Gab', '#217b16', 'chat'),
        'Gettr'       => array('Gettr', '#c72031', 'bolt'),
        'TruthSocial' => array('Truth Social', '#5442b5', 'chat'),
    );
}

function rssb_default_networks() {
    return array('Facebook', 'Twitter', 'Bluesky', 'WhatsApp', 'LinkedIn', 'Email', 'Copy');
}

function rssb_sanitize_networks($input) {
    if (!is_array($input)) {
        return array();
    }
    return array_values(array_unique(array_intersect(
        array_filter($input, 'is_string'), array_keys(rssb_networks())
    )));
}

function rssb_sanitize_settings($input) {
    $input = is_array($input) ? $input : array();
    $choices = array(
        'position' => array('both', 'above', 'below', 'manual'),
        'style'    => array('soft', 'solid', 'minimal'),
        'theme'    => array('auto', 'light', 'dark'),
        'labels'   => array('auto', 'visible', 'hidden'),
    );
    $settings = array();
    foreach ($choices as $key => $values) {
        $settings[$key] = isset($input[$key]) && in_array($input[$key], $values, true) ? $input[$key] : $values[0];
    }
    return $settings;
}

function rssb_settings() {
    return rssb_sanitize_settings(get_option('social_sharing_settings', array()));
}

/** SVGs are fixed local artwork; no remote icon library or font is loaded. */
function rssb_icon($name) {
    $paths = array(
        'facebook'  => '<path d="M14 21v-8h3l.5-4H14V7c0-1 .5-1.5 1.5-1.5H18V2.2c-.8-.1-1.8-.2-3-.2-3 0-5 1.8-5 5v2H7v4h3v8"/>',
        'x'         => '<path d="m4 3 16 18h-5L4 3h5l11 18M20 3 4 21"/>',
        'butterfly' => '<path d="M12 11C9 5 3 2 3 6c0 5 1 7 5 7-6 1-3 7 0 6 2-1 3-3 4-5 1 2 2 4 4 5 3 1 6-5 0-6 4 0 5-2 5-7 0-4-6-1-9 5Z"/>',
        'chat'      => '<path d="M21 11.5a9 9 0 0 1-13 8L3 21l1.5-5A9 9 0 1 1 21 11.5Z"/><path d="M8 7c0 5 4 9 9 9l1-3-3-1-1 1c-2-1-3-2-3-3l1-1-1-3Z"/>',
        'linkedin'  => '<path d="M4 9v11M4 4v.1M10 20V9h4v2c1-3 6-3 6 2v7M14 11v9"/>',
        'reddit'    => '<path d="m12 10 2-7 5 1"/><circle cx="20" cy="4" r="2"/><ellipse cx="12" cy="15" rx="9" ry="6"/><path d="M4 11C0 7 0 16 3 15m17-4c4-4 4 5 1 4m-13 2c2 2 6 2 8 0M8 14h.1M16 14h.1"/>',
        'pin'       => '<path d="m9 21 3-13m-2 7c6 4 10-3 8-8C15 0 4 4 5 11c0 2 1 3 2 4m5-7c-3 0-4 5-1 6"/>',
        'send'      => '<path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13"/>',
        'mail'      => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 6 9 7 9-7"/>',
        'link'      => '<path d="m10 13 4-4m-6 6-2 2a4 4 0 0 0 6 6l3-3a4 4 0 0 0 0-6M16 9l2-2a4 4 0 0 0-6-6L9 4a4 4 0 0 0 0 6" transform="scale(.9) translate(1 1)"/>',
        'share'     => '<path d="M12 15V3m-4 4 4-4 4 4M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/>',
        'bolt'      => '<path d="m13 2-9 12h7l-1 8 10-12h-7l1-8Z"/>',
    );
    $path = isset($paths[$name]) ? $paths[$name] : $paths['share'];
    return '<svg class="rssb-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

/** Share URLs carry public post details, never credentials or access tokens. */
function rssb_share_url($network, $url, $title, $image = '') {
    $u = rawurlencode($url);
    $t = rawurlencode($title);
    $text = rawurlencode($title . ' ' . $url);
    switch ($network) {
        case 'Facebook':    return 'https://www.facebook.com/sharer/sharer.php?u=' . $u;
        case 'Twitter':     return 'https://twitter.com/intent/tweet?url=' . $u . '&text=' . $t;
        case 'Bluesky':     return 'https://bsky.app/intent/compose?text=' . $text;
        case 'WhatsApp':    return 'https://wa.me/?text=' . $text;
        case 'LinkedIn':    return 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u;
        case 'Reddit':      return 'https://www.reddit.com/submit?url=' . $u . '&title=' . $t;
        case 'Pinterest':  return $image ? 'https://pinterest.com/pin/create/button/?url=' . $u . '&media=' . rawurlencode($image) . '&description=' . $t : '';
        case 'Telegram':   return 'https://t.me/share/url?url=' . $u . '&text=' . $t;
        case 'Gab':        return 'https://gab.com/compose?url=' . $u;
        case 'Gettr':      return 'https://gettr.com/share?text=' . $t . '&url=' . $u;
        case 'TruthSocial':return 'https://truthsocial.com/share?url=' . $u;
        case 'Email':      return 'mailto:?subject=' . $t . '&body=' . $u;
        default:           return '';
    }
}

function rssb_markup($url, $title, $image, $networks, $settings) {
    $buttons = '';
    $has_links = false;
    foreach (rssb_sanitize_networks($networks) as $network) {
        list($label, $color, $icon) = rssb_networks()[$network];
        $action = in_array($network, array('Copy', 'Native'), true);
        $href = $action ? '' : rssb_share_url($network, $url, $title, $image);
        if (!$action && !$href) {
            continue;
        }
        $class = 'rssb-button social-share-button share-' . strtolower($network);
        if ($action) {
            $attributes = 'type="button" data-rssb-action="' . strtolower($network) . '" hidden';
            $accessible = $label;
        } else {
            $has_links = true;
            $attributes = 'href="' . esc_url($href) . '"';
            if ('Email' !== $network) {
                $attributes .= ' target="_blank" rel="noopener noreferrer nofollow"';
            }
            /* translators: %s: social network name. */
            $accessible = 'Email' === $network ? __('Share by email', 'really-simple-social-sharing-buttons') : sprintf(__('Share on %s (opens in a new tab)', 'really-simple-social-sharing-buttons'), $label);
        }
        $tag = $action ? 'button' : 'a';
        $buttons .= '<' . $tag . ' class="' . esc_attr($class) . '" style="--rssb-accent:' . esc_attr($color) . '" aria-label="' . esc_attr($accessible) . '" title="' . esc_attr($label) . '" ' . $attributes . '>' . rssb_icon($icon) . '<span class="rssb-label social-share-text">' . esc_html($label) . '</span></' . $tag . '>';
    }
    if (!$buttons) {
        return '';
    }
    $classes = 'rssb social-share-buttons rssb--' . $settings['style'] . ' rssb--' . $settings['theme'] . ' rssb-labels--' . $settings['labels'];
    return '<div class="' . esc_attr($classes) . '" role="group" aria-label="' . esc_attr__('Share this post', 'really-simple-social-sharing-buttons') . '" data-rssb data-url="' . esc_url($url) . '" data-title="' . esc_attr($title) . '" data-copied="' . esc_attr__('Link copied!', 'really-simple-social-sharing-buttons') . '" data-copy-help="' . esc_attr__('Copy the selected link below.', 'really-simple-social-sharing-buttons') . '" data-share-error="' . esc_attr__('Sharing is unavailable. Try another button.', 'really-simple-social-sharing-buttons') . '"' . ($has_links ? '' : ' hidden') . '><span class="rssb-heading">' . esc_html__('Good reads travel.', 'really-simple-social-sharing-buttons') . '<span class="rssb-heading-note">' . esc_html__('Share this post', 'really-simple-social-sharing-buttons') . '</span></span><div class="rssb-actions">' . $buttons . '</div><span class="rssb-status" role="status" aria-live="polite" aria-atomic="true"></span><input class="rssb-copy-fallback" type="text" readonly hidden aria-label="' . esc_attr__('Link to copy', 'really-simple-social-sharing-buttons') . '" value="' . esc_url($url) . '"></div>';
}

function rssb_render_buttons($post_id = 0) {
    $post = get_post($post_id ?: get_the_ID());
    if (!$post || !is_post_publicly_viewable($post) || !empty($post->post_password)) {
        return '';
    }
    $title = html_entity_decode(wp_strip_all_tags(get_the_title($post)), ENT_QUOTES, get_bloginfo('charset'));
    return rssb_markup(
        get_permalink($post), $title, get_the_post_thumbnail_url($post, 'full'),
        get_option('social_sharing_networks', rssb_default_networks()), rssb_settings()
    );
}

function rssb_filter_content($content) {
    if (is_admin() || is_feed() || !is_singular('post') || !in_the_loop() || !is_main_query() || post_password_required()) {
        return $content;
    }
    $position = rssb_settings()['position'];
    // An explicit shortcode owns placement, so automatic placement cannot duplicate it.
    if ('manual' === $position || has_shortcode(get_post()->post_content, 'rssb_share')) {
        return $content;
    }
    $buttons = rssb_render_buttons();
    return ('below' !== $position ? $buttons : '') . $content . ('above' !== $position ? $buttons : '');
}
add_filter('the_content', 'rssb_filter_content', 20);

function rssb_shortcode() {
    return is_feed() ? '' : rssb_render_buttons();
}
add_shortcode('rssb_share', 'rssb_shortcode');

function rssb_enqueue_assets() {
    wp_enqueue_style('rssb', plugins_url('social-sharing-styles.css', __FILE__), array(), RSSB_VERSION);
    wp_enqueue_script('rssb', plugins_url('social-sharing.js', __FILE__), array(), RSSB_VERSION, array('in_footer' => true, 'strategy' => 'defer'));
}

function rssb_frontend_assets() {
    if (is_singular()) {
        rssb_enqueue_assets();
    }
}
add_action('wp_enqueue_scripts', 'rssb_frontend_assets');

function rssb_admin_menu() {
    add_menu_page(__('Social Sharing', 'really-simple-social-sharing-buttons'), __('Social Sharing', 'really-simple-social-sharing-buttons'), 'manage_options', 'social_sharing', 'rssb_admin_page', 'dashicons-share', 81);
}
add_action('admin_menu', 'rssb_admin_menu');

function rssb_admin_assets($hook) {
    if ('toplevel_page_social_sharing' === $hook) {
        rssb_enqueue_assets();
    }
}
add_action('admin_enqueue_scripts', 'rssb_admin_assets');

function rssb_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to manage these settings.', 'really-simple-social-sharing-buttons'));
    }
    $saved = false;
    if (isset($_POST['rssb_save'])) {
        check_admin_referer('rssb_save_settings', 'rssb_nonce');
        $networks = isset($_POST['networks']) ? wp_unslash($_POST['networks']) : array();
        $settings = isset($_POST['settings']) ? wp_unslash($_POST['settings']) : array();
        update_option('social_sharing_networks', rssb_sanitize_networks($networks));
        update_option('social_sharing_settings', rssb_sanitize_settings($settings));
        $saved = true;
    }
    $networks = rssb_sanitize_networks(get_option('social_sharing_networks', rssb_default_networks()));
    $settings = rssb_settings();
    $fields = array(
        'position' => array(__('Placement', 'really-simple-social-sharing-buttons'), array('both' => __('Above & below posts', 'really-simple-social-sharing-buttons'), 'above' => __('Above posts', 'really-simple-social-sharing-buttons'), 'below' => __('Below posts', 'really-simple-social-sharing-buttons'), 'manual' => __('Manual / shortcode only', 'really-simple-social-sharing-buttons'))),
        'style' => array(__('Button style', 'really-simple-social-sharing-buttons'), array('soft' => __('Soft outline', 'really-simple-social-sharing-buttons'), 'solid' => __('Solid color', 'really-simple-social-sharing-buttons'), 'minimal' => __('Minimal', 'really-simple-social-sharing-buttons'))),
        'theme' => array(__('Color theme', 'really-simple-social-sharing-buttons'), array('auto' => __('Follow device', 'really-simple-social-sharing-buttons'), 'light' => __('Light', 'really-simple-social-sharing-buttons'), 'dark' => __('Dark', 'really-simple-social-sharing-buttons'))),
        'labels' => array(__('Button labels', 'really-simple-social-sharing-buttons'), array('auto' => __('Hide labels on small screens', 'really-simple-social-sharing-buttons'), 'visible' => __('Always show labels', 'really-simple-social-sharing-buttons'), 'hidden' => __('Icons only', 'really-simple-social-sharing-buttons'))),
    );
    ?>
    <div class="wrap rssb-settings">
        <header class="rssb-admin-hero">
            <span class="rssb-admin-kicker">RIOTREQUEST <span> / </span> <?php esc_html_e('WORDPRESS PLUGINS', 'really-simple-social-sharing-buttons'); ?></span>
            <h1><?php esc_html_e('Small plugin. Big share energy.', 'really-simple-social-sharing-buttons'); ?></h1>
            <p><?php esc_html_e('Your content deserves to get around. Make it effortless.', 'really-simple-social-sharing-buttons'); ?></p>
            <span class="rssb-version">v<?php echo esc_html(RSSB_VERSION); ?></span>
        </header>
        <?php if ($saved) : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Saved. Your sharing buttons are ready.', 'really-simple-social-sharing-buttons'); ?></p></div>
        <?php endif; ?>
        <form method="post" class="rssb-admin-layout">
            <?php wp_nonce_field('rssb_save_settings', 'rssb_nonce'); ?>
            <div class="rssb-admin-card">
                <fieldset>
                    <legend><span class="rssb-step">01</span> <?php esc_html_e('Pick your channels', 'really-simple-social-sharing-buttons'); ?></legend>
                    <p class="description"><?php esc_html_e('Choose where readers can share. Leave everything unchecked to switch the buttons off.', 'really-simple-social-sharing-buttons'); ?></p>
                    <div class="rssb-network-grid">
                        <?php foreach (rssb_networks() as $key => $data) : ?>
                            <label class="rssb-network-option">
                                <input type="checkbox" name="networks[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $networks, true)); ?>>
                                <?php echo rssb_icon($data[2]); // Fixed, local SVG markup. ?>
                                <span><?php echo esc_html($data[0]); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <fieldset class="rssb-appearance">
                    <legend><span class="rssb-step">02</span> <?php esc_html_e('Make it yours', 'really-simple-social-sharing-buttons'); ?></legend>
                    <div class="rssb-field-grid">
                        <?php foreach ($fields as $key => $field) : ?>
                            <label for="rssb-<?php echo esc_attr($key); ?>"><?php echo esc_html($field[0]); ?>
                                <select id="rssb-<?php echo esc_attr($key); ?>" name="settings[<?php echo esc_attr($key); ?>]">
                                    <?php foreach ($field[1] as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($settings[$key], $value); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <div class="rssb-save-row">
                    <button type="submit" name="rssb_save" value="1" class="button button-primary rssb-save"><?php esc_html_e('Save your setup', 'really-simple-social-sharing-buttons'); ?></button>
                    <span><?php esc_html_e('No accounts. No API keys. Just sharing.', 'really-simple-social-sharing-buttons'); ?></span>
                </div>
            </div>
            <aside class="rssb-admin-aside">
                <h2><?php esc_html_e('The look', 'really-simple-social-sharing-buttons'); ?></h2>
                <p><?php esc_html_e('A preview of your saved settings. Save changes to refresh it.', 'really-simple-social-sharing-buttons'); ?></p>
                <?php echo rssb_markup(home_url('/example-post/'), __('A story worth sharing', 'really-simple-social-sharing-buttons'), '', $networks, $settings); ?>
                <div class="rssb-admin-tip">
                    <h3><?php esc_html_e('Put it exactly where you want.', 'really-simple-social-sharing-buttons'); ?></h3>
                    <p><?php esc_html_e('Use a Shortcode block in any public post or page:', 'really-simple-social-sharing-buttons'); ?></p>
                    <code>[rssb_share]</code>
                    <p><?php esc_html_e('Native sharing appears only on supported devices. Pinterest requires a featured image.', 'really-simple-social-sharing-buttons'); ?></p>
                </div>
            </aside>
        </form>
    </div>
    <?php
}
