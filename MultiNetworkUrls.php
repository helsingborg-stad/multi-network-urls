<?php
/*
Plugin Name:    WPMU Multi Network urls
Description:    Modifies *_*_options value on siteurl and upload_path to match our custom setup with wordpress in subfolder when creating network or new blog.
Version:        1.0
Author:         Joel Bernerman
*/

namespace MultiNetworkUrls;

class MultiNetworkUrls
{
    public function __construct()
    {
        add_action('wp_initialize_site', array($this, 'fixSiteUrl'), 9999, 2);
        add_action('add_network', array($this, 'fixUploadUrl'), 9999, 2);
    }

    /**
     * Adapt upload path when creating new blog.
     */
    public function fixUploadUrl($networkId, $args)
    {
        $blodId = $args['network_meta']['main_site'];
        $switch = false;
        if (get_current_blog_id() !== $blodId) {
            $switch = true;
            switch_to_blog($blodId);
        }

        $uploadSlug = '/uploads/networks/' . $networkId;
        $uploadPath = WP_CONTENT_DIR . $uploadSlug;
        $uploadUrlPath = 'https://' . $args['domain'] . '/wp-content' . $uploadSlug;
        global $wpdb;
        $wpdb->query("UPDATE $wpdb->options SET option_value = '$uploadPath' WHERE option_name = 'upload_path'");
        $wpdb->query("UPDATE $wpdb->options SET option_value = '$uploadUrlPath' WHERE option_name = 'upload_url_path'");

        if ($switch) {
            restore_current_blog();
        }
    }

    /**
     * Adapt upload path when creating new network.
     */
    public function fixSiteUrl($blog, $args)
    {
        $switch = false;
        if (get_current_blog_id() !== $blog->id) {
            $switch = true;
            switch_to_blog($blog->id);
        }

        $network = get_network();
        $networkId = $network->id;

        $uploadSlug = '/uploads/networks/' . $networkId;
        $uploadPath = WP_CONTENT_DIR . $uploadSlug;
        $uploadUrlPath = 'https://' . $blog->domain . '/wp-content' . $uploadSlug;

        global $wpdb;
        $siteUrl = 'https://' . $blog->domain . '/wp';
        $wpdb->query("UPDATE $wpdb->options SET option_value = '$siteUrl' WHERE option_name = 'siteurl'");
        $wpdb->query("UPDATE $wpdb->options SET option_value = '$uploadPath' WHERE option_name = 'upload_path'");
        $wpdb->query("UPDATE $wpdb->options SET option_value = '$uploadUrlPath' WHERE option_name = 'upload_url_path'");

        if ($switch) {
            restore_current_blog();
        }
    }
}

new \MultiNetworkUrls\MultiNetworkUrls();