$(document).ready(function() {
    // Function to update the report dynamically
    function updateReport() {
        $.ajax({
            url: window.location.href,  // Usar URL dinámica en lugar de URL estática
            type: 'GET',
            data: $('#filtersForm').serialize(),  // Serializa los elementos del formulario.
            success: function(data) {
                $('#reportData').html($(data).find('#reportData').html());
                initializePagination();  // Reinitialize pagination after update
            },
            error: function() {
                alert(M.util.get_string('error_updating_report', 'report_audit'));
            }
        });
    }

    // Event handlers for filters like user selection and date fields
    $('#userid, #timestart, #timeend').change(function() {
        updateReport();  // Update report when the user or date filters change
    });

    // Specifically handling date range validation or format if necessary
    $('#timestart, #timeend').change(function() {
        updateReport();  // Update report when the date fields change
    });

    // Initialize pagination dynamically
    function initializePagination() {
        $('.paging_bar a').on('click', function(e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            $('input[name="page"]').val(page);
            updateReport();  // Update report when pagination changes
        });
    }

    // Initialize functions on page load
    initializePagination();
});
