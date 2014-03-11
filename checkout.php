<?php 
	include_once('functions.php');
	include_once('fetch.php');

	global $theHostname;
	global $couponUrl;
	global $deliveryCostUrl;
	global $allowPickingUp;
	global $allowWaitress;
	$cart = null;
	$deliveryCosts = null;

	if(!isset($_SESSION['shoppingCart'])){
		$cart = json_decode('[]');
	}
	else {
		$cart =  $_SESSION['shoppingCart'];
	} 
	setlocale(LC_MONETARY, 'it_IT');

	$vatMap = array(); //groups totals by percentage
	$numPackage = 0;
	$numProducts = 0;
	$numMaterials = 0;
	foreach($cart as $i){
	
		$item = (object) $i;

		if($item->type == 'package'){
			$numPackages++;
		}
		if($item->type == 'product'){
			$numProducts++;
		}
		if($item->type == 'material'){
			$numMaterials++;
		}
		
		
		if(!in_array($item->VAT, $vatMap)){
			$vatMap[] = $item->VAT;
		}
	
	}
	if(!in_array(0.21, $vatMap))
		$vatMap[] = 0.21;
		
	$renderPersonalForm = true;
	if($_SESSION['isModifyingOrder'] === true)	{
	 //don't show personalForm
	 $renderPersonalForm = false;
	 $changeCode = $_SESSION['changeCode'];
	}
		
		
	function calculateProductPrice($product){
		//check if there are options checked
		if($product->options == null || count($product->options) == 0)
			return $product->quantity * $product->price;
			
		$pr = $product->price;
		
		foreach($product->options as $o){
			$pr += $o['optionSalesPrice'];
		}	
		
		return $product->quantity * $pr;
	}
	
	function getProductOptionString($product){
		if($product->options == null || count($product->options) == 0)
			return '';
		$ret = '(';
		$c = 0;
		foreach($product->options as $o){
			if($c > 0)
				$ret .= ', ';
			$ret .= $o['ingredientName'];
			$c++;
		}
		$ret .= ')';
		
		return $ret;	
	}
	
	function getSelectedOptionIdAttr($product){
		if($product->options == null || count($product->options) == 0)
			return '';
			
		$ret = 'selected_options="';
		$c = 0;
		foreach($product->options as $o){
			if($c > 0)
				$ret .= ',';
				
			$ret .= $o['ingredients_id'];
			$c++;
		}
		$ret .= '"';
		
		return $ret;
	}

?>
<script type="text/javascript">
	
	var updating = <?php if($renderPersonalForm ){ echo 'false'; } else { echo 'true'; } ?>;
 	var thisPageUrl = '<?php echo $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>';
 	var couponUrl = '<?php echo $couponUrl; ?>';
 	var hostname= '<?php echo $theHostname; ?>';
	var baseUrl = '<?php echo $_SERVER['HTTP_HOST']; ?>';
	var deliveryCostUrl = '<?php echo $deliveryCostUrl; ?>';
	jQuery(document).ready(function(){
		$('.xtooltip').popover();
		
		$('#deliveryElsewhere').change(function(){
			$('.address-line-more').toggleClass('hidden');
		});
		
		
		$('.info-click').click(function(evt){
			evt.preventDefault();
			var id = $(this).attr("id");
			$('.info-click-'+id).toggleClass('hidden');
		});
		
	});
	
</script>
<div class="row-fluid">
 <div class="span12 checkout">
	<table  class="table checkout-table">
		<thead>
			<tr>
				<th class="smallcolumn">Aantal</th>	
				<th>Naam</th>	
				<th class="text-right smallcolumn">Prijs</th>							
				<th class="smallcolumn text-center">Verwijder</th>	
			</tr>
		</thead>
		<tbody>
		<?php if($numPackages > 0){ ?>	
			<tr>
				<td colspan="4" class="product-category">
					Pakketten
				</td>
			</tr>
			<?php foreach($cart as $pkg) { $pkg = (object) $pkg; ?>
				<!-- list packages first -->
				<?php if($pkg->type == 'package' && $pkg->price > 0){ ?>
					<tr class="package-row-<?php echo $pkg->Package_id; ?>">
						<td><strong><?php echo $pkg->quantity; ?> x</strong></td>
						<td>
							<strong>
								<?php echo $pkg->title; ?>
								<a data-content="<p class=' '><?php echo htmlspecialchars($pkg->desc); ?></p><img src='<?php echo $pkg->thumb; ?>' />" 
							 		   rel="popover" 
							 		   data-trigger="hover"
							 		   class="label label-info xtooltip" 
							 		   href="#" 
						 		       id="pkg-row-<?php echo $pkg->Package_id; ?>"
						 		   data-original-title="<?php echo $pkg->title; ?>">info</a>
						 		   
						 		<a href="#"	class="label label-info  details-button info-click" id="pkg-row-<?php echo $pkg->Package_id; ?>">   
						 		details
						 		</a>
							
							</strong>
						</td>
						<td class="text-right">
							<strong>€ <?php echo money_format('%.2n', $pkg->quantity * $pkg->price); ?></strong>
							<!--<strong>€ <?php echo money_format('%.2n', $pkg->quantity * $pkg->price - ($pkg->VAT * $pkg->quantity * $pkg->price)); ?></strong>-->
						</td>
						<td class="text-center">
							<a class="removefromcart-checkout" href="#" 
								packageid="<?php echo $pkg->Package_id; ?>"  
								productdata='<?php echo json_encode($pkg); ?>'>&times;
							</a>
						</td>
					</tr>
					<?php if(count($pkg->products) > 0) : ?>
						<?php foreach($pkg->products as $p){ $p = (object) $p; ?>
							<tr class="package-row-<?php echo $pkg->Package_id; ?> info-click-pkg-row-<?php echo $pkg->Package_id; ?> hidden">
								<td>
									<?php if($p->showAmount) : ?>
									&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $p->quantity; ?> x
									<?php else: ?>
									&nbsp;
									<?php endif; ?>
								</td>
								<td><?php echo $p->title; ?>
								<a data-content="<p class=' '><?php echo htmlspecialchars($p->desc); ?></p><img src='<?php echo $p->thumb; ?>' />" 
								 		   rel="popover" 
								 		   data-trigger="hover"
								 		   class="label label-info xtooltip package-dish-tooltip" 
								 		   href="#" 
							 		   
							 		   data-original-title="<?php echo $p->title; ?>">info</a>
								</td>
								<td>&nbsp;</td>
								<td></td>					
							</tr>
						<?php } ?>
					<?php endif; ?>
					<?php if(count($pkg->materials) > 0) : ?>
						<?php foreach($pkg->materials as $p){ $p = (object) $p; ?>
							<tr class="package-row-<?php echo $pkg->Package_id; ?> info-click-pkg-row-<?php echo $pkg->Package_id; ?> hidden">
								<td>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $p->quantity; ?> x</td>
								<td><?php echo $p->title; ?>
								<a data-content="<p class=' '><?php echo htmlspecialchars($p->desc); ?></p><img src='<?php echo $p->thumb; ?>' />" 
								 		   rel="popover" 
								 		   data-trigger="hover"
								 		   class="label label-info xtooltip package-dish-tooltip" 
								 		   href="#" 
							 		   
							 		   data-original-title="<?php echo $p->title; ?>">info</a>
								</td>
								<td>&nbsp;</td>
								<td></td>					
							</tr>
						<?php } ?>
					<?php endif; ?>
				<?php } ?>
			<?php } ?>
		<?php } ?>
		<?php if($numProducts > 0) { ?>
			<tr>
				<td colspan="4" class="product-category">
					Producten
				</td>
			</tr>
			<?php foreach($cart as $p) { $p = (object) $p; ?>
				<!-- list separate products -->
				<?php if($p->type == 'product'){ ?>
						<tr class="product-row-<?php echo $p->Product_id; ?>">
							<td><?php echo $p->quantity; ?> x</td>
							<td><?php echo $p->title.' '.getProductOptionString($p); ?>
							<a data-content="<p class=' '><?php echo htmlspecialchars($p->desc); ?></p><img src='<?php echo $p->thumb; ?>' />" 
							 		   rel="popover" 
							 		   data-trigger="hover"
							 		   class="label label-info xtooltip" 
							 		   href="#" 
						 		   
						 		   data-original-title="<?php echo $p->title; ?>">info</a>
							
							</td>
							<td class="text-right">€ <?php echo money_format('%.2n', calculateProductPrice($p)); ?>
							
							</td>
							<td class="text-center">
								<a class="removefromcart-checkout" href="#" 
								   productid="<?php echo $p->Product_id; ?>" 
								   <?php echo getSelectedOptionIdAttr($p); ?>
								   productdata='<?php echo json_encode($p); ?>'>&times;</a>
							</td>					
						</tr>		
				
				<?php } ?>
			
			<?php } ?>
		<?php } ?>
		<?php if($numMaterials > 0) { ?>
			<tr>
				<td colspan="4" class="product-category">
					Materialen
				</td>
			</tr>
			<?php foreach($cart as $p) {  $p = (object) $p; ?>
				<!-- list separate products -->
				<?php if($p->type == 'material'){ ?>
						<tr class="material-row-<?php echo $p->Product_id; ?>">
							<td><?php echo $p->quantity; ?> x</td>
							<td><?php echo $p->title; ?>
							<a data-content="<p class=' '><?php echo htmlspecialchars($p->desc); ?></p><img src='<?php echo $p->thumb; ?>' />" 
							 		   rel="popover" 
							 		   data-trigger="hover"
							 		   class="label label-info xtooltip" 
							 		   href="#" 
						 		   
						 		   data-original-title="<?php echo $p->title; ?>">info</a>
							
							</td>
							<td class="text-right">€ <?php echo money_format('%.2n', $p->quantity * $p->price); ?>
							
							</td>
							<td class="text-center">
								<a class="removefromcart-checkout" href="#" 
								   materialid="<?php echo $p->Product_id; ?>" 
								   productdata='<?php echo json_encode($p); ?>'>&times;</a>
							</td>					
						</tr>		
				
				<?php } ?>
			
			<?php } ?>
		<?php } ?>		
		
		
			<tr class="deliverycosts-row thick-border">
				<td><strong>&nbsp;</strong></td>
				<td><strong>Bezorgkosten</strong></td>
				<td class="text-right deliverycosts-field"></td>
				<td>&nbsp;</td>
			</tr>			
			<tr class="subtotal-row thick-border">
				<td><strong>&nbsp;</strong></td>
				<td><strong>Subtotaal (excl. BTW)</strong></td>
				<td class="text-right subtotal-field"></td>
				<td>&nbsp;</td>
			</tr>
		<?php foreach($vatMap as $p): 
			//only generate placeholder HTML ,the JS plugin will populate the divs.
		?>
			<tr class="subtotal-row "> 
				<td><strong>&nbsp;</strong></td>
				<td><strong>BTW (<?php echo ($p*100); ?>%)</strong></td>
				<td class="text-right vat-field-x<?php echo str_replace(".","_",strval($p)); ?>">
					<strong>€ <span class="vat-value-x<?php echo str_replace(".","_",strval($p)); ?>"></span></strong>
				</td>
				<td>&nbsp;</td>
			</tr>
		<?php endforeach; ?>
			<tr class="subtotal-row  hidden " id="discount-row">
				<td><strong>&nbsp;</strong></td>
				<td><strong>Couponkorting</strong></td>
				<td class="text-right discount-field"></td>
				<td>&nbsp;</td>
			</tr>		
			<tr class="subtotal-row  hidden " id="discount-table-row">
				<td><strong>&nbsp;</strong></td>
				<td><strong>Quantumkorting</strong></td>
				<td class="text-right discount-table-field"></td>
				<td>&nbsp;</td>
			</tr>		
			<tr class="subtotal-row thick-border">
				<td><strong>&nbsp;</strong></td>
				<td><strong>Totaal (incl. BTW)</strong></td>
				<td class="text-right total-field"></td>
				<td>&nbsp;</td>
			</tr>	
		</tbody>
	</table>
 </div>
</div>
<div class="row-fluid">
	<div class="span12 deposit-container alert alert-warning hidden">
		<strong>NB:</strong> Voor deze bestelling wordt <strong>€ <span class="deposit-total">34,50</span></strong> borg gerekend voor borden etc. Dit dient contant te worden betaald, en krijgt u ook weer contant terug.
	</div>
</div>


 <form name="order-form_" id="order-form" class="form-horizontal" action="<?php echo SUBMIT_ORDER_URL; ?>" method="post">
<?php if($renderPersonalForm) : ?>

<div class="row-fluid">

  <fieldset> 
  
	 <div class="span6">
	 	<?php if($allowPickingUp == 'true'):  ?>
	 	<h3>Leveren / afhalen</h3>
	 	<div class="control-group">
			<div class="controls">
				<input type="radio" name="deliveryType" value="afhalen"  class="deliveryType input-large" id="afhalen" /> Afhalen &nbsp;&nbsp;
				<input type="radio" name="deliveryType" value="bezorgen" class="deliveryType input-large" id="bezorgen" checked /> Bezorgen
			</div>
		</div>
		<?php else: ?>
				<input type="hidden" name="deliveryType"  class="deliveryType input-large" value="bezorgen" />
		<?php endif;?>
	 
 		<h3>Persoonlijke gegevens</h3>
 		<input type="hidden" name="hostname" value="<?php echo $theHostname; ?>" />
 		
 		<div class="control-group">
			<label class="control-label" for="companyName">Bedrijfsnaam:</label>			

			<div class="controls">	
				<input type="text" name="companyName" class="input-large" id="companyName" />
			</div>		
		</div>	
 		
 	    <div class="control-group">
			<label class="control-label" for="firstname">Voornaam: *</label>
			
			<div class="controls">
				<input type="text" name="firstname" class="input-large" id="firstname" />
			</div>
		</div>	

 	    <div class="control-group">
			<label class="control-label" for="surname">Achternaam: *</label>
			
			<div class="controls">		
				<input type="text" name="surname" class="input-large" id="surname" />
			</div>
		</div>

 	    <div class="control-group">
			<label class="control-label" for="street">Straat: *</label>			

			<div class="controls">	
				<input type="text" name="street" class="input-large address-line" id="street" />
			</div>		
		</div>
	
	    <div class="control-group">
			<label class="control-label" for="number">Huisnummer: *</label>			

			<div class="controls">	
				<input type="text" name="number" maxlength="7" class="input-large span3 address-line" id="number" />
			</div>		
		</div>	
	
	    <div class="control-group">
			<label class="control-label" for="postcode">Postcode: *</label>			

			<div class="controls">	
				<input type="text" name="postcode" maxlength="7" class="input-large span3 address-line" id="postcode" />
			</div>		
		</div>	
	
	    <div class="control-group">
			<label class="control-label" for="city">Plaats: *</label>			

			<div class="controls">	
				<input type="text" name="city" class="input-large address-line" id="city" />
			</div>		
		</div>	
	
	    <div class="control-group">
			<label class="control-label" for="country">Land: *</label>			

			<div class="controls">	
				<input type="text" name="country" class="input-large address-line" id="country" />
			</div>		
		</div>		
	
	    <div class="control-group">
			<label class="control-label" for="email">E-mail: *</label>			

			<div class="controls">	
				<input type="text" name="email" class="input-large" id="email" />
			</div>		
		</div>	
	
	    <div class="control-group">
			<label class="control-label" for="phone">Telefoon: * 
					<a data-content="Vul een telefoonnummer in waarop u op de dag van levering bereikbaar bent." 
							 		   rel="popover" 
							 		   data-trigger="hover"
							 		   class="label label-info xtooltip" 
							 		   href="#" 
						 		   
						 		   data-original-title="Telefoon">&nbsp;?&nbsp;</a>
			
			</label>			

			<div class="controls">	
				<input type="text" name="phone" class="input-large" id="phone" />
			</div>		
		</div>	
		
 		<div class="control-group">
			<label class="control-label" for="VATnumber">BTW-nummer: 
				<a data-content="Vul dit alleen in als u bestelt op naam van een bedrijf." 
							 		   rel="popover" 
							 		   data-trigger="hover"
							 		   class="label label-info xtooltip" 
							 		   href="#" 
						 		   
						 		   data-original-title="BTW-nummer">&nbsp;?&nbsp;</a>
				
				
			</label>			

			<div class="controls">	
				<input type="text" name="VATnumber" class="input-large" id="VATnumber" />
			</div>		
		</div>	
		


		
		<!-- delivery costs, only show if checkbox is set -->
		<div class="control-group">
			<label class="control-label" for="deliveryElsewhere">Ergens anders bezorgen?</label>			

			<div class="controls">	
				<input type="checkbox" name="deliveryElsewhere" class="input-large address-line-elsewhere" id="deliveryElsewhere" />
			</div>		
		</div>	
				
		 <div class="control-group address-line-more hidden">
			<label class="control-label" for="deliveryStreet">Straat: </label>			

			<div class="controls">	
				<input type="text" name="deliveryStreet" class="input-large address-line-elsewhere" id="deliveryStreet" />
			</div>		
		</div>		
	
	    <div class="control-group address-line-more hidden">
			<label class="control-label" for="deliveryNumber">Huisnummer: </label>			

			<div class="controls">	
				<input type="text" name="deliveryNumber" class="input-large address-line-elsewhere" id="deliveryNumber" />
			</div>		
		</div>	
	
	    <div class="control-group address-line-more hidden">
			<label class="control-label" for="deliveryZipcode">Postcode: </label>			

			<div class="controls">	
				<input type="text" name="deliveryZipcode" class="input-large address-line-elsewhere" id="deliveryZipcode" />
			</div>		
		</div>	

	    <div class="control-group address-line-more hidden">
			<label class="control-label" for="deliveryCity">Plaats:</label>			

			<div class="controls">	
				<input type="text" name="deliveryCity" class="input-large address-line-elsewhere" id="deliveryCity" />
			</div>		
		</div>	
		
		 <div class="control-group address-line-more hidden">
			<label class="control-label" for="deliveryName">Feestzaal: <a data-content="Optioneel veld, naam van de zaal waar bezorgd moet worden. Dit is handig voor onze chauffeur, bij het navigeren." 
							 		   rel="popover"
							 		   data-trigger="hover"
							 		   class="label label-info xtooltip" 
							 		   href="#" 
						 		   
						 		   data-original-title="Accomodatie">&nbsp;?&nbsp;</a></label>			

			<div class="controls">	
				<input type="text" name="deliveryName" class="input-large address-line-elsewhere" id="deliveryName" />
			</div>		
		</div>		
		
		
 	</div>
	<div class="span6">
		<div class="row-fluid">
			<div class="span12">
				<h3>Bestellingsgegevens</h3>
				<div class="order-expl"></div>
			    <div class="control-group">
			   		<div id="dateError" class="hidden alert alert-error">De datum moet minimaal overmorgen zijn. Eerder bestellen? Neem dan contact op.</div>
			   		<label class="control-label" for="orderDate">Datum (formaat: dd-mm-jjjj): *</label>			
					<div class="controls">	
						<input type="text" name="orderDate" class="span4 input-large" id="orderDate" />
					</div>		
				</div>	
			    <div class="control-group">
					<label class="control-label control-label-time" for="orderDateTime">Tijd: *</label>			
		
					<div class="controls">	
						<select name="orderDateTime" class="input-large span6" id="orderDateTime" >
							<option value="12:00-15:00">12:00-15:00</option>
							<option value="15:00-17:00">15:00-17:00</option>					
						</select>
					</div>		
				</div>
				<div class="control-group">
					<label class="control-label" for="orderComment">Opmerkingen:</label>			
					<div class="controls">	
						<textarea id="orderComment" name="orderComment" class="input-large" rows="4"></textarea>
					</div>		
				</div>				
				<?php if($allowWaitress) : ?>
				<div class="control-group">
					<label class="control-label" for="allin">Optie all-in service:</label>			
					<p>Indien u dit vakje aanvinkt bezorgen wij u een offerte voor een all-in service. (meerprijs 20% tot 35% van de afleverprijs) Wij arriveren in dit geval met onze mobiele keuken incl. serveermateriaal, professionele oven, koeling en voldoende personeel op uw feest. We serveren de hapjes op een vooraf bepaalde tijdsperiode. Wij arriveren natuurlijk vroeger.</p>
					<div class="controls">	
						<input type="checkbox" name="allin" class="input-large allin" id="allin" />
					</div>		
					
					
					
				</div>
				<div class="control-group">
				 	<label class="control-label" for="deliveryElsewhere">Optie all-in service:</label>	
				 </div>
				
				<?php endif; ?>				
			</div><!-- span12 -->	
		</div><!-- /row -->		
		<div class="row-fluid">
			<div class="span12">
				<h3>Kortingscode</h3>
				<div class="control-group">
					<p>Heeft u een kortingscode? Vul deze dan hieronder in. Als de code geldig is, wordt de korting toegevoegd aan het prijs-overzicht.</p>						
				    <div id="discount-text" class="alert hidden"></div>

					<label class="control-label" for="coupon">Kortingscode:</label>					
					<div class="controls">	
						<input type="text" name="coupon" class="input-large" id="coupon" />
						
					</div>		
				</div>	
			</div>
		</div>		
		<div class="row-fluid">
			<div class="span12">
				<h3 class="payment-options">Betalingsopties</h3>
				<div class="control-group">
					<p><span class="invoice-expl">Hieronder kunt u de bestelling afronden.  Kies voor 'Direct betalen' om direct een factuur te ontvangen. Om een offerte aan te vragen, kies 'Offerte aanvragen'.</span></p>
					<p id="not-enough-ordered" class="hidden alert alert-error"></p>
					<p id="invalid-selection" class="hidden alert alert-error"></p>					
					<div class="controls">	
					<input type="hidden" name="distance" id="calculateddistance" value="" />					
					<input type="submit" name="invoice" class="submit-controls btn btn-primary " id="invoice" value="Plaats bestelling" style="width: 130px;" />
					<input type="submit" name="estimate" class="submit-controls btn " id="estimate" value="Offerte aanvragen" style="width: 130px;" />						
					</div>		
				</div>	
			</div>
		</div>
 	</div>
  </fieldset>
</div>
<?php else: //modifying order, make a hidden field with the changeCode, which is used in the submission of the order. ?>
<div class="row-fluid">
	<fieldset>				
	<input type="hidden" name="orderChangeCode" value="<?php echo $changeCode; ?>">
		<div class="span12">
				<h3 class="payment-options">Aangepaste bestelling versturen</h3>
				<div class="control-group">
					<p>Hieronder kunt u uw aangepaste bestelling weer versturen. U ontvangt wederom een bevestiging per e-mail, en een aangepaste factuur of offerte.</p>
					<p id="not-enough-ordered" class="hidden alert alert-error"></p>
					<p id="invalid-selection" class="hidden alert alert-error"></p>					
					<div class="controls">	
					<input type="submit" name="invoice" class="submit-controls btn btn-primary " id="invoice" value="Plaats bestelling" style="width: 130px;" />
					<input type="submit" name="estimate" class="submit-controls btn " id="estimate" value="Offerte aanvragen" style="width: 130px;" />						
					</div>		
				</div>
		
		</div>	
	</fieldset>		
</div>	
<?php endif; ?>
 </form>
