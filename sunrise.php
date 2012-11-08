<?php

if ( ! defined( 'SUNRISE_LOADED' ) )
	define( 'SUNRISE_LOADED', 1 );

if ( defined( 'COOKIE_DOMAIN' ) )
	die( 'The constant "COOKIE_DOMAIN" is defined (probably in wp-config.php). Please remove or comment out that define() line.' );


//set our custom table name using the WP DB prefix
// @todo it would be lovely to remove the custom table entirely
$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';

//capture the current domain request and if it includes www, strip that out for an alternate
$requested_domain = $_SERVER['HTTP_HOST'];

/**
 * Check our mbm cache key for the requested domain to see if we already know of a valid
 * blog ID. We check to see if it's valid via absint() before continuing and grab it from
 * the database if really necessary.
 */
$domain_mapping_blog_id = wp_cache_get( 'mbm-' . $requested_domain );

if ( 0 == absint( $domain_mapping_blog_id ) ) {

	$alternate_domain = preg_replace( '|^www\.|', '', $requested_domain );

	if ( $requested_domain !== $alternate_domain )
		$where = $wpdb->prepare( 'domain IN ( %s, %s )', $requested_domain, $alternate_domain );
	else
		$where = $wpdb->prepare( 'domain = %s', $requested_domain );

	//suppress errors and capture current suppression setting
	$suppression = $wpdb->suppress_errors();

	$domain_mapping_blog_id = $wpdb->get_var( "SELECT blog_id FROM $wpdb->dmtable WHERE {$where} ORDER BY CHAR_LENGTH(domain) DESC LIMIT 1" );

	//reset error suppression setting
	$wpdb->suppress_errors( $suppression );

}

/**
 * If we found a blog_id to match the domain above, then we turn to WordPress to get the
 * remaining bits of info from the standard wp_blogs and wp_site tables. Then we squash
 * it all together in the $current_site, $current_blog, $site_id, and $blog_id globals so
 * that it is available for the remaining operations on this page request.
 */
if( $domain_mapping_blog_id ) {

	/**
	 * We have a successful blog ID to map to, so we should store this in cache for use
	 * at a later time. We can use the alternate domain as part of the key because
	 * www is nonsense.
	 * @todo determine if this is a valid key strategy
	 */
	wp_cache_set( 'mbm-' . $requested_domain, $domain_mapping_blog_id );

	$current_blog = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->blogs WHERE blog_id = %d LIMIT 1", $domain_mapping_blog_id ) );

	//modify the WP DB's version of the domain and path to our domain mapped version
	$current_blog->domain = $requested_domain;
	$current_blog->path = '/';

	//set the blog_id and site_id globals that WordPress expects
	$blog_id = $domain_mapping_blog_id;
	$site_id = $current_blog->site_id;

	$current_site = $wpdb->get_row( $wpdb->prepare( "SELECT * from $wpdb->site WHERE id = %d LIMIT 0,1", $site_id ) );

	//add blog_id to the current_site object (necessary)
	$current_site->blog_id = $blog_id;

	// @todo set a cache key here

	//have the site name attached to the current_site object (necessary)
	$current_site = get_current_site_name( $current_site );

	define( 'COOKIE_DOMAIN', $requested_domain );
	define( 'DOMAIN_MAPPING', 1 );

}