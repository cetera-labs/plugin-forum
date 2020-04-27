<?php
namespace Forum;

class WidgetTopicItem extends \Cetera\Widget\Material
{
    use Traits\Output;

    protected $_params = array(
        'template' => 'default.twig',
        'posts_limit' => 0,
    );

    public function hasSubscriber()
    {
        $user = $this->application->getUser();
        $m = $this->getMaterial();
        return Subscription\Topic::hasSubscriber($user->id, $m->id);
    }

}