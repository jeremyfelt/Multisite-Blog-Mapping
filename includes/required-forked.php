<?php

add_action( 'network_admin_menu', 'dm_network_pages' );
/**
 * Adds the Domain Mapping settings page to control how to respond to
 * different page views, etc...
 */
function dm_network_pages() {
	add_submenu_page( 'settings.php', 'Domain Mapping', 'Domain Mapping', 'manage_options', 'dm_admin_page', 'dm_admin_page' );
}

function dm_admin_page() {
	global $current_site;

	if ( ! is_super_admin() )
		return false;

	dm_sunrise_warning();

	if ( '/' != $current_site->path )
		wp_die( sprintf( __( '<strong>Warning!</strong> This plugin will only work if WordPress is installed in the root directory of your webserver. It is currently installed in &#8217;%s&#8217;.', 'wordpress-mu-domain-mapping' ), $current_site->path ) );

	// set up some defaults
	if ( ! get_site_option( 'dm_remote_login', false ) )
		add_site_option( 'dm_remote_login', 1 );

	if ( ! get_site_option( 'dm_redirect_admin', false ) )
		add_site_option( 'dm_redirect_admin', 1 );

	if ( ! get_site_option( 'dm_user_settings', false ) )
		add_site_option( 'dm_user_settings', 1 );

	if ( ! empty( $_POST['action'] ) ) {
		check_admin_referer( 'domain_mapping' );

		if ( 'update' == $_POST['action'] ) {
			$ipok = true;
			$ip_addresses = explode( ',', $_POST['ipaddress'] );
			foreach( $ip_addresses as $address ) {
				if ( ( $ip = trim( $address ) ) && !preg_match( '|^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$|', $ip ) ) {
					$ipok = false;
					break;
				}
			}

			if( $ipok )
				update_site_option( 'dm_ipaddress', $_POST['ipaddress'] );

			if ( 0 == intval( $_POST['always_redirect_admin'] ) )
				$_POST['dm_remote_login'] = 0; // disable remote login if redirecting to mapped domain

			update_site_option( 'dm_remote_login', intval( $_POST[ 'dm_remote_login' ] ) );

			if ( ! preg_match( '/(--|\.\.)/', $_POST['cname'] ) && preg_match( '|^([a-zA-Z0-9-\.])+$|', $_POST['cname'] ) )
				update_site_option( 'dm_cname', stripslashes( $_POST['cname'] ) );
			else
				update_site_option( 'dm_cname', '' );

			update_site_option( 'dm_301_redirect', intval( $_POST['permanent_redirect'] ) );
			update_site_option( 'dm_redirect_admin', intval( $_POST['always_redirect_admin'] ) );
			update_site_option( 'dm_user_settings', intval( $_POST['dm_user_settings'] ) );
			update_site_option( 'dm_no_primary_domain', intval( $_POST['dm_no_primary_domain'] ) );
		}
	}

	?>
<h3><?php _e( 'Domain Mapping Configuration', 'wordpress-mu-domain-mapping' ); ?></h3>
<form method="POST">
    <input type="hidden" name="action" value="update" />
    <p><?php _e( 'As a super admin on this network you can set the IP address users need to point their DNS A records at <em>or</em> the domain to point CNAME record at. If you don\'t know what the IP address is, ping this blog to get it.', 'wordpress-mu-domain-mapping' ); ?></p>
    <p><?php _e( 'If you use round robin DNS or another load balancing technique with more than one IP, enter each address, separating them by commas.', 'wordpress-mu-domain-mapping' ); ?></p>
    <label for="ip_address"><?php _e( 'Server IP Address: ', 'wordpress-mu-domain-mapping' ); ?></label>
    <input type="text" name="ipaddress" id="ip_address" value="<?php echo esc_attr( get_site_option( 'dm_ipaddress' ) ); ?>" /><br />
    <p><?php _e( 'If you prefer the use of a CNAME record, you can set the domain here. This domain must be configured with an A record or ANAME pointing at an IP address. Visitors may experience problems if it is a CNAME of another domain.', 'wordpress-mu-domain-mapping' ); ?></p>
    <p><?php _e( 'NOTE, this voids the use of any IP address set above', 'wordpress-mu-domain-mapping' ); ?></p>
    <label for="cname"><?php _e( 'Server CNAME domain: ', 'wordpress-mu-domain-mapping' ); ?></label>
    <input type="text" name="cname" id="cname" value="<?php echo esc_attr( get_site_option( 'dm_cname' ) ); ?>" /> ("<?php echo dm_idn_warning(); ?>")<br />
    <p><?php _e( 'The information you enter here will be shown to your users so they can configure their DNS correctly. It is for informational purposes only', 'wordpress-mu-domain-mapping' ); ?></p>
    <h3><?php _e( 'Domain Options', 'wordpress-mu-domain-mapping' ); ?></h3>
    <ol>
        <li><input type="checkbox" name="dm_remote_login" value="1" <?php checked( get_site_option( 'dm_remote_login' ), '1' ); ?> /><?php _e( 'Remote Login', 'wordpress-mu-domain-mapping' ); ?></li>
        <li><input type="checkbox" name="permanent_redirect" value="1" <?php checked( get_site_option( 'dm_301_redirect' ), '1' ); ?> /> <?php _e( 'Permanent redirect (better for your blogger\'s pagerank)', 'wordpress-mu-domain-mapping' ); ?></li>
        <li><input type="checkbox" name="dm_user_settings" value="1" <?php checked( get_site_option( 'dm_user_settings', '1' ) ); ?> /> <?php _e( 'User domain mapping page', 'wordpress-mu-domain-mapping' ); ?></li>
        <li><input type="checkbox" name="always_redirect_admin" value="1" <?php checked( get_site_option( 'dm_redirect_admin' ), '1' ); ?> /> <?php _e( 'Redirect administration pages to site\'s original domain (remote login disabled if this redirect is disabled)', 'wordpress-mu-domain-mapping' ); ?></li>
        <li><input type="checkbox" name="dm_no_primary_domain" value="1" <?php checked( get_site_option( 'dm_no_primary_domain' ), '1' ); ?> /> <?php _e( 'Disable primary domain check. Sites will not redirect to one domain name. May cause duplicate content issues.', 'wordpress-mu-domain-mapping' ); ?></li>
    </ol>
	<?php wp_nonce_field( 'domain_mapping' ); ?>
    <p><input class="button-primary" type="submit" value="<?php _e( 'Save', 'wordpress-mu-domain-mapping' ); ?>" /></p>
</form>
<br />
<?php

}

/**
 * Called in dm_admin_page() to output a warning about International domain names
 *
 * @return string
 */
function dm_idn_warning() {
	return sprintf( __( 'International Domain Names should be in <a href="%s">punycode</a> format.', 'wordpress-mu-domain-mapping' ), "http://api.webnic.cc/idnconversion.html" );
}

function dm_sunrise_warning( $die = true ) {

	if ( ! file_exists( WP_CONTENT_DIR . '/sunrise.php' ) ) {
		if ( ! $die )
			return true;

		if ( is_super_admin() )
			wp_die( sprintf( __( 'Please copy sunrise.php to %s/sunrise.php and ensure the SUNRISE definition is in %swp-config.php', 'wordpress-mu-domain-mapping' ), WP_CONTENT_DIR, ABSPATH ) );
		else
			wp_die( __( 'This plugin has not been configured correctly yet.', 'wordpress-mu-domain-mapping' ) );

	} elseif ( ! defined( 'SUNRISE' ) ) {
		if ( !$die )
			return true;

		if ( is_super_admin() )
			wp_die( sprintf( __( 'Please uncomment the line <em>define( \'SUNRISE\', \'on\' );</em> or add it to your %swp-config.php', 'wordpress-mu-domain-mapping' ), ABSPATH ) );
		else
			wp_die( __( 'This plugin has not been configured correctly yet.', 'wordpress-mu-domain-mapping' ) );

	} elseif ( ! defined( 'SUNRISE_LOADED' ) ) {
		if ( ! $die )
			return true;

		if ( is_super_admin() )
			wp_die( sprintf( __( 'Please edit your %swp-config.php and move the line <em>define( \'SUNRISE\', \'on\' );</em> above the last require_once() in that file or make sure you updated sunrise.php.', 'wordpress-mu-domain-mapping' ), ABSPATH ) );
		else
			wp_die( __( 'This plugin has not been configured correctly yet.', 'wordpress-mu-domain-mapping' ) );

	}
	return true;
}

add_action( 'admin_init', 'dm_redirect_admin' );
/**
 * Redirects the admin URL ( i.e. www.domain.com/wp-admin/ ) to the original site URL
 * that WordPress is aware of (i.e. domain.my.sites.com/wp-admin/ )
 *
 * @return NULL if this is not a valid request
 */
function dm_redirect_admin() {

	// don't redirect admin ajax calls
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		return;

	if ( get_site_option( 'dm_redirect_admin' ) ) {
		// redirect mapped domain admin page to original url
		$url = get_original_url( 'siteurl' );
		if ( false === strpos( $url, $_SERVER[ 'HTTP_HOST' ] ) ) {
			wp_redirect( untrailingslashit( $url ) . $_SERVER[ 'REQUEST_URI' ] );
			exit;
		}
	} else {
		global $current_blog;
		// redirect original url to primary domain wp-admin/ - remote login is disabled!
		$url = domain_mapping_siteurl( false );
		$request_uri = str_replace( $current_blog->path, '/', $_SERVER[ 'REQUEST_URI' ] );
		if ( false === strpos( $url, $_SERVER[ 'HTTP_HOST' ] ) ) {
			wp_redirect( str_replace( '//wp-admin', '/wp-admin', trailingslashit( $url ) . $request_uri ) );
			exit;
		}
	}
}

// show mapping on site admin blogs screen
function ra_domain_mapping_columns( $columns ) {
	$columns[ 'map' ] = __( 'Mapping' );
	return $columns;
}
add_filter( 'wpmu_blogs_columns', 'ra_domain_mapping_columns' );

function ra_domain_mapping_field( $column, $blog_id ) {
	global $wpdb;
	static $maps = false;

	if ( $column == 'map' ) {
		if ( $maps === false ) {
			$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
			$work = $wpdb->get_results( "SELECT blog_id, domain FROM {$wpdb->dmtable} ORDER BY blog_id" );
			$maps = array();
			if($work) {
				foreach( $work as $blog ) {
					$maps[ $blog->blog_id ][] = $blog->domain;
				}
			}
		}
		if( !empty( $maps[ $blog_id ] ) && is_array( $maps[ $blog_id ] ) ) {
			foreach( $maps[ $blog_id ] as $blog ) {
				echo $blog . '<br />';
			}
		}
	}
}
add_action( 'manage_blogs_custom_column', 'ra_domain_mapping_field', 1, 3 );
add_action( 'manage_sites_custom_column', 'ra_domain_mapping_field', 1, 3 );