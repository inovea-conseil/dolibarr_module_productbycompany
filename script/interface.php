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
		print getCustomRefEditFields(GETPOST('id'), GETPOST('element_type'));
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
	$options = array('none' => '', 'custom' => $langs->trans('customize'));
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
	if ($exists > 0) $out.= "<input type='hidden' name='customRowid' value='".$customRef->id."'>";
	$out.= '<script type="text/javascript">
				$("#customRefSelect").on("change",function()
				{
				    if($(this).val() != \'none\')
				    {
				        $("#customRef").val($(this).data("ref"));
				    	$("#customLabel").val($(this).data("label"))
				    	if ($(this).val() != \'custom\')
				    	{
				    	    $("#customRef").attr("readonly", true);
				    	    $("#customLabel").attr("readonly", true);
				    	    $("#customCB").hide()
				    	}
				    	else
						{
							$("#customRef").attr("readonly", false);
				    	    $("#customLabel").attr("readonly", false);
				    	    $("#customCB").show()
						}
				    }
				    else
					{
						$("#customRef").val(\'\');
						$("#customLabel").val(\'\');
						$("#customCB").hide()
					}
				});
				</script>';
	$out.= '&nbsp;'.$langs->trans('Ref');
	$out.= '<input type="text" name="customRef" id="customRef">';
	$out.= '&nbsp;'.$langs->trans('Label');
	$out.= '<input type="text" name="customLabel" id="customLabel">';

	if ($exists > 0)
	{
		$out.= '&nbsp;<label for="majCustomRef" id="customCB"><input type="checkbox" name="majCustomRef" id="majCustomRef"> '.$langs->trans('majCustomRef').'</label>';
	}

	return $out;
}

function getCustomRefEditFields($id, $element_type)
{
	global $db, $langs;

	if (empty($id)) return '';
	$langs->load('productbycompany@productbycompany');

	$form = new Form($db);

	$customRef = new ProductByCompanyDet($db);
	$customRef->fk_origin = $id;
	$customRef->origin_type = $element_type;

	// récupérer la custom ref si elle existe pour la ligne indiquée
	$moreparam = "";
	$options = array('none' => '', 'custom' => $langs->trans('customize'));
	$exists = $customRef->alreadyExists();
	if ($exists > 0)
	{
		$customRef->fetchByArray(array('fk_origin'=>$id, 'origin_type'=>$element_type), false);
		$options[$customRef->id] = $customRef->ref;
		$moreparam = "data-ref='$customRef->ref' data-label='$customRef->label'";
	}

	$out = '<br>';
	$out.= $langs->trans('CustomRef').'&nbsp;';
	// créer le selectarray avec rien/custom/la ref existante
	$out.= $form->selectarray('customRefSelect', $options,$customRef->id,0, 0, 0, $moreparam);
	if ($exists > 0) $out.= "<input type='hidden' name='customDetRowid' value='".$customRef->id."'>";
	$out.= '<script type="text/javascript">
				$("#customRefSelect").on("change",function()
				{
				    if($(this).val() != \'none\')
				    {
				        $("#customRef").val($(this).data("ref"));
				    	$("#customLabel").val($(this).data("label"))
				    	if ($(this).val() != \'custom\')
				    	{
				    	    $("#customRef").attr("readonly", true);
				    	    $("#customLabel").attr("readonly", true);
				    	    $("#customCB").hide()
				    	}
				    	else
						{
							$("#customRef").attr("readonly", false);
				    	    $("#customLabel").attr("readonly", false);
				    	    $("#customCB").show()
						}
				    }
				    else
					{
						$("#customRef").val(\'\');
						$("#customLabel").val(\'\');
						$("#customCB").hide()
					}
				});
				</script>';
	$out.= '&nbsp;'.$langs->trans('Ref');
	$out.= '<input type="text" name="customRef" id="customRef" value="'.$customRef->ref.'" '.(!empty($customRef->id) ? 'readonly' : '' ).'>';
	$out.= '&nbsp;'.$langs->trans('Label');
	$out.= '<input type="text" name="customLabel" id="customLabel" value="'.$customRef->label.'" '.(!empty($customRef->id) ? 'readonly' : '' ).'>';

	if ($exists > 0)
	{
		$out.= '&nbsp;<label for="majCustomRef" id="customCB" '.(!empty($customRef->id) ? 'style="display:none;"' : '' ).'><input type="checkbox" name="majCustomRef" id="majCustomRef"> '.$langs->trans('majCustomRef').'</label>';
	}

	return $out;
}
