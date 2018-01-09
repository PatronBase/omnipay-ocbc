<?php

namespace Omnipay\OCBC\Message;

use Guzzle\Http\Message\Response as HttpResponse;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;

/**
 * OCBC Purchase Response
 */
class PurchaseResponse extends AbstractResponse
{
    /**
     * @var HttpResponse  HTTP response object
     */
    public $response;

    /**
     * @var bool  Flag indicating whether the HTTP response object returned a '200 OK' HTTP header
     */
    protected $isHttpSuccess = false;

    /**
     * @var string  Status code from the response that determines success
     */
    protected $successStatus = 'S';

    /**
     * Constructor
     *
     * Also verifies that signature supplied in the response is valid
     *
     * @param PurchaseRequest $request   The initiating request
     * @param HttpResponse    $response  HTTP response object
     */
    public function __construct(PurchaseRequest $request, $response)
    {
        $this->response = $response;

        $code = $this->response->getStatusCode();
        $this->isHttpSuccess = $code == 200;

        $data = $response->getBody(true);

        if ($this->isHttpSuccess) {
            $data = json_decode($data, true);
            // case of 'results' node is inconsistent between documentation and experimentation, so handle both
            if (isset($data['results'])) {
                $data = $data['results'];
            } elseif (isset($data['Results'])) {
                $data = $data['Results'];
            }
        } else {
            $data = json_decode(json_encode($response->xml()->children('http://wso2.org/apimanager/security')), true);
        }

        parent::__construct($request, $data);

        if ($this->isHttpSuccess) {
            // Build and validate signature
            // 'Merchant Transaction Password'
            $merchantPassword = $request->getPassword();
            // 'Merchant Account No'
            $merchantAccountNo = $request->getMerchantId();
            // 'Merchant Transaction ID'
            $merchantTransactionId = null;
            // 'Transaction Amount'
            $transactionAmount = $request->getAmount();
            // 'Transaction ID'
            $transactionId = null;
            // 'Transaction Status'
            $transactionStatus = null;
            // 'Response Code'
            $responseCode = null;

            // make sure keys to build signature are in response
            $response_keys = array(
                'merchantTransactionId' => 'merchantTranId',
                'transactionId' => 'transactionId',
                'transactionStatus' => 'txnStatus',
                'responseCode' => 'responseCode',
            );
            foreach ($response_keys as $variable => $key) {
                if (isset($data[$key])) {
                    ${$variable} = $data[$key];
                }
                if (${$variable} === null) {
                    throw new InvalidResponseException('Invalid response from payment gateway (missing data)');
                }
            }
            // make sure variables to build signature were correctly fetched from the request
            $request_variables = array(
                'merchantPassword',
                'merchantAccountNo',
                'transactionAmount',
            );
            foreach ($request_variables as $variable) {
                if (${$variable} === null) {
                    throw new InvalidRequestException('Invalid request from merchant (missing data)');
                }
            }
            $signature_data = $merchantPassword
                .$merchantAccountNo
                .$merchantTransactionId
                .$transactionAmount
                .$transactionId
                .$transactionStatus
                .$responseCode;

            $signature = strtoupper(openssl_digest(strtoupper($signature_data), 'sha512'));

            if (!isset($this->data['txnSignature2']) || $signature != $this->data['txnSignature2']) {
                throw new InvalidResponseException('Invalid response from payment gateway (signature mismatch)');
            }
        }
    }

    /**
     * Is the response successful?
     *
     * Based on both HTTP status code and body content, since 200-level responses are JSON and 400/500-level are XML
     * For example see tests/Mock/ResponseSuccess.txt
     *
     * @return bool
     */
    public function isSuccessful()
    {
        // check HTTP response code first
        if (!$this->isHttpSuccess) {
            return false;
        }

        // check transaction status is JSON response if the HTTP response was successful
        return isset($this->data['txnStatus'])
            && $this->data['txnStatus'] == $this->successStatus
            && $this->getCode() === '0';
    }

    /**
     * What is the relevant description of the transaction response?
     *
     * @return string|null
     */
    public function getMessage()
    {
        // check for HTTP failure response first
        if (!$this->isHttpSuccess) {
            if (isset($this->data['message'])) {
                return $this->data['message'];
            }
            // no message available
            return null;
        }

        // check if descriptive message is available
        if (isset($this->data['responseDesc'])) {
            return $this->data['responseDesc'];
        }

        // use general transaction status (if unavailable would error in constructor)
        return $this->data['txnStatus'];
    }

    /**
     * @return string|null
     */
    public function getCode()
    {
        if (!$this->isHttpSuccess && isset($this->data['code'])) {
            return $this->data['code'];
        }
        if (isset($this->data['responseCode'])) {
            return $this->data['responseCode'];
        }
    }

    /**
     * @return string|null
     */
    public function getTransactionId()
    {
        if (isset($this->data['merchantTranId'])) {
            return $this->data['merchantTranId'];
        }
    }

    /**
     * @return string|null
     */
    public function getTransactionReference()
    {
        if (isset($this->data['transactionId'])) {
            return $this->data['transactionId'];
        }
    }
}
