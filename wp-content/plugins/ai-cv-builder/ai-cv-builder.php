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
define( 'AI_CV_BUILDER_VERSION', '1.0.1' );
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

// Function to load plugin files
function aicvb_load_plugin_files() {
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
}
add_action( 'plugins_loaded', 'aicvb_load_plugin_files' );

// Initialize the plugin - This line can be removed if not used or kept if another class instantiation is planned.
// add_action( 'plugins_loaded', array( 'AI_CV_Builder', 'get_instance' ) );

/**
 * Dequeue jQuery Migrate for the frontend.
 *
 * jQuery Migrate is often included by default in WordPress for backward compatibility,
 * but it's best to ensure all custom JavaScript is up-to-date and remove it.
 */
function aicvb_dequeue_jquery_migrate( &$scripts ) {
    if ( ! is_admin() ) { // Only dequeue for the frontend
        $jquery_core_handle = 'jquery-core'; // WordPress 5.6+ uses 'jquery-core' for the main jQuery file
        $jquery_migrate_handle = 'jquery-migrate';

        // Check if jQuery core exists and has jquery-migrate as a dependency
        if ( isset( $scripts->registered[ $jquery_core_handle ] ) &&
             isset( $scripts->registered[ $jquery_migrate_handle ] ) ) {

            // Find jquery-migrate in the dependencies of jquery (or jquery-core)
            $jquery_dependencies = &$scripts->registered[ $jquery_core_handle ]->deps;
            $migrate_key = array_search( $jquery_migrate_handle, $jquery_dependencies );

            if ( $migrate_key !== false ) {
                // Remove jquery-migrate from jquery's dependencies
                unset( $jquery_dependencies[ $migrate_key ] );
            }

            // Also, explicitly deregister jquery-migrate to be sure
            // $scripts->remove( $jquery_migrate_handle ); // This might be too aggressive or not work as expected with wp_default_scripts
            // A gentler way for wp_default_scripts is to nullify its properties
             if (isset($scripts->registered[$jquery_migrate_handle])) {
                 $scripts->registered[$jquery_migrate_handle]->src = false;
                 $scripts->registered[$jquery_migrate_handle]->ver = false;
                 // If it has its own dependencies that are not needed elsewhere, they might also need handling.
                 // For jquery-migrate, it usually has no further dependencies.
             }
        }
    }
}
// For WordPress versions before 5.5, jquery-migrate might be handled differently.
// The hook 'wp_default_scripts' is generally robust for modifying default scripts.
// Using a priority like 11 to run after default scripts are set up.
add_action( 'wp_default_scripts', 'aicvb_dequeue_jquery_migrate', 11 );

?>
