<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AI CV Builder Admin Settings
 *
 * @package AI_CV_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Function to add the admin menu
function aicvb_admin_menu() {
    add_options_page(
        'AI CV Builder Settings', // Page title
        'AI CV Builder',          // Menu title
        'manage_options',         // Capability required
        'ai-cv-builder-settings', // Menu slug
        'aicvb_settings_page_html' // Callback function to display the page
    );
}
add_action( 'admin_menu', 'aicvb_admin_menu' );

// Function to render the settings page HTML
function aicvb_settings_page_html() {
    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'aicvb_settings_group' );
            do_settings_sections( 'aicvb_settings_page' );
            submit_button( 'Save API Key' );
            ?>
        </form>
    </div>
    <?php
}

// Function to register settings, sections, and fields
function aicvb_register_settings() {
    // Register the API key setting
    register_setting(
        'aicvb_settings_group',
        'aicvb_gemini_api_key',
        'sanitize_text_field'
    );

    // Add the API key section
    add_settings_section(
        'aicvb_api_key_section',
        'Gemini API Key Settings',
        null, // Callback for section description (optional)
        'aicvb_settings_page'
    );

    // Add the API key field
    add_settings_field(
        'aicvb_gemini_api_key_field',
        'Gemini API Key',
        'aicvb_gemini_api_key_field_html',
        'aicvb_settings_page',
        'aicvb_api_key_section'
    );
}
add_action( 'admin_init', 'aicvb_register_settings' );

// Function to render the API key input field
function aicvb_gemini_api_key_field_html() {
    $api_key = get_option( 'aicvb_gemini_api_key', '' );
    ?>
    <input type="text" name="aicvb_gemini_api_key" value="<?php echo esc_attr( $api_key ); ?>" size="50">
    <p class="description">Enter your Gemini API Key. This is required for the plugin to generate content.</p>
    <?php
}

// Function to enqueue admin styles
function aicvb_enqueue_admin_styles( $hook_suffix ) {
    // Check if we are on the plugin's settings page
    // The hook_suffix for an options page is 'settings_page_{menu_slug}'
    if ( 'settings_page_ai-cv-builder-settings' !== $hook_suffix ) {
        return;
    }
    wp_enqueue_style(
        'aicvb-admin-styles',
        AI_CV_BUILDER_PLUGIN_URL . 'admin/css/admin-styles.css',
        array(),
        AI_CV_BUILDER_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'aicvb_enqueue_admin_styles' );

?>
