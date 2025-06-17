<?php
/**
 * AI CV Builder AJAX Handlers
 *
 * @package AI_CV_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * AJAX handler for saving CV data.
 */
function aicvb_save_cv_data_ajax_handler() {
    // Verify nonce
    check_ajax_referer( 'aicvb_save_cv_nonce', 'nonce' );

    // Check user capabilities
    if ( ! current_user_can( 'edit_posts' ) ) { // Or a more specific capability
        wp_send_json_error( array( 'message' => __( 'You do not have permission to save CVs.', 'ai-cv-builder' ) ), 403 );
        return;
    }

    $user_id = get_current_user_id();
    if ( empty( $user_id ) ) {
        wp_send_json_error( array( 'message' => __( 'User not logged in.', 'ai-cv-builder' ) ), 401 );
        return;
    }

    $cv_id   = isset( $_POST['cv_id'] ) ? intval( $_POST['cv_id'] ) : 0;
    $cv_data = isset( $_POST['cv_data'] ) && is_array( $_POST['cv_data'] ) ? $_POST['cv_data'] : array();
    $selected_template = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : 'default';


    // --- CV Creation/Loading Logic ---
    if ( $cv_id === 0 ) {
        // Create a new CV post
        // For title, we could use data from $cv_data or a default
        $cv_title = isset( $cv_data['personal_info']['full_name'] ) && !empty(trim($cv_data['personal_info']['full_name']))
            ? trim($cv_data['personal_info']['full_name']) . "'s CV"
            : 'Untitled CV';
        $new_cv_id = aicvb_create_cv( $user_id, $cv_title );

        if ( is_wp_error( $new_cv_id ) ) {
            wp_send_json_error( array( 'message' => $new_cv_id->get_error_message() ) );
            return;
        }
        $cv_id = $new_cv_id;
        // Add selected template as meta
        update_post_meta($cv_id, '_aicvb_selected_template', $selected_template);

    } else {
        // Existing CV, check ownership/permission
        $post = get_post( $cv_id );
        if ( ! $post || $post->post_type !== 'aicv_resume' ) {
            wp_send_json_error( array( 'message' => __( 'Invalid CV ID.', 'ai-cv-builder' ) ), 400 );
            return;
        }
        if ( $post->post_author != $user_id && ! current_user_can( 'edit_others_posts', $cv_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-cv-builder' ) ), 403 );
            return;
        }
        // Update selected template if it changed or was not set
        update_post_meta($cv_id, '_aicvb_selected_template', $selected_template);
    }

    // --- Sanitize CV Data ---
    // This is a simplified sanitization. Real-world usage would require more robust and specific sanitization
    // based on the structure of each field (e.g., arrays of objects for experience).
    $sanitized_cv_data = array();

    if ( isset( $cv_data['personal_info'] ) && is_array( $cv_data['personal_info'] ) ) {
        $sanitized_cv_data['personal_info'] = array_map( 'sanitize_text_field', $cv_data['personal_info'] );
    }
    if ( isset( $cv_data['professional_summary'] ) ) {
        // Keep the key as 'summary' for saving, consistent with AICVB_META_SUMMARY
        $sanitized_cv_data[AICVB_META_SUMMARY] = sanitize_textarea_field( $cv_data['professional_summary'] );
    }

    // Sanitize Theme Settings
    if ( isset( $cv_data['aicv_selected_theme_class'] ) ) {
        $sanitized_cv_data[AICVB_META_SELECTED_THEME] = sanitize_text_field( $cv_data['aicv_selected_theme_class'] );
    }
    if ( isset( $cv_data['aicv_primary_color'] ) ) {
        // Basic hex color sanitization
        $color = sanitize_hex_color( $cv_data['aicv_primary_color'] );
        if ( $color ) {
            $sanitized_cv_data[AICVB_META_PRIMARY_COLOR] = $color;
        }
    }
    if ( isset( $cv_data['aicv_font_family'] ) ) {
        // Allow common font characters, but basic sanitization for now
        $sanitized_cv_data[AICVB_META_FONT_FAMILY] = sanitize_text_field( $cv_data['aicv_font_family'] );
    }
    // The initial template ID (functional choice) is passed as 'template_id' in AJAX, not in 'cv_data' object
    // It's saved directly in the AJAX handler when CV is created or updated.
    // update_post_meta($cv_id, AICVB_META_SELECTED_TEMPLATE_ID, $selected_template); // This is already handled earlier


    // Basic sanitization for repeatable fields (experience, education, skills)
    // These would need more complex loop-based sanitization if they contain nested arrays/objects
    // For now, we assume they are arrays of strings or arrays of arrays of strings.
    $repeatable_sections = array(
        'experience' => AICVB_META_EXPERIENCE,
        'education' => AICVB_META_EDUCATION,
        'skills' => AICVB_META_SKILLS,
    );

    foreach ($repeatable_sections as $data_key => $meta_key) {
        if (isset($cv_data[$data_key]) && is_array($cv_data[$data_key])) {
            $sanitized_entries = array();
            foreach ($cv_data[$data_key] as $entry) {
                if (is_array($entry)) {
                    $sanitized_entry = array();
                    foreach ($entry as $field_key => $field_value) {
                        // More specific sanitization could be applied based on field_key
                        if (is_array($field_value)) { // Should not happen with current structure
                             $sanitized_entry[sanitize_key($field_key)] = array_map('sanitize_text_field', $field_value);
                        } else if ($field_key === 'description'){
                             $sanitized_entry[sanitize_key($field_key)] = sanitize_textarea_field($field_value);
                        } else {
                             $sanitized_entry[sanitize_key($field_key)] = sanitize_text_field($field_value);
                        }
                    }
                    $sanitized_entries[] = $sanitized_entry;
                } else {
                    // If entries are simple strings (e.g. for skills if not objects)
                    $sanitized_entries[] = sanitize_text_field($entry);
                }
            }
            $sanitized_cv_data[$meta_key] = $sanitized_entries;
        } else {
             $sanitized_cv_data[$meta_key] = array();
        }
    }


    // --- Update CV Meta ---
    // aicvb_update_cv expects data keys to be the actual meta keys if it's iterating.
    // Or, if aicvb_update_cv is smarter, it could map them.
    // The current structure of sanitized_cv_data now uses defined meta keys.
    $updated = aicvb_update_cv( $cv_id, $sanitized_cv_data );

    if ( $updated ) {
        wp_send_json_success( array(
            'message' => __( 'CV Saved!', 'ai-cv-builder' ),
            'cv_id'   => $cv_id,
        ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'Failed to save CV data.', 'ai-cv-builder' ) ) );
    }
}
add_action( 'wp_ajax_aicvb_save_cv_data', 'aicvb_save_cv_data_ajax_handler' );

?>
