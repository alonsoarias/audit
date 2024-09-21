<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/audit/lib.php');
require_login();
admin_externalpage_setup('report_audit', '', null, '', ['pagelayout' => 'report']);

$context = context_system::instance();
require_capability('report/audit:view', $context);

// Captura de parámetros
$userid = optional_param('userid', 0, PARAM_INT);
$timestart = optional_param('timestart', '', PARAM_TEXT);
$timeend = optional_param('timeend', '', PARAM_TEXT);
$format = optional_param('format', 'excel', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 100;

$PAGE->set_url(new moodle_url('/report/audit/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('audit_report', 'report_audit'));
$PAGE->set_heading(get_string('audit_report', 'report_audit'));
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/report/audit/index.js'));
$PAGE->requires->css(new moodle_url('/report/audit/styles.css'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('audit_report', 'report_audit'));

// Formulario de filtros
echo '<form id="filtersForm" method="GET" class="form-inline mb-3">';
echo '<input type="hidden" name="page" value="' . $page . '">';
echo '<div class="form-row align-items-center">';

// Filtro de usuario
echo '<div class="col-auto">';
echo '<label for="userid" class="mr-2">' . get_string('fullname', 'report_audit') . '</label>';
echo '<select id="userid" name="userid" class="form-control mb-2">';
echo '<option value="0">' . get_string('all', 'report_audit') . '</option>';
$admins = get_all_admins();
foreach ($admins as $id => $name) {
    echo '<option value="' . $id . '"' . ($userid == $id ? ' selected' : '') . '>' . $name . '</option>';
}
echo '</select>';
echo '</div>';

// Filtro de fecha de inicio
echo '<div class="col-auto">';
echo '<label for="timestart" class="mr-2">' . get_string('timestart', 'report_audit') . '</label>';
echo '<select id="timestart" name="timestart" class="form-control mb-2">';
echo '<option value="">' . get_string('select_date', 'report_audit') . '</option>';
$dates = get_all_dates();
foreach ($dates as $date) {
    echo '<option value="' . $date . '"' . ($timestart == $date ? ' selected' : '') . '>' . $date . '</option>';
}
echo '</select>';
echo '</div>';

// Filtro de fecha de fin
echo '<div class="col-auto">';
echo '<label for="timeend" class="mr-2">' . get_string('timeend', 'report_audit') . '</label>';
echo '<select id="timeend" name="timeend" class="form-control mb-2">';
echo '<option value="">' . get_string('select_date', 'report_audit') . '</option>';
foreach ($dates as $date) {
    echo '<option value="' . $date . '"' . ($timeend == $date ? ' selected' : '') . '>' . $date . '</option>';
}
echo '</select>';
echo '</div>';

echo '</div>';
echo '</form>';

// Obtención de registros
$timestart_ts = !empty($timestart) ? strtotime($timestart) : 0;
$timeend_ts = !empty($timeend) ? strtotime($timeend . ' 23:59:59') : 0;

$params = [
    'userid' => $userid,
    'timestart' => $timestart_ts,
    'timeend' => $timeend_ts
];

$logs = get_superadmin_logs($params, $page, $perpage);
$totalcount = get_superadmin_logs_count($params);

echo '<div id="reportData">';
$table = new html_table();
$table->head = [
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
$table->data = [];

foreach ($logs as $log) {
    $table->data[] = [
        userdate($log->time),
        $log->fullname,
        $log->eventname,
        $log->component,
        $log->action,
        $log->target,
        $log->objecttable,
        $log->objectid,
        $log->description,
        $log->ip
    ];
}

echo html_writer::table($table);

// Mostrar el total de registros
echo '<div class="mt-3">';
echo '<strong>' . get_string('total_records', 'report_audit') . ': ' . $totalcount . '</strong>';
echo '</div>';

echo '</div>';

// Formulario de descarga
echo '<form id="downloadForm" method="GET">';
echo '<input type="hidden" name="userid" value="' . $userid . '">';
echo '<input type="hidden" name="timestart" value="' . $timestart . '">';
echo '<input type="hidden" name="timeend" value="' . $timeend . '">';
echo '<div class="form-group">';
echo '<label for="format" class="mr-2">' . get_string('download_format', 'report_audit') . '</label>';
echo '<select id="format" name="format" class="form-control d-inline w-auto">';
echo '<option value="excel" ' . ($format === 'excel' ? 'selected' : '') . '>Excel</option>';
echo '<option value="ods" ' . ($format === 'ods' ? 'selected' : '') . '>ODS</option>';
echo '<option value="csv" ' . ($format === 'csv' ? 'selected' : '') . '>CSV</option>';
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

echo $OUTPUT->footer();

// Manejo de la descarga
if (optional_param('download', '', PARAM_ALPHA)) {
    $logs = get_superadmin_logs($params, 0, 0); // Get all logs for download
    $data = [];
    foreach ($logs as $log) {
        $data[] = [
            userdate($log->time),
            $log->fullname,
            $log->eventname,
            $log->component,
            $log->action,
            $log->target,
            $log->objecttable,
            $log->objectid,
            $log->description,
            $log->ip
        ];
    }
    
    $downloadfilename = 'audit_report_' . date('Y-m-d');
    if ($format === 'csv') {
        export_to_csv($table->head, $data, $downloadfilename);
    } else {
        export_to_spreadsheet($table->head, $data, $downloadfilename, $format, get_string('audit_report', 'report_audit'));
    }
    exit;
}
?>