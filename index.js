$(document).ready(function() {
    function updateReport() {
        $.ajax({
            url: M.cfg.wwwroot + '/report/audit/index.php',
            type: 'GET',
            data: $('#filtersForm').serialize(),
            success: function(data) {
                $('#reportData').html($(data).find('#reportData').html());
                initializePagination();
            },
            error: function() {
                console.error('Error updating report.');
                alert(M.util.get_string('error_updating_report', 'report_audit'));
            }
        });
    }

    // Actualizar el reporte cuando cambie cualquier filtro
    $('#userid, #timestart, #timeend').on('change', function() {
        updateReport();
    });

    function initializePagination() {
        $('.paging_bar a').on('click', function(e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            $('input[name="page"]').val(page);
            updateReport();
        });
    }

    $('#downloadForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var url = M.cfg.wwwroot + '/report/audit/index.php?' + formData;
        
        // Realizar la solicitud de descarga
        window.location.href = url;
    });

    // Inicializar la paginación al cargar la página
    initializePagination();

    // Función para actualizar las opciones de fecha de fin
    function updateEndDateOptions() {
        var startDate = $('#timestart').val();
        $('#timeend option').prop('disabled', false);
        if (startDate) {
            $('#timeend option').filter(function() {
                return this.value < startDate;
            }).prop('disabled', true);
        }
    }

    // Actualizar las opciones de fecha de fin cuando cambie la fecha de inicio
    $('#timestart').on('change', function() {
        updateEndDateOptions();
        if ($('#timeend').val() < $(this).val()) {
            $('#timeend').val('');
        }
    });

    // Inicializar las opciones de fecha de fin al cargar la página
    updateEndDateOptions();
});