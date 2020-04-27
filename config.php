<?php

/**
 * 
 *
 * @version $Id: config.php,v 1.2 2006/04/15 09:16:17 cetera Exp $
 * @copyright 2005 
 **/

define('GROUP_MODERATOR', -101);
define('FORUMS_TYPE', 'forums');
define('PERM_FORUM_CREATE_THEME', 101);
define('PERM_FORUM_CREATE_POST', 102);
define('PERM_FORUM_MOD_THEME', 103);
define('PERM_FORUM_MOD_POST', 104);

$this->addUserGroup(array(
    'id' => GROUP_MODERATOR,
    'name' => $translator->_('Модераторы'),
    'describ' => $translator->_('Имеют доступ к модерированию комментариев'),
));

if ($this->getBo()&& $this->getUser() && ($this->getUser()->hasRight(GROUP_MODERATOR) || $this->getUser()->allowAdmin())) {

		$this->getBo()->addModule(array(
  			'id'	     => 'forums',
  			'position' => MENU_SITE,
        'name' 	   => $translator->_('Форумы'),
        'icon'     => '/cms/plugins/forum/images/icon.gif',
        'class'    => 'Plugin.forum.Panel'
		));
    
}

$t = $this->getTranslator();
$t->addTranslation(__DIR__ . '/lang');
$this->getTwig()->getLoader()->addPath(__DIR__ . '/pages');

try {
    $od = \Cetera\ObjectDefinition::findByAlias('forum_topics');
    $od->registerClass($od->id, '\Forum\Topic');
    $od = \Cetera\ObjectDefinition::findByAlias('forum_posts');
    $od->registerClass($od->id, '\Forum\Post');

    \Cetera\Material::addPlugin('\Forum\Plugin');

    $this->registerWidget(array(
        'name' => 'Forum.Category',
        'class' => '\\Forum\\WidgetCategory',
        'not_placeable' => true
    ));

    $this->registerWidget(array(
        'name' => 'Forum.Topic.Add',
        'class' => '\\Forum\\WidgetTopicAdd',
        'not_placeable' => true
    ));

    $this->registerWidget(array(
        'name' => 'Forum.Topic.List',
        'class' => '\\Forum\\WidgetTopicList',
        'not_placeable' => true
    ));

    $this->registerWidget(array(
        'name' => 'Forum.Topic.Item',
        'class' => '\\Forum\\WidgetTopicItem',
        'not_placeable' => true
    ));

    $this->registerWidget(array(
        'name' => 'Forum.Post.Add',
        'class' => '\\Forum\\WidgetPostAdd',
        'not_placeable' => true
    ));

    $this->registerWidget(array(
        'name' => 'Forum.Post.List',
        'class' => '\\Forum\\WidgetPostList',
        'not_placeable' => true
    ));

    // Добавим группу пользователей
    define('GROUP_FORUM', -200);

    $this->addUserGroup(array(
        'id' => GROUP_FORUM,
        'name' => $t->_('Модераторы форума'),
        'describ' => '',
    ));

} catch (\Exception $e) {
}