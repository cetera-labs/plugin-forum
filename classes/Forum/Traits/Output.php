<?php
namespace Forum\Traits;

use Thunder\Shortcode\ShortcodeFacade;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

trait Output
{

    public static function processOutput($text)
    {
        $text = self::parseShortcode($text);
        $text = self::highlightLinks($text);
        return $text;
    }

    public static function highlightLinks($text)
    {
        $ignoredTags = '<a.*?\/a>(*SKIP)(*F)|<img.*?>(*SKIP)(*F)|<audio.*?\/audio>(*SKIP)(*F)|<video.*?\/video>(*SKIP)(*F)|<embed.*?>(*SKIP)(*F)|<iframe.*?\/iframe>(*SKIP)(*F)';
        $protocols = '((f|ht)tp(s)?://)';
        $path = '[-a-zа-я()0-9@:%_+.~#?&;//=]+';
        return preg_replace('!' . $ignoredTags . '|(' . $protocols . $path . ')!iu', '<a href="$1">$1</a>', $text);
    }

    public static function parseShortcode($text)
    {
        if (!is_string($text) || empty($text))
            return '';

        $facade = new ShortcodeFacade();

        $facade->addHandler('link', function (ShortcodeInterface $s) {
            return sprintf('<a href="%s">%s</a>', $s->getContent(), $s->getParameter('text', $s->getContent()));
        });

        $facade->addHandler('image', function (ShortcodeInterface $s) {
            return sprintf('<img src="%s"/>', $s->getContent());
        });

        $facade->addHandler('quote', function (ShortcodeInterface $s) {
            return sprintf('<blockquote>%s</blockquote>', $s->getContent());
        });

        $facade->addHandler('code', function (ShortcodeInterface $s) {
            return sprintf('<code>%s</code>', $s->getContent());
        });

        return $facade->process($text);
    }

}