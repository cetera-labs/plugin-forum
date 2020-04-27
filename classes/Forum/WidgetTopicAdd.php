<?php
namespace Forum;

class WidgetTopicAdd extends \Cetera\Widget\Templateable
{
    use \Cetera\Widget\Traits\Catalog;
    use Traits\Input;

    public $statusText = '';
    public $errorText = '';
    public $addedTopic = '';

    protected function initParams()
    {
        $this->_params = array(
            'catalog' => 0,
            'publish' => 1,
            'submit_text' => $this->t->_('Создать тему'),
            'success_text' => $this->t->_('Тема успешно создана'),
            'template' => 'default.twig',
        );
    }

    public function getHiddenFields()
    {
        $str = '<input type="hidden" name="topic-add" value="' . $this->getUniqueId() . '" />';
        return $str;
    }

    protected function _getHtml()
    {
        if (isset($_REQUEST['topic-add'])) {
            try {
                $res = Topic::addTopic(
                    htmlspecialchars($_REQUEST['name']),
                    htmlspecialchars($_REQUEST['text']),
                    $this->getCatalog()->id,
                    $this->application->getUser(),
                    $this->getParam('publish')
                );
                $this->statusText = $this->getParam('success_text');
                $this->addedTopic = $res;

                Subscription\Category::notify($res->catalog);
            } catch (\Exception $e) {
                $this->errorText = $e->getMessage();
            }
        }

        return parent::_getHtml();
    }
}