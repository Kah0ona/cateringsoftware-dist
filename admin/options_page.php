<div class="wrap">
 <h2>CateringSoftware instellingen</h2>

 <form method="post" action="options.php"> 
  <?php settings_fields('cateringsoftware_options'); ?>
  <?php do_settings_sections( 'catering-software' ); ?>

  <?php submit_button(); ?>
 </form>
</div>