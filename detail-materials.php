<?php
//include_once('config.php');
	global $theHostname;
	global $theType;
	global $theNumCols;
	global $presetMaterialData;
	
	$params = array(
		'hostname'=>$theHostname,
		'Material_id'=>$_GET['id'],
	);
	$dealsClass ="";
	
	if($presetMaterialData==null){
		$params = array(
			'hostname'=>$theHostname,
			'dishPublished'=>'true',
			'orderBy'=>'materialName',
			'ordering'=>'ASC',
			'useNesting'=>'false'
		);
		$dealsClass ="";
		$products = fetchMaterials($params);
	}
	else {
		$products = $presetMaterialData;
		if($products == null){
			$products = Array();
		}
	}

	
	$w=null;
	if(count($products)>0){
		$w = $products[0];
	}
	
	$jsonShoppingCart = encodeProductToJson($w, 'material');
 	if($w->materialImage == null)
 		$w->materialImage = PLUGIN_SERVER_URL.'/img/materials_no_image.jpg';
 	else 
 		$w->materialImage = SYSTEM_URL_CATERINGSOFTWARE.'/'.$w->materialImage;
?>
<script type="text/javascript" >
	allProducts.materials = [<?php echo $jsonShoppingCart; ?>];
</script>

<div class="product single-product">
	<div class="row-fluid">
		<div class="product span12 featured">
			<div class="row-fluid">
				<div class="span12"><p><a  class="backtooverview"  href="javascript:history.back()">&larr; terug naar overzicht</a></p></div>
			</div>
			<div class="row-fluid">		
			   <div class="productimage span8"> 
				  <img alt="<?php echo $w->materialName; ?>" src="<?php echo $w->materialImage; ?>">
				  
				  
				<?php if($w->materialDeal) { ?>
					<div class="deal-image">
						<img src="<?php echo bloginfo('template_url').'/images/deal.png'; ?>" />
					</div>
				<?php } ?>	
			   </div><!-- productimage -->
			
	  		   <div class="product-data span4 ">
				    <span class="price">€ <?php echo money_format('%.2n', $w->materialPrice); ?> </span>
				    <h2><?php echo $w->materialName; ?></h2>
					<p><?php echo nl2br($w->materialDesc); ?></p>
			   </div><!-- product-data -->
		    </div><!-- row-fluid2 -->
		</div><!-- product -->
	</div>		
	
	<form class="form-horizontal">
		<div class="row-fluid data-wrap">
			<div class="span6 data-left">
				<div class="row-fluid datarow">
					<div class="span12 ">
						<h3>Aantal:
						    <span class="small">
						    	<input class="input-small" name="product-amount" id="product-amount" value="1" type="text" /> 
						    </span>
					    </h3>
				    </div>
				</div>
			</div><!--left column span6 -->
			<div class="span6 data-right">
				<div class="row-fluid datarow">
					<div class="span12 product-data ">
					    <h3>Toevoegen</h3>
						<p>Klik hier om dit product toe te voegen aan het winkelwagentje</p>
						<p  class="product-added hidden alert alert-info">
							<strong>Toegevoegd</strong> aan winkelwagentje. U kunt verder winkelen, of <a href="/checkout">afrekenen</a>.
						</p> 
					    <span product-index='0' product-type="material" class="addtocart">
						  <a href="#" class="btn" >Voeg toe</a>
						</span>
					</div>		
				</div><!-- row-fluid -->						
			</div><!-- right column span6 -->
		</div>
	</form>
</div>