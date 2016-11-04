<?php
//security check
defined( 'WP_UNINSTALL_PLUGIN' ) or die( 'Cheatin&#8217; uh?' );

//delete Options
delete_site_option('wp_instagram_wall_clientid');
delete_site_option('wp_instagram_wall_clientsecret');
delete_site_option('wp_instagram_wall_token');
delete_site_option('wp_instagram_wall_userid');
delete_site_option('wp_instagram_wall_template');