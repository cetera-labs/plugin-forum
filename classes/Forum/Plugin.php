<?php
namespace Forum;

class Plugin extends \Cetera\ObjectPlugin
{

    use \Cetera\DbConnection;
    use \Cetera\Translator;

    public $_topic = false;

    public function getTopic()
    {
        if ($this->_topic === false)
            $this->_topic = Topic::getByMaterial($this->object->id, $this->object->objectDefinition->id);

        return $this->_topic;
    }

    public function getPostsCount()
    {
        return $this->getPosts()->getCountAll();
    }

    public function getPosts()
    {
        $topic = $this->getTopic();
        if ($topic !== NULL)
            return \Forum\Post::getObjectDefinition()->getMaterials()->where('topic_id = '.$this->getTopic()->id);
        else
            return \Forum\Post::getObjectDefinition()->getMaterials()->where('topic_id = -1');
    }

    public function addTopicByMaterial($forum_catalog, $topic_name, $topic_text)
    {
        $topic_od = \Forum\Topic::getObjectDefinition()->id;

        if (!$forum_catalog)
            throw new \Exception(self::t()->_('Не указан раздел форума'));
        $topic_cat = \Cetera\Catalog::getById($forum_catalog);
        if ($topic_cat->getMaterialsObjectDefinition()->id !== $topic_od)
            throw new \Exception(self::t()->_('Указанный раздел не принадлежит форуму'));
        /*
        try {
            $mtype_topic_cat = $topic_cat->getChildByAlias($this->object->objectDefinition->alias);
        }
        catch (\Exception $e) {
            $mtype_topic_cat = false;
        }
        if (!$mtype_topic_cat) {
            $mtype_topic_cat = $topic_cat->createChild(array(
                'name'          => $this->object->objectDefinition->description,
                'alias'         => $this->object->objectDefinition->alias,
                'typ'           => $topic_od,
                'type'          => 290,
            ));
        }
        */
        $topic = Topic::addTopic(
            $topic_name,
            $topic_text,
            $topic_cat->id,
            \Cetera\Application::getInstance()->getUser(),
            true,
            $this->object->id,
            $this->object->objectDefinition->id
        );

        return $topic;
    }

}