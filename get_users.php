<?php
require_once('../../config.php');
require_login();

// Captura de parámetros (modificados para report_audit)
$usertype = optional_param('usertype', '', PARAM_TEXT);
$userid = optional_param('userid', 0, PARAM_INT);

$params = [];
$sql = "SELECT DISTINCT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
        FROM {user} u";

// Filtrar por tipo de usuario si está disponible
if ($usertype) {
    $sql .= " JOIN {user_info_data} d1 ON d1.userid = u.id
              JOIN {user_info_field} f1 ON d1.fieldid = f1.id AND f1.shortname = 'user_type'
              WHERE d1.data = :usertype";
    $params['usertype'] = $usertype;
}

// Filtrar por usuario específico (si se necesita un único usuario)
if ($userid) {
    $sql .= empty($usertype) ? " WHERE " : " AND ";
    $sql .= "u.id = :userid";
    $params['userid'] = $userid;
}

// Obtener los registros de los usuarios
$users = $DB->get_records_sql($sql, $params);

// Devolver la respuesta en formato JSON
echo json_encode(array_values($users));
?>
