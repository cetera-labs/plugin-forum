<?php
namespace Forum;

class Post extends \Cetera\Material
{

    use \Cetera\Translator;

    protected static $_od = null;

    public static function getById($id, $type = 0, $table = NULL)
    {
        return parent::getById($id, self::getObjectDefinition());
    }

    public static function getObjectDefinition()
    {
        if (!self::$_od) {
            self::$_od = \Cetera\ObjectDefinition::findByAlias('forum_posts');
        }
        return self::$_od;
    }

    public function getNickname()
    {
        if ($this->fields['nickname']) return $this->fields['nickname'];
        if ($this->autor->name) return $this->autor->name;
        if ($this->autor->login) return $this->autor->login;
    }

    public function getAvatar()
    {
        return '/plugins/forum/images/user.png';
    }

}