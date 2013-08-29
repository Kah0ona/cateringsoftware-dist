/**
* JQuery plugin that acts as a shopping cart 
* Author: Marten Sytema (marten@sytematic.nl)
* Plugin dependencies: 
* - JQuery-JSON: http://code.google.com/p/jquery-json/
* - Google Maps JS Api
* Version: 1.0
*/
;(function( $, window, document, undefined  ) {
	var cartPluginInstance = null;
	var totalExclVat=0;
	var totalInclVat=0;
	var vatMap = {};
	var discount = 0;
	var distance = 0;
	var	couponType = "";
	var elt;//the div to render everything on.
	var deliveryCosts = {"price": 0}; //object with details about the delivery costs. based on address user filled out in checkout form.
	var weightOfDishes = 0; //a number indicating how 'heavy' this order is. Used in calculating how many people this order is sufficient for.
	
	var pluginName = "shoppingCart",
		defaults = {
	      'detail' : false, //is this a detail page?
	      'address' : 'some address', // used for google maps distance calculation
	      'region': 'nl',
	      'deliveryCosts' : [],
	      'deliveryFormula' : false,//true is table, false is the other one
	      'checkout_page' : '/checkout',
	      'checkout_link' : 'Aanvraag versturen',
	      'cart_text' : 'Mijn bestelling',
	      'pickupAndDelivery' : false //false meanse: only delivery
	    };	
	
	function ShoppingcartPlugin(element, options){
		this.element = element;
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}
	
	ShoppingcartPlugin.prototype = {
		//initializes the plugin
	    init : function( options ) { 
	    	this.bindButtons();
	    	elt = $(this);
			var self = this;
			this.cartDataStore = [];

	    	this.load(function(jsonObj){
				this.cartDataStore = jsonObj;
			
		    	self.render();
		    	self.updatePrices();
		    	self.calculateWeight();
		    	self.calculateDeposit();
		    	var compareToAddress = '';
		    	$('.address-line').each(function(){
		    		compareToAddress += " "+$(this).val();
		    	});		    	
		    	
		    	if(self.allAddressFieldsFilledOut()){
			    	self.calculateDistance(compareToAddress, function(){
				    	self.updatePrices();		
				    });		    	
			    }
			    else{
				    distance = 0;
				    self.updatePrices();							    
			    }
			    
		    	self.checkCoupon(function(disc, couponType){
			    	self.updatePrices();
		    	});		    	
		    	
	     	    //if we are on a detail page, do the basic calculation of the totals of the flexible items of packages
	     	    if(self.settings.detail) {
				    self.updateFlexibleItemsTotals();
				}
	    	});
	    },
	    
	    allAddressFieldsFilledOut : function() {
 	        var ret = true;
 	        var deliveryElseWhere = $('#deliveryElsewhere').is(':checked');
 	        var self = this;
		    $('.address-line').each(function(){
		    	var x = $(this).val();
		    	if(deliveryElseWhere){
			    	if($(this).attr('id').indexOf("delivery") !== -1){
				    	if(x === undefined || x === null || x == "") {
				    		self.logger("Not all address fields set");
					    	ret = false;
				    	}
			    	}
		    	}
		    	else {
		    		if($(this).attr('id').indexOf("delivery") === -1){
				    	if(x === undefined || x === null || x == "") {
				    		self.logger("Not all address fields set");
					    	ret = false;
				    	}
			    	}
		    	}
			});
			
			if(ret){
	    		this.logger("All address fields set");			 
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
			
	    	var package  = this.lookupProduct($('.addtocart').attr('product-type'),0);

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
		  	for(var i = 0; i < this.cartDataStore.length; i++){
		  		var product = this.cartDataStore[i];
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
		  	this.logger("Weight of order calculated to be: "+weightOfDishes);		
		  	if(weightOfDishes > 0) {
			  	$('#numPersonsDishes').html(
			  		'<h4>U heeft reeds hapjes voor:</h4>'+
			  		'<ul class="calories-list">'+
			  		'<li><strong>Als hapje</strong> '+this.getInfoButton('Als hapje',' Ideaal indien u een feestje geeft tussen 2 maaltijden door of laat op de avond pas hapjes wil serveren. Bv: tussen 15u en 17u of na 21u. ')+': '+parseInt(weightOfDishes/4)+' personen.</li>'+
			  		'<li><strong>Iets meer dan...</strong> '+this.getInfoButton('Iets meer dan...',' Ideale hoeveelheid voor iets meer dan een hapje, een feestje van 14u tot 17u30 of indien u de hapjes serveert vanaf 20u. ')+': '+parseInt(weightOfDishes/5)+' personen.</li>'+
			  		'<li><strong>Maaltijd</strong> '+this.getInfoButton('Volwaardige maaltijd','Indien uw gasten een volwaardige maaltijd verwachten is dit de aangewezen hoeveelheid. Bv: van 12u tot 15u of vanaf 17u tot 20u. ')+': '+parseInt(weightOfDishes/7)+' personen.</ul>'		  				  	
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
			
			var package = this.lookupProduct($('.addtocart').attr('product-type'),0);

			if(package!=null && package.products != null && package.products!=undefined){ 
		    	for(var i = 0; i < package.products.length; i++){
		    		if(package.products[i].containmentType=='flexibel aantal'){
		    			//this.logger("quantity: "+package.products[i].title +" "+package.products[i].quantity);
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
	   		for(var i = 0; i < this.cartDataStore.length ; i++){
	    		if(this.cartDataStore[i].type == 'package'){
	    			return true;
	    		}
	    	}
	    	return false;
	   	},
	   	
	   	containsProducts : function() { 
	    	for(var i = 0; i < this.cartDataStore.length ; i++){
	    		if(this.cartDataStore[i].type == 'product'){
	    			return true;
	    		}
	    	}
	    	return false;
	   	},
	   	containsMaterials : function() { 
	    	for(var i = 0; i < this.cartDataStore.length ; i++){
	    		if(this.cartDataStore[i].type == 'material'){
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
	    	this.logger("Rendering");
	    	var str;

	    	if(this.cartDataStore.length == 0){
   		    	this.logger("Empty cart");
	    		str=this.getTemplate("<li><p>Het winkelwagentje is leeg.<p></li>");
	    	}
	    	else {
	    	   	this.logger("Non-empty cart");

		    	var packagesHtml="";
		    	var productsHtml="";
		    	var materialsHtml = "";	    	
		    	if(this.containsPackages())  
					packagesHtml = '<li class="nav-header">Pakketten</li>';
		    	
		    	if(this.containsProducts())
		    		productsHtml =  '<li class="nav-header">Losse gerechten</li>';
		    		
		    	if(this.containsMaterials())
		    		materialsHtml =  '<li class="nav-header">Los materiaal</li>';
		    		
		    	quantumDiscountHtml = this.getDiscountTableHTML();

		    	//loop over cartDataStore, and fill up all lists
		    	for(var i = 0; i < this.cartDataStore.length ; i++){
		    		var obj = this.cartDataStore[i];
					var removeclass = (obj.type == "package") ? 
										'packageid="'+obj.Package_id+'"': 
										obj.type == "product" ?
										'productid="'+obj.Product_id+'"' :
										'materialid="'+obj.Product_id+'"';
										
					var title = obj.title;					
					var selected_options_attr =''; 
					if(obj.type=='product'){
						this.logger("obj:");
						this.logger(obj);
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
		    	str = this.getTemplate(packagesHtml+productsHtml+materialsHtml+quantumDiscountHtml);
	    	}
	    	
	    	$(this.element).html(str);
	    },
	    getTemplate : function(content ){
		    var str;
			if(this.settings.cartDisplayMode == "dropdown")    {
				str='<ul class="'+this.settings.cartClass+'">'+
						'<li class="divider-vertical"></li>'+
						'<li class="dropdown">'+
							'<a class="dropdown-toggle" data-toggle="dropdown" href="#" ><i class="icon-shopping-cart icon-white"></i> '+this.settings.cart_text+': € <span class="total-price"></span><b class="caret"></b></a>'+
							'<ul class="dropdown-menu">'+
								'<li><a href="'+this.settings.checkout_page+'">'+this.settings.checkout_link+' &rarr;</a></li>'+
								'<li class="divider"></li>'+
								content+
								'<li class="divider"></li>'+
								'<li><a href="#">Totaal: € <span class="total-price"></span></a></li>'+
							
							'</ul>'+
						'</li>'+
					'</ul>';	
			}
		    else if(this.settings.cartDisplayMode == "block"){
				str='<ul class="cart-block">'+
							content+
							'<li class="divider"></li>'+
							'<li>Totaal: € <span class="total-price"></span></li>'+
							'<li class="divider"></li>'+
							'<li><a href="'+this.settings.checkout_page+'">Afrekenen</a></li>'+
					'</ul>';				    
		    }
		    else 
		    	str="no_such_template";
		    return str;
	    },
	   getDiscountTableHTML : function() {
		    if(this.settings.discountTable != null && this.settings.discountTable.length > 0){
		       return '<li class="discount-table-discount-cart divider"></li>'+
		              "<li class='discount-table-discount-cart' style='margin-left: 15px;'>Quantumkorting: <span id='discount-table-discount'></span></li>";
		    }
		    else {
			    this.logger("No Discount table");
			    return "";
		    }
	    },
	    calculateDiscountTableAndUpdateCart : function() {
            if(this.settings.discountTable != null && this.settings.discountTable != undefined  && this.settings.discountTable.length > 0){
	            for(var i = 0; i < this.settings.discountTable.length ; i++){
				    var cur = this.settings.discountTable[i];
				    if((totalInclVat >= parseFloat(cur.fromAmount) && (cur.toAmount == undefined || cur.toAmount == null)) ||
				    	totalInclVat >= parseFloat(cur.fromAmount) && totalInclVat < parseFloat(cur.toAmount)){
				    	var disc = parseFloat(cur.tableDiscountPercentage)*100; 
				    	if(disc > 0){
				    		var formattedDisc =this.formatPercentage(disc)+"%";					    	
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
	      this.logger("Analyzing cart... ");
		  var atleastOneTakeAwayOnly = false;
		  var atleastOneDeliveryOnly = false;
		  for(var i = 0; i < this.cartDataStore.length ; i++){
		  	var product = this.cartDataStore[i];
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
		  if($("#bezorgen").is(":checked") && this.settings.pickupAndDelivery){
		  		if(atleastOneTakeAwayOnly) {
					elt.removeClass('hidden').html('In uw bestelling zit minimaal één item dat alleen afgehaald kan worden, maar u heeft \'bezorgen\' aangevinkt.'+
					' Los dit op door dit item te verwijderen, of \'afhalen\' te kiezen.');
					error = true;
		  		}			  
		  }
		  else if($('#afhalen').is(":checked") && this.settings.pickupAndDelivery) { //afhalen is checked
				if(atleastOneDeliveryOnly) {
					elt.removeClass('hidden').html('In uw bestelling zit minimaal één item dat alleen bezorgd kan worden, maar u heeft \'afhalen\' aangevinkt.'+
					' Los dit op door dit item te verwijderen, of \'bezorgen\' te kiezen.');
					error = true;
				}			  
		  }
		  
		  this.logger("analyzed, error? :"+error);

		  return !error;
	    },
	    
	    
	    //if productData is supplied, that will be used, otherwise, the productdata attr will be used.
	    //if productData parameter is used, it is assumed the quantity is also supplied in the object itself
	    //warn: does not call this.persist, do this yourslef afterwards
	    addProduct : function (event, productData) {

	    	var quant=1;
	    	
	    	var product = null;
	    	if(productData == null || productData == undefined){
		       	var clicked = $(event.currentTarget);
				
				if(this.settings.detail){
					quant = parseInt($('#product-amount').val());
					

				}
				
				//passing things around by reference, so get the ref, and afterwards deepcopy it.
		    	productRef = /*eval('('+clicked.attr("productdata")+')');*/
		    			this.lookupProduct(clicked.attr("product-type"), clicked.attr("product-index"));
		 
		    	//deepcopy it
		    	product = jQuery.extend(true, {}, productRef);

		    			

		    	if(!this.settings.detail && product.type=='product'){
			    	quant = parseInt(product.orderSize);
		    	}
		    	
		    	if(product.type=='product' && this.settings.detail){
		    		product = this.addSelectedOptionsToProduct(product);
		    	
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
			var existingProduct = this.productExists(product); 
			
			//check if product exists in store
			if(existingProduct != null){ //get the current quantity 
				this.logger("product exists");

				if(existingProduct.type == 'package'){
					this.updateQuantityInPackage(product,existingProduct);
				}

				existingProduct.quantity = parseInt(existingProduct.quantity) + parseInt(product.quantity);				
/*				else { //material or product, just update the quantity
					existingProduct.quantity = parseInt(existingProduct.quantity) + parseInt(product.quantity);				
				}*/
			}
			else {
			   this.logger("product does not exist");
			   if(this.cartDataStore == "EMPTY")
			   		this.cartDataStore = [];
			   
			   product = this.multiplyProductsInPackageByX(quant, product);
			   this.cartDataStore.push(product);
			}
			this.logger("cart: ");
			this.logger(this.cartDataStore);
			
			
			this.render($(this));
			
			return true;

	    },
	    addSelectedOptionsToProduct : function(product){
    		for(var i = 0; i < dishOptions.options.length ; i++){
	    		if(this.optionIsSelected(parseInt(dishOptions.options[i].ingredients_id))){
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
					   		//this.logger(product.products[i].title+": "+product.products[i].quantity +" x "+quant)
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
   			this.logger("addExtraProducts");
   			var self = this;
	    	$('.amount-extra').each(function(){
	    		var quant = parseInt($(this).val());
	    		if(quant > 0){

	    			var pType = $(this).parent().attr("product-type");	    		
	    			var pIndex = $(this).parent().attr("product-index");
	    			var product = self.lookupProduct(pType, pIndex); 
	    			
	    			var productClone = jQuery.extend(true, {}, product);//deepcopy
				
	    			productClone.quantity = parseInt(quant); 
	    			self.addProduct(null, productClone);
	    		}
	    	});
	    	

	    },	    	    
	    removeProduct : function (event) {
	    	this.logger("Removing product");
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
	    		    	
	    		
	    	this.logger(type);
	    	this.logger(id);	
	    	
	    	for(var i = 0; i < this.cartDataStore.length ; i++){
				var obj = this.cartDataStore[i];

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
						       	if(!this.containsIngredient(selectedOptions, obj.options[j])){
							       	equal = false;
						       	}
					       	}
					       	if(equal) {
						    	this.cartDataStore.splice(i,1);
						    	break;						       	
					       	} //else continue iterating over the cart
				       	}
			       	}
			       	else {
				    	this.cartDataStore.splice(i,1);
				    	break;
			       	}
			    }
	    	}
	    	
	    	this.persist();

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
			for(var i = 0; i < this.cartDataStore.length ; i++){
	    		var obj = this.cartDataStore[i];

	    		
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
	    	this.logger("total deposit: "+deposit);
	    	
	    	if(deposit > 0){
	    		$('.deposit-total').html(this.formatEuro(deposit));
		    	$('.deposit-container').removeClass('hidden');
	    	}
	    
	    },
	    updatePrices : function(){
			var price = 0;
			vatMap = {};
	    	//loop over cartDataStore
	    	totalInclVat=0;
	    	this.logger("updatePrices");
	    	this.logger(this.cartDataStore);
	    	
	    	for(var i = 0; i < this.cartDataStore.length ; i++){
	    		var obj = this.cartDataStore[i];
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
	    		this.logger("updating VATMAP with: "+ parseFloat(obj.price));
	    		vatMap["x"+obj.VAT] += parseFloat(currentPrice);
	    		
	    	}

  			var delPrice = 0;
  			console.log($('.deliveryType'));
  			if($('#bezorgen').is(':checked') || $('.deliveryType').val() == 'bezorgen' ) {
		    	$('#not-enough-ordered').addClass('hidden');
  			
	  			if(!this.settings.deliveryFormula) { //if we are using the tabular form.
			    	//check in the settings.deliveryCosts what is the delivery price
			    	
			    	
			    	var doNotDeliver = true;
			    	var notEnoughOrdered = false;
					for(var i = 0; i < this.settings.deliveryCosts.length; i++){
						var min = parseInt(this.settings.deliveryCosts[i].minKm);
						var max = parseInt(this.settings.deliveryCosts[i].maxKm);	
						this.logger(min+" "+max+" "+distance);
						if(min <= distance && distance < max) { //if distance is within this range
							if(totalInclVat < parseFloat(this.settings.deliveryCosts[i].minimumOrderPrice)){
								if(distance > 0){
									$('#not-enough-ordered').removeClass('hidden').html(
									'We bezorgen op deze afstand ('+ 
													this.formatEuro(distance)+' km) vanaf een bedrag van €'+
													this.formatEuro(parseFloat(this.settings.deliveryCosts[i].minimumOrderPrice)));
									doNotDeliver=true;
									notEnoughOrdered = true;
								    //hide submit buttons
									$('.submit-controls').addClass('disabled');
								}
								break;
							}
							else {
								//update the table of the checkout 
								deliveryCosts.price = parseFloat(this.settings.deliveryCosts[i].price);
								$('.submit-controls').removeClass('disabled');
								$('#not-enough-ordered').addClass('hidden');
								$('.deliverycosts-field').html("<strong>€ "+this.formatEuro(delPrice)+"</strong>");
								doNotDeliver = false;						
							}
						}
					}
					console.log('out of reach?');
					if(this.distanceIsOutOfReach(distance)){
						this.logger("Address out of reach!");
						$('.submit-controls').addClass('disabled');
						$('#not-enough-ordered').removeClass('hidden').html('We bezorgen helaas niet op deze afstand.');
						doNotDeliver = true;						

					}
			
				} 
				else { //use the formula, which uses a different calculation structure.
		
				
					var delivery = this.settings.deliveryCosts[0];
					this.logger("delivery object");
					this.logger(delivery);
					
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
							this.logger("free delivery, since total > freedelivery threshold");
							delCosts = 0;
						}
					
						if(minOrderPrice > totalInclVat){
							if(distance > 0) {
								$('#not-enough-ordered')
									.removeClass('hidden')
									.html(
										'<strong>Let op:</strong> We bezorgen op deze afstand ('+ 
														this.formatEuro(distance)+' km) vanaf een bestelbedrag van €'+
														this.formatEuro(minOrderPrice));
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
	    	
	    	
	    	
	    	if(!this.analyzeDeliveryOptions()){
		    	$('.submit-controls').addClass('disabled');
	    	}
	    	
	    	if(vatMap["x0.21"] == null || vatMap["x0.21"] == undefined)
	    		vatMap["x0.21"] = 0;
	    		
  			vatMap["x0.21"] += parseFloat(deliveryCosts.price);
  			this.logger(deliveryCosts)
	    	this.logger("deliveryCosts.price: " + deliveryCosts.price);
	    	totalInclVat += parseFloat(deliveryCosts.price);
	    	
	    	
	    	if(this.settings.pricesAreInclVat){
		    	totalExclVat = totalInclVat;
		    	this.logger(vatMap);
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
			    this.logger(vatMap);
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
	    	this.logger('setting price: '+this.formatEuro(totalInclVat));
			
			var discountTableDiscount = this.calculateDiscountTableAndUpdateCart();
			
			if(discountTableDiscount == null || discountTableDiscount == undefined)
				discountTableDiscount = 0.0;
			
			var tot = 0;
			if(this.settings.pricesAreInclVat){
				tot = totalInclVat;
			}
			else {
				tot = totalExclVat;
			}
			
	    	$('.total-price').html(this.formatEuro(tot * (1 - discountTableDiscount)));

				
			if(totalInclVat == 0) {
				totalExclVat = 0;
			}
			
			$('.deliverycosts-field').html("<strong>€ "+this.formatEuro(deliveryCosts.price)+"</strong>");
			$('.subtotal-field').html("<strong>€ "+this.formatEuro(totalExclVat)+"</strong>");
			
			

			
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
			$('.total-field').html("<strong>€ "+this.formatEuro(totalWithCouponDiscount * (1-discountTableDiscount))+"</strong>");	

			
			
			for(var perc in vatMap){					
				var sel = String(perc).replace('.','_');
				$('.vat-field-'+sel).html('<strong>€ '+this.formatEuro(vatMap[perc])+'</strong>');
			}
	    },	
	    distanceIsOutOfReach : function(dist){
			this.logger('checking if it is out of reach');
	    	var max = 0;
			for(var i = 0; i < this.settings.deliveryCosts.length; i++){
				var cur = this.settings.deliveryCosts[i].maxKm;
				if(cur > max) {
					max = cur;
				}
			}
			return dist > max;
	    },
	    clearCart : function(){
	    	this.logger("Clearing cart!");
	    	this.cartDataStore = [];
	    	this.persist();
	    	this.render();
	    	this.updatePrices();
	    },    
	    removeProductFromCheckoutPage : function(event){
	    	this.logger("removeProductFromCheckoutPage");
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
	    	for(var i = 0; i < this.cartDataStore.length ; i++){
	    		var x = this.cartDataStore[i];
	    		price+= x.price * x.quantity;
	    	}
	    	
	    	
	    	totalInclVat = price;
	    	totalInclVat += deliveryCosts.price;
	    	
	    	
	    	
	    	this.logger('setting price: '+this.formatEuro(price));
	    	var tableDiscount = this.calculateDiscountTableAndUpdateCart();
	    	this.logger("TABLE DISCOUNT " +tableDiscount);
	    	var discountFactor = 1 - (tableDiscount + (parseFloat(discount) / 100.0));
	    	this.logger("DISCOUNT FACTOR " + discountFactor);
	    	
	    	var tot = this.settings.pricesAreInclVat ? totalInclVat : totalExclVat;
	    	
	    	
	    	var t = tot * discountFactor;
	    	
	 
	    	
	    	this.logger("TOTAL "+t);
	    	$('.total-price').html(this.formatEuro(t));

	    },
	    /* returns null if product doesnt exist in cart, and returns the product from the cart that is equal to the passed in object otherwise */
	    productExists : function(product){
	    	for(var i = 0; i < this.cartDataStore.length ; i++){
	    	   var obj = this.cartDataStore[i];
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
			    	if(this.checkDishOptionsAreEqual(product, obj))
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
			//this.logger(clicked.attr("productdata"));
	    	var product = //eval('('+clicked.attr("productdata")+')');
	    				this.lookupProduct(clicked.attr("product-type"), clicked.attr("product-index"));

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
			this.logger(product);
		//	var product2 = this.deepCopy(product);
		//	this.logger("prod2");
		//	this.logger(product2);
			var jsonString = $.toJSON(product);
			//var jsonString = jsDump.parse(product);
			
			clicked.attr("productdata", jsonString); 
		},
		
		validateForm : function(){
			$('#validation-error').addClass('hidden');			    
			var left = this.checkFlexItemsTotals();
			if(left < 0){
			 	$('#validation-error').removeClass('hidden');
				return false;
			}
			
			
			return true;
		},
	    //binds all the buttons to the respective event
	    bindButtons : function(){
	    	 var self = this;
	    	 $('body').on("change.shoppingCart",".deliveryType", function(event){
	    	     self.updatePrices();
 		    	 self.calculateDeposit();
	    	 });
			 $("body").on("click.shoppingCart","a.removefromcart", function(event){
			 	self.logger("a.removefromcart clicked");
		    	event.preventDefault();
		    	self.removeProduct(event);
		    	self.updateCartTotalPrice();
    			self.updatePrices();
		    	self.calculateWeight();
		    	self.calculateDeposit();
				event.stopPropagation();
		    });
		     $("body").on('click.shoppingCart', 'a.removefromcart-checkout', function(event){
		    	self.removeProduct(event);
		    	self.removeProductFromCheckoutPage(event);

    			self.updatePrices();
    			self.calculateWeight();
		    	self.calculateDeposit();		    	
		    });	 
	    
	    	if(this.settings.detail){
			    $('.addtocart').on('click.shoppingCart', function(event){
			    	event.preventDefault();
		         	$('.product-size-wrong').addClass('hidden');
		         	$('.product-added').addClass('hidden');
		         	
				    if(!self.validateForm())
				    	return;
				    
					self.updateProductDataInDOM(event);
									    	
				    var b = self.addProduct(event);
		    		if(b){
					    self.addExtraProducts(event); //aanvullingen
					    
					    self.updatePrices();
					    self.calculateWeight();
					    self.calculateDeposit();
					    $('.product-added').removeClass('hidden');
				    }
				    self.persist();
				    
			   });
	    	}
	    	else {
			    $('.addtocart').on('click.shoppingCart', function(event){
			    	self.logger("Adding product");
			    	event.preventDefault();
				
				    if(!self.validateForm()){
				    	self.logger("Invalid form, returning");
				    	return;
				    }
				    			    	
				    var b = self.addProduct(event);
				    if(b){
						self.updatePrices();		
						self.calculateWeight();		
						self.calculateDeposit();
						self.createAutoClosingAlert('.product-added-popup',2000);
					}
					
					self.persist();
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
			    	self.logger("Distance calc: Using address 'elsewhere'");
			    }
			    else {
				    self.logger("Distance calc: Using normal delivery address");
			    }
			    if(self.allAddressFieldsFilledOut()){
			    	self.calculateDistance(compareToAddress, function(dist){
			   			self.updatePrices();		    	
			    	});
		    	}
		    	else {
					distance = 0;
					self.updatePrices();							    
				}
		    });	   
		    
		    
		    $('.amount-flex').bind('change.shoppingCart',function(){
		    	self.updateFlexibleItemsTotals();
		    	self.validateForm();
		    	
		    });
		    
		    $('#product-amount').bind('change.shoppingCart',function(){
		    	self.updateFlexibleItemsTotals();
		    	self.validateForm();
		    });
		    
		    $('#coupon').bind('change.shoppingCart', function(){
		    	self.checkCoupon(function(){
		    		self.updatePrices();
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
			  origin: this.settings.address,
			  destination: cptaddr,
			  travelMode: google.maps.TravelMode.DRIVING,
			  unitSystem: google.maps.UnitSystem.METRIC,
			  avoidHighways : false,
			  avoidTolls: false,
			  region: this.settings.region
			}
					
			var directionsService = new google.maps.DirectionsService();
			distance = -1;
			var self = this;
			console.log(directionsService);
	        directionsService.route(queryData, function(response, status) {
	            if (status == google.maps.DirectionsStatus.OK) {
	            	distance = parseInt(response.routes[0].legs[0].distance.value) / 1000;
	            	self.logger("Distance found: "+distance+" km");
	            	
	            }
				else {
					self.logger("Something went wrong, or address not found: "+status)
					self.logger(response);
				}            
   	           	if(callback != null && callback != undefined)
	            	callback.call(distance);

			});			
	    	
	    },
	  	setDiscount : function(disc){
			discount = disc; 
			this.updateCartTotalPrice();
			if(disc == 0 || couponType != "normaal")
				$('#discount-row').addClass('hidden');
			else {	
				$('#discount-row').removeClass('hidden');
			}

			$('.discount-field').html('<strong>'+disc+'%</strong>');
			$('.total-field').html("<strong>€ "+this.formatEuro(totalInclVat * (1 - (discount / 100)))+"</strong>");						
			
	  	},
	    //saves the current data store object in cookie
	    persist : function(){
			if(this.cartDataStore.length == 0){
				this.cartDataStore="EMPTY";
			}
			var self=this;
			$.ajax({
				url : this.settings.session_url,
				type: 'POST',
				data: {"shoppingCart" : this.cartDataStore},
				success: function (jsonObj, textStatus, jqXHR){
					self.logger("Persisted: ")
					self.logger(jsonObj);
				},
				dataType: 'json'
			});
			
	    },
	    //loads the state from cookie, and returns an object with the contents of the shopping cart
	    load : function(callback){
		  var self = this;
		  $.ajax({
				url: this.settings.session_url,
				type: 'GET',
				data: {"action" : "load"},
				success: function (jsonObj, textStatus, jqXHR){
					self.logger("Loaded: ");
					self.logger(jsonObj);
					callback.call(self, jsonObj);
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

	$.fn[ pluginName ] = function ( options ) {
		return this.each(function() {
			if ( !$.data( this, "plugin_" + pluginName ) ) {
					$.data( this, "plugin_" + pluginName, new ShoppingcartPlugin( this, options ) );
			}
		});
	};
})( jQuery, window, document );