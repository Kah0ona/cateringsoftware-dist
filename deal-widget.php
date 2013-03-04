<?php 
	global $theHostname;	
	//$url = WP_PLUGIN_URL.'/cateringsoftware-dist/fetch-deals.php?hostname='.$theHostname;
	$url = BASE_URL_CATERINGSOFTWARE.'/packages?hostname='.$theHostname.'&packageDeal=true';
?>
<script type="text/javascript">
	var hostname = '<?php echo $theHostname; ?>';
	var url_ = '<?php echo $url;?>';
	
	$(document).ready(function(){
		$.ajax({
			url: url_,
			jsonpCallback: 'dealCallback',
			jsonp: 'callback',
			dataType: 'jsonp'
		});	
	});
	
	function dealCallback(json){
		if(json.length == 0){
			//
		}
		else {
			$('#deals-container').removeClass('hidden');

			var renderer = Tempo.prepare('deals-container').notify( function (event) {
				if (event.type === TempoEvent.Types.RENDER_COMPLETE) {
					$('#deals-container').cycle({
						timeout : 7000
					});
				}
			});
			renderer.starting();
			
			renderer.render(json);
		}	
	}
</script>
<!-- Tempo.js template -->
<div id="deals-container" class="hidden" style="height: 340px;">
	<!-- these are cycled using jquery cycle -->
	<div class="row-fluid deal-entry" data-template style="display: none;">
		<div class="span12">
			<img src="<?php echo SYSTEM_URL_CATERINGSOFTWARE; ?>/{{imagePkg}}" width="100" style="margin: 5px;" />
			<h3>{{pkgName}}</h3>
			<p>&euro; {{packagePrice}}</p>
			<p>{{pkgDesc | truncate 150 }}</p>
			<p><a href="/packages/{{Package_id}}">Lees meer</a></p>
		</div>
	</div>
</div>
<div style="clear:both"></div>