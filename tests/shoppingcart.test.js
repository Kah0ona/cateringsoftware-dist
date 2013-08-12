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
	    
	    	    
	});


	it("Should load data from the server upon intialization, via an AJAX call.", function() {
		spyOn($, 'ajax').andCallFake(function(){
			return [{ Product_id : 162 }];
		});
		$('#shoppingcart').shoppingCart({});
		expect($.ajax).toHaveBeenCalled();
	});

	it('Should persist a quantity of 8 added products when the add product is clicked on a detail page.', function(){
		spyOn($, 'ajax');
		allProducts = {};
		allProducts.packages = [{"Package_id":163,"type":"package","thumb":"http:\/\/beheer.cateringsoftware.nl\/","title":"Tapas mandjes voor 8 personen","desc":"vanaf 8 personen\n\nGevuld met Old Amsterdam, olijven, tapenade, Italiaanse cervelaat,\n Italiaanse gehakt lekkernijen, foccacia brood","price":2.5,"VAT":0.06,"deliveryOptions":"beide","products":[{"Product_id":2197,"type":"product","title":"Tapas Mandje","desc":"Gevuld met Old Amsterdam, olijven, tapenade, Italiaanse cervelaat, Italiaans gehaktlekkernijen en foccacia brood","thumb":"http:\/\/beheer.cateringsoftware.nl\/","quantity":1,"containmentType":"vast aantal","price":2.5,"dishWeightFactor":1,"VAT":0.06,"orderSize":1,"deliveryOptions":"beide","deposit":0,"showAmount":true}],"materials":[]}];	
		$('#shoppingcart').shoppingCart({ detail : true });
		$('.addtocart').click(); //simulate click

		expect($.ajax).toHaveBeenCalled();
		expect($.ajax.mostRecentCall.args[0].data.shoppingCart[0].quantity).toBe(8);
	});
});