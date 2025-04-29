// --- Helper Function for Download ---
// Moved outside the jQuery wrapper to ensure it's globally accessible
function downloadData(data, filename, format) {
    var blob;
    var contentType = format === 'csv' ? 'text/csv' : 'application/json';
    var content = format === 'csv' ? data : JSON.stringify(data, null, 2);

    try {
        blob = new Blob([content], { type: contentType + ';charset=utf-8;' });
    } catch (e) { // Handle IE specific Blob constructor
        window.BlobBuilder = window.BlobBuilder || window.WebKitBlobBuilder || window.MozBlobBuilder || window.MSBlobBuilder;
        if (window.BlobBuilder) {
            var bb = new BlobBuilder();
            bb.append(content);
            blob = bb.getBlob(contentType);
        } else {
            // Attempt to use localized string, fallback if not available yet
            var errorMsg = (typeof dmtp_localized_data !== 'undefined' && dmtp_localized_data.i18n && dmtp_localized_data.i18n.downloadError) 
                            ? dmtp_localized_data.i18n.downloadError 
                            : 'Your browser does not support the necessary features for downloading.';
            alert(errorMsg);
            return;
        }
    }
    
    if (navigator.msSaveBlob) { // IE 10+
        navigator.msSaveBlob(blob, filename);
    } else {
        var link = document.createElement("a");
        if (link.download !== undefined) { // Feature detection
            var url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        } else {
             // Fallback for browsers that don't support download attribute
             var popup = window.open("", "_blank");
             if (popup) {
                 popup.document.write("<pre>" + content + "</pre>");
                 popup.document.close();
             } else {
                 alert('Popup blocked. Please allow popups for this site to download the file.');
             }
        }
    }
}

/**
 * Admin scripts for the Delivery Manager Tracking Plugin.
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // --- Export Handlers ---

        // Export All Sprints
        $('#dmtp-export-all-sprints-json, #dmtp-export-all-sprints-csv').on('click', function(e) {
            e.preventDefault();
            var format = $(this).attr('id').includes('json') ? 'json' : 'csv';
            exportData('all_sprints', format);
        });
        
        // Export Developer Data
        $('.dmtp-export-developer').on('click', function(e) {
            e.preventDefault();
            var format = $(this).data('format');
            var devName = $(this).data('developer-name');
            // Store original text before disabling
            $(this).data('original-text', $(this).text()); 
            exportData('developer', format, { developer_name: devName }, e);
        });

        // --- Helper Function for Export (Uses global downloadData) ---
        function exportData(exportType, format, additionalData, event) {
            var data = {
                action: 'dmtp_export_data',
                nonce: dmtp_localized_data.nonce,
                export_type: exportType,
                format: format,
            };
            
            if (additionalData) {
                $.extend(data, additionalData);
            }

            console.log('Exporting:', data);
            
            var $button = event ? $(event.target).prop('disabled', true).text(dmtp_localized_data.i18n.exporting) : null;
            
            $.ajax({
                url: dmtp_localized_data.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        console.log('Export successful:', response.data);
                        // Trigger file download using the globally defined function
                        downloadData(response.data.data, exportType + '_export.' + format, format);
                    } else {
                        console.error('Error exporting data:', response.data.message);
                        alert(dmtp_localized_data.i18n.exportError + ' ' + response.data.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    alert(dmtp_localized_data.i18n.ajaxError + ' ' + textStatus);
                },
                complete: function() {
                    if ($button) {
                        $button.prop('disabled', false).text($button.data('original-text')); // Restore original text
                    }
                }
            });
        }
        
        // --- Helper Function for Download --- IS NOW DEFINED GLOBALLY ABOVE ---

        // --- Other Admin Logic ---
        // Initialize charts (e.g., using Chart.js) if needed
        // Add event listeners for other filters, buttons, etc.

        // --- Chart Initialization ---
        function initCharts() {
            // Sprint Burndown Chart
            var burndownCtx = document.getElementById('dmtp-burndown-chart');
            if (burndownCtx && typeof dmtp_chart_data !== 'undefined' && dmtp_chart_data.burndown) {
                new Chart(burndownCtx, {
                    type: 'line',
                    data: {
                        labels: dmtp_chart_data.burndown.map(item => item.date),
                        datasets: [
                            {
                                label: dmtp_localized_data.i18n.idealLine,
                                data: dmtp_chart_data.burndown.map(item => item.ideal_remaining),
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 2,
                                fill: false,
                                tension: 0.1
                            },
                            {
                                label: dmtp_localized_data.i18n.actualLine,
                                data: dmtp_chart_data.burndown.map(item => item.actual_remaining),
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 2,
                                fill: false,
                                tension: 0.1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: dmtp_localized_data.i18n.sprintBurndownTitle + (dmtp_chart_data.sprintTitle ? ': ' + dmtp_chart_data.sprintTitle : '')
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Remaining Story Points' // This should also be translatable if needed
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date' // This should also be translatable if needed
                                }
                            }
                        }
                    }
                });
            }

            // Velocity Trend Chart
            var velocityCtx = document.getElementById('dmtp-velocity-chart');
            if (velocityCtx && typeof dmtp_chart_data !== 'undefined' && dmtp_chart_data.velocity) {
                new Chart(velocityCtx, {
                    type: 'bar',
                    data: {
                        labels: dmtp_chart_data.velocity.map(item => item.sprint_name),
                        datasets: [
                            {
                                label: dmtp_localized_data.i18n.committedPoints,
                                data: dmtp_chart_data.velocity.map(item => item.committed_points),
                                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            },
                            {
                                label: dmtp_localized_data.i18n.deliveredPoints,
                                data: dmtp_chart_data.velocity.map(item => item.delivered_points),
                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: dmtp_localized_data.i18n.velocityTrendTitle.replace('%d', dmtp_chart_data.velocity.length)
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Story Points' // Translatable
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: dmtp_localized_data.i18n.sprint // Translatable
                                }
                            }
                        }
                    }
                });
            }
        }

        // --- Initialization on page load ---
        // Store original button text for export buttons
        $('.dmtp-export button, .dmtp-export-developer').each(function(){
            $(this).data('original-text', $(this).text());
        });
        
        initCharts();
        
        // --- Dynamic Developer Dropdown for Individual Tracker (AJAX based) ---
        var $indTeamSelect = $('#dmtp_team_name');
        var $indDeveloperSelect = $('#dmtp_developer_name');
        if ($indTeamSelect.length) { 
            var initialIndDevValue = $indDeveloperSelect.val(); 
            function populateIndDevelopers(selectedTeam) {
                if (selectedTeam) {
                    $indDeveloperSelect.prop('disabled', true).html('<option value="">' + dmtp_localized_data.i18n.loading + '</option>');
                    $.ajax({
                        url: dmtp_localized_data.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'dmtp_get_developers_for_team',
                            nonce: dmtp_localized_data.nonce,
                            team_name: selectedTeam
                        },
                        success: function(response) {
                            $indDeveloperSelect.prop('disabled', false).empty();
                            $indDeveloperSelect.append('<option value="">-- Select Developer --</option>');
                            if (response.success && response.data.developers && response.data.developers.length > 0) {
                                $.each(response.data.developers, function(index, developer) {
                                    var option = '<option value="' + developer.value + '">' + developer.label + '</option>';
                                    $indDeveloperSelect.append(option);
                                });
                                if (initialIndDevValue && $indDeveloperSelect.find('option[value="' + initialIndDevValue + '"]').length > 0) {
                                    $indDeveloperSelect.val(initialIndDevValue);
                                }
                                initialIndDevValue = null;
                            } else if (response.success) {
                                $indDeveloperSelect.append('<option value="" disabled>No developers found for this team</option>');
                            } else {
                                console.error('Error fetching developers:', response.data.message);
                                $indDeveloperSelect.prop('disabled', true).html('<option value="">Error loading developers</option>');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('AJAX Error fetching developers:', textStatus, errorThrown);
                            $indDeveloperSelect.prop('disabled', true).html('<option value="">AJAX Error</option>');
                        }
                    });
                } else {
                    $indDeveloperSelect.prop('disabled', true).html('<option value="">-- Select Team First --</option>');
                }
            }
            $indTeamSelect.on('change', function() {
                initialIndDevValue = null;
                populateIndDevelopers($(this).val());
            });
            if ($indTeamSelect.val()) {
                populateIndDevelopers($indTeamSelect.val());
            }
        }
        
        // --- Dynamic Developer Dropdown for Hotfix Tracker ---
        var $hotfixTeamSelect = $('#hotfix_team_name');
        var $hotfixDeveloperSelect = $('#hotfix_developer_name');
        if ($hotfixTeamSelect.length) { // Only run if Hotfix Tracker elements exist
            var initialHotfixDevValue = $hotfixDeveloperSelect.val();

            function populateHotfixDevelopers(selectedTeam) {
                if (selectedTeam) {
                    $hotfixDeveloperSelect.prop('disabled', true).html('<option value="">' + dmtp_localized_data.i18n.loading + '</option>');

                    $.ajax({
                        url: dmtp_localized_data.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'dmtp_get_developers_for_team',
                            nonce: dmtp_localized_data.nonce,
                            team_name: selectedTeam
                        },
                        success: function(response) {
                            $hotfixDeveloperSelect.prop('disabled', false).empty();
                            $hotfixDeveloperSelect.append('<option value="">-- All Developers --</option>'); // Allow "All Developers" for hotfix filter
                            
                            if (response.success && response.data.developers && response.data.developers.length > 0) {
                                $.each(response.data.developers, function(index, developer) {
                                    var option = '<option value="' + developer.value + '">' + developer.label + '</option>';
                                    $hotfixDeveloperSelect.append(option);
                                });
                                // Re-select initial value if it exists
                                if (initialHotfixDevValue && $hotfixDeveloperSelect.find('option[value="' + initialHotfixDevValue + '"]').length > 0) {
                                    $hotfixDeveloperSelect.val(initialHotfixDevValue);
                                }
                                initialHotfixDevValue = null; 
                            } else if (response.success) {
                                 // No developers found, but still allow "All Developers"
                                $hotfixDeveloperSelect.append('<option value="" disabled>No specific developers found for this team</option>');
                            } else {
                                console.error('Error fetching hotfix developers:', response.data.message);
                                $hotfixDeveloperSelect.prop('disabled', true).html('<option value="">Error loading developers</option>');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('AJAX Error fetching hotfix developers:', textStatus, errorThrown);
                            $hotfixDeveloperSelect.prop('disabled', true).html('<option value="">AJAX Error</option>');
                        }
                    });
                } else {
                    // No team selected, disable and reset developer dropdown
                    $hotfixDeveloperSelect.prop('disabled', true).html('<option value="">-- Select Team First --</option>');
                }
            }

            // Event listener for hotfix team selection change
            $hotfixTeamSelect.on('change', function() {
                initialHotfixDevValue = null;
                populateHotfixDevelopers($(this).val());
            });

            // Trigger on page load if a team is already selected for hotfix tracker
            if ($hotfixTeamSelect.val()) {
                 populateHotfixDevelopers($hotfixTeamSelect.val());
            }
        }

        // --- Dynamic Sprint Dropdown for Hotfix Tracker Filter ---
        var $hotfixTeamFilter = $('#hotfix_team_filter');
        var $sprintFilterSelect = $('#sprint_id'); // The sprint dropdown in the hotfix filter

        if ($hotfixTeamFilter.length) {
            // Function to populate sprints based on selected team
            function populateSprintsForFilter(selectedTeam) {
                if (selectedTeam) {
                    $sprintFilterSelect.prop('disabled', true).html('<option value="">Loading Sprints...</option>');

                    $.ajax({
                        url: dmtp_localized_data.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'dmtp_get_sprints_for_team_filter', // Our new AJAX action
                            nonce: dmtp_localized_data.nonce,
                            team_name: selectedTeam
                        },
                        success: function(response) {
                            $sprintFilterSelect.prop('disabled', false).empty(); // Enable and clear
                            $sprintFilterSelect.append('<option value="">-- Select Sprint --</option>');
                            
                            if (response.success && response.data.sprints && response.data.sprints.length > 0) {
                                $.each(response.data.sprints, function(index, sprint) {
                                    var option = '<option value="' + sprint.id + '">' + sprint.title + '</option>';
                                    $sprintFilterSelect.append(option);
                                });
                                // If a sprint was already selected (e.g. from GET param), try to re-select it
                                var currentSprintId = new URLSearchParams(window.location.search).get('sprint_id');
                                if (currentSprintId && $sprintFilterSelect.find('option[value="' + currentSprintId + '"]').length > 0) {
                                     $sprintFilterSelect.val(currentSprintId);
                                }
                            } else if (response.success) {
                                // No sprints found for this team
                                $sprintFilterSelect.append('<option value="" disabled>No sprints found for this team</option>');
                            } else {
                                console.error('Error fetching sprints:', response.data.message);
                                $sprintFilterSelect.prop('disabled', true).html('<option value="">Error loading sprints</option>');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('AJAX Error fetching sprints:', textStatus, errorThrown);
                            $sprintFilterSelect.prop('disabled', true).html('<option value="">AJAX Error</option>');
                        }
                    });
                } else {
                    // No team selected, disable and reset sprint dropdown
                    $sprintFilterSelect.prop('disabled', true).html('<option value="">-- Select Team First --</option>');
                }
            }

            // Event listener for team filter change
            $hotfixTeamFilter.on('change', function() {
                populateSprintsForFilter($(this).val());
            });

            // Trigger on page load if a team is already selected in the filter
            if ($hotfixTeamFilter.val()) {
                 populateSprintsForFilter($hotfixTeamFilter.val());
            }
        }

        // --- ACF Dependent Logic --- 
        // Only run if acf object exists AND is ready
        if (typeof acf !== 'undefined') {
            acf.addAction( 'ready', function() {
                // --- Temporarily Commented Out for Debugging ---
                
                // --- Auto-populate Member Performance Repeater --- 
                if (typeof dmtp_localized_data !== 'undefined' && dmtp_localized_data.teamsData) {
                    const teamsCheckboxKey_perf = dmtp_localized_data.fieldKeys.teamsCheckbox; // Use unique var name suffix
                    const performanceRepeaterKey = dmtp_localized_data.fieldKeys.performanceRepeater;
                    const memberNameSubFieldKey = dmtp_localized_data.fieldKeys.memberNameSubField;
                    const teamsData = dmtp_localized_data.teamsData;
                    
                    function updatePerformanceRepeater() {
                        console.log('[DMTP Debug] updatePerformanceRepeater called.'); 
                        const teamsCheckboxField = acf.getField(teamsCheckboxKey_perf);
                        if (!teamsCheckboxField) return;
                        const performanceRepeater = acf.getField(performanceRepeaterKey);
                        if (!performanceRepeater) return;

                        const selectedTeams = [];
                        const $checkedTeams = teamsCheckboxField.$('input[type="checkbox"]:checked');
                        
                        $checkedTeams.each(function() {
                            selectedTeams.push($(this).val()); 
                        });

                        const requiredMembers = [];
                        selectedTeams.forEach(teamKey => {
                            if (teamsData[teamKey]) {
                                requiredMembers.push(...teamsData[teamKey]);
                            }
                        });
                        const uniqueRequiredMembers = [...new Set(requiredMembers)];

                        const $rows = performanceRepeater.$el.find('.acf-row:not(.acf-clone)'); 
                        const currentMembers = [];

                        // Get current members and check for removal
                        $rows.each(function() {
                            const $row = $(this);
                            const memberNameField = acf.getFields({ parent: $row, key: memberNameSubFieldKey })[0];
                            const currentName = memberNameField ? memberNameField.val() : null;
                            
                            if (currentName) {
                                currentMembers.push(currentName);
                                if (!uniqueRequiredMembers.includes(currentName)) {
                                    console.log('[DMTP Debug] Removing row for:', currentName);
                                    acf.removeRow($row.data('id'), performanceRepeater.$el); 
                                }
                            }
                        });

                        // Add required members not currently present
                        uniqueRequiredMembers.forEach(memberName => {
                            if (!currentMembers.includes(memberName)) {
                                console.log('[DMTP Debug] Adding row for:', memberName);
                                acf.addRow(performanceRepeater.$el, function($newRow) {
                                    setTimeout(function() {
                                        console.log('[DMTP Debug] Finding name field in new row:', $newRow);
                                        const newMemberNameField = acf.getFields({ parent: $newRow, key: memberNameSubFieldKey })[0];
                                        console.log('[DMTP Debug] Found field:', newMemberNameField);
                                        if (newMemberNameField) {
                                            console.log('[DMTP Debug] Setting value to:', memberName);
                                            newMemberNameField.val(memberName);
                                            console.log('[DMTP Debug] Value after setting:', newMemberNameField.val());
                                        } else {
                                            console.error('[DMTP Debug] Could not find member name field [' + memberNameSubFieldKey + '] in new row.');
                                        }
                                    }, 50); 
                                });
                            }
                        });
                    }

                    // Event listener for performance repeater team selection
                    acf.on('change', '[data-key="' + teamsCheckboxKey_perf + '"] input[type="checkbox"]', function() {
                        console.log('[DMTP] Change detected on performance team checkbox');
                        setTimeout(updatePerformanceRepeater, 100);
                    });
                    
                    // Initial call if needed (optional)
                    setTimeout(updatePerformanceRepeater, 300);
                } // End check for performance repeater data
                
                // --- Dynamic Team Select Population in Sprint Hotfix Repeater ---
                const teamsCheckboxKey_hf = dmtp_localized_data.fieldKeys.teamsCheckbox; // Use unique var name suffix
                const hotfixRepeaterKey = dmtp_localized_data.fieldKeys.sprintHotfixRepeater;
                const teamSelectKey = dmtp_localized_data.fieldKeys.hotfixTeamSelect;
                
                function getSelectedTeamChoices() {
                    const choices = [{'value': '', 'label': '-- Select Team --'}];
                    const teamsCheckboxField = acf.getField(teamsCheckboxKey_hf);
                    if (teamsCheckboxField) {
                        const $checked = teamsCheckboxField.$el.find('input[type="checkbox"]:checked');
                        $checked.each(function() {
                            const val = $(this).val();
                            const label = $(this).closest('label').text().trim(); // Get label text
                            choices.push({ 'value': val, 'label': label });
                        });
                    }
                    return choices;
                }

                function updateHotfixTeamSelects() {
                    const newChoices = getSelectedTeamChoices();
                    const hotfixRepeaterField = acf.getField(hotfixRepeaterKey);
                    
                    if (hotfixRepeaterField) {
                        // Find all team select fields within the repeater rows (excluding clones)
                        const teamSelectFields = acf.findFields({ 
                            key: teamSelectKey, 
                            parent: hotfixRepeaterField.$el 
                        });
                        
                        teamSelectFields.forEach(function(field) {
                            const currentValue = field.val(); // Get current value before update
                            field.update({ choices: newChoices });
                            
                            // Try to re-select the previous value if it still exists in new choices
                            let valueStillExists = false;
                            for(let i=0; i < newChoices.length; i++) {
                                if (newChoices[i].value === currentValue) {
                                    valueStillExists = true;
                                    break;
                                }
                            }
                            
                            if (valueStillExists) {
                                field.val(currentValue);
                            } else {
                                field.val(''); // Reset if previous value is no longer valid
                            }
                        });
                    }
                }
                
                // Event listener for main team checkbox change (for hotfix selects)
                acf.on('change', '[data-key="' + teamsCheckboxKey_hf + '"] input[type="checkbox"]', function() {
                    console.log('[DMTP] Team checkbox changed, updating hotfix team selects.');
                    updateHotfixTeamSelects();
                });
                
                // Event listener for new hotfix repeater row append
                acf.on('append', '[data-key="' + hotfixRepeaterKey + '"]', function(e){
                    console.log('[DMTP] New hotfix team log row added, updating its team select.');
                    const $newRow = $(e.target);
                    const newTeamSelectField = acf.findFields({ 
                        key: teamSelectKey, 
                        parent: $newRow 
                    })[0]; // Get the field instance in the new row
                    
                    if (newTeamSelectField) {
                         const choices = getSelectedTeamChoices();
                         newTeamSelectField.update({ choices: choices });
                         newTeamSelectField.val(''); // Start with default selection
                    }
                });

                // Initial population for hotfix selects
                console.log('[DMTP] Initial population of hotfix team selects.');
                updateHotfixTeamSelects();
                
                // --- End Temporarily Commented Out ---
                
            }); // End acf.ready()
        } else {
            console.log('[DMTP] ACF object not found, skipping ACF-dependent features.');
        }

    }); // End document ready

})(jQuery); 