<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AI CV Builder CV Data Functions
 *
 * @package AI_CV_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Create a new CV.
 *
 * @param int    $user_id  The ID of the user creating the CV.
 * @param string $cv_title The title of the new CV.
 * @return int|WP_Error The new post ID on success, or WP_Error on failure.
 */
function aicvb_create_cv( $user_id, $cv_title ) {
    if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
        return new WP_Error( 'invalid_user_id', __( 'Invalid user ID provided.', 'ai-cv-builder' ) );
    }
    if ( empty( $cv_title ) ) {
        return new WP_Error( 'empty_cv_title', __( 'CV title cannot be empty.', 'ai-cv-builder' ) );
    }

    $post_data = array(
        'post_author'  => $user_id,
        'post_title'   => sanitize_text_field( $cv_title ),
        'post_type'    => 'aicv_resume',
        'post_status'  => 'publish', // Or 'draft' if preferred initial state
    );

    $post_id = wp_insert_post( $post_data, true ); // True to return WP_Error on failure

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    // Initialize default meta fields if needed
    // update_post_meta($post_id, AICVB_META_PERSONAL_INFO, array());
    // update_post_meta($post_id, AICVB_META_SUMMARY, '');
    // ... etc.

    return $post_id;
}

/**
 * Get CV data.
 *
 * @param int $post_id The ID of the CV post.
 * @return array|null Post data and meta as an array, or null if not found or not a CV.
 */
function aicvb_get_cv( $post_id ) {
    if ( ! $post_id || 'aicv_resume' !== get_post_type( $post_id ) ) {
        return null;
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        return null;
    }

    // Constants are defined in the main plugin file and should be available here.

    $cv_data = array(
        'id'             => $post->ID,
        'title'          => $post->post_title,
        'author'         => $post->post_author,
        'date_created'   => $post->post_date,
        'date_modified'  => $post->post_modified,
        'personal_info'  => get_post_meta( $post_id, AICVB_META_PERSONAL_INFO, true ),
        'summary'        => get_post_meta( $post_id, AICVB_META_SUMMARY, true ),
        'experience'     => get_post_meta( $post_id, AICVB_META_EXPERIENCE, true ),
        'education'      => get_post_meta( $post_id, AICVB_META_EDUCATION, true ),
        'skills'         => get_post_meta( $post_id, AICVB_META_SKILLS, true ),
        'theme_settings' => get_post_meta( $post_id, AICVB_META_THEME_SETTINGS, true ),
    );
    // Ensure meta fields that should be arrays are arrays
    $array_fields = ['personal_info', 'experience', 'education', 'skills', 'theme_settings'];
    foreach($array_fields as $field) {
        if (empty($cv_data[$field])) {
            $cv_data[$field] = array();
        }
    }


    return $cv_data;
}

/**
 * Update CV data.
 *
 * @param int   $post_id The ID of the CV post to update.
 * @param array $cv_data Associative array of CV data to save.
 * @return bool True on success, false on failure.
 */
function aicvb_update_cv( $post_id, $cv_data ) {
    if ( ! $post_id || 'aicv_resume' !== get_post_type( $post_id ) ) {
        return false;
    }

    // We might want to update post_title if it's part of $cv_data
    if ( isset( $cv_data['title'] ) ) {
        wp_update_post( array( 'ID' => $post_id, 'post_title' => sanitize_text_field( $cv_data['title'] ) ) );
        unset( $cv_data['title'] ); // Don't try to save it as meta
    }

    // Constants are defined in the main plugin file and should be available here.
    $meta_map = array(
        'personal_info'  => AICVB_META_PERSONAL_INFO,
        'summary'        => AICVB_META_SUMMARY,
        'experience'     => AICVB_META_EXPERIENCE,
        'education'      => AICVB_META_EDUCATION,
        'skills'         => AICVB_META_SKILLS,
        'theme_settings' => AICVB_META_THEME_SETTINGS,
    );

    foreach ( $meta_map as $data_key => $meta_key_constant ) {
        if ( isset( $cv_data[ $data_key ] ) ) {
            // Sanitize appropriately based on expected data type.
            // WordPress's update_post_meta handles some sanitization, especially for arrays/objects.
            // For complex data, ensure $cv_data is well-structured before calling this function.
            update_post_meta( $post_id, $meta_key_constant, $cv_data[ $data_key ] );
        }
    }

    return true;
}

/**
 * Delete a CV.
 *
 * @param int $post_id The ID of the CV post to delete.
 * @param int $user_id The ID of the user attempting the deletion.
 * @return bool True on success, false on failure.
 */
function aicvb_delete_cv( $post_id, $user_id ) {
    if ( ! $post_id || 'aicv_resume' !== get_post_type( $post_id ) ) {
        return false;
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        return false;
    }

    // Check if user is the author or has capabilities to delete others' posts
    if ( $post->post_author == $user_id || current_user_can( 'delete_others_posts', $post_id ) ) {
        $deleted = wp_delete_post( $post_id, true ); // True to force delete, false to trash
        return $deleted !== false;
    }

    return false;
}

/**
 * Get all CVs for a specific user.
 *
 * @param int $user_id The ID of the user.
 * @return array Array of CV post objects (or post IDs).
 */
function aicvb_get_user_cvs( $user_id ) {
    if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
        return array();
    }

    $args = array(
        'post_type'   => 'aicv_resume',
        'author'      => $user_id,
        'post_status' => 'publish', // Or array('publish', 'draft')
        'numberposts' => -1, // Get all posts
        'orderby'     => 'date',
        'order'       => 'DESC',
    );

    $cv_posts = get_posts( $args );

    // Optionally, instead of full post objects, return an array of aicvb_get_cv structured data
    // For now, returning post objects is fine.
    return $cv_posts;
}

?>
