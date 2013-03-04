<?php
include_once('functions.php');
/*
* $params is an array of key value parameters, that willl be encoded in the get
*/
function fetchPackages($params, $returnString=false){
	//fetch workshops from the backend
	$workshopUrl = BASE_URL_CATERINGSOFTWARE.'/packages?'.decodeParamsIntoGetString($params);
	$jsonString = curl_fetch($workshopUrl);
//	$jsonString = utf8_encode($jsonString);
	if($returnString)
		return $jsonString;
	else 
		return json_decode($jsonString);
}


function fetchDishes($params, $returnString=false){
	//fetch workshops from the backend
	$workshopUrl = BASE_URL_CATERINGSOFTWARE.'/dishes?'.decodeParamsIntoGetString($params);
	$jsonString = curl_fetch($workshopUrl);
//	$jsonString = utf8_encode($jsonString);
	if($returnString)
		return $jsonString;
	else 
		return json_decode($jsonString);
}

function fetchMaterials($params, $returnString=false){
	//fetch workshops from the backend
	$workshopUrl = BASE_URL_CATERINGSOFTWARE.'/materials?'.decodeParamsIntoGetString($params);
	$jsonString = curl_fetch($workshopUrl);
//	$jsonString = utf8_encode($jsonString);
	if($returnString)
		return $jsonString;
	else 
		return json_decode($jsonString);

}

function fetchCategories($params, $returnString=false){
	//fetch workshops from the backend
	$workshopUrl = BASE_URL_CATERINGSOFTWARE.'/categories?'.decodeParamsIntoGetString($params);
	$jsonString = curl_fetch($workshopUrl);
//	$jsonString = utf8_encode($jsonString);
//	print_r(BASE_URL_CATERINGSOFTWARE.'/categories?'.decodeParamsIntoGetString($params));

	if($returnString)
		return $jsonString;
	else 
		return json_decode($jsonString);
}

function fetchDeliveryCosts($params, $returnString=false){
	$workshopUrl = "";
	if($params['useFormula']){
		$hostname=  $params['hostname'];
		$workshopUrl = BASE_URL_CATERINGSOFTWARE.'/deliverycostformulas?hostname='.$hostname;
	}
	else {
		$workshopUrl = BASE_URL_CATERINGSOFTWARE.'/deliverycosts?'.decodeParamsIntoGetString($params);
	}

	//fetch workshops from the backend
	$jsonString = curl_fetch($workshopUrl);
//	$jsonString = utf8_encode($jsonString);
	if($returnString)
		return $jsonString;
	else 
		return json_decode($jsonString);
}

function fetchDiscountTable($params, $returnString = false){
	$workshopUrl = BASE_URL_CATERINGSOFTWARE.'/discounttables?'.decodeParamsIntoGetString($params);

	//fetch workshops from the backend
	$jsonString = curl_fetch($workshopUrl);
//	$jsonString = utf8_encode($jsonString);
	if($returnString)
		return $jsonString;
	else 
		return json_decode($jsonString);	
	
}

function fetchCart($params, $returnString = false){
	$workshopUrl = BASE_URL_CATERINGSOFTWARE.'/carts?'.decodeParamsIntoGetString($params);

	//fetch workshops from the backend
	$jsonString = curl_fetch($workshopUrl);
//	$jsonString = utf8_encode($jsonString);
	if($returnString)
		return $jsonString;
	else 
		return json_decode($jsonString);	
}


function curl_fetch($url){
	$cached = getCachedData($url);
	$cached=null; //comment this out this if u want caching
	if($cached != null){
		//return cachedData
		return $cached;
	}
	else {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$json = curl_exec($ch);
	
		curl_close($ch);
		setCachedData($url, $json); 
		return $json;
	}
}

function getCachedData($url){
	//todo: get parameters out of the url, and see if a different url with same parameters IS in the set.
	if(isset($_SESSION[md5($url)])){
		return $_SESSION[md5($url)];
	}
	else {
		return null;
	}
}

function setCachedData($url, $data){
	$_SESSION[md5($url)] = $data;
}

/**
* Examines the $_SERVER['REDIRECT_URI'], and determines which data to fetch.
* Stores the data in the relevant global variables $presetProductData, $presetMaterialData, $presetPackageData
*/
function fetchDataAccordingToUrl($options){
	global $presetProductData;
	global $presetPackageData;
	global $presetMaterialData;
	global $presetCategoryData;

	$path = $_SERVER['REQUEST_URI'];
	$productPattern = '/\/products\//';
	$categoryPattern = '/\/categories\//';
	$packagePattern = '/\/packages\//';
	$materialPattern = '/\/materials\//';	
	
	$patterns = array( // ie. /products/
		$productPattern, $categoryPattern, $packagePattern, $materialPattern
	);

	$params = array(
		'hostname'=>$options['hostname']					
	);
	$id = 0;
		
	foreach ($patterns as $pattern){
		$matches = array();
		$hasMatched = preg_match($pattern, $path);
		if($hasMatched){
			if($pattern == $productPattern){
				if(isDetailPage()) 
					$params['Dish_id'] = getDetailPageId();
				else  {
					$params['dishPublished']=true;
					$params['useNesting'] = 'false';
				}
					
				$presetProductData = fetchDishes($params);
			}
			if($pattern == $categoryPattern){
				global $theGroupTitle;
					
				if(isDetailPage()) 
					$params['Category_id'] = getDetailPageId();
				
				$presetCategoryData = fetchCategories($params);
			}
			if($pattern == $materialPattern){
				if(isDetailPage()) 
					$params['Material_id'] = getDetailPageId();
				else {
					$params['useNesting'] = false;
				}

				$presetMaterialData = fetchMaterials($params);
			}
			if($pattern == $packagePattern){

				if(isDetailPage()) {
					$params['Package_id'] = getDetailPageId();
				}
				else {
					$params['pkgPublished'] = 'true';	
					$params['useNesting'] = 'false';
				}
					
				$presetPackageData = fetchPackages($params);
			}
			return;
		}
	}

	
	
}


?>