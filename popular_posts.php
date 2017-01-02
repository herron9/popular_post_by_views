<?php
/**
 * Plugin Name: IFAS-popular posts by views
 * Plugin URI: 
 * Description: This plugin counts views of each post and show 3 most popuar posts on side bar
 * Version: 1.0.0
 * Author: Herong Yang
 * Author URI: http://www.eoheaven.com
 * License: none
 */

function pv_create_table(){
    // if (!isset($wpdb)) $wpdb = $GLOBALS['wpdb'];
    global $wpdb, $pv_table_name;
    $pv_table_name = $wpdb->prefix.'popular_view';
    if($wpdb->get_var('SHOW TABLES LIKE' .$pv_table_name) != $pv_table_name){
        $create_table_sql = "CREATE TABLE $pv_table_name (
            id BIGINT(50) NOT NULL AUTO_INCREMENT, 
            post_id VARCHAR(255) NOT NULL, 
            views BIGINT(50) NOT NULL, 
            PRIMARY KEY (id), 
            UNIQUE (id)
        );";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); 
        dbDelta( $create_table_sql);
        $wpdb->flush(); // Kill cached query results.
    }
}

register_activation_hook(__FILE__, 'pv_create_table');

function pv_update_post_view(){
    // single post && not a page
    if(is_single() && !is_page()){ 
        global $wpdb, $post, $pv_table_name;
        $pv_table_name = $wpdb->prefix.'popular_view'; 
        $wpdb->flush(); 

        $entry = $wpdb->get_row("SELECT * FROM $pv_table_name WHERE post_id = '{$post->ID}';", ARRAY_A); 
        // data exists then update it, views ++
        if(!is_null($entry)){ 
            $new_views = $entry['views'] + 1; 
            $wpdb->query("UPDATE $pv_table_name SET views ='{$new_views}' WHERE post_id = '{$post->ID}';"); 
            $wpdb->flush(); 
        }
        // no corresponding entry, create a new entry
        else { 
            $wpdb->query("INSERT INTO {$pv_table_name} (post_id, views) 
                          VALUES ('{$post->ID}','1');"); 
            $wpdb->flush(); 
        }
    }
}
add_action('wp_head','pv_update_post_view'); 

function pv_views_suffix($views) {
   if ($views == 1) {
      $pv_suffix = "view";
   } else {
      $pv_suffix = "views";
   }

   return $pv_suffix;

}

add_action("wp_head", "pv_views_suffix");

class IFAS_Popular_Post extends WP_Widget {

    function __construct() {
        parent :: __construct(false, $name = __( 'IFAS Popular Post'));
        // $name : name of the widget in admin panel
    }

    function widget($args, $instance) {
        global $wpdb, $pv_table_name;
        $pv_table_name = $wpdb->prefix.'popular_view';
        //exist

        ?>
        <div id="popular" class="widget widget_post"> 
            <h3 class="widget-title">Popular</h3>
            <ul>
                <?php 
                if($wpdb->get_var("SHOW TABLES LIKE '$pv_table_name' ") == $pv_table_name) {
                 //LIMIT (from), (count)
                    $popular = $wpdb->get_results("SELECT * FROM {$pv_table_name} ORDER BY views 
                                                   DESC LIMIT 0, 3",ARRAY_N);
                    foreach($popular as $post){
                        $ID = $post[1];
                        //with a comma (",") between every group of thousands.
                        $views = number_format($post[2]); 
                        $url = get_permalink($ID); 
                        $title = get_the_title($ID); 
                        $suffix = pv_views_suffix($post[2]);
                        echo "<li><a href= '{$url}'>{$title}</a> - {$views} {$suffix }</li>";
                }
            }
                ?>
            </ul>
        </div>
    <?php             
    }
}

add_action( 'widgets_init', function() {
    register_widget( 'IFAS_Popular_Post' );
});
?>