<?php
namespace Forum\Cache\Slot;

class Forum extends \Cetera\Cache\Slot\Slot {
    public function __construct($key) {
        parent::__construct("forum_{$key}", 3600 * 72);
    }
}