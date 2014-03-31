<?php

namespace Nekufa\SapRfc;

interface GatewayInterface
{
    /**
     * Execute function method
     * @param $name
     * @param $request
     * @param $responseKeys
     * @throws Exception
     * @return object
     */
    public function execute($name, $request, $responseKeys);

    /**
     * Get debug information
     * @param $name
     * @return mixed
     */
    public function debug($name);

    /**
     * @param Profiler $profiler
     */
    public function setProfiler(Profiler $profiler);

    /**
     * @return Profiler
     */
    public function getProfiler();
}