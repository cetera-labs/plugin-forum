<?php
namespace Forum;

/**
 * Форум
 *
 * @package CeteraCMS
 **/
class Forum extends \Cetera\Catalog {
	/**
	 * Возвращает форум по его идентификатору.	
	 * 	 
	 * @param int $id ID форума 	 
	 * @return Forum
	 * @throws Exception_CMS	 
	 */     	
	public static function getById($id) 
    {
		if (isset($GLOBALS['_forum_instances'][$id]))
			return $GLOBALS['_forum_instances'][$id];
			else {
			     $id = (int)$id;
				   $r = fssql_query('SELECT * FROM dir_data WHERE id='.$id.' and typ='.ObjectDefinition::findByAlias(FORUMS_TYPE)->id);
				   if (!mysql_num_rows($r)) throw new Cetera\Exception\CMS(Cetera\Exception\CMS::CAT_NOT_FOUND, 'ID='.$id);
			         return self::getByResult($r);
			}
	}
	
	/**
	 * Возвращает форум по его псевдониму.	
	 * 	 
	 * @param string $alias псевдоним форума 	 
	 * @return Forum
	 * @throws Exception_CMS	 
	 */     	
	public static function getByAlias($alias) 
    {
        $slot = new Cache\Slot\Forum($alias);
        if (false === ($forum = $slot->load())) {
		    $r = fssql_query('SELECT * FROM dir_data WHERE tablename="'.mysql_escape_string($alias).'" and typ='.ObjectDefinition::findByAlias(FORUMS_TYPE)->id);
		    $forum = self::getByResult($r);
            $slot->addTag(new \Cetera\Cache\Tag\CatalogID($forum->id));
            $slot->save($forum);
		} else {
            $GLOBALS['_forum_instances'][$forum->id] = $forum;
        }
		return $forum;
	}

    /**
     * Конструктор. Предотвращает создание нескольких экземпляров класса для одного и того же раздела.
     *   
     * @param array $fields поля раздела                   
     * @return Forum  
     */ 
    public static function singleton($fields) 
    {
		if (!isset($GLOBALS['_forum_instances'])) $GLOBALS['_forum_instances'] = array();
		if (!isset($GLOBALS['_forum_instances'][$fields['id']])){
            $GLOBALS['_forum_instances'][$fields['id']] = new Forum($fields);
        } 
		return $GLOBALS['_forum_instances'][$fields['id']]; 
    }

    /**
     * Возвращает форум по результату SQL запроса
     *   
     * @param resource $r результат SQL запроса              
     * @return Forum    
	 * @throws Exception_CMS     
     */       
	public static function getByResult($r) 
    {
		if (!$r || !mysql_num_rows($r)) throw new Exception_CMS(Exception_CMS::CAT_NOT_FOUND);
		$f = mysql_fetch_assoc($r);
		if (!$f) throw new Exception_CMS(Exception_CMS::CAT_NOT_FOUND);
       
		return self::singleton($f);
	}

    /**
     * Наследует ли раздел разрешения, заданные для родительского раздела 
     *         
     * @return boolean
     */         
    public function isInheritsPermissions()
    {
        return false;
    }
    
    public function replace($value)
    {
        $r = fssql_query("select id from forum_replace_groups where active>0");
        while ($f = mysql_fetch_row($r)) {
              $r1 = fssql_query("select pattern, replacement, prop from forum_replace where idgroup=$f[0] and active>0");
              while ($f1 = mysql_fetch_row($r1)) {
            	    switch ($f1[2]) {
            	    case 0: 
            			$value = str_replace($f1[0],$f1[1],$value);
            	        break;
            	    case 1:
            			$value = preg_replace($f1[0],$f1[1],$value);
            	        break;
            	    }
        	    }
        }
        return $value;
    }
    
    public function userAllowAccess($permission, $user)
    {
        if ($user) {
            if (!is_object($user)) $user = User::getById((int)$user);
            if (!is_object($user)) throw new Exception_CMS(Exception_CMS::INVALID_PARAMS);
        } else {
            $user = new UserAnonymous();
        }
    
        if ($user->allowAdmin()) {
            if ($permission == PERM_FORUM_MOD_POST || $permission == PERM_FORUM_MOD_THEME) return false;
            return TRUE;
        }
        return $this->allowAccess($permission, $user->groups);
    }
    
    public function isThemeClosed($themeid)
    {
        $r = fssql_query("select closed from forums where id=$themeid");
        if (mysql_num_rows($r)) $res = mysql_result($r,0);
        return (bool)$res;
    }
    
    public function canPost($themeid, $user)
    {
        if ($this->isThemeClosed($themeid)) return FALSE;
        return $this->userAllowAccess(PERM_FORUM_CREATE_POST, $user);
    }
    
    public function canCreateTheme($user)
    {
        return $this->userAllowAccess(PERM_FORUM_CREATE_THEME, $user);
    }
    
    public function getThemeIdByMaterial($id_art,$id_cat=0)
    {
        $r = fssql_query('SELECT id FROM forums WHERE parent=0 and link_cat='.$id_cat.' and link_art='.$id_art);
        if (mysql_num_rows($r)) 
            return mysql_result($r,0);
            else return 0;
    } 
    
    public function getThreadIdByPost($pid)
    {
        $r = fssql_query('SELECT thread FROM forums WHERE id='.(int)$pid);
        if (mysql_num_rows($r)) 
            return mysql_result($r,0);
            else return 0;
    } 
    
    public function newTheme($args, $user=0, $link_cat=0, $link_art=0, $checkmail=TRUE)
    {
        if (is_array($args)) {
            $args['text'] = nl2br(htmlspecialchars($args['text']));
            $args['name'] = nl2br(htmlspecialchars($args['name']));
        } else {
            $args = array(
                'text' => nl2br(htmlspecialchars($args)),
                'name' => nl2br(htmlspecialchars($args))
            );        
        }
        if ($user) {
            if (!is_object($user)) $user = User::getById((int)$user);
            if (!is_object($user)) throw new Exception_CMS(Exception_CMS::INVALID_PARAMS);
            $args['user'] = $user->login;
            $args['email'] = $user->email;
        } else {
            $user = new UserAnonymous();
            $args['user'] = mysql_escape_string($args['user']);
            $args['email'] = mysql_escape_string($args['email']);
            if ($checkmail)
              if (!is_email($args['email']))
                throw new Exception_CMS(Exception_CMS::INVALID_EMAIL);
        }
        if (!$this->canCreateTheme($user)) throw new Exception_CMS(Exception_CMS::CANT_CREATE);
        
        if (!$args['name'] || !$args['user'] || ($checkmail&!$args['email'])) 
            throw new Exception_CMS(Exception_CMS::INVALID_PARAMS);
            
        $args['text'] = $this->replace($args['text']);
        
        //$r = fssql_query("select count(id) from forums where (idcat=".$this->id.")and(name='".$args['name']."')");
        //if (mysql_result($r,0)) throw new Exception_CMS(Exception_CMS::MESSAGE_EXISTS);
        
        fssql_query("
            INSERT INTO forums SET
            name='".$args['name']."',
            text='".$args['text']."',
            username='".$args['user']."',
            email='".$args['email']."',
            autor=".$user->id.",
            parent=0, link_cat=".$link_cat.", link_art=".$link_art.",
            idcat=".$this->id.",
            dat=NOW(),
            ip=".ip2long(getenv('REMOTE_ADDR')).",
            type=".($this->userAllowAccess(PERM_FORUM_MOD_THEME, $user)?0:1));   
        
        $tid = mysql_insert_id();
        
        fssql_query('UPDATE forums SET thread='.$tid.' WHERE id='.$tid);
        
        return $tid;
    }   
    
    public function addPost($themeid, $args, $user=0, $checkmail=TRUE, $theme_name = FALSE, $theme_text = FALSE, $idcat=0, $idmath=0)
    {
        
        if (!$themeid) {
            if (!$theme_name) throw new Exception_CMS(Exception_CMS::INVALID_PARAMS);
            $targs = $args;
            if (!is_array($targs)) $targs = array();
            $targs['name'] = $theme_name;
            $targs['text'] = $theme_text;
            $themeid = $this->newTheme($targs,$user,$idcat,$idmath,$checkmail);
            $thread = $themeid;
        } else {
            $r = fssql_query('SELECT thread FROM forums WHERE id='.$themeid);
            $thread = mysql_result($r,0);
            if (!$thread) $thread = $themeid;
        }  
             
        if (!$this->canPost($themeid,$user)) throw new Exception_CMS(Exception_CMS::NO_RIGHTS);
        
        if (is_array($args)) {
            $args['text'] = nl2br(htmlspecialchars($args['text']));
            $args['name'] = nl2br(htmlspecialchars($args['name']));
        } else {
            $args = array(
                'text' => nl2br(htmlspecialchars($args)),
                'name' => nl2br(htmlspecialchars($args))
            );        
        }
        if ($user) {
            if (!is_object($user)) $user = User::getById((int)$user);
            if (!is_object($user)) throw new Exception_CMS(Exception_CMS::INVALID_PARAMS);
            $args['user'] = $user->login;
            $args['email'] = $user->email;
        } else {
            $user = new UserAnonymous();
            $args['user'] = mysql_escape_string($args['user']);
            $args['email'] = mysql_escape_string($args['email']);
            if ($checkmail)
              if (!is_email($args['email']))
                throw new Exception_CMS(Exception_CMS::INVALID_EMAIL);
        }

        if (!$args['name'] || !$args['text'] || !$args['user'] || ($checkmail&!$args['email'])) 
            throw new Exception_CMS(Exception_CMS::INVALID_PARAMS);
        
        
        $args['text'] = $this->replace($args['text']);
                        
        //$r = fssql_query("select count(id) from forums where (parent=$themeid)and(text='".mysql_escape_string($args['text'])."')and(name='".mysql_escape_string($args['name'])."')");
        //if (mysql_result($r,0)) throw new Exception_CMS(Exception_CMS::MESSAGE_EXISTS);
        
        $alias = '';
        if ($user->id == USER_OPENID) $alias = $user->openid;
        if ($user->id == USER_FACEBOOK) $alias = $user->facebook_id;
        
        fssql_query("
            INSERT INTO forums SET
            name='".mysql_escape_string($args['name'])."',
            text='".mysql_escape_string($args['text'])."',
            username='".mysql_escape_string($args['user'])."',
            email='".mysql_escape_string($args['email'])."',
            autor=".$user->id.",
            alias='".mysql_escape_string($alias)."',
            parent=$themeid,
            thread=$thread,
            idcat=".$this->id.",
            dat=NOW(),
            ip=".ip2long(getenv('REMOTE_ADDR')).",
            type=".($this->userAllowAccess(PERM_FORUM_MOD_POST, $user)?0:1));
        
        if (!mysql_error()) {
            $postid = mysql_insert_id();
            $p = $themeid;
            do {
                $themeid = $p;
                $r = fssql_query('SELECT parent FROM forums WHERE id='.$themeid);
                $p = mysql_result($r,0);
            } while($p>0);
            fssql_query("update forums set last_post=".$postid.", answers=answers+1,lastanswer=NOW() where id=$themeid");
        }
        
        return $postid;
    }
    
    /*
    * $cleanup - удалять тему, если не осталось постов
    */
    public function deletePost($id, $user, $hide = 0, $cleanup = 0)
    {
    	$id = (int)$id;
      	$r = fssql_query("SELECT autor, parent FROM forums WHERE id=$id");
    	if (!mysql_num_rows($r)) throw new Exception_CMS(Exception_CMS::MATERIAL_NOT_FOUND);
    	list($msguserid,$parent) = mysql_fetch_row($r);
    	
    	if (!$hide) {
    	   $r = fssql_query('SELECT count(*) FROM forums WHERE parent='.$id);
    	   if (mysql_result($r, 0)>0) throw new Exception_CMS(Exception_CMS::NO_RIGHTS);
    	}
      
        if ($user) {
            if (!is_object($user)) $user = User::getById((int)$user);
            if (!is_object($user)) throw new Exception_CMS(Exception_CMS::INVALID_PARAMS);
        } else {
            $user = new UserAnonymous();
        }
    	
    	$can_edit_own = $this->userAllowAccess(PERM_CAT_OWN_MAT, $user);
    	$can_edit_all = $this->userAllowAccess(PERM_CAT_ALL_MAT, $user);
      
      if (!($can_edit_all || ($can_edit_own && $user->id==$msguserid))) throw new Exception_CMS(Exception_CMS::NO_RIGHTS);

    	if ($hide)
    	    fssql_query("UPDATE forums SET type=0 WHERE id=".$id);
          else fssql_query("DELETE FROM forums WHERE id=".$id);
      while ($parent) {  
            fssql_query("UPDATE forums SET answers=answers-1 WHERE id=".$parent);
            $r = fssql_query("SELECT parent FROM forums WHERE id=$parent");
            if (!mysql_result($r,0)) {
                if ($cleanup) {
                    $r = fssql_query('SELECT id FROM forums WHERE parent='.$parent);
                    if (!mysql_num_rows($r)) fssql_query('DELETE FROM forums WHERE id='.$parent);
                }
                break;
            }  	
            $parent = mysql_result($r,0);
    	}
    }
    
    public function editPost($id,$user,$args)
    {
    	  $id = (int)$id;
      	$r = fssql_query("SELECT autor FROM forums WHERE id=$id");
    	  if (!mysql_num_rows($r)) throw new Exception_CMS(Exception_CMS::MATERIAL_NOT_FOUND);
    	  $msguserid = mysql_result($r,0);
      
        if ($user) {
            if (!is_object($user)) $user = User::getById((int)$user);
            if (!is_object($user)) throw new Exception_CMS(Exception_CMS::INVALID_PARAMS);
        } else {
            $user = new UserAnonymous();
        }
      
        if ($user->id > 0) {
    	     $can_edit_own = $this->userAllowAccess(PERM_CAT_OWN_MAT, $user);
    	     $can_edit_all = $this->userAllowAccess(PERM_CAT_ALL_MAT, $user);
        } else {
            $can_edit_own = false;
            $can_edit_all = false;
        }
    	  if (!($can_edit_all || ($can_edit_own && $user->id==$msguserid))) throw new Exception_CMS(Exception_CMS::NO_RIGHTS);
    
      	$args['text'] = nl2br(htmlspecialchars($args['text']));
      	$args['name'] = nl2br(htmlspecialchars($args['name']));
    	  if (!$args['name']||!$args['text']) throw new Exception_CMS(Exception_CMS::INVALID_PARAMS);
        
        $args['text'] = $this->replace($args['text']);

    	  fssql_query("UPDATE forums SET name='".mysql_escape_string($args['name'])."', text='".mysql_escape_string($args['text'])."' where id=".$id);
    }
}
