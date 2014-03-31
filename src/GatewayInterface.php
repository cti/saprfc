<?php

namespace Nekufa\SapRfc;

interface GatewayInterface
{
    /**
     * Execute function method
     * @param $name
     * @param $import
     * @param $export
     * @throws Exception
     * @return object
     */
    public function execute($name, $import, $export);

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