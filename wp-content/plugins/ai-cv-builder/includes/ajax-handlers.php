<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AI CV Builder AJAX Handlers - Adapted from user suggestion
 */

// Make sure meta constants are available (they are defined in ai-cv-builder.php)
// define( 'AICVB_META_PERSONAL_INFO', '_aicvb_personal_info' ); // Example, already defined
// define( 'AICVB_META_SUMMARY', '_aicvb_professional_summary' ); // Example, already defined
// define( 'AICVB_META_SELECTED_TEMPLATE_ID', '_aicvb_selected_template_id'); // Example, already defined

/**
 * Handles saving CV data.
 * Adapted from user's suggestion for aicvb_handle_save_cv_data.
 * Kept original function name 'aicvb_save_cv_data_ajax_handler' for consistency with existing add_action if possible,
 * or the add_action will be updated if the function name changes.
 * For this subtask, let's use 'aicvb_save_cv_data_ajax_handler' as the function name.
 */
function aicvb_save_cv_data_ajax_handler() {
    // Debug: Log that the handler was reached
    error_log('AICVB DEBUG: aicvb_save_cv_data_ajax_handler reached.');

    // Verify nonce
    // Using check_ajax_referer for standard WordPress behavior and error handling.
    // If this fails, it typically dies with a 403 status.
    // The user's manual check is also fine, but check_ajax_referer is more common.
    // Let's stick to check_ajax_referer for now as it was in the original code.
    // If it causes issues, we can switch to the user's manual verification.
    check_ajax_referer( 'aicvb_save_cv_nonce', 'nonce' );
    error_log('AICVB DEBUG: Nonce check passed.');

    // Check user capabilities (already in original code, good to keep)
    if ( ! current_user_can( 'edit_posts' ) ) {
        error_log('AICVB DEBUG: User does not have edit_posts capability.');
        wp_send_json_error( array( 'message' => __( 'You do not have permission to save CVs.', 'ai-cv-builder' ) ), 403 );
        return; // wp_send_json_error calls wp_die()
    }
    error_log('AICVB DEBUG: User capability check passed.');

    $user_id = get_current_user_id();
    if ( empty( $user_id ) ) {
        error_log('AICVB DEBUG: User not logged in.');
        wp_send_json_error( array( 'message' => __( 'User not logged in.', 'ai-cv-builder' ) ), 401 );
        return; // wp_send_json_error calls wp_die()
    }
    error_log('AICVB DEBUG: User ID: ' . $user_id);

    // Get the CV ID and template ID
    $cv_id = isset( $_POST['cv_id'] ) ? intval( $_POST['cv_id'] ) : 0;
    $template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
    error_log('AICVB DEBUG: Input CV ID: ' . (isset($_POST['cv_id']) ? $_POST['cv_id'] : 'Not set') . ' (parsed as ' . $cv_id . '), Template ID: ' . $template_id);


    // If no CV ID, create a new CV post
    if ( $cv_id === 0 ) {
        error_log('AICVB DEBUG: Creating new CV post.');
        $cv_post_title = 'Untitled CV - ' . time(); // Simple title
        if (isset($_POST['cv_data']['personal_info']['full_name']) && !empty(trim($_POST['cv_data']['personal_info']['full_name']))) {
            $cv_post_title = sanitize_text_field(trim($_POST['cv_data']['personal_info']['full_name'])) . "'s CV";
        }

        $cv_post_args = array(
            'post_title'   => $cv_post_title,
            'post_type'    => 'aicv_resume', // CRITICAL: Use actual CPT name verified in Step 1
            'post_status'  => 'publish', // Or 'draft' / 'private' as preferred
            'post_author'  => $user_id,
        );

        $new_cv_id = wp_insert_post( $cv_post_args, true ); // Pass true to get WP_Error on failure

        if ( is_wp_error( $new_cv_id ) ) {
            error_log('AICVB ERROR: Failed to create CV post: ' . $new_cv_id->get_error_message());
            wp_send_json_error( array( 'message' => 'Failed to create CV: ' . $new_cv_id->get_error_message() ) );
            return;
        }
        $cv_id = $new_cv_id; // Update cv_id to the new post ID
        error_log('AICVB DEBUG: Created new CV with ID: ' . $cv_id);

        // Save template_id for new CV
        if ( !empty($template_id) ) {
            update_post_meta( $cv_id, AICVB_META_SELECTED_TEMPLATE_ID, $template_id );
            error_log('AICVB DEBUG: Saved template_id (' . $template_id . ') for new CV ID: ' . $cv_id);
        }
    } else {
        // Existing CV: verify ownership/capability (already in original code, good to keep)
        $post = get_post( $cv_id );
        if ( ! $post || $post->post_type !== 'aicv_resume' ) { // CRITICAL: Use actual CPT name
            error_log('AICVB ERROR: Invalid CV ID or post type for existing CV. ID: ' . $cv_id);
            wp_send_json_error( array( 'message' => __( 'Invalid CV ID.', 'ai-cv-builder' ) ), 400 );
            return;
        }
        if ( $post->post_author != $user_id && ! current_user_can( 'edit_others_posts', $cv_id ) ) {
            error_log('AICVB ERROR: Permission denied for existing CV. CV_ID: ' . $cv_id . ', User_ID: ' . $user_id);
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-cv-builder' ) ), 403 );
            return;
        }
        error_log('AICVB DEBUG: Updating existing CV ID: ' . $cv_id);
        // Optionally update template_id if it's sent for existing CVs
        if ( !empty($template_id) ) {
             update_post_meta( $cv_id, AICVB_META_SELECTED_TEMPLATE_ID, $template_id );
             error_log('AICVB DEBUG: Updated template_id (' . $template_id . ') for existing CV ID: ' . $cv_id);
        }
    }

    // Process and save cv_data (personal_info, professional_summary)
    $cv_data_payload = isset( $_POST['cv_data'] ) && is_array($_POST['cv_data']) ? $_POST['cv_data'] : array();
    error_log('AICVB DEBUG: Received cv_data payload: ' . print_r($cv_data_payload, true));

    // For initial save, JS sends minimal data: personal_info[full_name] and professional_summary
    // For full saves, it sends more. This handler will save what's provided.

    $sanitized_personal_info = array();
    if ( isset( $cv_data_payload['personal_info'] ) && is_array( $cv_data_payload['personal_info'] ) ) {
        // Sanitize only 'full_name' if that's all that's expected for minimal save,
        // or expand to sanitize all allowed personal_info fields from original code.
        // For now, let's keep it simple as per user's focused example for initial save.
        if(isset($cv_data_payload['personal_info']['full_name'])){
            $sanitized_personal_info['full_name'] = sanitize_text_field($cv_data_payload['personal_info']['full_name']);
        }
        // Add other personal_info fields if they are part of the minimal payload or always expected
        // For a more complete save, you would iterate through allowed keys like in the original plugin
        // and sanitize them appropriately. Example for 'email':
        // if(isset($cv_data_payload['personal_info']['email'])){
        //     $sanitized_personal_info['email'] = sanitize_email($cv_data_payload['personal_info']['email']);
        // }
        update_post_meta( $cv_id, AICVB_META_PERSONAL_INFO, $sanitized_personal_info );
        error_log('AICVB DEBUG: Updated personal_info meta for CV ID: ' . $cv_id . ' Data: ' . print_r($sanitized_personal_info, true));
    }

    if ( isset( $cv_data_payload['professional_summary'] ) ) {
        $sanitized_summary = sanitize_textarea_field( $cv_data_payload['professional_summary'] );
        update_post_meta( $cv_id, AICVB_META_SUMMARY, $sanitized_summary );
        error_log('AICVB DEBUG: Updated professional_summary meta for CV ID: ' . $cv_id);
    }

    // Note: The user's example didn't include saving other parts of cv_data like theme settings, experience, etc.
    // For this fix, focusing on the initial save path being operational.
    // If the original code's more comprehensive sanitization and saving of other cv_data fields is needed for full saves,
    // that logic would need to be merged back carefully. The user's example is very minimal.
    // For now, this simplified save covers the initial save case.

    wp_send_json_success( array(
        'message' => 'CV data saved successfully (handler aicvb_save_cv_data_ajax_handler).',
        'cv_id'   => $cv_id
    ) );
    // wp_die() is called by wp_send_json_success
}
// Ensure this action hook uses the exact function name defined above.
add_action( 'wp_ajax_aicvb_save_cv_data', 'aicvb_save_cv_data_ajax_handler' );


// --- Stubs for other AJAX handlers as per user's suggestion to isolate the issue ---
// Or, if the original handlers are needed and were working, they can be kept.
// For this subtask, use stubs for handlers other than the save action.

function aicvb_generate_initial_cv_ajax_handler() {
    error_log('AICVB DEBUG: aicvb_generate_initial_cv_ajax_handler stub reached');
    check_ajax_referer( 'aicvb_generate_cv_nonce', 'nonce' );
    wp_send_json_error( array( 'message' => 'aicvb_generate_initial_cv_ajax_handler not fully implemented in this version.' ), 501 );
}
add_action( 'wp_ajax_aicvb_generate_initial_cv', 'aicvb_generate_initial_cv_ajax_handler' );

function aicvb_generate_field_content_ajax_handler() {
    error_log('AICVB DEBUG: aicvb_generate_field_content_ajax_handler stub reached');
    check_ajax_referer( 'aicvb_generate_field_nonce', 'nonce' );
    wp_send_json_error( array( 'message' => 'aicvb_generate_field_content_ajax_handler not fully implemented in this version.' ), 501 );
}
add_action( 'wp_ajax_aicvb_generate_field_content', 'aicvb_generate_field_content_ajax_handler' );

function aicvb_tailor_cv_ajax_handler() {
    error_log('AICVB DEBUG: aicvb_tailor_cv_ajax_handler stub reached');
    check_ajax_referer( 'aicvb_tailor_cv_nonce', 'nonce' );
    wp_send_json_error( array( 'message' => 'aicvb_tailor_cv_ajax_handler not fully implemented in this version.' ), 501 );
}
add_action( 'wp_ajax_aicvb_tailor_cv', 'aicvb_tailor_cv_ajax_handler' );

// Add the test AJAX handler from the previous plan step
function aicvb_test_ajax_handler() {
    error_log('AICVB DEBUG: aicvb_test_ajax_handler reached');
    // No nonce check for this simple test as per its original design.
    wp_send_json_success(array(
        'message' => 'AI CV Builder AJAX test successful!',
        'timestamp' => time(),
        'php_version' => phpversion()
    ));
}
add_action('wp_ajax_aicvb_test_action', 'aicvb_test_ajax_handler');

?>
