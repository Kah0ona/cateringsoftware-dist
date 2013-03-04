<?php
//include_once('config.php');
	global $theHostname;
	global $theType;
	global $theNumCols;
	global $presetProductData;
	$params = array(
		'hostname'=>$theHostname,
		'Dish_id'=>$_GET['id'],
	);
	$dealsClass ="";

	if($presetProductData==null){
		$params = array(
			'hostname'=>$theHostname,
			'dishPublished'=>'true',
			'orderBy'=>'dishName',
			'ordering'=>'ASC',
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
	
	$w=null;
	if(count($products)>0){
		$w = $products[0];
	}
	
	$jsonShoppingCart = encodeProductToJson($w);
 
?>
<script type="text/javascript" >
	allProducts.products = [<?php echo $jsonShoppingCart; ?>];
	
	
	function formatEuro2(price){
		Number.prototype.formatMoney = function(c, d, t){
		var n = this, c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : d, t = t == undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
		   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
		};
	
		return price.formatMoney(2,',','.');
	}
	var dishOptions = null;
	
	function renderDishOptions(json){
		dishOptions = json;
		var arr = json.options;
		var elt = jQuery('#dishOptions');
		
		if(arr == null || arr.length==0)
			return;
			
		var list = "<h3>Kies extra's</h3><ul class='product-list'>";
		for(var i = 0; i < arr.length; i++){
			var obj = arr[i];
			var price = "";
			if(obj.optionSalesPrice != null && parseFloat(obj.optionSalesPrice) > 0){
				price = " (&euro; "+formatEuro2(obj.optionSalesPrice)+")";
			}
			list += "<li><input type='checkbox' id='ingredientsid_"+obj.ingredients_id+"' class='ingredients' name='ingredients' /> "+obj.ingredientName+price+"</li>";
		}
		list += "</ul>";
		elt.append(list);
	}	
	
	
	
</script>

<div class="product single-product">
	<div class="row-fluid">
		<div class="product span12 featured">
			<div class="row-fluid">
				<div class="span12"><p><a class="backtooverview" href="javascript:history.back()">&larr; terug naar overzicht</a></p></div>
			</div>
			<div class="row-fluid">		
			   <?php if($w->imageDish != null) { $imgSpan = '8'; $imgSpan2='4'; ?>
				   <div class="productimage span<?php echo $imgSpan; ?>"> 
					  <img alt="<?php echo $w->dishName; ?>" src="<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'.$w->imageDish; ?>">
	
					  
					  <?php if($w->dishDeal) { ?>
					    <!-- placeholder for a 'promo' icon, settable via css -->
						<div class="deal-image">
							<img src="<?php echo bloginfo('template_url').'/images/deal.png'; ?>" />
						</div>
						<?php } ?>	
					  
				   </div><!-- productimage -->
			  <?php } else { $imgSpan2 = '12'; }?>
	  		   <div class="product-data span<?php echo $imgSpan2; ?>">
				    <span class="price">€ <?php echo money_format('%.2n', $w->dishPrice); ?> </span>
				    <h2><?php echo $w->dishName; ?></h2>
				    <?php if($w->orderSize > 1) : ?>
				    <p>Bestellen per: <?php echo $w->orderSize; ?> stuks</p>
				    <?php endif; ?>
				    <?php if($w->dishDeliveryOptions != 'beide'): ?>
				    <p>Bijzonderheden: <?php echo $w->dishDeliveryOptions; ?></p>
				    <?php endif; ?>
					<p><?php echo nl2br($w->dishDesc); ?></p>
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
						    	<input class="input-small" name="product-amount" id="product-amount" value="<?php echo $w->orderSize; ?>" type="text" /> 
						    </span>
					    </h3>
				    </div>
				</div>
				<div class="row-fluid datarow">
					<div class="span12 product-data standard-products">
						
						<div id="dishOptions"></div>
						<!-- get it with jsonPadding -->
						<script src="<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/public/dishoptions'; ?>?Dish_id=<?php echo $w->Dish_id; ?>&callback=renderDishOptions"></script>
				    </div>
				</div>
				
			</div><!--left column span6 -->
			<div class="span6 data-right">
				<div class="row-fluid datarow">
					<div class="span12 product-data ">
					    <h3>Toevoegen</h3>
						<p class="add-to-cart-text">Klik op de knop om dit product toe te voegen aan het winkelwagentje</p>
						<p  class="product-added hidden alert alert-info">
							<strong>Toegevoegd</strong>! U kunt verder winkelen, of <a href="/checkout">afrekenen</a>.
						</p> 
						<p  class="product-size-wrong hidden alert alert-error">
							
						</p> 

					    <span product-type="product" product-index='0' class="addtocart">
						  <a href="#" class="btn" >Voeg toe</a>
						</span>
					</div>		
				</div><!-- row-fluid -->						
			</div><!-- right column span6 -->
		</div>
	</form>
</div>