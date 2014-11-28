<?php
	include_once('config.php');
	global $theHostname;
	global $dealsOnly;
	$params = array(
		'hostname'=>$theHostname
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
	$workshopsMap = filterWorkshops($workshops); //only show the ones with a date >= today, and sort on date.

	function filterWorkshops($workshops) {
		$ret = array();
		
		foreach($workshops as $w) {
			if(count($w->WorkshopDate) > 0) {
				$max = $w->maxNumPersons;
				foreach($w->WorkshopDate as $d){
					if(/*($max - $d->numSubscriptions) > 0 &&*/ strtotime($d->workshopDate) >= time() ) { //there are spots left, add to $ret map.
						$v = clone $w;
						if(($max - $d->numSubscriptions) == 0)
							$v->isFull = true;
						

							
						$v->numSubscriptions = $d->numSubscriptions;
						$ret[$d->workshopDate] = $v; 
					}
				}
			}	
			else { //skip this one, no dates set
				continue;
			}
		}
		
		//sort array on date key, ASC
		ksort($ret);
		
		return $ret;
	}
?>

<script type="text/javascript">
	jQuery(document).ready(function($){
		var more = true;
		$('.agenda-show-more').click(function(event){
			event.preventDefault();
			$('.agenda-item-more').toggleClass('hidden');
			if(more){
				$('.agenda-show-more').html('Toon minder data');
				more=false;
			}
			else {
				$('.agenda-show-more').html('Toon meer data');				
				more=true;
			}
		});
	});
</script>
<div id="cooking-agenda">
	<p>
		Inschrijven op de kookagenda workshops kan al vanaf 1 persoon.
	</p>
	<ul class="agenda-item">
	
	<?php 
		$numShown=4;		
		$c = 1;
		foreach ($workshopsMap as $k=>$w) { 
		if($c > $numShown) $hidden = 'agenda-item-more hidden';
		else $hidden = '';
	?>
		<li class="<?php echo $hidden; ?>">
			<?php if($w->isFull): ?>
			&bull;  <?php echo date('d-m-Y, G:i',strtotime($k)); ?>  /&nbsp; <?php echo $w->workshopName; ?> <span class="workshop-full">VOL</span>
			<?php else: ?>	
			<a href="/workshops/<?php echo $w->Workshop_id; ?>" >&bull;  <?php echo date('d-m-Y, G:i',strtotime($k)); ?>  /&nbsp; <?php echo $w->workshopName; ?> 
				(<?php echo ($w->maxNumPersons - $w->numSubscriptions); ?> plaatsen vrij) &rarr;</a>
			<?php endif; ?>

		</li>
	<?php $c++;
		} 
	?>
	</ul>
	<?php if($c > $numShown) : //there are hidden items make 'show more' button. ?>
	<p>
		<a href="#" class="agenda-show-more">Toon meer data</a>
	</p>
	<?php endif; ?>
	
</div>
