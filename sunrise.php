<?php

if ( ! defined( 'SUNRISE_LOADED' ) )
	define( 'SUNRISE_LOADED', 1 );

if ( defined( 'COOKIE_DOMAIN' ) )
	die( 'The constant "COOKIE_DOMAIN" is defined (probably in wp-config.php). Please remove or comment out that define() line.' );

//set our custom table name using the WP DB prefix
$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';

//capture the current domain request and if it includes www, strip that out for an alternate
$requested_domain = $_SERVER[ 'HTTP_HOST' ];
$alternate_domain = preg_replace( '|^www\.|', '', $requested_domain );

if ( $requested_domain != $alternate_domain )
	$where = $wpdb->prepare( 'domain IN ( %s, %s )', $requested_domain, $alternate_domain );
else
	$where = $wpdb->prepare( 'domain = %s', $requested_domain );

//suppress errors and capture current suppression setting
$suppression = $wpdb->suppress_errors();

//get the blog_id from our custom SQL tables that matches the domain requested
$domain_mapping_blog_id = $wpdb->get_var( $wpdb->prepare( "SELECT blog_id FROM $wpdb->dmtable WHERE {$where} ORDER BY CHAR_LENGTH(domain) DESC LIMIT 1" ) );

//reset error suppression setting
$wpdb->suppress_errors( $suppression );

/**
 * If we found a blog_id to match the domain above, then we turn to WordPress to get the
 * remaining bits of info from the standard wp_blogs and wp_site tables. Then we squash
 * it all together in the $current_site and $current_blog globals so that it is available
 * for the remaining operations on this page request.
 */
if( $domain_mapping_blog_id ) {

	$current_blog = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->blogs WHERE blog_id = %d LIMIT 1", $domain_mapping_blog_id ) );

	//modify the WP DB's version of the domain and path to our domain mapped version
	$current_blog->domain = $requested_domain;
	$current_blog->path = '/';

	$blog_id = $domain_mapping_blog_id;
	$site_id = $current_blog->site_id;

	define( 'COOKIE_DOMAIN', $requested_domain );

	$current_site = get_current_site_name( $current_site );

	define( 'DOMAIN_MAPPING', 1 );
}