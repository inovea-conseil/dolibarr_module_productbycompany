<?php
require ('../config.php');
dol_include_once('/productbycompany/class/productbycompany.class.php');
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";

$get = GETPOST('get');

switch ($get)
{
	case 'getCustomRefCreateFields':
		print getCustomRefCreateFields(GETPOST('id_prod'), GETPOST('fk_soc'), (bool) GETPOST('isPrice'));
		break;
	case 'getCustomRefEditFields':
		print getCustomRefEditFields(GETPOST('id'), GETPOST('element_type'), GETPOST('fk_product'));
		break;
	case 'getProductFromCustomerCustomRef':
		print getProductFromCustomerCustomRef(GETPOST('ref_prod'), GETPOST('fk_soc'));
		break;
}

/**
 * @param int $id_prod 		ID de produit ou de prix fournisseur
 * @param int $fk_soc  		ID du tiers associé
 * @param bool $isPrice		booléen id_prod est-il un id de prix fournisseur ou pas
 * @return string
 */
function getCustomRefCreateFields($id_prod, $fk_soc, $isPrice = false)
{
	global $db, $langs, $conf, $user;

	if (empty($id_prod)) return '';
	$langs->load('productbycompany@productbycompany');

    $rightToCustomize = $user->rights->productbycompany->customize;
    $labelsExclude = explode(',', getDolGlobalString("PBC_EXCLUDE_LABEL_PRODUCT"));
    $autoriseCustomize = 1;

	if ($isPrice)
	{
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
		$pfp = new ProductFournisseur($db);
		$pfp->fetch_product_fournisseur_price($id_prod);
		if (!empty($pfp->id)) $id_prod = $pfp->id;

	}

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

    if (!empty($labelsExclude) && !$rightToCustomize) {
        $productLabels = explode(' ', strtoupper(trim($customRef->product->label)));

        foreach ($labelsExclude as $label) {
            $labelExclude = strtoupper(trim($label));
            foreach ($productLabels as $productLabel) {
                if (strpos($productLabel, $labelExclude) === 0) {
                    $autoriseCustomize = 0;
                }
            }
        }
    }

    if ($autoriseCustomize || $rightToCustomize) {
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
    } else {
        $out = -1;
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

/**
 * return the json-encoded array representing products matching the specified terms
 * @param string $ref_prod
 * @param int $fk_soc
 * @return String JSon
 */
function getProductFromCustomerCustomRef($ref_prod, $fk_soc){
	global $db;

	$jsonResponse = new stdClass();
	$jsonResponse->result = 1;
	$jsonResponse->newToken = newToken();
	$jsonResponse->msg = '';
	$jsonResponse->data = array();


	$pbc = new ProductByCompany($db);

	$sql = 'SELECT cr.fk_product, cr.ref, cr.label, p.ref origin_ref ';
	$sql.= ' FROM '.MAIN_DB_PREFIX.$pbc->table_element . ' cr ';
	$sql.= ' JOIN '.MAIN_DB_PREFIX.'product p ON (p.rowid = cr.fk_product )';
	$sql.= ' WHERE fk_soc = '.intval($fk_soc).' ';
	$sql.= natural_search('cr.ref', $ref_prod);
	$sql.= ' OR ' . natural_search('cr.label', $ref_prod, 0, 1);
	$sql.= ' LIMIT 50 ';

	$TObj = $db->getRows($sql);
	if ($TObj === false)
	{
		$jsonResponse->result = 0;
		$jsonResponse->msg = $db->lasterror;
	}else{
		$jsonResponse->data = $TObj;
	}

	return json_encode($jsonResponse, JSON_PRETTY_PRINT);
}
