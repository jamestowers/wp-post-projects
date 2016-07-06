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

		wp_enqueue_style( self::$plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-post-projects-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( self::$plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-post-projects-public.js', array( 'jquery' ), $this->version, false );

	}


	public static function get_project_posts($project_id)
	{
	  $args = array(
	    'post_type' => 'post',
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



	public static function sort_project_posts_by_content_type($project_id)
	{
		
		$query = self::get_project_posts($project_id);

		$sorted_posts = array();

		if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
			
			$content_type = wp_get_post_terms( $query->post->ID, 'content_type' );

			// Skip if this post doesnt have a content type
			if(!empty($content_type)){

				// Only one content type is allowed per post so just get the first one
				$content_type = $content_type[0]; 
				
				// If this project has post of this content type
				if( $content_type->count > 0 ){

					if(!isset($sorted_posts[$content_type->slug])){
						$sorted_posts[$content_type->slug] = array();
					}

					$thumb_url = has_post_thumbnail() ? get_the_post_thumbnail( $query->post->ID, 'post-thumbnail' ) : '';
					
					$p = array(
						'title' => get_the_title(),
						'content_type' => $content_type->name,
						'content' => get_the_content(),
						'thumbnail' => $thumb_url
					);
					
					array_push($sorted_posts[$content_type->slug], $p);
				}

			}

		endwhile; endif; 
		wp_reset_query();
		
		return $sorted_posts;
	}


	public static function get_project_date_range($project_id)
	{
		return get_post_meta($project_id, 'wp-post-projects_start_date', true) . ' - ' . get_post_meta($project_id, 'wp-post-projects_end_date', true);
	}
	

	/*public function render_project_posts($project_id)
	{
		if(null == $project_id){
			global $post;
			$project_id = $post->ID;
		}

	  $posts = $this->sort_project_posts_by_content_type($project_id);

	  foreach($posts as $content_type => $posts){
	  	if(!empty($posts)){
	  		$post = $posts[0];
	  		$link = add_query_arg( 'project_id', $project_id, get_term_link($content_type, 'content_type'));
	  		?>

	  		<a href="<?php echo esc_attr($link);?>" class="tile post-content-type post-content-type-<?php echo $content_type;?>">
	  			<figure class="thumbnail">
	  				<?php echo $post['thumbnail'];?>
	  			</figure>
	  			<h3><?php  echo $post['content_type'];?></h3>
	  		</a>

	  	<?php }
	  }
	}*/


	public function filter_project_taxonomy_archive ( $query ) {

	  if ( $query->is_main_query() && is_tax('projects') )

	    $query->set( 'post_type', 'post' );

	    if(isset($_GET['project_id'])){

	      $query->set( 'meta_query', array(
	        array(
	          'key' => 'wp-post-projects_project',
	          'value' => $_REQUEST['project_id'],
	          'compare' => '=',
	          'type' => 'numeric'
	          )
	        )
	      );

	    }
	}


	/**
	 * Enable shortcode
	 * Adds the [gfd_form] shortcode for displaying the submission form on a page
	 */
	public function add_shortcodes()
	{
		//add_shortcode('project_posts', array( &$this, 'render_project_posts'));
		//add_shortcode('project_posts_by_format', array( &$this, 'sort_project_posts_by_content_type'));
	}

}

function project_date_range($project_id = null)
{
	if(null == $project_id){
		global $post;
		$project_id = $post->ID;
	}

	echo Wp_Post_Projects_Public::get_project_date_range($project_id);
}