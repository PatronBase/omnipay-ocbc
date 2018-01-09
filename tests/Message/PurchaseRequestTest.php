<?php

namespace Omnipay\OCBC\Message;

use Omnipay\Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    public function setUp()
    {
        $this->request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'merchantId' => '3333700088889990',
                'password' => 'Mvp7Kpkv',
                'amount' => '150.00',
                'currency' => 'SGD',
                'description' => 'Order #4',
                'transactionId' => 'C017',
                'customerId' => 'My Customer',
                'card' => array(
                    'name' => "Luke Holder",
                    'address1' => '123 Somewhere St',
                    'address2' => 'Suburbia',
                    'city' => 'Little Town',
                    'postcode' => '1234',
                    'state' => 'CA',
                    'country' => 'US',
                    'phone' => '1-234-567-8900'
                )
            )
        );
    }

    public function testGetData()
    {
        $data = $this->request->getData();

        $this->assertSame('150.00', $data['amount']);
        $this->assertSame('SGD', $this->request->getCurrency());
        $this->assertSame('Order #4', $data['txnDesc']);
    }

    public function testGetDataTestMode()
    {
        $this->request->setTestMode(true);
        $this->assertSame('https://api.ocbc.com:8243/transactional/merchantcardpayments/1.0/sale', $this->request->getEndpoint());
        $this->request->setTestMode(false);
        $this->assertSame('https://api.ocbc.com/transactional/merchantcardpayments/1.0/sale', $this->request->getEndpoint());
    }

    /**
     * Simulate successful transaction
     * @todo replace message with accurate one
     */     
    public function testSendSuccess()
    {
        $this->setMockHttpResponse('PurchaseResponseSuccess.txt');
        $response = $this->request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('10003543', $response->getTransactionReference());
        $this->assertEquals('0', $response->getCode());
        $this->assertSame('Success', $response->getMessage());
        $this->assertSame('Omnipay\OCBC\Message\PurchaseResponse', get_class($response));
    }

    /**
     * Simulate card declined (no error in transit, 200 HTTP response)
     */
    public function testSendFailure()
    {
        $this->setMockHttpResponse('PurchaseResponseFailure.txt');
        $response = $this->request->send();

        $data = $this->request->getData();

        $code = $response->response->getStatusCode();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('150.00', $data['amount']);
        $this->assertEquals(200, $code);
        $this->assertEquals('C017', $response->getTransactionId());
        $this->assertEquals('4003', $response->getCode());
        $this->assertSame("Duplicate MERCHANT_TRANID detected! Please ensure the MERCHANT_TRANID is always unique.", $response->getMessage());
        $this->assertSame('', $response->getTransactionReference());
    }

    /**
     * Simulate card declined with lower case 'results' node (no error in transit, 200 HTTP response)
     */
    public function testSendFailureLowerCaseResults()
    {
        $this->setMockHttpResponse('PurchaseResponseFailureLowerCaseResults.txt');
        $response = $this->request->send();

        $data = $this->request->getData();

        $code = $response->response->getStatusCode();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('150.00', $data['amount']);
        $this->assertEquals(200, $code);
        $this->assertEquals('C017', $response->getTransactionId());
        $this->assertEquals('4003', $response->getCode());
        $this->assertSame("Duplicate MERCHANT_TRANID detected! Please ensure the MERCHANT_TRANID is always unique.", $response->getMessage());
        $this->assertSame('', $response->getTransactionReference());
    }

    /**
     * Simulate card declined without response description (no error in transit, 200 HTTP response)
     */
    public function testSendFailureWithoutResponseDescription()
    {
        $this->setMockHttpResponse('PurchaseResponseFailureWithoutResponseDescription.txt');
        $response = $this->request->send();
        $this->assertSame("F", $response->getMessage());
    }

    /**
     * Simulate card declined (bad signature)
     *
     * @expectedException \Omnipay\Common\Exception\InvalidResponseException
     */
    public function testSendSignatureFailure()
    {
        $this->setMockHttpResponse('PurchaseResponseSignatureFailure.txt');
        $response = $this->request->send();
    }

    /**
     * Simulate card declined (missing data)
     *
     * @expectedException \Omnipay\Common\Exception\InvalidResponseException
     */
    public function testSendDataFailure()
    {
        $this->setMockHttpResponse('PurchaseResponseDataFailure.txt');
        $response = $this->request->send();
    }

    /**
     * Simulate card declined (missing request data)
     *
     * @expectedException \Omnipay\Common\Exception\InvalidRequestException
     */
    public function testSendRequestFailure()
    {
        $this->setMockHttpResponse('PurchaseResponseFailure.txt');
        $this->request->setPassword(null);
        $response = $this->request->send();
    }

    /**
     * Simulate bad request (HTTP 400 or similar)
     */
    public function testSendError()
    {
        $this->setMockHttpResponse('PurchaseResponseError.txt');
        $response = $this->request->send();

        $data = $this->request->getData();

        $code = $response->response->getStatusCode();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('150.00', $data['amount']);
        $this->assertEquals(403, $code);
        $this->assertSame('900906', $response->getCode());
        $this->assertSame("No matching resource found in the API for the given request", $response->getMessage());
        $this->assertNull($response->getTransactionId());
        $this->assertNull($response->getTransactionReference());
    }

    /**
     * Simulate malformed response body (partial success conditions)
     */
    public function testSendMalformed()
    {
        $this->setMockHttpResponse('PurchaseResponseMalformed.txt');
        $response = $this->request->send();

        $code = $response->response->getStatusCode();
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(400, $code);
        $this->assertNull($response->getCode());
        $this->assertNull($response->getMessage());
        $this->assertNull($response->getTransactionId());
        $this->assertNull($response->getTransactionReference());
    }

}
