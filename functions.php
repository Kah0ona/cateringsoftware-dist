<?php
/**
* Executes the specified php file, and returns the (evaluated) output in a string
*/
function executeFile($filename){
	$filename = WP_PLUGIN_DIR.'/cateringsoftware-dist/'.$filename;
	$output="";
	$file = fopen($filename, "r");
	while(!feof($file)) {
		//read file line by line into variable
	    $output = $output . fgets($file, 4096);
	}
	
	ob_start();
	eval('?>' . $output);
	$bod = ob_get_contents();
	ob_end_clean();	
	
	fclose ($file);
	return $bod;
}


/**
* decodes array in get string. only works for 1-dimensional array
Test case:

	$p = Array(
		'a'=>1,
		'b'=>'abcdefg',
		'c'=>Array('c0','c1')
	);
	
	$res = decodeParamsIntoGetString($p);
	
	$expected = 'a=1&b=abcdefg&c=c0&c=c1';
	if($res == $expected)
		echo 'passed';
	else
		echo 'failed: '.$res.' != '.$expected;
*/
function decodeParamsIntoGetString($params){
	$ret = "";
	$c = 0;
	foreach($params as $k=>$v){
		$ret .= ($c == 0) ? '' : '&';		
		if(is_array($v)){
			$c2 = 0;
			foreach($v as $v1){
				if($c2 != 0)
					$ret .= '&';
				$ret .= $k.'='.urlencode($v1);
				$c2++;
			}
		
		}
		else {

			$ret .= $k.'='.urlencode($v);

		}
		$c++;
	}
	return $ret;
} 

	

function getAmountForm($default, $classnames = '', $id=''){
	$max = 500;
	if($default > $max)
		$max = $default;
		
	if($id == '')
		$id = '';
	else 
		$id = 'id="'.$id.'"';

	$ret = "<select  class='amount-item ".$classnames."' ".$id.">\n";

	for($i = 0; $i < $max ; $i++){
		$def="";
		if($i == $default){
			$def = "selected='selected'";
		}
		$ret.="<option value='".$i."' ".$def.">".$i."</option>\n";
	}

	$ret .= "</select>\n";

	return $ret;
}



/**
* Encodes a package to JSON in the format the shoppingcart jquery plugin wants.
* { "type" : "package", "Package_id": 14, "title": "Rauwkost Dip", "price": 8.50, "products" : [ {"type": "product", "Product_id" : 1, "title" : "Knoflooksaus", "quantity" : 1}, {"type": "product",  "Product_id" : 7, "title" : "Sambasaus", "quantity" : 1 }] }'
*/
function encodeToJson($pkg){

	$ret = array();
	$ret["Package_id"]= $pkg->Package_id;
	$ret["type"] = "package";
	$ret["thumb"] = SYSTEM_URL_CATERINGSOFTWARE.'/uploads/Package/'.$pkg->imagePkg;
	$ret["title"] = $pkg->pkgName;	
	$ret["desc"]  = $pkg->pkgDesc;	
	$ret["price"] = $pkg->packagePrice;	
	$ret["VAT"] =  $pkg->pkgVAT;
	$ret["deliveryOptions"] = $pkg->packageDeliveryOptions;

	$children = array();
		
	if($pkg->Dish != null) {
		foreach($pkg->Dish as $d){
			if($d->containmentType != 'aanvulling'){ //these will be handled separately
				$children[] = encodeProductToJson($d, 'product', false);
			}
		}
	}
	
	$ret["products"] = $children;

	$childrenMaterial = array();

	if($pkg->Material != null) {
		foreach($pkg->Material as $d){
			if($d->containmentTypeMaterial != 'aanvulling'){
				$childrenMaterial[] = encodeProductToJson($d, 'material', false);
			}
		}
	}
	$ret["materials"] =	 $childrenMaterial;
	
	
	return json_encode($ret);
}

function encodeProductToJson($pro, $type='product', $getString = true){
	$id=0;
	$title="";
	$thumb="";
	$quantity=0;
	$contain="";
	$price=0;
	$VAT=0;
	$dishWeightFactor = 0;
	$desc = "";
	$orderSize = 1;
	$deposit = 0;
	$showAmount = true;

	
	if($type == 'product'){
		$id = $pro->Dish_id;
		$title = addslashes($pro->dishName);	
		$thumb = SYSTEM_URL_CATERINGSOFTWARE.'/uploads/Dish/'.$pro->imageDish;
		$quantity = $pro->amount;


		$contain = $pro->containmentType;
		$price = $pro->dishPrice;
		$VAT = $pro->dishVAT;
		$dishWeightFactor = $pro->dishWeightFactor;
		$desc = $pro->dishDesc;
		$orderSize = $pro->orderSize;
		$deliveryOptions = $pro->dishDeliveryOptions;
		$deposit = $pro->dishDeposit;
		$showAmount = $pro->showNumberOnSite;
	}
	elseif($type == "material") {
		$id = $pro->Material_id;
		$title = addslashes($pro->materialName);	
		$thumb = SYSTEM_URL_CATERINGSOFTWARE.'/uploads/Material/'.$pro->materialImage;
		$quantity = $pro->amountMaterial;
		$contain = $pro->containmentTypeMaterial;
		$price = $pro->materialPrice;
		$VAT = $pro->materialVAT;
		$desc = $pro->materialDesc;
		$orderSize = 1;
		$deliveryOptions = $pro->materialDeliveryOptions;
		$deposit = $pro->materialDeposit;
	}

	if($quantity === null) {
		$quantity=1;
	}
		
	$jsonObj = array (
		"Product_id" => $id,
		"type" => $type,
		"title" => $title,
		"desc" => $desc,
		"thumb" => $thumb,
		"quantity" => $quantity,
		"containmentType" => $contain,
		"price"=> $price,
		"dishWeightFactor" => $dishWeightFactor,
		"VAT" => $VAT,
		"orderSize" => $orderSize,
		"deliveryOptions" => $deliveryOptions,
		"deposit" => $deposit,
		"showAmount" => $showAmount
	);
	if($getString){
		return json_encode($jsonObj);				
	}
	else {
		return $jsonObj;
	}
}

function isCSPage(){
	$productPattern = '/\/products/';
	$categoryPattern = '/\/categories/';
	$packagePattern = '/\/packages/';
	$materialPattern = '/\/materials/';	
	$path = $_SERVER['REQUEST_URI'];

	$patterns = array( // ie. /products/
		$productPattern, $categoryPattern, $packagePattern, $materialPattern
	);
	
	foreach ($patterns as $pattern){
		$matches = array();
		$hasMatched = preg_match($pattern, $path);
		if($hasMatched) return true;
	}
	return false;
}

function addMetaDescriptionIfCSPage(){
	if(!isCSPage())
		return;

	global $presetProductData;
	global $presetPackageData;
	global $presetMaterialData;
	global $presetCategoryData; 
	
	if(isDetailPage()){
		if($presetProductData != null){
			$metadesc = $presetProductData[0]->dishDesc;
		}
		if($presetPackageData != null){
			$metadesc = $presetPackageData[0]->pkgDesc;
		}
		if($presetMaterialData != null){
			$metadesc = $presetMaterialData[0]->materialDesc;
		}
		if($presetCategoryData != null){
			$metadesc = $presetCategoryData[0]->categoryDesc;
		}				
		
	}
	else {
		$options = get_option('cateringsoftware_options');
	
		if($presetProductData != null){
			$metadesc = $options['product_overview_metadesc'];
		}
		if($presetPackageData != null){
			$metadesc = $options['package_overview_metadesc'];	
		}
		if($presetMaterialData != null){
			$metadesc = $options['material_overview_metadesc'];
		}
		if($presetCategoryData != null){
			$metadesc = $options['category_overview_metadesc'];
		}	
	}
	if(strlen($metadesc) > 0)
		echo '<meta name="description" content="' . esc_attr( strip_tags( stripslashes( $metadesc ) ) ) . '"/>' . "\n";
}


function modifyTitleIfDetailPage($title, $sep, $seplocation ){
	if(isDetailPage()){
		global $presetProductData;
		global $presetPackageData;
		global $presetMaterialData;
		global $presetCategoryData; 
		$prefix = '';
		if(count($presetProductData)>0)
			$prefix .= $presetProductData[0]->dishName;
	
		if(count($presetMaterialData)>0)
			$prefix .= $presetMaterialData[0]->materialName;
		
		if(count($presetPackageData)>0)
			$prefix .= $presetPackageData[0]->pkgName;
		
		if(count($presetCategoryData)>0)
			$prefix .= $presetCategoryData[0]->categoryName;

		$prefix .=' bestellen bij ';	
		
		
		return $prefix.$title;
	}
	return $title ;
}


/**
* Observes the URL, and determines if the page is a detail page of a category, product, package or material (checks for /123/ in the url)
*/
function isDetailPage(){
	$path = $_SERVER['REQUEST_URI'];
	$patterns = array( // ie. /products/123/ is matched, or /categories/123 is matched
		'/\/products\/[0-9]+/',
		'/\/categories\/[0-9]+/',
		'/\/packages\/[0-9]+/',
		'/\/materials\/[0-9]+/'
	);
	foreach($patterns as $pattern){
		if(preg_match($pattern, $path)){ 
			return true;
		}
	}
	return false;
}

/**
* if this is a detail page for one of the CS entities, returns the id, returns 0 otherwise.
*/ 
function getDetailPageId(){
	$path = $_SERVER['REQUEST_URI'];
	$matched = preg_match('/\/([0-9]+)/', $path, $matches);
	if(!$matched)
		return 0;
	else {
		return $matches[1];
	}
	
}

?>