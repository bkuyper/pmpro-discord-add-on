jQuery( document ).ready( function( $ ) {
	if (etsPmproParams.is_admin) {
		/*create tabs*/
		$('.ets_tablinks').on('click',function() {
		 	$('.ets_tabcontent').each(function(){
		    	$(this).css({"display":"none"});
		  	});
			$('.ets_tablinks').each(function(){
		    	$(this).removeClass("active");;
		  	});
			$(this).addClass('active');
			var event = $(this).data('event');
			$('#'+event).css({"display":"block"});
			localStorage.setItem('activeTab', $(this).data('identity'));
		});

		/*check previous active tab*/
		var activeTab = localStorage.getItem('activeTab');
		if ( activeTab ) {
			$('.ets-tabs button[data-identity="' + activeTab + '"]').trigger('click');
		} else {
			$('.ets-tabs button[data-identity="settings"]').trigger('click');
		}

	
		/*Load all roles from discord server*/
		$.ajax({
			type:"POST",
			dataType:"JSON",
			url:etsPmproParams.admin_ajax,
	        data: {'action': 'load_discord_roles','ets_discord_nonce': etsPmproParams.ets_discord_nonce,},
	        beforeSend:function () {
				$(".discord-roles .spinner").addClass("is-active");
				$(".ets_tablinks .spinner").addClass("is-active");
			},
			success:function (response) {
				if ( response.hasOwnProperty('code') && response.code == 50001 && response.message == 'Missing Access' ) {
					$(".btn-connect-to-bot").show();
				} else if ( response.message == '401: Unauthorized' || response.hasOwnProperty('code') || response == 0 ) {
					$("#connect-discord-bot").show().html("Error: Please check all details are correct").addClass('error-bk');
				} else {
					if ( $('.ets-tabs button[data-identity="level-mapping"]').length ) {
						$('.ets-tabs button[data-identity="level-mapping"]').show();
					}
					$("#connect-discord-bot").show().html("Bot Connected <i class='fab fa-discord'></i>").addClass('not-active');
				}
				
				var activeTab = localStorage.getItem('activeTab');
				if ( $('.ets-tabs button[data-identity="level-mapping"]').length == 0 && activeTab == 'level-mapping' ) {
					$('.ets-tabs button[data-identity="settings"]').trigger('click');
				}
				$.each(response, function (key, val) {
					var isbot = false;
					if ( val.hasOwnProperty('tags') ) {	
						if ( val.tags.hasOwnProperty('bot_id') ) {
							isbot = true;
						}
					}

					if ( key != 'previous_mapping' && isbot == false && val.name != '@everyone' ) {
				        $('.discord-roles').append('<div class="makeMeDraggable" data-role_id="'+val.id+'" >'+val.name+'</div>');
			        	$('#defaultRole').append('<option value="'+val.id+'" >'+val.name+'</option>');
				        makeDrag($('.makeMeDraggable'));
				    }
			    });
			    var defaultRole = $('#selected_default_role').val();
			    if ( defaultRole ) {
			    	$('#defaultRole option[value='+defaultRole+']').prop('selected', true);
			    }

			    if ( response.previous_mapping ) {
			    	var mapjson = response.previous_mapping;
			    } else {
			    	var mapjson = localStorage.getItem('mappingjson');
			    }
			    
				$("#maaping_json_val").html(mapjson);
				$.each(JSON.parse(mapjson), function(key,val){
	    			var arrayofkey = key.split('id_');
			   		$('*[data-level_id="'+arrayofkey[1]+'"]').append($('*[data-role_id="'+val+'"]')).attr( 'data-drop-role_id', val).find('span').css({'order':'2'});
			   		if(jQuery('*[data-level_id="'+arrayofkey[1]+'"]').find('.makeMeDraggable').length >= 1){
						$('*[data-level_id="'+arrayofkey[1]+'"]').droppable("destroy");
					}
				   	$('*[data-role_id="'+val+'"]').css({'width':'100%','left': '0','top':'0','margin-bottom':'0px','order':'1'}).attr( 'data-level_id' ,arrayofkey[1]);
			    });
			},
	      	error: function(response) {
		        $("#connect-discord-bot").show().html("Error: Please check all details are correct").addClass('error-bk');
		        console.error(response);
	      	},
			complete: function(){
				$(".discord-roles .spinner").removeClass("is-active").css({"float":"right"});
				$(".ets_tablinks .spinner").removeClass("is-active").css({"float":"right", "display":"none"});
			}
		});
	

		/*Clear log log call-back*/
		$('#clrbtn').click(function(e) {
			e.preventDefault();
	  		$.ajax({
		      	url: etsPmproParams.admin_ajax,
		      	type: "POST",
		      	data: {'action':'ets_clear_logs','ets_discord_nonce': etsPmproParams.ets_discord_nonce,},
	      		beforeSend:function () {
					$(".spinner").addClass("is-active").show();
		   		},
		      	success: function(data) {
		      		if ( data.error ) {
			            // handle the error
		            	alert(data.error.msg);
		        	} else {
		        		$('.error-log').html("Clear logs Sucesssfully !");
			        }
		 	 	},
		 	 	error: function(response) { 
					console.error(response); 
				},
		  		complete: function(){
					$(".spinner").removeClass("is-active").hide();
		  		}
		    });
	  	});

		/*Flush settings from local storage*/
		$("#revertMapping").on('click', function(){
	  		localStorage.removeItem('mapArray');
	  		localStorage.removeItem('mappingjson');
	  		window.location.href = window.location.href;
	  	});

		/*Create droppable element*/
		function init() {
		    $('.makeMeDroppable').droppable( {
	      		drop: handleDropEvent,
	      		hoverClass: 'hoverActive',
		    } );
		    $('.discord-roles-col').droppable( {
	      		drop: handlePreviousDropEvent,
	      		hoverClass: 'hoverActive',  
		    } );
		}

		$( init );

		/*Create draggable element*/
		function makeDrag(el) {
	  		// Pass me an object, and I will make it draggable
	  		el.draggable({
	    		revert: "invalid"
	  		});
		}

		/*Handel droppable event for saved mapping*/
		function handlePreviousDropEvent( event, ui ) {
			var draggable = ui.draggable;
			$(this).append(draggable);
			$('*[data-drop-role_id="'+draggable.data('role_id')+'"]').droppable( {
	      		drop: handleDropEvent,
	      		hoverClass: 'hoverActive',
		    } );
			$('*[data-drop-role_id="'+draggable.data('role_id')+'"]').attr('data-drop-role_id', '');

			var oldItems = JSON.parse(localStorage.getItem('mapArray')) || [];
			$.each(oldItems, function(key,val){
		    	if ( val ) {
			    	var arrayofval = val.split(',');
				    if ( arrayofval[0] == 'level_id_'+draggable.data( 'level_id' ) || arrayofval[1] == draggable.data('role_id') ) {
				    	delete oldItems[key];
				    }
				}
		    });
			var jsonStart = "{";
		    $.each(oldItems, function(key,val){
		    	if ( val ) {
			    	var arrayofval = val.split(',');
				    if( arrayofval[0] != 'level_id_'+draggable.data( 'level_id' ) || arrayofval[1] != draggable.data('role_id') ) {
				    	jsonStart = jsonStart+'"'+arrayofval[0]+'":'+'"'+arrayofval[1]+'",';
				    }
				}
		    });
		    localStorage.setItem('mapArray', JSON.stringify(oldItems));
		    var lastChar = jsonStart.slice(-1);
		  	if ( lastChar == ',' ) {
	  			jsonStart = jsonStart.slice(0, -1);
		  	}

		    var mappingjson = jsonStart+'}';
			$("#maaping_json_val").html(mappingjson);
			localStorage.setItem('mappingjson', mappingjson);
			draggable.css({'width':'100%','left': '0','top':'0','margin-bottom':'10px'});
		}

		/*Handel droppable area for current mapping*/
	  	function handleDropEvent( event, ui ) {
		    var draggable = ui.draggable;
		   	var newItem = [];
		   	$('*[data-drop-role_id="'+draggable.data('role_id')+'"]').droppable( {
	      		drop: handleDropEvent,
	      		hoverClass: 'hoverActive',
		    } );
		   	$('*[data-drop-role_id="'+draggable.data('role_id')+'"]').attr('data-drop-role_id', '');
		    if ( $(this).data( 'drop-role_id') != draggable.data('role_id') ) {
			    var oldItems = JSON.parse(localStorage.getItem('mapArray')) || [];
				$(this).attr( 'data-drop-role_id', draggable.data('role_id'));
				draggable.attr( 'data-level_id' ,$(this).data('level_id'));

			    $.each(oldItems, function(key,val){
			    	if ( val ) {
				    	var arrayofval = val.split(',');
					    if( arrayofval[0] == 'level_id_'+$(this).data( 'level_id' ) || arrayofval[1] == draggable.data('role_id') ) {
					    	delete oldItems[key];
					    }
					}
			    });

			    var newkey = 'level_id_'+$(this).data( 'level_id' );
			    oldItems.push(newkey+','+draggable.data('role_id'));
			   	var jsonStart = "{";
			    $.each(oldItems, function(key,val){
			    	if ( val ) {
				    	var arrayofval = val.split(',');
					    if ( arrayofval[0] == 'level_id_'+$(this).data( 'level_id' ) || arrayofval[1] != draggable.data('role_id') && arrayofval[0] != 'level_id_'+$(this).data( 'level_id' ) || arrayofval[1] == draggable.data('role_id') ) {
					    	jsonStart = jsonStart+'"'+arrayofval[0]+'":'+'"'+arrayofval[1]+'",';
					    }
					}
			    });

			    localStorage.setItem('mapArray', JSON.stringify(oldItems));
			    var lastChar = jsonStart.slice(-1);
			  	if ( lastChar == ',' ) {
		  			jsonStart = jsonStart.slice(0, -1);
			  	}

			    var mappingjson = jsonStart+'}';
			    localStorage.setItem('mappingjson', mappingjson);
			    $("#maaping_json_val").html(mappingjson);
			}

			$(this).append(ui.draggable);
			$(this).find('span').css({'order':'2'});
			if(jQuery(this).find('.makeMeDraggable').length >= 1){
				$(this).droppable("destroy");
			}
			
		    draggable.css({'width':'100%','left': '0','top':'0','margin-bottom':'0px','order':'1'});
	  	}
  	}

  	/*Call-back on disconnect from discord*/
	$('#disconnect-discord').on('click',function (e) {
		e.preventDefault();
		var userId = $(this).data('user-id');
		$.ajax({
			type:"POST",
			dataType:"JSON",
			url:etsPmproParams.admin_ajax,
      data: {'action': 'disconnect_from_discord','user_id':userId,'ets_discord_nonce': etsPmproParams.ets_discord_nonce,},
			beforeSend:function () {
				$(".ets-spinner").addClass("ets-is-active");
			},
			success:function (response) {
				if ( response.status == 1 ) {
					window.location = window.location.href.split("?")[0];
				}
			},
			error: function(response) { 
				console.error(response); 
			}
		});
	});
	/*Call-back to manage member connection with discord from pmpro members-list*/
	$('.ets-run-api').on('click',function (e) {
		e.preventDefault();
		var userId = $(this).data('uid');
		$.ajax({
			type:"POST",
			dataType:"JSON",
			url:etsPmproParams.admin_ajax,
      data: {'action': 'ets_discord_run_api','user_id':userId,'ets_discord_nonce': etsPmproParams.ets_discord_nonce,},
			success:function (response) {
				if ( response.status == 1 ) {
					window.location = window.location.href;
				}
			},
			error: function(response) { 
				console.error(response); 
			}
		});
	});
});
