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

	jQuery.ajax({
		type:"POST",
		dataType:"JSON",
		url:etsPmproParams.admin_ajax,
        data: {'action': 'load_discord_roles'},
        beforeSend:function () {
			jQuery('#image-loader').show();
		},
		success:function (response) {
			jQuery.each(response, function (key, val) {
				if(key != 'previous_mapping'){
			        jQuery('.discord-roles').append('<div class="makeMeDraggable" data-role_id="'+val.id+'" >'+val.name+'</div>');
			        makeDrag(jQuery('.makeMeDraggable'));
			    }
		    });
		    var mapjson = localStorage.getItem('mappingjson') || response.previous_mapping;
			jQuery("#maaping_json_val").html(mapjson);
			jQuery.each(JSON.parse(mapjson), function(key,val){
		    	if(key != 'level_id_expired'){
			    	/*console.log(key+' '+val+'\n');*/
			    	var arrayofkey = key.split('id_');
			    	console.log('data-level_id="'+arrayofkey[1]+'"\n');
				   jQuery('*[data-level_id="'+arrayofkey[1]+'"]').append(jQuery('*[data-role_id="'+val+'"]')).attr( 'data-drop-role_id', val).find('span').css({'order':'2'});
				   jQuery('*[data-role_id="'+val+'"]').css({'width':'100%','left': '0','top':'0','margin-bottom':'0px','order':'1'}).attr( 'data-level_id' ,arrayofkey[1]);
				}
		    });
		},
		complete: function(){
			jQuery('#image-loader').hide();
		}
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
  		localStorage.removeItem('mapArray','firstmap_id','mappingjson');
  		location.reload(true);
  	});

  	jQuery( init );

	function init() {
	    jQuery('.makeMeDroppable').droppable( {
	      drop: handleDropEvent
	    } );
	    jQuery('.discord-roles-col').droppable( {
	      drop: handlePreviousDropEvent,
	      hoverClass: 'hoverActive',  
	    } );
	}
	function makeDrag(el) {
	  // Pass me an object, and I will make it draggable
	  el.draggable({
	    revert: "invalid"
	  });
	}
	function handlePreviousDropEvent( event, ui ) {
		var draggable = ui.draggable;
		jQuery(this).append(draggable);
		jQuery('*[data-drop-role_id="'+draggable.data('role_id')+'"]').attr('data-drop-role_id', '');
		var oldItems = JSON.parse(localStorage.getItem('mapArray')) || [];
		jQuery.each(oldItems, function(key,val){
	    	if(val){
		    	var arrayofval = val.split(',');
			    if(arrayofval[0] == 'level_id_'+draggable.data( 'level_id' ) || arrayofval[1] == draggable.data('role_id')){
			    	delete oldItems[key];
			    }
			}
	    });
		var jsonStart = "{";
	    jQuery.each(oldItems, function(key,val){
	    	if(val){
		    	var arrayofval = val.split(',');
			    if(arrayofval[0] != 'level_id_'+draggable.data( 'level_id' ) || arrayofval[1] != draggable.data('role_id')){
			    	jsonStart = jsonStart+'"'+arrayofval[0]+'":'+'"'+arrayofval[1]+'",';
			    }
			}
	    });
	    localStorage.setItem('mapArray', JSON.stringify(oldItems));
	    var mappingjson = jsonStart+'"level_id_expired":"'+localStorage.getItem('firstmap_id')+'"}';
		jQuery("#maaping_json_val").html(mappingjson);
		localStorage.setItem('mappingjson', mappingjson);
		draggable.css({'width':'100%','left': '0','top':'0','margin-bottom':'10px'});
	}
  	function handleDropEvent( event, ui ) {
	    var draggable = ui.draggable;
	   	var newItem = [];
	   	jQuery('*[data-drop-role_id="'+draggable.data('role_id')+'"]').attr('data-drop-role_id', '');
	    if(jQuery(this).data( 'drop-role_id') != draggable.data('role_id')){
		    var oldItems = JSON.parse(localStorage.getItem('mapArray')) || [];
		    if(!localStorage.getItem('firstmap_id')){
		    	var firstmap_id = draggable.data('role_id');
		    	localStorage.setItem('firstmap_id',firstmap_id);
			}
			jQuery(this).attr( 'data-drop-role_id', draggable.data('role_id'));
			draggable.attr( 'data-level_id' ,jQuery(this).data('level_id'));
		    jQuery.each(oldItems, function(key,val){
		    	if(val){
			    	var arrayofval = val.split(',');
				    if(arrayofval[0] == 'level_id_'+jQuery(this).data( 'level_id' ) || arrayofval[1] == draggable.data('role_id')){
				    	delete oldItems[key];
				    }
				}
		    });
		    var newkey = 'level_id_'+jQuery(this).data( 'level_id' );
		    oldItems.push(newkey+','+draggable.data('role_id'));
		   	var jsonStart = "{";
		    jQuery.each(oldItems, function(key,val){
		    	if(val){
			    	var arrayofval = val.split(',');
				    if(arrayofval[0] != 'level_id_'+jQuery(this).data( 'level_id' ) || arrayofval[1] != draggable.data('role_id')){
				    	jsonStart = jsonStart+'"'+arrayofval[0]+'":'+'"'+arrayofval[1]+'",';
				    }
				}
		    });
		    localStorage.setItem('mapArray', JSON.stringify(oldItems));
		    var mappingjson = jsonStart+'"level_id_expired":"'+localStorage.getItem('firstmap_id')+'"}';
		    localStorage.setItem('mappingjson', mappingjson);
		    jQuery("#maaping_json_val").html(mappingjson);
		}
		jQuery(this).append(ui.draggable);
		jQuery(this).find('span').css({'order':'2'});
	    draggable.css({'width':'100%','left': '0','top':'0','margin-bottom':'0px','order':'1'});
  	}
});
