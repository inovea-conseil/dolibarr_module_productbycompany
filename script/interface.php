<?php
require ('../config.php');
dol_include_once('/productbycompany/class/productbycompany.class.php');
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";

$get = GETPOST('get');

switch ($get)
{
	case 'getCustomRefCreateFields':
		print getCustomRefCreateFields(GETPOST('id_prod'), GETPOST('fk_soc'));
	case 'getCustomRefEditFields':
		print getCustomRefEditFields(GETPOST('id'), GETPOST('element_type'), GETPOST('fk_product'));
}


function getCustomRefCreateFields($id_prod, $fk_soc)
{
	global $db, $langs, $conf;

	if (empty($id_prod)) return '';
	$langs->load('productbycompany@productbycompany');

	$form = new Form($db);

	$customRef = new ProductByCompany($db);

	$customRef->fk_soc = $fk_soc;
	$customRef->fk_product = $id_prod;
	$customRef->fetch_product();

	// récupérer la custom ref si elle existe pour le couple produit/tiers
	$exists = $customRef->alreadyExists();
	if ($exists > 0)
	{
		$customRef->fetch($exists);
		$options[$customRef->id] = $customRef->ref;
		$moreparam = "data-ref='$customRef->ref' data-label='$customRef->label'";
	}

	$out = '<br>';
	// créer le selectarray avec rien/custom/la ref existante
	if ($exists > 0 && empty($conf->global->PBC_DONT_PRESELECT_CUSTOM_REF)) $checked = 'checked';
	$out.= '<input type="checkbox" name="customRefSelect" id="customRefSelect" style="display: none;"'.$checked.'>';

	if ($exists > 0) $out.= "<input type='hidden' name='customRowid' value='".$customRef->id."'>";

	$out.= '<p>'.$langs->trans('Ref');
	$out.= '<input type="text" name="customRef" id="customRef" value="'.($exists <= 0 ? $customRef->product->ref: $customRef->ref ).'"></p>';
	$out.= '<p>'.$langs->trans('Label');
	$out.= '<input type="text" name="customLabel" id="customLabel" value="'.($exists <= 0 ? $customRef->product->label: $customRef->label ).'" size="70%"></p>';

	if (empty($exists))
		$cb_label = 'CreateCustomRef';
	else
		$cb_label = 'majCustomRef';

	$out.= '<p><label for="majCustomRef" id="customCB"><input type="checkbox" name="majCustomRef" id="majCustomRef" '.(empty($exists) ? 'checked' : '').'> '.$langs->trans($cb_label).'</label></p>';

	if (!empty($exists) && empty($conf->global->PBC_DONT_PRESELECT_CUSTOM_REF))
	{
		$out.='<script type="text/javascript">
			$("#js_fieldset").show();
			$("#btnCustomRef").html("- '.$langs->trans('Customize').'");
//			$("#customRefSelect").click();
		</script>';
	}
	return $out;
}

function getCustomRefEditFields($id, $element_type,$fk_product)
{
	global $db, $langs, $conf;

	if (empty($id)) return '';
	$langs->load('productbycompany@productbycompany');

	$form = new Form($db);

	$customRef = new ProductByCompanyDet($db);
	$customRef->fk_origin = $id;
	$customRef->origin_type = $element_type;

	// récupérer la custom ref si elle existe pour la ligne indiquée
	$options = array('none' => '', 'custom' => $langs->trans('customize'));
	$exists = $customRef->alreadyExists();
	if ($exists > 0)
	{
		$customRef->fetch($exists);
	}
	else
	{
		$customRef->fk_product = $fk_product;
		$customRef->fetch_product();
		$customRef->ref = $customRef->product->ref;
		$customRef->label = $customRef->product->label;
	}

	$out = '';
	// créer le selectarray avec rien/custom/la ref existante
	if ($exists > 0) $checked = 'checked';
	$out.= '<input type="checkbox" name="customRefSelect" id="customRefSelect" style="display: none;"'.$checked.'>';

	if ($exists > 0) $out.= "<input type='hidden' name='customDetRowid' value='".$customRef->id."'>";

	$out.= '<p>'.$langs->trans('Ref');
	$out.= '<input type="text" name="customRef" id="customRef" value="'.$customRef->ref.'" ></p>';
	$out.= '<p>'.$langs->trans('Label');
	$out.= '<input type="text" name="customLabel" id="customLabel" value="'.$customRef->label.'" ></p>';

	$out.= '<p><label for="majCustomRef" id="customCB" ><input type="checkbox" name="majCustomRef" id="majCustomRef"> '.$langs->trans('majCustomRef').'</label></p>';

	if (!empty($exists))
	{
		$out.='<script type="text/javascript">
			$("#js_fieldset").show();
			$("#btnCustomRef").html("- '.$langs->trans('Customize').'");
//			$("#customRefSelect").click();
		</script>';
	}

	return $out;
}
