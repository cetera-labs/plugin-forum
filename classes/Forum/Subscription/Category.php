<?php
namespace Forum\Subscription;

class Category implements SubscriptionInterface
{

    use \Cetera\DbConnection;
    use \Cetera\Translator;

    /*
    * @todo проверка активности пользователей
    * @todo типы уведомлений (на крон): Каждый раз, Каждый день, Каждую неделю
    */

    const TABLE = 'forum_subscription_category';
    const ENTITY_COL = 'category_id';

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

    public static function getParentCategories($category)
    {
        if (!($category instanceof \Cetera\Catalog))
            throw new \Exception(self::t()->_('Параметр должен являться объектом класса \Cetera\Catalog'));
        $iterator = $category->path;
        $categories = [];
        foreach ($iterator as $cat)
            $categories[] = $cat->id;
        return $categories;
    }

    /*
     * return \Cetera\Iterator\User by entity id
     */
    public static function getSubscribers($category)
    {
        $categories = implode(',', self::getParentCategories($category));
        return \Cetera\User::enum()->where('id IN (SELECT user_id FROM ' . self::TABLE . ' WHERE ' . self::ENTITY_COL . ' IN (' . $categories . '))');
    }

    /*
     * return \Cetera\Iterator\Material by user id
     */
    public static function getEntities($user_id)
    {

    }

    public static function notify($category)
    {
        if (!($category instanceof \Cetera\Catalog)) {
            if (is_numeric($category))
                $category = \Cetera\Catalog::getById($category);
            else
                throw new \Exception(self::t()->_('Параметр должен являться объектом класса \Cetera\Catalog или числом'));
        }

        $subscribers = self::getSubscribers($category);
        if ($subscribers) {
            foreach ($subscribers as $subscriber) {
                $mailEventParams = array(
                    'subscriber' => $subscriber,
                    'server' => \Cetera\Application::getInstance()->getServer(),
                    'category' => $category,
                );
                $res = \Cetera\Event::trigger('FORUM_CATEGORY_SUBSCRIBE', $mailEventParams);
            }
        }
    }

}
