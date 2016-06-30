<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://dropshop.io
 * @since      1.0.0
 *
 * @package    Wp_Post_Projects
 * @subpackage Wp_Post_Projects/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Post_Projects
 * @subpackage Wp_Post_Projects/public
 * @author     James Towers <james@songdrop.com>
 */
class Wp_Post_Projects_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->add_shortcodes();

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Post_Projects_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Post_Projects_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-post-projects-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Post_Projects_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Post_Projects_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-post-projects-public.js', array( 'jquery' ), $this->version, false );

	}


	public function get_project_posts($project_id)
	{
	  
	  $args = array(
	    'post_type' => 'post',
	    'meta_query' => array(
	      array(
	        'key' => $this->plugin_name . '_project',
	        'value' => $project_id,
	        'compare' => 'LIKE'
	      )
	    ),
	    'post__not_in' => get_option( 'sticky_posts' )
	  );
	  $query = new WP_Query($args);
	  return $query;
	}

	public function sort_project_posts_by_format()
	{
		$query = $this->get_project_posts($project_id);

		$sorted_posts = array();

		if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
			
			$post_format = get_post_format($post->ID);
			
			if(!isset($sorted_posts[$post_format])){
				$sorted_posts[$post_format] = array();
			}

			$p = array(
				'title' => get_the_title(),
				'format' => $post_format,
				'content' => get_the_content()
			);
			
			array_push($sorted_posts[$post_format], $p);

		endwhile; endif; wp_reset_query();
		
		return $sorted_posts;
	}

	

	public function render_project_posts($project_id)
	{
	  $posts = $this->sort_project_posts_by_format($project_id);
	  foreach($posts as $format => $posts){
	  	echo '<h2>' . $format . '</h2>';
	  	echo '<ul>';
	  	foreach($posts as $post){
	  		echo '<li>' . $post['title'] . '</li>';
	  	}
	  	echo '</ul>';
	  }
	}

	/**
	 * Enable shortcode
	 *
	 * Adds the [gfd_form] shortcode for displaying the submission form on a page
	 *
	 * @since    1.0.0
	 */
	public function add_shortcodes()
	{
		add_shortcode('project_posts', array( &$this, 'render_project_posts'));
	}

}
