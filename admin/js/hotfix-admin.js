/**
 * Admin scripts specifically for the Hotfix Tracker page.
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        
        // --- Hotfix Table Search --- 
        // Ensure this targets inputs that exist on the page
        $(document).on('keyup', 'input[id^="hotfix-search-input-"]', function() {
            var searchTerm = $(this).val().toLowerCase();
            // Construct the ID for the tbody associated with this search input
            var tableBodyId = '#hotfix-table-body-' + $(this).attr('id').substring('hotfix-search-input-'.length);
            
            $(tableBodyId + ' tr').each(function() {
                var rowText = $(this).text().toLowerCase();
                if (rowText.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // --- Hotfix Table CSV Export --- 
        // Use event delegation in case the button is added dynamically (though likely not needed here)
        $(document).on('click', '.export-hotfix-csv', function(e) {

            e.preventDefault();
            
            var $button = $(this);
            var tableId = '#' + $button.data('table-id');
            var sprintTitle = $button.data('sprint-title') || 'Sprint';
            var teamName = $button.data('team-name') || 'Team';
            var $table = $(tableId);
            
            if (!$table.length) {
                console.error('Export Error: Table not found with ID:', tableId);
                alert('Could not find the table to export.');
                return;
            }

            var csv = [];
            var headers = [];
            
            // Get headers from the table
            $table.find('thead tr th').each(function() {
                headers.push('"' + $(this).text().trim().replace(/"/g, '""') + '"'); // Basic CSV escaping
            });
            csv.push(headers.join(','));

            // Get visible rows data
            $table.find('tbody tr:visible').each(function() {
                var row = [];
                $(this).find('td').each(function() {
                    var cellText = $(this).text().trim();
                    var $link = $(this).find('a');
                    // Always export the href of the first <a> tag if present
                    if ($link.length > 0) {
                        cellText = $link.attr('href');
                    }
                    row.push('"' + cellText.replace(/"/g, '""') + '"'); // Basic CSV escaping
                });
                csv.push(row.join(','));
            });
            
            if (csv.length <= 1) { // Check if only headers are present
                alert('No data available to export (check search filters).');
                return;
            }

            var csvContent = csv.join('\n');
            // Sanitize sprint title and team name for filename
            var safeSprintTitle = sprintTitle.replace(/[^a-zA-Z0-9]/g, '_');
            var safeTeamName = teamName.replace(/[^a-zA-Z0-9]/g, '_');
            var filename = 'HotfixExport_' + safeSprintTitle + '_' + safeTeamName + '_' + new Date().toISOString().slice(0,10) + '.csv';

            // Ensure downloadData function is available (should be from admin-scripts.js)
            if (typeof downloadData === 'function') {
                 downloadData(csvContent, filename, 'csv'); 
            } else {
                console.error('downloadData function is not defined. Ensure admin-scripts.js is loaded first.');
                alert('Error initiating download: Helper function missing.');
            }
        });

    }); // End document ready

})(jQuery); 