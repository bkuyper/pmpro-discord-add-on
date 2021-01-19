<?php
$user_id = get_current_user_id();
$pmpro_levels = pmpro_getAllLevels( true, true );
$membership_level = pmpro_getMembershipLevelForUser( $user_id );
$default_role = get_option( 'ets_discord_default_role_id' );
$upon_expiry_s = get_option( 'upon_expiry' );
$upon_cancel_s = get_option( 'upon_cancel' );
?>
<div class="notice notice-warning ets-notice">
  <p><?php echo __( "Drag and Drop the Discord Roles over to the PMPRO Levels", "ets_pmpro_discord" );?></p>
</div>
<div class="row-container">
  <div class="ets-column discord-roles-col">
    <h2><?php echo __( "Discord Roles", "ets_pmpro_discord" );?></h2>
    <hr>
    <div class="discord-roles">
      <img id="image-loader" src= <?php echo ETS_PMPRO_DISCORD_URL."assets/images/Spin-Preloader.gif;"?> >
    </div>
  </div>
  <div class="ets-column">
    <h2><?php echo __( "PMPRO Levels", "ets_pmpro_discord" );?></h2>
    <hr>
    <div class="pmpro-levels">
    <?php 
      foreach ($pmpro_levels as $key => $value) {
        if($value->allow_signups != 0 ):
        ?>
          <div class="makeMeDroppable" data-level_id="<?php echo $value->id; ?>" ><span><?php echo $value->name; ?></span></div>
        <?php
        endif;
      }
    ?>
    </div>
  </div>
</div>
<form method="post" action="#">
  <table class="form-table" role="presentation">
    <tbody>
      <tr>
        <th scope="row"><label for="defaultRole"><?php echo __( "Default Role", "ets_pmpro_discord" );?></label></th>
        <td>
          <input type="hidden" id="selected_default_role" value="<?php echo $default_role; ?>">
          <select id="defaultRole" name="defaultRole">
          </select>
          <p class="description"><?php echo __( "This Role will be assigned to all level members", "ets_pmpro_discord" );?></p>
        </td>
      </tr>
      <tr>
        <th scope="row"><label><?php echo __( "Upon Expiry", "ets_pmpro_discord" );?></label></th>
        <td>
          <fieldset>
          <label><input type="radio" name="upon_expiry" value="kick" <?php if( empty($upon_expiry_s) || $upon_expiry_s == 'kick' ){ echo 'checked="checked"'; }?> > <span><?php echo __( "Kick Member", "ets_pmpro_discord" );?></span></label><br>
          <label><input type="radio" name="upon_expiry" value="default" <?php if( $upon_expiry_s == 'default' ){ echo 'checked="checked"'; }?> > <span><?php echo __( "Assign Default Role", "ets_pmpro_discord" );?></span></label>
          </fieldset>
        </td>
      </tr>
      <tr>
        <th scope="row"><label><?php echo __( "Upon Cancel", "ets_pmpro_discord" );?></label></th>
        <td>
          <fieldset>
          <label><input type="radio" name="upon_cancel" value="kick"  <?php if( empty($upon_cancel_s) || $upon_cancel_s == 'kick' ){ echo 'checked="checked"'; }?> > <span><?php echo __( "Kick Member", "ets_pmpro_discord" );?></span></label><br>
          <label><input type="radio" name="upon_cancel" value="default" <?php if( $upon_cancel_s == 'default' ){ echo 'checked="checked"'; }?> > <span><?php echo __( "Assign Default Role", "ets_pmpro_discord" );?></span></label>
          </fieldset>
        </td>
      </tr>
    </tbody>
  </table>
  
    
    
    <br>
  <div class="mapping-json">
    <textarea id="maaping_json_val" name="ets_discord_role_mapping"><?php if ( isset( $ets_discord_roles ) )echo stripslashes( $ets_discord_roles );?></textarea>
  </div>
  <div class="bottom-btn">
    <button type="submit" name="submit" value="ets_submit" class="button">
      <?php echo __( "Submit", "ets_pmpro_discord" );?>
    </button>
    <button id="revertMapping" name="flush" class="button">
      <?php echo __( "Flush Settings", "ets_pmpro_discord" );?>
    </button>
  </div>
</form>