<?php
	global $theHostname;
	$url = BASE_URL_CATERINGSOFTWARE.'/getmenus?hostname='.$theHostname;
	$url_post = SUBMIT_EMAIL_URL;
?>
<script type="text/javascript">
var recipeData = null;
$(document).ready(function(){

	var url_ = '<?php echo $url;?>';
	var hostname = '<?php echo $theHostname; ?>';
	var url_post = '<?php echo $url_post;?>';
	$('#email-required').hide();
	
	if(getEmailCookie()){
		$('#email-container').hide();
	}
	else {
		$('#email-container').show();
	}
	
	$('#recipe-go').click(function(event){
		$('#email-required').hide();
		// fetch recipe using ajax call (jsonp)
		var email = $('#email').val();
		if((email == null || email == "") && !getEmailCookie()){
			$('#email-required').show();
			return;
		}
		
		window.location = url_ + '';
		
		$.ajax({
				url: url_,
				data: { menuCode : $('#recipePassword').val(), numPersons },
				jsonpCallback: 'recipeCallback',
				jsonp: 'callback',
				dataType: 'jsonp'
			});	
		//post email address to backend
		$.ajax({
	  			type: 'POST',
				url: url_post,
				data: { "mlEmail" : $('#email').val(), "hostname" : hostname, "source" : "Receptenpagina" },
				complete: function(data){
					setEmailCookie($('#email').val());
				},
				dataType: 'json'
			});	
			
	});
	
	//remove afterwards
/*	$.ajax({
			url: url_,
			data: { menuCode : '' },
			jsonpCallback: 'recipeCallback',
			jsonp: 'callback',
			dataType: 'jsonp'
		});	*/
	 ///remove afterwards
	
	$('#numPersonsRecipe2').click(recalculateNumPersons);
	$('#numPersonsRecipe').val('4');
	$('#shopping-list-num-persons').html('4');
	
	
});

function recalculateNumPersons(event){
	generateShoppingList();
	var num = parseInt($('#numPersonsRecipe').val());
	$('#shopping-list-num-persons').html(num);
	//select all ingredient li's
	$('.amount-container-original').each(function(){
		var curAmount = parseFloat($(this).html());

		//get the id
		var id = $(this).attr('data');

		//split it around -
		var pieces = id.split('-');
		var dishId = $(this).parent().parent().attr('id').split('-')[1];
		var ingredientId = pieces[3];
		//console.log(id);
		
		//look up their corresponding current number persons

		var curNumElt = $('#num-persons-recipe-'+dishId);
		var origElt = $('#num-persons-recipe-original-'+dishId);
		var curNum = parseInt(origElt.html());
		
		var total = (num / curNum) * curAmount;
		console.log("("+num +"/" + curNum +") * " + curAmount +" = "+ total);
		//do the math		
		//console.log("updating: "+'#ingredients-'+dishId+' .amount-container-'+ingredientId);
		$('#ingredients-'+dishId+' .amount-container-'+ingredientId).html(formatNumber(total));

		//update the num persons table
		curNumElt.html(num);
	});
	
}

function formatNumber(price){
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

	return price.formatMoney(1,',','.');
}


function recipeCallback(json){
		recipeData = json;
		if(json.length == 0){
			alert('Deze receptcode is niet geldig, of er konden geen recepten gevonden worden met deze code.');			
		}
		else {
			$('#numPersonsContainer').removeClass('hidden');

			var renderer = Tempo.prepare('menu-container');
			renderer.render(json);
				
			$('<div class="page-break">&nbsp;</div>').insertAfter('.course');	

			$('#recipe_form').hide();
			$('#print-workshop').show();
			$('#shopping-list').show();
			recalculateNumPersons(null);
		}
}

function generateShoppingList(){
	//remap the recipeData url to aggregate over the ingredients and sum the amounts 
	var numPersons = parseInt($('#numPersonsRecipe').val());
	
	var ingredientsMap = {};

	var menu = recipeData[0];	
	if(menu != null && menu != undefined) {
		var courses = menu.Course;
		if(courses != null && courses!= undefined){
			for(var i = 0; i < courses.length; i++){
				var dishes = courses[i].Dish;
				if(dishes != undefined && dishes != null){
					for(var j = 0; j < dishes.length; j++){
						var recipeNumPersons = parseInt(dishes[i].recipeNumPersons);
						var ingredients = dishes[j].Ingredient;
						if(ingredients != null && ingredients != undefined){
							for(var k = 0; k < ingredients.length; k++){
								if(ingredientsMap[ingredients[k].Ingredient_id] == null || 
								   ingredientsMap[ingredients[k].Ingredient_id] == undefined){
									ingredientsMap[ingredients[k].Ingredient_id] = ingredients[k];
									ingredientsMap[ingredients[k].Ingredient_id].amount = 0
									//console.log("initializing.." + ingredients[k].ingredientName);
								}
									/*console.log("-");
									console.log("name: "+ingredients[k].ingredientName);
									console.log("amount: "+									
												ingredientsMap[ingredients[k].Ingredient_id].amount )
									console.log("dishAmount: "+ingredients[k].dishAmount)
									console.log("recipeNumPersons: "+recipeNumPersons)
									console.log("numPersons: "+numPersons)									
									console.log("-");									
									*/
									ingredientsMap[ingredients[k].Ingredient_id].amount 
													+=  ((parseFloat(ingredients[k].dishAmount) / parseInt(recipeNumPersons)) 
															* numPersons);
															
									/*console.log("new amount: "+									
												ingredientsMap[ingredients[k].Ingredient_id].amount )
															
									*/
							}
						}
					}
				}
			}			
		}

	}
	//console.log(ingredientsMap);
	
	var html = ""
	for(var ingredientId in ingredientsMap){
		var ingredient = ingredientsMap[ingredientId];
		if(ingredient.amount > 0){
			html+= "<li>"+ingredient.amount+" "+ingredient.ingredientDefaultUnit+" "+ingredient.ingredientName+"</li>";
		}
	}
	$('#shopping-list-placeholder').html(html);
	
}

function setEmailCookie(emailString){
	var exdate = new Date();
	exdate.setDate(exdate.getDate() + 365); //stel in voor een jaar
	var c_value=escape(emailString) + "; expires="+exdate.toUTCString();
	document.cookie="email_field2" + "=" + c_value;	 
}

function getEmailCookie(){
	var i,x,y,ARRcookies=document.cookie.split(";");
	for (i=0; i<ARRcookies.length; i++) {
	  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
	  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
	  x=x.replace(/^\s+|\s+$/g,"");
	  if(x == "email_field2"){
	  	var z = unescape(y);
		return (z!=null && z!="");
      }
  }
  return false;
}

</script>





<div class="recipe_container">
	<div id="recipe_form" class="row-fluid">
		<div class="span12">
			<p id="email-required">Vul uw email-adres in</p>
			<form action="#" method="POST" >
			<p id="email-container">
				<label for="email" >Email-adres: *</label>
				<input type="text" name="email" length="255" size="25" id="email"  />
			</p>
			<p>
				<label for="recipePassword" >Receptcode:</label>
				<input type="text" name="recipePassword" length="255" size="25" id="recipePassword"  />
			</p>
			</form>
			<div class="read-more workshop-button workshop-back-to-overview" id="recipe-go" style="margin-right: 10px" >
				<a href="#" onclick="return false;">Recept opvragen &darr;</a>
			</div>
		</div>
	</div>
	<div id="print" class="row-fluid screen-only">
		<div class="span6">
			<form class="form-inline">
				<div class="control-group hidden" id="numPersonsContainer">
					<p>
					Met de knop hieronder kunt u het recept omrekenen voor een willekeurig aantal personen.
					</p>
	
					<label class="control-label" for="numPersonsRecipe" style="font-size: 1.3em">Aantal personen: </label>			
					<div class="controls">	
						<input type="text" name="numPersonsRecipe"  class="input-large span3 address-line" id="numPersonsRecipe" />
						<input type="button" name="recalc"  value="Omrekenen" class="btn btn-warning address-line" id="numPersonsRecipe2" />
					</div>	
				</div>	
			</form>
		</div>
		<div class="span6">				
			<div class="read-more workshop-button workshop-back-to-overview" id="print-workshop" style="float:right;display: none">
					<a href="#" onclick="window.print();return false;">Afdrukken</a>
					
			</div>
		</div>
		
	</div><!-- row-fluid -->
	<!--
	<div class="row-fluid" style="page-break-after: always;">
		<div class="span12" id="shopping-list-modal">
		 	<h3>Boodschappenlijst</h3>
		 	 <p>Uw boodschappenlijst voor <span id="shopping-list-num-persons"></span> personen:</p>
			 <p>
			 	<ul id="shopping-list-placeholder"></ul>
			 </p>
		</div>
	</div>
	-->
	<!-- /row-fluid -->
	
	
	<div id="menu-container" class="row-fluid">
		<div class="span12" id="menu-data" data-template style="display: none;">
			<div class="row-fluid">
				<div class="span12">
					<h2>{{ menuName }}</h2>
					<p>{{ menuDescription }}</p>
				</div><!-- span12 -->
			</div><!-- row-fluid -->
			<div data-template-for="Course" class="course row-fluid">
				<div class="span12">
					<div class="row-fluid">
						<div class="span12">
							<h3>{{courseName}} <span class="label label-warning">{{courseType}}</span></h3>
							<p>{{courseDesc}}</p>
						</div> <!-- span12 -->
					</div> <!-- row-fluid -->
					<div data-template-for="Dish" class="recipe row-fluid">
						<div class="span12">
							<div class="row-fluid">
								<h4>Recept: {{dishName}}</h4>
							</div><!-- row-fluid -->
							<div class="row-fluid">
								<div class="span4 ingredients-container">
									<h5>Ingredi&euml;nten</h5>
									<ul class="ingredients" id="ingredients-{{Dish_id}}">
										<li data-template-for="Ingredient">{{ingredientName}} (<span class="amount-container amount-container-{{Ingredient_id}}" >{{dishAmount}}</span>
<span class="amount-container-original hidden" data="amount-container-original-{{Ingredient_id}}">{{dishAmount}}</span> {{ingredientDefaultUnit}})</li>		
									</ul>	
								</div><!-- span4 -->
								<div class="span8">
									<div class="row-fluid">
										<div class="recipe-contents span12">
											<img src="<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'; ?>{{imageDish}}" alt="{{dishName}}" title="{{dishName}}" class="recipe-image" />
												<span class="hidden" id="num-persons-recipe-original-{{Dish_id}}">{{recipeNumPersons}}</span>
											    <table class="table recipe-table">
											    <thead>
											    <tr>
											    <th><i class="icon-user"></i> Aantal personen:</th>
											    <th><i class="icon-time"></i> Kooktijd:</th>
											    </tr>
											    </thead>
											    <tbody>
											    <tr>
											    <td id="num-persons-recipe-{{Dish_id}}">{{recipeNumPersons}}												    
												    
												    
											    </td>
											    <td>{{cookingTime}} {{cookingTimeUnit}}</td>
											    </tr>
											    </tbody>
											    </table>
										
											 <p><strong></strong> </p>
											 <p><strong></strong>  </p>

											 <p class="recipe-text">{{recipe}}</p>
										</div><!-- span12 -->
									</div><!-- row-fluid -->
								</div><!-- span8 -->
							</div><!-- row-fluid -->
						</div><!-- span12 -->
					</div><!-- row-fluid -->	
				</div><!-- span12 -->
			</div><!-- row-fluid -->
		</div><!-- span12 -->
	</div><!-- row-fluid -->
</div><!-- recipe-container -->
