<?php
namespace Forum\Subscription;

interface SubscriptionInterface
{
    public static function getSubscribers($item);

    public static function getEntities($user_id);

    public static function hasSubscriber($user_id, $item_id);

    public static function addSubscriber($user_id, $item_id);

    public static function delSubscriber($user_id, $item_id);

    public static function notify($item);
}
