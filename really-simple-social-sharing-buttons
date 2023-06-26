<?php
/**
 * Plugin Name: Really Simple Social Sharing Buttons
 * Description: Inserts share buttons with Font Awesome icons and text for popular social media sites at the top and bottom of the content.
 * Version: 1.0
 * Author: RiotRequest
 * License: GPL2
 */

// Enqueue Font Awesome stylesheet
function enqueue_font_awesome() {
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array(), '4.7.0');
}
add_action('wp_enqueue_scripts', 'enqueue_font_awesome');

// Add the share buttons to the content
function add_social_share_buttons($content) {
    if (is_singular('post')) {
        $post_title = urlencode(get_the_title());
        $post_permalink = urlencode(get_permalink());
        
        // Social media sites and their share URLs with icons, text, and colors
        $social_sites = array(
            'Facebook' => array(
                'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . $post_permalink,
                'icon' => 'fa fa-facebook',
                'text' => 'Facebook',
                'color' => '#3b5998'
            ),
            'Twitter' => array(
                'url' => 'https://twitter.com/share?url=' . $post_permalink . '&text=' . $post_title,
                'icon' => 'fa fa-twitter',
                'text' => 'Twitter',
                'color' => '#1da1f2'
            ),
            'LinkedIn' => array(
                'url' => 'https://www.linkedin.com/shareArticle?url=' . $post_permalink . '&title=' . $post_title,
                'icon' => 'fa fa-linkedin',
                'text' => 'LinkedIn',
                'color' => '#0077b5'
            ),
        );
        
        $buttons = '';

        // Generate share buttons HTML with Font Awesome icons, text, colors, and additional styling
        foreach ($social_sites as $site => $data) {
            $url = $data['url'];
            $icon = $data['icon'];
            $text = $data['text'];
            $color = $data['color'];
            
            $buttons .= '<a href="' . $url . '" target="_blank" rel="nofollow noopener" style="background-color:' . $color . '; padding: 8px 12px; color: #fff; text-decoration: none; display: inline-block; margin-right: 5px; border-radius: 4px;"><i class="' . $icon . '"></i> ' . $text . '</a> ';
        }
        
        // Insert share buttons at the top and bottom of the content with a margin
        $share_buttons = '<div class="social-share-buttons" style="margin-bottom: 15px;">' . $buttons . '</div>';
        $content = $share_buttons . $content . $share_buttons;
    }

    return $content;
}
add_filter('the_content', 'add_social_share_buttons');
