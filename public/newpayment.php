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
<HEAD><TITLE>Paiement en ligne</TITLE>
<META charset="utf-8"><META name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<LINK rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<LINK rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css">
<SCRIPT src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></SCRIPT>
<SCRIPT src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></SCRIPT>
<LINK href="//netdna.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.css" rel="stylesheet"></HEAD>
<?php 
if (isset($_POST['reference'])) {$reference=$_POST['reference'];}
if (isset($_GET['reference'])) {$reference=$_GET['reference'];} 
$langs->load("companies");
$langs->load("payplug@payplug");
$langs->load("orders");
print "<DIV class='container'><DIV class='row'><DIV class='col-md-12'><H1><span class='fa fa-credit-card'></span> ".$langs->trans('PAYPLUG_PUBLIC')."</H1><HR /></DIV></DIV>";       	
$order=new Commande($db);
$result=$order->fetch('',$reference);
	if ($result > 0)
	{
$result=$order->fetch_thirdparty($order->socid);
$codeclient=$order->thirdparty->code_client;
$reftype=order; 
$refid=$order->id;
$refstatut=$order->statut;
$refentity=$order->thirdparty->entity;
$refsocid=$order->socid; 
$refamount=$order->total_ttc; 
$refname=$order->thirdparty->name;
$refemail=$order->thirdparty->email;
$refdate=$order->date_commande;
	}
$invoice=new Facture($db);
$result=$invoice->fetch('',$reference);
	if ($result > 0)
	{
$result=$invoice->fetch_thirdparty($invoice->socid);
$codeclient=$invoice->thirdparty->code_client;
$reftype=invoice; 
$refid=$invoice->id;
$refstatut=$invoice->statut;
$refentity=$invoice->thirdparty->entity;
$refsocid=$invoice->socid;
$refamount=$invoice->total_ttc; 
$refname=$invoice->thirdparty->name;
$refemail=$invoice->thirdparty->email;
$refdate=$invoice->date_validation;
	}
if ($conf->global->PAYPLUG_MODE == 'TEST'){$secret_key=$conf->global->PAYPLUG_SK_TEST;}
elseif ($conf->global->PAYPLUG_MODE == 'LIVE'){$secret_key=$conf->global->PAYPLUG_SK_LIVE;}
// Error message
if (empty($conf->global->PAYPLUG_ENABLE_PUBLIC)){$msg="<DIV class='alert alert-info' role='alert'>".$langs->trans('PAYPLUG_ERROR_DISABLED')."</DIV>";}
elseif (isset($refstatut) && ($refstatut != '1')) {$msg="<DIV class='alert alert-info' role='alert'>".$langs->trans('PAYPLUG_ERROR_PAID')."</DIV>";}
elseif ($codeclient != $_POST['code_client']) {$msg="<DIV class='alert alert-danger' role='alert'>".$langs->trans('PAYPLUG_ERROR_INPUT')."</DIV>";}

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

print "<DIV class='row'><DIV class='col-md-3'></DIV>
      <DIV class='col-md-6'><H3>Vendeur</H3>
<DIV class='well'><P><B>".$langs->trans('CompanyName')." :</B> ".$conf->global->MAIN_INFO_SOCIETE_NOM."</P></DIV>
<H3>".$langs->trans('Customer')."</H3>
<DIV class='well'><P><B>".$langs->trans('CompanyName')." :</B> ".$refname."</P> 
<P><B>".$langs->trans('EMail')." :</B> ".$refemail."</P> 
<B>".$langs->trans('PAYPLUG_REF')." :</B> $reference</P>
        <P><B>".$langs->trans('Date')." :</B> ".dol_print_date($refdate,'%d/%m/%Y',true)."</P></div></DIV><DIV class='col-md-3'></DIV></DIV><DIV class='row'><DIV class='col-md-3'></DIV><DIV class='col-md-6'>";     
if ($conf->global->PAYPLUG_OFFER == 'PREMIUM'){
$amount=round($refamount*100); 
?>
<SCRIPT type="text/javascript" src="https://api.payplug.com/js/1.0/payplug.js"></SCRIPT>
<?php        
$valid= $_POST['validation'];
$token = $_POST['payplugToken'];

if (($valid =='OK') && isset($token)) {
\Payplug\Payplug::setSecretKey("$secret_key");
try {
  $payment = \Payplug\Payment::create(array(
    'amount'         => $amount,
    'currency'       => 'EUR',
    'save_card'      => false,
    'force_3ds'      => boolval($conf->global->PAYPLUG_FORCE_3DSECURE),
    'payment_method' => $token,
    'customer'       => array(
    'email'          => $refemail,
    'first_name'     => $refname,
    'last_name'      => $refname

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
print "<DIV><h3>".$langs->trans('PAYPLUG_THANK')." " . $payment->customer->email . ".</h3></DIV>";
} else {
  var_dump($e); 
print "<DIV><STRONG>Error !</STRONG><BR />". $payment->failure->message ." (" . $payment->failure->code . ").</DIV>";
}
print "</DIV>";
}
else {
if ($conf->global->PAYPLUG_MODE == TEST){$publish_key=$conf->global->PAYPLUG_PK_TEST;}
elseif ($conf->global->PAYPLUG_MODE == LIVE){$publish_key=$conf->global->PAYPLUG_PK_LIVE;}     
?>
<SCRIPT type="text/javascript">
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
</SCRIPT>
<?php
print "<form action='$return' method='POST' id='signupForm' class='form' novalidate data-payplug='form'><DIV class='row'>
<DIV class='col-md-12'><DIV class='input-error-wrapper' id='error-api'><P class='input-error' id='error-api-bad'></P></DIV></DIV>
<DIV class='col-md-12'><div class='form-group'><div class='input-group'>
<span class='input-group-addon'><span class='fa fa-credit-card'></span></span>
<INPUT type='number' class='form-control input-lg' placeholder='".$langs->trans('PAYPLUG_CARDNUMBER')."' value='' autocomplete='off' data-payplug='card_number' maxlength='17'></div>
<span id='helpBlock' class='help-block'><DIV class='input-error-wrapper' id='error-card'><P class='input-error' id='error-card-bad' style='display:none;'>".$langs->trans('PAYPLUG_ERROR_CARD')."</P></SPAN>
</DIV></DIV></DIV>
<DIV class='col-md-8'><div class='form-group'><div class='input-group'>
<span class='input-group-addon'><span class='fa fa-calendar'></span></span>
<INPUT type='text' class='form-control input-lg' placeholder='".$langs->trans('PAYPLUG_CARDEXPI')."' autocomplete='off' data-payplug='card_month_year' maxlength='7'></div>
<span id='helpBlock' class='help-block'><DIV class='input-error-wrapper' id='error-expiry'><P class='input-error' id='error-expiry-bad' style='display:none;'>".$langs->trans('PAYPLUG_ERROR_EXPI')."</P></SPAN>
</DIV></DIV></DIV>
<DIV class='col-md-4'><div class='form-group'><div class='input-group'>
<span class='input-group-addon'><span class='fa fa-lock'></span></span>
<INPUT type='number' class='form-control input-lg' placeholder='CVV' autocomplete='off' data-payplug='card_cvv' maxlength='3'></div>
<span id='helpBlock' class='help-block'><DIV class='input-error-wrapper' id='error-cvv'><P class='input-error' id='error-cvv-bad' style='display:none;'>".$langs->trans('PAYPLUG_ERROR_CVV')."</P></SPAN>
</DIV></DIV></DIV>
<DIV class='col-md-12'><INPUT type='hidden' name='validation' value='OK'><BUTTON type='submit' class='btn btn-danger btn-lg btn-block' data-payplug='submit'>".$langs->trans('PAYPLUG_PAID')." ".price($refamount)."  ".$langs->trans('Currency'.$conf->currency)."</BUTTON>
</DIV></DIV></FORM>
      ";
}}
elseif ($conf->global->PAYPLUG_OFFER == 'STARTER'){
$amount=round($refamount*100);

\Payplug\Payplug::setSecretKey("$secret_key"); 
$payment = \Payplug\Payment::create(array(
    'amount'         => $amount,
    'currency'       => 'EUR',
    'save_card'      => false,
    'force_3ds'      => boolval($conf->global->PAYPLUG_FORCE_3DSECURE),
    'customer'       => array(
    'email'          => $refemail,
    'first_name'     => $refname,
    'last_name'      => $refname
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
<SCRIPT type="text/javascript" src="https://api.payplug.com/js/1.0/form.js"></SCRIPT>
<SCRIPT type="text/javascript">
  document.addEventListener('DOMContentLoaded', function() {
    [].forEach.call(document.querySelectorAll("#signupForm"), function(el) {
      el.addEventListener('submit', function(event) {
        var payplug_url = '<?php echo $payment->hosted_payment->payment_url; ?>';
        Payplug.showPayment(payplug_url);
        event.preventDefault();
      })
    })
  })

</SCRIPT>
<?php
$amount2=round($refamount*100);
print "<FORM action'' method='post' id='signupForm' class='formulaire' novalidate><P><INPUT type='hidden' name='validation' value='OK'>
          <BUTTON type='submit' class='btn btn-danger btn-lg btn-block' data-payplug='submit' role='button'>".$langs->trans('PAYPLUG_PAID')." ".price($refamount)."  ".$langs->trans('Currency'.$conf->currency)."</BUTTON>
        </P>
      </FORM>"; 
} 
print "</DIV><DIV class='col-md-3'></DIV></div>";}
else {
print"<DIV class='row'><DIV class='col-md-4'>";
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
print"</DIV><DIV class='col-md-1'></DIV><DIV class='col-md-6'>";
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
  </div><INPUT type='hidden' name='validation' value='URLOK'>
  <button type='submit' class='btn btn-primary btn-block btn-lg' ";
if (empty($conf->global->PAYPLUG_ENABLE_PUBLIC)) print "disabled";
print ">".$langs->trans('Validate')."</button>
</form>";
print"</DIV><DIV class='col-md-1'></DIV></DIV>";
}
if (isset($conf->global->PAYPLUG_EMAIL_HELP)&&!empty($conf->global->PAYPLUG_ENABLE_PUBLIC)){$help=$langs->trans('PAYPLUG_HELP')." <a href='mailto:".$conf->global->PAYPLUG_EMAIL_HELP."?Subject=".$langs->trans('PAYPLUG_PUBLIC')."' target='_blank'>".$conf->global->PAYPLUG_EMAIL_HELP."</a>";}
print "<DIV class='col-md-12'><HR /><p style='text-align: center'>".$help."</p></DIV></DIV>";
$db->close();