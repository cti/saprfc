<?php

use Cti\SapRfc\Profiler;

class FakeGateway implements Cti\SapRfc\GatewayInterface
{
    protected $result;

    public function willReturn($result) 
    {
        $this->result = $result;
    }

    protected $addToProfiler;

    public function willAddToProfiler($addToProfiler) 
    {
        $this->addToProfiler = $addToProfiler;
    }

    /**
     * Execute function method
     * @param $name
     * @param $request
     * @param $responseKeys
     * @throws Exception
     * @return object
     */
    public function execute($name, $request, $responseKeys)
    {
        $this->methodCalled = 'execute';
        $this->methodArguments = func_get_args();
        if($this->addToProfiler) {
            $this->profiler->register($this->addToProfiler);
        }
        return $this->result;
    }

    /**
     * Get debug information
     * @param $name
     * @return mixed
     */
    public function debug($name)
    {
        $this->methodCalled = 'debug';
        $this->methodArguments = func_get_args();
        if($this->addToProfiler) {
            $this->profiler->register($this->addToProfiler);
        }
        return $this->result;
    }

    protected $profiler;

    /**
     * @param Profiler $profiler
     */
    public function setProfiler(Profiler $profiler)
    {
        $this->profiler = $profiler;
    }

    /**
     * @return Profiler
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

}