<?php
/************************************************************************************************

Список материалов

*************************************************************************************************/

try {
    
    include_once('common_bo.php');
    
    $m_id     = (int)$_REQUEST['id'];
    $catalog = \Cetera\Catalog::getById($m_id);
    
    $parent = (int)$_REQUEST['parent'];
    
    if (!$_REQUEST['m_filter']) $_REQUEST['m_filter'] = '%';
    $m_filter = $_REQUEST['m_filter'];
    
    $math_at_once = $_REQUEST['limit'];
    $m_first = $_REQUEST['start'];
        
    $order = $_REQUEST['dir'];
    $sort = $_REQUEST['sort'];
    
    // Выяснение прав пользователя ------------
    $right[0] = $user->allowCat(PERM_CAT_OWN_MAT, $m_id); // Pабота со своими материалами
    $right[1] = $user->allowCat(PERM_CAT_ALL_MAT, $m_id); // Работа с материалами других авторов
    
    // Вычисляем каков тип у материалов
    $f = $application->getDbConnection()->fetchArray("SELECT A.alias,A.id FROM types A, dir_data B where (A.id = B.typ)and(B.id = $m_id)");
    $math = $f[0];
    $type = $f[1];
        
    $sql_all = "select COUNT(A.id) from $math A where (A.name like '$m_filter' or A.text like '$m_filter')and(A.idcat=$m_id) and (A.parent=".(int)$parent.")";
    $f = $application->getDbConnection()->fetchArray($sql_all);
    $all_filter = $f[0];
    
    $sql = "SELECT A.id, A.type, A.autor as autor_id, username, ip, closed, answers,
                         if(A.name<>'',A.name,concat(substring(A.text,1,100),'...')) as name,
                         UNIX_TIMESTAMP(A.dat) as dat, 
                         IF(C.name<>'' and C.name IS NOT NULL, C.name, C.login) as autor
                         FROM $math A LEFT JOIN users C ON (A.autor=C.id)
                         WHERE (A.name like '$m_filter' or A.text like '$m_filter') and (A.idcat=$m_id) and (A.parent=".(int)$parent.")
                         ORDER BY $sort $order
                         LIMIT $m_first,$math_at_once";	
    $r  = $application->getDbConnection()->query($sql);
    
    $materials = array();
    
    while ($f = $r->fetch()) {

      $materials[] = array(
        'id'       => $f['id'],
        'icon_pub' => ($f['type'] & MATH_PUBLISHED)?1:0,
        'icon_close'=> (int)$f['closed'],
        'name'     => $f['name'],
        'dat'      => $f['dat'],
        'autor'    => ($f['autor']?$f['autor']:$f['username'])." [".long2ip($f['ip'])."]",
        'answers'  => (int)$f['answers'],
        'disabled' => !(($f['autor_id']==$user->id && $right[0])||($f['autor_id']!=$user->id && $right[1]))
      );

    }
    
    echo json_encode(array(
        'success' => true,
        'total'   => $all_filter,
        'rows'    => $materials
    ));
    
} catch (Exception $e) {

    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage(),
        'rows'    => false
    ));

}