<?php 
/* registers and builds the admin menu in the backend */

add_action( 'admin_menu', 'cateringsoftware_settings_menu' );
add_action( 'admin_init', 'cateringsoftware_register_settings' );
function cateringsoftware_settings_menu(){
		add_options_page( 'CateringSoftware opties', 
						  'CateringSoftware', 
						  'manage_options', 
						  'catering-software', 
						  'cateringsoftware_options' );
}
	
function cateringsoftware_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	echo executeFile('admin/options_page.php');
}

function cateringsoftware_register_settings() {  
  register_setting( 'cateringsoftware_options', 'cateringsoftware_options' ); //store all CS settings in one array
  add_settings_section('cateringsoftware_main_options', 'Instellingen', 'catsoft_section_text', 'catering-software');

  add_cs_field('hostname','Hostname:');
  add_cs_field('address','Adres van uw zaak (formaat: Kalverstraat 12 1234AB Amsterdam):');
  add_cs_field('cart_class', 'CSS-class van het winkelwagentje:');
  add_cs_field_select('region','Land:', array('nl'=>'Nederland','be'=>'Belgi&euml;'));
  add_cs_field_boolean('use_formula','Gebruik bezorgformule:'); 
  add_cs_field_boolean('prices_incl_vat','Prijzen staan inclusief BTW in CateringSoftware'); 
  
  add_cs_field_boolean('allow_takeaway','Afhalen mogelijk?');
  add_cs_field_boolean('allow_waitress','Bediening mogelijk?');
  add_cs_field_boolean('use_discount_table','Gebruik kortingstabel?');
  add_cs_field_select('cart_display_mode','Winkelwagen tonen als:', array('dropdown'=>'Dropdown','block'=>'In sidebar')); 
  add_cs_field_select('render_button', 'Toon knop in wooncheck?', array('yes'=>'Ja', 'no'=>'Nee'));
  add_cs_field('delivery_text','Bezorgtekst in woonplaatscheck (mogelijke variabelen: _total_, _deliverycosts_,_freedelivery_)', 'text','100');
  add_cs_field('deliver_text_nearby', 'Bezorgtekst indien dichtbij / gratis bezorgen:');
  add_cs_field('cart_text','Tekst in mandje indien in dropdown');
  add_cs_field('checkout_link','Tekst voor link naar checkout page in mandje');
  add_cs_field('package_overview_metadesc','Meta-desc pakkettenoverzicht', 'text','150');
  add_cs_field('product_overview_metadesc','Meta-desc productenoverzicht', 'text','150');
  add_cs_field('material_overview_metadesc','Meta-desc materialenoverzicht', 'text','150');
  add_cs_field('category_overview_metadesc','Meta-desc categorie&euml;noverzicht', 'text','150');

}

function add_cs_field($name, $title, $type='text', $size='40'){
	add_settings_field('cateringsoftware_'.$name, $title, 'cateringsoftware_add_setting_field', 'catering-software', 'cateringsoftware_main_options', 
		array('name'=>$name, 'type'=>$type, 'size'=>$size)
	);
}

function add_cs_field_boolean($name,$title){
		add_settings_field('cateringsoftware_'.$name, $title, 'cateringsoftware_add_setting_field_boolean', 'catering-software', 'cateringsoftware_main_options', 
		array('name'=>$name)
	);
}

function add_cs_field_select($name,$title,$values){
	add_settings_field('cateringsoftware_'.$name, $title, 'cateringsoftware_add_setting_field_select', 'catering-software', 'cateringsoftware_main_options', 
		array('values'=>$values, 'name'=>$name)
	);
}


function catsoft_section_text(){
	echo '<p>Hieronder kunt u diverse globale instellingen doen voor de CateringSoftware webshop module. Sommige instellingen zijn van technische aard, dus onze werknemers zullen dit voor u instellen.</p>';
}

function cateringsoftware_add_setting_field($args) {
	$name = $args['name'];
	$size = $args['size'];
	$type = $args['type'];
	$options = get_option('cateringsoftware_options');
	echo "<input id='cateringsoftware_".$name."' name='cateringsoftware_options[".$name."]' size='".$size."' type='".$type."' value='{$options[$name]}' />";
}

function cateringsoftware_add_setting_field_boolean($args){
	$name = $args['name'];
	$options = get_option('cateringsoftware_options');
	$checked = $options[$name] == 'true' ? 'checked="checked"' : '';
	
	echo '<input type="checkbox" name="cateringsoftware_options['.$name.']" value="true" '.$checked.' />';
}

function cateringsoftware_add_setting_field_select($args){
	$values = $args['values'];
	$name = $args['name'];

	$options = get_option('cateringsoftware_options');
	$ret = '<select name="cateringsoftware_options['.$name.']">';
	foreach($values as $k=>$v){
		$selected="";
		if($options[$name] == $k)
			$selected = 'selected="selected"';
			
		$ret .= '<option value="'.$k.'" '.$selected.'>';
		$ret .= ($v == null) ? $k : $v;
		$ret .= '</option>';
	}	
	$ret .= '</select>';
	
	echo $ret;
}


?>