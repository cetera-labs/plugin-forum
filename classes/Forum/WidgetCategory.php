<?php
namespace Forum;

class WidgetCategory extends \Cetera\Widget\Section
{
    public $productObjectDefinition = null;
    public $iterator = null;
    public $order = 'dat_update';
    public $sort = 'desc';

    protected function initParams()
    {
        $this->_params = array(
            'template' => 'default.twig',
            'catalog' => null,

            'display_index' => false,

            'material_id' => 0,
            'material_alias' => null,
            'material_template' => null,

            'list_limit' => 10,
            'list_where' => null,
            'list_page' => null,
            'list_page_param' => 'page',
            'list_order' => 'dat_update',
            'list_sort' => 'DESC',
            'list_paginator' => true,
            'list_template' => null,
            'paginator_template' => null,

            'posts_limit' => 0,

            'page404_title' => $this->t->_('Страница не найдена'),
            'page404_template' => null,
        );
    }

    protected function init()
    {
        if (!$this->application->getUnparsedUrl()) {
            $this->iterator = $this->getCatalog()->getMaterials()->subfolders()->orderBy($this->order, $this->sort, true);
        }
    }

    public function hasSubscriber()
    {
        $user = $this->application->getUser();
        $c = $this->getCatalog();
        return Subscription\Category::hasSubscriber($user->id, $c->id);
    }

}