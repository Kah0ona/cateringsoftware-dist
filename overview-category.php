<?php
include_once('config.php');
include_once('functions.php');
global $theHostname;
global $categoryTitleOrder;
//print_r($categoryTitleOrder);
$arr = array(
	'hostname'=>$theHostname
);
if($_GET['id'] != null && !$categoryDetailHasLoaded){
	$arr['Category_id'] = $_GET['id'];
}
else {
	$arr['useNesting']='false';
}


$cats = fetchCategories($arr);



//order by group title, and remap
$map = Array();
foreach($cats as $c){
	if($c->groupTitle != null){
		$t = trim($c->groupTitle);
		if($map[$t] == null)
			$map[$t] = Array();
			
		$map[$t][] = $c;
	}
	else {
		if($map['nogroup'] == null)
			$map['nogroup'] = Array();
			
			
		$map['nogroup'][] = $c;
	}
}

$sortedMap = Array();
//use guide map to sort
if($categoryTitleOrder != null){
	foreach($categoryTitleOrder as $title){
		$sortedMap[$title] = $map[$title];
	}
}
else {
	$sortedMap = $map;
}

//print_r($sortedMap); exit;
/*

*/

?>
<ul class="categories">
<?php
foreach ($sortedMap as $group=>$cats){
?>
	<?php if($group == 'nogroup'){ //ungrouped ?>
		<?php foreach($map['nogroup'] as $cat) : ?>
			<li class="category-item category-package"><a href="/categories/<?php echo $cat->Category_id; ?>#<?php echo $cat->categoryName; ?>"><?php echo $cat->categoryName; ?></a></li>
		<?php endforeach; ?>
	<?php } else { ?>
		<li class="category-title"><?php echo $group; ?></li>
		<li class="category dropdown-wrap">
			<ul>
			<?php foreach($map[$group] as $cat) : ?>
				<li class="category-item category-package"><a href="/categories/<?php echo $cat->Category_id; ?>#<?php echo $cat->categoryName; ?>"><?php echo $cat->categoryName; ?></a></li>
			<?php endforeach; ?>
			</ul>
		</li>
	<?php } ?>
<?php
}
?>
</ul>