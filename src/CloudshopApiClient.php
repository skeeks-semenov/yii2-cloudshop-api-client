<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\yii2\cloudshopApiClient;

use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;

/**
 * @property array $accessCredentials
 * @property string $accessToken
 * @property string $accessCompany
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class CloudshopApiClient extends Component
{
    /**
     * @var string
     */
    public $base_api_url = "https://api.cloudshop.ru";

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $password;

    /**
     * @var int
     */
    public $request_timeout = 5;

    /**
     * @var int
     */
    public $request_maxRedirects = 2;

    /**
     * @var string
     */
    public $cache_key = 'cloudshop_access';

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->email || !$this->password) {
            //throw new InvalidConfigException("Need email or password");
        }

        return parent::init();
    }

    /**
     * Получение данных авторизации из апи
     *
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    protected function _getAccessCredentialsFromApi()
    {
        $client = new Client([
            'requestConfig' => [
                'format' => Client::FORMAT_JSON
            ]
        ]);

        $request = $client->createRequest()
            ->setMethod("GET")
            ->setUrl($this->base_api_url . "//profile")
            ->addHeaders(['Authorization' => 'Basic '.base64_encode($this->email.":".$this->password)])
            ->setOptions([
                'timeout'      => $this->request_timeout,
                'maxRedirects' => $this->request_maxRedirects,
            ]);

        $response = $request->send();

        if (!$response->isOk) {
            throw new Exception("Error request:" . $response->content);
        }

        return (array) $response->data;
    }


    /**
     * @return array
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getAccessCredentials()
    {
        $data = \Yii::$app->cache->get($this->cache_key);
        
        if ($data === false) {
            $data = $this->_getAccessCredentialsFromApi();

            if (!$data || $data['error']) {
                throw new Exception("Ошибка получения ключа доступа к апи: " . print_r($data, true) . " Пользователь: " . $this->email);
            }

            \Yii::$app->cache->set($this->cache_key, $data, 3600*24);
        }

        return (array) $data;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        $data = $this->accessCredentials;
        return (string) ArrayHelper::getValue($data, 'data.0.token');
    }
    
    /**
     * @return string
     */
    public function getAccessCompany()
    {
        $data = $this->accessCredentials;
        return (string) key($data['data'][0]['positions']);
    }

    /**
     * @param string $api_method
     * @param string $request_method
     * @return \yii\httpclient\Request
     * @throws InvalidConfigException
     */
    protected function _createApiRequest(string $api_method, string $request_method = "GET") {
        $client = new Client([
            'requestConfig' => [
                'format' => Client::FORMAT_JSON
            ]
        ]);

        $request = $client->createRequest()
            ->setMethod("GET")
            ->setUrl($this->base_api_url . $api_method)
            ->addHeaders(['Authorization' => 'Bearer ' . $this->accessToken])
            ->setOptions([
                'timeout'      => $this->request_timeout,
                'maxRedirects' => $this->request_maxRedirects,
            ]);

        return $request;
    }

    /**
     * Данные профиля
     * 
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getProfileApiMethod()
    {
        $response = $this->_createApiRequest("/profile")->send();
        return (array) $response->data;
    }
    
    /**
     * Данные по складам
     * 
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getStoresApiMethod()
    {
        $method = "/data/" . $this->accessCompany . "/stores";
        $response = $this->_createApiRequest($method)->send();
        return (array) $response->data;
    }
    
    /**
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getAccountsApiMethod()
    {
        $method = "/data/" . $this->accessCompany . "/accounts";
        $response = $this->_createApiRequest($method)->send();
        return (array) $response->data;
    }
    
    /**
     * @return array
     * @throws InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getCatalogApiMethod($filters = [])
    {
        $method = "/data/" . $this->accessCompany . "/catalog";
        if ($filters) {
            $method .= "?" . http_build_query($filters);
        }
        $response = $this->_createApiRequest($method)->send();
        return (array) $response->data;
    }
}