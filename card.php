<?php
/* Copyright (C) 2019 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('productbycompany/class/productbycompany.class.php');
dol_include_once('productbycompany/lib/productbycompany.lib.php');

if(empty($user->rights->productbycompany->read)) accessforbidden();
$permissiondellink = $user->rights->webhost->write;	// Used by the include of actions_dellink.inc.php

$langs->load('productbycompany@productbycompany');

$productbycompany = new ProductByCompany($db);

$action = GETPOST('action');
$id = GETPOST('id');
$type = GETPOST('type');
$fk_productbycompany = GETPOST('fk_productbycompany');

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'productbycompanycard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

if (empty($id) || !in_array($type, array('product', 'company')))
{
    accessforbidden();
}
elseif ($type === 'product')
{
    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

    $langs->loadLangs(array('products', 'other'));

    $object = new Product($db);
    $object->fetch($id);

    $head = product_prepare_head($object);
    $picto = ($object->type== Product::TYPE_SERVICE?'service':'product');
    $title = $langs->trans("CardProduct".$object->type);
}
else
{
    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

    $langs->loadLangs(array("companies","commercial"));

    $object = new Societe($db);
    $object->fetch($id);

    $head = societe_prepare_head($object);
    $picto = 'company';
    $title = $langs->trans('ThirdParty');
}

$productbycompany->fetch($fk_productbycompany);

$hookmanager->initHooks(array('productbycompanycard', 'globalcard'));


if ($productbycompany->isextrafieldmanaged)
{
    $extrafields = new ExtraFields($db);

    $extralabels = $extrafields->fetch_name_optionals_label($productbycompany->table_element);
    $search_array_options = $extrafields->getOptionalsFromPost($productbycompany->table_element, '', 'search_');
}

// Initialize array of search criterias
//$search_all=trim(GETPOST("search_all",'alpha'));
//$search=array();
//foreach($object->fields as $key => $val)
//{
//    if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
//}

/*
 * Actions
 */

$parameters = array('id' => $id, 'ref' => $ref, 'productbycompany' => $productbycompany);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Si vide alors le comportement n'est pas remplacé
if (empty($reshook))
{

    if ($cancel)
    {
        if (! empty($backtopage))
        {
            header("Location: ".$backtopage);
            exit;
        }
        $action='';
    }

    // For object linked
    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once




    $error = 0;
	switch ($action) {
		case 'add':
		case 'update':
        $productbycompany->setValues($_REQUEST); // Set standard attributes

            if ($productbycompany->isextrafieldmanaged)
            {
                $ret = $extrafields->setOptionalsFromPost($extralabels, $productbycompany);
                if ($ret < 0) $error++;
            }

//			$productbycompany->date_other = dol_mktime(GETPOST('starthour'), GETPOST('startmin'), 0, GETPOST('startmonth'), GETPOST('startday'), GETPOST('startyear'));

			// Check parameters
//			if (empty($productbycompany->date_other))
//			{
//				$error++;
//				setEventMessages($langs->trans('warning_date_must_be_fill'), array(), 'warnings');
//			}
			
			// ...

			if ($error > 0)
			{
				$action = 'edit';
				break;
			}
			
			$res = $productbycompany->save($user);
            if ($res < 0)
            {
                setEventMessage($productbycompany->errors, 'errors');
                if (empty($productbycompany->id)) $action = 'create';
                else $action = 'edit';
            }
            else
            {
                header('Location: '.dol_buildpath('/productbycompany/card.php', 1).'?id='.$productbycompany->id);
                exit;
            }
        case 'update_extras':

            $productbycompany->oldcopy = dol_clone($productbycompany);

            // Fill array 'array_options' with data from update form
            $ret = $extrafields->setOptionalsFromPost($extralabels, $productbycompany, GETPOST('attribute', 'none'));
            if ($ret < 0) $error++;

            if (! $error)
            {
                $result = $productbycompany->insertExtraFields('PRODUCTBYCOMPANY_MODIFY');
                if ($result < 0)
                {
                    setEventMessages($productbycompany->error, $productbycompany->errors, 'errors');
                    $error++;
                }
            }

            if ($error) $action = 'edit_extras';
            else
            {
                header('Location: '.dol_buildpath('/productbycompany/card.php', 1).'?id='.$productbycompany->id);
                exit;
            }
            break;
	}
}

/**
 * View
 */
$form = new Form($db);

$title=$langs->trans('ProductByCompany');
llxHeader('', $title);

if ($action == 'create')
{
    print load_fiche_titre($langs->trans('NewProductByCompany'), '', 'productbycompany@productbycompany');

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

    dol_fiche_head(array(), '');

    print '<table class="border centpercent">'."\n";

    // Common attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    print '</table>'."\n";

    dol_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans('Create')).'">';
    print '&nbsp; ';
    print '<input type="'.($backtopage?"submit":"button").'" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans('Cancel')).'"'.($backtopage?'':' onclick="javascript:history.go(-1)"').'>';	// Cancel for create does not post form if we don't know the backtopage
    print '</div>';

    print '</form>';
}
else
{

    if (empty($object->id))
    {
        $langs->load('errors');
        print $langs->trans('ErrorRecordNotFound');
    }
    else
    {
        if (!empty($object->id) && $action === 'edit')
        {
            print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';

            $head = productbycompany_prepare_head($object);
            $picto = 'productbycompany@productbycompany';
            dol_fiche_head($head, 'card', $langs->trans('ProductByCompany'), 0, $picto);

            print '<table class="border centpercent">'."\n";

            // Common attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

            // Other attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

            print '</table>';

            dol_fiche_end();

            print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
            print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
            print '</div>';

            print '</form>';
        }
        elseif ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
        {
            dol_fiche_head($head, 'productbycompanytab', $title, -1, $picto);

            $formconfirm = getFormConfirmProductByCompany($form, $productbycompany, $action);
            if (!empty($formconfirm)) print $formconfirm;


            //$linkback = '<a href="' .dol_buildpath('/productbycompany/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

            //$morehtmlref='<div class="refidno">';
            /*
            // Ref bis
            $morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->productbycompany->write, 'string', '', 0, 1);
            $morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->productbycompany->write, 'string', '', null, null, '', 1);
            // Thirdparty
            $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
            */
            //$morehtmlref.='</div>';


            $morehtmlstatus.=''; //$object->getLibStatut(2); // pas besoin fait doublon
            dol_banner_tab($object, '', '', ($user->socid?0:1));

            print '<div class="fichecenter">';

//            print '<div class="fichehalfleft">'; // Auto close by commonfields_view.tpl.php
            print '<div class="underbanner clearboth"></div>';
            print '<table class="border tableforfield" width="100%">'."\n";


            if ($type === 'product')
            {
                print '<tr>';
                print '<td class="titlefield fieldrequired">'.$langs->trans($productbycompany->fields['fk_soc']['label']).'</td>';
                print '<td>'.$productbycompany->showOutputField($productbycompany->fields['fk_soc'], 'fk_soc', $productbycompany->fk_soc, '', '', '', 0).'</td>';
                print "</tr>\n";
            }
            else
            {
                print '<tr>';
                print '<td class="titlefield fieldrequired">'.$langs->trans($productbycompany->fields['fk_product']['label']).'</td>';
                print '<td>'.$productbycompany->showOutputField($productbycompany->fields['fk_product'], 'fk_product', $productbycompany->fk_product, '', '', '', 0).'</td>';
                print "</tr>\n";
            }

            print '<tr>';
            print '<td class="titlefield">'.$langs->trans($productbycompany->fields['ref']['label']).'</td>';
            print '<td>'.$productbycompany->showOutputField($productbycompany->fields['ref'], 'ref', $productbycompany->ref, '', '', '', 0).'</td>';
            print "</tr>\n";

            print '<tr>';
            print '<td class="titlefield">'.$langs->trans($productbycompany->fields['label']['label']).'</td>';
            print '<td>'.$productbycompany->showOutputField($productbycompany->fields['label'], 'label', $productbycompany->label, '', '', '', 0).'</td>';
            print "</tr>\n";

            // Common attributes
            //$keyforbreak='fieldkeytoswithonsecondcolumn';
//            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

            // Other attributes
//            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

            print '</table>';

//            print '</div></div>'; // Fin fichehalfright & ficheaddleft
            print '</div>'; // Fin fichecenter

            print '<div class="clearboth"></div><br />';

            print '<div class="tabsAction">'."\n";
            $parameters=array();
            $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
            if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

            if (empty($reshook))
            {
                if (!empty($user->rights->productbycompany->write))
                {
                    // Modify
                    print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&type='.$type.'&fk_productbycompany='.$productbycompany->id.'&action=edit">'.$langs->trans("ProductByCompanyModify").'</a></div>'."\n";

                    // Delete
                    print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&type='.$type.'&fk_productbycompany='.$productbycompany->id.'&action=delete">'.$langs->trans("ProductByCompanyDelete").'</a></div>'."\n";

                }
                else
                {

                    // Modify
                    print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("ProductByCompanyModify").'</a></div>'."\n";

                    // Delete
                    print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("ProductByCompanyDelete").'</a></div>'."\n";
                }
            }
            print '</div>'."\n";

            dol_fiche_end(-1);
        }
    }
}


llxFooter();
$db->close();
