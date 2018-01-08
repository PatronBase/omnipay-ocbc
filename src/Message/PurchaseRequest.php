<?php

namespace Omnipay\OCBC\Message;

use Guzzle\Http\Message\Response as HttpResponse;
use Omnipay\Common\Message\AbstractRequest;

/**
 * OCBC Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{
    /**
     * @var string  API endpoint base to connect to in production mode
     */
    protected $liveEndpoint = 'https://api.ocbc.com/transactional/merchantcardpayments/1.0';
    /**
     * @var string  API endpoint base to connect to in test mode
     */
    protected $testEndpoint = 'https://api.ocbc.com:8243/transactional/merchantcardpayments/1.0';

    /**
     * The HTTP method used to send data to the API endpoint
     *
     * @return string
     */
    public function getHttpMethod()
    {
        return 'POST';
    }

    /**
     * Get the stored access token
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->getParameter('accessToken');
    }

    /**
     * Set the stored access token
     *
     * @param string $value  Access token to store
     */
    public function setAccessToken($value)
    {
        return $this->setParameter('accessToken', $value);
    }

    /**
     * Get the stored merchant ID
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    /**
     * Set the stored merchant ID
     *
     * @param string $value  Merchant ID to store
     */
    public function setMerchantId($value)
    {
        return $this->setParameter('merchantId', $value);
    }

    /**
     * Get the stored merchant password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->getParameter('password');
    }

    /**
     * Set the stored merchant password
     *
     * @param string $value  Merchant password to store
     */
    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }

    /**
     * Get the stored customer ID
     *
     * @return string
     */
    public function getCustomerId()
    {
        return $this->getParameter('customerId');
    }

    /**
     * Set the stored customer ID
     *
     * @param string $value  Customer ID to store
     */
    public function setCustomerId($value)
    {
        return $this->setParameter('customerId', $value);
    }

    /**
     * Set up the base data for a purchase request
     *
     * Note: the gateway reference field 'transactionId' is omitted as it does not exist yet
     *
     * @return mixed[]
     */
    public function getData()
    {
        $this->validate('amount', 'card');

        $card = $this->getCard();

        $data = array(
            'merchantAccountNo' => $this->getMerchantId(),
            'merchantPassword' => $this->getPassword(),
            'cardHolderName' => $card->getName(),
            'cardNo' => $card->getNumber(),
            'cardCvc' => $card->getCvv(),
            'cardExpMm' => $card->getExpiryDate('m'),
            'cardExpYy' => $card->getExpiryDate('y'),
            'amount' => $this->getAmount(),
            'merchantTranId' => $this->getTransactionId(),
            'txnDesc' => $this->getDescription(),
            'customerId' => $this->getCustomerId(),
        );

        return $data;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return ($this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint).'/sale';
    }

    /**
     * Make the actual request to OCBC
     *
     * @param mixed $data  The data to encode and send to the API endpoint
     *
     * @return HttpResponse  HTTP response object
     */
    public function sendRequest($data)
    {
        $config = $this->httpClient->getConfig();
        $curlOptions = $config->get('curl.options');
        $curlOptions[CURLOPT_SSLVERSION] = 6;
        $config->set('curl.options', $curlOptions);
        $this->httpClient->setConfig($config);

        // don't throw exceptions for 4xx errors
        $this->httpClient->getEventDispatcher()->addListener(
            'request.error',
            function ($event) {
                if ($event['response']->isClientError()) {
                    $event->stopPropagation();
                }
            }
        );

        $httpRequest = $this->httpClient->createRequest(
            $this->getHttpMethod(),
            $this->getEndpoint(),
            null,
            json_encode($data)
        );

        $httpResponse = $httpRequest
            ->setHeader('Authorization', 'Bearer '.$this->getAccessToken())
            ->setHeader('Content-type', 'application/json')
            ->send();

        return $httpResponse;
    }
    
    /**
     * Send the request to the API then build the response object
     *
     * @param mixed $data  The data to encode and send to the API endpoint
     *
     * @return PurchaseResponse
     */
    public function sendData($data)
    {
        $httpResponse = $this->sendRequest($data);

        return $this->response = new PurchaseResponse($this, $httpResponse);
    }
}
