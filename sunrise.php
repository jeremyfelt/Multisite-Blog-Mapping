<?php

if ( ! defined( 'SUNRISE_LOADED' ) )
	define( 'SUNRISE_LOADED', 1 );

if ( defined( 'COOKIE_DOMAIN' ) )
	die( 'The constant "COOKIE_DOMAIN" is defined (probably in wp-config.php). Please remove or comment out that define() line.' );

// let the site admin page catch the VHOST == 'no'

//set our custom table name using the WP DB prefix
$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';

$current_domain = $wpdb->escape( $_SERVER[ 'HTTP_HOST' ] );
$alternate_domain = preg_replace( '|^www\.|', '', $dm_domain );

if ( $current_domain != $alternate_domain )
	$where = $wpdb->prepare( 'domain IN ( %s, %s )', $current_domain, $alternate_domain );
else
	$where = $wpdb->prepare( 'domain = %s', $current_domain );

//suppress errors and capture current suppression setting
$suppression = $wpdb->suppress_errors();

$domain_mapping_id = $wpdb->get_var( $wpdb->prepare( "SELECT blog_id FROM $wpdb->dmtable WHERE {$where} ORDER BY CHAR_LENGTH(domain) DESC LIMIT 1" ) );

//reset suppression setting
$wpdb->suppress_errors( $suppression );

if( $domain_mapping_id ) {

	$current_blog = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->blogs WHERE blog_id = %d LIMIT 1", $domain_mapping_id ) );

	$current_blog->domain = $_SERVER[ 'HTTP_HOST' ];
	$current_blog->path = '/';

	$blog_id = $domain_mapping_id;
	$site_id = $current_blog->site_id;

	define( 'COOKIE_DOMAIN', $_SERVER[ 'HTTP_HOST' ] );

	$current_site = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->site WHERE id = %d LIMIT 0,1", $current_blog->site_id ) );

	$current_site->blog_id = $wpdb->get_var( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE domain = %s AND path = %s LIMIT 1", $current_site->domain, $current_site->path ) );

	if( function_exists( 'get_current_site_name' ) )
		$current_site = get_current_site_name( $current_site );

	define( 'DOMAIN_MAPPING', 1 );
}