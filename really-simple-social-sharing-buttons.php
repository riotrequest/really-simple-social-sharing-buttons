<?php
/**
 * Plugin Name: Social Sharing Buttons
 * Description: Inserts share buttons with Font Awesome icons and text for selected social media sites at the top and bottom of the content.
 * Version: 2.0
 * Author: RiotRequest
 * License: GPL2
 */

// Enqueue Font Awesome stylesheet
function enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '5.15.4');
}
add_action('wp_enqueue_scripts', 'enqueue_font_awesome');

// Enqueue plugin CSS styles
function enqueue_social_sharing_styles() {
    wp_enqueue_style('social-sharing-styles', plugins_url('social-sharing-styles.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'enqueue_social_sharing_styles');

// Add plugin settings page
function social_sharing_settings_page() {
    add_menu_page('Social Sharing', 'Social Sharing', 'manage_options', 'social_sharing', 'social_sharing_options');
}
add_action('admin_menu', 'social_sharing_settings_page');

// Render plugin settings page
function social_sharing_options() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Get saved social networks configuration
    $social_networks = get_option('social_sharing_networks', array());
    
    // Check if form is submitted
    if (isset($_POST['submit'])) {
        // Save the selected social networks configuration
        $selected_networks = isset($_POST['networks']) ? $_POST['networks'] : array();
        update_option('social_sharing_networks', $selected_networks);
        echo '<div class="notice notice-success"><p>Social networks configuration saved.</p></div>';
    }
    
    // Render the settings form
    ?>
    <div class="wrap">
        <h1>Social Sharing Settings</h1>
        <form method="post" action="">
            <?php
            // Retrieve the available social networks
            $social_sites = get_social_sites();
            
            foreach ($social_sites as $site => $data) {
                $checked = in_array($site, $social_networks) ? 'checked' : '';
                echo '<label><input type="checkbox" name="networks[]" value="' . esc_attr($site) . '" ' . $checked . '> ' . esc_html($data['text']) . '</label><br>';
            }
            ?>
            <br>
            <input type="submit" class="button button-primary" name="submit" value="Save Configuration">
        </form>
    </div>
    <?php
}

// Get the available social sites and their details
function get_social_sites() {
    $social_sites = array(
        'Facebook' => array(
            'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode(get_permalink()),
            'icon' => 'fa-brands fa-facebook-f',
            'text' => 'Facebook',
            'color' => '#3b5998'
        ),
        'Twitter' => array(
            'url' => 'https://twitter.com/share?url=' . urlencode(get_permalink()) . '&text=' . urlencode(get_the_title()),
            'icon' => 'fa-brands fa-twitter',
            'text' => 'Twitter',
            'color' => '#1da1f2'
        ),
        'LinkedIn' => array(
            'url' => 'https://www.linkedin.com/shareArticle?url=' . urlencode(get_permalink()) . '&title=' . urlencode(get_the_title()),
            'icon' => 'fa-brands fa-linkedin',
            'text' => 'LinkedIn',
            'color' => '#0077b5'
        ),
        'Pinterest' => array(
            'url' => 'https://pinterest.com/pin/create/button/?url=' . urlencode(get_permalink()) . '&media=' . wp_get_attachment_url(get_post_thumbnail_id()) . '&description=' . urlencode(get_the_title()),
            'icon' => 'fa-brands fa-pinterest',
            'text' => 'Pinterest',
            'color' => '#bd081c'
        ),
        'Reddit' => array(
            'url' => 'https://www.reddit.com/submit?url=' . urlencode(get_permalink()) . '&title=' . urlencode(get_the_title()),
            'icon' => 'fa-brands fa-reddit',
            'text' => 'Reddit',
            'color' => '#ff4500'
        ),
        'Telegram' => array(
            'url' => 'https://telegram.me/share/url?url=' . urlencode(get_permalink()) . '&text=' . urlencode(get_the_title()),
            'icon' => 'fa-brands fa-telegram',
            'text' => 'Telegram',
            'color' => '#0088cc'
        ),
        'Gab' => array(
            'url' => 'https://gab.com/compose?url=' . urlencode(get_permalink()),
            'icon' => 'fa-solid fa-frog',
            'text' => 'Gab',
            'color' => '#21cf7a'
        ),
        'Gettr' => array(
            'url' => 'https://gettr.com/share?text=' . urlencode(get_the_title()) . '&url=' . urlencode(get_permalink()),
            'icon' => 'fa-solid fa-fire-flame-curved',
            'text' => 'Gettr',
            'color' => '#FC223B'
        ),
        'TruthSocial' => array(
            'url' => 'https://truthsocial.com/share?url=' . urlencode(get_permalink()),
            'icon' => 'fa-solid fa-t',
            'text' => 'Truth Social',
            'color' => '#1f1657'
        ),
        'Email' => array(
            'url' => 'mailto:?subject=' . urlencode(get_the_title()) . '&body=' . urlencode(get_permalink()),
            'icon' => 'fa fa-envelope',
            'text' => 'Email',
            'color' => '#888888'
        ),
        // Add more social media sites and their URLs/icons/text/colors as needed
    );
    
    return $social_sites;
}

// Add the share buttons to the content
function add_social_share_buttons($content) {
    if (is_singular('post')) {
        $social_networks = get_option('social_sharing_networks', array());
        $social_sites = get_social_sites();
        
        $buttons = '';
        
        foreach ($social_sites as $site => $data) {
            if (in_array($site, $social_networks)) {
                $url = $data['url'];
                $icon = $data['icon'];
                $text = $data['text'];
                $color = $data['color'];
                
                $buttons .= '<a href="' . $url . '" target="_blank" rel="nofollow noopener" style="background-color:' . $color . '; padding: 8px 12px; color: #fff; text-decoration: none; display: inline-flex; align-items: center; margin-right: 5px; border-radius: 4px;"><i class="' . $icon . '"></i><span class="social-share-text">' . $text . '</span></a>';
            }
        }
        
        $share_buttons = '<div class="social-share-buttons">' . $buttons . '</div>';
        $content = $share_buttons . $content . $share_buttons;
    }
    
    return $content;
}
add_filter('the_content', 'add_social_share_buttons');
