<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.janushenderson.com/
 * @since      1.0.0
 *
 * @package    Jh_Nyt_Top_Stories
 * @subpackage Jh_Nyt_Top_Stories/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Jh_Nyt_Top_Stories
 * @subpackage Jh_Nyt_Top_Stories/admin
 * @author     Janus Henderson <webtechteam@janushenderson.com>
 */
class Jh_Nyt_Top_Stories_Admin {

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

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Jh_Nyt_Top_Stories_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Jh_Nyt_Top_Stories_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/jh-nyt-top-stories-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Jh_Nyt_Top_Stories_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Jh_Nyt_Top_Stories_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/jh-nyt-top-stories-admin.js', array( 'jquery' ), $this->version, false );

	}

}

// Custom post type function
function create_posttype() {
  
    register_post_type( 'nyt_top_stories',
    // CPT Options
        array(
            'labels' => array(
                'name' => __( 'NYT Top Stories' ),
                'singular_name' => __( 'NYT Top Story' )
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'nyt_top_stories'),
            'show_in_rest' => true,
            'exclude_from_search' => true,
			'show_in_menu' => false
  
        )
    );
}
// Hooking up our function to theme setup
add_action( 'init', 'create_posttype' );

//Setup a function that automatically runs every hour
if ( ! wp_next_scheduled( 'nyt_task_hook' ) ) {
    wp_schedule_event( time(), 'hourly', 'nyt_task_hook' );
}
add_action( 'nyt_task_hook', 'retrieve_top_stories' );
 
//Use the NY Times Api to retrieve the current "Top Stories" 
function retrieve_top_stories() {
    
  $response = wp_remote_get( 'https://api.nytimes.com/svc/topstories/v2/home.json?api-key=iKdsfNemAIkxj1JGZZcFdq9YAhjShGHW' );
	echo "Retrieved Data";
	if(class_exists( 'WP_CLI' ) ){echo "\n";} else{echo "<br/>";}
	
 
  if ( is_array( $response ) && ! is_wp_error( $response ) ) {
      $body = $response['body'];
	  $body = json_decode($body, true);
      $results = $body['results'];
	  
	  //Cycle through posts
      foreach ($results as $article) {
        global $wpdb;

		//Create post array
        $my_post = array(
          'post_title' => $article['title'],
		  'post_content' => $article['url'],
          'post_excerpt' => $article['abstract'],
          'post_date' => $article['published_date'],
          'post_status' => 'publish',
          'meta_input' => array( 'URL' => $article['url'],'byline' => $article['byline']),
          'post_category' => $article['section'],
          'tags_input' => array( $article['des_facet']),
		  'post_type' => 'nyt_top_stories'
          );

		  $title = $article['title'];
		  
          //Check if post exists/insert into the database if it does not.
          if (get_page_by_title($title, OBJECT, 'nyt_top_stories') == NULL ){ 
			$my_post = wp_slash($my_post);
			$insertPost = wp_insert_post($my_post);
			echo "Inserting Post<br/>";
			if(class_exists( 'WP_CLI' ) ){echo "\n";} else{echo "<br/>";}
			if (is_wp_error($insertPost)) {
  				$errors = $insertPost->get_error_messages();
    			foreach ($errors as $error) {
        			echo $error;
					if(class_exists( 'WP_CLI' ) ){echo "\n";} else{echo "<br/>";}
					
    			}
			}
		}
      }
	  echo "Done";
	  if(class_exists( 'WP_CLI' ) ){echo "\n";} else{echo "<br/>";}
	  
   }
}

//Create custom CLI command
if (class_exists('WP_CLI')) {
	WP_CLI::add_command('retrieve_top_stories', 'retrieve_top_stories');
}

//Add admin menu for CPT
add_action('admin_menu', 'nyt_top_stories_menu');

function nyt_top_stories_menu(){
	
	//Add main admin menu for CPT
    add_menu_page('NYT Top Stories', 'NYT Top Stories - Settings', 'manage_options', 'nyt_stories', 'NYT_Top_Stories_Manual_Pull');
	
	//Add admin submenus for editing and adding  posts to CPT
   	 add_submenu_page(
     	   'nyt_stories',
    	   'Edit Stories',
		   'Edit Top Stories',
           'edit_posts',
           'edit.php?post_type=nyt_top_stories'
    );
	add_submenu_page(
     	   'nyt_stories',
    	   'Add New Story',
		   'Add New Story',
           'edit_posts',
           'post-new.php?post_type=nyt_top_stories'
    );
}

function NYT_Top_Stories_Manual_Pull(){?>
	<div class="wrap"><h1>Settings - NYT Top Stories Plugin</h1><br/>
		<p>Use this button to manually update the stories from the New York Times Top Stories API</p>
		<form  method="post">
			<?php
			if (array_key_exists ('submit', $_POST)) {retrieve_top_stories();}
			wp_nonce_field(submit_button('Manually Update Stories'));?>
		</form>
	</div>
<?php
}
?>
