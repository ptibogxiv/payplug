
$warehouse = CallAPI("GET", "/doliconnect/constante/PAYPLUG_ID_WAREHOUSE", $entity, "");
$warehouse = json_decode($warehouse, true);

$vld = [
    'idwarehouse' => $warehouse,
    'notrigger' => 0
	];
$validate = CallAPI("POST", "/orders/".$orderid."/validate", $entity, json_encode($vld));

$orderfo = CallAPI("GET", "/orders/".$orderid, $entity, "");
$orderfo = json_decode($orderfo, true);

$successurl2 = $successurl."&ref=".$orderfo[ref];
$returnurl2 = $returnurl."&ref=".$orderfo[ref];

$pays = CallAPI("GET", "/dictionarycountries/".$current_user->billing_country, $entity, "");
$pays = json_decode($pays, true);

$payplugmode = CallAPI("GET", "/doliconnect/constante/PAYPLUG_MODE", $entity, "");
$payplugmode = json_decode($payplugmode, true);

$montant = $_POST['total']*100;
$path=dirname(__FILE__).'/';
require_once($path."../../../../dolibarr/htdocs/custom/payplug/lib/init.php");

if ($payplugmode == 'TEST'){

$payplugkey = CallAPI("GET", "/doliconnect/constante/PAYPLUG_SK_TEST", $entity, "");
$payplugkey = json_decode($payplugkey, true);

}
elseif ($payplugmode == 'LIVE'){

$payplugkey = CallAPI("GET", "/doliconnect/constante/PAYPLUG_SK_LIVE", $entity, "");
$payplugkey = json_decode($payplugkey, true);

}
\Payplug\Payplug::setSecretKey($payplugkey);
$ipn = "https://dolibarr.ptibogxiv.net/custom/payplug/public/ipn.php?entity=$entity";

$payment = \Payplug\Payment::create(array(
  'amount'           => $montant,
  'currency'         => 'EUR',
  'save_card'        => false,
  'customer'         => array(
    'email'          => $current_user->user_email,
    'first_name'     => $current_user->user_firstname,
    'last_name'      => $current_user->user_lastname,
    'address1' => $current_user->billing_address,
    'postcode' => $current_user->billing_zipcode,
    'city' => $current_user->billing_city,
    'country' => $pays[code]
  ),
  'hosted_payment'   => array(
    'return_url'     => $successurl2,
    'cancel_url'     => $returnurl2
  ),
  'notification_url' => $ipn,
  'metadata'         => array( 
        'customer'       => constant("DOLIBARR"),
        'ref'       => $orderfo[ref],
        'source'       => 'order',
        'entity'       => $entity 
  )
));
$paymentUrl = $payment->hosted_payment->payment_url;
wp_redirect($paymentUrl); 
exit;                               