<?php 
	global $theHostname;
	global $theNumCols;
	global $theGroupTitle;
	global $presetCategoryData;
	global $showCategoryDescription;
	
	if($presetCategoryData == null ){
		$params = array(
			'hostname'=>$theHostname,
		);
	
		if(isset($_GET['id'])){
			$params['Category_id'] = $_GET['id'];
		}
	
		if($theGroupTitle != null && $theGroupTitle != ""){
			$params['groupTitle'] = $theGroupTitle;
		}
		
		
		$cats = fetchCategories($params);
	}
	else {
		$cats = $presetCategoryData;		
	}

	$cat = null;
	if(count($cats)>0){
		$cat = $cats[0];
	}

	if($showCategoryDescription && $cat->categoryDesc != null && $cat->categoryDesc != "") {
		echo '<p>'.nl2br($cat->categoryDesc).'</p>';
	} 

	$dishes = $cat->Dish;
	if(count($dishes) > 0){
		global $presetProductData;
		$presetProductData = $dishes;
		echo executeFile('overview-products.php');
	}


	$pkgs = $cat->Package;
	if(count($pkgs) > 0){
		global $presetPackageData;
		$presetPackageData = $pkgs;
		echo executeFile('overview-packages.php');
	}

	$mats = $cat->Material;
	if(count($mats) > 0){
		global $presetMaterialData;
		$presetMaterialData = $mats;
		echo executeFile('overview-materials.php');
	}
?>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('.pagetitle').html("<?php echo $cat->categoryName; ?>");
});

</script>



