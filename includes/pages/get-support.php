<div class="contact-form ">
	<form accept="#" method="post">
      <div class="ets-container">
        <div class="top-logo-title">
          <img src="https://www.expresstechsoftwares.com/wp-content/uploads/xcropped-2100-x1500-1.png.pagespeed.ic.DFnEHWLdQO.png" class="img-fluid company-logo" alt="">
          <h1><?php echo __( "ExpressTech Softwares Solutions Pvt. Ltd.", "ets_pmpro_discord" );?></h1>
          <p><?php echo __( "ExpressTech Software Solution Pvt. Ltd. is the leading Enterprise Wordpress development company.", "ets_pmpro_discord" );?><br>
          <?php echo __( "Contact us for any Wordpress Related development projects.", "ets_pmpro_discord" );?></p>
        </div>
        <div class="form-fields-box ">
          <div class="ets-row ets-mt-5 ets-align-items-center">
            <div class="ets-col-7 ets-offset-md-1">
              <div class="contact-fields pr-100">
                <div class="ets-form-group">
                  <label><?php echo __( "Full Name", "ets_pmpro_discord" );?></label>
                  <input type="text" name="ets_user_name" value="<?php echo $currUserName; ?>" class="form-control contact-input" placeholder="Write Your Full Name">
                </div>
                <div class="ets-form-group">
                  <label><?php echo __( "Contact Email", "ets_pmpro_discord" );?></label>
                  <input type="text" name="ets_user_email" class="form-control contact-input" value="<?php echo get_option( 'admin_email' );
							 ?>" placeholder="Write Your Email">
                </div>
                <div class="ets-form-group">
                  <label><?php echo __( "Subject", "ets_pmpro_discord" );?></label>
                  <input type="text" name="ets_support_subject" class="form-control contact-input" placeholder="Write Your Subject" required="">
                </div>
                <div class="ets-form-group">
                  <label><?php echo __( "Message", "ets_pmpro_discord" );?></label>
                  <textarea name="ets_support_msg" class="form-control contact-textarea" required=""></textarea>
                </div>
                <div class="submit-btn d-flex align-items-center w-100 pt-3">
                  <input type="submit" name="save" id="save" class="btn btn-submit" value="Submit">                  
                  <a href="skype:ravi.soni971?chat" class="btn btn-skype ml-auto"><?php echo __( "Skype", "ets_pmpro_discord" );?></a>
                </div>
              </div>
            </div>
            <div class="ets-col-3">
              <div class="right-side-box">
                <div class="contact-details d-inline-block w-100 mb-4">
                  <div class="top-icon-title d-flex align-items-center w-100">
                    <i class="fas fa-envelope title-icon fa-lg fa-inverse" style="margin-right: 5px" aria-hidden="true"></i>
                    <p><?php echo __( "Email", "ets_pmpro_discord" );?></p>
                  </div>
                  <div class="contact-body mt-3">
                    <p><a href="mailto:contact@expresstechsoftwares.com"><?php echo __( "contact@expresstechsoftwares.com", "ets_pmpro_discord" );?></a></p>
                    <p><a href="mailto:business@expresstechsoftwares.com"><?php echo __( "business@expresstechsoftwares.com", "ets_pmpro_discord" );?></a></p>
                  </div>
                </div>
                <div class="contact-details d-inline-block w-100 mb-4">
                  <div class="top-icon-title d-flex align-items-center w-100">
                    <i class="fab fa-skype title-icon fa-lg fa-inverse" style="margin-right: 5px" aria-hidden="true"></i>
                    <p><?php echo __( "Skype", "ets_pmpro_discord" );?></p>
                  </div>
                  <div class="contact-body mt-3">
                    <p><?php echo __( "ravi.soni971", "ets_pmpro_discord" );?></p>
                  </div>
                </div>
                <div class="contact-details d-inline-block w-100">
                  <div class="top-icon-title d-flex align-items-center w-100">
                    <i class="fab fa-whatsapp title-icon fa-lg fa-inverse" style="margin-right: 5px" aria-hidden="true"></i>
                    <p><?php echo __( "Whatsapp / Phone", "ets_pmpro_discord" );?></p>
                  </div>
                  <div class="contact-body mt-3">
                    <p><?php echo __( "+91-9806724185", "ets_pmpro_discord" );?></p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
	</form>
</div>