<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/report/audit/lib.php');
require_login();

$context = context_system::instance();
require_capability('report/audit:view', $context);

$admins = get_all_admins();
$dates = get_all_dates();

$response = [
    'admins' => array_map(function($id, $fullname) {
        return ['id' => $id, 'fullname' => $fullname];
    }, array_keys($admins), $admins),
    'dates' => $dates
];

echo json_encode($response);