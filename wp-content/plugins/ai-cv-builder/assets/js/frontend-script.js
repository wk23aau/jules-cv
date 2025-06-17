(function($) {
    'use strict';

    $(document).ready(function() {
        var selectedTemplateId = null;
        var $cvIdField = $('#aicv_cv_id');
        var $saveStatus = $('#aicv-save-status');
        var $spinner = $('#aicv-save-spinner');

        // --- Template Selection ---
        $('#aicv-template-selection-ui').on('click', '.aicv-select-template-button', function(e) {
            e.preventDefault();
            selectedTemplateId = $(this).data('template-id');

            if (selectedTemplateId) {
                console.log('Selected template ID:', selectedTemplateId);
                $('#aicv-template-selection-ui').hide();
                $('#aicv-builder-main-ui').show();
                $('#aicv-live-preview .aicv-resume-sheet').removeClass().addClass('aicv-resume-sheet template-' + selectedTemplateId);

                // Initial save if CV ID is not set (new CV)
                if (!$cvIdField.val() || $cvIdField.val() === '0') {
                    console.log('New CV, performing initial save...');
                    saveCvData(true); // Pass true for initial save
                }
            } else {
                alert('Could not determine the template ID.');
            }
        });

        // --- Live Preview Updates ---
        // Personal Info
        $('#aicv_full_name').on('input', function() { $('#preview_full_name').text($(this).val() || '[Full Name]'); });
        $('#aicv_email').on('input', function() { $('#preview_email').text($(this).val() || '[Email]'); });
        $('#aicv_phone').on('input', function() { $('#preview_phone').text($(this).val() || '[Phone]'); });
        $('#aicv_address').on('input', function() { $('#preview_address').text($(this).val() || '[Address]'); });
        $('#aicv_website').on('input', function() { $('#preview_website').text($(this).val() || '[Website/LinkedIn]'); });
        // Summary
        $('#aicv_summary').on('input', function() { $('#preview_summary_content').text($(this).val() || 'Your professional summary here...'); });


        // --- Collect CV Data ---
        function collectCvData() {
            var cvData = {
                personal_info: {
                    full_name: $('#aicv_full_name').val(),
                    email: $('#aicv_email').val(),
                    phone: $('#aicv_phone').val(),
                    address: $('#aicv_address').val(),
                    website: $('#aicv_website').val(),
                },
                professional_summary: $('#aicv_summary').val(),
                experience: [],
                education: [],
                skills: []
            };

            // Collect Experience data
            $('#aicv-experience-entries .aicv-experience-entry').each(function(index) {
                var entry = {
                    job_title: $(this).find('input[name^="aicv_experience[' + index + '][job_title]"]').val(),
                    company: $(this).find('input[name^="aicv_experience[' + index + '][company]"]').val(),
                    dates: $(this).find('input[name^="aicv_experience[' + index + '][dates]"]').val(),
                    description: $(this).find('textarea[name^="aicv_experience[' + index + '][description]"]').val()
                };
                cvData.experience.push(entry);
            });

            // TODO: Collect Education data similarly
            // TODO: Collect Skills data similarly

            // Collect Theme Settings
            cvData.aicv_selected_theme_class = $('#aicv_theme_select').val();
            cvData.aicv_primary_color = $('#aicv_primary_color').val();
            cvData.aicv_font_family = $('#aicv_font_family').val();
            // The initial 'selectedTemplateId' (functional template) is already stored in the 'selectedTemplateId' JS variable
            // and passed directly to AJAX, not part of this cvData object from form fields.

            return cvData;
        }

        // --- Save CV Data (AJAX) ---
        function saveCvData(isInitialSave = false) {
            var collectedData = collectCvData();
            var currentCvId = $cvIdField.val();

            $spinner.addClass('is-active');
            $saveStatus.hide();

            $.ajax({
                url: aicvb_ajax_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'aicvb_save_cv_data',
                    nonce: aicvb_ajax_vars.save_cv_nonce,
                    cv_id: currentCvId,
                    cv_data: collectedData,
                    template_id: selectedTemplateId
                },
                success: function(response) {
                    if (response.success) {
                        $cvIdField.val(response.data.cv_id); // Update CV ID if it was new
                        if (!isInitialSave) {
                           $saveStatus.text(response.data.message || 'CV Saved!').removeClass('error').addClass('success').show();
                        } else {
                            console.log('Initial save successful. CV ID:', response.data.cv_id);
                        }
                    } else {
                        $saveStatus.text(response.data.message || aicvb_ajax_vars.error_messages.general_save).removeClass('success').addClass('error').show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                    $saveStatus.text(aicvb_ajax_vars.error_messages.general_save + ' (' + textStatus + ')').removeClass('success').addClass('error').show();
                },
                complete: function() {
                    $spinner.removeClass('is-active');
                    if (!isInitialSave) {
                        setTimeout(function(){ $saveStatus.fadeOut(); }, 5000);
                    }
                }
            });
        }

        $('#aicv-manual-save').on('click', function() {
            saveCvData();
        });

        // --- Repeatable Fields (Basic for Experience) ---
        var experienceEntryIndex = 1; // Start next index at 1 as 0 is in HTML
        $('#aicv-add-experience').on('click', function() {
            var $newEntry = $('#aicv-experience-entries .aicv-experience-entry:first').clone();
            $newEntry.find('input, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[' + experienceEntryIndex + ']');
                    $(this).attr('name', name).val('');
                }
            });
            $newEntry.appendTo('#aicv-experience-entries');
            experienceEntryIndex++;
        });

        $('#aicv-experience-entries').on('click', '.aicv-delete-entry', function() {
            if ($('#aicv-experience-entries .aicv-experience-entry').length > 1) {
                $(this).closest('.aicv-experience-entry').remove();
            } else {
                alert('At least one entry is required.'); // Or clear the fields
            }
        });

        // TODO: Implement similar add/delete for Education and Skills, updating their respective indices.

        // --- Theme Customization JS ---
        var $resumeSheet = $('#aicv-live-preview .aicv-resume-sheet');

        // Predefined Themes
        $('#aicv_theme_select').on('change', function() {
            var selectedThemeClass = $(this).val();
            // Remove any existing theme- class before adding a new one
            $resumeSheet.removeClass (function (index, className) {
                return (className.match (/(^|\s)theme-\S+/g) || []).join(' ');
            });
            if (selectedThemeClass) {
                $resumeSheet.addClass(selectedThemeClass);
            }
            // Note: The 'selectedTemplateId' variable stores the *initial* functional template choice.
            // 'selectedThemeClass' is for the appearance theme from the theme tab.
            // These might be the same or different concepts depending on final plugin design.
            // For now, this control *only* changes appearance theme class.
        });

        // Primary Color
        $('#aicv_primary_color').on('input change', function() { // 'input' for live preview, 'change' for some pickers
            $resumeSheet.css('--aicv-primary-color', $(this).val());
        });

        // Font Family
        $('#aicv_font_family').on('change', function() {
            $resumeSheet.css('--aicv-font-family', $(this).val());
        });


        // --- Tab switching logic for control panel ---
        $('#aicv-control-panel .aicv-tabs').on('click', '.aicv-tab-button', function() {
            var tabId = $(this).data('tab');
            $('#aicv-control-panel .aicv-tab-button').removeClass('active');
            $('#aicv-control-panel .aicv-tab-pane').removeClass('active').hide();
            $(this).addClass('active');
            $('#aicv-tab-' + tabId).addClass('active').show();
        });

        if ($('#aicv-control-panel .aicv-tab-button.active[data-tab="content"]').length) {
            $('#aicv-tab-content').show();
            $('#aicv-tab-theme').hide();
        }
    });

})(jQuery);
