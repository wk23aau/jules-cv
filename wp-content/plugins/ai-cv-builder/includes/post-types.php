<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AI CV Builder Custom Post Types
 *
 * @package AI_CV_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register CV Custom Post Type.
 */
function aicvb_register_cv_post_type() {
    $labels = array(
        'name'               => _x( 'Resumes', 'post type general name', 'ai-cv-builder' ),
        'singular_name'      => _x( 'Resume', 'post type singular name', 'ai-cv-builder' ),
        'menu_name'          => _x( 'Resumes', 'admin menu', 'ai-cv-builder' ),
        'name_admin_bar'     => _x( 'Resume', 'add new on admin bar', 'ai-cv-builder' ),
        'add_new'            => _x( 'Add New', 'resume', 'ai-cv-builder' ),
        'add_new_item'       => __( 'Add New Resume', 'ai-cv-builder' ),
        'new_item'           => __( 'New Resume', 'ai-cv-builder' ),
        'edit_item'          => __( 'Edit Resume', 'ai-cv-builder' ),
        'view_item'          => __( 'View Resume', 'ai-cv-builder' ),
        'all_items'          => __( 'All Resumes', 'ai-cv-builder' ),
        'search_items'       => __( 'Search Resumes', 'ai-cv-builder' ),
        'parent_item_colon'  => __( 'Parent Resumes:', 'ai-cv-builder' ),
        'not_found'          => __( 'No resumes found.', 'ai-cv-builder' ),
        'not_found_in_trash' => __( 'No resumes found in Trash.', 'ai-cv-builder' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true, // True to see it in admin, but we might hide from main menu
        'show_in_menu'       => false, // Hidden from general admin menu, accessed via our settings page
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'author' ), // We will use post_meta for most fields
        'show_in_rest'       => true, // Useful for future Block Editor or REST API interactions
        'description'        => __( 'Custom Post Type for AI CV Builder Resumes.', 'ai-cv-builder' ),
        'text_domain'        => 'ai-cv-builder',
    );

    register_post_type( 'aicv_resume', $args );
}
add_action( 'init', 'aicvb_register_cv_post_type' );

?>
