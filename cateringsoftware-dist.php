<?php

/*
Plugin Name: Catering Software connector
Plugin URI: http://www.sytematic.nl/
Description: Deze plugin koppelt met CateringSoftware.nl
Version: 0.2
Author: Marten Sytema
Author URI: http://www.sytematic.nl
License: GPL2
*/

/*  Copyright 2011  Marten Sytema  (email : marten@sytematic.nl)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Plugin initialization code */
//define('BASE_URL_CATERINGSOFTWARE', 'http://cateringsoftware.sytematic.nl/public');
define('CATSOFT_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define('CATSOFT_PLUGIN_ADMIN_PATH',CATSOFT_PLUGIN_PATH.'admin');
define('PLUGIN_SERVER_URL',get_site_url().'/wp-content/plugins/cateringsoftware-dist');


include_once('config.php');
include_once('functions.php');
setlocale(LC_MONETARY, 'it_IT');

/**
* First function to be called when the plugin is loaded. 
* Will initialize some global variables, that can be used in the theme for SEO purposes etc.
*/
function cateringsoftware_initialize(){
	include_once(CATSOFT_PLUGIN_PATH . '/admin/admin_menu_init.php');
	session_start(); //we use sessions to store the intermediate shopping cart

	$options = get_option('cateringsoftware_options');
	
	include_once('fetch.php');
	fetchDataAccordingToUrl($options); //initializes some globals containing product data, by curl_fetch data from backend.
		
	if(!is_admin()){
		includeMandatoryScripts();
		//initialize the cart by registering a wp_head action hook
		add_action('wp_head', 'cateringsoftware_cart_init_via_hook');
	}
}

/**
* Initialize the shopping cart, using settings from the options panel in the admin section
*/
function cateringsoftware_cart_init_via_hook(){
	echo cateringsoftware_cart_init(get_option('cateringsoftware_options'));
}


/**
* only include the scripts if the posts contain the shorttag.
*/
function check_for_shortcode($posts) {
    if ( empty($posts) )
        return $posts;
	
	$foundRecipes=false;
    
    // false because we have to search through the posts first
    $found = false;
    // search through each post
    
    if(!$found){
	    foreach ($posts as $post) {
	        // check the post content for the short code
	        if(stripos($post->post_content, '[cateringsoftware_recepten') !== false ) {
	        	$foundRecipes = true;
	        }
	        if(stripos($post->post_content, '[cateringsoftware_producten') !== false ) {
	        	$foundProducts = true;
	        } 
	        if(stripos($post->post_content, '[cateringsoftware_afrekenen') !== false ) {
	        	$foundCheckout = true;
	        } 

	        
	        if(stripos($post->post_content, '[cateringsoftware ') !== false ) {
	            // we have found a post with the short code
	            $foundWorkshopDetail=true;
	            // stop the search
	            break;
	        }
	    }
    }
	/*
	if(!is_admin())
		includeMandatoryScripts();
	*/
    if (!is_admin()) { //todo improve me
		includeScripts($foundRecipes,$foundWorkshopDetail, $foundProducts, $foundCheckout);
    }
    return $posts;
}

function includeMandatoryScripts(){

	wp_deregister_script('jquery');
	wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js');
	wp_enqueue_script('jquery');

	$tooltipCss = PLUGIN_SERVER_URL.'/bootstrap/css/bootstrap.min.css';
	$handle = 'stylebootstrap.css';
	wp_register_style($handle, $tooltipCss);
	wp_enqueue_style($handle);    
	
	$tooltipCss = PLUGIN_SERVER_URL.'/bootstrap/css/bootstrap-responsive.min.css';
	$handle = 'stylebootstrap-responsive.css';
	wp_register_style($handle, $tooltipCss, array('stylebootstrap.css'));
	wp_enqueue_style($handle);
	
	
	$tooltipCss = PLUGIN_SERVER_URL.'/style.css';
	$handle = 'style1.css';
	wp_register_style($handle, $tooltipCss);
	wp_enqueue_style($handle);
	
	$bootstrapScript = PLUGIN_SERVER_URL.'/bootstrap/js/bootstrap.min.js';
	$handle='bootstrapscript';
	wp_register_script($handle, $bootstrapScript, array('jquery'));
	wp_enqueue_script($handle);
	
	
	$jqueryui_tooltip = PLUGIN_SERVER_URL.'/jquery.json.min.js';
	$handle = 'jquery-json';
	wp_register_script($handle,$jqueryui_tooltip, array('jquery'));
	wp_enqueue_script($handle);		

	$jqueryui_tooltip = PLUGIN_SERVER_URL.'/jsDump.js';
	$handle = 'jquery-json-dump';
	wp_register_script($handle,$jqueryui_tooltip, array('jquery'));
	wp_enqueue_script($handle);	

	$jqueryui_tooltip = PLUGIN_SERVER_URL.'/jquery.shoppingcart.js';
	$handle = 'jquery-shoppingcart';
	wp_register_script($handle,$jqueryui_tooltip, array('jquery', 'jquery-json'));
	wp_enqueue_script($handle);		
	
	
	$gmaps = 'http://maps.google.com/maps/api/js?sensor=false&key=AIzaSyCPR76T3otWlBnPh1fK0Pe2bNgIJOBjVwc';
	$handle = 'google-maps';
	wp_register_script($handle,$gmaps,array());
	wp_enqueue_script($handle);	


}

function includeScripts($foundRecipes=false,$foundWorkshopDetail=false,$foundProducts=false,$foundCheckout=false){
	
	if($foundWorkshopDetail || $foundCheckout) {
		$jqueryUiCss = 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/themes/base/jquery-ui.css';
		$handle = 'jquery-ui-css';
		wp_register_style($handle,$jqueryUiCss, array());
		wp_enqueue_style($handle);
		
		$jqueryui = 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js';
		$handle='jquery-ui';
		wp_register_script($handle,$jqueryui, array('jquery'));
		wp_enqueue_script($handle);	
		$jqueryui_i18n = 'http://ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery-ui-i18n.min.js';
		$handle = 'jquery-ui-i18n';
		wp_register_script($handle,$jqueryui_i18n, array('jquery-ui'));
		wp_enqueue_script($handle);		
				
		$script =  PLUGIN_SERVER_URL.'/jquery.form.js';
		$handle = 'form.js';
		wp_register_script($handle, $script, array('jquery'));
		wp_enqueue_script($handle);   
		
		$script =  PLUGIN_SERVER_URL.'/jquery.validate.js';
		$handle = 'validation.js';
		wp_register_script($handle, $script, array('jquery', 'form.js'));
		wp_enqueue_script($handle);  
		
		 
	}
	
	if($foundRecipes) {
		$script = PLUGIN_SERVER_URL.'/tempo.js';
		$handle = 'tempo.js';
		wp_register_script($handle, $script, array());
		wp_enqueue_script($handle);   
	}
	
	if($foundWorkshopDetail){
   		$script =  PLUGIN_SERVER_URL.'/workshop-booking.js';
		$handle = 'workshop-booking.js';
		wp_register_script($handle, $script, array('jquery-ui-i18n'));
		wp_enqueue_script($handle); 
	}
	
	if($foundCheckout){
		$script =  PLUGIN_SERVER_URL.'/order-form.js';
		$handle = 'order-form.js';
		wp_register_script($handle, $script, array('jquery-ui-i18n'));
		wp_enqueue_script($handle); 
		

		
	}
}

function check_for_shortcode_widget($txt){
	 if(stripos($txt, '[cateringsoftware') !== false && !is_admin()) {
	     includeScripts((stripos($txt, '[cateringsoftware_deal_widget') !== false),false,false,false);
	     return do_shortcode($txt);
	 }
	 else return $txt;
}

/**
* Function that substitutes [cateringsoftware] shorttag for a script showing the pics
* NOTE: the workshop module not yet works with backend options!
*/
function cateringsoftware_shorttag($atts) {
	extract(shortcode_atts(
	array(
		'hostname'=>'test',
		'type'=>'workshops',
		'location'=>'Kookstudio PUUR in Breda',
		'aanbiedingen'=>'false' //only show aanbiedingen
		), $atts));

	global $locationName;
	global $theHostname;
	global $dealsOnly;

	$hostname;
	$type;
	$theHostname= $hostname;
	$locationName =  $location;
	$filename="";
	$aanbiedingen;
	
	$dealsOnly = $aanbiedingen;
	//explode around /
	$pieces = explode('/' , $_SERVER['REDIRECT_URL']);
	
	$i = 0;
	foreach($pieces as $p){
		$i++;
		if($p == $type) //id is in the following piece, if there.
			break;
	}
	if(isset($pieces[$i]) && is_numeric($pieces[$i])){
		$action='detail';
		$_GET['id'] = $pieces[$i];
	}
	else {
		$action='overview';
	}
	
	//'controller' code
	if($type == 'workshops'){
		include_once('fetch-workshop.php');	
		if($action == 'detail'){
			$filename="detail-workshop.php";
		}
		else {
			$filename="overview-workshop.php";	
		}
	}
	
	return executeFile($filename);
}


function cateringsoftware_packages_dishes($atts) {
	extract(shortcode_atts(
	array(
		'hostname'=>'test',
		'type'=>'packages',
		'numcols'=>'3', //can be: 12, 6, 4, 3, 2, 1, if something else is specified, 3 is used
		'view_detail'=>"true",
		'quick_add_button'=>"true",
		'show_image_on_detail'=>"true",
		'columns_50_50' => false,
		'no_pic_on_overview' => false
		), $atts));
	
	$options = get_option('cateringsoftware_options');

	global $locationName;
	global $theHostname;
	global $dealsOnly;
	global $theType;
	global $theNumCols;
	global $theAddress;
	global $useViewDetail;
	global $useQuickAddButton;
	global $showProductImageOnDetail;
	global $showPicOnOverview;
	global $columns5050;
	$hostname;
	$type;
	$numcols;
	$quick_add_button;

	$view_detail;
	
	$theHostname = $options['hostname'];
	$theType=$type;
	$theNumCols= $numcols;
	$useViewDetail = $view_detail;
	$useQuickAddButton = $quick_add_button ;
    $showProductImageOnDetail = $show_image_on_detail;
    
    $columns5050 = ( $columns_50_50 == "true" || $columns_50_50 == 1) ? true : false;
    
    if($no_pic_on_overview == null)
	    $showPicOnOverview = true;	    
    elseif($no_pic_on_overview == "true" ||$no_pic_on_overview == 1)
     	$showPicOnOverview = false;
    else 
     	$showPicOnOverview = true;
     	
     	
	//explode around /
	$pieces = explode('/' , $_SERVER['REDIRECT_URL']);
	
	$i = 0;
	foreach($pieces as $p){
		$i++;
		if($p == $type) //id is in the following piece, if there.
			break;
	}
	if(isset($pieces[$i]) && is_numeric($pieces[$i])){
		$action='detail';
		$_GET['id'] = $pieces[$i];
	}
	else {
		$action='overview';
	}
	
	//'controller' code
	include_once('fetch.php');	
	
	if($type == 'packages'){
		if($action == 'detail'){
			$filename="detail-packages.php";
		}
		else {
			$filename="overview-packages.php";	
		}
	
	}
	elseif($type =='products'){
		if($action == 'detail'){
			$filename="detail-products.php";
		}
		else {
			$filename="overview-products.php";	
		}
	}
	elseif($type =='materials'){
		if($action == 'detail'){
			$filename="detail-materials.php";
		}
		else {
			$filename="overview-materials.php";	
		}
	}

	return executeFile($filename);
}

function cateringsoftware_recipe_shorttag($atts){
	extract(shortcode_atts(
	array(
		'hostname'=>'test'
		), $atts));
	global $theHostname;
	$theHostname=$hostname;

	//first show a form, with a pass code, on click make it show the recipe
	return executeFile('recipe.php');

}

function cateringsoftware_checkout_shorttag($atts){

	global $theHostname;
	global $allowWaitress;
	$options = get_option('cateringsoftware_options');

	$theHostname=$options['hostname'];
	$allowWaitress = $options['allow_waitress'];
	return executeFile('checkout.php');
}

function cateringsoftware_kookagenda_shorttag($atts){
	extract(shortcode_atts(
	array(
		'hostname'=>'test'
		), $atts));
	global $theHostname;
	$theHostname=$hostname;

	include_once('fetch-workshop.php');	

	return executeFile('agenda.php');

} 

function cateringsoftware_category_shorttag($atts){
	extract(shortcode_atts(
	array(
		'type'=>'both', // packages | products | both
		'category_id'=>'all',
		'num_cols' =>'3',
		'group_title'=>null,
		'quick_add_button'=>"true",
		'display_type'=>'widget', //sidebar widget | page
		'category_order'=>null,
		'no_pic_on_overview'=>false,
		'show_category_description'=>"false"
		), $atts));
	global $theHostname;
	global $theNumCols;
	global $useQuickAddButton;
	global $categoryId;
	global $theGroupTitle;
	global $showPicOnOverview;
	global $categoryTitleOrder;
	global $categoryDetailHasLoaded;
	global $showCategoryDescription;
	$useQuickAddButton = $quick_add_button;



	$options = get_option('cateringsoftware_options');
	$theHostname=$options['hostname'];
	
	$theNumCols = $num_cols;
	
	if($category_order != null){
		$categoryTitleOrder = explode('|',$category_order);
	}
	include_once('fetch.php');	
	$categoryId = $category_id;
	$theGroupTitle = $group_title;


    if($no_pic_on_overview == null)
	    $showPicOnOverview = true;	    
    elseif($no_pic_on_overview == "true" ||$no_pic_on_overview == 1)
     	$showPicOnOverview = false;
    else 
     	$showPicOnOverview = true;
     	
    $showCategoryDescription= ($show_category_description == "true" || $show_category_description == 1);
     	

	if($display_type=='widget'){
		//return executeFile('category.php');	
		if($categoryId != 'all' && is_numeric($categoryId)){
			$_GET['id'] = $categoryId;
			return executeFile('overview-category.php');
		}
		else {
			return executeFile('overview-category.php');
		}
	}
	else {
		if($categoryId != 'all' && is_numeric($categoryId)){
			$_GET['id'] = $categoryId;
			return executeFile('detail-category.php');
		}
		else if($theGroupTitle != null && $theGroupTitle != ""){
			return executeFile('detail-category.php');
		}
	
		//explode around /
		$pieces = explode('/' , $_SERVER['REDIRECT_URL']);

		$i = 0;
		$type='categories';
		foreach($pieces as $p){
			$i++;
			if($p == $type) //id is in the following piece, if there.
				break;
		}
		if(isset($pieces[$i]) && is_numeric($pieces[$i])){
			$action='detail';
			$_GET['id'] = $pieces[$i];
		}
		else {
			$action='overview';
		}	
	

		if($action == 'detail'){
			return executeFile('detail-category.php');
		}
		else {
			return executeFile('overview-category.php');

		}
	}

}

function cateringsoftware_deal_widget_shorttag($atts){
	 global $theHostname;
	 $options = get_option('cateringsoftware_options');
	 $theHostname=$options['hostname'];
	 return executeFile('deal-widget.php');
}

function cateringsoftware_woonplaats_check($atts){
	$hostname;
	global $theHostname;
	global $theAddress;
	global $theRegion;
	global $renderButton;
	global $useDeliveryFormula;
	global $deliveryText;
	
	$options = get_option('cateringsoftware_options');
	$theHostname=$options['hostname'];

	$theAddress = $options['address'];
	$theRegion = $options['region'];
	$renderButton = $options['render_button'];
	$useDeliveryFormula = $options['use_formula'] == null ? 'false' : 'true';
	$deliveryText = $options['delivery_text'];
	$deliveryTextNearby = $options['delivery_text_nearby'];	
	return executeFile('deliverycheck.php');	
}



function cateringsoftware_cart_init($atts) {
	global $changeCodeError;

	if(isset($_GET['changeCode']) && !isset($_SESSION['changeCodeMessageDisplayed']) ){
	
		include_once('cart_store.php');
		$error = initStoreByChangeCode($_GET['changeCode']);
		print $error;
		if($error != null){
			$changeCodeError = $error;
		}	
	}


	//print_r($atts);
	extract(shortcode_atts(array(
	'hostname'=>'test', 
	'address'=>'', 
	'region' =>'nl', 
	'cart_class'=>'nav pull-right', 
	'cart_display_mode'=>'dropdown',//dropdown OR block
	'use_formula'=>'false',
	'allow_takeaway'=>'false',
	'use_discount_table'=>'false',
	'cart_text'=>'Totaal bestelling',
	'checkout_link'=>'Afrekenen',
	'prices_incl_vat' => 'false'
	), $atts));
	$hostname;
	global $theHostname;
	global $theAddress;
	global $theRegion;
	global $theCartClass;
	global $gCartDisplayMode;
	global $useDeliveryFormula;
	global $allowPickingUp;
	global $useDiscountTable;
	global $cartText;
	global $checkoutLink;
	global $pricesAreInclVat;
	
	$pricesAreInclVat = $prices_incl_vat;
	$theHostname = $hostname;
	$theAddress = $address;
	$theRegion = $region;
	$theCartClass = $cart_class;
	$gCartDisplayMode = $cart_display_mode;
	$useDeliveryFormula = $use_formula;
	$allowPickingUp = $allow_takeaway;
	$useDiscountTable = ($use_discount_table == "true") ? true : false;
	$cartText = $cart_text;
	$checkoutLink = $checkout_link;
	return executeFile('init-cart.php');


}

function cateringsoftware_shopping_cart($atts){
	return '<div id="shoppingcart"></div>';
}
function load_admin_script(){
	if (isset($_GET['page']) && $_GET['page'] == 'catering-software') {
        wp_enqueue_media();
        wp_register_script('file-upload-js', WP_PLUGIN_URL.'/cateringsoftware-dist/file.upload.js', array('jquery'));
        wp_enqueue_script('file-upload-js');
	}
}


// perform the check when the_posts() function is called
add_filter('the_posts', 'check_for_shortcode');
add_filter('widget_text','check_for_shortcode_widget');
add_filter('plugins_loaded', 'cateringsoftware_initialize'); //first code to be executed.


/* Register short tag for [cateringsoftware] */
add_shortcode('cateringsoftware', 'cateringsoftware_shorttag');
add_shortcode('cateringsoftware_recepten','cateringsoftware_recipe_shorttag');

add_shortcode('cateringsoftware_kookagenda','cateringsoftware_kookagenda_shorttag');
add_shortcode('cateringsoftware_producten','cateringsoftware_packages_dishes');

add_shortcode('cateringsoftware_afrekenen', 'cateringsoftware_checkout_shorttag');
add_shortcode('cateringsoftware_categorie','cateringsoftware_category_shorttag');

add_shortcode('cateringsoftware_init_plugin', 'cateringsoftware_cart_init');
add_shortcode('cateringsoftware_woonplaats_check', 'cateringsoftware_woonplaats_check');
add_shortcode('cateringsoftware_shopping_cart', 'cateringsoftware_shopping_cart');

add_shortcode('cateringsoftware_deal_widget', 'cateringsoftware_deal_widget_shorttag');

add_action('admin_enqueue_scripts', 'load_admin_script'  );


//SEO
add_filter('wp_title', 'modifyTitleIfDetailPage', 16, 3); 
add_filter('wp_head', 'addMetaDescriptionIfCSPage', 16);

?>