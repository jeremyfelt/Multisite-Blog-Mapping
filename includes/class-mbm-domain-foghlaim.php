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
		add_action( 'save_post', array( $this, 'save_meta_data' ), 10, 2 );
		add_action( 'delete_blog', array( $this, 'delete_blog' ), 10, 2 );
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
	 * Add meta boxes to be displayed when adding/editing the domain custom
	 * content type
	 *
	 * @param $post WP_Post
	 */
	public function register_meta_boxes( $post ) {
		add_meta_box( 'mbm_domain_name', 'Domain Name:', array( $this, 'display_domain_name_meta_box' ), $post->post_type, 'normal', 'default' );
		add_meta_box( 'mbm_domain_blog_id', 'Blog ID:', array( $this, 'display_blog_id_meta_box' ), $post->post_type, 'normal', 'default' );
	}

	/**
	 * Display the domain name meta box that will capture the actual domain
	 * name associated with this mapping.
	 *
	 * @param $post WP_Post
	 */
	public function display_domain_name_meta_box( $post ) {
		$domain_name = get_post_meta( $post->ID, '_mbm_domain_name', true );
		wp_nonce_field( 'mbm-domain-meta-data', '_mbm_domain_plugin_nonce' );
		?>
		<label for="mbm-domain-name">Enter the domain name for this mapping: (www.domain.com)</label>
		<input id="mbm-domain-name" name="mbm_domain_name" type="text" value="<?php echo esc_attr( $domain_name ); ?>" class="widefat" />
		<?php
	}

	/**
	 * Display the blog ID meta box that will capture the blog ID associated
	 * with this mapping.
	 *
	 * @param $post WP_Post
	 */
	public function display_blog_id_meta_box( $post ) {
		$blog_id = get_post_meta( $post->ID, '_mbm_domain_blog_id', true );
		?>
		<label for="mbm-domain-blog-id">Enter the blog ID for this mapping:</label>
		<input id="mbm-domain-blog-id" name="mbm_domain_blog_id" type="text" value="<?php echo esc_attr( $blog_id ); ?>" class="widefat" />
		<?php
	}

	/**
	 * Save the post meta data for the domain content type as it is submitted.
	 *
	 * @param $post_id int containing current post's ID
	 * @param $post WP_POST containing current post's object
	 *
	 * @return null always with the null, it's an action!
	 */
	public function save_meta_data( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return NULL;

		if ( 'auto-draft' === $post->post_status )
			return NULL;

		if ( ! isset( $_POST['_mbm_domain_plugin_nonce'] ) || ! wp_verify_nonce( $_POST['_mbm_domain_plugin_nonce'], 'mbm-domain-meta-data' ) )
			return NULL;

		if ( isset( $_POST['mbm_domain_name'] ) ) {

			// @todo maybe unhackety
			// escape the domain name here, but then strip off the http - hacky, but I'm sleepy.
			$domain_name = trim( str_replace( 'http://', '', esc_url_raw( $_POST['mbm_domain_name'] ) ) );
			if ( empty( $domain_name ) )
				return NULL;

			update_post_meta( $post_id, '_mbm_domain_name', $domain_name );
		}

		if ( isset( $_POST['mbm_domain_blog_id'] ) && 0 !== absint( $_POST['mbm_domain_blog_id'] ) )
			update_post_meta( $post_id, '_mbm_domain_blog_id', absint( $_POST['mbm_domain_blog_id'] ) );

		return NULL;
	}

	/**
	 * Called when a blog is deleted
	 *
	 * @todo something to delete the entries in the domain mapping that we have setup
	 * @todo find out what $drop represents
	 *
	 * @param $blog_id
	 * @param $drop
	 *
	 * @return null
	 */
	public function delete_blog( $blog_id, $drop ) {
		return NULL;
	}

	/**
	 * Manually add an option for the domain custom content type to the menu
	 * in the network admin. This is so ugly and not permanent, but whatever.
	 *
	 * The ugly hacks hurt kittens. I'm so sorry.
	 */
	public function modify_network_menu() {
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