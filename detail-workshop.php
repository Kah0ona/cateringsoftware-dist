<?php
	//include_once('config.php');
	global $theHostname;
	global $locationName;
	global $couponUrl;
	$params = array(
		'hostname'=>$theHostname,
		'Workshop_id'=>$_GET['id']
	);
	$workshopsString = fetchWorkshops($params, true);
	$workshops = json_decode($workshopsString);
	$params2 = array(
		'hostname'=>$theHostname	 
	);
	$bookingsString = fetchBookings($params2, true);
	$bookings = json_decode($bookingsString);	
// Let's justify to the left, with 14 positions of width, 8 digits of
// left precision, 2 of right precision, withouth grouping character
// and using the international format for the nl_NL locale.
setlocale(LC_MONETARY, 'it_IT');


?>
<script type="text/javascript">
 	var workshops = <?php echo $workshopsString; ?>;
 	var bookings =  <?php echo $bookingsString; ?>;
 	var locationName = '<?php echo $locationName; ?>';
 	var thisPageUrl = '<?php echo $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>';
 	var couponUrl = '<?php echo $couponUrl; ?>';
 	var hostname= '<?php echo $theHostname; ?>';
	var baseUrl = '<?php echo $_SERVER['HTTP_HOST']; ?>';
	
</script>


<div id="workshops">
<?php foreach($workshops as $w){ 
	$numDates = 0;
	
	foreach($w->WorkshopDate as $d){
		if(strtotime($d->workshopDate) >= time()){
			$numDates++;
		}
	}
	
	$hasDates = $numDates > 0 ;
	
	
	if($w->maxNumPersons == null){
		$w->maxNumPersons = 24;
	}
		
?>
<script type="text/javascript"> 
	jQuery(document).ready(function($){
		$('.xtooltip').popover();
	});
	
</script>

<script type="text/javascript"> 
	var hasDates = <?php if($hasDates) echo "true"; else echo "false" ; ?>;
	var spotsLeftMap = new Array();
	<?php foreach($w->WorkshopDate as $d){ ?>
	spotsLeftMap['<?php echo $d->WorkshopDate_id; ?>'] = <?php echo ($w->maxNumPersons - $d->numSubscriptions); ?>;
	<?php } ?>
</script>
	<div class="workshop detail">
		<?php if($w->deal) {?><div class='deal'>Aanbieding</div><?php } ?>
 
		<h3><?php echo $w->workshopName; ?></h3>
		
		<ul>
		   <li>Prijs: &euro;<?php echo money_format('%.2n', $w->workshopPrice); ?> pp. </li>
		   <li>Tijdsduur: <?php echo $w->durationHours; ?> uur </li>
		   <li>Aantal personen: <?php echo $w->minNumPersons; ?> tot <?php echo $w->maxNumPersons; ?> </li>
		</ul>
		<img src="<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'.$w->imageWorkshop; ?>" />
		<p><?php echo nl2br($w->workshopDesc); ?></p>
		
		<div class="buttons">
			<div class="read-more workshop-button workshop-back-to-overview"><a href="/workshops/">&larr; Terug</a></div>
			<div class="read-more workshop-button" id="book-workshop"><a href="#workshop-booking-form-container"><?php if(!$hasDates) { ?>Boeken/Optie nemen/Offerte opvragen <?php } else { ?>Boeken<?php }?> &darr; </a></div>
		</div>
		<div class="clear"></div>
	</div>
	
<?php } ?>
</div>
<div class="clear"></div>

<form id="workshop-booking-form" class="workshop-form" action="<?php echo SUBMIT_BOOKING_URL; ?>" method="post">

<div id="workshop-booking-form-container">
	<div class="row-fluid">
		<div class="span12">
			<h3>Boeking plaatsen</h3>   
	    </div>
	</div>
	<div class="row-fluid">
		<div id="workshop-booking-personal" class="span6 form-column">
			<p class="bold">Persoonlijke gegevens</p>
				<input type="hidden" name="hostname" value="<?php echo $theHostname; ?>" /> 
				<input type="hidden" name="Workshop_id" value="<?php echo $w->Workshop_id; ?>" />
			  <p>
			  <label for="companyName" >Bedrijfsnaam: </label>
	          <input type="text" name="companyName" id="companyName" />
			  </p>
		  	  <p>
		  	  
				<label for="firstname" >Naam: *</label>
				<input type="text" name="firstname" id="firstname" />
			  </p>
			  <p> 
			   <label for="surname" >Achternaam: *</label>
	           <input type="text" name="surname" id="surname" />
			  </p>
			  <p>
			  <label for="street" >Straat: *</label>
	          <input type="text" name="street" id="street" />
			  </p>
			  <p>
			  <label for="number" >Huisnummer: *</label>
	          <input type="text" name="number" id="number_" class="small-input"/>
	          </p>
			  <p>
			  <label for="postcode" >Postcode: *</label>
	          <input type="text" name="postcode" id="postcode" class="small-input" maxlength="7"/>
			  </p>
			  <p>
			  <label for="city" >Plaats: *</label>
	          <input type="text" name="city" id="city" />
			  </p>
			  <p>
			  <label for="email" >E-mail: *</label>
	          <input type="email" name="email" id="email" />
			  </p>
			  <p>
			  <label for="phone" >Telefoon: *</label>
	          <input type="tel" name="phone" id="phone" />
			  </p>
	
		          
	         <p>
	
			  <label for="comment_" >Opmerkingen (bijv. allergi&euml;n):</label>
	          <textarea type="text" name="comment_" id="comment_" ></textarea>
	          </p>	  
			  
			  <p>
			  <br/>
			  <label for="coupon" >Couponcode: </label>
	          <input type="text" name="coupon" id="coupon" />
	          </p>
	
	          
	          
	          <p id="discount-text">
	          
	          </p>
		</div>
	
		<div id="workshop-booking-orderdetails" class="span6 form-column">
			<p class="bold">Boekingsgegevens</p>
	
	
	       <p>
			<label for="persons" >Aantal personen: *</label>
			<input type="text" name="persons" id="persons" class="numeric updates-price" />
		   </p>
		   <p id="not-a-number" id="not-a-number-message">
		   		Vul een getal in. 
		   </p>
		   <p id="not-enough-persons-warning">
		   		U geeft minder mensen op dan het minimale aantal van <?php echo $w->minNumPersons; ?>. Als u doorgaat betaalt u voor <?php echo $w->minNumPersons; ?>.
		   </p>
	
		   <p id="too-many-persons-warning">
		   		Het maximale aantal personen is <?php echo $w->maxNumPersons; ?>. Als u doorgaat boekt u voor <?php echo $w->maxNumPersons; ?>.
		   </p>
		   <p id="too-many-persons-warning2">
		   		Er zijn nog maar NUM plaatsen vrij, als u doorgaat boekt u voor maximaal NUM personen.
		   </p>
		   	   
	       <p>
	        <?php if($hasDates) {  ?>
				<label for="datum" >Datum: *</label>
				<select id="WorkshopDate_id" name="WorkshopDate_id">
				 <?php foreach($w->WorkshopDate as $d) {
			 			if($d->numSubscriptions == null || !isset($d->numSubscriptions)  ) $d->numSubscriptions = 0;
			 			$spotsLeft = $w->maxNumPersons - $d->numSubscriptions;
			 			
					 	if($spotsLeft > 0 && strtotime($d->workshopDate) >= time()){ ?>
				  				<option value="<?php echo $d->WorkshopDate_id; ?>" >
				  					<?php echo date('d-m-Y G:i',strtotime($d->workshopDate)).' (' .$spotsLeft. ' plaats(en) vrij)'; ?>
		  					</option>
					 <?php 
					 	}
				 	} ?>
				</select>
	       
	        <?php } else { ?>
			<label for="datum" >Datum: *</label>
			<input type="text" name="datum" id="datum" class="dateitem"/>
			
		   </p>
		   
		   <p>
		    <label for="deliveryTime">Aanvangsttijd: *</label>
			<select id="deliveryTime" name="deliverOnTime"> 
				<?php foreach($w->WorkshopTime as $k=>$v) { ?>
				<option value="<?php echo substr($v->workshopTime,0,5); ?>"><?php echo substr($v->workshopTime,0,5); ?></option>
				<?php } ?>
			</select> uur
			<?php } ?>
	
		   </p>
		   
		   <p>
		   <span id="availability" >Deze datum is nog beschikbaar.</span>
		   </p>
		   
		   
		   <?php if(count($w->WorkshopExtra) > 0 && !$hasDates) { ?>
		   <p class="bold">Opties</p>
	 	   <p class="checkbox-group">
			<!--<label for="extra" >Extra opties:</label>-->
		   </p>
		    <?php foreach($w->WorkshopExtra as $v) { ?>
		    <p class="checkbox-group">
		      <input type="checkbox" name="WorkshopExtra_id[]" id="updates_price_<?php echo $v->extraPrice; ?>" value="<?php echo $v->WorkshopExtra_id; ?>" class="updates-price" />
		    		<?php echo $v->extraName.' (&euro; '.money_format('%.2n', $v->extraPrice).'pp.)'; ?> 
		    		<!--<img class="tooltip" src="<?php echo PLUGIN_SERVER_URL;?>/info.png" title="<?php echo $v->extraName; ?> - <?php echo $v->extraDesc; ?>" />-->
		    		
		    		<a  data-content="<p class=' '><?php echo $v->extraDesc; ?></p>" 
						 		   rel="popover" 
						 		   data-trigger="hover"
						 		   class="label label-info xtooltip" 
						 		   href="#" 
						 		   
						 		   data-original-title="<?php echo $v->extraName; ?>">info</a>

		    </p>
		    <?php } ?>
		   <?php } ?>
		   
		   
		   <p class="total">
		    <div class="total total-price">Totaalprijs:</div><div class="total-price total">&euro; <span id="the-price">-,--</span></div>
		    <div class="clear"></div>
		   </p>
		   <p class="agree">
		   Na het klikken op 'Boeking bevestigen' is uw boeking geplaatst, en ontvangt u een bevestiging en factuur per e-mail. U kunt tot 2 dagen voor de workshop het aantal personen nog wijzigen (telefonisch).<br/><br/>
		   U kunt, indien gewenst, ook een optie nemen op de workshop. U ontvangt dan nog geen factuur, en nemen wij t.z.t. contact met u op. Deze optie blijft zonder verplichtingen 5 dagen geldig.
		   <?php if(!$hasDates) { ?>
		   <br/><br/>
		   Tot slot kunt u ook een offerte aanvragen, die u dan in uw mailbox ontvangt.
		   <?php } ?>
		   </p>
		   <p>
				<input type="submit" class="form-button order" value="Boeking bevestigen" name="submit"/>
				<input type="submit" class="form-button optie" value="Ik neem een optie" name="option"/>
				<?php if(!$hasDates) { ?>
				<input type="submit" class="form-button quote" value="Offerte aanvragen" name="quote"/>
				<?php } ?>
			</p>
		</div>
	</div>

 		
</div>
</form>

<div class="submitting-message" style="display:none;">
	<!-- placeholder -->
</div>