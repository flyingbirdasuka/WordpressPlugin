<?php
/**
 * Fired when the plugin is uninstalled.
 * @package streetview
 *
 **/

// If uninstall functionality not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}


// Remove the data from the database
global $wpdb;
$wpdb->query("DELETE FROM wp_posts WHERE post_type = 'streets' ");
$wpdb->query("DELETE FROM wp_postmeta WHERE post_id NOT IN(SELECT if FROM wp_posts)");
$wpdb->query("DELETE FROM wp_term_relationshios WHERE object_id NOT IN(SELECT if FROM wp_posts)");


