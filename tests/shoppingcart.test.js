describe("Testsuite for the shoppingcart jquery plugin.", function() {
	beforeEach(function () {
	    $('<div id="shoppingcart"></div>').appendTo('body');
	});


	it("Should calculate a distance between the address from the settings and the address from the parameter, of greater than zero", function() {
	  var cart = jQuery('#shoppingcart').shoppingCart('testInterface', { 
			"detail" : false,
			"address" : "hugo de grootstraat 62b 2518 EE den haag",
			"pricesAreInclVat" : true,
			"region"  : "nl",
			"deliveryCosts" : [],
			"cartClass" : "nav pull-right",
			"cartDisplayMode" : "dropdown",
			"session_url" : "http://localhost/~marten/cateringsoftware/wp-content/plugins/cateringsoftware-dist/cart_store.php",
			"checkout_page" : "http://localhost/~marten/cateringsoftware/checkout",
			"deliveryFormula" : false,
			"checkout_link" : "Afrekenen",
			"cart_text" : "Mijn bestellingen",
			"pickupAndDelivery" : true	
	  });
	  
	  console.log(cart);
	
	    
	
	});

});