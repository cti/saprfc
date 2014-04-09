<?php

namespace Cti\SapRfc;

class Gateway implements GatewayInterface
{
    /**
     * encoding that sap_rfc connection used
     * @var string
     */
    public $sapEncoding = 'CP1251';

    /**
     * @var string
     */
    public $applicationEncoding = 'UTF-8';

    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * errors description
     * @var array
     */
    private $errors = null;

    /**
     * @param $config
     * @throws Exception
     */
    function __construct($config)
    {
        if (!function_exists('saprfc_open')) {
            throw new Exception('no saprfc extension');
        }
        $this->connection = saprfc_open($config);

        if (!$this->connection) {
            throw new Exception($this->decodeString(saprfc_error()));
        }

        $this->errors = array(
            SAPRFC_FAILURE => 'Error occurred',
            SAPRFC_EXCEPTION => 'Exception raised',
            SAPRFC_SYS_EXCEPTION => 'System exception raised, connection closed',
            SAPRFC_CALL => 'Call received',
            SAPRFC_INTERNAL_COM => 'Internal communication, repeat (internal use only)',
            SAPRFC_CLOSED => 'Connection closed by the other side',
            SAPRFC_RETRY => 'No data yet',
            SAPRFC_NO_TID => 'No Transaction ID available',
            SAPRFC_EXECUTED => 'Function already executed',
            SAPRFC_SYNCHRONIZE => 'Synchronous Call in Progress',
            SAPRFC_MEMORY_INSUFFICIENT => 'Memory insufficient',
            SAPRFC_VERSION_MISMATCH => 'Version mismatch',
            SAPRFC_NOT_FOUND => 'Function not found (internal use only)',
            SAPRFC_CALL_NOT_SUPPORTED => 'This call is not supported',
            SAPRFC_NOT_OWNER => 'Caller does not own the specified handle',
            SAPRFC_NOT_INITIALIZED => 'RFC not yet initialized.',
            SAPRFC_SYSTEM_CALLED => 'A system call such as RFC_PING for connectiontest is executed',
            SAPRFC_INVALID_HANDLE => 'An invalid handle was passed to an API call.',
            SAPRFC_INVALID_PARAMETER => 'An invalid parameter was passed to an API call.',
            SAPRFC_CANCELED => 'Internal use only',
        );
    }

    /**
     * @param string $name
     * @param array $request
     * @param array $responseKeys
     * @throws Exception
     * @return object
     */
    public function execute($name, $request, $responseKeys)
    {
        $start = microtime(1);

        $fce = saprfc_function_discover($this->connection, $name);

        if (!$fce) {
            throw new Exception("Error discovering " . $name, 1);
        }

        foreach ($request as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $index => $row) {
                    if (is_object($row)) {
                        $row = get_object_vars($row);
                    }
                    foreach ($row as $row_key => $row_value) {
                        $row[$row_key] = $this->encodeString($row_value);
                    }
                    saprfc_table_insert($fce, $k, $row, $index + 1);
                }
            } else {
                saprfc_request($fce, $k, $this->encodeString($v));
            }
        }

        $result = saprfc_call_and_receive($fce);

        if ($result != SAPRFC_OK) {
            $message = isset($this->errors[$result]) ? $this->errors[$result] : 'Unknown error';
            if ($this->profiler) {
                $this->profiler->register((object) array(
                    'name' => $name,
                    'request' => $request,
                    'success' => false,
                    'message' => $message,
                    'time' => microtime(1) - $start,
                ));
            }
            throw new Exception($message);
        }

        $response = array();
        foreach ($responseKeys as $table) {

            $count = saprfc_table_rows($fce, $table);

            if ($count == -1) {
                // responseKeys param
                $data = $this->decodeString(saprfc_export($fce, $table));

            } else {
                // responseKeys table
                $data = array();
                for ($i = 1; $i <= $count; $i++) {
                    $row = saprfc_table_read($fce, $table, $i);
                    foreach ($row as $k => $v) {
                        $row[$k] = $this->decodeString($v);
                    }
                    $data[] = (object)$row;
                }
            }
            $response[$table] = $data;
        }

        if ($this->profiler) {
            $this->profiler->register((object) array(
                'name' => $name,
                'request' => (object) $request,
                'response' => (object) $response,
                'success' => true,
                'time' => microtime(1) - $start,
            ));
        }

        return (object) $response;
    }

    /**
     * Get debug information
     * @param $name
     * @return mixed
     */
    public function debug($name)
    {
        ob_start();
        $fce = saprfc_function_discover($this->connection, $name);
        saprfc_function_debug_info($fce);
        return ob_get_clean();
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

    /**
     * @param $string
     * @return string
     */
    public function decodeString($string)
    {
        if ($this->applicationEncoding == $this->sapEncoding) {
            return $string;
        }
        return iconv($this->sapEncoding, $this->applicationEncoding, $string);
    }

    /**
     * @param $string
     * @return string
     */
    public function encodeString($string)
    {
        if ($this->applicationEncoding == $this->sapEncoding) {
            return $string;
        }
        return iconv($this->applicationEncoding, $this->sapEncoding, $string);
    }
}