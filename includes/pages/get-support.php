<div class="ets-details"> 
	<div class="ets-com-logo">
		<div class="ets-co-logo" > 
			<img src= <?php echo ETS_PMPRO_DISCORD_URL."assets/images/user-original.png;"?> > 
		</div>
	</div>
	<div class="ets-detail-dec"> 
		<h2><?php echo __( "ExpressTech Software Solutions Pvt. Ltd.","ets_pmpro_discord" ); ?></h2>
		<a href="https://www.expresstechsoftwares.com/">
		<?php echo __( "ExpressTech Software Solutions Pvt. Ltd.", "ets_pmpro_discord" ); ?></a>
		<?php echo __( "is the leading Enterprise Wordpress development company.", "ets_pmpro_discord" ); ?>
		<?php echo __( "Contact us for any Wordpress Related development project.", "ets_pmpro_discord" ); ?>
		<br> 
		<span><b><?php echo __( "Email","ets_pmpro_discord" ); ?>: </b>
		<a href="mailto:contact@expresstechsoftwares.com">contact@expresstechsoftwares.com</a> , 
		<a href="mailto:business@expresstechsoftwares.com">business@expresstechsoftwares.com</a>
		</span><br>
		<span><b><?php echo __( "Skype","ets_pmpro_discord" ); ?>: </b>ravi.soni971</span><br>
<span><b><?php echo __( "Phone/WhatsApp","ets_pmpro_discord" ); ?>: </b>+91-9806724185</span>
	</div>
</div>

<div class="ets-support-lavel">
	<div class="ets-supp-form">
	  	<form accept="#" method="post">
			<table class="form-table">
				<tbody>						
					<tr>
						<th scope="row">
							<?php echo __( "Full Name","ets_pmpro_discord" ); ?>	 
						</th>
						<td>
							<input type="text" name="ets_user_name" placeholder="Enter Name" class="regular-text" required=""
							value="<?php echo $currUserName;
							 ?>">
							<p class="description">
								<?php echo __( "Write your full name","ets_pmpro_discord" );?>	
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo __( "Contact Email","ets_pmpro_discord" );?> 
						</th>
						<td>
							<input type="email" name="ets_user_email" placeholder=" Enter email" class="regular-text" required="" value="<?php echo get_option( 'admin_email' );
							 ?>">
							<p class="description"><?php echo __( "Write your contact email","ets_pmpro_discord" );?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php echo __( "Subject","ets_pmpro_discord" ); ?> 
						</th>
						<td>
							<input type="text" name="ets_support_subject" placeholder=" Enter your subject" class="regular-text" required="">
							<p class="description"><?php echo __( "Write your support subject","ets_pmpro_discord" );?></p>
					
						</td>
					</tr>
					<tr>
						<th scope="row">
						<?php echo __( "Message","ets_pmpro_discord" ); ?> 
						</th>
						<td>
							<textarea name="ets_support_msg" rows="5" cols="50" required="" class="ets-regular-text"></textarea>
							<p class="description"><?php echo __( "Write your support message","ets_pmpro_discord" );?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<input type="submit" name="save" id="save" class="ets-submit" value="Send">
			</p>
		</form>
	</div> 
</div>