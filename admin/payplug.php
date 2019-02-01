<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013	Laurent Destailleur		<eldy@users.sourceforge.org>
 * Copyright (C) 2011-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012  Juanjo Menent			<jmenent@2byte.es>
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
 */

/**
 * \file       htdocs/paypal/admin/paypal.php
 * \ingroup    paypal
 * \brief      Page to setup paypal module
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

$servicename='Payplug';

$langs->load("admin");
$langs->load("payplug@payplug");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');

if ($action == 'setvalue' && $user->admin)
{
	$db->begin();
    $result=dolibarr_set_const($db, "PAYPLUG_MODE",GETPOST('PAYPLUG_MODE','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPLUG_OFFER",GETPOST('PAYPLUG_OFFER','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPLUG_SK_LIVE",GETPOST('PAYPLUG_SK_LIVE','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPLUG_SK_TEST",GETPOST('PAYPLUG_SK_TEST','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;             
    $result=dolibarr_set_const($db, "PAYPLUG_PK_LIVE",GETPOST('PAYPLUG_PK_LIVE','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;             
    $result=dolibarr_set_const($db, "PAYPLUG_PK_TEST",GETPOST('PAYPLUG_PK_TEST','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
	 	$result=dolibarr_set_const($db, "PAYPLUG_ID_BANKACCOUNT",(GETPOST('PAYPLUG_ID_BANKACCOUNT','alpha') > 0 ? GETPOST('PAYPLUG_ID_BANKACCOUNT','alpha') : ''),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
	 	$result=dolibarr_set_const($db, "PAYPLUG_ID_AUTO",(GETPOST('PAYPLUG_ID_AUTO','alpha') > 0 ? GETPOST('PAYPLUG_ID_AUTO','alpha') : ''),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db,"PAYPLUG_ID_WAREHOUSE",(GETPOST('PAYPLUG_ID_WAREHOUSE','alpha') > 0 ? GETPOST('PAYPLUG_ID_WAREHOUSE','alpha') : ''),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db,"PAYPLUG_ENABLE_PUBLIC",(GETPOST('PAYPLUG_ENABLE_PUBLIC','alpha') > 0 ? GETPOST('PAYPLUG_ENABLE_PUBLIC','alpha') : ''),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPLUG_FORCE_3DSECURE",GETPOST('PAYPLUG_FORCE_3DSECURE','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "PAYPLUG_EMAIL_HELP",GETPOST('PAYPLUG_EMAIL_HELP','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
	if (! $error)
  	{
  		$db->commit();
  		setEventMessage($langs->trans("SetupSaved"));
  	}
  	else
  	{
  		$db->rollback();
		dol_print_error($db);
    }
}


/*
 *	View
 */

$form=new Form($db);
$formproduct=new FormProduct($db);

llxHeader('',$langs->trans("PayplugSetup"));


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("ModuleSetup").' Payplug',$linkback);
print '<br>';

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';


dol_fiche_head ( $head, 'payplug', $langs->trans ( "PayplugSetup" ), 0, "payplug@payplug" );

print $langs->trans("PayplugDesc")."<br>\n";

if (! empty($conf->multicompany->enabled))  {
$linkentity="?entity=".$conf->entity;
}
print '<p>' . $langs->trans("PAYPLUG_PUBLIC_URL") . ' <a href="' . dol_buildpath('/payplug/public/newpayment.php'.$linkentity.'', 1) . '" target="_blank" >' . dol_buildpath('/payplug/public/newpayment.php'.$linkentity.'', 2) . '</a></p>';

print '<table class="noborder" width="100%">';

$var=true;
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("PAYPLUG_PARAMETER").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("PAYPLUG_MODE").'</td><td>';
$tmplist=array('TEST'=>TEST, 'LIVE'=>LIVE);
print $form->selectarray('PAYPLUG_MODE', $tmplist, $conf->global->PAYPLUG_MODE);
print '</td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("PAYPLUG_OFFER").'</td><td>';
$tmplist=array('STARTER'=>$langs->trans("PAYPLUG_STARTER"), 'PREMIUM'=>$langs->trans("PAYPLUG_PREMIUM"));
print $form->selectarray('PAYPLUG_OFFER', $tmplist, $conf->global->PAYPLUG_OFFER);
print '</td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("PAYPLUG_SK_TEST").'</td><td>';
print '<input size="48" type="text" name="PAYPLUG_SK_TEST" value="'.$conf->global->PAYPLUG_SK_TEST.'">';
print ' &nbsp; '.$langs->trans("Example").': SK_TEST_1234566789';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("PAYPLUG_SK_LIVE").'</td><td>';
print '<input size="48" type="text" name="PAYPLUG_SK_LIVE" value="'.$conf->global->PAYPLUG_SK_LIVE.'">';
print ' &nbsp; '.$langs->trans("Example").': SK_LIVE_1234566789';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("PAYPLUG_PK_TEST").'</td><td>';
print '<input size="48" type="text" name="PAYPLUG_PK_TEST" value="'.$conf->global->PAYPLUG_PK_TEST.'">';
print ' &nbsp; '.$langs->trans("Example").': PK_TEST_1234566789';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("PAYPLUG_PK_LIVE").'</td><td>';
print '<input size="48" type="text" name="PAYPLUG_PK_LIVE" value="'.$conf->global->PAYPLUG_PK_LIVE.'">';
print ' &nbsp; '.$langs->trans("Example").': PK_LIVE_1234566789';
print '</td></tr>';

$var=true;
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("PAYPLUG_PARAMETER").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYPLUG_ACCOUNT").'</td><td>';
print $form->select_comptes($conf->global->PAYPLUG_ID_BANKACCOUNT,'PAYPLUG_ID_BANKACCOUNT',0,"courant=1",1);
print ' &nbsp; '.$langs->trans("PAYPLUG_ACCOUNT_EX");
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYPLUG_AUTO").'</td><td>';
print $form->select_users($conf->global->PAYPLUG_ID_AUTO, 'PAYPLUG_ID_AUTO', 1, "", 0);
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYPLUG_WAREHOUSE").'</td><td>'; 
print $formproduct->selectWarehouses($conf->global->PAYPLUG_ID_WAREHOUSE,'PAYPLUG_ID_WAREHOUSE','',1,$disabled);
print '</td></tr>';

$var=! $var;
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYPLUG_FORCE_3DSECURE");
print '</td><td>';
$tmplist2=array('0'=>$langs->trans("PAYPLUG_FORCE_3DSECUREDISABLED"), '1'=>$langs->trans("PAYPLUG_FORCE_3DSECUREACTIVATED"));
print $form->selectarray('PAYPLUG_FORCE_3DSECURE', $tmplist2, $conf->global->PAYPLUG_FORCE_3DSECURE);
print "</td></tr>";

$var=! $var;
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("PAYPLUG_ENABLE_PUBLIC");
print '</td><td>';
print $form->selectyesno("PAYPLUG_ENABLE_PUBLIC",(! empty($conf->global->PAYPLUG_ENABLE_PUBLIC)?$conf->global->PAYPLUG_ENABLE_PUBLIC:0),1);
print "</td></tr>";

// Texte de signature
$var=! $var;
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr><td>' . $langs->trans("PAYPLUG_EMAIL_HELP") . '</label>';
print '</td><td>';
print '<input type="email" name="PAYPLUG_EMAIL_HELP" value="' . $conf->global->PAYPLUG_EMAIL_HELP . '" size="40" ></td>';
print '</td></tr>';

print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

print '<br><br>';

print '<div id="apidoc"><p>' . $langs->trans("PAYPLUG_IPN_URL") . ' <a href="' . dol_buildpath('/payplug/public/ipn.php'.$linkentity.'', 1) . '" target="_blank" >' . dol_buildpath('/payplug/public/ipn.php'.$linkentity.'', 2) . '</a><br/><br/>';
print 'METADATA to use for external module connexion with this module<br/>';
print '- entity ex: 1<br/>'; 
print '- type order or invoice<br/>';
print '- ref ex CU1701-1234 or FA1601-1234<br/>';
print '- customer ex: 345 (customer rowid)<br/>'; 
print '- member ex: 45 (member rowid)<br/>';
print '- typeadherent ex: 2 ( type rowid), default 0 for inactive member action<br/>';
print '- cotisationstart ex: 1487290754 in timestamp, default 0<br/>';
print '- cotisationend ex: 1518913154 in timestamp, default 0<br/>';
print '- cotisationamount ex:40.00, default 0 for inactive member action<br/>';
print '</div>';


llxFooter();
$db->close();
