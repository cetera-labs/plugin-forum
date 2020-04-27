<?php
include_once('common_bo.php');

if (!$user->allowAdmin())  throw new Exception_CMS(Exception_CMS::NO_RIGHTS);

$data = array();

$data = $application->getDbConnection()->fetchAll('SELECT id, name FROM dir_data WHERE typ=?', [\Cetera\ObjectDefinition::findByAlias(FORUMS_TYPE)->id]);		

echo json_encode(array(
    'success' => true,
    'rows'    => $data
));