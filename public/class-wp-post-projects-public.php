<?php

class Wp_Post_Projects_Public {

	private static $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {

		self::$plugin_name = $plugin_name;
		$this->version = $version;
		$this->add_shortcodes();

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->$plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-post-projects-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->$plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-post-projects-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Get all posts in a project
	 * @param  [Int] $project_id [Project ID]
	 * @return [Array] [WP_QUERY]
	 */
	public static function get_project_posts($project_id)
	{
	  $args = array(
	    'post_type' => array('post', 'album'),
	    'meta_query' => array(
	      array(
	        'key' => self::$plugin_name . '_project',
	        'value' => $project_id,
	        'compare' => 'LIKE',
	        'orderby' => 'date',
	        'order' => 'DESC'
	      )
	    ),
	    'post__not_in' => get_option( 'sticky_posts' )
	  );
	  $query = new WP_Query($args);
	  return $query;
	}

	public static function get_project_posts_in_directory($project_id, $directory_slug)
	{
		$args = array(
			'post_type' => array( 'post', 'album' ),
		  'meta_query' => array(
		      array(
		        'key'     => self::$plugin_name . '_project',
		        'value'   => $project_id,
		        //'compare' => 'IN',
		      ),
		    ),
		  'tax_query' => array(
		      array(
		        'taxonomy' => 'directory',
		        'field'    => 'slug',
		        'terms'    => $directory_slug,
		      ),
		    )
		);

		$directory_query = new WP_Query($args);
		return $directory_query;
	}


	public static function get_directories()
	{
		global $post;
		// Make sure the post has directories
		/*if(!self::post_has_project($post->ID))
			return;*/

		$directories = get_terms( array(
		    'taxonomy' => 'directory',
		    'hide_empty' => false,
		) );

		return $directories;
	}

	public static function directory_links()
	{
		//$project_id = isset($_GET['project_id']) ? $_GET['project_id'] : null;

	  $directories = self::get_directories();

	  echo '<div class="tabs"><ul>';
	  foreach($directories as $directory)
	  {
	  	//$url = get_term_link($directory);
	  	$url = get_the_permalink();
	  	$url = add_query_arg('type', $directory->slug, $url);
	  	$active = (isset($_GET['type']) && $_GET['type'] === $directory->slug) ? 'active' : '';
	  	/*if($project_id){
	  		$url = add_query_arg('project_id', $project_id, $url);
	  	}
	  	$active = is_tax('directory', $directory->name) ? 'active' : '';*/
	    echo '<li class="' . $active . '"><a href="' . $url . '">' . $directory->name . '</a></li>';
	  }
	  echo '</ul></div>';
	}



	/**
	 * Order the post in a project by directory name
	 * @param  [Int] $project_id [Project ID]
	 * @return [Array] [Associative array of directories]
	 */
	public static function sort_project_posts_by_directory($project_id)
	{
		$query = self::get_project_posts($project_id);

		$sorted_posts = array();

		if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
			
			$directory = wp_get_post_terms( $query->post->ID, 'directory' );

			// Skip if this post doesnt have a content type
			if(!empty($directory)){

				// Only one content type is allowed per post so just get the first one
				$directory = $directory[0]; 
				
				// If this project has post of this content type
				if( $directory->count > 0 ){

					if(!isset($sorted_posts[$directory->slug])){
						$sorted_posts[$directory->slug] = array(
							'id' => $directory->ID,
							'title' => $directory->name,
							'slug' => $directory->slug,
							'thumbnail' => has_post_thumbnail() ? get_the_post_thumbnail( $query->post->ID, 'post-thumbnail' ) : '',
							'posts' => array()
							);
					}
					
					array_push($sorted_posts[$directory->slug]['posts'], $query->post);
				}

			}

		endwhile; endif; 
		wp_reset_query();
		
		return $sorted_posts;
	}


	/**
	 * Get the start and end date of a project by taking
	 * @param  [Int] $project_id [The projecvt ID]
	 * @return [String]
	 */
	public static function get_project_date_range($project_id)
	{
		$start = get_post_meta($project_id, 'wp-post-projects_start_date', true);
		$end = get_post_meta($project_id, 'wp-post-projects_end_date', true);
		return date("M Y", strtotime($start)) . ' - ' . date("M Y", strtotime($end));
	}
	


	/**
	 * If on a directory taxonomy page and project_id query param 
	 * is valid then display only the post in that directory and from that project
	 * (Prepends data ot thew query on the taxonomy page)
	 * @param  [Array] $query [qwuery passed in from pre_get_posts action]
	 * @return [wp_query]
	 */
	public function filter_project_taxonomy_archive ( $query ) {

	  if ( $query->is_main_query() && is_tax('projects') )

	    $query->set( 'post_type', 'post' );

	    if(isset($_GET['project_id'])){

	      $query->set( 'meta_query', array(
	        array(
	          'key' => self::$plugin_name . '_project',
	          'value' => $_REQUEST['project_id'],
	          'compare' => '=',
	          'type' => 'numeric'
	          )
	        )
	      );

	    }
	}

	public static function post_has_project($post_id)
	{
		return get_post_meta($post_id, self::$plugin_name . '_project', true);
	}

	public static function post_project_link()
	{
		global $post;
		if(isset($_GET['type'])){
			$project_id = $post->ID;
		}else{
			$project_id = self::post_has_project($post->ID);
			if(!$project_id)
				return false;
		}

		$project = get_post($project_id);
		return '<a href="' . get_the_permalink($project->ID) . '" class="directory-up">Back to ' . $project->post_title . ' main page</a>';

	}

	public static function breadcrumb()
	{
		global $post;
		$levels = array();

		if($post->post_type == 'project' && !isset($_GET['type']))
			return;

		if(is_single()){
			$directories = wp_get_post_terms( $post->ID, 'directory');
			$project_id = self::post_has_project($post->ID);
			$project = get_post($project_id);

			if($directories[0]){
				array_push($levels, array('title' => $directories[0]->name, 'url' => get_permalink($project_id) . '?type=' . $directories[0]->slug));
			}
			
			array_push($levels, array('title' => $project->post_title, 'url' => get_permalink($project->ID)));
		}

		/*if(isset($_GET['type'])) {
			$project_id = self::post_has_project($post->ID);
			$project = get_post($project);
			array_push($levels, array('title' => $project->post_title, 'url' => ''));
		}else{

		}*/

		return $levels;
	}


	/**
	 * Enable shortcode
	 * Adds the [gfd_form] shortcode for displaying the submission form on a page
	 */
	public function add_shortcodes()
	{
		//add_shortcode('project_posts', array( &$this, 'render_project_posts'));
		//add_shortcode('project_posts_by_format', array( &$this, 'sort_project_posts_by_directory'));
	}

}


/**
 * Static function to display the projects date range
 * @param  [Int] $project_id [The project ID]
 * @return [Method] [Method from above class]
 */
function project_date_range($project_id = null)
{
	if(null == $project_id){
		global $post;
		$project_id = $post->ID;
	}

	echo Wp_Post_Projects_Public::get_project_date_range($project_id);
}