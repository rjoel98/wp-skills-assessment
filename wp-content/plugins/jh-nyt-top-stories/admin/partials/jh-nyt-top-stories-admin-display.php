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

add_action( 'my_hourly_event', 'do_this_hourly' );
 
function my_activation() {
    wp_schedule_event( time(), 'hourly', 'my_hourly_event' );
}

//Use the NY Times Api to retrieve the current "Top Stories" 
function do_this_hourly() {
    
  $response = wp_remote_get( 'https://api.nytimes.com/svc/topstories/v2/home.json?api-key=iKdsfNemAIkxj1JGZZcFdq9YAhjShGHW' );
 
  if ( is_array( $response ) && ! is_wp_error( $response ) ) {
      $body = $response['body'];
      $results = $body['results'];
      
      foreach ($results as $article) {
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

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
