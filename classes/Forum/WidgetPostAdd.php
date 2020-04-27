<?php
namespace Forum;

class WidgetPostAdd extends \Cetera\Widget\Templateable
{
    use \Cetera\Widget\Traits\Material;
    use Traits\Input;

    public $statusText = '';
    public $errorText = '';

    protected function initParams()
    {
        $this->_params = array(
            'material' => 0,
            'catalog' => 0,
            'material_id' => 0,
            'material_type' => 0,
            'material_alias' => null,
            'publish' => 1,
            'ajax' => false,
            'rating' => true,
            'rating_text' => $this->t->_('Оцените материал'),
            'submit_text' => $this->t->_('Отправить сообщение'),
            'success_text' => $this->t->_('Ваш комментарий принят'),
            'template' => 'default.twig',

            'recaptcha' => false,
            'recaptcha_site_key' => null,
            'recaptcha_secret_key' => null,

            'forum_catalog' => 0,
            'posts_catalog' => 0,
            'name_field' => 'name',
            'text_field' => 'text',
        );
    }

    public function getHiddenFields()
    {
        $str = '<input type="hidden" name="post-add" value="' . $this->getUniqueId() . '" />';
        return $str;
    }

    public function showNicknameInput()
    {
        $user = $this->application->getUser();
        if ($user) return false;
        return true;
    }

    public function showRecaptcha()
    {
        return $this->getParam('recaptcha') && !$this->getParam('ajax') && $this->getParam('recaptcha_site_key') && $this->getParam('recaptcha_secret_key');
    }

    protected function init()
    {
        if ($this->showRecaptcha()) {
            $this->application->addScript('https://www.google.com/recaptcha/api.js');
        }
        if (!$this->getParam('forum_catalog')) {
            $statement = $this->application->getConn()->executeQuery('SELECT id FROM dir_data WHERE typ = ?', array($forum_od));
            $result = $statement->fetch();
            if ($result['id'])
                $this->setParam('forum_catalog', $result['id']);
        }
    }

    protected function _getHtml()
    {

        $material = $this->getMaterial();

        $this->setParam('material_type', $material->objectDefinition->id);
        $this->setParam('material_id', $material->id);
        $this->setParam('material', null);
        $this->setParam('catalog', null);
        $this->setParam('material_alias', null);

        if (isset($_REQUEST['post-add'])) {
            try {

                if ($this->showRecaptcha()) {
                    $client = new \GuzzleHttp\Client();
                    $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                        'form_params' => [
                            'secret' => $this->getParam('recaptcha_secret_key'),
                            'response' => $_REQUEST['g-recaptcha-response'],
                            'remoteip' => $_SERVER['REMOTE_ADDR'],
                        ]
                    ]);
                    $res = json_decode($response->getBody(), true);
                    if (!$res['success']) {
                        throw new \Exception($this->t->_('Проверка не пройдена'));
                    }
                }

                $rating = null;
                if ($this->getParam('rating') && isset($_REQUEST['rating'])) {
                    $rating = (int)$_REQUEST['rating'];
                }


                if (Topic::isTopic($material->objectDefinition->id)) {
                    $topic = $material;
                } else {
                    $topic = $material->getTopic();
                    if (!$topic) {
                        $topic = $material->addTopicByMaterial(
                            $this->getParam('forum_catalog'),
                            $material->{$this->getParam('name_field')},
                            $material->{$this->getParam('text_field')}
                        );
                    }

                }

                $res = $topic->addPost(
                    htmlspecialchars($_REQUEST['text']),
                    $this->getParam('posts_catalog'),
                    $this->getParam('publish'),
                    $this->application->getUser(),
                    htmlspecialchars(strip_tags($_REQUEST['nickname'])),
                    $rating
                );

                $this->statusText = $this->getParam('success_text');

                Subscription\Topic::notify($topic);
            } catch (\Exception $e) {
                $this->errorText = $e->getMessage();
            }
        }

        return parent::_getHtml();
    }
}