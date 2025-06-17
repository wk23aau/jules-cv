<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AI CV Builder Gemini API Integration
 *
 * @package AI_CV_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define Gemini API Base URL and Model Name
// Note: Verify these against the latest Google Gemini API documentation.
define( 'AICVB_GEMINI_API_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/models/' );
define( 'AICVB_GEMINI_MODEL', 'gemini-1.5-flash-latest' ); // Example model

/**
 * Makes a generic request to the Gemini API.
 *
 * @param string $model_action The API action to perform (e.g., 'generateContent').
 * @param array  $prompt_data  The data to send to the API, typically containing the prompt.
 * @param string|null $api_key_override Optional. Override the stored API key.
 * @return array|WP_Error The decoded JSON response from the API or a WP_Error on failure.
 */
function aicvb_make_gemini_api_request( $model_action, $prompt_data, $api_key_override = null ) {
    $api_key = $api_key_override ? $api_key_override : get_option( 'aicvb_gemini_api_key' );

    if ( empty( $api_key ) ) {
        return new WP_Error( 'api_key_missing', __( 'Gemini API key is not configured.', 'ai-cv-builder' ) );
    }

    $request_url = AICVB_GEMINI_API_BASE_URL . AICVB_GEMINI_MODEL . ':' . $model_action . '?key=' . $api_key;

    $args = array(
        'method'  => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body'    => wp_json_encode( $prompt_data ),
        'timeout' => apply_filters( 'aicvb_gemini_api_request_timeout', 30 ), // Allow filtering timeout
    );

    $response = wp_remote_post( $request_url, $args );

    if ( is_wp_error( $response ) ) {
        // Add context to the WordPress error
        return new WP_Error(
            $response->get_error_code(),
            sprintf( __( 'API request failed (WP_Error): %s', 'ai-cv-builder' ), $response->get_error_message() ),
            $response->get_error_data()
        );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );

    if ( $response_code !== 200 ) {
        $error_message = sprintf( __( 'API request failed with HTTP status %d.', 'ai-cv-builder' ), $response_code );
        $error_data = array( 'status' => $response_code );

        // Try to get more details from response body if available
        $decoded_error_body = json_decode( $response_body, true );
        if ( $decoded_error_body && isset( $decoded_error_body['error']['message'] ) ) {
            $error_message .= ' ' . sprintf( __( 'Error: %s', 'ai-cv-builder' ), $decoded_error_body['error']['message'] );
            $error_data['api_error'] = $decoded_error_body['error'];
        } elseif (!empty($response_body)) {
            $error_message .= ' ' . sprintf( __( 'Response: %s', 'ai-cv-builder' ), substr(esc_html($response_body), 0, 200) . '...');
        }
        return new WP_Error( 'api_http_error', $error_message, $error_data );
    }

    $decoded_body = json_decode( $response_body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error( 'json_decode_error', __( 'Failed to decode API JSON response: ', 'ai-cv-builder' ) . json_last_error_msg() );
    }

    // Check for errors within the Gemini response structure (e.g., if isset($decoded_body['error']))
    // This structure can vary based on the API version and specific error.
    if ( isset( $decoded_body['error'] ) && is_array( $decoded_body['error'] ) ) {
        $error_message = isset( $decoded_body['error']['message'] ) ? $decoded_body['error']['message'] : __( 'Unknown API error.', 'ai-cv-builder' );
        return new WP_Error( 'gemini_api_specific_error', $error_message, $decoded_body['error'] );
    }

    // Some Gemini errors might be in a different structure, e.g. for safety reasons (prompt blocked)
    if ( empty($decoded_body['candidates']) && isset($decoded_body['promptFeedback']) ) {
        $block_reason = isset($decoded_body['promptFeedback']['blockReason']) ? $decoded_body['promptFeedback']['blockReason'] : 'unknown';
        $safety_ratings_info = '';
        if(isset($decoded_body['promptFeedback']['safetyRatings'])){
            foreach($decoded_body['promptFeedback']['safetyRatings'] as $rating){
                $safety_ratings_info .= sprintf(" Category: %s, Probability: %s.", $rating['category'], $rating['probability']);
            }
        }
        return new WP_Error(
            'prompt_blocked_or_empty_response',
            sprintf(__('The prompt was blocked or returned an empty response. Reason: %s.%s', 'ai-cv-builder'), $block_reason, $safety_ratings_info),
            $decoded_body['promptFeedback']
        );
    }


    return $decoded_body;
}

/**
 * Generates text from a given prompt using the Gemini API.
 *
 * @param string $prompt_text The text prompt to send to the API.
 * @return string|WP_Error The generated text string on success, or a WP_Error on failure.
 */
function aicvb_gemini_generate_text_from_prompt( $prompt_text ) {
    // Construct the prompt data in the format Gemini expects.
    // This is a common structure for simple text generation.
    // Refer to Gemini API documentation for the exact structure for your model and use case.
    $prompt_data = array(
        'contents' => array(
            array(
                'parts' => array(
                    array(
                        'text' => $prompt_text,
                    ),
                ),
            ),
        ),
        // Optional: Add generationConfig if needed
        // 'generationConfig' => array(
        //   'temperature' => 0.7,
        //   'topK' => 20,
        //   'topP' => 0.8,
        //   'maxOutputTokens' => 2048,
        //   'stopSequences' => [],
        // ),
        // Optional: Add safetySettings if needed
        // 'safetySettings' => array(
        //   array('category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'),
        //   // ... other categories
        // )
    );

    $response = aicvb_make_gemini_api_request( 'generateContent', $prompt_data );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    // Attempt to extract the generated text from the response.
    // IMPORTANT: This path MUST be verified against the actual Gemini API documentation for the model being used.
    // Example path: $response['candidates'][0]['content']['parts'][0]['text']
    if ( isset( $response['candidates'][0]['content']['parts'][0]['text'] ) ) {
        return $response['candidates'][0]['content']['parts'][0]['text'];
    } elseif (isset($response['candidates']) && is_array($response['candidates']) && empty($response['candidates'])) {
         // This case might be due to safety settings or other configurations resulting in no candidates.
        return new WP_Error('no_candidates_returned', __('The API returned no candidates. This might be due to safety settings or the nature of the prompt.', 'ai-cv-builder'), $response);
    }

    // If the expected path is not found, return an error or the full response for debugging.
    // For this subtask, returning an error is more informative.
    return new WP_Error( 'unexpected_response_structure', __( 'Could not extract text from API response. The response structure might have changed or was not as expected.', 'ai-cv-builder' ), $response );
}

?>
