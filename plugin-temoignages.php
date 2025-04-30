<?php

/**
 * Plugin Name: Témoignages Clients
 * Description: Un plugin pour gérer et afficher des témoignages clients
 * Version: 1.0
 * Author: Christelle Hendrickx
 * Author URI: /
 **/
require_once plugin_dir_path(__FILE__) . 'plugin-functions.php';

function temoignages_cpt()
{
    register_post_type('temoignage', array(
        'label' => 'Témoignage',
        'name' => 'Témoignages',
        'rewrite' => ['slug' => 'temoignage'],
        'supports' => ['title','editor', 'thumbnail'],
        'show_in_menu' => true,
        'public' => true,
        'has_archive' => true,


        'edit_post'          => 'edit_temoignage',
        'read_post'          => 'read_temoignage',
        'delete_post'        => 'delete_temoignage',
        'publish_posts'      => 'publish_temoignages',
        'edit_posts'         => 'edit_temoignages',
        'delete_posts'       => 'delete_temoignages',
        'read_private_posts' => 'read_private_temoignages',

        'add_new' => 'Ajouter un témoignage',
        'add_new_item' => 'Ajouter un nouveau témoignage',
        'edit_item' => 'Modifier le témoignage',
        'new_item' => 'Nouveau témoignage',
        'view_item' => 'Voir le témoignage',
        'menu_icon' => 'dashicons-format-quote',
    ));
}
add_action('init', 'temoignages_cpt');

function charger_plugin_style() {
    
    if (is_single()) {
        wp_enqueue_style('plugin-style', plugin_dir_url(__FILE__) . 'assets/plugin-style.css');
    }
}
add_action('wp_enqueue_scripts', 'charger_plugin_style');
