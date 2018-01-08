<?php

namespace Omnipay\OCBC;

use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->setAccessToken('489efb0ee095e75d625ccc0fa5f23e30');
        $this->gateway->setMerchantId('3333700088889990');
        $this->gateway->setPassword('Mvp7Kpkv');

        $this->options = array('amount' => '150.00');
    }

    public function testPurchase()
    {
        $request = $this->gateway->purchase($this->options);

        $this->assertInstanceOf('Omnipay\OCBC\Message\PurchaseRequest', $request);
        $this->assertEquals('489efb0ee095e75d625ccc0fa5f23e30', $request->getAccessToken());
        $this->assertEquals('3333700088889990', $request->getMerchantId());
        $this->assertEquals('Mvp7Kpkv', $request->getPassword());
        $this->assertEquals('15000', $request->getAmountInteger());
    }
}
