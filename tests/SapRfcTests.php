<?php

use Nekufa\SapRfc\Gateway;
use Nekufa\SapRfc\Proxy;
use Nekufa\SapRfc\Profiler;

class SapRfcTests extends PHPUnit_Framework_TestCase
{
    function testProfiler()
    {
        $row = (object) array(
            'name' => 'test',
        );
        
        $p = new Profiler;

        $this->assertCount(0, $p->getData());

        $p->register($row);
        $this->assertSame(array($row), $p->getData());
    }

    function testProxy()
    {
        $proxy = new Proxy();

        $proxy->setUrl('http://ya.ru');
        $this->assertSame($proxy->url, 'http://ya.ru');

        $expectedResponse = array('v' => 'result');

        $fake = new FakeGateway;
        $fake->willReturn($expectedResponse);
        $fake->willAddToProfiler(array('name' => 'test'));

        $this->start();
        $proxy->processRequest($fake, array(
            'method' => 'execute',
            'enableProfiler' => true,
            'transaction' => json_encode(array(
                'name' => 'test',
                'request' => array('k' => 'v'),
                'responseKeys' => array('v')
            ))
        ));
        $this->assertSame(get_object_vars($this->parse()->data), $expectedResponse);
        $this->assertSame($fake->methodCalled, 'execute');

        $arguments = $fake->methodArguments;

        $this->assertSame($arguments[0], 'test');
        $this->assertSame(get_object_vars($arguments[1]), array('k' => 'v'));
        $this->assertSame($arguments[2], array('v'));

        $profiler = $this->parse()->profiler;
        $this->assertCount(1, $profiler);
        $this->assertSame($profiler[0]->name, 'test');

        $this->start();
        $proxy->processRequest($fake, array(
            'method' => 'debug',
            'enableProfiler' => true,
            'transaction' => json_encode(array(
                'name' => 'test',
                'request' => array(),
                'responseKeys' => array()
            ))
        ));
        $this->assertSame(get_object_vars($this->parse()->data), $expectedResponse);
        $this->assertCount(2, $this->parse()->profiler);

        $this->start();
        $proxy->processRequest($fake, array(
            'method' => 'test',
        ));
        $this->assertSame($this->parse()->exception, 'Unknown method test');

        $this->start();
        $proxy->processRequest($fake, array(
            'method' => 'execute',
            'transaction' => 'bla-bla-bla'
        ));
        $this->assertSame($this->parse()->exception, 'No valid transaction found');

        $this->assertNull($proxy->getProfiler());

        $profiler = new Profiler;
        $proxy->setProfiler($profiler);

        $this->assertSame($profiler, $proxy->getProfiler());
    }

    protected function start()
    {
        $this->processing = true;
        ob_start();
    }

    protected function parse()
    {
        if($this->processing) {
            $this->result = json_decode(ob_get_clean());
            $this->processing = false;
        }
        return $this->result;
    }
    
}