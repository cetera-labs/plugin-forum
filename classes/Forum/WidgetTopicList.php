<?php
namespace Forum;

class WidgetTopicList extends \Cetera\Widget\Templateable
{

    use \Cetera\Widget\Traits\Catalog;
    use \Cetera\Widget\Traits\Paginator;

    protected $_form = null;

    protected function initParams()
    {
        $this->_params = array(
            'catalog' => 0,
            'iterator' => null,
            'where' => null,
            'limit' => 0,
            'page' => null,
            'page_param' => 'page',
            'order' => 'dat_update',
            'sort' => 'DESC',
            'paginator' => true,
            'paginator_template' => false,
            'paginator_url' => '{catalog}?page={page}',
            'template' => 'default.twig',

            'form' => true,
            'form_template' => null,
            'form_title' => '<h3>' . $this->t->_('Создать тему') . '</h3>',
            'form_submit_text' => $this->t->_('Создать тему'),
            'form_success_text' => $this->t->_('Тема успешно создана'),
			'recaptcha_use' => $this->getParam('recaptcha_use'),
            'recaptcha_site_key' => $this->getParam('recaptcha_site_key'),
            'recaptcha_secret_key' => $this->getParam('recaptcha_secret_key'),
        );
    }

    /**
     * Список тем для показа
     */
    public function getChildren()
    {
        if (!$this->_children) {
            if ($this->getParam('iterator')) {
                $this->_children = $this->getParam('iterator');
            } else {
                $cat = $this->getCatalog(false);
                if ($cat) $this->_children = $cat->getMaterials()->subfolders();
                else $this->_children = \Forum\Topic::getObjectDefinition()->getMaterials();

                $this->_children->orderBy($this->getParam('order'), $this->getParam('sort'));
            }
            if ($this->getParam('limit')) $this->_children->setItemCountPerPage($this->getParam('limit'));
            if ($this->getParam('where')) $this->_children->where($this->getParam('where'));
            $this->_children->setCurrentPageNumber($this->getPage());
        }
        return $this->_children;
    }

    public function getForm()
    {
        if ($this->_form === null) {
            if (!$this->getParam('form')) {
                $this->_form = '';
            } else {
                $this->_form = $this->application->getWidget('Forum.Topic.Add', array(
                    'publish' => $this->getParam('form_publish'),
                    'widgetTitle' => $this->getParam('form_title'),
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

        return parent::_getHtml();
    }

    public function sliceString($string, $size, $ending = '', $encode = true)
    {
        $decodedString = html_entity_decode(strip_tags($string));
        if (mb_strlen($decodedString) > $size) {
            $substr = mb_substr($decodedString, 0, $size);
            if ($encode)
                $substr = htmlentities($substr);
            if ($ending)
                $substr .= $ending;
            return $substr;
        } else {
            if ($encode)
                return $string;
            else
                return $decodedString;
        }
    }

}