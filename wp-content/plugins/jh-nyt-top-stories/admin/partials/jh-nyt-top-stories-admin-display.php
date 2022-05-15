<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.janushenderson.com/
 * @since      1.0.0
 *
 * @package    Jh_Nyt_Top_Stories
 * @subpackage Jh_Nyt_Top_Stories/admin/partials
 */

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
  
        )
    );
}
// Hooking up our function to theme setup
add_action( 'init', 'create_posttype' );



//Setup a function that automatically runs every hour
register_activation_hook( __FILE__, 'my_activation' );

add_action( 'my_hourly_event', 'retrieve_top_stories' );
 
function my_activation() {
    wp_schedule_event( time(), 'hourly', 'my_hourly_event' );
}

//Set and action for the hourly data
add_action( 'wp_ajax_top_stories_action', 'retrieve_top_stories' );

//Use the NY Times Api to retrieve the current "Top Stories" 
function retrieve_top_stories() {
    
  $response = wp_remote_get( 'https://api.nytimes.com/svc/topstories/v2/home.json?api-key=iKdsfNemAIkxj1JGZZcFdq9YAhjShGHW' );
 
  if ( is_array( $response ) && ! is_wp_error( $response ) ) {
      $body = $response['body'];
      $results = $body['results'];
      
      foreach ($results as $article) {
        global $wpdb;
        
        $my_post = array(
          'post_title' => $article['title'],
          'post_excerpt' => $article['abstract'],
          'post_date' => $article['published_date'],
          'post_status' => 'publish',
          'post_author' => 1,
          'meta_input' => array( 'URL' => $article['url'],'byline' => $article['byline']),
          'post_category' => $article['section'],
          'tags_input' => array( $article['des_facet'])
          );
        
          //Check if post exists/insert into the database if it does not.
          post_exists( $my_post ) or wp_insert_post( $my_post );
      }
   }
}

WP_CLI::add_command( 'retrieve top stories',
	function () {
		WP_CLI::line( 'Starting...' );
		try {
			retrieve_top_stories();
		} catch ( \Throwable $e ) {
			WP_CLI::error( $e->getMessage() );
			throw $e;
		}

		WP_CLI::success( "Success! Most recent stories have been uploaded to the site." );
	}
);


add_action( 'admin_footer', 'manually_pull_top_stories' );

function manually_pull_top_stories() { ?>
	<script type="text/javascript" >
	function top_stories_ajax_call() {

		var data = {
			'action': 'top_stories_action',
		};

		jQuery.post(ajaxurl, data, function(response) {
			alert('Got this from the server: ' + response);
		});
	});
	</script> <?php
}

add_action('admin_menu', 'nyt_top_stories');


function nyt_top_stories(){
    add_menu_page('NYTTopStories', 'NYT_Top_Stories', 'manage_options', 'nyt_stories', 'NYT_Top_Stories');
    
}

function Your_Plugin_text(){?>
	<div class="wrap">
	<h1>your first plugin</h1>
	<button onclick="top_stories_ajax_call()">Update Stories</button>
	</div>
<?php
}
?>


<!-- This file should primarily consist of HTML with a little bit of PHP. -->
