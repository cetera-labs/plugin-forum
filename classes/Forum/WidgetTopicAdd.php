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

            'recaptcha_use' => $this->getParam('recaptcha_use'),
            'recaptcha_site_key' => $this->getParam('recaptcha_site_key'),
            'recaptcha_secret_key' => $this->getParam('recaptcha_secret_key'),
        );
    }

    public function getHiddenFields()
    {
        $str = '<input type="hidden" name="topic-add" value="' . $this->getUniqueId() . '" />';
        return $str;
    }

    public function showRecaptcha()
    {
        return $this->getParam('recaptcha_use') && !$this->getParam('ajax') && $this->getParam('recaptcha_site_key') && $this->getParam('recaptcha_secret_key');
    }

    protected function init()
    {
        if ($this->showRecaptcha()) {
            $this->application->addScript('https://www.google.com/recaptcha/api.js?render='.$this->getParam('recaptcha_site_key'));
        }
    }

    protected function _getHtml()
    {
        if (isset($_REQUEST['topic-add'])) {
            try {

                if ($this->showRecaptcha()) {
                    $client = new \GuzzleHttp\Client();
                    $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                        'form_params' => [
                            'secret' => $this->getParam('recaptcha_secret_key'),
                            'response' => $_REQUEST['gRecaptchaResponse'],
                            'remoteip' => $_SERVER['REMOTE_ADDR'],
                        ]
                    ]);
                    $res = json_decode($response->getBody(), true);
                    if (!$res['success']) {
                        throw new \Exception($this->t->_('Проверка не пройдена'));
                    }
                }


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
