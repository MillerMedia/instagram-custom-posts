<?php
/**
 * Plugin Name: Bizzy Chicks Insta-tiles
 * Description: Instagram Tile Feed with Search
 * Version: 1.0.0
 * Author: Miller Media (Matt Miller)
 * Author URI: http://www.millermedia.io
 * License: GPL2
 */

// Add max_id to end to get the next page
//?max_id=1159671764610578339_5322684

add_shortcode('instatiles','instatiles_callback');

function instatiles_callback()
{
    // Enqueue front-end files
    wp_enqueue_style('instatiles-frontend', plugins_url('instatiles/css/frontend.css'));
    wp_enqueue_style('instatiles-main', plugins_url('instatiles/css/main.css'));
    wp_enqueue_style('instatiles-overview', plugins_url('instatiles/css/overview.css'));

    wp_enqueue_script('jquery');
    wp_enqueue_script('instastiles-wookmark', plugins_url('instatiles/js/wookmark.js'), array('jquery'));
    wp_enqueue_script('instastiles-frontend', plugins_url('instatiles/js/frontend.js'), array('jquery'));

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://www.instagram.com/mxmastamills/media/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch);

    curl_close($ch);

    $json = json_decode($server_output, true);
    $images = $json["items"];

    $output = "<div id='instatiles-container'>";
    $output .= "<div class='form-group'>";
    $output .= "<input type='text' placeholder='Search Posts' name='insta-search' class='col-xs-8' />";
    $output .= "</div>";
    $output .= "<ul id='tiles'>";

    foreach ($images as $image) {
        $image_url = $image["images"]["standard_resolution"]["url"];
        $caption = $image["caption"]["text"];
        $id = $image["id"];

        $output .= "<li class='ig-tile'><img src='".$image_url."' /><p class='ig-caption'>".$caption."</p></li>";

    }

    $output .= "</ul>";
    $output .= "</div>";

    echo $output;
}