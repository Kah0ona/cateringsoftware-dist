<?php
define('SYSTEM_URL_CATERINGSOFTWARE', 'http://beheer.cateringsoftware.nl');
define('BASE_URL_CATERINGSOFTWARE', SYSTEM_URL_CATERINGSOFTWARE.'/public');
define('EURO_FORMAT', '%.2n');

define('SUBMIT_BOOKING_URL','/wp-content/plugins/cateringsoftware-dist/submit_booking.php');
define('SUBMIT_ORDER_URL','/wp-content/plugins/cateringsoftware-dist/submit_order.php');

define('SUBMIT_EMAIL_URL','/wp-content/plugins/cateringsoftware-dist/submit_email.php');


//below this line, do not edit!
$theHostname= '123';
$locationName='123';
$theType='123';
$theNumCols='123';
$dealsOnly = false;
$couponUrl = SYSTEM_URL_CATERINGSOFTWARE.'/public/coupons';
$deliveryCostUrl = SYSTEM_URL_CATERINGSOFTWARE.'/public/deliverycosts';
$theAddress='';
$theRegion = 'nl';
$theCartClass='';
$gCartDisplayMode='';
$useQuickAddButton=true;
$useViewDetail=true;
$useDeliveryFormula=true;
$allowPickingUp = 'false'; //means only delivery
$deliveryText = "";
$deliveryTextNear="";
$showProductImageOnDetail;
$categoryId=null;
$theGroupTitle;
$useDiscountTable = "false";
$categoryTitleOrder = null;
$presetProductData=null;
$presetPackageData=null;
$presetMaterialData=null;
$presetCategoryData=null;
$changeCodeError = null;
$showPicOnOverview = true;
$cartText = '';
$checkoutLink = '';
$pricesAreInclVat = true;
$columns5050 = false;
$showCategoryDescription=false;
?>