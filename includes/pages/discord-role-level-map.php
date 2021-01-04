<?php
$pmpro_levels = pmpro_getAllLevels( true, true );
?>
<div class="notice notice-warning ets-notice">
  <p>Drag and Drop the Discord Roles over to the PMPRO Levels</p>
</div>
<div class="row-container">
  <div class="ets-column" style="background-color:#aaa;">
    <h2>Discord Roles</h2>
    <hr>
    <div class="discord-roles">
      <img id="image-loader" src= <?php echo ETS_PMPRO_DISCORD_URL."assets/images/Spin-Preloader.gif;"?> >
    </div>
  </div>
  <div class="ets-column" style="background-color:#bbb;">
    <h2>PMPRO Levels</h2>
    <hr>
    <div class="pmpro-levels">
    <?php 
      foreach ($pmpro_levels as $key => $value) {
        if($value->allow_signups != 0 ):
        ?>
        <div class="makeMeDroppable" data-level_id="<?php echo $value->id; ?>" ><?php echo $value->name; ?></div>
        <?php
        endif;
      }
    ?>
    </div>
  </div>
</div>
<form method="post" action="#">
  <div class="mapping-json">
    <textarea id="maaping_json_val" name="ets_discord_role_mapping"><?php if ( isset( $ets_discord_roles ) )echo stripslashes( $ets_discord_roles );?></textarea>
  </div>
  <div class="bottom-btn">
    <button type="submit" name="submit" value="ets_submit" class="button">
      <?php echo __( "Submit", "ets_pmpro_discord" );?>
    </button>
    <button id="revertMapping" class="button">
      <?php echo __( "Revert Mapping", "ets_pmpro_discord" );?>
    </button>
  </div>
</form>