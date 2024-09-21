<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/excellib.class.php');
require_once($CFG->libdir.'/odslib.class.php');
require_once($CFG->libdir.'/csvlib.class.php');

// Función para obtener los registros de logs de superadministradores
function get_superadmin_logs($params, $page = 0, $perpage = 100, $sort = 'l.timecreated DESC') {
    global $CFG, $DB;
    $conditions = [];
    $sql_params = [];
    $adminids = explode(',', $CFG->siteadmins);
    
    list($insql, $inparams) = $DB->get_in_or_equal($adminids, SQL_PARAMS_NAMED);
    $conditions[] = "l.userid $insql";
    $sql_params = array_merge($sql_params, $inparams);
    
    if (!empty($params['userid'])) {
        $conditions[] = "u.id = :userid";
        $sql_params['userid'] = $params['userid'];
    }

    if (!empty($params['timestart'])) {
        $conditions[] = "l.timecreated >= :timestart";
        $sql_params['timestart'] = $params['timestart'];
    }

    if (!empty($params['timeend'])) {
        $conditions[] = "l.timecreated <= :timeend";
        $sql_params['timeend'] = $params['timeend'];
    }

    $where = implode(' AND ', $conditions);
    
    $sql = "SELECT
                l.id,
                l.timecreated AS time,
                CONCAT(u.firstname, ' ', u.lastname) AS fullname,
                u.username,
                ra.username AS affecteduser,
                l.contextinstanceid AS eventcontext,
                l.component,
                l.eventname,
                l.action,
                l.target,
                l.objecttable,
                l.objectid,
                l.other AS description,
                l.origin,
                l.ip
            FROM
                {logstore_standard_log} l
                JOIN {user} u ON l.userid = u.id
                LEFT JOIN {user} ra ON l.relateduserid = ra.id
            WHERE $where
            ORDER BY $sort";

    if ($perpage > 0) {
        $sql .= " LIMIT $perpage OFFSET " . ($page * $perpage);
    }

    return $DB->get_records_sql($sql, $sql_params);
}

// Contador de registros para los logs de superadministradores
function get_superadmin_logs_count($params) {
    global $CFG, $DB;
    $conditions = [];
    $sql_params = [];
    $adminids = explode(',', $CFG->siteadmins);
    
    list($insql, $inparams) = $DB->get_in_or_equal($adminids, SQL_PARAMS_NAMED);
    $conditions[] = "l.userid $insql";
    $sql_params = array_merge($sql_params, $inparams);
    
    if (!empty($params['userid'])) {
        $conditions[] = "u.id = :userid";
        $sql_params['userid'] = $params['userid'];
    }

    if (!empty($params['timestart'])) {
        $conditions[] = "l.timecreated >= :timestart";
        $sql_params['timestart'] = $params['timestart'];
    }

    if (!empty($params['timeend'])) {
        $conditions[] = "l.timecreated <= :timeend";
        $sql_params['timeend'] = $params['timeend'];
    }

    $where = implode(' AND ', $conditions);
    
    $sql = "SELECT COUNT(1)
            FROM {logstore_standard_log} l
            JOIN {user} u ON l.userid = u.id
            WHERE $where";

    return $DB->count_records_sql($sql, $sql_params);
}

// Función para obtener todos los administradores
function get_all_admins() {
    global $CFG, $DB;
    $adminids = explode(',', $CFG->siteadmins);
    list($insql, $params) = $DB->get_in_or_equal($adminids);
    $sql = "SELECT id, CONCAT(firstname, ' ', lastname) AS fullname
            FROM {user}
            WHERE id $insql
            ORDER BY fullname ASC";
    return $DB->get_records_sql_menu($sql, $params);
}

// Función para obtener todas las fechas de actividad de logs
function get_all_dates() {
    global $CFG, $DB;
    $adminids = explode(',', $CFG->siteadmins);
    
    list($insql, $params) = $DB->get_in_or_equal($adminids);
    $sql = "
        SELECT DISTINCT DATE(FROM_UNIXTIME(l.timecreated)) as date
        FROM {logstore_standard_log} l
        WHERE l.userid $insql
        ORDER BY date DESC
    ";
    $results = $DB->get_records_sql($sql, $params);
    $dates = [];
    foreach ($results as $result) {
        $dates[] = $result->date;
    }
    return $dates;
}

// Exportar datos a CSV
function export_to_csv($header, $data, $filename) {
    // Limpieza del buffer de salida para evitar problemas con encabezados
    while (ob_get_level()) {
        ob_end_clean();
    }

    $output = fopen('php://temp', 'r+');
    fputcsv($output, $header);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $csv;
}

// Exportar datos a Excel o ODS
function export_to_spreadsheet($header, $data, $filename, $format, $title) {
    global $CFG;
    
    // Limpieza del buffer de salida para evitar problemas con encabezados
    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($format === 'excel') {
        $workbook = new MoodleExcelWorkbook('-');
    } else {
        $workbook = new MoodleODSWorkbook('-');
    }
    
    $workbook->send($filename . '.' . $format);
    $worksheet = $workbook->add_worksheet($title);
    
    $row = 0;
    $col = 0;
    foreach ($header as $item) {
        $worksheet->write($row, $col++, $item);
    }
    
    $row++;
    foreach ($data as $datarow) {
        $col = 0;
        foreach ($datarow as $item) {
            $worksheet->write($row, $col++, $item);
        }
        $row++;
    }
    
    $workbook->close();
}
?>
