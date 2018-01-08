<?php

namespace Omnipay\OCBC;

use Omnipay\Common\AbstractGateway;

/**
 * OCBC JSON Gateway
 *
 * @link https://api.ocbc.com/store/site/pages/api_documentation.jag?name=Transactional_MerchantCardPayments
 */
class Gateway extends AbstractGateway
{
    /**
     * Name of the gateway
     *
     * @return string
     */
    public function getName()
    {
        return 'OCBC JSON';
    }

    /**
     * Setup the default parameters
     *
     * @return string[]
     */
    public function getDefaultParameters()
    {
        return array(
            'accessToken' => '',
            'merchantId' => '',
            'password' => '',
        );
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
     * Create purchase request
     *
     * @param array $parameters
     *
     * @return \Omnipay\OCBC\Message\PurchaseRequest
     */
    public function purchase(array $parameters = array())
    {
        // currency is always Singapore Dollars
        $parameters['currency'] = 'SGD';
        return $this->createRequest('\Omnipay\OCBC\Message\PurchaseRequest', $parameters);
    }
}
