<?php
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

    $output = '<div id="aicv-cv-builder-wrapper">'; // Overall wrapper

    // --- Template Selection UI ---
    $output .= '<div id="aicv-template-selection-ui">'; // Wrapper for template selection
    $output .= '<h2>' . esc_html__( 'Choose Your CV Template', 'ai-cv-builder' ) . '</h2>';
    $output .= '<div class="aicv-templates-grid">';

    foreach ( $templates as $template ) {
        $output .= '<div class="aicv-template-item" data-template-id="' . esc_attr( $template['id'] ) . '">';
        $output .= '<div class="aicv-template-preview aicv-template-preview-' . esc_attr( $template['id'] ) . '">';
        $output .= '<span>' . esc_html( $template['name'] ) . ' Preview</span>';
        $output .= '</div>'; // .aicv-template-preview
        $output .= '<h3>' . esc_html( $template['name'] ) . '</h3>';
        $output .= '<p>' . esc_html( $template['description'] ) . '</p>';
        $output .= '<button class="aicv-select-template-button" data-template-id="' . esc_attr( $template['id'] ) . '">' . esc_html__( 'Select', 'ai-cv-builder' ) . '</button>';
        $output .= '</div>'; // .aicv-template-item
    }
    $output .= '</div>'; // .aicv-templates-grid
    $output .= '</div>'; // #aicv-template-selection-ui

    // --- CV Builder Main UI (Initially Hidden) ---
    $output .= '<div id="aicv-builder-main-ui" style="display: none;">';

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
    $output .= '<h3>' . __( 'Personal Information', 'ai-cv-builder' ) . '</h3>';
    $output .= '<label for="aicv_full_name">' . __( 'Full Name:', 'ai-cv-builder' ) . '</label><input type="text" id="aicv_full_name" name="aicv_personal_info[full_name]">';
    $output .= '<label for="aicv_email">' . __( 'Email:', 'ai-cv-builder' ) . '</label><input type="email" id="aicv_email" name="aicv_personal_info[email]">';
    $output .= '<label for="aicv_phone">' . __( 'Phone:', 'ai-cv-builder' ) . '</label><input type="tel" id="aicv_phone" name="aicv_personal_info[phone]">';
    $output .= '<label for="aicv_address">' . __( 'Address:', 'ai-cv-builder' ) . '</label><textarea id="aicv_address" name="aicv_personal_info[address]"></textarea>';
    $output .= '<label for="aicv_website">' . __( 'Website/LinkedIn:', 'ai-cv-builder' ) . '</label><input type="url" id="aicv_website" name="aicv_personal_info[website]">';
    $output .= '</div>';

    // Professional Summary
    $output .= '<div class="aicv-control-section" id="aicv-summary-controls">';
    $output .= '<h3>' . __( 'Professional Summary', 'ai-cv-builder' ) . '</h3>';
    $output .= '<textarea id="aicv_summary" name="aicv_professional_summary" rows="5"></textarea>';
    $output .= '</div>';

    // Work Experience
    $output .= '<div class="aicv-control-section" id="aicv-experience-controls">';
    $output .= '<h3>' . __( 'Work Experience', 'ai-cv-builder' ) . '</h3>';
    $output .= '<div id="aicv-experience-entries">';
    // Entry structure (JS will clone this or use a template)
    $output .= '<div class="aicv-experience-entry aicv-repeatable-entry">';
    $output .= '<input type="text" name="aicv_experience[0][job_title]" placeholder="' . __('Job Title', 'ai-cv-builder') . '">';
    $output .= '<input type="text" name="aicv_experience[0][company]" placeholder="' . __('Company', 'ai-cv-builder') . '">';
    $output .= '<input type="text" name="aicv_experience[0][dates]" placeholder="' . __('Dates (e.g., Jan 2020 - Present)', 'ai-cv-builder') . '">';
    $output .= '<textarea name="aicv_experience[0][description]" placeholder="' . __('Description, responsibilities, achievements...', 'ai-cv-builder') . '" rows="3"></textarea>';
    $output .= '<button type="button" class="aicv-delete-entry">' . __('Delete', 'ai-cv-builder') . '</button>';
    $output .= '</div>';
    $output .= '</div>'; // #aicv-experience-entries
    $output .= '<button type="button" id="aicv-add-experience" class="aicv-add-entry-button">' . __( 'Add Experience', 'ai-cv-builder' ) . '</button>';
    $output .= '</div>';

    // Education
    $output .= '<div class="aicv-control-section" id="aicv-education-controls">';
    $output .= '<h3>' . __( 'Education', 'ai-cv-builder' ) . '</h3>';
    $output .= '<div id="aicv-education-entries">';
    $output .= '<div class="aicv-education-entry aicv-repeatable-entry">';
    $output .= '<input type="text" name="aicv_education[0][degree]" placeholder="' . __('Degree/Certificate', 'ai-cv-builder') . '">';
    $output .= '<input type="text" name="aicv_education[0][institution]" placeholder="' . __('Institution', 'ai-cv-builder') . '">';
    $output .= '<input type="text" name="aicv_education[0][dates]" placeholder="' . __('Dates (e.g., Aug 2016 - May 2020)', 'ai-cv-builder') . '">';
    $output .= '<textarea name="aicv_education[0][description]" placeholder="' . __('Description, honors, relevant coursework...', 'ai-cv-builder') . '" rows="2"></textarea>';
    $output .= '<button type="button" class="aicv-delete-entry">' . __('Delete', 'ai-cv-builder') . '</button>';
    $output .= '</div>';
    $output .= '</div>'; // #aicv-education-entries
    $output .= '<button type="button" id="aicv-add-education" class="aicv-add-entry-button">' . __( 'Add Education', 'ai-cv-builder' ) . '</button>';
    $output .= '</div>';

    // Skills
    $output .= '<div class="aicv-control-section" id="aicv-skills-controls">';
    $output .= '<h3>' . __( 'Skills', 'ai-cv-builder' ) . '</h3>';
    $output .= '<div id="aicv-skills-entries">';
    $output .= '<div class="aicv-skill-entry aicv-repeatable-entry">';
    $output .= '<input type="text" name="aicv_skills[0][skill_name]" placeholder="' . __('Skill (e.g., JavaScript)', 'ai-cv-builder') . '">';
    // $output .= '<input type="range" name="aicv_skills[0][proficiency]" min="1" max="5" value="3">'; // Optional proficiency
    $output .= '<button type="button" class="aicv-delete-entry">' . __('Delete', 'ai-cv-builder') . '</button>';
    $output .= '</div>';
    $output .= '</div>'; // #aicv-skills-entries
    $output .= '<button type="button" id="aicv-add-skill" class="aicv-add-entry-button">' . __( 'Add Skill', 'ai-cv-builder' ) . '</button>';
    $output .= '</div>';

    $output .= '<button type="button" id="aicv-manual-save" class="button button-primary button-large">' . __( 'Save CV', 'ai-cv-builder' ) . '</button>';
    $output .= '<span id="aicv-save-spinner" class="spinner"></span>';
    $output .= '<div id="aicv-save-status" style="display: none; margin-top: 10px;"></div>';

    $output .= '</div>'; // #aicv-tab-content

    // Theme Tab Pane
    $output .= '<div id="aicv-tab-theme" class="aicv-tab-pane" style="display: none;">';

    // Predefined Themes / Styles
    $output .= '<div class="aicv-control-section" id="aicv-select-theme-controls">';
    $output .= '<h3>' . __( 'Appearance Theme', 'ai-cv-builder' ) . '</h3>';
    $output .= '<label for="aicv_theme_select">' . __( 'Select Theme Style:', 'ai-cv-builder' ) . '</label>';
    $output .= '<select id="aicv_theme_select" name="aicv_selected_theme_class">'; // Name matches meta key for direct saving via flat structure
    $output .= '<option value="theme-default">' . __( 'Default', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="theme-classic">' . __( 'Classic', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="theme-modern">' . __( 'Modern', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="theme-creative">' . __( 'Creative', 'ai-cv-builder' ) . '</option>';
    $output .= '</select>';
    $output .= '<p class="description">' . __( 'Changes the overall look and feel (borders, backgrounds, etc.). This is different from the initial content template choice.', 'ai-cv-builder') . '</p>';
    $output .= '</div>';

    // Primary Color
    $output .= '<div class="aicv-control-section" id="aicv-primary-color-controls">';
    $output .= '<h3>' . __( 'Primary Color', 'ai-cv-builder' ) . '</h3>';
    $output .= '<label for="aicv_primary_color">' . __( 'Select Primary Color:', 'ai-cv-builder' ) . '</label>';
    $output .= '<input type="color" id="aicv_primary_color" name="aicv_primary_color" value="#337ab7">'; // Default color, name matches meta key
    $output .= '<p class="description">' . __( 'Typically used for headings, accents, and section titles.', 'ai-cv-builder') . '</p>';
    $output .= '</div>';

    // Font Family
    $output .= '<div class="aicv-control-section" id="aicv-font-family-controls">';
    $output .= '<h3>' . __( 'Font Family', 'ai-cv-builder' ) . '</h3>';
    $output .= '<label for="aicv_font_family">' . __( 'Select Font Family:', 'ai-cv-builder' ) . '</label>';
    $output .= '<select id="aicv_font_family" name="aicv_font_family">'; // Name matches meta key
    $output .= '<option value="Arial, Helvetica, sans-serif">' . __( 'Arial (sans-serif)', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="Georgia, serif">' . __( 'Georgia (serif)', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="Times New Roman, Times, serif">' . __( 'Times New Roman (serif)', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="Verdana, Geneva, sans-serif">' . __( 'Verdana (sans-serif)', 'ai-cv-builder' ) . '</option>';
    $output .= '<option value="\'Courier New\', Courier, monospace">' . __( 'Courier New (monospace)', 'ai-cv-builder' ) . '</option>';
    $output .= '</select>';
    $output .= '<p class="description">' . __( 'Applies to the main body text. Headings may use variants.', 'ai-cv-builder') . '</p>';
    $output .= '</div>';

    $output .= '</div>'; // #aicv-tab-theme
    $output .= '</div>'; // #aicv-control-panel

    // Right Pane (Live Preview) - Update placeholders for better JS targeting
    $output .= '<div id="aicv-live-preview">';
    $output .= '<div class="aicv-resume-sheet">'; // Class 'template-ID' will be added by JS
    $output .= '<div class="preview-section" id="preview-personal-info">';
    $output .= '<h4 id="preview_full_name">[Full Name]</h4>';
    $output .= '<p><span id="preview_email">[Email]</span> | <span id="preview_phone">[Phone]</span> | <span id="preview_website">[Website/LinkedIn]</span></p>';
    $output .= '<p id="preview_address">[Address]</p>';
    $output .= '</div>'; // #preview-personal-info

    $output .= '<div class="preview-section" id="preview-summary">';
    $output .= '<h4>' . __( 'Summary', 'ai-cv-builder' ) . '</h4>';
    $output .= '<p id="preview_summary_content">' . __( 'Your professional summary here...', 'ai-cv-builder' ) . '</p>';
    $output .= '</div>'; // #preview-summary

    $output .= '<div class="preview-section" id="preview-experience">';
    $output .= '<h4>' . __( 'Experience', 'ai-cv-builder' ) . '</h4>';
    $output .= '<div id="preview-experience-entries">'; // JS will populate this based on form
    $output .= '<p>' . __( '[Job Title] at [Company Name] (Dates)', 'ai-cv-builder' ) . '</p><ul><li>' . __( 'Responsibility/Achievement 1', 'ai-cv-builder' ) . '</li></ul>'; // Default placeholder
    $output .= '</div>';
    $output .= '</div>'; // #preview-experience
    $output .= '<div class="preview-section" id="preview-education">';
    $output .= '<h4>' . __( 'Education', 'ai-cv-builder' ) . '</h4>';
    $output .= '<div id="preview-education-entries">';
    $output .= '<p>' . __( '[Degree] in [Major] from [University Name] (Year)', 'ai-cv-builder' ) . '</p>'; // Default placeholder
    $output .= '</div>';
    $output .= '</div>'; // #preview-education

    $output .= '<div class="preview-section" id="preview-skills">';
    $output .= '<h4>' . __( 'Skills', 'ai-cv-builder' ) . '</h4>';
    $output .= '<div id="preview-skills-entries">';
    $output .= '<p>' . __( 'Skill 1, Skill 2, Skill 3', 'ai-cv-builder' ) . '</p>'; // Default placeholder
    $output .= '</div>';
    $output .= '</div>'; // #preview-skills

    $output .= '</div>'; // .aicv-resume-sheet
    $output .= '</div>'; // #aicv-live-preview

    $output .= '</div>'; // #aicv-builder-main-ui
    $output .= '</div>'; // #aicv-cv-builder-wrapper

    // This flag can be used to conditionally enqueue scripts/styles if checked early enough in wp_enqueue_scripts
    // For a more robust solution, has_shortcode() in the enqueue function is better.
    // define('AICVB_SHORTCODE_LOADED', true);

    return $output;
}

/**
 * Register all shortcodes for the AI CV Builder plugin.
 */
function aicvb_register_shortcodes() {
    add_shortcode( 'ai_cv_builder', 'aicvb_render_cv_builder_shortcode' );
}
add_action( 'init', 'aicvb_register_shortcodes' );


/**
 * Enqueue frontend scripts and styles.
 */
function aicvb_enqueue_frontend_scripts_styles() {
    // Ideally, check if the shortcode is present on the page
    // For example, if ( is_singular() && has_shortcode( get_post()->post_content, 'ai_cv_builder' ) )
    // For now, we'll enqueue them broadly for simplicity of the subtask.

    wp_enqueue_style(
        'aicvb-frontend-styles',
        AI_CV_BUILDER_PLUGIN_URL . 'assets/css/frontend-styles.css',
        array(),
        AI_CV_BUILDER_VERSION
    );

    wp_enqueue_script(
        'aicvb-frontend-script',
        AI_CV_BUILDER_PLUGIN_URL . 'assets/js/frontend-script.js',
        array( 'jquery' ), // Add jQuery as a dependency
        AI_CV_BUILDER_VERSION,
        true // Load in footer
    );

    // Localize script with AJAX URL and nonce
    wp_localize_script(
        'aicvb-frontend-script', // Handle of the script to attach data to
        'aicvb_ajax_vars',      // Object name in JavaScript
        array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'save_cv_nonce' => wp_create_nonce( 'aicvb_save_cv_nonce' ),
            // Add other nonces or translatable strings here if needed
            'error_messages' => array(
                'general_save' => __('An error occurred while saving. Please try again.', 'ai-cv-builder'),
                'fill_required' => __('Please fill all required fields.', 'ai-cv-builder'),
            )
        )
    );
}
add_action( 'wp_enqueue_scripts', 'aicvb_enqueue_frontend_scripts_styles' );
?>
