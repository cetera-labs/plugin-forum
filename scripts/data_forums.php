<?php
include_once('common_bo.php');
include('common_forums.php');

if (!$user->allowAdmin())  throw new Exception_CMS(Exception_CMS::NO_RIGHTS);

$data = array();

$r = fssql_query('SELECT id, name FROM dir_data WHERE typ='.ObjectDefinition::findByAlias(FORUMS_TYPE)->id); 
while ($f = mysql_fetch_assoc($r)) $data[] = $f;

echo json_encode(array(
    'success' => true,
    'rows'    => $data
));
?>
