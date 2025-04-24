/**
 * Admin scripts for the Delivery Manager Tracking Plugin.
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('DMTP admin scripts loaded.');

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

        // --- Helper Function for Export ---
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
            
            // Add a loading indicator here
            // TODO: Add a more robust loading indicator
            var $button = event ? $(event.target).prop('disabled', true).text(dmtp_localized_data.i18n.exporting) : null;
            
            $.ajax({
                url: dmtp_localized_data.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        console.log('Export successful:', response.data);
                        // Trigger file download
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
                    // Remove loading indicator
                    $button.prop('disabled', false).text($button.data('original-text')); // Restore original text
                }
            });
        }
        
        // --- Helper Function for Download ---
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
                    alert(dmtp_localized_data.i18n.downloadError);
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
                     popup.document.write("<pre>" + content + "</pre>");
                     popup.document.close();
                }
            }
        }

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

        // --- Auto-populate Member Performance (Run AFTER ACF is ready) --- 
        // Check if acf object exists before trying to use it
        if (typeof acf !== 'undefined') { 
            acf.ready(function() {

                if (typeof dmtp_localized_data !== 'undefined' && dmtp_localized_data.teamsData) {
                    
                    const teamsCheckboxKey = dmtp_localized_data.fieldKeys.teamsCheckbox;
                    const performanceRepeaterKey = dmtp_localized_data.fieldKeys.performanceRepeater;
                    const memberNameSubFieldKey = dmtp_localized_data.fieldKeys.memberNameSubField;
                    const teamsData = dmtp_localized_data.teamsData; // { 'team-key': ['member1', 'member2'] }
                    
                    // Function to update the performance repeater
                    function updatePerformanceRepeater() {
                        console.log('[DMTP Debug] updatePerformanceRepeater called.'); 
                        
                        const teamsCheckboxField = acf.getField(teamsCheckboxKey);
                        console.log('[DMTP Debug] Teams Checkbox Field:', teamsCheckboxField);
                        if (!teamsCheckboxField) {
                            console.error('[DMTP Debug] Could not find Teams Checkbox field using key:', teamsCheckboxKey);
                            return; 
                        }

                        const performanceRepeater = acf.getField(performanceRepeaterKey);
                        console.log('[DMTP Debug] Performance Repeater Field:', performanceRepeater);
                        if (!performanceRepeater) {
                            console.error('[DMTP Debug] Could not find Performance Repeater field using key:', performanceRepeaterKey);
                            return; 
                        }

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

                    // Listen for changes on the team selection checkbox field
                    // We need to listen on the document because the field itself might be added/removed by ACF
                    $(document).on('change', '.acf-field[data-key="' + teamsCheckboxKey + '"] input[type="checkbox"]' , function(e){
                        console.log('[DMTP Debug] Change detected on team checkbox via event delegation');
                        setTimeout(updatePerformanceRepeater, 100); 
                    });

                    // Initial population on page load (optional, but good UX)
                    // Uncomment if you want it to run immediately when the page loads/refreshes
                     // setTimeout(updatePerformanceRepeater, 300); // Add a longer delay on load

                }
            }); // End acf.ready
        } // End typeof acf check

        // --- Dynamic Developer Dropdown for Individual Tracker ---
        var $indTeamSelect = $('#dmtp_team_name');
        var $indDeveloperSelect = $('#dmtp_developer_name');
        if ($indTeamSelect.length) { // Only run if Individual Tracker elements exist
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

    }); // End document ready

})(jQuery); 