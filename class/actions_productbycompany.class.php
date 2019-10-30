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

/**
 * \file    class/actions_productbycompany.class.php
 * \ingroup productbycompany
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsProductByCompany
 */
class ActionsProductByCompany
{
    /**
     * @var DoliDb		Database handler (result of a new DoliDB)
     */
    public $db;

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
     * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		/*$error = 0; // Error counter
		$myvalue = 'test'; // A result value

		print_r($parameters);
		echo "action: " . $action;
		print_r($object);

		if (in_array('somecontext', explode(':', $parameters['context'])))
		{
		  // do something only for the context 'somecontext'
		}

		if (! $error)
		{
			$this->results = array('myreturn' => $myvalue);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}*/
	}

	public function formEditProductOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $form;
		$langs->load('productbycompany@productbycompany');

		$TContext = explode(':', $parameters['context']);

		if (
			in_array('propalcard', $TContext)
			|| in_array('invoicecard', $TContext)
			|| in_array('ordercard', $TContext)
			|| in_array('supplier_proposalcard', $TContext)
			|| in_array('invoicesuppliercard', $TContext)
			|| in_array('ordersuppliercard', $TContext)
		)
		{
			?>
			<a class="button" id="btnCustomRef">+ <?php echo $langs->trans('Customize'); ?></a>
			<br /><br />
			<fieldset id="js_fieldset" style="display: none; ">
				<legend><?php echo $langs->trans('CustomRef'); ?></legend>
				<div id="js_customref"></div>
			</fieldset>
			<script type="text/javascript">
				while ($('#btnCustomRef').prev('br').length > 0) $('#btnCustomRef').prev('br').remove();

                // afficher les champs ref et label + "checkbox mise à jour existant"

                $(document).ready(function(){

					$.ajax({
						url : "<?php echo dol_buildpath('/productbycompany/script/interface.php',1) ?>"
						,data:{
							get: 'getCustomRefEditFields'
							,id: '<?php echo $parameters['line']->id; ?>'
							,element_type: '<?php echo $parameters['line']->element; ?>'
							,fk_product: '<?php echo $parameters['line']->fk_product; ?>'
						}
						,method:"get"
					}).done(function(html){
						$("#js_customref").html(html);
					});

                    $('#btnCustomRef').on('click', function (e) {
                        e.preventDefault();
                        if($('#js_fieldset').is(':visible')) {
                            $('#js_fieldset').hide();
                            $("#customRefSelect").click();
                            $(this).html("+ <?php echo $langs->trans('Customize'); ?>");
                        }
                        else {
                            $('#js_fieldset').show();
                            $("#customRefSelect").click();
                            $(this).html("- <?php echo $langs->trans('Customize'); ?>");
                        }
                    })
                });
			</script>

			<?php

		}

	}

	public function formCreateProductOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $form;
		$langs->load('productbycompany@productbycompany');

		$TContext = explode(':', $parameters['context']);

		if (
			in_array('propalcard', $TContext)
			|| in_array('invoicecard', $TContext)
			|| in_array('ordercard', $TContext)
		)
		{
			?>
			<a class="button" id="btnCustomRef" style="display: none;">+ <?php echo $langs->trans('Customize'); ?></a>
			<fieldset id="js_fieldset" style="display: none;">
				<legend><?php echo $langs->trans('CustomRef'); ?></legend>
				<div id="js_customref"></div>
			</fieldset>
			<script type="text/javascript">

			// afficher les champs ref et label + "checkbox mise à jour existant"

            $(document).ready(function(){

                $('#idprod').on('change', function(e){
                    $('#btnCustomRef').show();
                    $('#js_fieldset').hide();

                    $.ajax({
                        url : "<?php echo dol_buildpath('/productbycompany/script/interface.php',1) ?>"
                        ,data:{
                            get: 'getCustomRefCreateFields'
                            ,id_prod:$(this).val()
							,fk_soc:<?php echo $object->socid; ?>
                        }
                        ,method:"get"
                    }).done(function(html){
                        $("#js_customref").html(html);
                    });
                });

                $('#btnCustomRef').on('click', function (e) {
                    e.preventDefault();
                    if($('#js_fieldset').is(':visible')) {
                        $('#js_fieldset').hide();
                        $("#customRefSelect").click()
                    }
                    else {
                        $('#js_fieldset').show();
                        $("#customRefSelect").click()
					}
				})
            });
			</script>

			<?php
		}
	}

	public function formCreateProductSupplierOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $form;
		$langs->load('productbycompany@productbycompany');

		$TContext = explode(':', $parameters['context']);

		if (
			in_array('supplier_proposalcard', $TContext)
			|| in_array('invoicesuppliercard', $TContext)
			|| in_array('ordersuppliercard', $TContext)
		)
		{
			?>
			<a class="button" id="btnCustomRef" style="display: none;">+ <?php echo $langs->trans('Customize'); ?></a>
			<fieldset id="js_fieldset" style="display: none;">
				<legend>Personnalisation</legend>
				<div id="js_customref"></div>
			</fieldset>
			<script type="text/javascript">

                // afficher les champs ref et label + "checkbox mise à jour existant"

                $(document).ready(function(){
                    console.log('chu la');

                    $('#idprodfournprice').on('change', function(e){

                        $('#btnCustomRef').show();
                        $('#js_fieldset').hide();

                        $.ajax({
                            url : "<?php echo dol_buildpath('/productbycompany/script/interface.php',1) ?>"
                            ,data:{
                                get: 'getCustomRefCreateFields'
                                ,id_prod:$(this).val().substr(7)
                                ,fk_soc:<?php echo $object->socid; ?>
                            }
                            ,method:"get"
                        }).done(function(html){
                            $("#js_customref").html(html);
                        });
                    });

                    $('#btnCustomRef').on('click', function (e) {
                        e.preventDefault();
                        if($('#js_fieldset').is(':visible')) {
                            $('#js_fieldset').hide();
                            $("#customRefSelect").click()
                        }
                        else {
                            $('#js_fieldset').show();
                            $("#customRefSelect").click()
                        }
                    })
                });
			</script>

			<?php
		}
	}

	public function pdf_writelinedesc($parameters, &$object, &$action, $hookmanager)
	{
		global $db;

		$TContext = explode(':', $parameters['context']);

		if (
			in_array('pdfgeneration', $TContext)
			&& (
				$object->element == 'propal'
				|| $object->element == 'commande'
				|| $object->element == 'facture'
				|| $object->element == 'supplier_proposal'
				|| $object->element == 'order_supplier'
				|| $object->element == 'invoice_supplier'
			)
		)
		{
			foreach($parameters as $key => $value) {
				$$key = $value;
			}

			dol_include_once('/productbycompany/class/productbycompany.class.php');
			$pbc_det = new ProductByCompanyDet($db);
			$pbc_det->origin_type = $object->lines[$i]->element;
			$pbc_det->fk_origin = $object->lines[$i]->id;

			$existing = $pbc_det->alreadyExists();
			if ($existing > 0)
			{
				$pbc_det->fetch($existing);
				$object->lines[$i]->label = $pbc_det->label;
				$object->lines[$i]->fk_product = 0;
				$desc = $pbc_det->ref.' - '.pdf_getlinedesc($object, $i, $outputlangs, $hideref, $hidedesc, $issupplierline);
//				var_dump($desc, $pbc_det->ref, pdf_getlinedesc($object, $i, $outputlangs, $hideref, $hidedesc, $issupplierline)); exit;

				$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($desc), 0, 1, false, true, 'J',true);

				$this->resprints = $desc;
				return 1;
			}
		}

	}
}
