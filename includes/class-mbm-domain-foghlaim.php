<?php

class Mbm_Domain_Foghlaim {

	/**
	 * Contains the one instance of the domain handler
	 *
	 * @var Mbm_Domain_Foghlaim
	 */
	static private $instance;

	const post_type = 'mbm_domain';

	/**
	 * Construct.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_content_type' ) );
		add_filter( 'parent_file', array( $this, 'modify_network_menu' ) );
	}

	/**
	 * Maintain and return the one instance of the domain handler
	 *
	 * @return Mbm_Domain_Foghlaim
	 */
	public static function get_instance() {
		if ( ! self::$instance )
			self::$instance = new Mbm_Domain_Foghlaim();

		return self::$instance;
	}

	/**
	 * Register the domain content type. This will allow the storage of domain
	 * and domain mapping data as a custom content type in WordPress.
	 */
	public function register_content_type() {
		$content_type_labels = array(
			'name'               => 'Site Domains',
			'singular_name'      => 'Site Domain',
			'add_new'            => 'Add Domain',
			'add_new_item'       => 'Add New Domain',
			'edit_item'          => 'Edit Domain',
			'edit_new_item'      => 'Edit New Domain',
			'all_items'          => 'All Domains',
			'view_item'          => 'View Domains',
			'search_items'       => 'Search Domans',
			'not_found'          => 'No domains found',
			'not_found_in_trash' => 'No domains found in Trash',
		);

		$content_type_arguments = array(
			'labels'               => $content_type_labels,
			'public'               => false,
			'show_ui'              => true,
			'show_in_admin_bar'    => true,
			'menu_position'        => 5,
			'menu_icon'            => NULL,
			'supports'             => array( 'title' ),
			'register_meta_box_cb' => array( $this, 'register_meta_boxes' ),
			'has_archive'          => false,
			'rewrite'              => false,
		);

		register_post_type( self::post_type, $content_type_arguments );
	}

	/**
	 * Manually add an option for the domain custom content type to the menu
	 * in the network admin. This is so ugly and not permanent, but whatever.
	 *
	 * The ugly hacks hurt kittens. I'm so sorry.
	 */
	function modify_network_menu() {
		global $menu, $submenu;

		// A hacked modification is only necessary in the network dashboard
		$current_screen = get_current_screen();
		if ( 'dashboard-network' !== $current_screen->base )
			return;

		$menu[6] = array(
			'Site Domains',
			'edit_posts',
			'../edit.php?post_type=mbm_domain',
			'',
			'menu-top menu-icon-post',
			'menu-posts-mbm_domain',
			'none',
		);
		sort( $menu );

		$submenu['../edit.php?post_type=mbm_domain'] = array(
			5 => array(
				'All Domains',
				'edit_posts',
				'../edit.php?post_type=mbm_domain',
			),
			10 => array(
				'Add Domain',
				'edit_posts',
				'../post-new.php?post_type=mbm_domain',
			),
		);

	}
}
$mbm_domain_foghlaim = Mbm_Domain_Foghlaim::get_instance();