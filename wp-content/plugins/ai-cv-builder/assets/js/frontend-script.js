(function($) {
    'use strict';

    $(document).ready(function() {
        var $notificationsArea = $('#aicv-user-notifications'); // Cache notification area
        var selectedTemplateId = null;
        var $cvIdField = $('#aicv_cv_id');
        var $saveStatus = $('#aicv-save-status');
        var $spinner = $('#aicv-save-spinner');
        var $initialInputModal = $('#aicv-initial-input-modal');
        var $jobDescriptionInput = $('#aicv_job_description_input');
        var $generateInitialCvButton = $('#aicv_generate_initial_cv_button');
        var $skipInitialCvButton = $('#aicv_skip_initial_cv_button');
        var $modalLoadingIndicator = $initialInputModal.find('.aicv-loading-indicator');

        // --- User Message Functions ---
        function clearUserMessages() {
            $notificationsArea.empty();
        }

        function showUserMessage(message, type = 'error', duration = 7000) {
            // clearUserMessages(); // Optional: clear previous messages or allow multiple
            var messageClass = 'aicv-message aicv-message-' + type;
            var $messageDiv = $('<div>').addClass(messageClass).text(message);
            var $closeButton = $('<button>').addClass('aicv-message-close').html('&times;').on('click', function() {
                $(this).parent().fadeOut(300, function() { $(this).remove(); });
            });
            $messageDiv.append($closeButton);
            $notificationsArea.append($messageDiv);

            if (duration > 0) {
                setTimeout(function() {
                    $messageDiv.fadeOut(500, function() { $(this).remove(); });
                }, duration);
            }
        }

        // --- Template Selection ---
        $('#aicv-template-selection-ui').on('click', '.aicv-select-template-button', function(e) {
            e.preventDefault();
            selectedTemplateId = $(this).data('template-id');

            if (selectedTemplateId) {
                // console.log('Selected template ID:', selectedTemplateId);
                $('#aicv-template-selection-ui').hide();
                $('#aicv-builder-main-ui').show();
                var $resumeSheet = $('#aicv-live-preview .aicv-resume-sheet');
                $resumeSheet.removeClass().addClass('aicv-resume-sheet theme-default template-' + selectedTemplateId);

                if (!$cvIdField.val() || $cvIdField.val() === '0') {
                    // console.log('New CV, performing initial save to get ID...');
                    saveCvData(true, function(success, data, message) {
                        if (success && data.cv_id) {
                            $cvIdField.val(data.cv_id);
                            // console.log('Initial save successful. CV ID:', data.cv_id, "Showing AI input modal.");
                            $initialInputModal.show();
                        } else {
                            showUserMessage(message || 'Error: Could not create a new CV. Please try again.', 'error');
                            $('#aicv-builder-main-ui').hide();
                            $('#aicv-template-selection-ui').show();
                        }
                    });
                } else {
                    // console.log('Existing CV loaded. CV ID:', $cvIdField.val());
                }
            } else {
                showUserMessage('Could not determine the template ID.', 'error');
            }
        });

        // --- Initial CV Generation Modal Logic ---
        $generateInitialCvButton.on('click', function() {
            var jobDesc = $jobDescriptionInput.val().trim();
            if (!jobDesc) {
                showUserMessage('Please enter a job title or description.', 'info', 3000);
                $jobDescriptionInput.focus();
                return;
            }
            clearUserMessages();
            $modalLoadingIndicator.show();
            $(this).prop('disabled', true);
            $skipInitialCvButton.prop('disabled', true);

            $.ajax({
                url: aicvb_ajax_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'aicvb_generate_initial_cv',
                    nonce: aicvb_ajax_vars.generate_cv_nonce,
                    cv_id: $cvIdField.val(),
                    job_description: jobDesc
                },
                success: function(response) {
                    if (response.success) {
                        populateFormWithAIData(response.data.generated_data);
                        $initialInputModal.hide();
                        showUserMessage(response.data.message || 'CV draft generated!', 'success');
                    } else {
                        showUserMessage(response.data.message || 'Error generating CV draft.', 'error');
                    }
                },
                error: function() {
                    showUserMessage('AJAX error: Could not connect to server for initial CV generation.', 'error');
                },
                complete: function() {
                    $modalLoadingIndicator.hide();
                    $generateInitialCvButton.prop('disabled', false);
                    $skipInitialCvButton.prop('disabled', false);
                }
            });
        });

        $skipInitialCvButton.on('click', function() {
            $initialInputModal.hide();
            // console.log('Skipped AI initial generation.');
        });

        function populateFormWithAIData(data) {
            if (!data) return;

            if (data.professional_summary) {
                $('#aicv_summary').val(data.professional_summary).trigger('input');
            }

            // Skills - assuming skills are an array of objects like [{skill_name: '...'}, ...]
            if (data.skills && Array.isArray(data.skills)) {
                var $skillsContainer = $('#aicv-skills-entries');
                var $firstSkillEntry = $skillsContainer.find('.aicv-skill-entry:first');
                $skillsContainer.empty(); // Clear existing (usually one blank)

                data.skills.forEach(function(skill, index) {
                    var $newEntry = $firstSkillEntry.clone();
                    $newEntry.find('input[name$="[skill_name]"]').attr('name', 'aicv_skills[' + index + '][skill_name]').val(skill.skill_name);
                    $skillsContainer.append($newEntry);
                    // Trigger input for live preview if skills preview is implemented
                });
                skillEntryIndex = data.skills.length; // Reset index for next manual add
            }

            // Experience - assuming experience is an array of objects
            if (data.experience && Array.isArray(data.experience)) {
                var $experienceContainer = $('#aicv-experience-entries');
                var $firstExperienceEntry = $experienceContainer.find('.aicv-experience-entry:first');
                $experienceContainer.empty();

                data.experience.forEach(function(exp, index) {
                    var $newEntry = $firstExperienceEntry.clone();
                    $newEntry.find('input[name$="[job_title]"]').attr('name', 'aicv_experience[' + index + '][job_title]').val(exp.job_title);
                    $newEntry.find('input[name$="[company]"]').attr('name', 'aicv_experience[' + index + '][company]').val(exp.company);
                    $newEntry.find('input[name$="[dates]"]').attr('name', 'aicv_experience[' + index + '][dates]').val(exp.dates);
                    $newEntry.find('textarea[name$="[description]"]').attr('name', 'aicv_experience[' + index + '][description]').val(exp.description);
                    $experienceContainer.append($newEntry);
                    // Trigger input for live preview if implemented
                });
                experienceEntryIndex = data.experience.length; // Reset index
            }
            // Trigger a save after populating form
            saveCvData();
        }


        // --- Live Preview Updates ---
        // Personal Info - Targets updated HTML structure
        $controlPanel.on('input', '#aicv_full_name', function() { $('#preview_full_name').text($(this).val() || '[Full Name]'); });
        $controlPanel.on('input', '#aicv_email', function() { $('#preview_email').text($(this).val() || '[Email]'); });
        $controlPanel.on('input', '#aicv_phone', function() { $('#preview_phone').text($(this).val() || '[Phone]'); });
        $controlPanel.on('input', '#aicv_address', function() { $('#preview_address').text($(this).val() || '[Address]'); });
        $controlPanel.on('input', '#aicv_website', function() { $('#preview_website').text($(this).val() || '[Website/LinkedIn]'); });
        // Summary
        $controlPanel.on('input', '#aicv_summary', function() { $('#preview_summary_content').text($(this).val() || 'Your professional summary here...'); });

        // Function to generate preview for a single experience item
        function generateExperiencePreviewHtml(index, data = {}) {
            var title = escapeHtml(data.job_title || '[Job Title]');
            var company = escapeHtml(data.company || '[Company]');
            var dates = escapeHtml(data.dates || '[Dates]');
            var description = escapeHtml(data.description || '[Description placeholder]').replace(/\n/g, '<br>'); // Preserve line breaks
            return `
                <div class="preview-experience-item" data-preview-for="exp-${index}">
                    <h4 class="preview-job-title">${title}</h4>
                    <p class="preview-company-dates"><span class="preview-company">${company}</span> | <span class="preview-dates">${dates}</span></p>
                    <div class="preview-description">${description}</div>
                </div>`;
        }
        // Function to generate preview for a single education item
        function generateEducationPreviewHtml(index, data = {}) {
            var degree = escapeHtml(data.degree || '[Degree]');
            var institution = escapeHtml(data.institution || '[Institution]');
            var dates = escapeHtml(data.dates || '[Dates]');
            var description = escapeHtml(data.description || '[Description placeholder]').replace(/\n/g, '<br>');
             return `
                <div class="preview-education-item" data-preview-for="edu-${index}">
                    <h4 class="preview-degree">${degree}</h4>
                    <p class="preview-institution-dates"><span class="preview-institution">${institution}</span> | <span class="preview-dates">${dates}</span></p>
                    <div class="preview-description">${description}</div>
                </div>`;
        }
        // Function to generate preview for a single skill item
        function generateSkillPreviewHtml(index, data = {}) {
            var skillName = escapeHtml(data.skill_name || '[Skill]');
            return `<li class="preview-skill-item" data-preview-for="skill-${index}">${skillName}</li>`;
        }

        // Live update for Experience fields
        $controlPanel.on('input', '#aicv-experience-entries .aicv-experience-entry input, #aicv-experience-entries .aicv-experience-entry textarea', function() {
            var $entry = $(this).closest('.aicv-experience-entry');
            var index = $entry.data('entry-index'); // Requires data-entry-index on the form entry
            var $previewEntry = $('#preview-experience-entries .preview-experience-item[data-preview-for="exp-' + index + '"]');

            if ($previewEntry.length) {
                $previewEntry.find('.preview-job-title').text($entry.find('.aicv-exp-job-title').val() || '[Job Title]');
                $previewEntry.find('.preview-company').text($entry.find('.aicv-exp-company').val() || '[Company]');
                $previewEntry.find('.preview-dates').text($entry.find('.aicv-exp-dates').val() || '[Dates]');
                $previewEntry.find('.preview-description').html(escapeHtml($entry.find('.aicv-exp-description').val() || '[Description placeholder]').replace(/\n/g, '<br>'));
            }
        });
        // Live update for Education fields
         $controlPanel.on('input', '#aicv-education-entries .aicv-education-entry input, #aicv-education-entries .aicv-education-entry textarea', function() {
            var $entry = $(this).closest('.aicv-education-entry');
            var index = $entry.data('entry-index');
            var $previewEntry = $('#preview-education-entries .preview-education-item[data-preview-for="edu-' + index + '"]');
            if ($previewEntry.length) {
                $previewEntry.find('.preview-degree').text($entry.find('input[name$="[degree]"]').val() || '[Degree]');
                $previewEntry.find('.preview-institution').text($entry.find('input[name$="[institution]"]').val() || '[Institution]');
                $previewEntry.find('.preview-dates').text($entry.find('input[name$="[dates]"]').val() || '[Dates]');
                $previewEntry.find('.preview-description').html(escapeHtml($entry.find('textarea[name$="[description]"]').val() || '[Description placeholder]').replace(/\n/g, '<br>'));
            }
        });
        // Live update for Skill fields
        $controlPanel.on('input', '#aicv-skills-entries .aicv-skill-entry input', function() {
            var $entry = $(this).closest('.aicv-skill-entry');
            var index = $entry.data('entry-index');
            var $previewEntry = $('#preview-skills-entries .preview-skill-item[data-preview-for="skill-' + index + '"]');
            if ($previewEntry.length) {
                $previewEntry.text($entry.find('input[name$="[skill_name]"]').val() || '[Skill]');
            }
        });


        // --- Collect CV Data ---
        function collectCvData() { // Ensure this function is defined before being called by saveCvData
            var cvData = {
                personal_info: { // This structure is for the AICVB_META_PERSONAL_INFO array
                    full_name: $('#aicv_full_name').val(),
                    email: $('#aicv_email').val(),
                    phone: $('#aicv_phone').val(),
                    address: $('#aicv_address').val(),
                    website: $('#aicv_website').val(),
                },
                // These are direct meta keys as per PHP handler adjustment
                professional_summary: $('#aicv_summary').val(),
                aicv_selected_theme_class: $('#aicv_theme_select').val(),
                aicv_primary_color: $('#aicv_primary_color').val(),
                aicv_font_family: $('#aicv_font_family').val(),
                // Repeatable fields, expect arrays of objects
                experience: [],
                education: [],
                skills: []
            };

            // Collect Experience data
            $('#aicv-experience-entries .aicv-experience-entry').each(function() {
                var $entry = $(this);
                // Check if any field in the entry has a value to avoid saving empty objects
                var hasValue = false;
                $entry.find('input[type="text"], textarea').each(function() {
                    if ($(this).val().trim() !== '') {
                        hasValue = true;
                        return false; // break loop
                    }
                });

                if(hasValue) {
                    cvData.experience.push({
                        job_title: $entry.find('input[name$="[job_title]"]').val(),
                        company: $entry.find('input[name$="[company]"]').val(),
                        dates: $entry.find('input[name$="[dates]"]').val(),
                        description: $entry.find('textarea[name$="[description]"]').val()
                    });
                }
            });

            // Collect Education data
            $('#aicv-education-entries .aicv-education-entry').each(function() {
                var $entry = $(this);
                var hasValue = false;
                $entry.find('input[type="text"], textarea').each(function() {
                    if ($(this).val().trim() !== '') {
                        hasValue = true;
                        return false;
                    }
                });
                if(hasValue) {
                    cvData.education.push({
                        degree: $entry.find('input[name$="[degree]"]').val(),
                        institution: $entry.find('input[name$="[institution]"]').val(),
                        dates: $entry.find('input[name$="[dates]"]').val(),
                        description: $entry.find('textarea[name$="[description]"]').val()
                    });
                }
            });

            // Collect Skills data (array of objects: {skill_name: '...'})
             $('#aicv-skills-entries .aicv-skill-entry').each(function() {
                var $entry = $(this);
                var skillName = $entry.find('input[name$="[skill_name]"]').val().trim();
                if (skillName !== '') {
                    cvData.skills.push({
                        skill_name: skillName
                        // proficiency: $entry.find('input[name$="[proficiency]"]').val() // if proficiency is added
                    });
                }
            });

            return cvData;
        }

        // --- Save CV Data (AJAX) ---
        function saveCvData(isInitialSave = false, callback) { // Added callback
            var collectedData = collectCvData();
            var currentCvId = $cvIdField.val();

            if (!isInitialSave) { // Don't clear messages if it's an initial auto-save in background
                clearUserMessages();
            }
            $spinner.addClass('is-active');
            // $saveStatus.hide(); // Old system

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
                        $cvIdField.val(response.data.cv_id);
                        if (!isInitialSave) {
                           showUserMessage(response.data.message || 'CV Saved!', 'success');
                        } else {
                           // For initial save, success is handled by the callback (e.g. showing modal)
                           // console.log('Initial save successful for saveCvData. CV ID:', response.data.cv_id);
                        }
                        if (typeof callback === 'function') {
                            callback(true, response.data, response.data.message);
                        }
                    } else {
                        if (!isInitialSave) {
                            showUserMessage(response.data.message || aicvb_ajax_vars.error_messages.general_save, 'error');
                        }
                        if (typeof callback === 'function') {
                            callback(false, response.data, response.data.message);
                        }
                    }
                },
                error: function() {
                    var errorMsg = 'AJAX error: Could not connect to server for saving.';
                    if (!isInitialSave) {
                        showUserMessage(errorMsg, 'error');
                    }
                     if (typeof callback === 'function') {
                        callback(false, { message: errorMsg }, errorMsg);
                    }
                },
                complete: function() {
                    $spinner.removeClass('is-active');
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

        // Attach to a parent container that exists on page load for delegated events
        var $controlPanel = $('#aicv-control-panel');


        // Predefined Themes
        $controlPanel.on('change', '#aicv_theme_select', function() {
            var selectedThemeClass = $(this).val();
            $resumeSheet.removeClass (function (index, className) {
                return (className.match (/(^|\s)theme-\S+/g) || []).join(' ');
            });
            if (selectedThemeClass) {
                $resumeSheet.addClass(selectedThemeClass);
            }
        });

        // Primary Color
        $controlPanel.on('input change', '#aicv_primary_color', function() {
            $resumeSheet.css('--aicv-primary-color', $(this).val());
        });

        // Font Family
        $controlPanel.on('change', '#aicv_font_family', function() {
            $resumeSheet.css('--aicv-font-family', $(this).val());
        });

        // --- AI Assist for Specific Fields ---
        $controlPanel.on('click', '.aicv-generate-field-button', function() {
            var $button = $(this);
            var $spinner = $button.next('.aicv-field-spinner');
            var fieldType = $button.data('field-type');
            var targetFieldId = $button.data('target-field'); // For non-repeatable summary
            var $targetTextarea;
            var contextData = {};
            var currentText = '';

            if (fieldType === 'summary') {
                $targetTextarea = $('#' + targetFieldId);
                currentText = $targetTextarea.val();
                // Try to get job title from first experience entry for context
                var firstJobTitle = $('#aicv-experience-entries .aicv-experience-entry:first .aicv-exp-job-title').val();
                if (firstJobTitle && firstJobTitle.trim() !== '') {
                    contextData.job_title = firstJobTitle.trim();
                }
            } else if (fieldType === 'experience_description') {
                var $experienceEntry = $button.closest('.aicv-experience-entry');
                $targetTextarea = $experienceEntry.find('.aicv-exp-description');
                targetFieldId = $targetTextarea.attr('id'); // Ensure ID is set on these textareas if not already
                if (!targetFieldId) { // Fallback if ID isn't set (though it should be for repeatable fields)
                    // Create a unique ID if necessary - this part is tricky for dynamic fields
                    // For now, we assume the textarea can be uniquely identified or has an ID.
                    // The PHP handler expects 'target_field_id_attr' to be the actual ID.
                    // Let's ensure our cloning logic for experience entries sets unique IDs on textareas.
                    // For now, this alert indicates a gap:
                    // alert("Target textarea for experience description needs a unique ID.");
                    // return;
                }
                targetFieldId = $targetTextarea.attr('id'); // Re-fetch after potential ID assignment
                currentText = $targetTextarea.val();
                contextData.job_title = $experienceEntry.find('.aicv-exp-job-title').val();
                contextData.company = $experienceEntry.find('.aicv-exp-company').val();
            } else {
                console.error('Unknown field type for AI Assist:', fieldType);
                return;
            }

            if (!$targetTextarea || !$targetTextarea.length) {
                console.error('Could not find target textarea for AI Assist. Field Type:', fieldType, 'Target ID attempted:', targetFieldId);
                return;
            }
            // If targetFieldId is still undefined for experience (because dynamic ID assignment isn't robust yet),
            // we need a way to identify it. For now, we'll proceed if $targetTextarea is found.
            // The PHP response will echo back target_field_id_attr, which JS will use.
            // For experience, we pass the actual ID of the textarea (which should be made unique on creation).
            var effectiveTargetIdForAjax = $targetTextarea.attr('id');

            clearUserMessages();
            $spinner.addClass('is-active');
            $button.prop('disabled', true);

            $.ajax({
                url: aicvb_ajax_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'aicvb_generate_field_content',
                    nonce: aicvb_ajax_vars.generate_field_nonce,
                    cv_id: $cvIdField.val(),
                    field_type: fieldType,
                    context_data: contextData,
                    current_text: currentText,
                    target_field_id_attr: effectiveTargetIdForAjax
                },
                success: function(response) {
                    if (response.success) {
                        var $fieldToUpdate = $('#' + response.data.target_field_id_attr);
                        if ($fieldToUpdate.length) {
                            var currentVal = $fieldToUpdate.val();
                            var newText = response.data.generated_text;
                            if (fieldType === 'experience_description' && currentVal.trim().length > 0 && newText.trim().length > 0) {
                                $fieldToUpdate.val(currentVal + "\n" + newText).trigger('input');
                            } else {
                                $fieldToUpdate.val(newText).trigger('input');
                            }
                            showUserMessage('Content generated and applied!', 'success', 4000);
                            saveCvData();
                        } else {
                            console.error('Target field not found for ID:', response.data.target_field_id_attr);
                            showUserMessage('Error: Could not find target field to update.', 'error');
                        }
                    } else {
                        showUserMessage('AI Assist Error: ' + (response.data.message || 'Unknown error.'), 'error');
                    }
                },
                error: function() {
                    showUserMessage('AJAX error: Could not connect to server for AI Assist.', 'error');
                },
                complete: function() {
                    $spinner.removeClass('is-active');
                    $button.prop('disabled', false);
                }
            });
        });


        // --- Tab switching logic for control panel ---
        $controlPanel.on('click', '.aicv-tabs .aicv-tab-button', function() { // Ensured it's specific to tabs within control panel
            var tabId = $(this).data('tab');
            $controlPanel.find('.aicv-tabs .aicv-tab-button').removeClass('active');
            $('#aicv-control-panel .aicv-tab-pane').removeClass('active').hide();
            $(this).addClass('active');
            $('#aicv-tab-' + tabId).addClass('active').show();
        });

        if ($('#aicv-control-panel .aicv-tab-button.active[data-tab="content"]').length) {
            $('#aicv-tab-content').show();
            $('#aicv-tab-theme').hide();
        }

        // --- CV Tailoring Modal Logic ---
        var $tailoringModal = $('#aicv-tailoring-suggestions-modal');
        var $suggestionsContainer = $('#aicv-suggestions-container');
        var $tailoringSpinner = $('#aicv_tailoring_spinner');
        var currentCvDataForTailoring = null; // To store CV data before suggestions
        var suggestedCvDataFromAI = null; // To store AI suggestions

        $controlPanel.on('click', '#aicv_trigger_tailor_cv_button', function() {
            var jobDescriptionText = $('#aicv_job_description_for_tailoring').val().trim();
            if (!jobDescriptionText) {
                alert('Please paste a job description first.');
                $('#aicv_job_description_for_tailoring').focus();
                return;
            }

            currentCvDataForTailoring = collectCvData();
            clearUserMessages();
            $tailoringSpinner.addClass('is-active').show();
            $(this).prop('disabled', true);

            $.ajax({
                url: aicvb_ajax_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'aicvb_tailor_cv',
                    nonce: aicvb_ajax_vars.tailor_cv_nonce,
                    cv_id: $cvIdField.val(),
                    current_cv_data: currentCvDataForTailoring,
                    job_description: jobDescriptionText
                },
                success: function(response) {
                    if (response.success) {
                        suggestedCvDataFromAI = response.data.tailored_suggestions;
                        displayTailoringSuggestions();
                        $tailoringModal.show();
                        showUserMessage(response.data.message || 'Suggestions generated!', 'success', 3000);
                    } else {
                        showUserMessage('Error tailoring CV: ' + (response.data.message || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    showUserMessage('AJAX error: Could not connect to server for CV tailoring.', 'error');
                },
                complete: function() {
                    $tailoringSpinner.removeClass('is-active').hide();
                    $('#aicv_trigger_tailor_cv_button').prop('disabled', false);
                }
            });
        });

        function displayTailoringSuggestions() {
            $suggestionsContainer.empty(); // Clear previous suggestions

            if (!suggestedCvDataFromAI || !currentCvDataForTailoring) {
                $suggestionsContainer.html('<p>No suggestions available or error in data.</p>');
                return;
            }

            // Professional Summary
            if (suggestedCvDataFromAI.suggested_professional_summary) {
                $suggestionsContainer.append('<h4>Professional Summary</h4>');
                $suggestionsContainer.append('<div class="suggestion-group"><p><strong>Original:</strong></p><p class="original-text">' + escapeHtml(currentCvDataForTailoring.professional_summary || '') + '</p></div>');
                $suggestionsContainer.append('<div class="suggestion-group"><p><strong>Suggested:</strong></p><p class="suggested-text">' + escapeHtml(suggestedCvDataFromAI.suggested_professional_summary) + '</p></div>');
            }

            // Skills
            if (suggestedCvDataFromAI.suggested_skills && Array.isArray(suggestedCvDataFromAI.suggested_skills)) {
                $suggestionsContainer.append('<h4>Skills</h4>');
                var originalSkillsHtml = '<ul>';
                if(currentCvDataForTailoring.skills && currentCvDataForTailoring.skills.length > 0){
                    currentCvDataForTailoring.skills.forEach(function(skill) { originalSkillsHtml += '<li>' + escapeHtml(skill.skill_name) + '</li>'; });
                } else {
                    originalSkillsHtml += '<li>No skills listed.</li>';
                }
                originalSkillsHtml += '</ul>';
                $suggestionsContainer.append('<div class="suggestion-group"><p><strong>Original:</strong></p>' + originalSkillsHtml + '</div>');

                var suggestedSkillsHtml = '<ul>';
                suggestedCvDataFromAI.suggested_skills.forEach(function(skill) { suggestedSkillsHtml += '<li>' + escapeHtml(skill) + '</li>'; });
                suggestedSkillsHtml += '</ul>';
                $suggestionsContainer.append('<div class="suggestion-group"><p><strong>Suggested:</strong></p><div class="suggested-text">' + suggestedSkillsHtml + '</div></div>');
            }

            // Work Experience
            if (suggestedCvDataFromAI.suggested_work_experience && Array.isArray(suggestedCvDataFromAI.suggested_work_experience)) {
                 $suggestionsContainer.append('<h4>Work Experience</h4>');
                suggestedCvDataFromAI.suggested_work_experience.forEach(function(suggestedExp, index) {
                    // Find original experience to compare (simplistic match by original_job_title or index)
                    var originalExp = currentCvDataForTailoring.experience.find(function(exp) { return exp.job_title === suggestedExp.original_job_title; });
                     if(!originalExp && currentCvDataForTailoring.experience[index]) originalExp = currentCvDataForTailoring.experience[index]; // Fallback to index

                    $suggestionsContainer.append('<h5>' + escapeHtml(suggestedExp.original_job_title || ('Experience Item ' + (index + 1))) + '</h5>');
                    if (originalExp && originalExp.description) {
                        $suggestionsContainer.append('<div class="suggestion-group"><p><strong>Original Description:</strong></p><p class="original-text">' + escapeHtml(originalExp.description) + '</p></div>');
                    }
                    $suggestionsContainer.append('<div class="suggestion-group"><p><strong>Suggested Description:</strong></p><p class="suggested-text">' + escapeHtml(suggestedExp.revised_description) + '</p></div>');
                });
            }
        }

        function escapeHtml(unsafe) {
            if (typeof unsafe !== 'string') {
                if (unsafe === null || typeof unsafe === 'undefined') return '';
                try { unsafe = String(unsafe); } catch (e) { return ''; }
            }
            return unsafe
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }


        $tailoringModal.on('click', '#aicv_apply_suggestions_button', function() {
            if (!suggestedCvDataFromAI) {
                showUserMessage('No suggestions to apply.', 'info', 3000);
                return;
            }
            clearUserMessages();
            // Apply Summary
            if (suggestedCvDataFromAI.suggested_professional_summary) {
                $('#aicv_summary').val(suggestedCvDataFromAI.suggested_professional_summary).trigger('input');
            }

            // Apply Skills
            if (suggestedCvDataFromAI.suggested_skills && Array.isArray(suggestedCvDataFromAI.suggested_skills)) {
                var $skillsContainer = $('#aicv-skills-entries');
                var $firstSkillEntryTemplate = $skillsContainer.find('.aicv-skill-entry:first').clone(); // Keep a template
                $skillsContainer.empty();
                $('#preview-skills-entries').empty(); // Clear skill previews
                // skillEntryIndex = 0; // Reset index // Not needed if using length
                suggestedCvDataFromAI.suggested_skills.forEach(function(skillName, i) {
                    var $newEntry = $firstSkillEntryTemplate.clone();
                    $newEntry.attr('data-entry-index', i);
                    $newEntry.find('input[name$="[skill_name]"]').attr('name', 'aicv_skills[' + i + '][skill_name]').val(skillName).attr('id', 'aicv_skill_name_' + i);
                    $skillsContainer.append($newEntry);
                    $('#preview-skills-entries').append(generateSkillPreviewHtml(i, {skill_name: skillName}));
                    // $newEntry.find('input').trigger('input'); // Trigger preview update for the skill
                });
            }

            // Apply Work Experience
            if (suggestedCvDataFromAI.suggested_work_experience && Array.isArray(suggestedCvDataFromAI.suggested_work_experience)) {
                var $experienceContainer = $('#aicv-experience-entries');
                var $firstExperienceEntryTemplate = $experienceContainer.find('.aicv-experience-entry:first').clone();
                // For simplicity in "apply", we'll replace all experiences if suggestions exist.
                // A more complex diff/match could be done but is harder.
                if (suggestedCvDataFromAI.suggested_work_experience.length > 0) {
                    $experienceContainer.empty();
                    $('#preview-experience-entries').empty();
                    // experienceEntryIndex = 0; // Not needed
                }

                suggestedCvDataFromAI.suggested_work_experience.forEach(function(suggestedExp, i) {
                    var $newEntry = $firstExperienceEntryTemplate.clone();
                    $newEntry.attr('data-entry-index', i);
                    // Find original experience to get other details if needed, or use AI's suggestions primarily
                    var originalExpData = currentCvDataForTailoring.experience.find(function(exp) { return exp.job_title === suggestedExp.original_job_title; });
                     if(!originalExpData && currentCvDataForTailoring.experience[i]) originalExpData = currentCvDataForTailoring.experience[i];


                    var title = (originalExpData && suggestedExp.original_job_title) ? originalExpData.job_title : (suggestedExp.job_title || '[Job Title]');
                    var company = (originalExpData && suggestedExp.original_job_title) ? originalExpData.company : (suggestedExp.company || '[Company]');
                    var dates = (originalExpData && suggestedExp.original_job_title) ? originalExpData.dates : (suggestedExp.dates || '[Dates]');
                    var description = suggestedExp.revised_description;

                    $newEntry.find('.aicv-exp-job-title').attr('name', 'aicv_experience['+i+'][job_title]').val(title);
                    $newEntry.find('.aicv-exp-company').attr('name', 'aicv_experience['+i+'][company]').val(company);
                    $newEntry.find('.aicv-exp-dates').attr('name', 'aicv_experience['+i+'][dates]').val(dates);
                    $newEntry.find('.aicv-exp-description').attr('name', 'aicv_experience['+i+'][description]').attr('id', 'aicv_experience_description_' + i).val(description);
                    $experienceContainer.append($newEntry);
                    $('#preview-experience-entries').append(generateExperiencePreviewHtml(i, {job_title: title, company: company, dates: dates, description: description}));
                    // $newEntry.find('input, textarea').trigger('input'); // Trigger preview updates
                });
            }

            $tailoringModal.hide();
            // Trigger input on all changed fields for live preview (summary already done)
            // For skills and experience, direct manipulation of preview items is done above.
            // A general preview refresh function might be better for complex changes.
            showUserMessage('Suggestions applied and CV updated!', 'success');
            saveCvData(); // Save all applied changes
        });

        $tailoringModal.on('click', '#aicv_cancel_suggestions_button', function() {
            $tailoringModal.hide();
        });

        // --- PDF Generation ---
        $('#aicv_download_pdf_button').on('click', function() {
            var $button = $(this);
            var $spinner = $('#aicv-pdf-generating-spinner');
            var originalButtonText = $button.text();
            clearUserMessages();

            $button.text('Generating PDF...').prop('disabled', true);
            $spinner.addClass('is-active').show();

            const element = document.querySelector('.aicv-resume-sheet');
            if (!element) {
                showUserMessage('Error: Could not find resume content to print.', 'error');
                $button.text(originalButtonText).prop('disabled', false);
                $spinner.removeClass('is-active').hide();
                return;
            }

            var cvTitle = $('#preview_full_name').text().trim() || 'resume';
            var filename = cvTitle.replace(/[^a-z0-9_ \-]+/gi, '_').replace(/\s+/g, '_') + '.pdf';


            const opt = {
                margin:       [0.5, 0.25, 0.5, 0.25],
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  {
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    scrollX: 0,
                    scrollY: -window.scrollY,
                    windowWidth: element.scrollWidth,
                    windowHeight: element.scrollHeight
                },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' },
            };

            $('body').addClass('print-styles-active');

            if (typeof html2pdf === 'undefined') {
                showUserMessage('Error: PDF generation library (html2pdf) is not loaded. Cannot generate PDF.', 'error');
                $button.text(originalButtonText).prop('disabled', false);
                $spinner.removeClass('is-active').hide();
                $('body').removeClass('print-styles-active');
                return;
            }

            html2pdf().from(element).set(opt).save()
                .then(function() {
                    $button.text(originalButtonText).prop('disabled', false);
                    $spinner.removeClass('is-active').hide();
                    $('body').removeClass('print-styles-active');
                    showUserMessage('PDF generated and download initiated.', 'success', 10000);
                    // console.log('PDF generated and download initiated.');
                })
                .catch(function(error) {
                    console.error('Error generating PDF:', error);
                    showUserMessage('An error occurred while generating the PDF. Check console for details.', 'error');
                    $button.text(originalButtonText).prop('disabled', false);
                    $spinner.removeClass('is-active').hide();
                    $('body').removeClass('print-styles-active');
                });
        });

    });
