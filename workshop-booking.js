var SELECT_FUTURE  = -1;
var NOT_AVAILABLE  = -2;
var AVAILABLE      = -3;

var reqMsg = "Dit veld is verplicht.";
var emailMsg = "Vul een geldig e-mailadres in. ";
var discount = 0;
var couponType = "";
$(document).ready(function(){
	$( ".tooltip" ).tooltip({
		delay: 0,
		showURL: false,
		showBody: " - ",
		track: true
	});

	$('#book-workshop').click(function(){
		if(!hasDates){
			$.datepicker.setDefaults( $.datepicker.regional[ "nl" ] )		
			$("#datum").datepicker({
				onSelect: function(dateText, inst){
					updateAvailabilityMessage(validateDateAndTime());
				}
			});
		}
		if(!hasDates){
			$('#deliveryTime').change(function(){
				updateAvailabilityMessage(validateDateAndTime());
			});
		}
		if(hasDates){
			$('#WorkshopDate_id').change(function(){
				 updateNumSubscriptionsMessage();
			});
		}


		$('.updates-price').change(function(){
			updatePrice();
		});

		$('#workshop-booking-form-container').show('slow');
	});

	//only allow numbers
	$(".numeric").keydown(function(event) {
		// Allow only backspace and delete, and arrows and tab
		if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode==9 || event.keyCode==37 || event.keyCode==39) {
			// let it happen, don't do anything
		}
		else {
			// Ensure that it is a number and stop the keypress
			if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 )) {
				event.preventDefault(); 
			}   
		}
	});
	
	$("#workshop-booking-form").validate({
			rules : {
				firstname : {
					required: true
				},
				surname : {
					required: true
				},
				street : {
					required: true
				},
				number : {
					required: true
				},
				postcode : {
					required: true
				},
				city : {
					required: true
				},
				email : {
					required: true,
					email: true
				},
				phone : {
					required: true
				},
				persons : {
					required: true
				},
				datum: {
					required: true
				}
			},
			submitHandler: function(form) {
				if(!hasDates){
					//do some extra validation
					var avail = validateDateAndTime();
					if(avail != AVAILABLE){
						updateAvailabilityMessage(avail);
						$('#availability').focus();
						return false;
					}
				}
				
				//if all fine, submit to server via post
				$(form).ajaxSubmit({
				  beforeSubmit:  function(){
				  	//hide the form to prevent another click
				  	$('#workshop-booking-form-container').hide('slow');
					$('#book-workshop').hide('slow');
					showSendingMessage();

				  },
				  success : function(data, textStatus, jqXHR) {
					showSuccesMessage();
				  }
				});
				
				return false;
			},
			messages : {
				firstname: {
				   required: reqMsg
			    },
				surname: {
				   required: reqMsg
			    },
				street: {
				   required: reqMsg
			    },
				number: {
				   required: reqMsg
			    },
				postcode: {
				   required: reqMsg
			    },
				city: {
				   required: reqMsg
			    },
				email: {
				   required: reqMsg,
				   email: emailMsg
			    },
				phone: {
				   required: reqMsg
			    },
			    persons : {
			    	required: reqMsg
			    },
			    datum : {
			    	required: reqMsg
			    }  
			},
		    errorPlacement: function(error, element) {
			   error.insertAfter(element);
			}
			
	}); 

	//check coupon code
	$('#coupon').change(function(){
		$('#discount-text').html('Controleren couponcode...').show();
		$.ajax({
			url: couponUrl,
			data: { "hostname" : hostname , "couponCode" : $('#coupon').val()},
			jsonpCallback: 'couponCallback',
			jsonp: 'callback',
			dataType: 'jsonp'
		});		
	});
	
	$('#datum').val('');
	//$('#persons').val(workshops[0].minNumPersons);	

	updatePrice();
	$('#not-a-number').hide(); //initial hide
	$('#not-enough-persons-warning').hide();
	$('#too-many-persons-warning').hide();
	//initialize message
	updateAvailabilityMessage(false);
}); 

function showSendingMessage(){
	$('#submitting-message').html("Bezig met versturen van de gegevens...").show();
}

function showSuccesMessage(){
	//just redirect to the success page
	window.location.href = baseUrl+"/bedankt/?id="+workshops[0].Workshop_id+'&email='+escape($('#email').val());
}

/**
* returns number of persons to book iff num <= spotsLeft, else returns spotsLeft. Use the return value as the value to post to
* the submit_booking.php script.
*/
function updateNumSubscriptionsMessage(){

	$('#too-many-persons-warning2').hide();
	var num = $('#persons').val();

	if(num == null || num == undefined || num == ""){
		num = 0;
	}
	
	var max = workshops[0].maxNumPersons;
	
	var WorkshopDate_id = $('#WorkshopDate_id').val();
	
	if(WorkshopDate_id == null || WorkshopDate_id == undefined) { //nothing selected, return.
  	    $('#availability').css('color','none').html('Kies een datum.').show();

		return;
	}
	else {
		$('#availability').hide();
	}
	
	
	if(max == null || max == undefined) {
		max = 24;
	}
	
	var cur = 0;
	for(var i = 0 ; i < workshops[0].WorkshopDate.length; i++) {
		if(workshops[0].WorkshopDate[i].WorkshopDate_id == WorkshopDate_id){
			cur = workshops[0].WorkshopDate[i].numSubscriptions;
			if(cur == undefined)
				cur = 0;
				
			break;
		}		
	}

	var html ="Er zijn nog maar NUM plaatsen vrij, als u doorgaat boekt u voor maximaal NUM personen.";	   	
	$('#too-many-persons-warning2').html(html); //reset the value

	
	if((parseInt(num)+parseInt(cur)) > max){
		if((max-cur) == 1) {
			html = "Er is nog maar 1 plaats vrij, als u doorgaat boekt u voor 1 persoon.";
			$('#too-many-persons-warning2').html(html);
			$('#too-many-persons-warning2').show();
			return 1;
		}

		html  = $('#too-many-persons-warning2').html();
	
		html = html.replace(/NUM/g, (max-cur));
		$('#too-many-persons-warning2').html(html);
		$('#too-many-persons-warning2').show();
		
		return (max-cur);
	}
	else {
		return num;
	}
}

function isNumeric(input) {
    return (input - 0) == input && input.length > 0;
}

function validateDateAndTime(){
	var dateString = convertDateToMySqlFormat($('#datum').val());
	var time  = $('#deliveryTime').val();
	var avail = isDateAvailable(dateString,time, workshops[0]);
	return avail;
}


function updatePrice(){
	$('#not-a-number').hide();
	var num = $('#persons').val();
	var elt = $('#the-price');
	
	if(!isNumeric(num) || (num == null || num == undefined || num == "")){
		$('#not-a-number').show();
		elt.html('-,--');
		return;
	}
	
	if(!hasDates){
		if(num < workshops[0].minNumPersons){
			$('#not-enough-persons-warning').show();
			$('#too-many-persons-warning').hide();
			num = workshops[0].minNumPersons;
		}
		else {
			$('#not-enough-persons-warning').hide();
		}
	
		if(num > workshops[0].maxNumPersons){
			$('#too-many-persons-warning').show();
			$('#not-enough-persons-warning').hide();
			num = workshops[0].maxNumPersons;
		}
		else {
			$('#too-many-persons-warning').hide();
		}
	}
	else {
		num = updateNumSubscriptionsMessage();
		$('#persons').val(num);
	}
	
	
	var discountFactor = 1
	
	if(couponType=="normaal")
		discountFactor = 1-(discount/100);

	var price = num * workshops[0].workshopPrice;
	
	//foreach selected checkbox, add this price * num to the price.
	$('input:checkbox[name=WorkshopExtra_id]:checked').each(function(){
		//get id, extract the price
		var p = $(this).attr("id").split("_")[2];
		p = p * num;
		
		price += p;
	});
	
	price = price * discountFactor;
	
	price = formatEuro(price);

	elt.html(price);
	
}

function couponCallback(jsonObj){
	
	discount = jsonObj.discount;
	couponType = jsonObj.couponType;
	/*if(discount == 0){
		$('#discount-text').html('Dit is geen geldige couponcode.').show();
	}
	else {
		$('#discount-text').html('<span style="color: green">Couponcode geldig, u krijgt '+discount+'% korting.</span>').show();
	}*/
	
	
	if(discount == 0){
		$('#discount-text')
			.removeClass('hidden')
			.html('<span style="color: red">Dit is geen geldige couponcode.</span>')
			
	}
	else {
		if(couponType == "normaal" || couponType == null || couponType == undefined) {
			$('#discount-text').html('<span style="color: green;">Couponcode geldig, u krijgt '+discount+'% korting.</span>').show();
		}
		if(couponType == "sponsorcoupon") {
			$('#discount-text').html('<span style="color: green;">Couponcode geldig, '+discount+'% van het totale boekingsbedrag zal aan uw vereniging worden gesponsord.</span>').show();
		}
	}


	updatePrice();
}

function formatEuro(price){
	Number.prototype.formatMoney = function(c, d, t){
	var n = this, c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : d, t = t == undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
	   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	};

	return price.formatMoney(2,',','.');
}
/**
* Transforms a date in format dd-mm-yyyy to yyyy-mm-dd
*/
function convertDateToMySqlFormat(dateString){
	var pieces = dateString.split('-');
	return pieces[2]+'-'+pieces[1]+'-'+pieces[0];
}

function isDateAvailable(selectedDate, selectedTime, workshop) {
	var	duration = 4; //defaults to 4
	if(workshop.durationHours != null && workshop.durationHours != undefined) 
		duration = workshop.durationHours;
		
	var sel = getTimeFromDateTime(selectedDate,selectedTime);
	
	if(sel <= new Date().getTime()+(7*24*60*60*1000)){
		return SELECT_FUTURE;
	}
	
	var selEnd = sel + duration*60*60*1000; 
	//loop over bookings
	for(var i = 0; i < bookings.length; i++){  
	    var booking = bookings[i]; 
	    var d2 = booking.dateBooked.split('.')[0];

		var pieces2 = d2.split(' ');
		
		var date = getTimeFromDateTime(pieces2[0],pieces2[1]);

	    var dateEnd = date + duration * 60 * 60 * 1000;
		if(selEnd >= date && sel <= dateEnd) { //date overlap, 
			return NOT_AVAILABLE; //conflict found, return not available
		}
	}
	return AVAILABLE; 
}

function getTimeFromDateTime(dateString, timeString){
	var p1 = dateString.split("-");
	var p2 = timeString.split(":");
    var d = new Date(p1[0], p1[1]-1, p1[2], p2[0], p2[1], "00"); //this is also tested on iPhone, and now works

	//var d = new Date(dateString); //this does not work on iphone
	var sel = d.getTime(); // ms since Unix EPOCH
	return sel;
}

function updateAvailabilityMessage(available){
	var curVal = $('#datum').val();
	if(curVal == null || curVal == undefined || curVal == ""){
		$('#availability').css('color','none').html('Kies een datum.');
		return;
	}

	if(available == AVAILABLE){
		$('#availability').css('color','green').html('Dit tijdstip is nog beschikbaar.');
	}
	else if(available == NOT_AVAILABLE)  {
		$('#availability').css('color','red').html('Dit tijdstip is helaas niet beschikbaar, kies een andere datum en/of tijd.');	
	}
	else if(available == SELECT_FUTURE) {
		$('#availability').css('color','red').html('Het gekozen tijdstip moet <b>minimaal &eacute;&eacute;n week</b> in de toekomst liggen.');	
	}
}


