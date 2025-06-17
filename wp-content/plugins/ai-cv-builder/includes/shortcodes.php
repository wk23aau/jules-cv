<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AI CV Builder Shortcodes
 *
 * @package AI_CV_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles the [ai_cv_builder] shortcode.
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content (not used for this shortcode).
 * @return string HTML output for the shortcode.
 */
function aicvb_render_cv_builder_shortcode( $atts, $content = null ) {
    // Sanitize and extract attributes (though none are used yet)
    $atts = shortcode_atts(
        array(
            // 'example_attribute' => 'default_value',
        ),
        $atts,
        'ai_cv_builder'
    );

    $output = '<div id="aicv-user-notifications" aria-live="assertive" role="alert"></div>';

    // Define placeholder templates
    $templates = array(
        array(
            'id'                => 'classic',
            'name'              => __( 'Classic Professional', 'ai-cv-builder' ),
            'preview_image_url' => '', // Placeholder for image path or CSS class
            'description'       => __( 'A timeless, traditional layout suitable for corporate roles.', 'ai-cv-builder' ),
        ),
        array(
            'id'                => 'modern',
            'name'              => __( 'Modern Minimalist', 'ai-cv-builder' ),
            'preview_image_url' => '',
            'description'       => __( 'Clean lines and a sleek design for contemporary fields.', 'ai-cv-builder' ),
        ),
        array(
            'id'                => 'creative',
            'name'              => __( 'Creative Spark', 'ai-cv-builder' ),
            'preview_image_url' => '',
            'description'       => __( 'A vibrant and unique design for artistic professions.', 'ai-cv-builder' ),
        ),
    );

    $output .= '<div id="aicv-cv-builder-wrapper">'; // Overall wrapper

    // --- Template Selection UI ---
    $output .= '<div id="aicv-template-selection-ui">'; // Wrapper for template selection
    $output .= '<h2>' . esc_html__( 'Choose Your CV Template', 'ai-cv-builder' ) . '</h2>';
    $output .= '<div class="aicv-templates-grid">';

    foreach ( $templates as $template ) {
        $output .= '<div class="aicv-template-item" data-template-id="' . esc_attr( $template['id'] ) . '">';
        $output .= '<div class="aicv-template-preview aicv-template-preview-' . esc_attr( $template['id'] ) . '">';
        $output .= '<span>' . esc_html( $template['name'] ) . ' Preview</span>'; // name is from code, already safe or escaped if dynamic
        $output .= '</div>'; // .aicv-template-preview
        $output .= '<h3>' . esc_html( $template['name'] ) . '</h3>'; // name is from code
        $output .= '<p>' . esc_html( $template['description'] ) . '</p>'; // description is from code
        $output .= '<button class="aicv-select-template-button" data-template-id="' . esc_attr( $template['id'] ) . '">' . esc_html__( 'Select', 'ai-cv-builder' ) . '</button>';
        $output .= '</div>'; // .aicv-template-item
    }
    $output .= '</div>'; // .aicv-templates-grid
    $output .= '</div>'; // #aicv-template-selection-ui

    // --- CV Builder Main UI (Initially Hidden) ---
    $output .= '<div id="aicv-builder-main-ui" style="display: none;">';

    // Download PDF Button - Placed above the two panes for now
    $output .= '<div style="text-align: right; margin-bottom: 15px; padding-right: 20px;">'; // Basic wrapper for positioning
    $output .= '<button type="button" id="aicv_download_pdf_button" class="button button-secondary">' . esc_html__( 'Download CV as PDF', 'ai-cv-builder' ) . '</button>';
    $output .= '<span id="aicv-pdf-generating-spinner" class="spinner" style="display:none; vertical-align: middle; margin-left: 5px;"></span>';
    $output .= '</div>';

    // Left Pane (Control Panel)
    $output .= '<div id="aicv-control-panel">';
    $output .= '<div class="aicv-tabs">';
    $output .= '<button class="aicv-tab-button active" data-tab="content">' . esc_html__( 'Content', 'ai-cv-builder' ) . '</button>';
    $output .= '<button class="aicv-tab-button" data-tab="theme">' . esc_html__( 'Theme', 'ai-cv-builder' ) . '</button>';
    $output .= '</div>'; // .aicv-tabs

    // Content Tab Pane
    $output .= '<div id="aicv-tab-content" class="aicv-tab-pane active">';
    $output .= '<input type="hidden" id="aicv_cv_id" name="aicv_cv_id" value="">'; // CV ID holder

    // Personal Information
    $output .= '<div class="aicv-control-section" id="aicv-personal-info-controls">';
    $output .= '<h3>' . esc_html__( 'Personal Information', 'ai-cv-builder' ) . '</h3>';
    $output .= '<label for="aicv_full_name">' . esc_html__( 'Full Name:', 'ai-cv-builder' ) . '</label><input type="text" id="aicv_full_name" name="aicv_personal_info[full_name]">';
    $output .= '<label for="aicv_email">' . esc_html__( 'Email:', 'ai-cv-builder' ) . '</label><input type="email" id="aicv_email" name="aicv_personal_info[email]">';
    $output .= '<label for="aicv_phone">' . esc_html__( 'Phone:', 'ai-cv-builder' ) . '</label><input type="tel" id="aicv_phone" name="aicv_personal_info[phone]">';
    $output .= '<label for="aicv_address">' . esc_html__( 'Address:', 'ai-cv-builder' ) . '</label><textarea id="aicv_address" name="aicv_personal_info[address]"></textarea>';
    $output .= '<label for="aicv_website">' . esc_html__( 'Website/LinkedIn:', 'ai-cv-builder' ) . '</label><input type="url" id="aicv_website" name="aicv_personal_info[website]">';
    $output .= '</div>';

    // Professional Summary
    $output .= '<div class="aicv-control-section" id="aicv-summary-controls">';
    $output .= '<h3>' . esc_html__( 'Professional Summary', 'ai-cv-builder' ) . '</h3>';
    $output .= '<textarea id="aicv_summary" name="aicv_professional_summary" rows="5"></textarea>';
    $output .= '<button type="button" class="aicv-generate-field-button" data-target-field="aicv_summary" data-field-type="summary">' . esc_html__( 'AI Assist', 'ai-cv-builder') . '</button>';
    $output .= '<span class="spinner aicv-field-spinner"></span>';
    $output .= '</div>';

    // Work Experience
    $output .= '<div class="aicv-control-section" id="aicv-experience-controls">';
    $output .= '<h3>' . esc_html__( 'Work Experience', 'ai-cv-builder' ) . '</h3>';
    $output .= '<div id="aicv-experience-entries">';
    // Entry structure (JS will clone this or use a template)
    $output .= '<div class="aicv-experience-entry aicv-repeatable-entry">';
    $output .= '<input type="text" class="aicv-exp-job-title" name="aicv_experience[0][job_title]" placeholder="' . esc_attr__('Job Title', 'ai-cv-builder') . '">';
    $output .= '<input type="text" class="aicv-exp-company" name="aicv_experience[0][company]" placeholder="' . esc_attr__('Company', 'ai-cv-builder') . '">';
    $output .= '<input type="text" class="aicv-exp-dates" name="aicv_experience[0][dates]" placeholder="' . esc_attr__('Dates (e.g., Jan 2020 - Present)', 'ai-cv-builder') . '">';
    $output .= '<textarea class="aicv-exp-description" name="aicv_experience[0][description]" placeholder="' . esc_attr__('Description, responsibilities, achievements...', 'ai-cv-builder') . '" rows="3"></textarea>';
    $output .= '<button type="button" class="aicv-generate-field-button" data-field-type="experience_description">' . esc_html__( 'AI Assist Description', 'ai-cv-builder') . '</button>';
    $output .= '<span class="spinner aicv-field-spinner"></span>';
    $output .= '<button type="button" class="aicv-delete-entry">' . esc_html__('Delete', 'ai-cv-builder') . '</button>';
    $output .= '</div>';
    $output .= '</div>'; // #aicv-experience-entries
    $output .= '<button type="button" id="aicv-add-experience" class="aicv-add-entry-button">' . esc_html__( 'Add Experience', 'ai-cv-builder' ) . '</button>';
    $output .= '</div>';

    // Education
    $output .= '<div class="aicv-control-section" id="aicv-education-controls">';
    $output .= '<h3>' . esc_html__( 'Education', 'ai-cv-builder' ) . '</h3>';
    $output .= '<div id="aicv-education-entries">';
    $output .= '<div class="aicv-education-entry aicv-repeatable-entry">';
    $output .= '<input type="text" name="aicv_education[0][degree]" placeholder="' . esc_attr__('Degree/Certificate', 'ai-cv-builder') . '">';
    $output .= '<input type="text" name="aicv_education[0][institution]" placeholder="' . esc_attr__('Institution', 'ai-cv-builder') . '">';
    $output .= '<input type="text" name="aicv_education[0][dates]" placeholder="' . esc_attr__('Dates (e.g., Aug 2016 - May 2020)', 'ai-cv-builder') . '">';
    $output .= '<textarea name="aicv_education[0][description]" placeholder="' . esc_attr__('Description, honors, relevant coursework...', 'ai-cv-builder') . '" rows="2"></textarea>';
    $output .= '<button type="button" class="aicv-delete-entry">' . esc_html__('Delete', 'ai-cv-builder') . '</button>';
    $output .= '</div>';
    $output .= '</div>'; // #aicv-education-entries
    $output .= '<button type="button" id="aicv-add-education" class="aicv-add-entry-button">' . esc_html__( 'Add Education', 'ai-cv-builder' ) . '</button>';
    $output .= '</div>';

    // Skills
    $output .= '<div class="aicv-control-section" id="aicv-skills-controls">';
    $output .= '<h3>' . esc_html__( 'Skills', 'ai-cv-builder' ) . '</h3>';
    $output .= '<div id="aicv-skills-entries">';
    $output .= '<div class="aicv-skill-entry aicv-repeatable-entry">';
    $output .= '<input type="text" name="aicv_skills[0][skill_name]" placeholder="' . esc_attr__('Skill (e.g., JavaScript)', 'ai-cv-builder') . '">';
    // $output .= '<input type="range" name="aicv_skills[0][proficiency]" min="1" max="5" value="3">'; // Optional proficiency
    $output .= '<button type="button" class="aicv-delete-entry">' . esc_html__('Delete', 'ai-cv-builder') . '</button>';
    $output .= '</div>';
    $output .= '</div>'; // #aicv-skills-entries
    $output .= '<button type="button" id="aicv-add-skill" class="aicv-add-entry-button">' . esc_html__( 'Add Skill', 'ai-cv-builder' ) . '</button>';
    $output .= '</div>';

    $output .= '<button type="button" id="aicv-manual-save" class="button button-primary button-large">' . __( 'Save CV', 'ai-cv-builder' ) . '</button>';
    $output .= '<span id="aicv-save-spinner" class="spinner"></span>';
    $output .= '<div id="aicv-save-status" style="display: none; margin-top: 10px;"></div>';

    // --- CV Tailoring Section ---
    $output .= '<div class="aicv-control-section" id="aicv-tailoring-controls">';
    $output .= '<h3>' . esc_html__( 'Tailor CV to Job Description', 'ai-cv-builder' ) . '</h3>';
    $output .= '<p>' . esc_html__( 'Paste a job description below. AI will analyze your current CV content and suggest improvements to align it with the job requirements.', 'ai-cv-builder' ) . '</p>';
    $output .= '<textarea id="aicv_job_description_for_tailoring" rows="8" placeholder="' . esc_attr__( 'Paste the full job description here...', 'ai-cv-builder' ) . '"></textarea>';
    $output .= '<button type="button" id="aicv_trigger_tailor_cv_button" class="button">' . esc_html__( 'Tailor CV with AI', 'ai-cv-builder' ) . '</button>';
    $output .= '<div class="aicv-loading-indicator" id="aicv_tailoring_spinner" style="display: none;">';
    $output .= '<span class="spinner is-active" style="float:none; width:auto; height:auto; vertical-align: middle; margin-right: 10px;"></span>';
    $output .= '<span>' . __( 'Tailoring your CV...', 'ai-cv-builder' ) . '</span>';
    $output .= '</div>'; // .aicv-loading-indicator
    $output .= '</div>'; // #aicv-tailoring-controls

    $output .= '</div>'; // #aicv-tab-content

    // Theme Tab Pane
    $output .= '<div id="aicv-tab-theme" class="aicv-tab-pane" style="display: none;">';

    // Predefined Themes / Styles
    $output .= '<div class="aicv-control-section" id="aicv-select-theme-controls">';
    $output .= '<h3>' . esc_html__( 'Appearance Theme', 'ai-cv-builder' ) . '</h3>';
    $output .= '<label for="aicv_theme_select">' . esc_html__( 'Select Theme Style:', 'ai-cv-builder' ) . '</label>';
    $output .= '<select id="aicv_theme_select" name="aicv_selected_theme_class">'; // Name matches meta key for direct saving via flat structure
    $output .= '<option value="theme-default">' . esc_html__( 'Default', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="theme-classic">' . esc_html__( 'Classic', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="theme-modern">' . esc_html__( 'Modern', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="theme-creative">' . esc_html__( 'Creative', 'ai-cv-builder' ) . '</option>';
    $output .= '</select>';
    $output .= '<p class="description">' . esc_html__( 'Changes the overall look and feel (borders, backgrounds, etc.). This is different from the initial content template choice.', 'ai-cv-builder') . '</p>';
    $output .= '</div>';

    // Primary Color
    $output .= '<div class="aicv-control-section" id="aicv-primary-color-controls">';
    $output .= '<h3>' . esc_html__( 'Primary Color', 'ai-cv-builder' ) . '</h3>';
    $output .= '<label for="aicv_primary_color">' . esc_html__( 'Select Primary Color:', 'ai-cv-builder' ) . '</label>';
    $output .= '<input type="color" id="aicv_primary_color" name="aicv_primary_color" value="#337ab7">'; // Default color, name matches meta key
    $output .= '<p class="description">' . esc_html__( 'Typically used for headings, accents, and section titles.', 'ai-cv-builder') . '</p>';
    $output .= '</div>';

    // Font Family
    $output .= '<div class="aicv-control-section" id="aicv-font-family-controls">';
    $output .= '<h3>' . esc_html__( 'Font Family', 'ai-cv-builder' ) . '</h3>';
    $output .= '<label for="aicv_font_family">' . esc_html__( 'Select Font Family:', 'ai-cv-builder' ) . '</label>';
    $output .= '<select id="aicv_font_family" name="aicv_font_family">'; // Name matches meta key
    $output .= '<option value="Arial, Helvetica, sans-serif">' . esc_html__( 'Arial (sans-serif)', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="Georgia, serif">' . esc_html__( 'Georgia (serif)', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="Times New Roman, Times, serif">' . esc_html__( 'Times New Roman (serif)', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="Verdana, Geneva, sans-serif">' . esc_html__( 'Verdana (sans-serif)', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="\'Courier New\', Courier, monospace">' . esc_html__( 'Courier New (monospace)', 'ai-cv-builder' ) . '</option>';
    $output .= '</select>';
    $output .= '<p class="description">' . esc_html__( 'Applies to the main body text. Headings may use variants.', 'ai-cv-builder') . '</p>';
    $output .= '</div>';

    $output .= '</div>'; // #aicv-tab-theme
    $output .= '</div>'; // #aicv-control-panel

    // Right Pane (Live Preview) - Update placeholders for better JS targeting
    $output .= '<div id="aicv-live-preview">';
    $output .= '<div class="aicv-resume-sheet">'; // Theme and Template classes will be added by JS

    // Personal Information Preview
    $output .= '<div class="preview-section" id="preview-personal-info">';
    $output .= '<h2 class="preview-name" id="preview_full_name">[Full Name]</h2>'; // Changed h4 to h2 for name
    $output .= '<p class="preview-contact-info">';
    $output .= '<span class="preview-email" id="preview_email">[Email]</span>';
    $output .= ' | <span class="preview-phone" id="preview_phone">[Phone]</span>';
    $output .= ' | <span class="preview-website" id="preview_website">[Website/LinkedIn]</span>';
    $output .= '</p>';
    $output .= '<p class="preview-address" id="preview_address">[Address]</p>';
    $output .= '</div>'; // #preview-personal-info

    // Professional Summary Preview
    $output .= '<div class="preview-section" id="preview-summary">';
    $output .= '<h3 class="preview-section-title">' . __( 'Summary', 'ai-cv-builder' ) . '</h3>'; // Changed h4 to h3
    $output .= '<p class="preview-content" id="preview_summary_content">' . __( 'Your professional summary here...', 'ai-cv-builder' ) . '</p>';
    $output .= '</div>'; // #preview-summary

    // Work Experience Preview
    $output .= '<div class="preview-section" id="preview-experience">';
    $output .= '<h3 class="preview-section-title">' . __( 'Experience', 'ai-cv-builder' ) . '</h3>';
    $output .= '<div class="preview-entries-list" id="preview-experience-entries">';
    // Placeholder for a single experience item (JS will replicate this structure)
    // Actual items will be added by JS. This is just a comment for structure.
    // <div class="preview-experience-item">
    //     <h4 class="preview-job-title">[Job Title]</h4>
    //     <p class="preview-company-dates"><span class="preview-company">[Company]</span> | <span class="preview-dates">[Dates]</span></p>
    //     <div class="preview-description">[Description]</div>
    // </div>
    $output .= '</div>'; // #preview-experience-entries
    $output .= '</div>'; // #preview-experience

    // Education Preview
    $output .= '<div class="preview-section" id="preview-education">';
    $output .= '<h3 class="preview-section-title">' . __( 'Education', 'ai-cv-builder' ) . '</h3>';
    $output .= '<div class="preview-entries-list" id="preview-education-entries">';
    // Placeholder for a single education item
    // <div class="preview-education-item">
    //     <h4 class="preview-degree">[Degree]</h4>
    //     <p class="preview-institution-dates"><span class="preview-institution">[Institution]</span> | <span class="preview-dates">[Dates]</span></p>
    //     <div class="preview-description">[Description]</div>
    // </div>
    $output .= '</div>'; // #preview-education-entries
    $output .= '</div>'; // #preview-education

    // Skills Preview
    $output .= '<div class="preview-section" id="preview-skills">';
    $output .= '<h3 class="preview-section-title">' . __( 'Skills', 'ai-cv-builder' ) . '</h3>';
    $output .= '<ul class="preview-skills-list" id="preview-skills-entries">'; // Changed to UL for skills
    // Placeholder for skills
    // <li class="preview-skill-item">[Skill Name]</li>
    $output .= '</ul>'; // #preview-skills-entries
    $output .= '</div>'; // #preview-skills

    $output .= '</div>'; // .aicv-resume-sheet
    $output .= '</div>'; // #aicv-live-preview

    $output .= '</div>'; // #aicv-builder-main-ui

    // --- Initial Input Modal (Hidden by default) ---
    $output .= '<div id="aicv-initial-input-modal" class="aicv-modal aicv-common-modal" style="display: none;">'; // Added common class
    $output .= '<div class="aicv-modal-content">';
    $output .= '<h3>' . __( 'Start Your CV with AI', 'ai-cv-builder' ) . '</h3>';
    $output .= '<p>' . __( 'Provide a job title, a brief description of your desired role, or even a full job posting to get an AI-generated head start on your CV.', 'ai-cv-builder' ) . '</p>';
    $output .= '<textarea id="aicv_job_description_input" rows="6" placeholder="' . __( 'E.g., \'Senior Software Engineer specializing in WordPress plugin development\' or paste a full job description...', 'ai-cv-builder' ) . '"></textarea>';
    $output .= '<div class="aicv-modal-actions">';
    $output .= '<button id="aicv_generate_initial_cv_button" class="button button-primary">' . __( 'Generate with AI', 'ai-cv-builder' ) . '</button>';
    $output .= '<button id="aicv_skip_initial_cv_button" class="button">' . __( 'Start Blank', 'ai-cv-builder' ) . '</button>';
    $output .= '</div>'; // .aicv-modal-actions
    $output .= '<div class="aicv-loading-indicator" style="display: none;">';
    $output .= '<span class="spinner is-active" style="float:none; width:auto; height:auto; vertical-align: middle; margin-right: 10px;"></span>';
    $output .= '<span>' . __( 'Generating your CV draft...', 'ai-cv-builder' ) . '</span>';
    $output .= '</div>'; // .aicv-loading-indicator
    $output .= '</div>'; // .aicv-modal-content
    $output .= '</div>'; // #aicv-initial-input-modal

    // --- Tailoring Suggestions Modal (Hidden by default) ---
    $output .= '<div id="aicv-tailoring-suggestions-modal" class="aicv-modal aicv-common-modal" style="display: none;">';
    $output .= '<div class="aicv-modal-content aicv-modal-content-large">'; // Larger modal for suggestions
    $output .= '<h3>' . __( 'AI Tailoring Suggestions', 'ai-cv-builder' ) . '</h3>';
    $output .= '<p>' . __( 'Review the suggestions below. Apply them to update your CV content.', 'ai-cv-builder' ) . '</p>';
    $output .= '<div id="aicv-suggestions-container" style="text-align:left; max-height: 60vh; overflow-y: auto; padding:10px; border:1px solid #eee; margin-bottom:15px;">';
    // Suggestions will be populated here by JS
    // Example structure for one section:
    // $output .= '<h4>Professional Summary</h4>';
    // $output .= '<div class="suggestion-group"><strong>Original:</strong><p class="original-summary-text"></p></div>';
    // $output .= '<div class="suggestion-group"><strong>Suggested:</strong><p class="suggested-summary-text"></p></div>';
    $output .= '</div>'; // #aicv-suggestions-container
    $output .= '<div class="aicv-modal-actions">';
    $output .= '<button id="aicv_apply_suggestions_button" class="button button-primary">' . __( 'Apply Suggestions', 'ai-cv-builder' ) . '</button>';
    $output .= '<button id="aicv_cancel_suggestions_button" class="button">' . __( 'Cancel', 'ai-cv-builder' ) . '</button>';
    $output .= '</div>'; // .aicv-modal-actions
    $output .= '</div>'; // .aicv-modal-content
    $output .= '</div>'; // #aicv-tailoring-suggestions-modal


    $output .= '</div>'; // #aicv-cv-builder-wrapper


    // This flag can be used to conditionally enqueue scripts/styles if checked early enough in wp_enqueue_scripts
    // For a more robust solution, has_shortcode() in the enqueue function is better.
    // define('AICVB_SHORTCODE_LOADED', true);

    // Enqueue scripts and styles directly within the shortcode function
    // This ensures they are only loaded when the shortcode is actually used.

    // Enqueue html2pdf.js library first as frontend-script depends on it.
    wp_enqueue_script(
        'aicv-html2pdf',
        AI_CV_BUILDER_PLUGIN_URL . 'assets/js/lib/html2pdf.bundle.min.js',
        array(),
        null, // Or a specific version number for the library if known
        true  // Load in footer
    );

    // Enqueue main frontend script
    wp_enqueue_script(
        'aicv-frontend-script',
        AI_CV_BUILDER_PLUGIN_URL . 'assets/js/frontend-script.js',
        array( 'jquery', 'aicv-html2pdf' ), // Dependencies
        AI_CV_BUILDER_VERSION,
        true  // Load in footer
    );

    // Localize script with AJAX URL and nonces - must be done AFTER the script is enqueued.
    wp_localize_script(
        'aicv-frontend-script',
        'aicvb_ajax_vars',
        array(
            'ajax_url'             => admin_url( 'admin-ajax.php' ),
            'save_cv_nonce'        => wp_create_nonce( 'aicvb_save_cv_nonce' ),
            'generate_cv_nonce'    => wp_create_nonce( 'aicvb_generate_cv_nonce' ),
            'generate_field_nonce' => wp_create_nonce( 'aicvb_generate_field_nonce' ),
            'tailor_cv_nonce'      => wp_create_nonce( 'aicvb_tailor_cv_nonce' ),
            'error_messages'       => array(
                'general_save'  => __( 'An error occurred while saving. Please try again.', 'ai-cv-builder' ),
                'fill_required' => __( 'Please fill all required fields.', 'ai-cv-builder' ),
            ),
        )
    );

    // Enqueue frontend styles
    wp_enqueue_style(
        'aicvb-frontend-styles',
        AI_CV_BUILDER_PLUGIN_URL . 'assets/css/frontend-styles.css',
        array(),
        AI_CV_BUILDER_VERSION
    );

    return $output;
}

/**
 * Register all shortcodes for the AI CV Builder plugin.
 */
function aicvb_register_shortcodes() {
    add_shortcode( 'ai_cv_builder', 'aicvb_render_cv_builder_shortcode' );
}
add_action( 'init', 'aicvb_register_shortcodes' );

// The old aicvb_enqueue_frontend_scripts_styles function and its action hook are now removed/commented out.
// add_action( 'wp_enqueue_scripts', 'aicvb_enqueue_frontend_scripts_styles' );
?>
