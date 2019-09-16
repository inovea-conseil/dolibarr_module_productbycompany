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
dol_include_once('productbycompany/class/productbycompany.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

if(empty($user->rights->productbycompany->read)) accessforbidden();

$langs->load('abricot@abricot');
$langs->load('productbycompany@productbycompany');

$productbycompany = new ProductByCompany($db);

$massaction = GETPOST('massaction', 'alpha');
$confirmmassaction = GETPOST('confirmmassaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

$id = GETPOST('id');
$type = GETPOST('type');

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
    $titlelist = $langs->trans('ProductByCompanyListFromProduct');
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
    $titlelist = $langs->trans('ProductByCompanyListFromCompany');
}

$hookmanager->initHooks(array('productbycompanylist'));

/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend')
{
    $massaction = '';
}


if (empty($reshook))
{
	// do action from GETPOST ...
}


/*
 * View
 */

llxHeader('', $title, '', '');

// TODO ajouter les champs de son objet que l'on souhaite afficher
$keys = array_keys($productbycompany->fields);
$fieldList = 't.'.implode(', t.', $keys);
if (!empty($productbycompany->isextrafieldmanaged))
{
    $keys = array_keys($extralabels);
	if(!empty($keys)) {
		$fieldList .= ', et.' . implode(', et.', $keys);
	}
}
$fieldList.= ', t.rowid AS fk_productbycompany';

$sql = 'SELECT '.$fieldList;

// Add fields from hooks
$parameters=array('sql' => $sql, 'productbycompany' => $productbycompany);
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= ' FROM '.MAIN_DB_PREFIX.'product_by_company t ';

if (!empty($object->isextrafieldmanaged))
{
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_by_company_extrafields et ON (et.fk_object = t.rowid)';
}

$sql.= ' WHERE 1=1';
if ($type === 'product') $sql.= ' AND fk_product = '.$object->id;
else $sql.= ' AND fk_soc = '.$object->id;

//$sql.= ' AND t.entity IN ('.getEntity('ProductByCompany', 1).')';
//if ($type == 'mine') $sql.= ' AND t.fk_user = '.$user->id;

// Add where from hooks
$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;


dol_fiche_head($head, 'productbycompanytab', $title, -1, $picto);

dol_banner_tab($object, '', '', ($user->socid?0:1));

// PRINT LIST
$newcardbutton = '';
if ($user->rights->productbycompany->write)
{
    $backtopage=dol_buildpath('productbycompany/list.php', 1).'?origin_id='.$object->id.'&type='.$type;
    $addproductbycomapny = $langs->trans("AddNewProductByCompany");
    $fk = $type=='product' ? "&fk_product=".$object->id : "&fk_soc=".$object->id ;
    $newcardbutton.='<a class="butActionNew" href="'.dol_buildpath('productbycompany/card.php', 1).'?action=create&origin_id='.$object->id.'&type='.$type.$fk.'&backtopage='.urlencode($backtopage).'"><span class="valignmiddle">'.$addproductbycomapny.'</span>';
    $newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
    $newcardbutton.= '</a>';
}


$formcore = new TFormCore($_SERVER['PHP_SELF'], 'form_list_productbycompany', 'GET');

$nbLine = !empty($user->conf->MAIN_SIZE_LISTE_LIMIT) ? $user->conf->MAIN_SIZE_LISTE_LIMIT : $conf->global->MAIN_SIZE_LISTE_LIMIT;

$r = new Listview($db, 'productbycompany');
echo $r->render($sql, array(
	'view_type' => 'list' // default = [list], [raw], [chart]
    ,'allow-fields-select' => true
	,'limit'=>array(
		'nbLine' => $nbLine
	)
    ,'list' => array(
        'title' => $titlelist
        ,'morehtmlrighttitle' => $newcardbutton
        ,'image' => 'title_generic.png'
        ,'picto_precedent' => '<'
        ,'picto_suivant' => '>'
        ,'noheader' => 0
        ,'messageNothing' => $langs->trans('NoProductByCompany')
        ,'picto_search' => img_picto('', 'search.png', '', 0)
        ,'massactions'=>array(
            'yourmassactioncode'  => $langs->trans('YourMassActionLabel')
        )
    )
	,'subQuery' => array()
	,'link' => array(
	    'fk_productbycompany' => '<a href="'.dol_buildpath('productbycompany/card.php', 1).'?origin_id='.$object->id.'&type='.$type.'&fk_productbycompany=@val@">@val@</a>'
    )
	,'type' => array(
		'date_creation' => 'date' // [datetime], [hour], [money], [number], [integer]
		,'tms' => 'date'
	)
	,'search' => array(
		'date_creation' => array('search_type' => 'calendars', 'allow_is_null' => true)
		,'tms' => array('search_type' => 'calendars', 'allow_is_null' => false)
		,'ref' => array('search_type' => true, 'table' => 't', 'field' => 'ref')
		,'label' => array('search_type' => true, 'table' => array('t', 't'), 'field' => array('label')) // input text de recherche sur plusieurs champs
	)
	,'translate' => array()
	,'hide' => array(
		'rowid' // important : rowid doit exister dans la query sql pour les checkbox de massaction
	)
	,'title'=>array(
		'fk_productbycompany' => $langs->trans('ID.')
		,'ref' => $langs->trans('Ref.')
		,'label' => $langs->trans('Label')
		,'date_creation' => $langs->trans('DateCre')
		,'tms' => $langs->trans('DateMaj')

	)
	,'eval'=>array()
));

$parameters=array('sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

$formcore->end_form();

dol_fiche_end();

llxFooter('');
$db->close();
