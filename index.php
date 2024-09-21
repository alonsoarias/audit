<?php
require_once('../../config.php');
require_once('lib.php');
require_login();

$context = context_system::instance();
require_capability('report/audit:view', $context);

// Captura de parámetros
$userid = optional_param('userid', 0, PARAM_INT);
$timestart = optional_param('timestart', '', PARAM_TEXT);
$timeend = optional_param('timeend', '', PARAM_TEXT);
$format = optional_param('format', 'excel', PARAM_TEXT);

// Preparando parámetros para la consulta
$params = [
    'userid' => $userid,
    'timestart' => !empty($timestart) ? strtotime($timestart) : null,
    'timeend' => !empty($timeend) ? strtotime($timeend . ' 23:59:59') : null
];

// Recuperando registros aplicando filtros
$records = get_superadmin_logs($params, 0, 0);

$data = new stdClass();
$data->tabhead = [
    get_string('time', 'report_audit'),
    get_string('fullname', 'report_audit'),
    get_string('event', 'report_audit'),
    get_string('component', 'report_audit'),
    get_string('action', 'report_audit'),
    get_string('target', 'report_audit'),
    get_string('objecttable', 'report_audit'),
    get_string('objectid', 'report_audit'),
    get_string('description', 'report_audit'),
    get_string('ip', 'report_audit')
];
$data->table = [];

// Procesando registros y validando datos antes de exportar
foreach ($records as $record) {
    $data->table[] = [
        userdate($record->time),
        !empty($record->fullname) ? $record->fullname : '',
        !empty($record->eventname) ? $record->eventname : '',
        !empty($record->component) ? $record->component : '',
        !empty($record->action) ? $record->action : '',
        !empty($record->target) ? $record->target : '',
        !empty($record->objecttable) ? $record->objecttable : '',
        !empty($record->objectid) ? $record->objectid : '',
        !empty($record->description) ? $record->description : '',
        !empty($record->ip) ? $record->ip : ''
    ];
}

// Exportación de datos
if (optional_param('download', '', PARAM_TEXT)) {
    while (ob_get_level()) {
        ob_end_clean(); // Limpiar cualquier salida previa
    }

    // Verificar formato de exportación y realizar la exportación
    if ($format === 'csv') {
        export_to_csv($data->tabhead, $data->table, 'audit_report');
    } else {
        export_to_spreadsheet($data->tabhead, $data->table, 'audit_report', $format, get_string('audit_report', 'report_audit'));
    }
    exit;
}

// Configuración de la página y carga de recursos necesarios
$PAGE->set_url(new moodle_url('/report/audit/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('audit_report', 'report_audit'));
$PAGE->set_heading(get_string('audit_report', 'report_audit'));
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/report/audit/index.js'));

$perpage = 100;
$page = optional_param('page', 0, PARAM_INT);

echo $OUTPUT->header();

// Renderizado del formulario de filtros
echo '<form id="filtersForm" method="GET" class="form-inline mb-3">';
echo '<div class="form-row align-items-center">';

// Filtro de usuario
echo '<div class="col-auto">';
echo '<label for="userid" class="mr-2">' . get_string('fullname', 'report_audit') . ':</label>';
echo '<select id="userid" name="userid" class="form-control mb-2">';
echo '<option value="0">' . get_string('all', 'report_audit') . '</option>';
$admins = get_all_admins();
foreach ($admins as $id => $name) {
    $selected = $userid == $id ? 'selected' : '';
    echo '<option value="' . $id . '" ' . $selected . '>' . $name . '</option>';
}
echo '</select>';
echo '</div>';

// Filtro de fecha de inicio
echo '<div class="col-auto">';
echo '<label for="timestart" class="mr-2">' . get_string('timestart', 'report_audit') . ':</label>';
echo '<input type="date" id="timestart" name="timestart" value="' . $timestart . '" class="form-control mb-2">';
echo '</div>';

// Filtro de fecha de fin
echo '<div class="col-auto">';
echo '<label for="timeend" class="mr-2">' . get_string('timeend', 'report_audit') . ':</label>';
echo '<input type="date" id="timeend" name="timeend" value="' . $timeend . '" class="form-control mb-2">';
echo '</div>';
echo '</div>';
echo '</form>';

echo '<div id="reportData">';

$totalcount = count($records);
$records = array_slice($records, $page * $perpage, $perpage);

$table = new html_table();
$table->head = $data->tabhead;

foreach ($records as $record) {
    $table->data[] = [
        userdate($record->time),
        $record->fullname,
        $record->eventname,
        $record->component,
        $record->action,
        $record->target,
        $record->objecttable,
        $record->objectid,
        $record->description,
        $record->ip
    ];
}

echo html_writer::table($table);

// Mostrar el total de registros
echo '<div class="mt-3">';
echo '<strong>' . get_string('total_records', 'report_audit') . ': ' . $totalcount . '</strong>';
echo '</div>';

// Formulario de descarga
echo '<form id="downloadForm" method="GET">';
echo '<input type="hidden" name="userid" value="' . $userid . '">';
echo '<input type="hidden" name="timestart" value="' . $timestart . '">';
echo '<input type="hidden" name="timeend" value="' . $timeend . '">';
echo '<div class="form-group">';
echo '<label for="format" class="mr-2">' . get_string('download_format', 'report_audit') . ':</label>';
echo '<select id="format" name="format" class="form-control d-inline w-auto">';
echo '<option value="excel" ' . ($format === 'excel' ? 'selected' : '') . '>' . get_string('download', 'report_audit') . ' (Excel)</option>';
echo '<option value="ods" ' . ($format === 'ods' ? 'selected' : '') . '>' . get_string('download', 'report_audit') . ' (ODS)</option>';
echo '<option value="csv" ' . ($format === 'csv' ? 'selected' : '') . '>' . get_string('download', 'report_audit') . ' (CSV)</option>';
echo '</select>';
echo '<button type="submit" name="download" value="1" class="btn btn-primary ml-2">' . get_string('download', 'report_audit') . '</button>';
echo '</div>';
echo '</form>';

$baseurl = new moodle_url('/report/audit/index.php', [
    'userid' => $userid,
    'timestart' => $timestart,
    'timeend' => $timeend
]);
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);

echo '</div>';
echo $OUTPUT->footer();
?>
