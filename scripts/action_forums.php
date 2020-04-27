<?php
/**
 * Cetera CMS 3 
 * 
 * AJAX-backend действия с форумами
 *
 * @package CeteraCMS
 * @version $Id$
 * @copyright 2000-2010 Cetera labs (http://www.cetera.ru) 
 * @author Roman Romanov <nicodim@mail.ru> 
 **/
  
include_once('common_bo.php');

$res = array(
    'success' => false,
    'errors'  => array()
);


$action = $_POST['action'];
$sel = $_POST['sel'];
$id = (int)$_POST['id'];

if ($action == 'delete_forum') {

    if (!$user->allowCat(PERM_CAT_ADMIN, $id)) 
        throw new Exception_CMS(Exception_CMS::NO_RIGHTS);

    $c = \Cetera\Catalog::getById($id);
    $c->delete();

}

if ($action == 'save_forum') {

    if (!$id) {
        if (!$user->allowCat(PERM_CAT_ADMIN, $_POST['parentid']))
            throw new Exception_CMS(Exception_CMS::NO_RIGHTS);    
        
        $c = \Cetera\Catalog::getById($_POST['parentid']); 
        $id = $c->createChild(array(
        	'name'		=> $_POST['name'],
        	'alias'		=> $_POST['alias'],
//        	'describ'	=> $_POST['describ'],
        	'typ'		  => \Cetera\ObjectDefinition::findByAlias(FORUMS_TYPE)->id,
        	'link'		=> false,
//        	'recurse'	=> false,
        	'server'    => false
        ));
        $catalog = \Cetera\Catalog::getById($id);
    } else {
        $catalog = \Cetera\Catalog::getById($id);
               
        if (isset($_POST['parentid']) && ((int)$_POST['parentid'] != $catalog->parent->id)) 
            $catalog->move((int)$_POST['parentid']);
               
        $catalog->update($_POST);
    }
       
    if ($user->allowCat(PERM_CAT_ADMIN, $id)) 
        $catalog->updatePermissions($_POST['permissions']);
        
    $res['success'] = true;
}

if ($action == 'get_forum') {
    $catalog = \Cetera\Catalog::getById($id);
    $res['data'] = array(
        'name'        => $catalog->name,
        'alias'       => $catalog->alias,
        //'pic'         => $catalog->pic,
        //'describ'     => $catalog->describ,
        'parentid'    => $catalog->parent->id,
        'parentpath'  => $catalog->parent->getTreePath(),
        'parentname'  => $catalog->parent->getPath()->implode(),
        'permissions' => array()
    );
    foreach ($forum_permissions as $pid => $name) {
		$r = $application->getDbConnection()->fetchAll('SELECT group_id FROM users_groups_allow_cat WHERE permission=? and catalog_id=?', [$pid,$id]);
        $res['data']['permissions'][$pid] = array();
        foreach ($r as $perm) $res['data']['permissions'][$pid][] = (int)$perm['group_id'];
    }
    $res['success'] = true;
}

if ($action == 'replace_get_replace') {
    $res['data'] = $application->getDbConnection()->fetchAssoc('SELECT * FROM forum_replace WHERE id=?',[(int)$id]);
    $res['success'] = true;
}

if ($action == 'replace_delete') {
    list($item, $id) = explode('-', $_POST['node']);
    if ($item == 'group') {
        $application->getDbConnection()->executeQuery("delete from forum_replace_groups where id=".(int)$id);
        $application->getDbConnection()->executeQuery("delete from forum_replace where idgroup=".(int)$id);
    } else {
        $application->getDbConnection()->executeQuery("delete from forum_replace where id=".(int)$id);
    }
    $res['success'] = true;
}

if ($action == 'replace_save') {
    $id      = (int)$_POST['id'];
    $name    = $_POST['name'];
    $pattern = $_POST['pattern'];
    $replace = $_POST['replacement'];
    $prop    = (integer) $_POST['prop'];
    $group   = (integer) $_POST['idgroup'];
    $active  = (integer) $_POST['active'];
    if ($id) {
        $application->getDbConnection()->executeQuery("update forum_replace set name='$name', pattern='$pattern', replacement='$replace', prop=$prop, idgroup=$group, active=$active where id=$id");
    } else {
  	    $application->getDbConnection()->executeQuery("insert into forum_replace set name='$name', pattern='$pattern', replacement='$replace', prop=$prop, idgroup=$group, active=$active");
    }
    $res['success'] = true;
}

if ($action == 'replace_disable' || $action == 'replace_enable') {
    list($item, $id) = explode('-', $_POST['node']);
    if ($item == 'group') $table = 'forum_replace_groups'; else $table = 'forum_replace';
    if ($action == 'replace_disable') $active = 0; else $active = 1;
    $application->getDbConnection()->executeQuery('UPDATE '.$table.' SET active='.$active.' WHERE id='.$id);
    $res['success'] = true;
}

if ($action == 'new_replace_group') {
  if ($id) {
    $application->getDbConnection()->executeQuery('update forum_replace_groups set name="'.$_POST['name'].'" where id='.$id);
  } else {
    $application->getDbConnection()->executeQuery('insert into forum_replace_groups set name="'.$_POST['name'].'"');
  }
  $res['success'] = true;
}

if ($action == 'permissions') {
    
    $right[0] = $user->allowCat(PERM_CAT_OWN_MAT, $id); // Pабота со своими материалами
    $right[1] = $user->allowCat(PERM_CAT_ALL_MAT, $id); // Работа с материалами других авторов
    $right[2] = $user->allowCat(PERM_CAT_MAT_PUB, $id); // Публикация материалов

    $right[3] = '';
    list($right[3], $right[4]) = $application->getDbConnection()->fetchArray('SELECT preview, typ FROM dir_data WHERE id=?',[(int)$id]);

    $res['success'] = true;
       $res['right']   = $right;
}

if ($action == 'delete' && is_array($sel)) {
    $catalog = \Cetera\Catalog::getById($id);
	foreach ($sel as $val) {
	   $m = \Forum\Post::getById($val, $catalog->materialsType, $catalog->materialsTable);
       $m->delete();
    }
	$res['success'] = true;

}

if (($action == 'pub' || $action == 'unpub' || $action == 'move' || $action == 'close' || $action == 'open') && is_array($sel)) {
    $catalog = \Cetera\Catalog::getById($id);
    
    $cats = "A.idcat=$id";

    $where = '';
	
    foreach($sel as $val) {
	  
	  if ($action == 'pub' && function_exists('on_publish')) on_publish();
	  if ($action == 'unpub' && function_exists('on_unpublish')) on_unpublish();

	  if ($where == '') 
		  $where="id=$val"; else $where .= " or id=$val";
		
	    $tpl = new \Cetera\Cache\Tag\Material($catalog->materialsTable,$val);
        $tpl->clean();
        
	    $tpl = new \Cetera\Cache\Tag\Material($catalog->materialsTable,0);
        $tpl->clean();
    }
	if ($action == 'pub') {
	  $stat = "update ".$catalog->materialsTable." set type=type | ".MATH_PUBLISHED;
	}
	if ($action == 'unpub') {
	  $not_publish_bit = ~ MATH_PUBLISHED;
	  $stat = "update ".$catalog->materialsTable." set type=type & $not_publish_bit";
	}
	if ($action == 'close') {
	  $stat = "update ".$catalog->materialsTable." set closed=1";
	}
	if ($action == 'open') {
	  $stat = "update ".$catalog->materialsTable." set closed=0";
	}	
	if ($action == 'move') {
	  $r = $application->getDbConnection()->fetchColumn("SELECT MAX(tag) FROM ".$catalog->materialsTable." WHERE idcat=".$_POST['cat'],[],0);
	  $tt = $r + 1;	
	  $stat = "update ".$catalog->materialsTable." set idcat=".$_POST['cat'].", tag=$tt";
	}
    if ($stat) $application->getDbConnection()->executeQuery("$stat where ($where)");
    
    $res['success'] = true;
}


echo json_encode($res);