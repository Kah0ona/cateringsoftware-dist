/**
* JQuery plugin that acts as a shopping cart 
* Author: Marten Sytema (marten@sytematic.nl)
* Plugin dependencies: 
* - JQuery-JSON: http://code.google.com/p/jquery-json/
* - Google Maps JS Api
* Version: 0.5
*/
(function( $ ) {
	var cartDataStore = null; //TODO is this elegant? Refactor?
	var cartPluginInstance = null;
	var settings = null;
	var totalExclVat=0;
	var totalInclVat=0;
	var vatMap = {};
	var discount = 0;
	var distance = 0;
	var	couponType = "";
	var elt;//the div to render everything on.
	var deliveryCosts = {"price": 0}; //object with details about the delivery costs. based on address user filled out in checkout form.
	var weightOfDishes = 0; //a number indicating how 'heavy' this order is. Used in calculating how many people this order is sufficient for.
	var methods = {
		
		//initializes the plugin
	    init : function( options ) { 
		    settings = $.extend( {
		      'detail' : false, //is this a detail page?
		      'address' : 'some address', // used for google maps distance calculation
		      'region': 'nl',
		      'deliveryCosts' : [],
		      'deliveryFormula' : false,//true is table, false is the other one
		      'checkout_page' : '/checkout',
		      'checkout_link' : 'Aanvraag versturen',
		      'cart_text' : 'Mijn bestelling',
		      'pickupAndDelivery' : false //false meanse: only delivery
		    }, options);
		    cartPluginInstance = this;
		    return this.each(function(){
		    	methods.bindButtons();
		    	elt = $(this);

		    	methods.load(function(jsonObj){

				    dataFromCookie = jsonObj;
				    var $this = $(this);
			    	var data = $this.data('shoppingCart');
			    	
			    	if( data == null) {
			    		//set the shoppingCart entry in the data object of jQuery
			    		$(this).data('shoppingCart', dataFromCookie);
			    		cartDataStore = $(this).data('shoppingCart');
			    	}
			    	methods.render();
			    	methods.updatePrices();
			    	methods.calculateWeight();
			    	methods.calculateDeposit();
			    	var compareToAddress = '';
			    	$('.address-line').each(function(){
			    		compareToAddress += " "+$(this).val();
			    	});		    	
			    	
			    	if(methods.allAddressFieldsFilledOut()){
				    	methods.calculateDistance(compareToAddress, function(){
					    	methods.updatePrices();		
					    });		    	
				    }
				    else{
					    distance = 0;
					    methods.updatePrices();							    
				    }
				    
			    	methods.checkCoupon(function(disc, couponType){
				    	methods.updatePrices();
			    	});		    	
			    	
		     	    //if we are on a detail page, do the basic calculation of the totals of the flexible items of packages
		     	    if(settings.detail) {
					    methods.updateFlexibleItemsTotals();
					}
		    	});
		    });
	    },
	    
	    allAddressFieldsFilledOut : function() {
 	        var ret = true;
 	        var deliveryElseWhere = $('#deliveryElsewhere').is(':checked');
		    $('.address-line').each(function(){
		    	var x = $(this).val();
		    	if(deliveryElseWhere){
			    	if($(this).attr('id').indexOf("delivery") !== -1){
				    	if(x === undefined || x === null || x == "") {
				    		methods.logger("Not all address fields set");
					    	ret = false;
				    	}
			    	}
		    	}
		    	else {
		    		if($(this).attr('id').indexOf("delivery") === -1){
				    	if(x === undefined || x === null || x == "") {
				    		methods.logger("Not all address fields set");
					    	ret = false;
				    	}
			    	}
		    	}
			});
			
			if(ret){
	    		methods.logger("All address fields set");			 
			}
			return ret;
	    },
	    updateFlexibleItemsTotals : function(){
	   		$('.total-sentence').removeClass('hidden');
    		$('.too-many').addClass('hidden');
    		var multiplier = parseInt($('#product-amount').val());
	    	var tot = 0;
	    	var formTotal=0;
	    	var left = 0; 
	    	$('.amount-flex').each(function(){
	    		formTotal +=  parseInt($(this).val());
	    	});

	    	
	    	//assume we are on a detail page
			
	    	var package  = methods.lookupProduct($('.addtocart').attr('product-type'),0);

			if(package != null && package.products != null && package.products!=undefined){ 
		    	for(var i = 0; i < package.products.length; i++){
		    		if(package.products[i].containmentType=='flexibel aantal'){
			    		tot += (multiplier * parseInt(package.products[i].quantity));
		    		}
		    	}
		    	
		    	left = tot-formTotal;
		    	
		    	if(left < 0){
		    		$('.total-sentence').addClass('hidden');
		    		$('.too-many').removeClass('hidden');
					$('#left-calculation-2').html(-1*left);
		    	}
		    	
				$('#total-calculation').html(tot);
				$('#left-calculation').html(left);   
			}
	    },
	    calculateWeight : function(){
	    	weightOfDishes = 0;
		  	for(var i = 0; i < cartDataStore.length; i++){
		  		var product = cartDataStore[i];
		  		if(product.type == "package"){
		  			if(product.products != null) {
				  		for(var j = 0; j  < product.products.length; j++){
				  			if(product.products[j].dishWeightFactor != null && product.products[j].dishWeightFactor != undefined)
						  		weightOfDishes += product.products[j].dishWeightFactor * product.products[j].quantity;
				  		}
			  		}
		  		}
		  		else if (product.type == 'product') {
		  			if(product.dishWeightFactor != null && product.dishWeightFactor != undefined){
				  		weightOfDishes += product.quantity * product.dishWeightFactor;
		  			}
		  		}
		  	}  
		  	methods.logger("Weight of order calculated to be: "+weightOfDishes);		
		  	if(weightOfDishes > 0) {
			  	$('#numPersonsDishes').html(
			  		'<h4>U heeft reeds hapjes voor:</h4>'+
			  		'<ul class="calories-list">'+
			  		'<li><strong>Als hapje</strong> '+methods.getInfoButton('Als hapje',' Ideaal indien u een feestje geeft tussen 2 maaltijden door of laat op de avond pas hapjes wil serveren. Bv: tussen 15u en 17u of na 21u. ')+': '+parseInt(weightOfDishes/4)+' personen.</li>'+
			  		'<li><strong>Iets meer dan...</strong> '+methods.getInfoButton('Iets meer dan...',' Ideale hoeveelheid voor iets meer dan een hapje, een feestje van 14u tot 17u30 of indien u de hapjes serveert vanaf 20u. ')+': '+parseInt(weightOfDishes/5)+' personen.</li>'+
			  		'<li><strong>Maaltijd</strong> '+methods.getInfoButton('Volwaardige maaltijd','Indien uw gasten een volwaardige maaltijd verwachten is dit de aangewezen hoeveelheid. Bv: van 12u tot 15u of vanaf 17u tot 20u. ')+': '+parseInt(weightOfDishes/7)+' personen.</ul>'		  				  	
			  	); 

			  	$('.itooltip').popover(); 	
		  	}
		  	else {
			  	$('#numPersonsDishes').html("");  	
		  	}
		  	return weightOfDishes;
		  	
	    },
	    getInfoButton : function(title,text){
		 	return '<a data-content="'+text+'" rel="popover" data-trigger="hover" class="label label-info itooltip" href="#" data-original-title="'+title+'">info</a>';
	    },
	    
	    //returns number of spots left
	    checkFlexItemsTotals : function() {
	    	var tot = 0;
    		var multiplier = parseInt($('#product-amount').val());
	    	var formTotal=0;
	    	var left = 0; 
	    	$('.amount-flex').each(function(){
	    		formTotal += parseInt($(this).val());
	    	});
	    	
	    	//assume we are on a detail page
	    	
	    	
			var json = $('.addtocart').attr("productdata");
			
			var package = methods.lookupProduct($('.addtocart').attr('product-type'),0);

			if(package!=null && package.products != null && package.products!=undefined){ 
		    	for(var i = 0; i < package.products.length; i++){
		    		if(package.products[i].containmentType=='flexibel aantal'){
		    			//methods.logger("quantity: "+package.products[i].title +" "+package.products[i].quantity);
			    		tot += (multiplier * parseInt(package.products[i].quantity));
		    		}
		    	}
		    	
		    	left = tot-formTotal;
	    	}
	    	return left;
	    },
	    showValidationError : function(){
		    $('#validation-error').removeClass('hidden');
	    },	    
	    hideValidationError : function(){
			$('#validation-error').addClass('hidden');	    
	    },
	   	containsPackages : function()   {
	   		for(var i = 0; i < cartDataStore.length ; i++){
	    		if(cartDataStore[i].type == 'package'){
	    			return true;
	    		}
	    	}
	    	return false;
	   	},
	   	
	   	containsProducts : function() { 
	    	for(var i = 0; i < cartDataStore.length ; i++){
	    		if(cartDataStore[i].type == 'product'){
	    			return true;
	    		}
	    	}
	    	return false;
	   	},
	   	containsMaterials : function() { 
	    	for(var i = 0; i < cartDataStore.length ; i++){
	    		if(cartDataStore[i].type == 'material'){
	    			return true;
	    		}
	    	}
	    	return false;
	   	},	 
	   	lookupProduct: function(type, index){
	   		if(allProducts[type+'s'] == null)
		   		return null;
		   		
		   		
		   	return allProducts[type+'s'][index];
		   	
	   	},
	    render : function(){
	    	methods.logger("Rendering");
	    	var str;

	    	if(cartDataStore.length == 0){
   		    	methods.logger("Empty cart");

	    		str=methods.getTemplate("<li><p>Het winkelwagentje is leeg.<p></li>");
	    	
	    	}
	    	else {
	    	   	methods.logger("Non-empty cart");

		    	var packagesHtml="";
		    	var productsHtml="";
		    	var materialsHtml = "";	    	
		    	if(methods.containsPackages())  
					packagesHtml = '<li class="nav-header">Pakketten</li>';
		    	
		    	if(methods.containsProducts())
		    		productsHtml =  '<li class="nav-header">Losse gerechten</li>';
		    		
		    	if(methods.containsMaterials())
		    		materialsHtml =  '<li class="nav-header">Los materiaal</li>';
		    		
		    	quantumDiscountHtml = methods.getDiscountTableHTML();

		    	//loop over cartDataStore, and fill up all lists
		    	for(var i = 0; i < cartDataStore.length ; i++){
		    		var obj = cartDataStore[i];
					var removeclass = (obj.type == "package") ? 
										'packageid="'+obj.Package_id+'"': 
										obj.type == "product" ?
										'productid="'+obj.Product_id+'"' :
										'materialid="'+obj.Product_id+'"';
										
					var title = obj.title;					
					var selected_options_attr =''; 
					if(obj.type=='product'){
						methods.logger("obj:");
						methods.logger(obj);
						if(obj.options != null && obj.options != undefined){
							title += " (";
							if(obj.options.length > 0){
								selected_options_attr += 'selected_options="';
							}
							for(var j = 0; j < obj.options.length; j++){
								title += obj.options[j].ingredientName;
								selected_options_attr += obj.options[j].ingredients_id;
								if(j < obj.options.length-1) {
									title+= ', ';
									selected_options_attr+=',';
								}
							}
							if(obj.options.length > 0){
								selected_options_attr += '"';
							}
							
							title += ")";
						}
						
					}
					
		    		var html = 
		    				'<li class="product-row">'+
								 '<span class="quantity">'+obj.quantity+'x</span>'+
								 '<span class="product-name">'+title+'</span>'+
								 '<span class="product-remove"><a href="#" '+removeclass+' '+selected_options_attr+' class="removefromcart">&times;</a></span>'+
								 '<div style="clear: both"></div>'
							'</li>';

		    		//only add if price is > 0 for packages
		    		if(obj.type == "package" && obj.price > 0) {
		    			packagesHtml += html;    		
		    		}
		    		else if (obj.type == "product") {
	    				productsHtml += html;
		    		}
		    		else if (obj.type == "material"){
		    			materialsHtml += html;
		    		}
		    	} 
		    	str = methods.getTemplate(packagesHtml+productsHtml+materialsHtml+quantumDiscountHtml);
	    	}
	    	
	    	elt.html(str);
	    },
	    getTemplate : function(content ){
		    var str;
			if(settings.cartDisplayMode == "dropdown")    {
				str='<ul class="'+settings.cartClass+'">'+
						'<li class="divider-vertical"></li>'+
						'<li class="dropdown">'+
							'<a class="dropdown-toggle" data-toggle="dropdown" href="#" ><i class="icon-shopping-cart icon-white"></i> '+settings.cart_text+': € <span class="total-price"></span><b class="caret"></b></a>'+
							'<ul class="dropdown-menu">'+
								'<li><a href="'+settings.checkout_page+'">'+settings.checkout_link+' &rarr;</a></li>'+
								'<li class="divider"></li>'+
								content+
								'<li class="divider"></li>'+
								'<li><a href="#">Totaal: € <span class="total-price"></span></a></li>'+
							
							'</ul>'+
						'</li>'+
					'</ul>';	
			}
		    else if(settings.cartDisplayMode == "block"){
				str='<ul class="cart-block">'+
							content+
							'<li class="divider"></li>'+
							'<li>Totaal: € <span class="total-price"></span></li>'+
							'<li class="divider"></li>'+
							'<li><a href="'+settings.checkout_page+'">Afrekenen</a></li>'+
					'</ul>';				    
		    }
		    else 
		    	str="no_such_template";
		    return str;
	    },
	   getDiscountTableHTML : function() {
		    if(settings.discountTable != null && settings.discountTable.length > 0){
		       return '<li class="discount-table-discount-cart divider"></li>'+
		              "<li class='discount-table-discount-cart' style='margin-left: 15px;'>Quantumkorting: <span id='discount-table-discount'></span></li>";
		    }
		    else {
			    methods.logger("No Discount table");
			    return "";
		    }
	    },
	    calculateDiscountTableAndUpdateCart : function() {
            if(settings.discountTable != null && settings.discountTable != undefined  && settings.discountTable.length > 0){
	            for(var i = 0; i < settings.discountTable.length ; i++){
				    var cur = settings.discountTable[i];
				    if((totalInclVat >= parseFloat(cur.fromAmount) && (cur.toAmount == undefined || cur.toAmount == null)) ||
				    	totalInclVat >= parseFloat(cur.fromAmount) && totalInclVat < parseFloat(cur.toAmount)){
				    	var disc = parseFloat(cur.tableDiscountPercentage)*100; 
				    	if(disc > 0){
				    		var formattedDisc =methods.formatPercentage(disc)+"%";					    	
				    		$('#discount-table-row').removeClass('hidden');
					    	$('#discount-table-discount').html(formattedDisc);	
					    	$('.discount-table-field').html("<strong>"+formattedDisc+"</strong>");
					    	$('.discount-table-discount-cart').removeClass('hidden');

				    	}
				    	else {
					    	$('#discount-table-row').addClass('hidden');
					    	$('.discount-table-discount-cart').addClass('hidden');
				    	}
					    	
				    	return cur.tableDiscountPercentage;
				    }
				}
		    }
		    else {
		    	$('#discount-table-discount-cart').addClass('hidden');
				    	
		    	$('#discount-table-row').addClass('hidden');
		    	
		    }
		},
	    analyzeDeliveryOptions : function() { //assumes take away is allowed, checks the shopping cart and checks if there is a product/package with 'deliver only' set.
	      methods.logger("Analyzing cart... ");
		  var atleastOneTakeAwayOnly = false;
		  var atleastOneDeliveryOnly = false;
		  for(var i = 0; i < cartDataStore.length ; i++){
		  	var product = cartDataStore[i];
		  	 if(product.deliveryOptions == "alleen bezorgen"){
			  	 atleastOneDeliveryOnly = true;
		  	 }
		  	 else if(product.deliveryOptions == "alleen afhalen"){
			  	 atleastOneTakeAwayOnly == true;
		  	 }
		  }
		
		
		  var elt = $('#invalid-selection');
		  elt.addClass('hidden');
		  var error = false;
		  if($("#bezorgen").is(":checked") && settings.pickupAndDelivery){
		  		if(atleastOneTakeAwayOnly) {
					elt.removeClass('hidden').html('In uw bestelling zit minimaal één item dat alleen afgehaald kan worden, maar u heeft \'bezorgen\' aangevinkt.'+
					' Los dit op door dit item te verwijderen, of \'afhalen\' te kiezen.');
					error = true;
		  		}			  
		  }
		  else if($('#afhalen').is(":checked") && settings.pickupAndDelivery) { //afhalen is checked
				if(atleastOneDeliveryOnly) {
					elt.removeClass('hidden').html('In uw bestelling zit minimaal één item dat alleen bezorgd kan worden, maar u heeft \'afhalen\' aangevinkt.'+
					' Los dit op door dit item te verwijderen, of \'bezorgen\' te kiezen.');
					error = true;
				}			  
		  }
		  
		  methods.logger("analyzed, error? :"+error);

		  return !error;
	    },
	    
	    
	    //if productData is supplied, that will be used, otherwise, the productdata attr will be used.
	    //if productData parameter is used, it is assumed the quantity is also supplied in the object itself
	    addProduct : function (event, productData, shouldPersist) {
	    	if (shouldPersist == null){
		    	shouldPersist = true;
	    	}
	    	var quant=1;
	    	
	    	var product = null;
	    	if(productData == null || productData == undefined){
		       	var clicked = $(event.currentTarget);
				
				if(settings.detail){
					quant = parseInt($('#product-amount').val());
					

				}
				
				//passing things around by reference, so get the ref, and afterwards deepcopy it.
		    	productRef = /*eval('('+clicked.attr("productdata")+')');*/
		    			methods.lookupProduct(clicked.attr("product-type"), clicked.attr("product-index"));
		 
		    	//deepcopy it
		    	product = jQuery.extend(true, {}, productRef);

		    			

		    	if(!settings.detail && product.type=='product'){
			    	quant = parseInt(product.orderSize);
		    	}
		    	
		    	if(product.type=='product' && settings.detail){
		    		product = methods.addSelectedOptionsToProduct(product);
		    	
		    	}
		    	
		    	if(product.type=='product' && quant%product.orderSize != 0){
			    	$('.product-size-wrong').removeClass('hidden').html('Dit item dient u in een veelvoud van <b>'+product.orderSize+'</b> te bestellen.');
			    	return false;
		    	}
		    				 
				product.quantity = quant;
			
			}
			else { //used productData as input, ignore the event parameter, assume quantity is set in there
				product = productData;
			}
			

			//returns null if non-existent, and the obj from the cart it's equal to otherwise
			var existingProduct = methods.productExists(product); 
			
			//check if product exists in store
			if(existingProduct != null){ //get the current quantity 
				methods.logger("product exists");

				if(existingProduct.type == 'package'){
					methods.updateQuantityInPackage(product,existingProduct);
				}

				existingProduct.quantity = parseInt(existingProduct.quantity) + parseInt(product.quantity);				
/*				else { //material or product, just update the quantity
					existingProduct.quantity = parseInt(existingProduct.quantity) + parseInt(product.quantity);				
				}*/
			}
			else {
			   methods.logger("product does not exist");
			   if(cartDataStore == "EMPTY")
			   		cartDataStore = [];
			   
			   product = methods.multiplyProductsInPackageByX(quant, product);
			   cartDataStore.push(product);
			}
			methods.logger("cart: ");
			methods.logger(cartDataStore);
			
			if(shouldPersist){
				methods.persist();
			}
			
			//add the item to each cart visually
			cartPluginInstance.each(function(){
				methods.logger("calling render");
				methods.render($(this));			
			});
			
			return true;

	    },
	    addSelectedOptionsToProduct : function(product){
    		for(var i = 0; i < dishOptions.options.length ; i++){
	    		if(methods.optionIsSelected(parseInt(dishOptions.options[i].ingredients_id))){
	    			if(product.options == null || product.options == undefined){
		    			product.options = [];
	    			}
	    			
			    	product.options.push(dishOptions.options[i]);			    		
	    		}
    		}

	    	return product;
	    },
	    optionIsSelected : function(id){
	    	return $('#ingredientsid_'+id).is(':checked'); 	    
	    },
	    /* @pre product.type == 'package' */
	    updateQuantityInPackage : function(productToBeAdded, existingProduct){
	    	var quant = productToBeAdded.quantity;
		    //loop products
			if(existingProduct.products != null){
	    		for(var p = 0 ; p < existingProduct.products.length ; p++ ) {
	    			if(productToBeAdded.products != null){
		    			for(var j = 0; j < productToBeAdded.products.length; j++){
		    				if(productToBeAdded.products[j].Product_id == existingProduct.products[p].Product_id){
			    				existingProduct.products[p].quantity = 
			    					(productToBeAdded.products[j].quantity * quant) +
				    					parseInt(existingProduct.products[p].quantity);
		    				}
		    			}
	    			}
	    		}
    		}
    		//loop materials
    		if(existingProduct.materials != null){
	    		for(var p = 0 ; p < existingProduct.materials.length ; p++ ) {
	    			if(productToBeAdded.materials != null){ 
		    			for(var j = 0; j < productToBeAdded.materials.length; j++){
		    				if(productToBeAdded.materials[j].Product_id == existingProduct.materials[p].Product_id){
			    				existingProduct.materials[p].quantity = 
			    				(productToBeAdded.materials[j].quantity * quant) +
			    				parseInt(existingProduct.materials[p].quantity);
		    				}
		    			}
	    			}
	    		}
    		}	    
	    
	    },
	    multiplyProductsInPackageByX : function(quant, product){
	    	if(quant > 1 && product.type=="package"){	

			   		if(product.products != null){ //multiply by quant 
					   for(var i = 0; i < product.products.length; i++){
					   		//methods.logger(product.products[i].title+": "+product.products[i].quantity +" x "+quant)
					   		if(product.products[i].containmentType == 'flexibel aantal'){ //multiplication is already done, don't multiply
						   		product.products[i].quantity = parseInt(product.products[i].quantity);	
					   		}
					   		else {
						   		product.products[i].quantity = parseInt(product.products[i].quantity) * quant;
						   	}
					   }
			   		}
			   		
			   		if(product.materials != null){
					   for(var i = 0; i < product.materials.length; i++){
						   	product.materials[i].quantity = parseInt(product.materials[i].quantity) * quant;
					   }
			   		}

			}
			return product;	
	    
	    },
	    createAutoClosingAlert : function(selector, delay) {
	    	$(selector).html('<div class="the-alert alert alert-info fade in">' +
	    						 '<button data-dismiss="alert" class="close" type="button">×</button>' +
	    						 '<p>Product toegevoegd aan de bestelling.</p>' +
	    					 '</div>');
		    var alert = $('.the-alert').alert();
		    window.setTimeout(function() { alert.alert('close') }, delay);
   		},
	    addExtraProducts : function () { //only relevant for detail view where there are product extras shown
   			methods.logger("addExtraProducts");

	    	$('.amount-extra').each(function(){
	    		var quant = parseInt($(this).val());
	    		if(quant > 0){

	    			var pType = $(this).parent().attr("product-type");	    		
	    			var pIndex = $(this).parent().attr("product-index");
	    			var product = methods.lookupProduct(pType, pIndex); 
	    			
	    			var productClone = jQuery.extend(true, {}, product);//deepcopy
				
	    			productClone.quantity = parseInt(quant); 
	    			methods.addProduct(null, productClone,false);
	    		}
	    	});
	    	
   			methods.persist();

	    },	    	    
	    removeProduct : function (event) {
	    	methods.logger("Removing product");
	    	var clicked = $(event.currentTarget);
	    	
	    	var type;
	    	if(clicked.attr("packageid") != null)
	    		type = 'package';
	    	else if(clicked.attr("productid") != null)
	    		type = 'product';
	    	else if(clicked.attr("materialid") != null)
	    		type = 'material';
	    		    	
	       	var id;
	    	if(clicked.attr("packageid") != null)
	    		id = clicked.attr("packageid");
	    	else if(clicked.attr("productid") != null)
	    		id = clicked.attr("productid");
	    	else if(clicked.attr("materialid") != null)
	    		id = clicked.attr("materialid");
	    		    	
	    		
	    	methods.logger(type);
	    	methods.logger(id);	
	    	
	    	for(var i = 0; i < cartDataStore.length ; i++){
				var obj = cartDataStore[i];

	    		if((type == 'package' && id == obj.Package_id) ||
			       ((type == 'product' || type=='material') && id == obj.Product_id)) {
			       
			       	if(type=='product') { 
				       	//list of ingredients_id: selected_options="1,2,3,4", so we know which sub product we need to remove; the one with
				       	//namely the option id encoded in this attribute
				       	var selectedOptionsString = clicked.attr('selected_options');

				       	var selectedOptions = (selectedOptionsString == null || selectedOptionsString == undefined) 
				       									? [] : selectedOptionsString.split(',');
						var objLength = (obj.options == null || obj.options == undefined) ? 0 : obj.options.length;		
				       	if(selectedOptions.length == objLength) {
				       	    //pair-wise compare the id's  of the selectedOptions var with the obj.options, 
				       	    //to see if they ALL match. if so, remove product, else, don't continue;
				       		var equal = true;
					       	for(var j = 0; j < objLength; j++){
						       	if(!methods.containsIngredient(selectedOptions, obj.options[j])){
							       	equal = false;
						       	}
					       	}
					       	if(equal) {
						    	cartDataStore.splice(i,1);
						    	break;						       	
					       	} //else continue iterating over the cart
				       	}
			       	}
			       	else {
				    	cartDataStore.splice(i,1);
				    	break;
			       	}
			    }
	    	}
	    	
	    	methods.persist();

	    	var parentRow = clicked.parents('.product-row');
	    	parentRow.fadeOut();
	    },
	    containsIngredient : function(selectedOptions, optionObj){
		  	for(var i = 0; i < selectedOptions.length; i++){
			  	if(parseInt(optionObj.ingredients_id) == parseInt(selectedOptions[i])){
				  	return true;
			  	}
		  	}  
		  	return false;
	    },
	    calculateDeposit : function(){
	    	var deposit = 0;
			for(var i = 0; i < cartDataStore.length ; i++){
	    		var obj = cartDataStore[i];

	    		
	    		if(obj.type == "package"){
		    		if(obj.products != null && obj.products != undefined){
						for(var j = 0; j < obj.products.length; j++){
							var prod = obj.products[j];
							//products
							if(prod.orderSize == null || prod.orderSize == 0)
								prod.orderSize = 1;
							
				    		deposit+= (parseFloat(prod.deposit) * parseInt(prod.quantity)) / parseInt(prod.orderSize);							
						}		    		
		    		}
		    		if(obj.materials != null && obj.materials != undefined){
			    		for(var j=0; j< obj.materials.length; j++){
			    			var mat = obj.materials[j];
							//materials
				    		deposit+= (parseFloat(mat.deposit) * parseInt(mat.quantity));				    		
			    		}
		    		}

	    		}	
	    		else { //separate dishes and materials
	    			if(obj.orderSize == null || obj.orderSize == 0)
						obj.orderSize = 1;
	    		
		    		deposit += (parseFloat(obj.deposit) * parseInt(obj.quantity)) / parseInt(obj.orderSize);			
	    		}
	    	}
	    	methods.logger("total deposit: "+deposit);
	    	
	    	if(deposit > 0){
	    		$('.deposit-total').html(methods.formatEuro(deposit));
		    	$('.deposit-container').removeClass('hidden');
	    	}
	    
	    },
	    updatePrices : function(){
			var price = 0;
			vatMap = {};
	    	//loop over cartDataStore
	    	totalInclVat=0;
	    	methods.logger("updatePrices");
	    	methods.logger(cartDataStore);
	    	
	    	for(var i = 0; i < cartDataStore.length ; i++){
	    		var obj = cartDataStore[i];
	    		var currentPrice = 0;
	    		if(obj.type == 'product'){
		    		//add the extra price of the options.
		    		var p = parseFloat(obj.price);
		    		if(obj.options != null && obj.options != undefined){
			    		for(var j = 0 ; j < obj.options.length ; j++){
				    		p += parseFloat(obj.options[j].optionSalesPrice);
			    		}
		    		}
		    		currentPrice = p * parseInt(obj.quantity);
	    		}
	    		else {
		    		currentPrice = parseFloat(obj.price) * parseInt(obj.quantity);		    		
	    		}
	    		
	    		totalInclVat += currentPrice;

	    		
	    		//update vatMap
    			if(vatMap["x"+obj.VAT] == null || vatMap["x"+obj.VAT] == undefined){
	    			vatMap["x"+obj.VAT] = 0;
	    		}
	    		
	    		//vatMap["x"+obj.VAT] += (obj.quantity * obj.price * obj.VAT);
	    		methods.logger("updating VATMAP with: "+ parseFloat(obj.price));
	    		vatMap["x"+obj.VAT] += parseFloat(currentPrice);
	    		
	    	}

  			var delPrice = 0;
  			
  			
  			if($('#bezorgen').is(':checked') || $('.deliveryType').val() == 'bezorgen' ) {
		    	$('#not-enough-ordered').addClass('hidden');
  			
	  			if(!settings.deliveryFormula) { //if we are using the tabular form.
			    	//check in the settings.deliveryCosts what is the delivery price
			    	
			    	
			    	var doNotDeliver = true;
			    	var notEnoughOrdered = false;
					for(var i = 0; i < settings.deliveryCosts.length; i++){
						var min = parseInt(settings.deliveryCosts[i].minKm);
						var max = parseInt(settings.deliveryCosts[i].maxKm);	
						methods.logger(min+" "+max+" "+distance);
						if(min <= distance && distance < max) { //if distance is within this range
							if(totalInclVat < parseFloat(settings.deliveryCosts[i].minimumOrderPrice)){
								if(distance > 0){
									$('#not-enough-ordered').removeClass('hidden').html(
									'We bezorgen op deze afstand ('+ 
													methods.formatEuro(distance)+' km) vanaf een bedrag van €'+
													methods.formatEuro(parseFloat(settings.deliveryCosts[i].minimumOrderPrice)));
									doNotDeliver=true;
									notEnoughOrdered = true;
								    //hide submit buttons
									$('.submit-controls').addClass('disabled');
								}
								break;
							}
							else {
								//update the table of the checkout 
								deliveryCosts.price = parseFloat(settings.deliveryCosts[i].price);
								$('.submit-controls').removeClass('disabled');
								$('#not-enough-ordered').addClass('hidden');
								$('.deliverycosts-field').html("<strong>€ "+methods.formatEuro(delPrice)+"</strong>");
								doNotDeliver = false;						
							}
						}
					}
				/*
					if(doNotDeliver && !notEnoughOrdered){
						methods.logger("DO NOT DELIVER, OUT OF RANGE");
						$('#not-enough-ordered').removeClass('hidden').html(
							'We bezorgen helaas niet op deze afstand.');
						doNotDeliver=true;
					    //hide submit buttons
						$('.submit-controls').addClass('disabled');
					}*/
				} 
				else { //use the formula, which uses a different calculation structure.
		
				
					var delivery = settings.deliveryCosts[0];
					methods.logger("delivery object");
					methods.logger(delivery);
					
					var delCosts = 0;
					
					var minOrderPrice = 0;
	
					if(distance * parseFloat(delivery.minOrderPricePerKm) < delivery.absoluteMinOrderPrice) 
						minOrderPrice = delivery.absoluteMinOrderPrice;
					else
						minOrderPrice = distance * parseFloat(delivery.minOrderPricePerKm); 
					
					var freeDelivery = 0; //euro amount to be ordered for free delivery
					
					
					delCosts = distance * parseFloat(delivery.pricePerKm);


					
					if(delivery.useMultiplierFreeDelivery){
						freeDelivery = parseFloat(delivery.deliveryFreeMultiplier) * parseFloat(minOrderPrice);
					}
					else {
						freeDelivery = parseFloat(delivery.deliveryFreeAmount);
					}
					
					if(delivery.absoluteMaxDistance == null)
						delivery.absoluteMaxDistance=500;
						
					if(distance > delivery.absoluteMaxDistance){
						$('.submit-controls').addClass('disabled');
						$('#not-enough-ordered').removeClass('hidden').html('We bezorgen helaas niet op deze afstand.');
					    delCosts = 0;					
					}
					else { //within reach, now check for
						if(totalInclVat > freeDelivery) { //delcosts = 0, since someone ordered enough
							methods.logger("free delivery, since total > freedelivery threshold");
							delCosts = 0;
						}
					
						if(minOrderPrice > totalInclVat){
							if(distance > 0) {
								$('#not-enough-ordered')
									.removeClass('hidden')
									.html(
										'<strong>Let op:</strong> We bezorgen op deze afstand ('+ 
														methods.formatEuro(distance)+' km) vanaf een bestelbedrag van €'+
														methods.formatEuro(minOrderPrice));
								doNotDeliver=true;
							    //hide submit buttons
							    delCosts = 0;
						    }
							$('.submit-controls').addClass('disabled');						
						}
						else {
	
							$('#not-enough-ordered').addClass('hidden').html('');
							doNotDeliver=false;
						    //show submit buttons
							$('.submit-controls').removeClass('disabled');
	
						}
					}
					if(parseFloat(delCosts) < 0)
						delCosts=  0;
					
					deliveryCosts.price = parseFloat(delCosts);
				}  
			
			} 
			else {//afhalen/pickup, delivery costs is zero
				$('#not-enough-ordered').addClass('hidden').html('');;
				$('.submit-controls').removeClass('disabled');
				deliveryCosts.price = 0;
			}	
	    	
	    	
	    	
	    	if(!methods.analyzeDeliveryOptions()){
		    	$('.submit-controls').addClass('disabled');
	    	}
	    	
	    	if(vatMap["x0.21"] == null || vatMap["x0.21"] == undefined)
	    		vatMap["x0.21"] = 0;
	    		
  			vatMap["x0.21"] += parseFloat(deliveryCosts.price);
  			methods.logger(deliveryCosts)
	    	methods.logger("deliveryCosts.price: " + deliveryCosts.price);
	    	totalInclVat += parseFloat(deliveryCosts.price);
	    	
	    	
	    	if(settings.pricesAreInclVat){
		    	totalExclVat = totalInclVat;
		    	methods.logger(vatMap);
		    	for(var perc in vatMap){
		    	    var p = parseFloat(perc.substr(1));
		    		totalExclVat -= parseFloat(vatMap[perc]) - (parseFloat(vatMap[perc]) / (1+p));
		    		if(totalInclVat == 0){
						vatMap[perc] = 0;	    		
		    		}
		    		else {
						vatMap[perc]=parseFloat(vatMap[perc]) - (parseFloat(vatMap[perc]) / (1+p));
					}
			    }
		    }
		    else {
			    totalExclVat = totalInclVat;
			    
			    //modify totalInclVat to be the REAL totalInclVat, by adding the vatmap's contents
			    methods.logger(vatMap);
		    	for(var perc in vatMap){
		    	    var p = parseFloat(perc.substr(1));
		    		totalInclVat += parseFloat(vatMap[perc] * p);
		    		if(totalInclVat == 0){
						vatMap[perc] = 0;	    		
		    		}
		    		else {
						vatMap[perc]=parseFloat(vatMap[perc] * p);
					}
			    }			    

		    }
	    	methods.logger('setting price: '+methods.formatEuro(totalInclVat));
			
			var discountTableDiscount = methods.calculateDiscountTableAndUpdateCart();
			
			if(discountTableDiscount == null || discountTableDiscount == undefined)
				discountTableDiscount = 0.0;
			
			var tot = 0;
			if(settings.pricesAreInclVat){
				tot = totalInclVat;
			}
			else {
				tot = totalExclVat;
			}
			
	    	$('.total-price').html(methods.formatEuro(tot * (1 - discountTableDiscount)));

				
			if(totalInclVat == 0) {
				totalExclVat = 0;
			}
			
			$('.deliverycosts-field').html("<strong>€ "+methods.formatEuro(deliveryCosts.price)+"</strong>");
			$('.subtotal-field').html("<strong>€ "+methods.formatEuro(totalExclVat)+"</strong>");
			
			

			
			$('.discount-field').html("<strong>"+parseInt(discount)+"%</strong>");
			
			var totalWithCouponDiscount = 0;
			var totalWithCouponDiscountExcl = 0;
			if(discount > 0 && couponType == "normaal" || couponType == null || couponType == undefined){
				$('#discount-row').removeClass('hidden');
				totalWithCouponDiscount = totalInclVat * (1 - (parseInt(discount) / 100));
			}
			else {
				$('#discount-row').addClass('hidden');
				totalWithCouponDiscount = totalInclVat;
			}					
			//checkout page field
			$('.total-field').html("<strong>€ "+methods.formatEuro(totalWithCouponDiscount * (1-discountTableDiscount))+"</strong>");	

			
			
			for(var perc in vatMap){					
				var sel = String(perc).replace('.','_');
				$('.vat-field-'+sel).html('<strong>€ '+methods.formatEuro(vatMap[perc])+'</strong>');
			}
	    },	
	    clearCart : function(){
	    	methods.logger("Clearing cart!");
	    	cartDataStore = [];
	    	methods.persist();
	    	methods.render();
	    	methods.updatePrices();
	    },    
	    removeProductFromCheckoutPage : function(event){
	    	methods.logger("removeProductFromCheckoutPage");
			event.preventDefault();
 	    	var clicked = $(event.currentTarget);
 	    	

			var type;
	    	if(clicked.attr("packageid") != null)
	    		type = 'package';
	    	else if(clicked.attr("productid") != null)
	    		type = 'product';
	    	else if(clicked.attr("materialid") != null)
	    		type = 'material';
	    		    	
	       	var id;
	    	if(clicked.attr("packageid") != null)
	    		id = clicked.attr("packageid");
	    	else if(clicked.attr("productid") != null)
	    		id = clicked.attr("productid");
	    	else if(clicked.attr("materialid") != null)
	    		id = clicked.attr("materialid");

	    	
	    	var parentRow = clicked.parent().parent();

			parentRow.addClass('hidden');			
	    },
	    updateCartTotalPrice : function(){
	    	var price = 0;

	    	//loop over cartDataStore
	    	for(var i = 0; i < cartDataStore.length ; i++){
	    		var x = cartDataStore[i];
	    		price+= x.price * x.quantity;
	    	}
	    	
	    	
	    	totalInclVat = price;
	    	totalInclVat += deliveryCosts.price;
	    	
	    	
	    	
	    	methods.logger('setting price: '+methods.formatEuro(price));
	    	var tableDiscount = methods.calculateDiscountTableAndUpdateCart();
	    	methods.logger("TABLE DISCOUNT " +tableDiscount);
	    	var discountFactor = 1 - (tableDiscount + (parseFloat(discount) / 100.0));
	    	methods.logger("DISCOUNT FACTOR " + discountFactor);
	    	
	    	var tot = settings.pricesAreInclVat ? totalInclVat : totalExclVat;
	    	
	    	
	    	var t = tot * discountFactor;
	    	
	 
	    	
	    	methods.logger("TOTAL "+t);
	    	$('.total-price').html(methods.formatEuro(t));

	    },
	    /* returns null if product doesnt exist in cart, and returns the product from the cart that is equal to the passed in object otherwise */
	    productExists : function(product){
	    	for(var i = 0; i < cartDataStore.length ; i++){
	    	   var obj = cartDataStore[i];
	    	   if((product.type == 'package' 
		    		 && product.Package_id == obj.Package_id) ||
		    	  	product.type=='material'
		    		 && product.Product_id == obj.Product_id
		    	   ) {
		    		return obj;
		    	}
		    	else if (product.type=='product' 
		    		 && product.Product_id == obj.Product_id){
			    	 //if it is a product, first check if there is an extra option configuration, AND one with this option config does not exist.
			    	if(methods.checkDishOptionsAreEqual(product, obj))
			    		return obj;
			    	//else continue to loop the cart
			    	
		    	}
	    	}
	    	return null;
	    },
	    checkDishOptionsAreEqual : function(product1, product2){
		    var options1 = product1.options;
		    var options2 = product2.options;
		    
		    if(options1 == null && options2 == null)
		    	return true;
		    	
		    if((options1 != null && options2 == null) || (options1 == null && options2 != null))
		    	return false;
		    
		    if(options1.length != options2.length) return false;
		    	
		    //both options1 and 2 are not null and have same length;
		    for(var i = 0; i < options1.length; i++){
		    	var checking = options1[i].ingredients_id;
		    	found = false;
			    for(var j = 0; j < options2.length; j++){
				   if(checking == options2[i].ingredients_id) {
				   		found = true;
				   		break;
				   }
			    }
			    if(!found) return false;
		    }
			//if we reach this, they are equal		    
		    return true;
		    
	    },
		updateProductDataInDOM : function(event){
			//updates the productdata attribute of the clicked ADD TO CART 
			//button with the latest settings from the configuration form. 
			//only applies to detail pages where there are more configuration options.
			var clicked = $(event.currentTarget);
			//methods.logger(clicked.attr("productdata"));
	    	var product = //eval('('+clicked.attr("productdata")+')');
	    				methods.lookupProduct(clicked.attr("product-type"), clicked.attr("product-index"));

	    	var q = $('#product-amount').val();
			product.quantity = parseInt(q);
			
			
			
			$('.amount-flex').each(function(){
				var amount = parseInt($(this).val());
				var classes =  $(this).attr('class');
				var pieces = classes.split(" ");
				for(var i = 0; i < pieces.length ; i++){
					if(pieces[i].substr(0,13) == 'flex-product-'){
						var pieces2= pieces[i].split("-");
						
						var id = pieces2[pieces2.length-1];
						
						//loop through product and update the amounts to reflect the amount from the form
						for(var j = 0; j < product.products.length; j++){
							if(product.products[j].Product_id == id){
								product.products[j].quantity = amount;
							}
						}
					}
				}
				
			});
			methods.logger(product);
		//	var product2 = methods.deepCopy(product);
		//	methods.logger("prod2");
		//	methods.logger(product2);
			var jsonString = $.toJSON(product);
			//var jsonString = jsDump.parse(product);
			
			clicked.attr("productdata", jsonString); 
		},
		
		validateForm : function(){
			$('#validation-error').addClass('hidden');			    
			var left = methods.checkFlexItemsTotals();
			if(left < 0){
			 	$('#validation-error').removeClass('hidden');
				return false;
			}
			
			
			return true;
		},
	    //binds all the buttons to the respective event
	    bindButtons : function(){
	    	 $('body').on("change.shoppingCart",".deliveryType", function(event){
	    	     methods.updatePrices();
 		    	 methods.calculateDeposit();
	    	 });
			 $("body").on("click.shoppingCart","a.removefromcart", function(event){
		    	event.preventDefault();
		    	methods.removeProduct(event);
		    	methods.updateCartTotalPrice();
    			methods.updatePrices();
		    	methods.calculateWeight();
		    	methods.calculateDeposit();
				event.stopPropagation();
		    });
		     $("body").on('click.shoppingCart', 'a.removefromcart-checkout', function(event){
		    	methods.removeProduct(event);
		    	methods.removeProductFromCheckoutPage(event);

    			methods.updatePrices();
    			methods.calculateWeight();
		    	methods.calculateDeposit();		    	
		    });	 
	    
	    	if(settings.detail){
			    $('.addtocart').on('click.shoppingCart', function(event){
			    	event.preventDefault();
		         	$('.product-size-wrong').addClass('hidden');
		         	$('.product-added').addClass('hidden');
		         	
				    if(!methods.validateForm())
				    	return;
				    
					methods.updateProductDataInDOM(event);
									    	
				    var b = methods.addProduct(event);
		    		if(b){
					    methods.addExtraProducts(event); //aanvullingen
					    methods.updatePrices();
					    methods.calculateWeight();
					    methods.calculateDeposit();
					    $('.product-added').removeClass('hidden');
				    }
				    
			   });
	    	}
	    	else {
			    $('.addtocart').on('click.shoppingCart', function(event){
			    	methods.logger("Adding product");
			    	event.preventDefault();
				
				    if(!methods.validateForm()){
				    	methods.logger("Invalid form, returning");
				    	return;
				    }
				    			    	
				    var b = methods.addProduct(event);
				    if(b){
						methods.updatePrices();		
						methods.calculateWeight();		
						methods.calculateDeposit();
						methods.createAutoClosingAlert('.product-added-popup',2000);
					}
			    });
		    }
		    	
		    $('.address-line, .address-line-elsewhere').bind('change.shoppingCart', function(){
		    	var compareToAddress = '';
		    	var compareToAddress2="";
		    	
		    	$('.address-line').each(function(){
		    		compareToAddress += " "+$(this).val();
		    	});
		    	$('.address-line-elsewhere').each(function(){
		    		if($(this).val() != ""){
				    	compareToAddress2 += " "+$(this).val();	
			    	}
		    	})

		    	if(compareToAddress2.length > 0 && $('#deliveryElsewhere').is(':checked')){
			    	compareToAddress = compareToAddress2;
			    	methods.logger("Distance calc: Using address 'elsewhere'");
			    }
			    else {
				    methods.logger("Distance calc: Using normal delivery address");
			    }
			    if(methods.allAddressFieldsFilledOut()){
			    	methods.calculateDistance(compareToAddress, function(){
			   			methods.updatePrices();		    	
			    	});
		    	}
		    	else {
					distance = 0;
					methods.updatePrices();							    
				}
		    });	   
		    
		    
		    $('.amount-flex').bind('change.shoppingCart',function(){
		    	methods.updateFlexibleItemsTotals();
		    	methods.validateForm();
		    	
		    });
		    
		    $('#product-amount').bind('change.shoppingCart',function(){
		    	methods.updateFlexibleItemsTotals();
		    	methods.validateForm();
		    });
		    
		    $('#coupon').bind('change.shoppingCart', function(){
		    	methods.checkCoupon(function(){
		    		methods.updatePrices();
		    	});
		    });
		    
	    },
	    checkCoupon : function(callback){
			$('#discount-text').html('Controleren couponcode…').addClass('hidden');
			if($('#coupon').val() == null || $('#coupon').val() == "" || $('#coupon').val() == undefined)
				return;
	
			$('#discount-text').html('Controleren couponcode…').removeClass('hidden');
			
			$.ajax({
				url: couponUrl,
				data: { "hostname" : hostname , "couponCode" : $('#coupon').val()},
				success: function (jsonObj, textStatus, jqXHR){

					discount = jsonObj.discount;
					couponType = jsonObj.couponType;
					if(discount == 0){
						$('#discount-text')
							.removeClass('hidden')
							.html('Dit is geen geldige couponcode.')
							.addClass('alert-error');
					}
					else {
						if(couponType == "normaal" || couponType == null || couponType == undefined) {
							$('#discount-text').html('Couponcode geldig, u krijgt '+discount+'% korting.');
						}
						if(couponType == "sponsorcoupon") {
							$('#discount-text').html('Couponcode geldig, '+discount+'% van het totale bestelbedrag zal aan uw vereniging worden gesponsord.');
						}

						$('#discount-text').removeClass('alert-error')
											.addClass('alert-success')
											.removeClass('hidden');

						
					}
					
					if(callback!=null && callback!=undefined)
						callback(discount, couponType);
				},
				dataType: 'jsonp'
			});		
		},
	    calculateDistance : function(cptaddr, callback) {
	    	//calc distance between store and cptaddr (compare to address)
	    	var queryData = {
			  origin: settings.address,
			  destination: cptaddr,
			  travelMode: google.maps.TravelMode.DRIVING,
			  unitSystem: google.maps.UnitSystem.METRIC,
			  region: settings.region
			}
					
			var directionsService = new google.maps.DirectionsService();
			distance = -1;
	        directionsService.route(queryData, function(response, status) {
	            if (status == google.maps.DirectionsStatus.OK) {
	            	distance = parseInt(response.routes[0].legs[0].distance.value) / 1000;
	            	methods.logger("Distance found: "+distance+" km");
	            	
	            }
				else {
					methods.logger("Something went wrong, or address not found: "+status)
					methods.logger(response);
				}            
   	           	if(callback != null && callback != undefined)
	            	callback.call(distance);

			});			
	    	
	    	
  			
	    },
	  	setDiscount : function(disc){
			discount = disc; 
			methods.updateCartTotalPrice();
			if(disc == 0 || couponType != "normaal")
				$('#discount-row').addClass('hidden');
			else {	
				$('#discount-row').removeClass('hidden');
			}

			$('.discount-field').html('<strong>'+disc+'%</strong>');
			$('.total-field').html("<strong>€ "+methods.formatEuro(totalInclVat * (1 - (discount / 100)))+"</strong>");						
			
	  	},
	    //saves the current data store object in cookie
	    persist : function(){
	    	/*
	    	var exDate=new Date();
			exDate.setTime(exDate.getTime()+ 1000*60*60*24); //24 hours
			//exDate.setUTCMilliSeconds(999); //todo check timezone
			
			var jsonString = $.toJSON(cartDataStore);
			methods.logger("Persisting jsonString: ");
			methods.logger(jsonString);
			var c_value=escape(jsonString) + "; expires="+exDate.toUTCString()+'; path=/';
			document.cookie="shoppingCart" + "=" + c_value;	
			*/
			
			//var js = $.toJSON(cartDataStore);
			if(cartDataStore.length == 0){
				cartDataStore="EMPTY";
			}

			$.ajax({
				url : settings.session_url,
				type: 'POST',
				data: {"shoppingCart" : cartDataStore},
				success: function (jsonObj, textStatus, jqXHR){
					methods.logger("Persisted: ")
					methods.logger(jsonObj);
				},
				dataType: 'json'
			});
			
	    },
	   
	    //loads the state from cookie, and returns an object with the contents of the shopping cart
	    load : function(callback){
		  /*var i,x,y,ARRcookies=document.cookie.split(";");
		  for (i=0; i<ARRcookies.length; i++) {
			  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
			  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
			  x=x.replace(/^\s+|\s+$/g,"");
			  if(x == "shoppingCart"){
		      	return eval('('+unescape(y)+')'); //return the stored json object
		      }
		
		  }
		  return [];*/
		  $.ajax({
				url: settings.session_url,
				type: 'GET',
				data: {"action" : "load"},
				success: function (jsonObj, textStatus, jqXHR){
					methods.logger("Loaded: ");
					methods.logger(jsonObj);
					callback.call(this, jsonObj);
				},
				dataType: 'json'
			});
	    },
	    logger : function(msg) {
			if (window.console) console.log(msg);
		},
	    formatEuro : function(price){
			Number.prototype.formatMoney = function(c, d, t){
			var n = this, c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : 
					d, t = t == undefined ? "." : 
					t, s = n < 0 ? "-" : 
					"", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
					j = (j = i.length) > 3 ? j % 3 : 0;
					
			   return s + (j ? i.substr(0, j) + t : "") 
					    + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t)
					    + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
			};
		
			return price.formatMoney(2,',','.');
		},
		formatPercentage : function(price){
			Number.prototype.formatPercentage = function(c, d, t){
			var n = this, c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : 
					d, t = t == undefined ? "." : 
					t, s = n < 0 ? "-" : 
					"", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
					j = (j = i.length) > 3 ? j % 3 : 0;
					
			   return s + (j ? i.substr(0, j) + t : "") 
					    + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t)
					    + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
			};
		
			return price.formatPercentage(0,',','.');
		}
	};

	$.fn.shoppingCart = function( method ) {
		
		//the 'this' keyword is a jQuery object
	    // Method calling logic
	    if ( methods[method] ) {
	      return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	    } else if ( typeof method === 'object' || ! method ) {
	      return methods.init.apply( this, arguments );
	    } else {
	      $.error( 'Method ' +  method + ' does not exist on jQuery.shoppingCart' );
	    }    
	};
})( jQuery );