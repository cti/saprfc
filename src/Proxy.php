<?php

namespace Nekufa\SapRfc;

class Proxy implements GatewayInterface
{
    /**
     * @var Nekufa\SapRfc\Profiler
     */
    private $profiler;

    /**
     * proxy url
     * @var string
     */
    public $url;

    /**
     * @var array
     */
    public $extra = array();

    /**
     * request timeout in seconds
     * @var int
     */
    public $timeout = 1800;

    /**
     * @param string $url proxy url
     * @return Nekufa\SapRfc\Proxy
     */
    public function setUrl($url) 
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param GatewayInterface $sap
     * @param array $params
     */
    public function processRequest(GatewayInterface $sap, $params = null)
    {
        try {

            if (!$params) {
                $params = $_POST;
            }

            $method = $params['method'];
            if (!in_array($method, array('execute', 'debug'))) {
                throw new Exception("Unknown method $method");
            }

            $transaction = json_decode($params['transaction']);
            if (!$transaction) {
                throw new Exception("No valid transaction found");
            }

            if ($params['enableProfiler'] && !$sap->getProfiler()) {
                $sap->setProfiler(new Profiler());
            }

            $data = $sap->$method($transaction->name, $transaction->request, $transaction->responseKeys);
            $result = array('data' => $data);

        } catch (Exception $e) {
            $result = array('exception' => $e->getMessage());
        }

        if ($sap->getProfiler()) {
            $result['profiler'] = $sap->getProfiler()->getData();
        }
        echo json_encode($result);
    }

    /**
     * @param $name
     * @param $request
     * @param $responseKeys
     * @throws Exception
     * @return object
     */
    public function execute($name, $request, $responseKeys)
    {
        $text = file_get_contents($this->url, false, stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'timeout' => $this->timeout,
                'content' => http_build_query(array(
                    'method' => 'execute',
                    'enableProfiler' => $this->profiler != null,
                    'transaction' => json_encode(array(
                        'name' => $name,
                        'request' => $request,
                        'responseKeys' => $responseKeys,
                    )),
                    'extra' => json_encode($this->extra)
                )),
            ),
        )));
        $response = json_decode($text);
        if (!$response) {
            throw new Exception("Error Processing Request.<br/>" . $text);
        } elseif (isset($response->exception)) {
            throw new Exception($response->exception);
        }

        if (isset($response->profiler)) {
            if (!$this->profiler) {
                $this->profiler = new Profiler();
            }
            foreach ($response->profiler as $row) {
                $this->profiler->register((object) $row);
            }
        }

        return $response->data;
    }

    /**
     * Get debug information
     * @param $name
     * @throws Exception
     * @return mixed
     */
    public function debug($name)
    {
        $text = file_get_contents($this->url, false, stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'timeout' => $this->timeout,
                'content' => http_build_query(array(
                    'method' => 'debug',
                    'transaction' => json_encode(array(
                        'name' => $name,
                    )),
                    'extra' => json_encode($this->extra)
                )),
            ),
        )));
        $result = json_decode($text);
        if (!$result) {
            throw new Exception("Error Processing Request: " . $text);

        } elseif (isset($result->exception)) {
            throw new Exception($result->exception);
        }

        return $result->data;
    }

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