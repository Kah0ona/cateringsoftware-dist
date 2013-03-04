<?php
	include_once('config.php');
	global $theHostname;
	global $theType;
	global $theNumCols;
	global $useViewDetail;
	global $useQuickAddButton;
	global $presetPackageData;
	global $showPicOnOverview;

	$packages = null;
	if($presetPackageData == null){
		$params = array(
			'hostname'=>$theHostname,
			'pkgPublished'=>'true',
			'orderBy'=>'pkgFeatured',
			'ordering'=>'DESC',
			'useNesting'=>'false'
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
	 

// Let's justify to the left, with 14 positions of width, 8 digits of
// left precision, 2 of right precision, withouth grouping character
// and using the international format for the nl_NL locale.
setlocale(LC_MONETARY, 'it_IT');
?>

<script type="text/javascript">
	allProducts.packages = [
<?php
	$c = 0;
	foreach($packages as $w){
		$jsonShoppingCart = encodeToJson($w);
		echo $jsonShoppingCart;
		if($c < count($packages)-1 ){
			echo ',';
		}
		$c++;
	}

?>
	];
</script>

<?php 
	$numFeaturedProducts = 0;
	foreach($packages as $w){
		if(!$w->pkgFeatured){
			$numFeaturedProducts++;
		}
	}
	
	$counter = 1;
	$c = 0;
	foreach($packages as $w){ 
		$featured = '';
		if($w->pkgFeatured){
			$featured='featured';
		}

		$endrow = ($counter%$theNumCols == 0) ? 'endrow' : '';
		$prediv = ($counter%$theNumCols == 1 || $theNumCols == 1 || $w->pkgFeatured) ? '<div class="row-fluid">' : '';
		$postdiv= ($counter%$theNumCols == 0 || $counter == $numFeaturedProducts || $w->pkgFeatured) ? "</div><!-- row-fluid1 -->" : ''; 

		$prediv2 = ($w->pkgFeatured) ? '<div class="row-fluid">' : '';
		$postdiv2= ($w->pkgFeatured) ? "</div><!-- row-fluid2 -->" : '';

		
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
		if($w->pkgFeatured){
			$numColName = 'span12';
			$spanImg = 'span8';
			$spanDate = 'span4';
		}
		
		$jsonShoppingCart = encodeToJson($w);
		
?>
<!-- Template starting -->
<?php echo $prediv; ?>
<div class="product  product-overview  <?php echo $numColName; ?> <?php echo $featured; ?> <?php echo $endrow; ?>">
	<?php echo $prediv2; ?>

		<?php if($w->pkgFeatured) { ?>
		<div class="productimage <?php echo $spanImg; ?>"> 
		<?php } ?>
		
			<a href="/packages/<?php echo $w->Package_id; ?>/">
				<?php  $showPicOnOverview; if($w->imagePkg != null && $showPicOnOverview) { ?>
				<img src="<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'.$w->imagePkg; ?>" alt="<?php echo $w->pkgName; ?>" />
				<?php } ?>

				<?php if($w->packageDeal) { ?>
					<div class="deal-image">
						<img src="<?php echo bloginfo('template_url').'/images/deal.png'; ?>" />
					</div>
				<?php } ?>	
				
			</a>
			
		<?php if($w->pkgFeatured) { ?>
		</div><!-- productimage -->
		<?php } ?>

		<div class="product-data <?php echo $spanDate; ?> ">
			
			<h3><?php echo $w->pkgName; ?></h3>
			
			<?php if($w->pkgFeatured){ ?>
				<p><?php echo nl2br($w->pkgDesc); ?></p>
			<?php } ?>
			<div class="buy">
				<span class="price">&euro;<?php echo money_format('%.2n', $w->packagePrice); ?> </span>
				<?php if($useQuickAddButton=="true" && false) : //now not feasible anymore, since API becomes too slow. ?>
				<span class="addtocart" product-type="package" product-index='<?php echo $c; ?>'><a href="#">Voeg toe</a></span>
				<?php endif; ?>
				<?php if($useViewDetail=="true") : ?>
				<span class="viewdetail" ><a href="/packages/<?php echo $w->Package_id; ?>#<?php echo $w->pkgName; ?>">Details</a></span>
				<?php endif; ?>
				
			</div><!-- buy -->
			
		</div><!-- product-data -->
	<?php echo $postdiv2; ?>
</div><!-- product -->
<?php echo $postdiv; ?>
<!-- /Template -->

<?php 
		if(!$w->pkgFeatured){
			$counter++;
		}
		$c++;
	} 
?>
