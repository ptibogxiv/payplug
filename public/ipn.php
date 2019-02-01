<?php
/* Copyright (C) 2016-2017	Thibault FOUCART	<ptibogxiv@ptibogxiv.net>
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

//require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php'; 
dol_include_once('/payplug/lib/init.php');

if ($conf->global->PAYPLUG_MODE == 'TEST'){$secret_key=$conf->global->PAYPLUG_SK_TEST;}
elseif ($conf->global->PAYPLUG_MODE == 'LIVE'){$secret_key=$conf->global->PAYPLUG_SK_LIVE;}

// Security check
\Payplug\Payplug::setSecretKey($secret_key);  

$input = file_get_contents('php://input');
try {
$resource = \Payplug\Notification::treat($input);
$total = $resource->amount/100;
$reference = $resource->metadata['ref'];
$source = $resource->metadata['source'];
$dolibarr = $resource->metadata['customer'];
$adherent = $resource->metadata['member'];
$entite = $resource->metadata['entity'];
$cotisationstart = $resource->metadata['cotisationstart'];
$cotisationend = $resource->metadata['cotisationend'];
$yearcot = strftime('%Y',$cotisationend);
$cotisationamount = $resource->metadata['cotisationamount'];
$typeadherent = $resource->metadata['typeadherent'];

$date = date("Y-m-d H:i:s");
$date2 = date("Y-m-d");
$payment_id = $resource->id;
$commercial = $resource->metadata['firstName']." ".$resource->metadata['lastName'];
 
  if ($resource instanceof \Payplug\Resource\Payment 
    && $resource->is_paid 
    // Ensure that the payment was paid
    ) { 
// Process a paid payment.
$langs->load("main");				// To load language file for default language
@set_time_limit(0);

$object = new User($db);
$object->fetch($conf->global->PAYPLUG_ID_AUTO);
// Load user and its permissions
$result=$user->fetch('',$object->login);	// Load user for login 'admin'. Comment line to run as anonymous user.
if (! $result > 0) { dol_print_error('',$user->error); exit; }
$user->getrights();
$db->begin();

// create Facture if order -----------------------------------------------------------------------------------------------------------
if ($source=='order') {
$order=new Commande($db);
$order->fetch('',$reference);
$invoice = new Facture($db);  
$invoice->createFromOrder($order);
$idinv=$invoice->create($user);
if ($idinv > 0)
{
	// Change status to validated
	$result=$invoice->validate($user);
	if ($result > 0) {
$order->classifyBilled($user);

}
	else
	{
		$error++;
		dol_print_error($db,$obj->error);
	}
}
else
{
	$error++;
	dol_print_error($db,$obj->error);
}
}
elseif ($source=='invoice') {
$invoice=new Facture($db);
$$result=$invoice->fetch('',$reference);
if ($result > 0)
	{ 

$idinv=$invoice->id;

}
}       

// -------------------- END OF YOUR CODE --------------------
if (! $error)
{
$invoice->set_paid($user);
if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE) && count($invoice->lines))
			{
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $invoice->thirdparty->default_lang;
				if (! empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model=$invoice->modelpdf;
				$ret = $invoice->fetch($idinv); // Reload to get new records

				$invoice->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);

			}
$db->query("INSERT ".MAIN_DB_PREFIX."bank SET amount = '$total', datec ='$date',datev ='$date2',dateo ='$date2', label='(CustomerInvoicePayment)', fk_account='".$conf->global->PAYPLUG_ID_BANKACCOUNT."', num_chq='$payment_id', fk_user_author = '".$conf->global->PAYPLUG_ID_AUTO."', fk_type = 'CB' ");
$idbank=$db->last_insert_id('llx_bank');
$db->query("INSERT ".MAIN_DB_PREFIX."paiement SET entity ='1', datec='$date', datep ='$date', amount='$total', multicurrency_amount='$total', fk_user_creat='".$conf->global->PAYPLUG_ID_AUTO."', fk_paiement='6', note='via Payplug', num_paiement='$payment_id', fk_bank='$idbank', statut='0' ");
$idpay=$db->last_insert_id('llx_paiement');
$db->query("INSERT ".MAIN_DB_PREFIX."paiement_facture set fk_paiement='$idpay', fk_facture='$invoice', amount='$total', multicurrency_amount='$total'");
$db->query("INSERT ".MAIN_DB_PREFIX."bank_url SET fk_bank='$idbank', url_id='$dolibarr', url='/comm/card.php?socid=', label='$commercial', type='company'");
$db->query("INSERT ".MAIN_DB_PREFIX."bank_url SET fk_bank='$idbank', url_id='$idpay', url='/compta/paiement/card.php?id=', label='(paiement)', type='payment'");
//$db->query("INSERT ".MAIN_DB_PREFIX."payplug_payment SET entity='$entity',payment_id='$payment_id',fk_invoice='$invoice',last4='".$resource->card->last4."',exp_month='".$resource->card->exp_month."',exp_year='".$resource->card->exp_year."',brand='".$resource->card->brand.",fk_country='".$resource->card->country."'");
$db->commit();
}
else
{
	print '--- end error code='.$error."\n";
	$db->rollback();
}

$db->close();
return $error;           
//*****
  } else if ($resource instanceof \Payplug\Resource\Refund) {
    // Process the refund.  to do
  }
}
catch (\Payplug\Exception\PayplugException $exception) {
 // Handle errors to do
}