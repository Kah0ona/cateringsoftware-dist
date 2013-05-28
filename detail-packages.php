<?php
//include_once('config.php');
	global $theHostname;
	global $theType;
	global $theNumCols;
	global $showProductImageOnDetail;
	global $presetPackageData;
	global $columns5050;
	$dealsClass ="";
	if($presetPackageData==null){
		$params = array(
			'hostname'=>$theHostname,
			'pkgPublished'=>'true',
			'orderBy'=>'pkgName',
			'ordering'=>'ASC',
			'Package_id'=>$_GET['Package_id'],
			'useNesting'=>'true'
		);
		$dealsClass ="";
		$packages = fetchPackages($params);
	}
	else {
		$packages = $presetPackageData;
		if($packages == null){
			$packages = Array();
		}
	}
	
	$w=null;
	if(count($packages)>0){
		$w = $packages[0];
	}
	$hasFixedItems = false;
	$hasFlexItems = false;
	$hasExtras = false;
	$hasFixedMaterial=false;
	$hasExtraMaterial=false;
	foreach($w->Dish as $d){
		if($d->containmentType == 'vast aantal'){
			$hasFixedItems=true;
		}
		if($d->containmentType == 'flexibel aantal'){
			$hasFlexItems=true;
		}
		if($d->containmentType == 'aanvulling'){
			$hasExtras=true;
		}
	}
	
	foreach($w->Material as $d){
		if($d->containmentTypeMaterial == 'vast aantal'){
			$hasFixedMaterial=true;			
		}
		if($d->containmentTypeMaterial == 'aanvulling'){
			$hasExtraMaterial=true;
		}
	}
	$jsonShoppingCart = encodeToJson($w);
 
?>
<script type="text/javascript" >
	allProducts.packages = [<?php echo $jsonShoppingCart; ?>];
	
	allProducts.products = [
	<?php 
	$c = 0;
	foreach($w->Dish as $d){
		//all extar products, add to allProducts.products	
		if($d->containmentType == 'aanvulling') {
			$jsonShoppingCart = encodeProductToJson($d, 'product');
			echo $jsonShoppingCart;
			echo ',';
		}
	}
	?>
	];
	
	allProducts.materials = [
	<?php 
	$c = 0;
	foreach($w->Material as $d){
		//all extar products, add to allProducts.products	
		if($d->containmentTypeMaterial == 'aanvulling') {
			$jsonShoppingCart = encodeProductToJson($d, 'material');
			echo $jsonShoppingCart;
			echo ',';
		}
	}
	?>
	];
	
</script>
<script type="text/javascript"> 
	jQuery(document).ready(function(){
		$('.xtooltip').popover();
		
		if(allProducts.packages[0].price == 0){
			$('.amount-form-row').css('visibility','hidden');
		}
	});
	
</script>

<div class="product single-product">
	<p><a class="backtooverview" href="javascript:history.back()">&larr; terug naar overzicht</a></p>
	<div class="row-fluid">
		<div class="product span12 featured">			
			<div class="row-fluid">		
			   <?php if($w->imagePkg != null) { $imgSpan = '8'; $imgSpan2='4'; 
				   		if($columns5050) { $imgSpan = '6'; $imgSpan2 = '6'; }
			   ?>
				   <div class="productimage span<?php echo $imgSpan; ?>"> 
					  <img alt="<?php echo $w->pkgName; ?>" src="<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'.$w->imagePkg; ?>">
					  
					  	<?php if($w->packageDeal) { ?>
						<div class="deal-image">
							<img src="<?php echo bloginfo('template_url').'/images/deal.png'; ?>" />
						</div>
						<?php } ?>	
					  
				   </div><!-- productimage -->
			   <?php } else { $imgSpan2 = '12'; }?>
	  		   <div class="product-data span<?php echo $imgSpan2; ?>">
					<?php if($w->packagePrice == 0 || $w->packagePrice == "0" || $w->packagePrice == null) :?>
				    <span class="price">Prijs: zelf samenstellen</span>
				    <?php else: ?>
				    <span class="price">€ <?php echo money_format('%.2n', $w->packagePrice); ?> </span>				    
				    <?php endif; ?>
				    <h3><?php echo $w->pkgName; ?></h3>
					<p><?php echo nl2br($w->pkgDesc); ?></p>
			   </div><!-- product-data -->
		    </div><!-- row-fluid2 -->
		</div><!-- product -->
	</div>		
	
	<form class="form-horizontal">
		<div class="row-fluid data-wrap">
			<div class="span6 data-left">
				<div class="row-fluid datarow amount-form-row">
					<div class="span12 ">
						<h3><span id="num">Aantal:</span>
						    <span class="small">
						    	<input class="input-small" name="product-amount" id="product-amount" value="<?php echo ($w->pkgNumPersons == null ? "1" : $w->pkgNumPersons) ?>" type="text" /> 
						    </span>
					    </h3>
				    </div>
				</div>
				<?php if($hasFixedItems) : ?>
				<div class="row-fluid datarow">
					<div class="span12 standard-products product-data">
					 <h3>
					  Standaard in dit menu
					 </h3>
					 <ul class="product-list">
						<?php foreach($w->Dish as $d){ ?>
							<?php if($d->containmentType == 'vast aantal'){ ?>
						 	<li>
						 		<?php if($d->showNumberOnSite) : ?>
						 			<?php echo $d->amount; ?>x 
					 				<?php endif; ?>
						 			<?php echo $d->dishName; ?>
						 	
						 		<a  data-content="<p class=' '><?php echo $d->dishDesc; ?></p> <?php if($d->imageDish != null) { ?><img src='<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'.$d->imageDish; ?>' /><?php } ?>" 
						 		   rel="popover" 
							 	   data-placement="left"
						 		   
						 		   data-trigger="hover"
						 		   class="label label-info xtooltip package-dish-tooltip" 
						 		   href="#" 
						 		   
						 		   data-original-title="<?php echo $d->dishName; ?>">info</a>
						 	</li>
						 	<?php } ?>
					 	<?php } ?>	 
					 </ul>
					</div>				
				</div><!--row-fluid-->
				<?php endif; ?>
				<?php if($hasFlexItems) : ?>
				<div class="row-fluid datarow">
 					 <div class="span12 basic-products product-data ">
					 <h3>
					 	Kies uit basisitems in dit menu
					 </h3>
					 <?php if($w->pkgDesc2 != null){ ?>
					 	<p><?php echo $w->pkgDesc2; ?></p>
					 <?php } ?>
					 	<?php foreach($w->Dish as $d){ ?>
							<?php if($d->containmentType == 'flexibel aantal'){ ?>
						 		<label for="flex-product-<?php echo $d->Dish_id; ?>">
						 		   <?php echo getAmountForm($d->amount, 'amount-flex flex-product-'.$d->Dish_id, 'flex-product-'.$d->Dish_id); ?>&nbsp;
							 	   <?php echo $d->dishName; ?>
							 	   <a data-content="<p class=' '><?php echo $d->dishDesc; ?></p> <?php if($d->imageDish != null) { ?><img src='<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'.$d->imageDish; ?>' /><?php } ?>"
							 		   rel="popover" 
								 	   data-placement="left"
							 		   
							 		   data-trigger="hover"
							 		   class="label label-info xtooltip package-dish-tooltip" 
							 		   href="#" 
						 		   
						 		   data-original-title="<?php echo $d->dishName; ?>">info</a>
						 		</label>
						 	<?php } ?>
					 	<?php } ?>			 
					 <p class="total-sentence">Het totale aantal moet <strong id="total-calculation">0</strong> zijn. U kunt nog <strong id="left-calculation">3</strong> items kiezen.
					 </p>
					 <p class="too-many alert alert-error hidden">U heeft <strong id="left-calculation-2">3</strong> items teveel gekozen.</p>
				    </div>
				    
				</div>	
				<?php endif; ?>	
				<?php if($hasExtras) : ?>

				<div class="row-fluid datarow">
					<div class="span12 extra-products product-data  ">
					 <?php if($w->packagePrice == 0 || $w->packagePrice == "0" || $w->packagePrice == null) { ?>
					 <h3>Stel uw menu samen:</h3>					 
					 <?php } else { ?>
					 <h3>Aanvullingen op dit menu:</h3>
					 <?php } ?>
						 <?php $counter = 0;
						 	 foreach($w->Dish as $d){ ?>
							<?php if($d->containmentType == 'aanvulling'){ ?>
						 		<label for="extra-product-<?php echo $d->Dish_id; ?>" product-index="<?php echo $counter; ?>" product-type="product" sub-product-index="<?php echo $counter;?>">
						 		   <?php echo getAmountForm($d->amount, 'amount-extra extra-product-'.$d->Dish_id, 'extra-product-'.$d->Dish_id); ?>&nbsp;
							 	   <?php echo $d->dishName; ?> <small>(€<?php echo money_format('%.2n', $d->dishPrice); ?> p.s.)</small>
							 	   <a data-content="<p class=' '><?php echo $d->dishDesc; ?></p> <?php if($d->imageDish != null) { ?><img src='<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'.$d->imageDish; ?>' /><?php } ?>"
						 		   rel="popover" 
						 		   data-trigger="hover"
						 		   data-placement="left"

						 		   class="label label-info xtooltip package-dish-tooltip" 
						 		   href="#" 
						 		   data-original-title="<?php echo $d->dishName; ?>">info</a>
						 		</label>
						 	<?php $counter++;
						 		} ?>
					 	<?php } ?>			 
					</div>				
				</div>
				<?php endif; ?>
			</div><!--left column span6 -->
			<div class="span6 data-right">
				<?php if($hasFixedMaterial) : ?>

				<div class="row-fluid datarow">
					<div class="span12 standard-materials product-data">
					  <h3>
						Standaard inbegrepen materialen
					  </h3>
					  <ul class="product-list">
						<?php foreach($w->Material as $d){ ?>
							<?php if($d->containmentTypeMaterial == 'vast aantal'){ ?>
						 		<li>
						 		<?php if($d->showNumberOnSiteMaterial) : ?>
						 		<?php echo $d->amountMaterial; ?>x 
						 		<?php endif; ?>
						 		
						 		<?php echo $d->materialName; ?>
						 		
						 		<a data-content="<p class=' '><?php echo $d->materialDesc; ?></p> <?php if($d->materialImage != null) { ?><img src='<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'.$d->materialImage; ?>' /><?php } ?>" 
							 		   rel="popover" 
   							 		   data-placement="left"
							 		   data-trigger="hover"
							 		   class="label label-info xtooltip package-dish-tooltip" 
							 		   href="#" 
						 		   
						 		   data-original-title="<?php echo $d->materialName; ?>">info</a>
						 		
						 		</li>
						 	<?php } ?>
					 	<?php } ?>	 
					 </ul>
					</div>	
				</div><!-- row-fluid -->	
				<?php endif; ?>
				<?php if($hasExtraMaterial) : ?>
				<div class="row-fluid datarow">
					<div class="span12 extra-materials product-data">
					  <h3>Extra materiaal:</h3>
					  <?php $counter =0;
					  		foreach($w->Material as $d){ ?>
							<?php if($d->containmentTypeMaterial == 'aanvulling') { ?>
						 		<label for="extra-material-<?php echo $d->Material_id; ?>" product-index="<?php echo $counter; ?>" product-type="material" sub-product-index="<?php echo $counter;?>">
						 		   <?php echo getAmountForm($d->amountMaterial, 'amount-extra extra-material-'.$d->Material_id, 'extra-product-'.$d->Material_id); ?>&nbsp;
							 	   <?php echo $d->materialName; ?> (€<?php echo money_format('%.2n', $d->materialPrice); ?> per stuk)
							 	   <a data-content="<p class=' '><?php echo $d->materialDesc; ?></p> <?php if($d->materialImage != null) { ?><img src='<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'.$d->materialImage; ?>' /><?php } ?>" 
							 		   rel="popover"
							 		   data-placement="left"							 		    
							 		   data-trigger="hover"
							 		   class="label label-info xtooltip package-dish-tooltip" 
							 		   href="#" 
						 		   
						 		   data-original-title="<?php echo $d->materialName; ?>">info</a>

						 		</label>

						 	<?php $counter++; } ?>
					 	<?php } ?>	 
					</div>
				</div><!-- row-fluid -->	
				<?php endif; ?>
				<div class="row-fluid datarow">
					<div class="span12 product-data ">
					    <h3>Selectie gemaakt?</h3>
						<p class="add-to-cart-text-pkg">Als u tevreden bent met uw keuze-instellingen, klik op onderstaande knop om dit pakket aan het winkelmandje toe te voegen.</p>
						<p id="validation-error" class="hidden alert alert-error"><strong>Let op!</strong> Een aantal dingen in het formulier is niet goed ingevuld, herstel deze eerst.</p>
						<p  class="product-added hidden alert alert-info">
							<strong>Toegevoegd</strong> aan winkelwagentje. U kunt verder winkelen, of <a href="/checkout">uw bestelling plaatsen</a>.
						</p> 
					    <span product-type="package" product-index='0' class="addtocart">
						  <a href="#" class="btn" >Voeg toe</a>
						</span>
					</div>		
				</div><!-- row-fluid -->						
			</div><!-- right column span6 -->
		
		
		</div>
	


		
		
		
	</form>

</div>