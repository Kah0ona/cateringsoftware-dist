<?php
	//include_once('config.php');
	global $theHostname;
	global $theType;
	global $theNumCols;
	global $useViewDetail;
	global $useQuickAddButton;
	global $presetProductData;
	global $showPicOnOverview;
	if($presetProductData == null){
		$params = array(
			'hostname'=>$theHostname,
			'dishPublished'=>'true',
			'orderBy'=>'dishFeatured',
			'ordering'=>'DESC',
			'useNesting'=>'false'
		);
		
		$dealsClass ="";
		$products = fetchDishes($params);
	}
	else {
		$products = $presetProductData;
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


	allProducts.products = [
<?php
	$c = 0;

	foreach($products as $w){
		$jsonShoppingCart = encodeProductToJson($w, 'product');
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
	foreach($products as $w){
		if(!$w->dishFeatured){
			$numFeaturedProducts++;
		}
	}

	$c = 0;
	$counter = 1;
	foreach($products as $w){ 
		$featured = '';
		if($w->dishFeatured){
			$featured='featured';
		}

		$endrow = ($counter%$theNumCols == 0) ? 'endrow' : '';
		$prediv = ($counter%$theNumCols == 1 || $theNumCols == 1 || $w->dishFeatured) ? '<div class="row-fluid">' : '';
		$postdiv= ($counter%$theNumCols == 0 || $counter == $numFeaturedProducts ||  $w->dishFeatured) ? "</div><!-- row-fluid1 -->" : ''; 
		
		$prediv2 = ($w->dishFeatured) ? '<div class="row-fluid">' : '';
		$postdiv2= ($w->dishFeatured) ? "</div><!-- row-fluid2 -->" : '';

		
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
		if($w->dishFeatured){
			$numColName = 'span12';
			$spanImg = 'span8';
			$spanDate = 'span4';
		}
		
		$jsonShoppingCart = encodeProductToJson($w, 'product');
		
		$options = get_option('cateringsoftware_options');
?>
<!-- Template starting -->
<?php echo $prediv; ?>
<div class="product product-overview <?php echo $numColName; ?> <?php echo $featured; ?> <?php echo $endrow; ?>">
	<?php echo $prediv2; ?>

		<?php if($w->dishFeatured) { ?>
		<div class="productimage <?php echo $spanImg; ?>"> 
		<?php } ?>



			<a href="/products/<?php echo $w->Dish_id; ?>/">
			
				<?php if($w->imageDish != null && $w->imageDish != "uploads/Dish/" && $showPicOnOverview) { ?>
				<img src="<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/uploads/Dish/'.$w->imageDish; ?>" alt="<?php echo $w->dishName; ?>" />
				<?php } elseif($options['NoImage'] != null) { ?>
					<img src="<?php echo $options['NoImage']; ?>" alt="<?php echo $w->dishName; ?>" />
				<?php } ?>
				<?php if($w->dishDeal) { ?>
				    <!-- placeholder for a 'promo' icon, settable via css -->
					<div class="deal-image">
						<img src="<?php echo bloginfo('template_url').'/images/deal.png'; ?>" />
					</div>
				<?php } ?>				
			</a>



		<?php if($w->dishFeatured) { ?>
		</div><!-- productimage -->
		<?php } ?>

		<div class="product-data <?php echo $spanDate; ?> ">
			
			<h3><?php echo $w->dishName; ?> <?php if($w->orderSize>1) echo '<small>(per '.$w->orderSize.' st.)</small>'; ?></h3>
			
			<?php if($w->dishFeatured){ ?>
				<p><?php echo nl2br($w->dishDesc); ?></p>
			<?php } ?>
			<div class="buy">
				<span class="price">&euro;<?php echo money_format('%.2n', $w->dishPrice); ?> </span>
				<?php if($useQuickAddButton == "true") : ?>
				<span class="addtocart" product-type="product" product-index='<?php echo $c; ?>'><a href="#">Voeg toe</a></span>
				<?php endif; ?>
				<?php if($useViewDetail == "true") : ?>
				<span class="viewdetail" ><a href="/products/<?php echo $w->Dish_id; ?>#<?php echo urlencode($w->dishName); ?>" class="detail-link">Details</a></span>
				<?php endif; ?>
			</div><!-- buy -->
			
		</div><!-- product-data -->
	<?php echo $postdiv2; ?>
</div><!-- product -->
<?php echo $postdiv; ?>
<!-- /Template -->

<?php 
		if(!$w->dishFeatured){
			$counter++;
		}
		$c++;
	} 
?>
