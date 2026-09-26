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
     * Build the siteurl for a new site.
     *
     * WordPress lives in /wp, so the main site of a network and every site in a
     * subdomain install get https://domain/wp. Sites in a subfolder install
     * (https://domain/slug) must not have /wp in their siteurl, as
     * /slug/wp/wp-admin/ is not routable and the admin then resolves to the main site.
     */
    public function buildSiteUrl($domain, $sitePath, $networkPath, $isSubdomainInstall)
    {
        $sitePath = trim($sitePath, '/');
        $isMainSite = $sitePath === trim($networkPath, '/');

        if ($isSubdomainInstall || $isMainSite) {
            return 'https://' . $domain . '/wp';
        }

        return 'https://' . $domain . '/' . $sitePath;
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
        $siteUrl = $this->buildSiteUrl($blog->domain, $blog->path, $network->path, is_subdomain_install());
        $wpdb->query("UPDATE $wpdb->options SET option_value = '$siteUrl' WHERE option_name = 'siteurl'");
        $wpdb->query("UPDATE $wpdb->options SET option_value = '$uploadPath' WHERE option_name = 'upload_path'");
        $wpdb->query("UPDATE $wpdb->options SET option_value = '$uploadUrlPath' WHERE option_name = 'upload_url_path'");

        if ($switch) {
            restore_current_blog();
        }
    }
}

new \MultiNetworkUrls\MultiNetworkUrls();