<?php
namespace Forum;

class WidgetPostList extends \Cetera\Widget\Templateable
{
    use \Cetera\Widget\Traits\Material;
    use Traits\Output;

    protected $_posts = null;
    protected $_form = null;

    protected function initParams()
    {
        $this->_params = array(
            'template' => 'default.twig',
            'material' => 0,
            'catalog' => 0,
            'material_alias' => null,
            'material_type' => 0,
            'material_id' => 0,
            'ajax' => false,
            'order' => 'dat',
            'sort' => 'ASC',
            'limit' => 0,
            'page' => null,
            'page_param' => 'page',
            'paginator' => true,
            'paginator_url' => '{material}?page={page}',
            'paginator_template' => false,
            'rating' => false,

            'forum_catalog' => 0,
            'posts_catalog' => 0,

            'form' => true,
            'form_template' => null,
            'form_publish' => 1,
            'form_rating_text' => $this->t->_('Оцените материал'),
            'form_title' => '<h3>' . $this->t->_('Добавить комментарий') . '</h3>',
            'form_submit_text' => $this->t->_('Отправить сообщение'),
            'form_success_text' => $this->t->_('Ваш комментарий принят'),
            'recaptcha_use' => $this->getParam('recaptcha_use'),
            'recaptcha_site_key' => $this->getParam('recaptcha_site_key'),
            'recaptcha_secret_key' => $this->getParam('recaptcha_secret_key'),
        );
    }

    public function getWidgetTitle()
    {
        return str_replace('{count}', $this->getPosts()->getCountAll(), $this->widgetTitle);
    }

    public function getPosts()
    {
        if (!$this->_posts) {
            $m = $this->getMaterial(false);
            if ($m) {
                $this->_posts = $m->getPosts();
            } else {
                $this->_posts = \Forum\Post::getObjectDefinition()->getMaterials();
            }
            $this->_posts->orderBy($this->getParam('order'), $this->getParam('sort'), false);
            if ($this->getParam('limit')) $this->_posts->setItemCountPerPage($this->getParam('limit'));
            $this->_posts->setCurrentPageNumber($this->getPage());
        }
        return $this->_posts;
    }

    public function getPage()
    {
        $p = null;
        if ($this->getParam('page')) $p = (int)$this->getParam('page');
        if (isset($_REQUEST[$this->getParam('page_param')])) $p = (int)$_REQUEST[$this->getParam('page_param')];
        if (!$p) $p = 1;
        return $p;
    }

    public function getPaginator()
    {
        if (!$this->getParam('paginator')) return;

        return $this->application->getWidget('Paginator', array(
            'iterator' => $this->getPosts(),
            'url' => str_replace('{material}', $this->getMaterial()->url, $this->getParam('paginator_url')),
            'template' => $this->getParam('paginator_template'),
        ))->getHtml();
    }

    public function getForm()
    {
        if ($this->_form === null) {
            if (!$this->getParam('form')) {
                $this->_form = '';
            } else {
                $this->_form = $this->application->getWidget('Forum.Post.Add', array(
                    'forum_catalog' => $this->getParam('forum_catalog'),
                    'posts_catalog' => $this->getParam('posts_catalog'),
                    'material' => $this->getMaterial(),
                    'ajax' => $this->getParam('ajax'),
                    'widgetTitle' => $this->getParam('form_title'),
                    'publish' => $this->getParam('form_publish'),
                    'rating' => $this->getParam('rating'),
                    'rating_text' => $this->getParam('form_rating_text'),
                    'submit_text' => $this->getParam('form_submit_text'),
                    'success_text' => $this->getParam('form_success_text'),
                    'template' => $this->getParam('form_template') ? $this->getParam('form_template') : 'default.twig',
                    'recaptcha_use' => $this->getParam('recaptcha_use'),
                    'recaptcha_site_key' => $this->getParam('recaptcha_site_key'),
                    'recaptcha_secret_key' => $this->getParam('recaptcha_secret_key'),
                ))->getHtml();
            }
        }
        return $this->_form;
    }

    protected function _getHtml()
    {
        $this->getForm();

        $material = $this->getMaterial(false);
        if ($material) {
            $this->setParam('material_type', $material->objectDefinition->id);
            $this->setParam('material_id', $material->id);
        }
        $this->setParam('material', null);
        $this->setParam('catalog', null);
        $this->setParam('material_alias', null);

        return parent::_getHtml();
    }

}
