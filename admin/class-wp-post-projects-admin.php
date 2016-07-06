<?php

class Wp_Post_Projects_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-post-projects-admin.css', array(), $this->version, 'all' );

	}

	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-post-projects-admin.js', array( 'jquery' ), $this->version, false );

	}


	/**
	 * Create Project custom post type
	 */
	public function create_project_post_type() {
	  register_post_type( 'project',
	    array(
	      'labels' => array(
	        'name' => __( 'Projects' ),
	        'singular_name' => __( 'Project' )
	      ),
	      'public' => true,
	      'has_archive' => true,
	      'menu_icon' => 'dashicons-category',
	      'rewrite' => array( 'slug' => 'projects/%directory%', 'with_front' => false ),
	              'has_archive' => 'projects',
	    )
	  );
	}


	public function create_directory_taxonomy() {
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Post directories', 'taxonomy general name' ),
			'singular_name'     => _x( 'Post directory', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Post directories' ),
			'all_items'         => __( 'All Post directories' ),
			'parent_item'       => __( 'Parent Post directory' ),
			'parent_item_colon' => __( 'Parent Post directory:' ),
			'edit_item'         => __( 'Edit Post directory' ),
			'update_item'       => __( 'Update Post directory' ),
			'add_new_item'      => __( 'Add New Post directory' ),
			'new_item_name'     => __( 'New Post directory Name' ),
			'menu_name'         => __( 'Post directories' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite' => array( 
				'slug' => 'projects', 
				'with_front' => false 
			)
		);

		register_taxonomy( 'directory', array( 'post' ), $args );
	}


	/*public function project_permalinks( $post_link, $post ){
    if ( is_object( $post ) && $post->post_type == 'project' ){
        $terms = wp_get_object_terms( $post->ID, 'directory' );
        if( $terms ){
            return str_replace( '%directory%' , $terms[0]->slug , $post_link );
        }
    }
    return $post_link;
	}*/


	public function set_project_dates($project_id)
	{
		// If this isnt a project or is just a revision, don't do anything.
		if ( wp_is_post_revision( $project_id ) || 'project' != get_post_type($project_id)  )
			return;

		$projects = Wp_Post_Projects_Public::get_project_posts($project_id);

		$last_date = get_the_date('M Y', $projects->posts[0]);
		$earliest_date = get_the_date('M Y', end($projects->posts));

		update_post_meta($project_id, $this->plugin_name . '_start_date', $earliest_date);
		update_post_meta($project_id, $this->plugin_name . '_end_date', $last_date);

	}


	/**
	 * Detect embedded content on save_post and update directory meta accordingly
	 * @param [Int] $post_id [The ID of the post to update (passed in by save_post action)]
	 */
	public function set_post_directory($post_id)
	{

		// If this is just a revision, don't do anything.
		if ( wp_is_post_revision( $post_id ) )
			return;

		$post = get_post($post_id);
		$directory = wp_get_post_terms( $post_id, 'directory');
		
		//Get the content, apply filters and execute shortcodes
		$content = apply_filters( 'the_content', $post->post_content );
		$embeds = get_media_embedded_in_content( $content );

		if( !empty($embeds) ) {
      //check what is the first embed containg video tag, youtube or vimeo
      foreach( $embeds as $embed ) {
        if( strpos( $embed, 'video' ) || strpos( $embed, 'youtube' ) || strpos( $embed, 'vimeo' ) ) {
          return wp_set_post_terms( $post_id, 242, 'directory');
        }
        elseif( strpos( $embed, 'audio' ) || strpos( $embed, 'soundcloud' ) ) {
          return wp_set_post_terms( $post_id, 245, 'directory');
        }
        else{
        	return false;
        }
      }
		} else {
      //No embeds found
      return false;
    }

	}



	/**
	 * Add project and directory columns to post write panel
	 * @param see: https://codex.wordpress.org/Plugin_API/Action_Reference/manage_posts_custom_column
	 */
	function set_post_columns($columns) {
	    return array(
	        'cb' => '<input type="checkbox" />',
	        'title' => __('Title'),
	        'project' => __('Project'),
	        'directory' =>__( 'Content Type'),
	        'tags' =>__( 'Tags'),
	        'date' => __('Date')
	    );
	}

	/**
	 * Populate project and directory custom columns
	 * @param see: https://codex.wordpress.org/Plugin_API/Action_Reference/manage_posts_custom_column
	 */
	public function populate_custom_columns( $column, $post_id ) {
	  switch ( $column ) {
	    case 'project':
	      $project_id = get_post_meta( $post_id, $this->plugin_name . '_project', true );
	      if ( $project_id ) {
	        echo '<a href="' . get_edit_post_link($project_id) . '">' . get_the_title($project_id) . '</a>';
	      }
	      break;

	    case 'directory':
	    	$terms = wp_get_post_terms( $post_id, 'directory' ); 
	    	if(!empty($terms)){
	      	echo $terms[0]->name; 
	      }
	      break;
	  }
	}

}
