<?php
namespace Forum;

class Topic extends \Cetera\Material
{

    use \Cetera\Translator;

    private static $_od = null;

    public static function getById($id, $type = 0, $table = NULL)
    {
        return parent::getById($id, self::getObjectDefinition());
    }

    public static function getObjectDefinition()
    {
        if (!self::$_od) self::$_od = \Cetera\ObjectDefinition::findByAlias('forum_topics');
        return self::$_od;
    }

    public function getNickname()
    {
        if ($this->autor->name) return $this->autor->name;
        if ($this->autor->login) return $this->autor->login;
    }

    public function getAvatar()
    {
        return '/plugins/forum/images/user.png';
    }

    public function getPostsCount()
    {
        return $this->getPosts()->getCountAll();
    }

    public function getPosts()
    {
        return \Forum\Post::getObjectDefinition()->getMaterials()
            ->where('topic_id = ' . $this->id)
            ->orderBy('dat', 'ASC');
    }

    public function addPost($text, $idcat = 0, $publish = true, $user = null, $nickname = null, $rating = null)
    {

        if (!$user) $user = \Cetera\Application::getInstance()->getUser();

        if (!$user) {
            $user = new \Cetera\User\Anonymous();
        } else {
            if ($user->name)
                $nickname = $user->name;
            else $nickname = $user->login;
        }

        if (!$nickname) $nickname = $user->login;

        $name = trim($text);
        if (strlen($name) > 99) {
            $name = substr($name, 0, 96);
            $name .= '...';
        }

        if (!$idcat) {
            $statement = \Cetera\Application::getInstance()->getConn()->executeQuery('SELECT default_value FROM types_fields WHERE id = ? AND name = ?', array(\Forum\Post::getObjectDefinition()->id, 'idcat'));
            $idcat = reset($statement->fetch());
            if (!$idcat)
                throw new \Exception(self::t()->_('Не задан каталог по умолчанию для комментариев форума'));
        }

        $m = \Cetera\Material::fetch(array(
            'name' => $name,
            'text' => $text,
            'idcat' => $idcat,
            'autor' => $user->id,
            'publish' => $publish,
            'topic_id' => $this->id,
            'nickname' => $nickname,
            'rating' => $rating,
            'ip' => $_SERVER['REMOTE_ADDR'],
        ), \Forum\Post::getObjectDefinition());

        $m->save();

        return $m;

    }

    public static function addTopic($name, $text, $idcat, $user, $publish = true, $material_id = null, $material_type = null)
    {
        if (!$user)
            $user = new \Cetera\User\Anonymous();
        if (strlen($name) === 0)
            throw new \Exception(self::t()->_('Отсутствует название темы'));
        if (strlen($text) === 0)
            throw new \Exception(self::t()->_('Отсутствует содержание темы'));
        if (!$idcat)
            throw new \Exception(self::t()->_('Отсутствует раздел темы'));

        $m = \Cetera\Material::fetch(array(
            'name' => $name,
            'text' => $text,
            'idcat' => $idcat,
            'autor' => $user->id,
            'publish' => $publish,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'material_id' => $material_id,
            'material_type' => $material_type,
        ), \Forum\Topic::getObjectDefinition());

        $m->save();

        return $m;
    }

    public static function getByMaterial($id, $type)
    {
        return self::getObjectDefinition()->getMaterials()->where('material_id=' . $id, 'material_type=' . $type)->current();
    }

    public static function isTopic($type)
    {
        if (self::getObjectDefinition()->id == $type)
            return true;
        else
            return false;
    }

}