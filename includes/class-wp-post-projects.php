<?php

class Wp_Post_Projects {

	protected $loader;

	protected $plugin_name;

	protected $version;

	public function __construct() {

		$this->plugin_name = 'wp-post-projects';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-projects-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-post-projects-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-post-projects-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-post-projects-meta-boxes.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-post-projects-public.php';

		$this->loader = new Wp_Post_Projects_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Post_Projects_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Post_Projects_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wp_Post_Projects_Admin( $this->get_plugin_name(), $this->get_version() );

		$plugin_meta_boxes = new Wp_Post_Projects_Meta_Boxes( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'init', $plugin_admin, 'create_project_post_type' );
		$this->loader->add_action( 'init', $plugin_admin, 'create_directory_taxonomy' );
		$this->loader->add_filter( 'manage_post_posts_columns', $plugin_admin, 'set_post_columns' );
		//$this->loader->add_filter( 'post_type_link', $plugin_admin, 'project_permalinks', 1, 2 );
		$this->loader->add_action( 'manage_posts_custom_column', $plugin_admin, 'populate_custom_columns', 10, 2 );
		$this->loader->add_action( 'save_post', $plugin_admin, 'set_post_directory' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'set_project_dates' );


		/* Fire the project meta box setup function on the post editor screen. */
		//-- Remove default content type meta box
		$this->loader->add_action( 'admin_menu', $plugin_meta_boxes, 'remove_default_post_directory_meta_box' );
		//-- add new ones
		$this->loader->add_action( 'load-post.php', $plugin_meta_boxes, 'post_meta_boxes_setup' );
		$this->loader->add_action( 'load-post-new.php', $plugin_meta_boxes, 'post_meta_boxes_setup' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Post_Projects_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'pre_get_posts', $plugin_public, 'filter_project_taxonomy_archive' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Post_Projects_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
