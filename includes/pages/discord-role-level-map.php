
<style type="text/css">
* {
  box-sizing: border-box;
}

/* Create two equal columns that floats next to each other */
.column {
  float: left;
  width: 50%;
  padding: 10px;
  height: 300px; /* Should be removed. Only for demonstration */
}

/* Clear floats after the columns */
.row:after {
  content: "";
  display: table;
  clear: both;
}
</style>
<script type="text/javascript">

jQuery( init );

function init() {
  jQuery('.makeMeDraggable').draggable();
  jQuery('.makeMeDroppable').droppable( {
    drop: handleDropEvent
  } );
}

function handleDropEvent( event, ui ) {
  var draggable = ui.draggable;
  alert( 'The square with ID "' + draggable.data('role_id') + '" was dropped onto me!' );
}

</script>
<div class="row">
  <div class="column" style="background-color:#aaa;">
    <h2>Discord Roles</h2>
    <hr>
    <div class="makeMeDraggable" style="background-color:#abb;" data-role_id="2">hii</div>
  </div>
  <div class="column" style="background-color:#bbb;">
    <h2>PMPRO Levels</h2>
    <hr>
    <div class="makeMeDroppable" style="background-color:#baa;" >hello</div>
  </div>
</div>