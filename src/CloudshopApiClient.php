<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\yii2\cloudshopApiClient;

use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
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
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->email || !$this->password) {
            throw new InvalidConfigException("Need email or password");
        }

        return parent::init();
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getAccessCredentials()
    {
        $client = new Client([
            'requestConfig' => [
                'format' => Client::FORMAT_JSON
            ]
        ]);

        $request = $client->createRequest()
            ->setMethod("GET")
            ->setUrl($this->base_api_url . "/profile")
            ->addHeaders(['Authorization' => 'Basic '.base64_encode($this->email.":".$this->password)])
            ->setOptions([
                'timeout'      => $this->request_timeout,
                'maxRedirects' => $this->request_maxRedirects,
            ]);

        $response = $request->send();

        if (!$response->isOk) {
            print_r($response->data);
        }
    }
}