<?php
include_once('common_bo.php');

$node = $_REQUEST['node'];

$nodes = array();

if ($node == 'root') {

    $r  = fssql_query("SELECT id, name, active from forum_replace_groups ORDER BY name");
    while ($f = mysql_fetch_assoc($r)) {
        $nodes[] = array(
            'text' => $f['name'],
            'id'   => isset($_REQUEST['store'])?$f['id']:'group-'.$f['id'],
            'cls'  => $f['active']?'tree-folder-visible':'tree-folder-hidden',
            'leaf' => FALSE
        );
    }

} else {

    $data = explode('-',$node);
    $r = fssql_query("SELECT id, name, active from forum_replace where idgroup=".(int)$data[1]." ORDER BY name");
    while ($f = mysql_fetch_assoc($r)) {
        $nodes[] = array(
            'text' => $f['name'],
            'id'   => 'item-'.$f['id'],
            'icon' => '/'.CMS_DIR.'/images/'.($f['active']?'lamp_glow':'lamp').'.gif',
            'leaf' => TRUE
        );
    }

}

if (isset($_REQUEST['store'])) {
    echo json_encode(array(
        'success' => true,
        'rows'    => $nodes
    ));
} else {
    echo json_encode($nodes);
}
