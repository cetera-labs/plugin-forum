<?php
namespace Forum\Subscription;

class Topic implements SubscriptionInterface
{

    use \Cetera\DbConnection;
    use \Cetera\Translator;

    /*
    * @todo проверка активности пользователей
    * @todo типы уведомлений (на крон): Каждый раз, Каждый день, Каждую неделю
    */

    const TABLE = 'forum_subscription_topic';
    const ENTITY_COL = 'topic_id';

    public static function addSubscriber($user_id, $entity_id)
    {
        return self::getDbConnection()->insert(self::TABLE, array('user_id' => $user_id, self::ENTITY_COL => $entity_id));
    }

    public static function delSubscriber($user_id, $entity_id)
    {
        return self::getDbConnection()->delete(self::TABLE, array('user_id' => $user_id, self::ENTITY_COL => $entity_id));
    }

    public static function hasSubscriber($user_id, $entity_id)
    {
        $res = self::getDbConnection()->fetchArray('SELECT id FROM ' . self::TABLE . ' WHERE user_id=? AND ' . self::ENTITY_COL . '=?', array($user_id, $entity_id));
        $data = (bool)$res;
        return $data;
    }

    /*
     * return \Cetera\Iterator\User by entity id
     */
    public static function getSubscribers($entity_id)
    {
        return \Cetera\User::enum()->where('id IN (SELECT user_id FROM ' . self::TABLE . ' WHERE ' . self::ENTITY_COL . '=' . $entity_id . ')');
    }

    /*
     * return \Cetera\Iterator\Material by user id
     */
    public static function getEntities($user_id)
    {

    }

    public static function notify($topic)
    {
        if (!($topic instanceof \Forum\Topic)) {
            if (is_numeric($topic))
                $topic = \Forum\Topic::getById($topic);
            else
                throw new \Exception(self::t()->_('Параметр должен являться объектом класса \Forum\Topic или числом'));
        }

        $subscribers = self::getSubscribers($topic->id);
        if ($subscribers) {
            foreach ($subscribers as $subscriber) {
                $mailEventParams = array(
                    'subscriber' => $subscriber,
                    'server' => \Cetera\Application::getInstance()->getServer(),
                    'topic' => $topic,
                );
                $res = \Cetera\Event::trigger('FORUM_TOPIC_SUBSCRIBE', $mailEventParams);
            }
        }
    }

}
