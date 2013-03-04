<?php
	//include_once('config.php');
	global $theHostname;
	global $theType;
	global $theNumCols;
	$params = array(
		'hostname'=>$theHostname,
	);
	$dealsClass ="";
	$cats = fetchCategories($params);

	//print_r($packages);
	 

// Let's justify to the left, with 14 positions of width, 8 digits of
// left precision, 2 of right precision, withouth grouping character
// and using the international format for the nl_NL locale.
setlocale(LC_MONETARY, 'it_IT');
?>

<ul class="categories">
<?php foreach($cats as $w){ ?>
	<li class="category-title"><?php echo $w->categoryName; ?></li>
	<li class="category dropdown-wrap">
			<ul>
			<?php if(count($w->Package) > 0){ ?>
				<?php foreach($w->Package as $pkg){ ?>
					<li class="category-package">
						<a href="/packages/<?php echo $pkg->Package_id; ?>#<?php echo urlencode($pkg->pkgName); ?>"><?php echo $pkg->pkgName; ?></a>
					</li>	
				<?php } ?>
			<?php } ?>
			
			<?php if(count($w->Dish) > 0){ ?>
				<?php foreach($w->Dish as $dish){ ?>
					<li class="category-package">
						<a href="/products/<?php echo $dish->Dish_id; ?>#<?php echo urlencode($dish->dishName); ?>"><?php echo $dish->dishName; ?></a>
					</li>	
				<?php } ?>
			<?php } ?>
			<?php if(count($w->Material) > 0){ ?>
				<?php foreach($w->Material as $mat){ ?>
					<li class="category-package">
						<a href="/materials/<?php echo $mat->Material_id; ?>#<?php echo urlencode($mat->materialName); ?>"><?php echo $mat->materialName; ?></a>
					</li>	
				<?php } ?>
			<?php } ?>
		</ul>
	</li>
<?php } ?>
</ul>

