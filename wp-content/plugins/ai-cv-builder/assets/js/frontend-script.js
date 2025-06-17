(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('aicvb_ajax_vars:', aicvb_ajax_vars);
        var $notificationsArea = $('#aicv-user-notifications'); // Cache notification area
        var selectedTemplateId = null;
        var $cvIdField = $('#aicv_cv_id');
        // var $saveStatus = $('#aicv-save-status'); // No longer used
        var $spinner = $('#aicv-save-spinner');
        var $initialInputModal = $('#aicv-initial-input-modal');
        var $jobDescriptionInput = $('#aicv_job_description_input');
        var $generateInitialCvButton = $('#aicv_generate_initial_cv_button');
        var $skipInitialCvButton = $('#aicv_skip_initial_cv_button');
        var $modalLoadingIndicator = $initialInputModal.find('.aicv-loading-indicator');
        var $controlPanel = $('#aicv-control-panel');


        // --- User Message Functions ---
        function clearUserMessages() {
            $notificationsArea.empty();
        }

        function showUserMessage(message, type = 'error', duration = 7000) {
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

                console.log('[TemplateClick] Clicked template. Current cv_id value:', $cvIdField.val(), 'Selected template ID:', $(this).data('template-id'));
                if (!$cvIdField.val() || $cvIdField.val() === '0') {
                    console.log('[TemplateClick] Condition for new CV met. Calling saveCvData with isInitialSave = true.');
                    saveCvData(true, function(success, data, message) {
                        if (success && data && data.cv_id) { // Ensure data object and cv_id are present
                            $cvIdField.val(data.cv_id);
                            console.log('CV ID set after initial save:', data.cv_id);
                            $initialInputModal.show();
                        } else {
                            // Display a persistent error message
                            showUserMessage(message || 'Fatal Error: CV creation failed. The CV could not be saved to the server. Please reload the page and try again. If the problem persists, contact support.', 'error', 0);
                            // Prevent the #aicv-initial-input-modal from showing (already handled by not calling .show())
                            // Optional: Consider disabling further save actions or resetting UI further
                            // For now, the error message is the primary goal.
                            // $('#aicv-builder-main-ui').hide(); // Optionally hide main UI if it was shown too early
                            // $('#aicv-template-selection-ui').show(); // Optionally show template selection again
                        }
                    });
                } else {
                    console.log('[TemplateClick] Condition for new CV NOT met (likely existing CV). cv_id:', $cvIdField.val());
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

        var skillEntryIndex = 1;
        var educationEntryIndex = 1;
        var experienceEntryIndex = 1;

        function populateFormWithAIData(data) {
            if (!data) return;

            if (data.professional_summary) {
                $('#aicv_summary').val(data.professional_summary).trigger('input');
            }

            if (data.skills && Array.isArray(data.skills)) {
                var $skillsContainer = $('#aicv-skills-entries');
                var $firstSkillEntryTemplate = $skillsContainer.find('.aicv-skill-entry:first').clone();
                $skillsContainer.empty();
                $('#preview-skills-entries').empty();
                skillEntryIndex = 0;
                data.skills.forEach(function(skill, index) {
                    var $newEntry = $firstSkillEntryTemplate.clone();
                    $newEntry.attr('data-entry-index', index);
                    $newEntry.find('input[name$="[skill_name]"]')
                             .attr('name', 'aicv_skills[' + index + '][skill_name]')
                             .attr('id', 'aicv_skill_name_' + index)
                             .val(skill.skill_name);
                    $skillsContainer.append($newEntry);
                    $('#preview-skills-entries').append(generateSkillPreviewHtml(index, skill));
                    skillEntryIndex++;
                });
            }

            if (data.experience && Array.isArray(data.experience)) {
                var $experienceContainer = $('#aicv-experience-entries');
                var $firstExperienceEntryTemplate = $experienceContainer.find('.aicv-experience-entry:first').clone();
                $experienceContainer.empty();
                $('#preview-experience-entries').empty();
                experienceEntryIndex = 0;
                data.experience.forEach(function(exp, index) {
                    var $newEntry = $firstExperienceEntryTemplate.clone();
                    $newEntry.attr('data-entry-index', index);
                    $newEntry.find('.aicv-exp-job-title').attr('name', 'aicv_experience[' + index + '][job_title]').val(exp.job_title);
                    $newEntry.find('.aicv-exp-company').attr('name', 'aicv_experience[' + index + '][company]').val(exp.company);
                    $newEntry.find('.aicv-exp-dates').attr('name', 'aicv_experience[' + index + '][dates]').val(exp.dates);
                    $newEntry.find('.aicv-exp-description')
                             .attr('name', 'aicv_experience[' + index + '][description]')
                             .attr('id', 'aicv_experience_description_' + index)
                             .val(exp.description);
                    $experienceContainer.append($newEntry);
                    $('#preview-experience-entries').append(generateExperiencePreviewHtml(index, exp));
                    experienceEntryIndex++;
                });
            }
            saveCvData();
        }

        // --- Live Preview Updates ---
        $controlPanel.on('input', '#aicv_full_name', function() { $('#preview_full_name').text($(this).val() || '[Full Name]'); });
        $controlPanel.on('input', '#aicv_email', function() { $('#preview_email').text($(this).val() || '[Email]'); });
        $controlPanel.on('input', '#aicv_phone', function() { $('#preview_phone').text($(this).val() || '[Phone]'); });
        $controlPanel.on('input', '#aicv_address', function() { $('#preview_address').text($(this).val() || '[Address]'); });
        $controlPanel.on('input', '#aicv_website', function() { $('#preview_website').text($(this).val() || '[Website/LinkedIn]'); });
        $controlPanel.on('input', '#aicv_summary', function() { $('#preview_summary_content').text($(this).val() || 'Your professional summary here...'); });

        function generateExperiencePreviewHtml(index, data = {}) {
            var title = escapeHtml(data.job_title || '[Job Title]');
            var company = escapeHtml(data.company || '[Company]');
            var dates = escapeHtml(data.dates || '[Dates]');
            var description = escapeHtml(data.description || '[Description placeholder]').replace(/\n/g, '<br>');
            return `
                <div class="preview-experience-item" data-preview-for="exp-${index}">
                    <h4 class="preview-job-title">${title}</h4>
                    <p class="preview-company-dates"><span class="preview-company">${company}</span> | <span class="preview-dates">${dates}</span></p>
                    <div class="preview-description">${description}</div>
                </div>`;
        }
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
        function generateSkillPreviewHtml(index, data = {}) {
            var skillName = escapeHtml(data.skill_name || '[Skill]');
            return `<li class="preview-skill-item" data-preview-for="skill-${index}">${skillName}</li>`;
        }

        $controlPanel.on('input', '#aicv-experience-entries .aicv-experience-entry input, #aicv-experience-entries .aicv-experience-entry textarea', function() {
            var $entry = $(this).closest('.aicv-experience-entry');
            var index = $entry.data('entry-index');
            var $previewEntry = $('#preview-experience-entries .preview-experience-item[data-preview-for="exp-' + index + '"]');
            if ($previewEntry.length) {
                $previewEntry.find('.preview-job-title').text($entry.find('.aicv-exp-job-title').val() || '[Job Title]');
                $previewEntry.find('.preview-company').text($entry.find('.aicv-exp-company').val() || '[Company]');
                $previewEntry.find('.preview-dates').text($entry.find('.aicv-exp-dates').val() || '[Dates]');
                $previewEntry.find('.preview-description').html(escapeHtml($entry.find('.aicv-exp-description').val() || '[Description placeholder]').replace(/\n/g, '<br>'));
            }
        });
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
        $controlPanel.on('input', '#aicv-skills-entries .aicv-skill-entry input', function() {
            var $entry = $(this).closest('.aicv-skill-entry');
            var index = $entry.data('entry-index');
            var $previewEntry = $('#preview-skills-entries .preview-skill-item[data-preview-for="skill-' + index + '"]');
            if ($previewEntry.length) {
                $previewEntry.text($entry.find('input[name$="[skill_name]"]').val() || '[Skill]');
            }
        });

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
                aicv_selected_theme_class: $('#aicv_theme_select').val(),
                aicv_primary_color: $('#aicv_primary_color').val(),
                aicv_font_family: $('#aicv_font_family').val(),
                experience: [],
                education: [],
                skills: []
            };

            $('#aicv-experience-entries .aicv-experience-entry').each(function() {
                var $entry = $(this);
                var hasValue = false;
                $entry.find('input[type="text"], textarea').each(function() {
                    if ($(this).val().trim() !== '') { hasValue = true; return false; }
                });
                if(hasValue) {
                    cvData.experience.push({
                        job_title: $entry.find('.aicv-exp-job-title').val(),
                        company: $entry.find('.aicv-exp-company').val(),
                        dates: $entry.find('.aicv-exp-dates').val(),
                        description: $entry.find('.aicv-exp-description').val()
                    });
                }
            });

            $('#aicv-education-entries .aicv-education-entry').each(function() {
                var $entry = $(this);
                var hasValue = false;
                $entry.find('input[type="text"], textarea').each(function() {
                    if ($(this).val().trim() !== '') { hasValue = true; return false; }
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

             $('#aicv-skills-entries .aicv-skill-entry').each(function() {
                var $entry = $(this);
                var skillName = $entry.find('input[name$="[skill_name]"]').val().trim();
                if (skillName !== '') {
                    cvData.skills.push({ skill_name: skillName });
                }
            });
            return cvData;
        }

        function saveCvData(isInitialSave = false, callback) {
            console.log('[saveCvData] Called. isInitialSave:', isInitialSave, 'currentCvId:', $cvIdField.val(), 'selectedTemplateId:', selectedTemplateId);
            var collectedData = collectCvData();
            var currentCvId = $cvIdField.val();

            if (!isInitialSave) {
                clearUserMessages();
            }
            $spinner.addClass('is-active');

            var ajaxData = {
                action: 'aicvb_save_cv_data',
                nonce: aicvb_ajax_vars.save_cv_nonce,
                cv_id: currentCvId,
                cv_data: collectedData, // Default to full data
                template_id: selectedTemplateId
            };

            if (isInitialSave) {
                ajaxData.cv_data = {
                    personal_info: { full_name: '' },
                    professional_summary: '' // Set to empty string for consistency
                };
                console.log('[saveCvData] isInitialSave=true. ajaxData.cv_data is now minimal:', ajaxData.cv_data);
                // ajaxData.is_initial_save_test = 'yes'; // Removed as per requirement
            }

            console.log('[saveCvData] Final ajaxData being sent:', JSON.parse(JSON.stringify(ajaxData)));
            $.ajax({
                url: aicvb_ajax_vars.ajax_url,
                type: 'POST',
                data: ajaxData, // Use the potentially modified ajaxData
                success: function(response) {
                    if (response.success) {
                        $cvIdField.val(response.data.cv_id);
                        if (!isInitialSave) {
                           showUserMessage(response.data.message || 'CV Saved!', 'success');
                        } else {
                           // console.log('Initial save successful for saveCvData. CV ID:', response.data.cv_id); // Removed
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

        function setupRepeatableSection(section) {
            var $container = $('#aicv-' + section + '-entries');
            var $previewContainer = $('#preview-' + section + '-entries');
            var entryClass = '.aicv-' + section + '-entry';
            var previewItemClass = '.preview-' + section + '-item';
            var firstEntryTemplate = $container.find(entryClass + ':first').clone(true);
            var generatePreviewHtml;
            var idPrefix;
            var fieldSelectors = {}; // To map field type to its selector within an entry

            if (section === 'experience') {
                generatePreviewHtml = generateExperiencePreviewHtml;
                idPrefix = 'aicv_experience_';
                fieldSelectors = {
                    job_title: '.aicv-exp-job-title',
                    company: '.aicv-exp-company',
                    dates: '.aicv-exp-dates',
                    description: '.aicv-exp-description'
                };
            } else if (section === 'education') {
                generatePreviewHtml = generateEducationPreviewHtml;
                idPrefix = 'aicv_education_';
                 fieldSelectors = {
                    degree: 'input[name*="[degree]"]',
                    institution: 'input[name*="[institution]"]',
                    dates: 'input[name*="[dates]"]',
                    description: 'textarea[name*="[description]"]'
                };
            } else if (section === 'skills') {
                generatePreviewHtml = generateSkillPreviewHtml;
                idPrefix = 'aicv_skill_';
                fieldSelectors = {
                    skill_name: 'input[name*="[skill_name]"]'
                };
            }

            function updateFieldAttributes($element, newIndex) {
                var currentName = $element.attr('name');
                if (currentName) {
                    $element.attr('name', currentName.replace(/\[\d+\]/, '[' + newIndex + ']'));
                }
                var currentId = $element.attr('id');
                if (currentId) {
                    // More robust ID replacement, assuming format like prefix_0_field or prefix_0
                    var newId = currentId.replace(/_\d+(_\w+)?$/, '_' + newIndex + (currentId.match(/(_\w+)$/) ? currentId.match(/(_\w+)$/)[0] : ''));
                    $element.attr('id', newId);
                }
            }

            $container.find(entryClass).each(function(index){
                $(this).attr('data-entry-index', index);
                $(this).find('input, textarea').each(function(){
                    updateFieldAttributes($(this), index);
                });
                 // Add initial preview items if form entries exist on page load (e.g. if loading saved data)
                if ($previewContainer.find('[data-preview-for="' + section.substring(0,3) + '-' + index + '"]').length === 0) {
                    var initialData = {};
                    for(var key in fieldSelectors){
                        initialData[key] = $(this).find(fieldSelectors[key]).val();
                    }
                    // Only add if it's not the very first template entry AND it has some value
                    var hasValue = false;
                    for(var k in initialData) { if(initialData[k] && initialData[k].trim() !== '') { hasValue=true; break;} }

                    if(index > 0 || hasValue) { // Avoid duplicating placeholder for the first blank entry if it is truly blank
                       $previewContainer.append(generatePreviewHtml(index, initialData));
                    } else if (index === 0 && !hasValue && $previewContainer.children().length === 0) {
                        // For the very first, blank entry, ensure its preview placeholder is there
                        // This might be redundant if the static HTML already has one.
                        // $previewContainer.append(generatePreviewHtml(0));
                    }
                }
            });


            $('#aicv-add-' + section).on('click', function() {
                var newIndex = $container.find(entryClass).length;
                var $newEntry = firstEntryTemplate.clone(true);
                $newEntry.attr('data-entry-index', newIndex);
                $newEntry.find('input, textarea').each(function() {
                    $(this).val(''); // Clear values
                    updateFieldAttributes($(this), newIndex);
                });
                $container.append($newEntry);
                $previewContainer.append(generatePreviewHtml(newIndex));
                 // Ensure the new entry's fields are properly initialized for live preview
                var initialData = {};
                for(var key in fieldSelectors){ initialData[key] = ''; } // empty data for placeholder text
                var $newPreviewEntry = $previewContainer.find(previewItemClass + '[data-preview-for="' + section.substring(0,3) + '-' + newIndex + '"]');
                if(section === 'experience' && $newPreviewEntry.length) {
                    $newPreviewEntry.find('.preview-job-title').text('[Job Title]');
                    $newPreviewEntry.find('.preview-company').text('[Company]');
                    $newPreviewEntry.find('.preview-dates').text('[Dates]');
                    $newPreviewEntry.find('.preview-description').html('[Description placeholder]');
                } // Add similar for education and skills if needed for more complex placeholders
            });

            $container.on('click', '.aicv-delete-entry', function() {
                if ($container.find(entryClass).length > 1) {
                    var $entryToRemove = $(this).closest(entryClass);
                    var indexToRemove = $entryToRemove.data('entry-index');
                    $entryToRemove.remove();
                    $previewContainer.find(previewItemClass + '[data-preview-for="' + section.substring(0,3) + '-' + indexToRemove + '"]').remove();

                    $container.find(entryClass).each(function(i) {
                        $(this).attr('data-entry-index', i);
                        $(this).find('input, textarea').each(function() {
                            updateFieldAttributes($(this), i);
                        });
                    });
                    $previewContainer.find(previewItemClass).each(function(i) {
                        $(this).attr('data-preview-for', section.substring(0,3) + '-' + i);
                    });

                } else {
                    showUserMessage('At least one entry is required. You can clear the fields if you wish to remove it.', 'info', 4000);
                }
            });
        }

        setupRepeatableSection('experience');
        setupRepeatableSection('education');
        setupRepeatableSection('skills');

        // --- Theme Customization JS ---
        var $resumeSheet = $('#aicv-live-preview .aicv-resume-sheet');
        $controlPanel.on('change', '#aicv_theme_select', function() {
            var selectedThemeClass = $(this).val();
            $resumeSheet.removeClass (function (index, className) {
                return (className.match (/(^|\s)theme-\S+/g) || []).join(' ');
            });
            if (selectedThemeClass) {
                $resumeSheet.addClass(selectedThemeClass);
            }
        });
        $controlPanel.on('input change', '#aicv_primary_color', function() {
            $resumeSheet.css('--aicv-primary-color', $(this).val());
        });
        $controlPanel.on('change', '#aicv_font_family', function() {
            $resumeSheet.css('--aicv-font-family', $(this).val());
        });

        // --- AI Assist for Specific Fields ---
        $controlPanel.on('click', '.aicv-generate-field-button', function() {
            var $button = $(this);
            var $spinner = $button.next('.aicv-field-spinner');
            var fieldType = $button.data('field-type');
            var targetFieldIdFromData = $button.data('target-field');
            var $targetTextarea;
            var contextData = {};
            var currentText = '';
            var effectiveTargetIdForAjax;

            if (fieldType === 'summary') {
                $targetTextarea = $('#' + targetFieldIdFromData);
                effectiveTargetIdForAjax = targetFieldIdFromData;
                currentText = $targetTextarea.val();
                var firstJobTitle = $('#aicv-experience-entries .aicv-experience-entry:first .aicv-exp-job-title').val();
                if (firstJobTitle && firstJobTitle.trim() !== '') {
                    contextData.job_title = firstJobTitle.trim();
                }
            } else if (fieldType === 'experience_description') {
                var $experienceEntry = $button.closest('.aicv-experience-entry');
                $targetTextarea = $experienceEntry.find('.aicv-exp-description');
                effectiveTargetIdForAjax = $targetTextarea.attr('id');
                currentText = $targetTextarea.val();
                contextData.job_title = $experienceEntry.find('.aicv-exp-job-title').val();
                contextData.company = $experienceEntry.find('.aicv-exp-company').val();
            } else {
                console.error('Unknown field type for AI Assist:', fieldType);
                return;
            }

            if (!$targetTextarea || !$targetTextarea.length) {
                console.error('Could not find target textarea for AI Assist. Field Type:', fieldType);
                return;
            }
            if (!effectiveTargetIdForAjax && fieldType === 'experience_description') { // Check if ID is missing for repeatable
                 console.error('Target textarea for experience description needs a unique ID for AI assist.');
                 showUserMessage('Error: Target field for AI assist could not be identified. Please ensure entries are correctly indexed (try deleting and re-adding the item).', 'error');
                 return;
            }

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

        $controlPanel.on('click', '.aicv-tabs .aicv-tab-button', function() {
            var tabId = $(this).data('tab');
            $controlPanel.find('.aicv-tabs .aicv-tab-button').removeClass('active');
            $controlPanel.find('.aicv-tab-pane').removeClass('active').hide();
            $(this).addClass('active');
            $controlPanel.find('#aicv-tab-' + tabId).addClass('active').show();
        });

        if ($controlPanel.find('.aicv-tabs .aicv-tab-button.active[data-tab="content"]').length) {
            $controlPanel.find('#aicv-tab-content').show();
            $controlPanel.find('#aicv-tab-theme').hide();
        }

        var $tailoringModal = $('#aicv-tailoring-suggestions-modal');
        var $suggestionsContainer = $('#aicv-suggestions-container');
        var $tailoringSpinner = $('#aicv_tailoring_spinner');
        var currentCvDataForTailoring = null;
        var suggestedCvDataFromAI = null;

        $controlPanel.on('click', '#aicv_trigger_tailor_cv_button', function() {
            var jobDescriptionText = $('#aicv_job_description_for_tailoring').val().trim();
            if (!jobDescriptionText) {
                showUserMessage('Please paste a job description first.', 'info', 3000);
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
            $suggestionsContainer.empty();

            if (!suggestedCvDataFromAI || !currentCvDataForTailoring) {
                $suggestionsContainer.html('<p>No suggestions available or error in data.</p>');
                return;
            }

            if (suggestedCvDataFromAI.suggested_professional_summary) {
                $suggestionsContainer.append('<h4>Professional Summary</h4>');
                $suggestionsContainer.append('<div class="suggestion-group"><p><strong>Original:</strong></p><p class="original-text">' + escapeHtml(currentCvDataForTailoring.professional_summary || '') + '</p></div>');
                $suggestionsContainer.append('<div class="suggestion-group"><p><strong>Suggested:</strong></p><p class="suggested-text">' + escapeHtml(suggestedCvDataFromAI.suggested_professional_summary) + '</p></div>');
            }

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

            if (suggestedCvDataFromAI.suggested_work_experience && Array.isArray(suggestedCvDataFromAI.suggested_work_experience)) {
                 $suggestionsContainer.append('<h4>Work Experience</h4>');
                suggestedCvDataFromAI.suggested_work_experience.forEach(function(suggestedExp, index) {
                    var originalExp = currentCvDataForTailoring.experience.find(function(exp) { return exp.job_title === suggestedExp.original_job_title; });
                     if(!originalExp && currentCvDataForTailoring.experience[index]) originalExp = currentCvDataForTailoring.experience[index];

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
            if (suggestedCvDataFromAI.suggested_professional_summary) {
                $('#aicv_summary').val(suggestedCvDataFromAI.suggested_professional_summary).trigger('input');
            }

            if (suggestedCvDataFromAI.suggested_skills && Array.isArray(suggestedCvDataFromAI.suggested_skills)) {
                var $skillsContainer = $('#aicv-skills-entries');
                var $firstSkillEntryTemplate = $skillsContainer.find('.aicv-skill-entry:first').clone();
                $skillsContainer.empty();
                $('#preview-skills-entries').empty();
                suggestedCvDataFromAI.suggested_skills.forEach(function(skillName, i) {
                    var $newEntry = $firstSkillEntryTemplate.clone();
                    $newEntry.attr('data-entry-index', i);
                    $newEntry.find('input[name$="[skill_name]"]').attr('name', 'aicv_skills[' + i + '][skill_name]').val(skillName).attr('id', 'aicv_skill_name_' + i);
                    $skillsContainer.append($newEntry);
                    $('#preview-skills-entries').append(generateSkillPreviewHtml(i, {skill_name: skillName}));
                });
            }

            if (suggestedCvDataFromAI.suggested_work_experience && Array.isArray(suggestedCvDataFromAI.suggested_work_experience)) {
                var $experienceContainer = $('#aicv-experience-entries');
                var $firstExperienceEntryTemplate = $experienceContainer.find('.aicv-experience-entry:first').clone();
                if (suggestedCvDataFromAI.suggested_work_experience.length > 0) {
                    $experienceContainer.empty();
                    $('#preview-experience-entries').empty();
                }

                suggestedCvDataFromAI.suggested_work_experience.forEach(function(suggestedExp, i) {
                    var $newEntry = $firstExperienceEntryTemplate.clone();
                    $newEntry.attr('data-entry-index', i);
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
                });
            }

            $tailoringModal.hide();
            showUserMessage('Suggestions applied and CV updated!', 'success');
            saveCvData();
        });

        $tailoringModal.on('click', '#aicv_cancel_suggestions_button', function() {
            $tailoringModal.hide();
        });

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
})(jQuery);
