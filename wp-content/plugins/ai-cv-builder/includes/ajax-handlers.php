<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
    $sanitized_cv_data = array();
    $allowed_personal_info_keys = array('full_name', 'email', 'phone', 'address', 'website');
    $allowed_theme_keys = array('aicv_selected_theme_class', 'aicv_primary_color', 'aicv_font_family');

    // Sanitize Personal Information
    if ( isset( $cv_data['personal_info'] ) && is_array( $cv_data['personal_info'] ) ) {
        $temp_personal_info = array();
        foreach ( $cv_data['personal_info'] as $key => $value ) {
            if ( in_array( $key, $allowed_personal_info_keys ) ) {
                if ( $key === 'email' ) {
                    $temp_personal_info[ sanitize_key( $key ) ] = sanitize_email( $value );
                } elseif ( $key === 'website' ) {
                    $temp_personal_info[ sanitize_key( $key ) ] = esc_url_raw( $value );
                } elseif ( $key === 'address' ) {
                    $temp_personal_info[ sanitize_key( $key ) ] = sanitize_textarea_field( $value );
                } else {
                    $temp_personal_info[ sanitize_key( $key ) ] = sanitize_text_field( $value );
                }
            }
        }
        $sanitized_cv_data[AICVB_META_PERSONAL_INFO] = $temp_personal_info; // Save under the single meta key
    }

    // Sanitize Professional Summary
    if ( isset( $cv_data['professional_summary'] ) ) {
        $sanitized_cv_data[AICVB_META_SUMMARY] = sanitize_textarea_field( $cv_data['professional_summary'] );
    }

    // Sanitize Theme Settings (Iterating through expected keys from form)
    foreach($allowed_theme_keys as $key) {
        if (isset($cv_data[$key])) {
            if ($key === 'aicv_selected_theme_class') {
                $sanitized_cv_data[AICVB_META_SELECTED_THEME] = sanitize_key($cv_data[$key]);
            } elseif ($key === 'aicv_primary_color') {
                $color = sanitize_hex_color($cv_data[$key]);
                if ($color) $sanitized_cv_data[AICVB_META_PRIMARY_COLOR] = $color;
            } elseif ($key === 'aicv_font_family') {
                // Basic sanitize, assuming it's a font stack string
                $sanitized_cv_data[AICVB_META_FONT_FAMILY] = sanitize_text_field($cv_data[$key]);
            }
        }
    }
    // $selected_template is already sanitized with sanitize_text_field at the beginning.
    // update_post_meta($cv_id, AICVB_META_SELECTED_TEMPLATE_ID, $selected_template); // This is handled earlier


    // Sanitize Repeatable Sections (Experience, Education, Skills)
    $repeatable_sections_map = array(
        'experience' => array(
            'meta_key' => AICVB_META_EXPERIENCE,
            'fields' => array('job_title', 'company', 'dates', 'description')
        ),
        'education'  => array(
            'meta_key' => AICVB_META_EDUCATION,
            'fields' => array('degree', 'institution', 'dates', 'description')
        ),
        'skills'     => array(
            'meta_key' => AICVB_META_SKILLS,
            'fields' => array('skill_name') // Assuming skills are objects like {skill_name: '...'}
        ),
    );

    foreach ($repeatable_sections_map as $data_key => $config) {
        $meta_key = $config['meta_key'];
        $allowed_fields = $config['fields'];
        $sanitized_entries = array();

        if (isset($cv_data[$data_key]) && is_array($cv_data[$data_key])) {
            foreach ($cv_data[$data_key] as $entry) {
                if (is_array($entry)) {
                    $sanitized_entry = array();
                    foreach ($allowed_fields as $field_key) {
                        if (isset($entry[$field_key])) {
                            if ($field_key === 'description') {
                                $sanitized_entry[$field_key] = sanitize_textarea_field($entry[$field_key]);
                            } else {
                                $sanitized_entry[$field_key] = sanitize_text_field($entry[$field_key]);
                            }
                        }
                    }
                    // Only add if the sanitized entry has actual content (ignoring empty/default entries)
                    if (!empty(array_filter($sanitized_entry, function($value) { return trim($value) !== ''; }))) {
                        $sanitized_entries[] = $sanitized_entry;
                    }
                }
                // If entry is a simple string (e.g. for an older skills format, though current JS sends objects)
                // This part might not be needed if JS always sends objects for skills.
                // else if (is_string($entry) && $data_key === 'skills') {
                //    $trimmed_skill = trim(sanitize_text_field($entry));
                //    if (!empty($trimmed_skill)) $sanitized_entries[] = array('skill_name' => $trimmed_skill);
                // }
            }
        }
        $sanitized_cv_data[$meta_key] = $sanitized_entries; // Save even if empty, to clear out old data
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


/**
 * AJAX handler for generating initial CV content using Gemini.
 */
function aicvb_generate_initial_cv_ajax_handler() {
    check_ajax_referer( 'aicvb_generate_cv_nonce', 'nonce' ); // Use a different nonce for this action

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'You do not have permission.', 'ai-cv-builder' ) ), 403 );
        return;
    }

    $cv_id = isset( $_POST['cv_id'] ) ? intval( $_POST['cv_id'] ) : 0;
    $job_description_text = isset( $_POST['job_description'] ) ? sanitize_textarea_field( $_POST['job_description'] ) : '';

    if ( empty( $cv_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid CV ID.', 'ai-cv-builder' ) ), 400 );
        return;
    }
    if ( empty( $job_description_text ) ) {
        wp_send_json_error( array( 'message' => __( 'Job description cannot be empty.', 'ai-cv-builder' ) ), 400 );
        return;
    }

    // Construct the prompt for Gemini
    // IMPORTANT: Prompt engineering is key here. This is a basic example.
    $prompt = "Based on the following job information, generate a foundational resume draft:\n\n";
    $prompt .= "Job Information: \"$job_description_text\"\n\n";
    $prompt .= "Please provide the output as a JSON object with the following keys and structure:\n";
    $prompt .= "- 'professional_summary': A string containing a concise professional summary (2-3 sentences).\n";
    $prompt .= "- 'skills': An array of 5-7 relevant skill strings (e.g., ['JavaScript', 'Project Management']).\n";
    $prompt .= "- 'work_experience': An array of 1-2 work experience objects. Each object should have:\n";
    $prompt .= "  - 'job_title': A string for the job title.\n";
    $prompt .= "  - 'company': A string for the company name (make a reasonable guess if not explicitly provided in the job info, like 'Relevant Industry Company').\n";
    $prompt .= "  - 'dates': A placeholder string like 'YYYY - YYYY' or 'Month YYYY - Present'.\n";
    $prompt .= "  - 'description_points': An array of 2-3 strings, each being a bullet point describing a key responsibility or achievement relevant to the job info.\n";
    $prompt .= "Example for one work experience object: {\"job_title\": \"Example Developer\", \"company\": \"Tech Solutions Inc.\", \"dates\": \"Jan 2020 - Dec 2022\", \"description_points\": [\"Developed new features.\", \"Collaborated with teams.\"]}\n";
    $prompt .= "Ensure the entire output is a single, valid JSON object string.";


    $gemini_response = aicvb_gemini_generate_text_from_prompt( $prompt );

    if ( is_wp_error( $gemini_response ) ) {
        wp_send_json_error( array( 'message' => $gemini_response->get_error_message() ) );
        return;
    }

    // The response from aicvb_gemini_generate_text_from_prompt is expected to be the text content,
    // which we've instructed Gemini to be a JSON string.
    $generated_data = json_decode( $gemini_response, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        wp_send_json_error( array( 'message' => __( 'Failed to parse AI response as JSON. Raw response: ', 'ai-cv-builder' ) . esc_html( substr( $gemini_response, 0, 500 ) ) . '...' ) );
        return;
    }

    // Validate basic structure
    if ( !isset($generated_data['professional_summary']) || !isset($generated_data['skills']) || !isset($generated_data['work_experience']) ) {
        wp_send_json_error( array( 'message' => __( 'AI response is missing expected data sections (summary, skills, or experience).', 'ai-cv-builder' ), 'raw_response' => $gemini_response ) );
        return;
    }


    // Map AI data to the CV structure expected by aicvb_update_cv
    $mapped_cv_data = array();
    $mapped_cv_data[AICVB_META_SUMMARY] = sanitize_textarea_field( $generated_data['professional_summary'] );

    if (is_array($generated_data['skills'])) {
        $mapped_skills = array();
        foreach($generated_data['skills'] as $skill_name) {
            if(is_string($skill_name) && !empty(trim($skill_name))){
                 $mapped_skills[] = array('skill_name' => sanitize_text_field(trim($skill_name)));
            }
        }
        $mapped_cv_data[AICVB_META_SKILLS] = $mapped_skills;
    } else {
        $mapped_cv_data[AICVB_META_SKILLS] = array();
    }

    // Work Experience
    if (is_array($generated_data['work_experience'])) {
        $mapped_experience = array();
        foreach ($generated_data['work_experience'] as $exp_item) {
            $sanitized_exp_item = array();
            $sanitized_exp_item['job_title'] = isset($exp_item['job_title']) ? sanitize_text_field($exp_item['job_title']) : '';
            $sanitized_exp_item['company'] = isset($exp_item['company']) ? sanitize_text_field($exp_item['company']) : '';
            $sanitized_exp_item['dates'] = isset($exp_item['dates']) ? sanitize_text_field($exp_item['dates']) : '';
            $description_points = array();
            if(isset($exp_item['description_points']) && is_array($exp_item['description_points'])){
                foreach($exp_item['description_points'] as $point){
                    if(is_string($point)) $description_points[] = sanitize_textarea_field($point);
                }
            }
            $sanitized_exp_item['description'] = implode("\n- ", $description_points);
            if(!empty($description_points)) $sanitized_exp_item['description'] = "- " . $sanitized_exp_item['description'];


            if(!empty(array_filter($sanitized_exp_item))){
                 $mapped_experience[] = $sanitized_exp_item;
            }
        }
        $mapped_cv_data[AICVB_META_EXPERIENCE] = $mapped_experience;
    } else {
        $mapped_cv_data[AICVB_META_EXPERIENCE] = array();
    }

    // Mark that initial generation is done (optional, could be useful)
    // update_post_meta($cv_id, '_aicvb_initial_generation_done', true);

    // Update the CV with this new data
    $updated = aicvb_update_cv( $cv_id, $mapped_cv_data );

    if ( $updated ) {
        $js_populatable_data = array(
            'professional_summary' => $mapped_cv_data[AICVB_META_SUMMARY],
            'skills' => $mapped_cv_data[AICVB_META_SKILLS],
            'experience' => $mapped_cv_data[AICVB_META_EXPERIENCE]
        );
        wp_send_json_success( array( 'message' => __( 'CV draft generated!', 'ai-cv-builder' ), 'generated_data' => $js_populatable_data ) );
    } else {
        wp_send_json_error( array( 'message' => __( 'Failed to save generated CV data.', 'ai-cv-builder' ) ) );
    }
}
add_action( 'wp_ajax_aicvb_generate_initial_cv', 'aicvb_generate_initial_cv_ajax_handler' );

// Make sure all AICVB_META_* constants are loaded if this file is processed directly or early
// This is generally handled by including this file after the main plugin file defines constants.


/**
 * AJAX handler for generating content for specific fields using Gemini.
 */
function aicvb_generate_field_content_ajax_handler() {
    check_ajax_referer( 'aicvb_generate_field_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'You do not have permission.', 'ai-cv-builder' ) ), 403 );
        return;
    }
    $user_id = get_current_user_id(); // Get user ID for authorship check

    $cv_id = isset( $_POST['cv_id'] ) ? intval( $_POST['cv_id'] ) : 0;
    $field_type = isset( $_POST['field_type'] ) ? sanitize_key( $_POST['field_type'] ) : ''; // Changed to sanitize_key
    $context_data = isset( $_POST['context_data'] ) && is_array( $_POST['context_data'] ) ? array_map( 'sanitize_text_field', $_POST['context_data'] ) : array();
    $target_field_id_attr = isset( $_POST['target_field_id_attr'] ) ? sanitize_text_field( $_POST['target_field_id_attr'] ) : '';
    $current_text = isset( $_POST['current_text'] ) ? sanitize_textarea_field( $_POST['current_text'] ) : '';


    if ( empty( $cv_id ) || empty( $field_type ) || empty( $target_field_id_attr ) ) {
        wp_send_json_error( array( 'message' => __( 'Missing required parameters (CV ID, field type, or target field).', 'ai-cv-builder' ) ), 400 );
        return;
    }

    // Check post authorship
    $post = get_post( $cv_id );
    if ( ! $post || $post->post_type !== 'aicv_resume' ) {
        wp_send_json_error( array( 'message' => __( 'Invalid CV ID.', 'ai-cv-builder' ) ), 400 );
        return;
    }
    if ( $post->post_author != $user_id && ! current_user_can( 'edit_others_posts', $cv_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied to modify this CV.', 'ai-cv-builder' ) ), 403 );
        return;
    }

    $prompt = "";

    switch ( $field_type ) {
        case 'summary':
            $job_title_context = isset( $context_data['job_title'] ) && !empty($context_data['job_title']) ? " for a role like '{$context_data['job_title']}'" : "";
            $prompt = "Generate a concise and impactful professional summary for a CV{$job_title_context}. ";
            if(!empty($current_text)){
                $prompt .= "The current summary is: \"{$current_text}\". Please refine or expand upon it. ";
            }
            $prompt .= "The summary should be 2-4 sentences long.";
            break;

        case 'experience_description':
            $job_title = isset( $context_data['job_title'] ) ? $context_data['job_title'] : 'the specified role';
            $company = isset( $context_data['company'] ) ? $context_data['company'] : 'the company';
            $prompt = "Generate 3-5 resume bullet points for the role of '{$job_title}' at '{$company}'. ";
            if(!empty($current_text)){
                 $prompt .= "The current description is: \"{$current_text}\". Please generate additional distinct points, or refine these if they seem like rough notes. Each point should start with an action verb and highlight a key responsibility or achievement. ";
            } else {
                $prompt .= "Each point should start with an action verb and highlight a key responsibility or achievement. ";
            }
            $prompt .= "Return the points as a multi-line string, each point starting with '- '.";
            break;

        default:
            wp_send_json_error( array( 'message' => __( 'Invalid field type for AI generation.', 'ai-cv-builder' ) ), 400 );
            return;
    }

    if ( empty( $prompt ) ) {
        wp_send_json_error( array( 'message' => __( 'Could not construct a valid prompt for AI generation.', 'ai-cv-builder' ) ), 500 );
        return;
    }

    $gemini_response_text = aicvb_gemini_generate_text_from_prompt( $prompt );

    if ( is_wp_error( $gemini_response_text ) ) {
        wp_send_json_error( array( 'message' => $gemini_response_text->get_error_message() ) );
        return;
    }

    // For experience description, ensure it's formatted as bullet points if not already.
    // This is a simplistic check; more robust formatting might be needed.
    $final_text = $gemini_response_text;
    if ($field_type === 'experience_description') {
        $lines = explode("\n", $gemini_response_text);
        $formatted_lines = array_map(function($line) {
            $trimmed_line = trim($line);
            if (!empty($trimmed_line) && strpos($trimmed_line, '-') !== 0 && strpos($trimmed_line, '*') !== 0) {
                return '- ' . $trimmed_line;
            }
            return $trimmed_line;
        }, $lines);
        $final_text = implode("\n", array_filter($formatted_lines)); // array_filter to remove empty lines
    }


    wp_send_json_success( array(
        'target_field_id_attr' => $target_field_id_attr,
        'generated_text'       => $final_text,
        'field_type'           => $field_type
    ) );
}
add_action( 'wp_ajax_aicvb_generate_field_content', 'aicvb_generate_field_content_ajax_handler' );


/**
 * AJAX handler for tailoring the entire CV based on a job description.
 */
function aicvb_tailor_cv_ajax_handler() {
    check_ajax_referer( 'aicvb_tailor_cv_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'You do not have permission.', 'ai-cv-builder' ) ), 403 );
        return;
    }

    $cv_id = isset( $_POST['cv_id'] ) ? intval( $_POST['cv_id'] ) : 0;
    $job_description = isset( $_POST['job_description'] ) ? sanitize_textarea_field( $_POST['job_description'] ) : '';

    if ( empty( $cv_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid CV ID.', 'ai-cv-builder' ) ), 400 );
        return;
    }

    // Check post authorship before proceeding
    $user_id_for_tailor = get_current_user_id();
    $post_for_tailor = get_post( $cv_id );
    if ( ! $post_for_tailor || $post_for_tailor->post_type !== 'aicv_resume' ) {
        wp_send_json_error( array( 'message' => __( 'Invalid CV ID for tailoring.', 'ai-cv-builder' ) ), 400 );
        return;
    }
    if ( $post_for_tailor->post_author != $user_id_for_tailor && ! current_user_can( 'edit_others_posts', $cv_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied to tailor this CV.', 'ai-cv-builder' ) ), 403 );
        return;
    }

    if ( empty( $job_description ) ) {
        wp_send_json_error( array( 'message' => __( 'Job description cannot be empty for tailoring.', 'ai-cv-builder' ) ), 400 );
        return;
    }

    // Decode current CV data. Note: JS sends already structured data, but here it's double-encoded by AJAX if not careful.
    // The JS should send current_cv_data as an object, not a JSON string, to avoid double encoding.
    // For now, assuming it's correctly passed as an array by jQuery AJAX if JS side is an object.
    // If $_POST['current_cv_data'] is indeed a JSON string, then:
    // $current_cv_data = json_decode( $current_cv_data_json, true );
    // if ( json_last_error() !== JSON_ERROR_NONE ) {
    //     wp_send_json_error( array( 'message' => __( 'Invalid current CV data format.', 'ai-cv-builder' ) ), 400 );
    //     return;
    // }
    // Correction: jQuery will typically send it as form data, so it will be an array on the PHP side.
    $raw_current_cv_data = isset( $_POST['current_cv_data'] ) && is_array($_POST['current_cv_data']) ? $_POST['current_cv_data'] : array();

    // Sanitize the $raw_current_cv_data before using it in the prompt
    $cv_context_for_prompt = array();

    if (isset($raw_current_cv_data['professional_summary'])) {
        $cv_context_for_prompt['professional_summary'] = sanitize_textarea_field($raw_current_cv_data['professional_summary']);
    }

    if (isset($raw_current_cv_data['skills']) && is_array($raw_current_cv_data['skills'])) {
        $sanitized_skills_for_prompt = array();
        foreach ($raw_current_cv_data['skills'] as $skill_entry) {
            if (is_array($skill_entry) && isset($skill_entry['skill_name'])) {
                $trimmed_skill = trim(sanitize_text_field($skill_entry['skill_name']));
                if (!empty($trimmed_skill)) {
                    $sanitized_skills_for_prompt[] = $trimmed_skill;
                }
            }
        }
        if (!empty($sanitized_skills_for_prompt)) {
            $cv_context_for_prompt['skills'] = $sanitized_skills_for_prompt;
        }
    }

    if (isset($raw_current_cv_data['experience']) && is_array($raw_current_cv_data['experience'])) {
        $sanitized_experience_for_prompt = array();
        foreach ($raw_current_cv_data['experience'] as $exp_entry) {
            if (is_array($exp_entry)) {
                $temp_exp = array();
                if (isset($exp_entry['job_title'])) {
                    $temp_exp['job_title'] = sanitize_text_field($exp_entry['job_title']);
                }
                if (isset($exp_entry['company'])) {
                    $temp_exp['company'] = sanitize_text_field($exp_entry['company']);
                }
                if (isset($exp_entry['description'])) {
                    $temp_exp['description'] = sanitize_textarea_field($exp_entry['description']);
                }
                // Only add if it has at least a job title or description, to make sense for the prompt
                if (!empty($temp_exp['job_title']) || !empty($temp_exp['description'])) {
                    $sanitized_experience_for_prompt[] = $temp_exp;
                }
            }
        }
        if(!empty($sanitized_experience_for_prompt)){
            $cv_context_for_prompt['work_experience'] = $sanitized_experience_for_prompt;
        }
    }

    $cv_context_json_string = wp_json_encode($cv_context_for_prompt);


    // Prompt Engineering for CV Tailoring
    $prompt = "You are an expert resume writer. Review the following current CV data and a target job description. ";
    $prompt .= "Your goal is to suggest improvements to the CV to better align it with the job description. ";
    $prompt .= "Focus on tailoring the professional summary, suggesting relevant skills (both existing and new), and refining work experience descriptions to highlight relevant achievements and keywords from the job description.\n\n";
    $prompt .= "Current CV Data (JSON):\n" . $cv_context_json_string . "\n\n";
    $prompt .= "Target Job Description:\n" . $job_description . "\n\n";
    $prompt .= "Provide your suggestions as a JSON object with these exact keys: 'suggested_professional_summary' (string), 'suggested_skills' (array of strings - these should be skills directly applicable or keywords from the JD), and 'suggested_work_experience' (array of objects). ";
    $prompt .= "For each object in 'suggested_work_experience', include 'original_job_title' (use the job_title from the input CV data for matching), and 'revised_description' (a string with bullet points, ideally starting with action verbs, tailored to the job description). ";
    $prompt .= "If a work experience from the CV is not relevant, you can omit it or suggest making it more concise. If new skills are suggested, list them. Ensure the entire output is a single, valid JSON object string.";

    $gemini_response_text = aicvb_gemini_generate_text_from_prompt( $prompt );

    if ( is_wp_error( $gemini_response_text ) ) {
        wp_send_json_error( array( 'message' => "Gemini API Error: " . $gemini_response_text->get_error_message() ) );
        return;
    }

    $parsed_suggestions = json_decode( $gemini_response_text, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        wp_send_json_error( array( 'message' => __( 'Failed to parse AI tailoring suggestions as JSON. Raw response: ', 'ai-cv-builder' ) . esc_html( substr( $gemini_response_text, 0, 500 ) ) . '...' ) );
        return;
    }

    // Basic validation of the structure of suggestions
    if ( !isset($parsed_suggestions['suggested_professional_summary']) || !isset($parsed_suggestions['suggested_skills']) || !isset($parsed_suggestions['suggested_work_experience']) ) {
        wp_send_json_error( array( 'message' => __( 'AI response is missing expected suggestion sections (summary, skills, or experience).', 'ai-cv-builder' ), 'raw_response_partial' => substr( $gemini_response_text, 0, 1000 ) ) );
        return;
    }

    // No database update here; just returning suggestions to the frontend.
    wp_send_json_success( array( 'message' => 'Suggestions generated!', 'tailored_suggestions' => $parsed_suggestions ) );
}
add_action( 'wp_ajax_aicvb_tailor_cv', 'aicvb_tailor_cv_ajax_handler' );
?>
