<?php
/*
Plugin Name: WP Category Thumbnail
Plugin URI: http://www.nettantra.com/
Description: This plugin provides thumbnails for categories fetched from the latest page/post of the corresponding category. The post thumbnail are generated either from its featured image or from the first available image in the post.
Author: Pitabas Behera
Author URI: http://www.nettantra.com/
Version: 0.9
*/

// Version Check
global $wp_version;
if( ! function_exists( 'wpct_dependencies_unmet_warning' ) ):
  function wpct_dependencies_unmet_warning() {
    echo '<div class="error fade"><p>'.__( 'WP Category Thumbnail Wordpress 3.0 or Newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update!</a>' ).'. <br />'.__( 'The plugin could not be activated due to unmet dependencies.' ).'</p></div>';
    unset( $_GET['activate'] );
  }
endif;
if( version_compare( $wp_version, "3.0", "<" ) ) {
  include_once( ABSPATH.'/wp-admin/includes/plugin.php' );
  deactivate_plugins( plugin_basename( __FILE__ ), true );
  add_action( 'admin_notices', 'wpct_dependencies_unmet_warning', 100 );
}

//register widget
 add_action( 'widgets_init', 'wpct' );
 function wpct() {
  register_widget( 'WP_Category_Thumbnail' );
 }

//define path
if( ! defined( 'DS' ) )
  define( 'DS', DIRECTORY_SEPARATOR );
  
if( ! defined( 'WPCT_PLUGIN_DIR' ) )
  define( 'WPCT_PLUGIN_DIR', dirname( __FILE__ ) );

if ( ! defined( 'WPCT_PLUGIN_BASENAME' ) )
  define( 'WPCT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if( ! defined( 'WPCT_PLUGIN_URL' ) )
  define( 'WPCT_PLUGIN_URL', WP_PLUGIN_URL.'/'.str_replace( basename( __FILE__ ), '', WPCT_PLUGIN_BASENAME ) );

//widget class
class WP_Category_Thumbnail extends WP_Widget {
  function wp_category_thumbnail() {
    parent::WP_Widget( false, $name= 'WP Category Thumbnail' );
    add_action( 'wp_print_scripts', array( $this, 'load_script' ) );
  }

  //script load
  function load_script() {
    echo '<link rel="stylesheet" type="text/css" href="' .WPCT_PLUGIN_URL.'css/thumb.css" />'."\n";
    wp_enqueue_script( 'jquery', '1.4.2' );
    wp_enqueue_script( 'jquery-thumb', WPCT_PLUGIN_URL. 'js/thumb.1.0.js', '1.0' );
  }
  
  //widget function
  function widget( $args, $instance ) {
    extract( $args );
    echo $before_widget;
      
      //WP post Query
      $latestpost = new WP_Query();
      $latestpost->query( array(
         "showposts"   => $instance['num_post'],
         "cat"         => $instance['category'],
         "post_type"   => $instance['post_type'],
         "orderby"     => "date",
         "order"       => "DESC",
       ) );

      //add new_excerpt_more
      if( $instance['more_link'] ) {
        if( ! function_exists( 'new_excerpt_more' ) ) {
          function new_excerpt_more() {
            global $post;
            return '<a class="wpct-read-more" href="'. get_permalink( $post->ID ) .'"> Read more &raquo;</a>';
          }
        }
       add_filter( 'excerpt_more', 'new_excerpt_more' );
      }

      //define length for excerpt
      $new_length = create_function( '$length', "return " .$instance['excerpt_length']. ";" );
      if( $instance['excerpt_length'] > 0 )
      add_filter( 'excerpt_length', $new_length );
      
      // display widget content
      if( ! empty( $instance['title'] ) )
        echo $before_title . $instance['title'] . $after_title;
         ?>
          <ul class="wpct custom-wpct">
            <?php 
              while( $latestpost->have_posts() ):
               $latestpost->the_post();
               $upload_dir = wp_upload_dir(); 
              $featureImage = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), array( 200,200 ), false, '' );
             //Found the images from the post content
              global $post, $posts;
              $postContent = $post->post_content;
              $searchimages = '~<img [^>]* />~';

              preg_match_all( $searchimages, $postContent, $post_imgs );

              // Check to see if we have at least 1 image
              $findImages = count( $post_imgs[0] );
              
              /*
              * Here check the post have a image then display the Post data otherwise the post data out of the loop.
              */
              
              if ( $findImages >  0 ) : ?>
              <li>
                <div class="wpct-wrap custom-wpct-wrap" style="width: <?php echo $instance['thumb_width'].'px'; ?>; height: <?php echo $instance['thumb_height'].'px'; ?>;">

                <?php 
                //get the first image from post 
                if( ! function_exists( 'catch_that_image' ) ) {
                  function catch_that_image() {
                    global $post, $posts;
                    $first_img = '';
                    ob_start();
                    ob_end_clean();
                    $output = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches );
                    $first_img = $matches [1] [0];
                    return $first_img;
                  }
                }
                ?>
                
                <?php $upload_dir = wp_upload_dir();
                //if the post have no image, the featured image have display
                if ( has_post_thumbnail( $post->ID ) ): ?>
                  <?php 
                  //Returns an array with the image attributes "url", "width" and "height", of an image attachment file.
                  $featureImage = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), array( 720,405 ), false, '' ); ?>
                  <img class="cat-img" src="<?php echo WPCT_PLUGIN_URL; ?>scripts/timthumb.php?src=<?php echo $featureImage[0]; ?>&amp;w=<?php echo $instance['thumb_width'] ?>&amp;h=<?php echo $instance['thumb_height']; ?>&amp;zc=1" title="<?php the_title(); ?>" />
                  <?php else: ?>
                   <img class="cat-img" src="<?php echo WPCT_PLUGIN_URL; ?>scripts/timthumb.php?src=<?php echo catch_that_image(); ?>&amp;w=<?php echo $instance['thumb_width'] ?>&amp;h=<?php echo $instance['thumb_height']; ?>&amp;zc=1" title="<?php the_title(); ?>" />
                <?php endif; ?>  

                  <div class="wpct-box custom-wpct-box" style="width: <?php echo $instance['thumb_width'].'px'; ?>; height: <?php echo $instance['thumb_height'].'px'; ?>; background: <?php echo $instance['overlay_color']; ?>; color: <?php echo $instance['content_color']; ?>;">
                  <?php
                  /*
                  * define the overlay height and width
                  */
                   ?>
                  <a style="width: <?php echo $instance['thumb_width'].'px'; ?>; height: <?php echo $instance['thumb_height'].'px'; ?>;"  class="overlay" href="<?php echo get_permalink( $post->ID ); ?>" title="<?php the_title(); ?>"></a>
                  
                    <div class="wpct-box-content">
                    <?php if( $instance['show_title'] ):?>
                      <h2 class="thumb-title"><a href="<?php echo get_permalink( $post->ID ); ?>"><?php the_title(); ?></a></h2>
                     <?php endif; //show_title ?>
                      <?php if( $instance['post_date'] ): ?>
                      <span class="thumb-date"><?php the_time( 'jS F, Y' ); ?></span>
                      <?php endif; ?>
                      <?php if( $instance['comment_num'] ): ?>
                      <span class="comment-num"><?php comments_number(); ?></span>
                      <?php endif; ?>
                      <?php the_excerpt(); ?>
                    </div><!--.wpct-box-content-->
                 </div>
                </div><!--.wpct_box-wrap-->
              </li>
          <?php // check the post type or first image then use timthumb
          elseif( ! $findImages && $featureImage ): ?>
              <li>
                <div class="wpct-wrap custom-box-wrap" style="width: <?php echo $instance['thumb_width'].'px'; ?>; height: <?php echo $instance['thumb_height'].'px'; ?>;">
                <?php 
                //check the post type or first image then use timthumb
                if ( has_post_thumbnail( $post->ID ) ): ?>
                  <?php 
                  //Returns an array with the image attributes "url", "width" and "height", of an image attachment file.
                  $featureImage = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), array( 720,405 ), false, '' ); ?>
                  <img class="cat-img" src="<?php echo WPCT_PLUGIN_URL; ?>scripts/timthumb.php?src=<?php echo $featureImage[0]; ?>&amp;w=<?php echo $instance['thumb_width'] ?>&amp;h=<?php echo $instance['thumb_height']; ?>&amp;zc=1" title="<?php the_title(); ?>" />
                <?php endif; ?>

                  <div class="wpct-box custom-wpct-box" style="width: <?php echo $instance['thumb_width'].'px'; ?>; height: <?php echo $instance['thumb_height'].'px'; ?>; background: <?php echo $instance['overlay_color']; ?>; color: <?php echo $instance['content_color']; ?>;">
                  <?php
                  /*
                  * define the overlay link height and width
                  */
                   ?>
                  <a style="width: <?php echo $instance['thumb_width'].'px'; ?>; height: <?php echo $instance['thumb_height'].'px'; ?>;"  class="overlay" href="<?php echo get_permalink( $post->ID ); ?>" title="<?php the_title(); ?>"></a>
                    <div class="wpct-box-content">
                   <?php if( $instance['show_title'] ):?>
                      <h2 class="thumb-title"><a href="<?php echo get_permalink( $post->ID ); ?>"><?php the_title(); ?></a></h2>
                     <?php endif; //show_title?>
                      <?php if( $instance['post_date'] ): ?>
                      <span class="thumb-date"><?php the_time( 'jS F, Y' ); ?></span>
                      <?php endif; ?>
                      <?php if( $instance['comment_num'] ): ?>
                      <span class="comment-num"><?php comments_number(); ?></span>
                      <?php endif; ?>
                      <?php the_excerpt(); ?>
                    </div><!--.wpct-box-content-->
                 </div>
                </div><!--.wpct-box-wrap-->
              </li>
            <?php endif; //$findImages ?>

          <?php endwhile; ?>
        </ul>
         <?php 
    echo $after_widget;
  }//widget function
  
  //widget update option
  function update_i18n( $new_instance, $old_instance ) {
  }

  //widget form 
  function form( $instance ) {
   ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>">
        <?php _e( 'Title:' ); ?>
        <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr_e( $instance['title'] ); ?>" class="widefat" />
      </label>
    </p>
  
      <p>
        <label>
          <?php _e( 'Post Type' ); ?>:
          <select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>">
            <?php 
            $post_types = array( 'post'=>'Posts', 'page'=>'Pages' );
            foreach( $post_types as $id => $post_type ): ?>
              <option value="<?php echo $id?>" <?php echo selected($id, $instance['post_type'])?>><?php echo $post_type?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </p>
  
    <p>
      <label>
        <?php _e( 'Category' ); ?>:
        <?php wp_dropdown_categories( array(
            'show_option_all'  => 'all categories',
            'selected'         => $instance['category'],
            'name'             => $this->get_field_name( 'category' ),
          ) );
       ?> 
      </label>
    </p>
    
    <p>
      <label for="<?php echo $this->get_field_id( 'num_post' ); ?>">
        <?php _e( 'Number of posts to show:' ); ?>
        <input id="<?php echo $this->get_field_id( 'num_post' ); ?>" name="<?php echo $this->get_field_name( 'num_post' ); ?>" type="text" value="<?php echo (int) $instance['num_post']; ?>" size="3" />
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'excerpt_length' ); ?>">
        <?php _e( 'Excerpt length (in words):' ); ?>
        <input id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" type="text" value="<?php echo  $instance['excerpt_length']; ?>" size="3" />
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'show_title' ); ?>">
        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_title' ); ?>" name="<?php echo $this->get_field_name( 'show_title' ); ?>"  <?php checked( ( bool )$instance['show_title'], true ); ?> />
       <?php _e( 'Show Post title' ); ?>
     </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'post_date' ); ?>">
        <input class="checkbox" type="checkbox" name="<?php echo $this->get_field_name( 'post_date' ); ?>" id="<?php echo $this->get_field_id( 'post_date' ); ?>" <?php checked( ( bool ) $instance['post_date'], true ); ?> />
        <?php _e( 'Show Post date' ); ?>
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'comment_num' ); ?>">
        <input type="checkbox" name="<?php echo $this->get_field_name( 'comment_num' ); ?>" id="<?php echo $this->get_field_id( 'comment_num' ); ?>" class="checkbox" <?php echo checked( ( bool )$instance['comment_num'], true ); ?> />
        <?php _e( 'Show number of Comment' ); ?>
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'more_link' ); ?>">
        <input type="checkbox" class="checkbox" name="<?php echo $this->get_field_name( 'more_link' ); ?>" id="<?php echo $this->get_field_id( 'more_link' ); ?>"  <?php checked( ( bool ) $instance['more_link'], true ); ?> />
      <?php _e( 'Show more link' ); ?>
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'overlay_color' ); ?>">
        <?php _e( 'Overlay background color:' ); ?>
        <input id="<?php echo $this->get_field_id( 'overlay_color' ); ?>" name="<?php echo $this->get_field_name( 'overlay_color' ); ?>" type="text" value="<?php echo $instance['overlay_color']; ?>" size="5" />
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id( 'content_color' ); ?>">
        <?php _e( 'Overlay content color:' ); ?>
        <input id="<?php echo $this->get_field_id( 'content_color' ); ?>" name="<?php echo $this->get_field_name( 'content_color' ); ?>" type="text" value="<?php echo $instance['content_color']; ?>" size="5" />
      </label>
    </p>

    <p>
      <label><?php _e( 'Thumbnail dimensions:' ); ?></label>
        <div>
        <label>
          <label for="<?php echo $this->get_field_id( 'thumb_width' ); ?>">
            <?php _e( 'W:' ); ?><input type="text" name="<?php echo $this->get_field_name( 'thumb_width' ); ?>" id="<?php echo $this->get_field_id( 'thumb_width' ); ?>" value="<?php echo $instance['thumb_width']; ?>" size="6"/>
          </label>
          <label for="<?php echo $this->get_field_id( 'thumb_height' ); ?>">
            <?php _e( 'H:' ); ?><input type="text" name="<?php echo $this->get_field_name( 'thumb_height' ); ?>" id="<?php echo $this->get_field_id( 'thumb_height' ); ?>" value="<?php echo $instance['thumb_height']; ?>" size="6"/>
          </label>
        </div>
    </p>

    <?php
  }//form end
  
}//WP_Category_Thumbnail class
