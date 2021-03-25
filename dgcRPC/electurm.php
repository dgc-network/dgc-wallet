<?php

//$client = new \Electrum\Client('http://1.163.27.45', 7998, 0, 'digitalcoinrpc', '56c735f3910a53eeda0357670bc6a02f');
//$method = new \Electrum\Request\Method\Version($client);
//$method = new \Electrum\Request\Method\Version();
$method = new \Electrum\Request\Method\GetAddressBalance();

try {
    $response = $method->execute();
} catch (\Electrum\Request\Exception\BadRequestException $exception) {
    die(sprintf(
        'Failed to send request: %s',
        $exception->getMessage()
    ));
} catch(\Electrum\Response\Exception\BadResponseException $exception) {
    die(sprintf(
        'Electrum-Client failed to respond correctly: %s',
        $exception->getMessage()
    ));
}