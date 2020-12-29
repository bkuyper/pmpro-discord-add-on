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
});
