<?php
	//include_once('config.php');
	global $theHostname;
	global $dealsOnly;
	$params = array(
		'hostname'=>$theHostname,
		'isPublished'=>'true',
		'useNesting'=>'false'
	);
	$dealsClass ="";
	$useDeals=false;
	if($dealsOnly === 'true' || $dealsOnly === '1'){
		$params['deal']='true';
		$dealsClass=" deals";
		$useDeals=true;
	}
	$workshops = fetchWorkshops($params);

// Let's justify to the left, with 14 positions of width, 8 digits of
// left precision, 2 of right precision, withouth grouping character
// and using the international format for the nl_NL locale.
setlocale(LC_MONETARY, 'it_IT');
?>
<?php if($useDeals) { ?>
<script type="text/javascript">
$(window).load(function() {
$('#workshops-widget').cycle({ 
    fx:    'fade', 
    speed:  1000, 
    timeout: 6000
 });
});
</script>
<?php } ?>


<?php if(!$useDeals) { ?>
<div id="workshops" >
	<?php $oddEven = 1; 
		$theNumCols = 3;
		$counter =1;
		foreach($workshops as $w){ 
			$w->isFeatured = false;
			$endrow = ($counter%$theNumCols == 0) ? 'endrow' : '';
			$prediv = ($counter%$theNumCols == 1 || $w->isFeatured) ? '<div class="row-fluid">' : '';
			$postdiv= ($counter%$theNumCols == 0 || $counter == $numFeaturedProducts || $w->isFeatured) ? "</div><!-- row-fluid1 -->" : ''; 
	
			$prediv2 = ($w->isFeatured) ? '<div class="row-fluid">' : '';
			$postdiv2= ($w->isFeatured) ? "</div><!-- row-fluid2 -->" : '';
	
			
			// Foundation grid framework names
			switch($theNumCols){
				case 1:
					$numColName='span12';
				break;
				case 2:
					$numColName='span6';
				break;
				case 3:
					$numColName='span4';
				break;
				case 4:
					$numColName='span3';
				break;
				case 6:
					$numColName='span2';
				break;
				case 12: 
					$numColName='span1';	
				break;
				default:
					$numColName='span4';
				break;
			}
			
			$spanImg = '';
			$spanDate = '';
			if($w->isFeatured){
				$numColName = 'span12';
				$spanImg = 'span8';
				$spanDate = 'span4';
			}		
		
		
		
		?>
		<?php echo $prediv; ?>
		<div class="workshop overview  <?php echo $numColName; ?>">
				
	
			<a href="/workshops/<?php echo $w->Workshop_id; ?>"><img alt="<?php echo $w->workshopName; ?>"  src="<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'.$w->imageWorkshop; ?>" /></a>
			
			<h3 class="workshop-title"><a href="/workshops/<?php echo $w->Workshop_id; ?>"><?php echo $w->workshopName; ?></a> <?php if($w->deal) { ?><span class="deal">Aanbieding</span><?php } ?></h3>
			<p>&euro;<?php echo money_format('%.2n', $w->workshopPrice); ?> pp.</p>
			
			<p class="workshop-excerpt"><?php echo substr($w->workshopDesc, 0, 115); ?>... 
			<a href="/workshops/<?php echo $w->Workshop_id; ?>">Lees meer &rarr;</a>
			
			</p>
		</div>
		<?php echo $postdiv; 	
		
		if(!$w->isFeatured){
			$counter++;
		}
		
		?>
	
	<?php } ?>
	</div>	
</div>	
<?php 

} 

else { /* widget below */ ?>

	<div id="workshops-widget">
	<?php foreach($workshops as $w) { ?>	
		<div class="workshop-widget">
			<a href="/workshops/<?php echo $w->Workshop_id; ?>"><img src="<?php echo SYSTEM_URL_CATERINGSOFTWARE.'/'.$w->imageWorkshop; ?>" /></a>
			
			<h4 class="workshop-title-widget"><a href="/workshops/<?php echo $w->Workshop_id; ?>"><?php echo $w->workshopName; ?></a></h4>
			<p>&euro;<?php echo money_format('%.2n', $w->workshopPrice); ?> pp.</p>
			
			<p><?php echo substr(nl2br($w->workshopDesc), 0, 140); ?>...</p>
			
			<p><a href="/workshops/<?php echo $w->Workshop_id; ?>">Lees meer / direct boeken &rarr;</a></p>		
		</div>
	<?php } ?>
	</div>

<?php 
	} 
?>
