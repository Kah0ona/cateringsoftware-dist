<?php
	//include_once('config.php');
	global $theHostname;
	global $theType;
	global $theNumCols;
	global $useViewDetail;
	global $useQuickAddButton;	
	global $presetMaterialData;

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
	//print_r($packages);
	 
	
	
// Let's justify to the left, with 14 positions of width, 8 digits of
// left precision, 2 of right precision, withouth grouping character
// and using the international format for the nl_NL locale.
setlocale(LC_MONETARY, 'it_IT');
?>

<script type="text/javascript">
	allProducts.materials = [
<?php
	$c = 0;

	foreach($products as $w){
		$jsonShoppingCart = encodeProductToJson($w, 'material');
		echo $jsonShoppingCart;
		if($c < count($products)-1 ){
			echo ',';
		}
		$c++;
	}

?>
	];
</script>

<?php 
	$numFeaturedProducts = 0;

	
	$counter = 1; $c=0;
	$numItems = count($products);
	foreach($products as $w){ 


		$endrow = ($counter%$theNumCols == 0 ) ? 'endrow' : '';
		$prediv = ($counter%$theNumCols == 1 ) ? '<div class="row-fluid">' : '';
		$postdiv= ($counter%$theNumCols == 0 || $counter == $numItems) ? "</div><!-- row-fluid1 -->" : ''; 
		
		// Foundation grid framework names
		switch($theNumCols){
			case 1:
				$numColName='span12';
			break;
			case 2:
				$numColName='span6';
			break;
			case 3:
				$numColName='span4';
			break;
			case 4:
				$numColName='span3';
			break;
			case 6:
				$numColName='span2';
			break;
			case 12: 
				$numColName='span1';	
			break;
			default:
				$numColName='span4';
			break;
		}
		
		$spanImg = '';
		$spanDate = '';

		
		$jsonShoppingCart = encodeProductToJson($w, 'material');
		$options = get_option('cateringsoftware_options');
				
	 	if($w->materialImage == null){
	 		if($options['NoImage'] != null){
		 		$w->materialImage = $options['NoImage'];
	 		}
	 		else {
	 			$w->materialImage = PLUGIN_SERVER_URL.'/img/materials_no_image.jpg';
	 		}
	 	}
	 	else 
	 		$w->materialImage = SYSTEM_URL_CATERINGSOFTWARE.'/uploads/Material/'.$w->materialImage;
		
		

?>
<!-- Template starting -->
<?php echo $prediv; ?>
<div class="product <?php echo $numColName; ?> <?php echo $endrow; ?>">
		<a href="/materials/<?php echo $w->Material_id; ?>/">
			<img src="<?php echo $w->materialImage; ?>"  alt="<?php echo $w->materialName; ?>"  />
			<?php if($w->materialDeal) { ?>
					<div class="deal-image">
						<img src="<?php echo bloginfo('template_url').'/images/deal.png'; ?>" />
					</div>
			<?php } ?>	
			
		</a>
		<div class="product-data <?php echo $spanDate; ?> ">
			
			<h3><?php echo $w->materialName; ?></h3>
			
			<div class="buy">
				<span class="price">&euro;<?php echo money_format('%.2n', $w->materialPrice); ?> </span>
				<?php if($useQuickAddButton == "true") : ?>
				<span class="addtocart" product-type="material" product-index='<?php echo $c; ?>'><a href="#">Voeg toe</a></span>
				<?php endif; ?>
				<?php if($useViewDetail == "true") : ?>
				<span class="viewdetail" ><a href="/materials/<?php echo $w->Material_id; ?>#<?php echo urlencode($w->materialName); ?>">Details</a></span>
				<?php endif; ?>			
			</div><!-- buy -->
			
		</div><!-- product-data -->
</div><!-- product -->
<?php echo $postdiv; ?>
<!-- /Template -->

<?php 
$counter++; $c++;
	} 
?>
