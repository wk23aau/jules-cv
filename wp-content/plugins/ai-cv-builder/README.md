=== AI CV Builder ===
Contributors: AI CV Builder Bot
Tags: wordpress, plugin, resume, cv, ai, gemini api, resume builder
Requires at least: 6.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build professional, AI-enhanced CVs and resumes directly within your WordPress site using the power of Google's Gemini API.

== Description ==

AI CV Builder is a WordPress plugin designed to streamline the resume creation process. It leverages Google's Gemini API for advanced content generation and suggestions, helping users craft compelling CVs tailored to specific job descriptions. Whether you're starting from scratch, looking to refine existing content, or aiming to optimize your resume for a particular role, AI CV Builder provides the tools you need.

**What this plugin can do:**

*   **Create and Manage Multiple CVs:** Users can create and store multiple versions of their CVs.
*   **AI-Powered Content Generation:**
    *   Generate an initial CV draft based on a job title or description.
    *   Get AI-assisted suggestions for specific sections like professional summaries or work experience descriptions.
    *   Tailor the entire CV content to a specific job description with AI-driven recommendations.
*   **Structured CV Building:** A user-friendly two-pane interface allows for easy content input and live preview.
    *   **Content Sections:** Manage personal information, professional summary, work experience, education, and skills.
    *   **Repeatable Fields:** Easily add and manage multiple entries for work experience, education, and skills.
*   **Theme Customization:** Control the appearance of your CV with options for predefined theme styles, primary colors, and font families.
*   **Live Preview:** See changes to content and theme reflected instantly.
*   **PDF Export:** Download your formatted CV as a PDF document using html2pdf.js.
*   **Secure:** Implements WordPress security best practices including nonces, input sanitization, and output escaping.

This plugin aims to reduce the time and effort involved in resume writing by integrating AI assistance directly into your WordPress dashboard.

== Features ==

*   User-friendly CV builder interface with live preview.
*   Secure Custom Post Type for storing CV data.
*   Gemini API integration for:
    *   Initial CV draft generation from a job description.
    *   AI-assisted content suggestions for individual fields.
    *   Full CV tailoring based on a target job description.
*   Template selection for initial CV structure (concept, currently one main builder UI).
*   Theme customization:
    *   Predefined appearance themes (e.g., Classic, Modern).
    *   Primary color selection.
    *   Font family selection for the CV.
*   Dynamic addition and deletion of repeatable sections (Work Experience, Education, Skills).
*   AJAX-based saving for a smooth user experience.
*   PDF export of the generated CV.
*   Admin settings page for API Key management.
*   Secure AJAX handling with nonces and permission checks.
*   Comprehensive input sanitization and output escaping.
*   Efficient script and style loading (only when the shortcode is active).

== Installation ==

1.  **Download the Plugin:**
    *   Download the `ai-cv-builder.zip` file from the plugin source (e.g., GitHub repository if available, or from where you obtained it).
2.  **Install via WordPress Admin:**
    *   In your WordPress admin panel, navigate to `Plugins` > `Add New`.
    *   Click on `Upload Plugin` at the top of the page.
    *   Click `Choose File` and select the `ai-cv-builder.zip` file you downloaded.
    *   Click `Install Now`.
3.  **Activate the Plugin:**
    *   After the installation is complete, click `Activate Plugin`.
4.  **Configure API Key:**
    *   Navigate to `Settings` > `AI CV Builder` in your WordPress admin panel.
    *   Enter your Google Gemini API Key. You can obtain one from Google AI Studio.
    *   Click `Save API Key`.

== Frequently Asked Questions ==

*   **How do I get a Gemini API Key?**
    You can obtain a Gemini API key from [Google AI Studio](https://aistudio.google.com/app/apikey) (or the relevant Google Cloud project console where the Gemini API is enabled). Follow Google's documentation for the latest instructions.

*   **What if AI generation fails?**
    *   **Check API Key:** Ensure your API key is correctly entered in `Settings` > `AI CV Builder` and is active.
    *   **API Quota/Billing:** Verify that your Gemini API account has sufficient quota and that billing is enabled if required by Google.
    *   **Prompt Complexity:** Sometimes, very complex or ambiguous prompts might not yield the best results. Try simplifying your input for AI features.
    *   **Internet Connection:** Ensure your server can make outbound HTTPS requests (required to connect to the Gemini API).
    *   **Plugin Errors:** Check your browser's developer console for any error messages, and enable WordPress debugging if necessary to capture server-side errors.

*   **Can I customize the PDF output further?**
    Currently, the PDF export uses a standard set of options for formatting (Letter size, portrait orientation, basic margins). Advanced customization of the PDF template itself (beyond the theme options in the builder) would require code modifications. Future versions might include more PDF export options.

== Usage ==

1.  **Add the Shortcode:**
    *   Create a new page or edit an existing one where you want the CV builder to appear.
    *   Add the shortcode `[ai_cv_builder]` to the content of the page.
    *   Publish or update the page.
2.  **Navigating the Builder:**
    *   **Template Selection (Initial):** When you first load the page with the shortcode (for a new CV), you might be presented with template choices (this feature is conceptual for initial structure; currently leads to one main builder).
    *   **Initial AI Input Modal:** For a new CV, a modal will pop up asking for a job title or description to generate an initial draft. You can use this or choose to "Start Blank".
    *   **Content Tab:** This is where you'll spend most of your time.
        *   **Personal Information:** Your name, contact details.
        *   **Professional Summary:** Your elevator pitch. Use the "AI Assist" button for suggestions based on your target job (if provided in the tailoring section or from first experience item).
        *   **Work Experience, Education, Skills:** Add, edit, or delete entries. Each experience description also has an "AI Assist" button to help generate bullet points based on the job title and company for that entry.
    *   **Theme Tab:** Customize the appearance of your CV.
        *   **Appearance Theme:** Select from predefined styles like Default, Classic, Modern.
        *   **Primary Color:** Choose an accent color for headings and other elements.
        *   **Font Family:** Select a font for the body text of your CV.
    *   **Live Preview:** The right-hand pane shows a live preview of your CV as you make changes to content or theme.
3.  **Key AI Features:**
    *   **Initial CV Generation:** When starting a new CV, provide a job description in the initial modal to get an AI-generated draft for summary, skills, and work experience.
    *   **AI Assist (Field-Specific):** Click the "AI Assist" button next to the Professional Summary or within a Work Experience entry's description field to get targeted AI suggestions for that specific field.
    *   **Tailor CV with AI:** In the "Content" tab, there's a dedicated section "Tailor CV to Job Description." Paste a full job description here and click "Tailor CV with AI." A modal will appear showing your original content alongside AI-suggested revisions for summary, skills, and work experience, tailored to the job description. You can then choose to apply these suggestions.
4.  **Saving and Downloading:**
    *   **Saving:** The CV is saved automatically via AJAX shortly after you make changes. There is also a manual "Save CV" button. Look for success messages or error notifications at the top right of the builder.
    *   **Downloading:** Click the "Download CV as PDF" button (usually located above the builder interface) to generate and download a PDF version of your current CV preview.

== Screenshots ==

1.  Main CV builder interface showing content input fields and live preview.
2.  Template selection screen (conceptual).
3.  Admin settings page for API Key.
4.  AI Tailoring suggestions modal.
5.  Theme customization options in the "Theme" tab.

*(Screenshots to be added here once available)*

== Changelog ==

= 1.0.0 =
* Initial release.
* Features: CV creation, AI-powered summary, skills, and experience generation, CV tailoring to job description, theme customization, live preview, PDF export.

== Upgrade Notice ==

= 1.0.0 =
* Initial release. No upgrade notice needed at this time.
