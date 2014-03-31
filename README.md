# SapRfc library

This component is wrapper for native saprfc extension (saprfc.sourceforge.net).  
Library do function module reverse-engeneering and provides only one method to do all tasks.  
You can write your code using GatewayInterface and then use proxy or direct connection.


# Basic usage

Server has saprfc extension and can connect to SAP R/3 (same network).

```php
<?php

use Nekufa\SapRfc\Gateway;

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

use Nekufa\SapRfc\Gateway;
use Nekufa\SapRfc\Proxy;

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

use Nekufa\SapRfc\Proxy;

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