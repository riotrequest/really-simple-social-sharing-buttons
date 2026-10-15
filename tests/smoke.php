<?php
/** Run only against a disposable WordPress install: php tests/smoke.php /path/to/wp-load.php */
if ('cli' !== PHP_SAPI) {
    http_response_code(404);
    exit;
}
if (empty($argv[1]) || !is_file($argv[1])) {
    fwrite(STDERR, "Usage: php tests/smoke.php /path/to/disposable-wordpress/wp-load.php\n");
    exit(1);
}
require $argv[1];
require_once dirname(__DIR__) . '/really-simple-social-sharing-buttons.php';

$checks = 0;
$posts = array();
$options = array();
foreach (array('social_sharing_networks', 'social_sharing_settings') as $key) {
    $options[$key] = get_option($key, null);
}
$original_user = get_current_user_id();
$original_query = $GLOBALS['wp_query'];
$original_main = $GLOBALS['wp_the_query'];
$original_post = isset($GLOBALS['post']) ? $GLOBALS['post'] : null;
$original_request = $_REQUEST;
$original_post_data = $_POST;
$_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=social_sharing';

function rssb_test($condition, $message) {
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
    echo "PASS: $message\n";
}

function rssb_test_content($id) {
    $GLOBALS['wp_query'] = new WP_Query(array('p' => $id, 'post_type' => get_post_type($id)));
    $GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
    $GLOBALS['wp_query']->the_post();
    return apply_filters('the_content', get_the_content());
}

// Turn WordPress's permission/nonce failures into catchable test failures.
$die_filter = function () {
    return function ($message) { throw new RuntimeException(wp_strip_all_tags($message)); };
};
add_filter('wp_die_handler', $die_filter);
$admin = get_users(array('role' => 'administrator', 'number' => 1));

try {
    rssb_test(!empty($admin), 'The test install has an administrator.');
    wp_set_current_user($admin[0]->ID);
    delete_option('social_sharing_networks');
    delete_option('social_sharing_settings');

    $public = wp_insert_post(array('post_title' => 'Rock & roll — <strong>東京</strong> "quotes"', 'post_content' => '<p>A story.</p>', 'post_status' => 'publish'));
    $posts[] = $public;
    rssb_test(strpos(rssb_render_buttons($public), 'share-bluesky') !== false, 'A fresh install has useful default channels.');
    update_option('social_sharing_networks', array('Twitter', 'Gab', 'TruthSocial'));
    $legacy = rssb_render_buttons($public);
    rssb_test(strpos($legacy, '>X</span>') !== false && strpos($legacy, 'share-gab') !== false && strpos($legacy, 'share-bluesky') === false, 'Legacy selections survive and Twitter displays as X.');
    update_option('social_sharing_networks', array());
    rssb_test('' === rssb_render_buttons($public), 'An intentionally empty selection stays empty.');
    rssb_test(array('Twitter') === rssb_sanitize_networks(array('Twitter', 'Twitter', '<script>', array('bad'), 4)), 'Network input rejects unknown, duplicate, and non-string values.');
    rssb_test(array() === rssb_sanitize_networks('Twitter'), 'Malformed network input is safe.');
    rssb_test(rssb_sanitize_settings(array('theme' => array('dark'), 'style' => '<script>')) === rssb_sanitize_settings(array()), 'Display input uses strict allowlists and safe defaults.');

    $url = 'https://example.com/東京/?a=1&b="quoted"';
    $title = 'Rock & roll — 東京 "quotes"';
    parse_str(parse_url(rssb_share_url('Twitter', $url, $title), PHP_URL_QUERY), $query);
    rssb_test($query['url'] === $url && $query['text'] === $title, 'Unicode, ampersands, and quotes survive URL encoding.');
    rssb_test('' === rssb_share_url('Pinterest', $url, $title), 'Pinterest is omitted when there is no featured image.');
    parse_str(parse_url(rssb_share_url('Pinterest', $url, $title, $url), PHP_URL_QUERY), $pin);
    rssb_test($pin['media'] === $url, 'Pinterest image URLs are encoded.');
    $markup = rssb_markup($url, '"><img src=x onerror=alert(1)>', '', array('Email', 'Twitter'), rssb_settings());
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $markup);
    rssb_test(0 === $dom->getElementsByTagName('img')->length, 'Titles cannot inject HTML.');
    $anchors = $dom->getElementsByTagName('a');
    rssb_test(!$anchors->item(0)->hasAttribute('target') && false !== strpos($anchors->item(1)->getAttribute('rel'), 'noopener'), 'Email stays in context and external tabs are isolated.');

    update_option('social_sharing_networks', array('Twitter', 'Copy'));
    foreach (array('both' => 2, 'above' => 1, 'below' => 1, 'manual' => 0) as $position => $count) {
        update_option('social_sharing_settings', array('position' => $position));
        $rendered = rssb_test_content($public);
        rssb_test($count === substr_count($rendered, 'data-rssb data-url'), 'Automatic placement: ' . $position . '.');
    }
    update_option('social_sharing_settings', array('position' => 'both'));
    wp_update_post(array('ID' => $public, 'post_content' => '[rssb_share]'));
    rssb_test(1 === substr_count(rssb_test_content($public), 'data-rssb data-url'), 'Shortcodes suppress automatic duplicates.');
    $GLOBALS['wp_query']->is_feed = true;
    rssb_test('' === rssb_shortcode(), 'Shortcodes stay out of feeds.');
    $GLOBALS['wp_query']->is_feed = false;
    $GLOBALS['wp_query']->in_the_loop = false;
    rssb_test('untouched' === rssb_filter_content('untouched'), 'Content outside the loop is untouched.');

    foreach (array('draft', 'private', 'password') as $state) {
        $id = wp_insert_post(array('post_title' => 'Protected', 'post_status' => 'password' === $state ? 'publish' : $state, 'post_password' => 'password' === $state ? 'secret' : ''));
        $posts[] = $id;
        rssb_test('' === rssb_render_buttons($id), 'No sharing UI for ' . $state . ' content.');
    }
    $page = wp_insert_post(array('post_title' => 'Page', 'post_type' => 'page', 'post_status' => 'publish', 'post_content' => '[rssb_share]'));
    $posts[] = $page;
    rssb_test(1 === substr_count(rssb_test_content($page), 'data-rssb data-url'), 'Shortcodes work on public pages.');

    $_POST = array('rssb_save' => '1', 'networks' => array('Twitter', 'fake'), 'settings' => array('theme' => 'dark'));
    $_REQUEST['rssb_nonce'] = wp_create_nonce('rssb_save_settings');
    ob_start();
    rssb_admin_page();
    $admin_html = ob_get_clean();
    rssb_test(array('Twitter') === get_option('social_sharing_networks') && 'dark' === rssb_settings()['theme'], 'Authorized, nonce-verified saves sanitize and persist preferences.');
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $admin_html);
    $xpath = new DOMXPath($dom);
    rssb_test(1 === $xpath->query('//input[@value="Twitter"][@checked]')->length && 0 === $xpath->query('//input[@value="Copy"][@checked]')->length, 'Saved selections appear immediately after submitting.');
    $_POST = array('rssb_save' => '1');
    ob_start();
    rssb_admin_page();
    ob_end_clean();
    rssb_test(array() === get_option('social_sharing_networks'), 'Submitting no checkboxes disables every channel.');

    $_REQUEST['rssb_nonce'] = 'invalid';
    $_POST['networks'] = array('Facebook');
    $rejected = false;
    try { rssb_admin_page(); } catch (RuntimeException $error) { $rejected = true; }
    rssb_test($rejected && array() === get_option('social_sharing_networks'), 'An invalid nonce cannot change settings.');
    wp_set_current_user(0);
    $rejected = false;
    try { rssb_admin_page(); } catch (RuntimeException $error) { $rejected = true; }
    rssb_test($rejected && array() === get_option('social_sharing_networks'), 'An unauthorized request cannot change settings.');
    echo "\n$checks checks passed on WordPress " . get_bloginfo('version') . ".\n";
} finally {
    foreach ($posts as $id) { wp_delete_post($id, true); }
    foreach ($options as $key => $value) {
        if (null === $value) { delete_option($key); } else { update_option($key, $value); }
    }
    wp_set_current_user($original_user);
    $GLOBALS['wp_query'] = $original_query;
    $GLOBALS['wp_the_query'] = $original_main;
    $GLOBALS['post'] = $original_post;
    $_POST = $original_post_data;
    $_REQUEST = $original_request;
    remove_filter('wp_die_handler', $die_filter);
}
