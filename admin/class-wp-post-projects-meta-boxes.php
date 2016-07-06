<?php
class Wp_Post_Projects_Meta_Boxes {

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
   * @param      string    $plugin_name       The name of this plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct( $plugin_name, $version ) {

    $this->plugin_name = $plugin_name;
    $this->version = $version;

  }

  public function get_projects()
  {
    $args = array(
      'posts_per_page'   => -1,
      'orderby'          => 'title',
      'order'            => 'DESC',
      'post_type'        => 'project',
      'post_status'      => 'publish'
    );

    $projects = get_posts( $args );

    return $projects;
  }



  public function post_meta_boxes_setup()
  {
    /* Add meta boxes on the 'add_meta_boxes' hook. */
    add_action( 'add_meta_boxes', array( &$this, 'add_project_meta_boxes') );
    add_action( 'add_meta_boxes', array( &$this, 'add_post_directory_meta_boxes') );
    /* Save post meta on the 'save_post' hook. */
    add_action( 'save_post', array( &$this, 'save_post_project'), 10, 2 );
  }

  public function add_project_meta_boxes()
  {
    add_meta_box(
      $this->plugin_name . '_project_meta_box',      // Unique ID
      esc_html__( 'Project', $this->plugin_name ),    // Title
      array( &$this, 'render_project_info_meta_box'),   // Callback function
      'post',         // Admin page (or post type)
      'side',         // Context
      'default'       // Priority
    );
  }


  // remove default directory taxonomy meta box 
  // so that we can add our oen in its place
  public function remove_default_post_directory_meta_box()
  {
    remove_meta_box('directorydiv', 'post', 'side');
  }

  // Add our own...
  public function add_post_directory_meta_boxes()
  {
    
    add_meta_box( 
      $this->plugin_name . '_directory_meta_box', 
      esc_html__( 'Content Type', $this->plugin_name ), 
      array( &$this, 'render_directory_metabox'),
      'post',
      'side',
      'core'
    );
  }

  /* Display the post meta box. */
  public function render_project_info_meta_box( $object, $box ) { 

      // Save meta key name for later use
      $meta_key = $this->plugin_name . '_project';

      $projects = $this->get_projects();
      // Get the currently selected option
      $selected_option = get_post_meta($object->ID, $meta_key, true);

      // Add nonce field - use meta key name with '_nonce' appended
      wp_nonce_field( basename( __FILE__ ), $meta_key . '_nonce' );

      echo '<p class="description">' .  _e( "Optionally add this post to a project", $this->plugin_name ) . '</p>';?>

      <select class="" name="<?php echo $meta_key;?>" id="<?php echo $meta_key;?>">
        <option value="">None</option>
        <?php foreach( $projects as $project ){
          $selected = $selected_option == $project->ID ? 'selected="selected"' : '';
          echo '<option value="' . $project->ID . '" ' . $selected . '>' . $project->post_title . '</option>';
        }?>
      </select>
    
  <?php }


  public function render_directory_metabox( $post, $box )
  {
    //Get taxonomy and terms
    $taxonomy = 'directory';
 
    //Set up the taxonomy object and get terms
    $tax = get_taxonomy($taxonomy);
    $terms = get_terms($taxonomy,array('hide_empty' => 0));
 
    //Name of the form
    $name = 'tax_input[' . $taxonomy . ']';
 
    //Get current and popular terms
    //$popular = get_terms( $taxonomy, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );
    $postterms = get_the_terms( $post->ID,$taxonomy );
    $current = ($postterms ? array_pop($postterms) : false);
    $current = ($current ? $current->term_id : 0);
    ?>
 
    <div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
      <ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
          <?php   foreach($terms as $term){
              $id = $taxonomy.'-'.$term->term_id;
              echo "<li id='$id'><label class='selectit'>";
              echo "<input type='radio' id='in-$id' name='{$name}'".checked($current,$term->term_id,false)."value='$term->term_id' />$term->name<br />";
             echo "</label></li>";
          }?>
      </ul>
    </div>
    <?php
  }



  public function save_post_project( $post_id, $post )
  {
    $this->save_meta($post_id, $post, $this->plugin_name . '_project'); 
  }



  public function save_meta($post_id, $post, $meta_key)
  {
    $this->verify_nonce($meta_key . '_nonce', $post_id);

    /* Get the post type object. */
    $post_type = get_post_type_object( $post->post_type );
    /* Check if the current user has permission to edit the post. */
    if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
      return $post_id;

    /* Get the posted data and sanitize it for use as an HTML class. */
    $new_meta_value = ( isset( $_POST[$meta_key] ) ? sanitize_html_class( $_POST[$meta_key] ) : '' );

    $this->save_or_edit_meta($post_id, $meta_key, $new_meta_value);
  }



  public function verify_nonce($nonce_key, $post_id)
  {
    if ( !isset( $_POST[$nonce_key] ) || !wp_verify_nonce( $_POST[$nonce_key], basename( __FILE__ ) ) )
      return $post_id;
  }



  public function save_or_edit_meta($post_id, $meta_key, $new_meta_value)
  {
    /* Get the meta value of the custom field key. */
    $meta_value = get_post_meta( $post_id, $meta_key, true );

    /* If a new meta value was added and there was no previous value, add it. */
    if ( $new_meta_value && '' == $meta_value )
      add_post_meta( $post_id, $meta_key, $new_meta_value, true );

    /* If the new meta value does not match the old value, update it. */
    elseif ( $new_meta_value && $new_meta_value != $meta_value )
      update_post_meta( $post_id, $meta_key, $new_meta_value );

    /* If there is no new meta value but an old value exists, delete it. */
    elseif ( '' == $new_meta_value && $meta_value )
      delete_post_meta( $post_id, $meta_key, $meta_value );
  }

}