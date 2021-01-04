function openTab(evt, tabName) {
  var i, ets_tabcontent, ets_tablinks;
  ets_tabcontent = document.getElementsByClassName("ets_tabcontent");
  for (i = 0; i < ets_tabcontent.length; i++) {
    ets_tabcontent[i].style.display = "none";
  }
  ets_tablinks = document.getElementsByClassName("ets_tablinks");
  for (i = 0; i < ets_tablinks.length; i++) {
    ets_tablinks[i].className = ets_tablinks[i].className.replace(" active", "");
  }
  document.getElementById(tabName).style.display = "block";
  evt.currentTarget.className += " active";
}
jQuery(document).ready(function () {

	jQuery('button[data-toggle="tab"]').on('click', function() {
		localStorage.setItem('activeTab', jQuery(this).data('identity'));
	});

	var activeTab = localStorage.getItem('activeTab');
	if(activeTab){
		jQuery('.ets-tabs button[data-identity="' + activeTab + '"]').trigger('click');
	}else{
		jQuery('.ets-tabs button[data-identity="settings"]').trigger('click');;
	}
	jQuery('#disconnect-discord').on('click',function (e) {
		e.preventDefault();
		var userId = jQuery(this).data('user-id');
		jQuery.ajax({
			type:"POST",
			dataType:"JSON",
			url:etsPmproParams.admin_ajax,
            data: {'action': 'disconnect_from_discord','user_id':userId},
			beforeSend:function () {
				jQuery('#image-loader').show();
			},
			success:function (response) {
				if (response.status == 1) {
					location.reload();
				}
			},
			error: function(data) {
				alert('Server error');
		  	}
		});
	});

	jQuery(window).on('load',function (e) {
		e.preventDefault();
		jQuery.ajax({
			type:"POST",
			dataType:"JSON",
			url:etsPmproParams.admin_ajax,
            data: {'action': 'load_discord_roles'},
            beforeSend:function () {
				jQuery('#image-loader').show();
			},
			success:function (response) {
				jQuery('#image-loader').hide();
				jQuery.each(response, function (key, val) {
			        jQuery('.discord-roles').append('<div class="makeMeDraggable" data-role_id="'+val.id+'" >'+val.name+'</div>');
			        makeDrag(jQuery('.makeMeDraggable'));
			    });
			}
		});
	});
	jQuery('#clrbtn').click(function(e) {
	    e.preventDefault();
	      jQuery.ajax({
	      url: etsPmproParams.admin_ajax,
	      type: "POST",
	      data: 'action=ets_clear_logs&',
	      success: function(data) {
	      	if (data.error) {
	            // handle the error
	            alert(data.error.msg);
	        }else{
	        	jQuery('.error-log').html("Clear logs Sucesssfully !");
	        }
	      }
	    });
  	});

	jQuery("#revertMapping").on('click', function(){
  		localStorage.removeItem('mapArray','firstmap_id');
  		location.reload(true);
  	});
  	jQuery( init );

	function init() {
	    jQuery('.makeMeDroppable').droppable( {
	      drop: handleDropEvent
	    } );
	}
	function makeDrag(el) {
	  // Pass me an object, and I will make it draggable
	  el.draggable({
	    revert: "invalid"
	  });
	}
  	function handleDropEvent( event, ui ) {
	    var draggable = ui.draggable;
	    var oldItems = JSON.parse(localStorage.getItem('mapArray')) || [];
	    if(!localStorage.getItem('firstmap_id')){
	    	var firstmap_id = draggable.data('role_id');
	    	localStorage.setItem('firstmap_id',firstmap_id);
		}
	    var newItem = '"level_id_'+jQuery(this).data( 'level_id' )+'"'+':'+'"'+draggable.data('role_id')+'"';
	    oldItems.push(newItem);
	   	var jsonStart = "{";
	    jQuery.each(oldItems, function(key,val){
	    	jsonStart = jsonStart+val+',';
	    });
	    var mappingjson = jsonStart+'"level_id_expired":"'+localStorage.getItem('firstmap_id')+'"}';
	    console.log(mappingjson);
	    jQuery("#maaping_json_val").html(mappingjson);
	    localStorage.setItem('mapArray', JSON.stringify(oldItems));
  	}

  	
	
});
