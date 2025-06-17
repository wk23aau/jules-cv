<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Name:       AI CV Builder
 * Plugin URI:        https://example.com/ai-cv-builder
 * Description:       A plugin to build CVs and resumes using AI, with content generation powered by the Gemini API.
 * Version:           1.0.0
 * Author:            AI CV Builder Bot
 * Author URI:        https://example.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-cv-builder
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'AI_CV_BUILDER_VERSION', '1.0.0' );
define( 'AI_CV_BUILDER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_CV_BUILDER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Define Meta Key Constants
define( 'AICVB_META_PERSONAL_INFO', '_aicvb_personal_info' );
define( 'AICVB_META_SUMMARY', '_aicvb_professional_summary' );
define( 'AICVB_META_EXPERIENCE', '_aicvb_experience' );
define( 'AICVB_META_EDUCATION', '_aicvb_education' );
define( 'AICVB_META_SKILLS', '_aicvb_skills' );
// define( 'AICVB_META_THEME_SETTINGS', '_aicvb_theme_settings' ); // Replaced by individual theme meta keys for now

// Theme Specific Meta Keys
define( 'AICVB_META_SELECTED_TEMPLATE_ID', '_aicvb_selected_template_id'); // Stores the initial template choice like 'classic', 'modern'
define( 'AICVB_META_SELECTED_THEME', '_aicvb_selected_theme_class'); // Stores the theme style class like 'theme-default', 'theme-classic'
define( 'AICVB_META_PRIMARY_COLOR', '_aicvb_primary_color' );
define( 'AICVB_META_FONT_FAMILY', '_aicvb_font_family' );


// Activation hook
register_activation_hook( __FILE__, 'ai_cv_builder_activate' );
function ai_cv_builder_activate() {
    // Ensure CPT is registered before flushing
    if ( function_exists( 'aicvb_register_cv_post_type' ) ) {
        aicvb_register_cv_post_type();
    }
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'ai_cv_builder_deactivate' );
function ai_cv_builder_deactivate() {
    flush_rewrite_rules();
}

// Uninstall hook
register_uninstall_hook( __FILE__, 'ai_cv_builder_uninstall' );
function ai_cv_builder_uninstall() {
    // Uninstall code here
}

// Include other necessary files
if ( file_exists( AI_CV_BUILDER_PLUGIN_DIR . 'admin/admin-settings.php' ) ) {
    require_once AI_CV_BUILDER_PLUGIN_DIR . 'admin/admin-settings.php';
}
if ( file_exists( AI_CV_BUILDER_PLUGIN_DIR . 'includes/post-types.php' ) ) {
    require_once AI_CV_BUILDER_PLUGIN_DIR . 'includes/post-types.php';
}
if ( file_exists( AI_CV_BUILDER_PLUGIN_DIR . 'includes/cv-data-functions.php' ) ) {
    require_once AI_CV_BUILDER_PLUGIN_DIR . 'includes/cv-data-functions.php';
}
if ( file_exists( AI_CV_BUILDER_PLUGIN_DIR . 'includes/shortcodes.php' ) ) {
    require_once AI_CV_BUILDER_PLUGIN_DIR . 'includes/shortcodes.php';
}
if ( file_exists( AI_CV_BUILDER_PLUGIN_DIR . 'includes/ajax-handlers.php' ) ) {
    require_once AI_CV_BUILDER_PLUGIN_DIR . 'includes/ajax-handlers.php';
}
if ( file_exists( AI_CV_BUILDER_PLUGIN_DIR . 'includes/gemini-api.php' ) ) {
    require_once AI_CV_BUILDER_PLUGIN_DIR . 'includes/gemini-api.php';
}
// require_once AI_CV_BUILDER_PLUGIN_DIR . 'includes/class-ai-cv-builder.php';

// Initialize the plugin
// add_action( 'plugins_loaded', array( 'AI_CV_Builder', 'get_instance' ) );
?>
