<?php
$application->connectDb();
$application->initSession();
$application->initPlugins();
$application->setFrontOffice();

if (isset($_REQUEST['action']))
    $action = $_REQUEST['action'];

if ($action == 'post_get') {
    $postId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
    if ($postId) {
        $post = \Forum\Post::getById($postId, \Forum\Post::getObjectDefinition());
        print json_encode($post->text);
    }
    die();
}

if ($action == 'topic_get') {
    $topicId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
    if ($topicId) {
        $topic = \Forum\Topic::getById($topicId, \Forum\Topic::getObjectDefinition());
        print json_encode($topic->text);
    }
    die();
}

if ($action == 'topic_subscribe') {
    $user = $application->getUser();
    $topicId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
    if ($user && $topicId) {
        $res = Forum\Subscription\Topic::addSubscriber($user->id, $topicId);
        print json_encode($res);
    } else {
        print json_encode(false);
    }
    die();
}

if ($action == 'topic_unsubscribe') {
    $user = $application->getUser();
    $topicId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
    if ($user && $topicId) {
        $res = Forum\Subscription\Topic::delSubscriber($user->id, $topicId);
        print json_encode($res);
    } else {
        print json_encode(false);
    }
    die();
}

if ($action == 'category_subscribe') {
    $user = $application->getUser();
    $categoryId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
    if ($user && $categoryId) {
        $res = Forum\Subscription\Category::addSubscriber($user->id, $categoryId);
        print json_encode($res);
    } else {
        print json_encode(false);
    }
    die();
}

if ($action == 'category_unsubscribe') {
    $user = $application->getUser();
    $categoryId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
    if ($user && $categoryId) {
        $res = Forum\Subscription\Category::delSubscriber($user->id, $categoryId);
        print json_encode($res);
    } else {
        print json_encode(false);
    }
    die();
}
