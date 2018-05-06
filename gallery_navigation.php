<?php
/*
Plugin Name: Gallery Navigation
Plugin URI: https://www.jjlmoya.es/wordpress/plugins/gallery-navigation
Description: Plugin para crear de un post una llamada a todos los hijos de esta categoría
Author: jjlmoya
Author URI: https://jjlmoya.es
License: GPLv2 o posterior
*/


function zh_get_tags($post_tags) {
    $tagList = [];
    if($post_tags) {
        foreach( $post_tags as $tag) :
            array_push($tagList, $tag->name);
        endforeach;
    }
    return $tagList;
}

function zh_get_post_query_settings($catName) {
    return array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'category_name' => $catName
    );
}

function zh_get_page_query_settings() {
    return array(
        'post_type' => 'page',
        'posts_per_page' => -1
    );
}

function zh_get_the_posts($catName){
    $posts = [];
    $args2 = zh_get_post_query_settings($catName);
    $query2 = new WP_Query($args2);
    while($query2->have_posts()) : $query2->the_post();
        $post = new stdClass();
        $post -> title = get_the_title();
        $post -> url = get_the_permalink();
        $post -> image = get_the_post_thumbnail_url();
        if(get_the_tags()) {
            $post -> tags = zh_get_tags(get_the_tags());
        }
        array_push($posts, $post);
        unset($post);
    endwhile;
    wp_reset_query();
    return $posts;
}

function zh_get_json_post() {
    $args = zh_get_page_query_settings();

    $pages = [];
    $query = new WP_Query($args);
    while($query->have_posts()) : $query->the_post();
        $page = new stdClass();
        $page -> name = get_the_title();
        $page -> url = get_the_permalink();
        if ($page -> name) {
            $page -> image = get_the_post_thumbnail_url();
            $page -> posts = zh_get_the_posts($page -> name);
            if(get_the_tags()) {
                $page -> tags = zh_get_tags(get_the_tags());
            }
            array_push($pages, $page);
        }
        unset($page);
    endwhile; wp_reset_query();
    return $pages;
}


function zh_render_gallery_element_template($headerSize){
    $image = get_the_post_thumbnail_url(get_the_ID());
    if(WP_DEBUG){
        $image = $image ? $image : '/wp-content/plugins/gallery-navigation/debug-image.png';
    }
    return '<div class="zh-gallery-template" data-template="default"></div>';
}

function showCatBlock( $atts ){
    wp_enqueue_style('gallery-navigation', plugins_url("gallery-navigation/").'styles.css');//la ruta de nuestro css
    extract(shortcode_atts(
        array('cat'=> '',
            'tags' => '',
            'h' => '3',
            'template' => 'single'
        ), $atts));
    return '<div class="zh-gallery-template"
                 data-template="'. $template .'"
                 data-header="'. $h .'"
                 data-tags="'. $tags .'"
                 data-category="'.$cat .'"></div>';
}


function zh_widget_gallery_scripts() {
    wp_enqueue_script( 'lodash', plugin_dir_url(__FILE__) . '/scripts/lodash.js', array(), '20150623', true );
    wp_enqueue_script( 'handlebars', plugin_dir_url(__FILE__) . '/scripts/handlebars.js', array(), '20150623', true );
    wp_enqueue_script( 'gallery_navigation', plugin_dir_url(__FILE__) . '/scripts/index.js', array('handlebars', 'lodash', 'jquery'), '20150623', true );
}

add_shortcode('showCat', 'showCatBlock');

add_action('wp_enqueue_scripts', 'zh_widget_gallery_scripts');
add_action('publish_post',
    function($ID, $post) {
        file_put_contents(plugin_dir_path(__FILE__) . '/map.json',
            json_encode(zh_get_json_post())
        );
    }, 10, 2);

add_action('publish_page',
    function($ID, $post) {
        file_put_contents(plugin_dir_path(__FILE__) . '/map.json',
            json_encode(zh_get_json_post())
        );
    }, 10, 2);

?>

