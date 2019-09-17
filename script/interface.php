<?php
require ('../config.php');
dol_include_once('/productbycompany/class/productbycompany.class.php');
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";

$get = GETPOST('get');

switch ($get)
{
	case 'getCustomRefCreateFields':
		print getCustomRefCreateFields(GETPOST('id_prod'), GETPOST('fk_soc'));
}


function getCustomRefCreateFields($id_prod, $fk_soc)
{
	global $db, $langs;

	if (empty($id_prod)) return '';
	$langs->load('productbycompany@productbycompany');

	$form = new Form($db);

	$customRef = new ProductByCompany($db);

	$customRef->fk_soc = $fk_soc;
	$customRef->fk_product = $id_prod;

	// récupérer la custom ref si elle existe pour le couple produit/tiers
	$moreparam = "";
	$options = array('none' => '', 'custom' => 'personnaliser');
	$exists = $customRef->alreadyExists();
	if ($exists > 0)
	{
		$customRef->fetchByArray(array('fk_product'=>$id_prod, 'fk_soc'=>$fk_soc), false);
		$options[$customRef->id] = $customRef->ref;
		$moreparam = "data-ref='$customRef->ref' data-label='$customRef->label'";
	}

	$out = '<br>';
	$out.= $langs->trans('CustomRef').'&nbsp;';
	// créer le selectarray avec rien/custom/la ref existante
	$out.= $form->selectarray('customRefSelect', $options,'',0, 0, 0, $moreparam);
	$out.= '<script type="text/javascript">
				$("#customRefSelect").on("change",function()
				{
				    if($(this).val() !== \'none\')
				    {
				        $("#customRef").val($(this).data("ref"));
				    	$("#customLabel").val($(this).data("label"))
				    }
				    else
					{
						$("#customRef").val(\'\');
						$("#customLabel").val(\'\');
					}
				})</script>';
	$out.= '&nbsp;'.$langs->trans('Ref');
	$out.= '<input type="text" name="customRef" id="customRef">';
	$out.= '&nbsp;'.$langs->trans('Label');
	$out.= '<input type="text" name="customLabel" id="customLabel">';

	if ($exists > 0)
	{
		$out.= '&nbsp;<label for="majCustomRef"><input type="checkbox" name="majCustomRef" id="majCustomRef"> '.$langs->trans('majCustomRef').'</label>';
	}

	return $out;
}
