<?php

namespace Nekufa\SapRfc;

class Profiler
{
    /**
     * @var array
     */
    private $data = array();

    /**
     * register info
     * @param $row
     */
    public function register($row)
    {
        $this->data[] = $row;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}