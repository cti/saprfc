# SapRfc library
[![Latest Stable Version](https://poser.pugx.org/cti/saprfc/v/stable.png)](https://packagist.org/packages/cti/saprfc)
[![Total Downloads](https://poser.pugx.org/cti/saprfc/downloads.png)](https://packagist.org/packages/cti/saprfc)
[![License](https://poser.pugx.org/cti/saprfc/license.png)](https://packagist.org/packages/cti/saprfc)
[![Build Status](https://travis-ci.org/cti/saprfc.svg)](https://travis-ci.org/cti/saprfc)
[![Coverage Status](https://coveralls.io/repos/cti/saprfc/badge.png)](https://coveralls.io/r/cti/saprfc)


This component is wrapper for native saprfc extension (saprfc.sourceforge.net).  
Function module reverse-engeneering and providing only one method to do all tasks.  
You can write your code using GatewayInterface and then use proxy or direct connection.

# Installation

Using composer.

```json
{
    "require": {
        "cti/saprfc": "*"    
    }
}
```


# Basic usage

Server has saprfc extension and can connect to SAP R/3 (same network).

```php
<?php

use Cti\SapRfc\Gateway;

// params for saprfc_open
// http://saprfc.sourceforge.net/src/saprfc.html#function.saprfc-open.html
$connectionParams = array(
    'USER' => 'USERNAME',
    'PASSWD' => 'PASSWORD',
    // ...
);

$sap = new Gateway($connectionParams);

$request = array(
    // use import params
    'I_ID_USER' => 12345,
    'I_LIMIT' => 50,
    'I_OFFSET' => 25,

    // fill tables
    'IT_FILTER' => array(
        array('FIELD' => 'STATUS', 'VALUE' => 'ACTIVE')
    )
);

$response = array(
    // use export params
    'TOTAL_CNT', 'LAST_UPDATE', 

    // use table result
    'IT_RECIPE_LIST'
);


$result = $sap->execute('Z_GET_RECIPE_LIST', $request, $response);

// $result contains properties TOTAL_CNT, LAST_UPDATE, IT_RECIPE_LIST.
// TOTAL_CNT and LAST_UPDATE are scalar values
// IT_RECIPE_LIST is array of data
// 
echo $result->TOTAL_CNT; 

foreach($result->IT_RECIPE_LIST as $recipe) {
    echo $recipe->ID_RECIPE, ' ', $recipe->name, '<br/>';
}

```

# Proxy usage

Sap rfc library can be used in proxy mode.
For example:
- first server is in intranet and it can connect to Sap R/3
- second server has vpn connection to first server and has no saprfc extension

First server proxy.php file:
```php
<?php

use Cti\SapRfc\Gateway;
use Cti\SapRfc\Proxy;

// params for saprfc_open
// http://saprfc.sourceforge.net/src/saprfc.html#function.saprfc-open.html
$connectionParams = array(
    'USER' => 'USERNAME',
    'PASSWD' => 'PASSWORD',
    // ...
);

$sap = new Gateway($connectionParams);

$proxy = new Proxy();
$proxy->processRequest($sap);

```

Second server request.php file:

```php
<?php

use Cti\SapRfc\Proxy;

$request = array(
    // use import params
    'I_ID_USER' => 12345,
    'I_LIMIT' => 50,
    'I_OFFSET' => 25,

    // fill tables
    'IT_FILTER' => array(
        array('FIELD' => 'STATUS', 'VALUE' => 'ACTIVE')
    )
);

$response = array(
    // use export params
    'TOTAL_CNT', 'LAST_UPDATE', 

    // use table result
    'IT_RECIPE_LIST'
);


$proxy = new Proxy();
$proxy->setUrl("http://intranet_server_url/proxy.php");

$result = $proxy->execute('Z_GET_RECIPE_LIST', $request, $response);

// $result contains properties TOTAL_CNT, LAST_UPDATE, IT_RECIPE_LIST.
// TOTAL_CNT and LAST_UPDATE are scalar values
// IT_RECIPE_LIST is array of data
// 
echo $result->TOTAL_CNT; 

foreach($result->IT_RECIPE_LIST as $recipe) {
    echo $recipe->ID_RECIPE, ' ', $recipe->name, '<br/>';
}
```

# Profiling your requests

Gateway interface provides profiler injection.  
With this object you can analyze your call, time and memory usage.  

```php
<?php

use Cti\SapRfc\Gateway;
use Cti\SapRfc\Profiler;

// params for saprfc_open
// http://saprfc.sourceforge.net/src/saprfc.html#function.saprfc-open.html
$connectionParams = array(
    'USER' => 'USERNAME',
    'PASSWD' => 'PASSWORD',
    // ...
);

$sap = new Gateway($connectionParams);

$sap->setProfiler(new Profiler());

$name = 'FUNCTIONAL_MODULE_NAME';

$request = array(
    // ...
);

$response = array(
    // ...
);

$result = $sap->execute($name, $request, $response);

foreach($sap->getProfiler()->getData() as $transaction) {

    echo $transaction->name;
    var_dump($transaction->request);

    if($transaction->success) {
        var_dump($transaction->response);

    } else {
        echo 'FAIL: ', $transaction->message;
    }

    echo $transaction->time, ' seconds' ;
}

```