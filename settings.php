<?php
defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage(
    'report_audit',
    get_string('pluginname', 'report_audit'),
    new moodle_url('/report/audit/index.php')
));