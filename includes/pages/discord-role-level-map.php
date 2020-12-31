<?php
$pmpro_levels = pmpro_getAllLevels( true, true );
?>
<div class="row-container">
  <div class="ets-column" style="background-color:#aaa;">
    <h2>Discord Roles</h2>
    <hr>
    <div class="discord-roles">
    </div>
  </div>
  <div class="ets-column" style="background-color:#bbb;">
    <h2>PMPRO Levels</h2>
    <hr>
    <div class="pmpro-levels">
    <?php 
      foreach ($pmpro_levels as $key => $value) {
        ?>
        <div class="makeMeDroppable" data-level_id="<?php echo $value->id; ?>" ><?php echo $value->name; ?></div>
        <?php
      }
    ?>
    </div>
  </div>
</div>
<div class="mapping-json">
  <textarea id="maaping_json_val"></textarea>
</div>
<div class="bottom-btn">
  <button id="revertMapping" class="button">Revert Mapping</button>
</div>