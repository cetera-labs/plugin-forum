<?php
$a = \Cetera\Application::getInstance();
$s = $a->getServer();
$t = $a->getTranslator();
$conn = $a->getConn();
$forum_od = \Forum\Topic::getObjectDefinition()->id;
$posts_od = \Forum\Post::getObjectDefinition()->id;

// В случае существования раздела с указанным alias добавляем к alias метку времени
function createCatalog($s, $fields)
{
    try {
        $c = $s->getChildByAlias($fields['alias']);
    } catch (\Exception $e) {
        $c = false;
    }
    if ($c) $fields['alias'] .= time();

    return $s->createChild($fields);
}


if (!$forum_od || !$posts_od) throw new \Exception\CMS(Exception\CMS::CANT_CREATE);


$statement = $conn->executeQuery('SELECT id FROM dir_data WHERE typ = ?', array($forum_od));
$result = $statement->fetch();

// При наличии разделов с типом материалов forum_topics, устанавливаем id каталога в котором расположены темы форума (topic_id->len) для типа forum_comments раным id первого раздела с типом материалов forum_topics
// Иначе создаем раздел с типом материалов forum_topics, устанавливаем id каталога по умолчанию (topic_id->len) для типа forum_comments раным id созданного раздела
if ($result['id']) $forum_id = $result['id'];
else {
    $forum_id = createCatalog($s, array(
        'name' => $t->_('Форум'),
        'alias' => 'forum',
        'typ' => $forum_od,
        'type' => 290,
        'template' => 'page_forum.twig',
    ));

}
// Параметр сбрасывается при установке. Нужно устанавливать заново.
if ($forum_id) {
    $conn->executeUpdate('UPDATE types_fields SET len = ? WHERE id = ? AND name = ?', array($forum_id, $posts_od, 'topic_id'));
    $forum_cat = \Cetera\Catalog::getById($forum_id);
    $forum_cat->updatePermissions(array(
        5 => array(GROUP_FORUM),
        6 => array(GROUP_FORUM),
        7 => array(GROUP_FORUM),
        8 => array(GROUP_FORUM),
        9 => array(GROUP_FORUM)
    ));
}

// При наличии разделов с типом материалов forum_comments, устанавливаем id каталога по умолчанию (idcat->default_value) для типа forum_comments раным id первого раздела с типом материалов forum_comments
// Иначе создаем раздел с типом материалов forum_comments, устанавливаем id каталога по умолчанию (idcat->default_value) для типа forum_comments раным id созданного раздела
$statement = $conn->executeQuery('SELECT id FROM dir_data WHERE typ = ?', array($posts_od));
$result = $statement->fetch();

if ($result['id']) $post_id = $result['id'];
else {
    $post_id = createCatalog($s, array(
        'name' => $t->_('Комментарии форума'),
        'alias' => 'forum-posts',
        'typ' => $posts_od,
        'type' => 290,
        'template' => 'page_404.twig',
        'hidden' => 1,
    ));

}
// Параметр сбрасывается при установке. Нужно устанавливать заново.
if ($post_id) {
    $conn->executeUpdate('UPDATE types_fields SET default_value = ? WHERE id = ? AND name = ?', array($post_id, $posts_od, 'idcat'));
    $comment_cat = \Cetera\Catalog::getById($post_id);
    $comment_cat->updatePermissions(array(
        5 => array(GROUP_FORUM),
        6 => array(GROUP_FORUM),
        7 => array(GROUP_FORUM),
        8 => array(GROUP_FORUM),
        9 => array(GROUP_FORUM)
    ));
}
