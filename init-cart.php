<?php
	include_once('config.php');
	include_once('fetch.php');
	global $theHostname;
	global $theAddress;
	global $theRegion;
	global $theCartClass;
	global $gCartDisplayMode;
	global $useDeliveryFormula;
	global $allowPickingUp;
	global $useDiscountTable;
	global $changeCodeError;
	global $cartText;
	global $checkoutLink;
	$shouldRender=false;

//print_r($_SESSION);
	if(!isset($_SESSION['changeCodeMessageDisplayed']) && isset($_GET['changeCode'])){
		$shouldRender = true;
		$_SESSION['changeCodeMessageDisplayed']=true;
	} //comment out in production
	else unset($_SESSION['changeCodeMessageDisplayed']);
?>

<script type="text/javascript">
<?php echo '//'.$useDeliveryFormula;?>

var allProducts = {};
jQuery(document).ready(function(){
	jQuery('#shoppingcart').shoppingCart({ 
			"detail" : <?php 
			//are we on a detail page?
			//explode around /
			$pieces = explode('/' , $_SERVER['REDIRECT_URL']);
			$isDetail=false;
			$prev = null;
			foreach($pieces as $p){
				
				if(is_numeric($p) && $prev != 'categories'){
					
					$isDetail=true;
					break;
				}
				$prev = $p;
			}
					
			$x = $isDetail ? 'true' : 'false'; 
					
			echo $x;
			?>,
			"address" : "<?php echo $theAddress; ?>",
			"region"  : "<?php echo $theRegion; ?>",
			"deliveryCosts" : <?php echo fetchDeliveryCosts(array('hostname'=>$theHostname, 'ordering'=>'ASC', 'orderBy'=>'minKm', 'useFormula'=>(($useDeliveryFormula == "false") ? false : true)), true); ?>,
			"cartClass" : "<?php echo $theCartClass; ?>",
			"cartDisplayMode" : "<?php echo $gCartDisplayMode; ?>",
			"session_url" : "/wp-content/plugins/cateringsoftware-dist/cart_store.php",
			"deliveryFormula" : <?php echo $useDeliveryFormula; ?>,
			"checkout_link" : "<?php echo $checkoutLink; ?>",
			"cart_text" : "<?php echo $cartText; ?>",
			<?php if($useDiscountTable) : ?>
			"discountTable" : <?php echo fetchDiscountTable(array('hostname'=>$theHostname), true); ?>,
			<?php endif; ?>
			"pickupAndDelivery" : <?php echo $allowPickingUp; ?>
	});
	
	<?php
	if($shouldRender){
		echo "jQuery('#changeCodeResult').removeClass('hidden');";
		echo "jQuery('#changeCodeResultMessage').html('";
		if($changeCodeError != null){
			echo $changeCodeError;
		}
		else{
			echo 'Uw bestelling is opgehaald, zodat u deze kunt aanpassen.';						
		}	
		echo "');";
		
		if($changeCodeError != null) {
			echo "jQuery('#changeCodeBox').addClass('alert-error');";
		}
		else {
			echo "jQuery('#changeCodeBox').addClass('alert-success');";
		}
	}
	?>

});
</script>