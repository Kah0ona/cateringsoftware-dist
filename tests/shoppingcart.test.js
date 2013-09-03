describe("Testsuite for the shoppingcart jquery plugin.", function() {
	afterEach(function(){
		$('#shoppingcart').remove();
		$('#detailform').remove();
	});
	
	beforeEach(function () {
	    $('<div id="shoppingcart"></div>').appendTo('body');
   		var detailForm = 
   			'<div id="detailform"><input class="input-small" name="product-amount" id="product-amount" value="8" type="text"> '+
			'<div class="span12 standard-products product-data">'+
			'<div class="span12 product-data ">'+
			'<span product-type="package" product-index="0" class="addtocart">'+
			'  <a href="#" class="btn">Voeg toe</a>'+
			'</span></div>';

	    $(detailForm).appendTo('body');
	    
	    spyOn($, 'ajax');
	    
	});

	describe('Loading and initializing the shoppingcart plugin', function(){
		it("Should load data from the server upon intialization, via an AJAX call.", function() {
			$('#shoppingcart').shoppingCart({});
			expect($.ajax).toHaveBeenCalled();
		});
	});
	
	describe('Adding products test suite', function(){
		it('Should persist a quantity of 8 added products when the add product is clicked on a detail page.', function(){
			allProducts = {};
			allProducts.packages = [{"Package_id":163,"type":"package","thumb":"http:\/\/beheer.cateringsoftware.nl\/","title":"Tapas mandjes voor 8 personen","desc":"vanaf 8 personen\n\nGevuld met Old Amsterdam, olijven, tapenade, Italiaanse cervelaat,\n Italiaanse gehakt lekkernijen, foccacia brood","price":2.5,"VAT":0.06,"deliveryOptions":"beide","products":[{"Product_id":2197,"type":"product","title":"Tapas Mandje","desc":"Gevuld met Old Amsterdam, olijven, tapenade, Italiaanse cervelaat, Italiaans gehaktlekkernijen en foccacia brood","thumb":"http:\/\/beheer.cateringsoftware.nl\/","quantity":1,"containmentType":"vast aantal","price":2.5,"dishWeightFactor":1,"VAT":0.06,"orderSize":1,"deliveryOptions":"beide","deposit":0,"showAmount":true}],"materials":[]}];	
			$('#shoppingcart').shoppingCart({ detail : true });
			$('.addtocart').click(); //simulate click
	
			expect($.ajax).toHaveBeenCalled();
			expect($.ajax.mostRecentCall.args[0].data.shoppingCart[0].quantity).toBe(8);
		});
		
		it('When adding a product twice, the cart only has 1 entry for the product, but the quantity has to be 2', function(){
			allProducts = {};
			allProducts.packages = [{"Package_id":163,"type":"package","thumb":"http:\/\/beheer.cateringsoftware.nl\/","title":"Tapas mandjes voor 8 personen","desc":"vanaf 8 personen\n\nGevuld met Old Amsterdam, olijven, tapenade, Italiaanse cervelaat,\n Italiaanse gehakt lekkernijen, foccacia brood","price":2.5,"VAT":0.06,"deliveryOptions":"beide","products":[{"Product_id":2197,"type":"product","title":"Tapas Mandje","desc":"Gevuld met Old Amsterdam, olijven, tapenade, Italiaanse cervelaat, Italiaans gehaktlekkernijen en foccacia brood","thumb":"http:\/\/beheer.cateringsoftware.nl\/","quantity":1,"containmentType":"vast aantal","price":2.5,"dishWeightFactor":1,"VAT":0.06,"orderSize":1,"deliveryOptions":"beide","deposit":0,"showAmount":true}],"materials":[]}];				
			$('#shoppingcart').shoppingCart({ detail : true });
			$('.addtocart').click(); //simulate click 
			
			expect($.ajax.calls[1].args[0].data.shoppingCart[0].quantity).toBe(8);
			
			$('.addtocart').click(); //simulate click #2

			expect($.ajax.calls[2].args[0].data.shoppingCart[0].quantity).toBe(16);			
			expect($.ajax.calls[2].args[0].data.shoppingCart.length).toBe(1);		
			
			expect($.ajax.calls.length).toBe(3); //load, save, save of adding product twice.
			
		});
		
	});
	
	describe('Removing products testsuite', function(){
		it('Should yield an empty cart, when the X is clicked in a cart with a single product', function(){
			$('#shoppingcart').shoppingCart({cartDisplayMode : 'block'});

			allProducts = {};
			allProducts.packages = [{"Package_id":163,"type":"package","thumb":"http:\/\/beheer.cateringsoftware.nl\/","title":"Tapas mandjes voor 8 personen","desc":"vanaf 8 personen\n\nGevuld met Old Amsterdam, olijven, tapenade, Italiaanse cervelaat,\n Italiaanse gehakt lekkernijen, foccacia brood","price":2.5,"VAT":0.06,"deliveryOptions":"beide","products":[{"Product_id":2197,"type":"product","title":"Tapas Mandje","desc":"Gevuld met Old Amsterdam, olijven, tapenade, Italiaanse cervelaat, Italiaans gehaktlekkernijen en foccacia brood","thumb":"http:\/\/beheer.cateringsoftware.nl\/","quantity":1,"containmentType":"vast aantal","price":2.5,"dishWeightFactor":1,"VAT":0.06,"orderSize":1,"deliveryOptions":"beide","deposit":0,"showAmount":true}],"materials":[]}];				
			
			$('.addtocart').click(); //simulate click, and adding a product


			expect($.ajax.calls[1].args[0].data.shoppingCart[0].quantity).toBe(1); //not using detailForm here, we 're on a public page
			expect($.ajax.calls[1].args[0].data.shoppingCart.length).toBe(1);		

			$('a.removefromcart').trigger('click');
			

			expect($.ajax.calls[1].args[0].data.shoppingCart.length).toBe(0);
		});
	});
	
	describe('Calculate distance', function(){
		it('should calculate the distance when all address fields in the checkout form are filled out', function(){
			spyOn(google.maps.DirectionsService.prototype, 'route');

		
			$('<div id="wrap"></div>').appendTo('body');	
			$('<input type="text" value="hugo de grootstraat" name="street" class="input-large address-line" id="street">').appendTo('#wrap');
			$('<input type="text" name="city" class="input-large address-line" id="city">').appendTo('#wrap');
			$('<input type="text" name="postcode" maxlength="7" class="input-large span3 address-line" id="postcode">').appendTo('#wrap');
			$('<input type="text" name="number" maxlength="7" class="input-large span3 address-line" id="number">').appendTo('#wrap');
			$('<input type="text" name="country" class="input-large address-line" id="country">').appendTo('#wrap');			

			$('#shoppingcart').shoppingCart({cartDisplayMode : 'block'});

			expect(google.maps.DirectionsService.prototype.route).not.toHaveBeenCalled();
			$('#city').val('Den Haag');
			$('#postcode').val('2518EE');
			$('#number').val('62b');

			$('.address-line').change();
			expect(google.maps.DirectionsService.prototype.route).not.toHaveBeenCalled();
			$('#country').val('Nederland');
			$('.address-line').change();
			expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();						
			$('#wrap').remove();
			
		});
		
		
		it('should use delivery address lines in the calculation of the distance, and all those field should be filled out first AND the "deliver elsewhere" checkbox should be checked ', function(){
			spyOn(google.maps.DirectionsService.prototype, 'route');
		
			$('<div id="wrap"></div>').appendTo('body');	
			$('<input type="checkbox" name="deliveryElsewhere" class="input-large address-line-elsewhere" id="deliveryElsewhere" checked="checked">').appendTo('#wrap');			
			$('<input type="text" name="deliveryStreet" class="input-large address-line-elsewhere valid" id="deliveryStreet">').appendTo('#wrap');
			$('<input type="text" name="deliveryNumber" class="input-large address-line-elsewhere valid" id="deliveryNumber">').appendTo('#wrap');
			$('<input type="text" name="deliveryZipcode" class="input-large address-line-elsewhere valid" id="deliveryZipcode">').appendTo('#wrap');
			$('<input type="text" name="deliveryCity" class="input-large address-line-elsewhere valid" id="deliveryCity">').appendTo('#wrap');
			$('<input type="text" name="deliveryName" class="input-large address-line valid" id="deliveryName">').appendTo('#wrap');			

			$('#shoppingcart').shoppingCart({cartDisplayMode : 'block'});

			expect(google.maps.DirectionsService.prototype.route).not.toHaveBeenCalled();
			$('#deliveryStreet').val('Hugo de grootstraat');
			$('#deliveryZipcode').val('2518EE');
			$('#deliveryNumber').val('62b');
			
			$('.address-line-elsewhere').change();
			expect(google.maps.DirectionsService.prototype.route).not.toHaveBeenCalled();
			$('#deliveryCity').val('Den Haag');
			$('.address-line-elsewhere').change();
			expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();						
			$('#wrap').remove();

		});
		
		
		var fakeGoogleMapsReply = function(x,y){
				//console.log(y);
				y.call(this, { routes : [{ legs: [{distance : {value: 10000000}}]}]}, google.maps.DirectionsStatus.OK);
		};
		var fakeGoogleMapsReplyWithinReach = function(x,y){
				//console.log(y);
				y.call(this, { routes : [{ legs: [{distance : {value: 10000}}]}]}, google.maps.DirectionsStatus.OK);
		};
		
		var buildDom = function(delCosts){
			$('<div id="wrap"></div>').appendTo('body');	
			$('<input type="hidden" name="deliveryType"  class="deliveryType input-large" value="bezorgen" />').appendTo('#wrap');
			$('<input type="text"  name="street" class="input-large address-line" id="street">').appendTo('#wrap');
			$('<input type="text" name="city" class="input-large address-line" id="city">').appendTo('#wrap');
			$('<input type="text" name="postcode" maxlength="7" class="input-large span3 address-line" id="postcode">').appendTo('#wrap');
			$('<input type="text" name="number" maxlength="7" class="input-large span3 address-line" id="number">').appendTo('#wrap');
			$('<input type="text" name="country" class="input-large address-line" id="country">').appendTo('#wrap');			
			$('<input type="hidden" name="calculateddistance" id="calculateddistance" value="" />').appendTo('#wrap');

			$('<p id="not-enough-ordered" class="hidden alert alert-error"></p>').appendTo('#wrap');

			$('#shoppingcart').shoppingCart({cartDisplayMode : 'block',
				"deliveryCosts" : delCosts
			});

			$('#street').val('balistraat');
			$('#city').val('Den Helder');
			$('#postcode').val(' ');
			$('#number').val('1');
			$('#country').val('Nederland');
			$('.address-line').change();
		}
		
		it('should show an error message when the distance is out of reach of the delivery table', function(){
			spyOn(google.maps.DirectionsService.prototype, 'route').andCallFake(fakeGoogleMapsReply);
			
			buildDom( [
					   {"DeliveryCost_id":3,"price":0,"minKm":0,"maxKm":35,"minimumOrderPrice":109.5},
					   {"DeliveryCost_id":5,"price":10,"minKm":35,"maxKm":50,"minimumOrderPrice":109.5},
					   {"DeliveryCost_id":6,"price":15,"minKm":50,"maxKm":70,"minimumOrderPrice":250},
					   {"DeliveryCost_id":46,"price":22.5,"minKm":70,"maxKm":350,"minimumOrderPrice":350}
				  ]);

			expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();						
			
			var result = $('#not-enough-ordered').html();
			var isHidden = $('#not-enough-ordered').hasClass('hidden');
			expect(result).toBe('We bezorgen helaas niet op deze afstand.');
			expect(isHidden).toBe(false);
			
			$('#wrap').remove();

		});
		
		it('should show an error message when the distance is within reach of the delivery table, but there is not enough ordered', function(){
			spyOn(google.maps.DirectionsService.prototype, 'route').andCallFake(fakeGoogleMapsReplyWithinReach);
			buildDom( [
					   {"DeliveryCost_id":3,"price":0,"minKm":0,"maxKm":35,"minimumOrderPrice":109.5},
					   {"DeliveryCost_id":5,"price":10,"minKm":35,"maxKm":50,"minimumOrderPrice":109.5},
					   {"DeliveryCost_id":6,"price":15,"minKm":50,"maxKm":70,"minimumOrderPrice":250},
					   {"DeliveryCost_id":46,"price":22.5,"minKm":70,"maxKm":350,"minimumOrderPrice":350}
			]);
			expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();						

			var isHidden = $('#not-enough-ordered').hasClass('hidden');
			expect(isHidden).toBe(false);
			expect($('#not-enough-ordered').html()).toMatch('vanaf een bedrag van');
			
			expect($('.submit-controls').hasClass('disabled')).toBe(false);
			
			$('#wrap').remove();

		});
		
		it('should show NO error message when the distance is within reach of the delivery table, AND there is enough ordered', function(){
			spyOn(google.maps.DirectionsService.prototype, 'route').andCallFake(fakeGoogleMapsReplyWithinReach);
			buildDom([
									   {"DeliveryCost_id":3,"price":0,"minKm":0,"maxKm":35,"minimumOrderPrice":0},
									   {"DeliveryCost_id":5,"price":10,"minKm":35,"maxKm":50,"minimumOrderPrice":0},
									   {"DeliveryCost_id":6,"price":15,"minKm":50,"maxKm":70,"minimumOrderPrice":0},
									   {"DeliveryCost_id":46,"price":22.5,"minKm":70,"maxKm":350,"minimumOrderPrice":0}
			]);
			expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();						

			var isHidden = $('#not-enough-ordered').hasClass('hidden');
			expect(isHidden).toBe(true);
			expect($('.submit-controls').hasClass('disabled')).toBe(false);
			
			$('#wrap').remove();

		});
		
		it('should show an error message when the address is not found', function(){
			spyOn(google.maps.DirectionsService.prototype, 'route').andCallFake(function(x,y){
				//console.log(y);
				y.call(this, [], google.maps.DirectionsStatus.NOT_FOUND);
			});
			
			buildDom([{"DeliveryCost_id":3,"price":0,"minKm":0,"maxKm":35,"minimumOrderPrice":0}]);
						expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();						

			var result = $('#not-enough-ordered').html();
			var isHidden = $('#not-enough-ordered').hasClass('hidden');
			expect(result).toBe('Het ingevoerde adres kon niet worden gevonden, waardoor we eventuele bezorgkosten niet kunnen berekenen. Verander het adres. Blijft het probleem zich voordoen? Neem dan contact met ons op.');
			expect(isHidden).toBe(false);
			
			$('#wrap').remove();


		});
		
		it('should render a hidden input element in the checkout form with the calculated distance', function(){
			spyOn(google.maps.DirectionsService.prototype, 'route').andCallFake(fakeGoogleMapsReplyWithinReach);
			buildDom([
									   {"DeliveryCost_id":3,"price":0,"minKm":0,"maxKm":35,"minimumOrderPrice":0},
									   {"DeliveryCost_id":5,"price":10,"minKm":35,"maxKm":50,"minimumOrderPrice":0},
									   {"DeliveryCost_id":6,"price":15,"minKm":50,"maxKm":70,"minimumOrderPrice":0},
									   {"DeliveryCost_id":46,"price":22.5,"minKm":70,"maxKm":350,"minimumOrderPrice":0}
			]);
			expect(google.maps.DirectionsService.prototype.route).toHaveBeenCalled();				
			var result = parseInt($('#calculateddistance').val());
			expect(result).toBe(10000);
			
			$('#wrap').remove();
		});
	});
});