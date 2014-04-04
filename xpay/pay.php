<?php

// Set up a xPay transaction and redirect user to payment service.
require dirname(dirname(dirname(__FILE__))) . "/config.php";
require_once "{$CFG->dirroot}/lib/enrollib.php";

require_login();

$id = required_param('id', PARAM_INT);  // plugin instance id

// get plugin instance
if (!$plugin_instance = $DB->get_record("enrol", array("id"=>$id, "status"=>0))) {
    print_error('invalidinstance');
}

$plugin = enrol_get_plugin('xpay');

$xmlreply = $plugin->begin_transaction($plugin_instance, $USER);
echo $xmlreply;

/*$response = $plugin->getdom($xmlreply);

// abort if xPay returns an invalid response
if ($response->attributes()->valid != '1') {
    print_error('error_xpayinitiate', 'enrol_xpay');
}*/

// otherwise, redirect to the xPay provided URI
//redirect($response->URI);

