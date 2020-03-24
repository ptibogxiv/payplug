<?php
/* Copyright (C) 2016-2017	Thibault FOUCART	<ptibogxiv@ptibogxiv.net>
/* Copyright (C) 2016-2017	PAYPLUG	http://support.payplug.com/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

 /**
 *   	\file       htdocs/custo/admin/adherent.php
 *		\ingroup    payplug
 *		\brief      Page to setup the module Payplug
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : (! empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");			// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");		// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails"); 

if (empty($conf->payplug->enabled)) accessforbidden('',0,0,1);

// Libraries 
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php";

$db->begin();

?>
<head><title>Paiement en ligne</title>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<link href="//netdna.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.css" rel="stylesheet"></head>
<?php 
if (isset($_POST['reference'])) {$reference=$_POST['reference'];}
if (isset($_GET['reference'])) {$reference=$_GET['reference'];} 
$langs->load("companies");
$langs->load("payplug@payplug");
$langs->load("orders");
print "<div class='container'><div class='row'><div class='col-md-12'><h1><span class='fa fa-credit-card'></span> ".$langs->trans('PAYPLUG_PUBLIC')."</h1><hr /></div></div>";       	
$order=new Commande($db);
$result=$order->fetch('',$reference);
	if ($result > 0)
	{
$result=$order->fetch_thirdparty($order->socid);
$codeclient=$order->thirdparty->code_client;
$reftype='order'; 
$refid=$order->id;
$refstatut=$order->statut;
$refentity=$order->thirdparty->entity;
$refsocid=$order->socid; 
$refamount=$order->total_ttc; 
$refname=$order->thirdparty->name;
$refemail=$order->thirdparty->email;
$refaddress=$order->thirdparty->address;
$refpostcode=$order->thirdparty->zip;
$refcity=$order->thirdparty->town;
$refcountry=$order->thirdparty->country_id;
$refdate=$order->date_commande;
	}
$invoice=new Facture($db);
$result=$invoice->fetch('',$reference);
	if ($result > 0)
	{
$result=$invoice->fetch_thirdparty($invoice->socid);
$codeclient=$invoice->thirdparty->code_client;
$reftype='invoice'; 
$refid=$invoice->id;
$refstatut=$invoice->statut;
$refentity=$invoice->thirdparty->entity;
$refsocid=$invoice->socid;
$refamount=$invoice->total_ttc; 
$refname=$invoice->thirdparty->name;
$refemail=$invoice->thirdparty->email;
$refaddress=$invoice->thirdparty->address;
$refpostcode=$invoice->thirdparty->zip;
$refcity=$invoice->thirdparty->town;
$refcountry=$invoice->thirdparty->country_id;
$refdate=$invoice->date_validation;
	}
if ($conf->global->PAYPLUG_MODE == 'TEST'){$secret_key=$conf->global->PAYPLUG_SK_TEST;}
elseif ($conf->global->PAYPLUG_MODE == 'LIVE'){$secret_key=$conf->global->PAYPLUG_SK_LIVE;}
// Error message
if (empty($conf->global->PAYPLUG_ENABLE_PUBLIC)){$msg="<div class='alert alert-info' role='alert'>".$langs->trans('PAYPLUG_ERROR_DISABLED')."</div>";}
elseif (isset($refstatut) && ($refstatut != '1')) {$msg="<div class='alert alert-info' role='alert'>".$langs->trans('PAYPLUG_ERROR_PAID')."</div>";}
elseif ($codeclient != $_POST['code_client']) {$msg="<div class='alert alert-danger' role='alert'>".$langs->trans('PAYPLUG_ERROR_INPUT')."</div>";}

// secure key & entity link
$key=dol_hash($refsocid.$type.$refid .$refentity, 2); 
if (! empty($conf->multicompany->enabled))  {
$linkentity="&entity=$entity";
}
$return=$dolibarr_main_url_root.dol_buildpath('/payplug/public/newpayment.php?reference='.$reference.$linkentity.'&secure='.$key.'', 1);
$cancel=$dolibarr_main_url_root.dol_buildpath('/payplug/public/newpayment.php?'.$linkentity.'', 1);
$notification = $dolibarr_main_url_root.dol_buildpath('/payplug/public/ipn.php?entity='.$refentity.'', 1);
// redirect payment page
if ((!empty($conf->global->PAYPLUG_ENABLE_PUBLIC)) && ($_POST['validation'] == 'URLOK') && ($codeclient == $_POST['code_client']) && isset($reftype) && isset($reference) && ($refstatut < '2')){
header("location: ".$return);
}

if ((!empty($conf->global->PAYPLUG_ENABLE_PUBLIC)) && ($_GET['secure'] == $key) && ($entity == $refentity) && isset($reference) && isset($refsocid) && ($refstatut < '2')){
dol_include_once('/payplug/lib/init.php');

print "<div class='row'><div class='col-md-3'></div>
      <div class='col-md-6'><h3>Vendeur</h3>
<div class='well'><p><b>".$langs->trans('CompanyName')." :</b> ".$conf->global->MAIN_INFO_SOCIETE_NOM."</p></div>
<h3>".$langs->trans('Customer')."</h3>
<div class='well'><p><b>".$langs->trans('CompanyName')." :</b> ".$refname."</p> 
<p><b>".$langs->trans('EMail')." :</b> ".$refemail."</p> 
<b>".$langs->trans('PAYPLUG_REF')." :</b> $reference</p>
        <p><b>".$langs->trans('Date')." :</b> ".dol_print_date($refdate,'%d/%m/%Y',true)."</p></div></div><div class='col-md-3'></div></div><div class='row'><div class='col-md-3'></div><div class='col-md-6'>";     
if ($conf->global->PAYPLUG_OFFER == 'PREMIUM'){
$amount=round($refamount*100); 
?>
<script type="text/javascript" src="https://api.payplug.com/js/1/payplug.latest.js"></script>
<?php        
$valid= $_POST['validation'];
$token = $_POST['payplugToken'];

if (($valid =='OK') && isset($token)) {
//\Payplug\Payplug::setSecretKey("$secret_key");
\Payplug\Payplug::init(array(
  'secretKey' => $secret_key,
//  'apiVersion' => 'LA_VERSION_API',
));
try {
  $payment = \Payplug\Payment::create(array(
    'amount'         => $amount,
    'currency'       => 'EUR',
    'save_card'      => false,
    'force_3ds'      => boolval($conf->global->PAYPLUG_FORCE_3DSECURE),
    'payment_method' => $token,
    'billing'       => array(
    'email'          => $refemail,
    'first_name'     => $refname,
    'last_name'      => $refname,
    'address1'       => $refaddress,
    'postcode'       => $refzip,
    'city'           => $reftown,
    'country'        => $refcountry
    ),
    'shipping'       => array(
    'email'          => $refemail,
    'first_name'     => $refname,
    'last_name'      => $refname,
    'address1'       => $refaddress,
    'postcode'       => $refzip,
    'city'           => $reftown,
    'country'        => $refcountry,
    'delivery_type'  => 'VERIFIED'
    ),
    'notification_url' => $notification,   
    'metadata'         => array(
        'entity'       => $refentity, 
        'source'       => $reftype,
        'ref'       => $reference,
        'customer'       => $refsocid, 
        'member'       => '0',
        'typeadherent'    => '0',
        'cotisationstart'       => '0',
        'cotisationend'       => '0',
        'cotisationamount'       => '0'
  )
  ));
} catch (\Payplug\Exception\ConnectionException $e) {
print "Connection  with the PayPlug API failed.";
} catch (\Payplug\Exception\InvalidPaymentException $e) {
print "Payment object provided is invalid.";
} catch (\Payplug\Exception\UndefinedAttributeException $e) {
print "Requested attribute is undefined.";
} catch (\Payplug\Exception\HttpException $e) {
print "Http errors.";
} catch (\Payplug\Exception\PayplugException $e) {
print 'Failure code: ' . $e->getMessage();
} catch (Exception $e) {
print 'Caught exception: '. $e->getMessage();
}

if ($payment->is_paid == true) {
print "<div><h3>".$langs->trans('PAYPLUG_THANK')." " . $payment->customer->email . ".</h3></div>";
} else {
  var_dump($e); 
print "<div><strong>Error !</strong><br />". $payment->failure->message ." (" . $payment->failure->code . ").</div>";
}
print "</div>";
}
else {
if ($conf->global->PAYPLUG_MODE == 'TEST'){$publish_key=$conf->global->PAYPLUG_PK_TEST;}
elseif ($conf->global->PAYPLUG_MODE == 'LIVE'){$publish_key=$conf->global->PAYPLUG_PK_LIVE;}     
?>
<script type="text/javascript">
  Payplug.setPublishableKey('<?PHP echo $publish_key;?>');

  var payplugResponseHandler = function(code, response, details) {
    console.log(code + ' : ' + response + ' : ' + details);
    if (code == 'card_number_invalid') {
      document.querySelectorAll("#error-card-bad")[0].style.display = 'block';
    }
    if (code == 'cvv_invalid') {
      document.querySelectorAll("#error-cvv-bad")[0].style.display = 'block';
    }
    if (code == 'expiry_date_invalid') {
      document.querySelectorAll("#error-expiry-bad")[0].style.display = 'block';
    }
    if (code == 'payplug_api_error') {
      document.querySelectorAll("#error-api-bad")[0].innerHTML = response + ', details: ' +  details;
      document.querySelectorAll("#error-api-bad")[0].style.display = 'block';
    }
    return false;
  };
  var amount2='<?PHP echo $amount;?>';
var amount3=parseInt(amount2); 
document.addEventListener('DOMContentLoaded', function() { [].forEach.call(document.querySelectorAll("[data-payplug='form']"), 
function(el) { el.addEventListener('submit', function(event) { 
var form = document.querySelectorAll("#signupForm")[0]; Payplug.card.createToken(form, payplugResponseHandler, {'amount': amount3, 'currency': 'EUR' }); 
event.preventDefault(); }) }) })
</script>
<?php
print "<form action='$return' method='POST' id='signupForm' class='form' novalidate data-payplug='form'><div class='row'>
<div class='col-md-12'><div class='input-error-wrapper' id='error-api'><p class='input-error' id='error-api-bad'></p></div></div>
<div class='col-md-12'><div class='form-group'><div class='input-group'>
<span class='input-group-addon'><span class='fa fa-credit-card'></span></span>
<input type='number' class='form-control input-lg' placeholder='".$langs->trans('PAYPLUG_CARDNUMBER')."' value='' autocomplete='off' data-payplug='card_number' maxlength='17'></div>
<span id='helpBlock' class='help-block'><div class='input-error-wrapper' id='error-card'><p class='input-error' id='error-card-bad' style='display:none;'>".$langs->trans('PAYPLUG_ERROR_CARD')."</p></span>
</div></div></div>
<div class='col-md-8'><div class='form-group'><div class='input-group'>
<span class='input-group-addon'><span class='fa fa-calendar'></span></span>
<input type='text' class='form-control input-lg' placeholder='".$langs->trans('PAYPLUG_CARDEXPI')."' autocomplete='off' data-payplug='card_month_year' maxlength='7'></div>
<span id='helpBlock' class='help-block'><div class='input-error-wrapper' id='error-expiry'><p class='input-error' id='error-expiry-bad' style='display:none;'>".$langs->trans('PAYPLUG_ERROR_EXPI')."</p></span>
</div></div></div>
<div class='col-md-4'><div class='form-group'><div class='input-group'>
<span class='input-group-addon'><span class='fa fa-lock'></span></span>
<input type='number' class='form-control input-lg' placeholder='CVV' autocomplete='off' data-payplug='card_cvv' maxlength='3'></div>
<span id='helpBlock' class='help-block'><div class='input-error-wrapper' id='error-cvv'><p class='input-error' id='error-cvv-bad' style='display:none;'>".$langs->trans('PAYPLUG_ERROR_CVV')."</p></span>
</div></div></div>
<div class='col-md-12'><input type='hidden' name='validation' value='OK'><button type='submit' class='btn btn-danger btn-lg btn-block' data-payplug='submit'>".$langs->trans('PAYPLUG_PAID')." ".price($refamount)."  ".$langs->trans('Currency'.$conf->currency)."</button>
</div></div></form>
      ";
}}
elseif ($conf->global->PAYPLUG_OFFER == 'STARTER'){
$amount=round($refamount*100);

//\Payplug\Payplug::setSecretKey("$secret_key");
\Payplug\Payplug::init(array(
  'secretKey' => $secret_key,
//  'apiVersion' => 'LA_VERSION_API',
));
$payment = \Payplug\Payment::create(array(
    'amount'         => $amount,
    'currency'       => 'EUR',
    'save_card'      => false,
    'force_3ds'      => boolval($conf->global->PAYPLUG_FORCE_3DSECURE),
    'billing'       => array(
    'email'          => $refemail,
    'first_name'     => $refname,
    'last_name'      => $refname,
    'address1'       => $refaddress,
    'postcode'       => $refzip,
    'city'           => $reftown,
    'country'        => $refcountry
    ),
    'shipping'       => array(
    'email'          => $refemail,
    'first_name'     => $refname,
    'last_name'      => $refname,
    'address1'       => $refaddress,
    'postcode'       => $refzip,
    'city'           => $reftown,
    'country'        => $refcountry,
    'delivery_type'  => 'VERIFIED'
    ),
    'hosted_payment' => array(
        'return_url' => $return,
        'cancel_url' => $cancel
    ),
    'notification_url' => $notification,
    'metadata'        => array(
        'entity'       => $refentity, 
        'source'       => $reftype,
        'ref'       => $reference,
        'customer'       => $refsocid, 
        'member'       => '0',
        'typeadherent'    => '0',
        'cotisationstart'       => '0',
        'cotisationend'       => '0',
        'cotisationamount'       => '0'
    )
));
?>
<script type="text/javascript" src="https://api.payplug.com/js/1.0/form.js"></script>
<script type="text/javascript">
  document.addEventListener('DOMContentLoaded', function() {
    [].forEach.call(document.querySelectorAll("#signupForm"), function(el) {
      el.addEventListener('submit', function(event) {
        var payplug_url = '<?php echo $payment->hosted_payment->payment_url; ?>';
        Payplug.showPayment(payplug_url);
        event.preventDefault();
      })
    })
  })

</script>
<?php
$amount2=round($refamount*100);
print "<FORM action'' method='post' id='signupForm' class='formulaire' novalidate><p><input type='hidden' name='validation' value='OK'>
          <button type='submit' class='btn btn-danger btn-lg btn-block' data-payplug='submit' role='button'>".$langs->trans('PAYPLUG_PAID')." ".price($refamount)."  ".$langs->trans('Currency'.$conf->currency)."</button>
        </p>
      </form>"; 
} 
print "</div><div class='col-md-3'></div></div>";}
else {
print"<div class='row'><div class='col-md-4'>";
// Show logo (search order: logo defined by PAYBOX_LOGO_suffix, then PAYBOX_LOGO, then small company logo, large company logo, theme logo, common logo)
$width=0;
// Define logo and logosmall
$logosmall=$mysoc->logo_small;
$logo=$mysoc->logo;
$paramlogo='PAYPLUG_LOGO_'.$suffix;
if (! empty($conf->global->$paramlogo)) $logosmall=$conf->global->$paramlogo;
else if (! empty($conf->global->PAYPLUG_LOGO)) $logosmall=$conf->global->PAYPLUG_LOGO;
//print '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo='';
if (! empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('thumbs/'.$logosmall);
}
elseif (! empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode($logo);
	$width=96;
}
// Output html code for logo
if ($urllogo)
{

	print '<center><img id="dolpaymentlogo" title="'.$title.'" src="'.$urllogo.'"';
	if ($width) print ' width="'.$width.'"';
	print '></center>';

}
print"</div><div class='col-md-1'></div><div class='col-md-6'>";
print $msg;
print "<form action='newpayment.php?".$linkentity."' class='form' method='post'><h3>".$langs->trans('PAYPLUG_PUBLIC_WELCOME')."</h3>
  <div class='form-group'>
    <div class='input-group'>
      <div class='input-group-addon'>".$langs->trans('Customer')."</div>
      <input type='text' autocomplete='off' class='form-control input-lg' id='code_client' name='code_client' placeholder='".$langs->trans('CustomerCode')."' required ";
if (empty($conf->global->PAYPLUG_ENABLE_PUBLIC)) print "disabled";
print ">
    </div><span id='helpBlock' class='help-block'>ex: CU1701-1234</span>
  </div>
    <div class='form-group'>
    <div class='input-group'>
      <div class='input-group-addon'>".$langs->trans('PAYPLUG_REF')."</div>
      <input type='text' autocomplete='off' class='form-control input-lg' id='reference' name='reference' placeholder='".$langs->trans('PAYPLUG_ID_TRANSACTION')."' required ";
if (empty($conf->global->PAYPLUG_ENABLE_PUBLIC)) print "disabled";
print ">
    </div><span id='helpBlock' class='help-block'>ex: CO1701-1234 / FA1701-1234</span>
  </div><input type='hidden' name='validation' value='URLOK'>
  <button type='submit' class='btn btn-primary btn-block btn-lg' ";
if (empty($conf->global->PAYPLUG_ENABLE_PUBLIC)) print "disabled";
print ">".$langs->trans('Validate')."</button>
</form>";
print"</div><div class='col-md-1'></div></div>";
}
if (isset($conf->global->PAYPLUG_EMAIL_HELP)&&!empty($conf->global->PAYPLUG_ENABLE_PUBLIC)){$help=$langs->trans('PAYPLUG_HELP')." <a href='mailto:".$conf->global->PAYPLUG_EMAIL_HELP."?Subject=".$langs->trans('PAYPLUG_PUBLIC')."' target='_blank'>".$conf->global->PAYPLUG_EMAIL_HELP."</a>";}
print "<div class='col-md-12'><hr /><p style='text-align: center'>".$help."</p></div></div>";
$db->close();
