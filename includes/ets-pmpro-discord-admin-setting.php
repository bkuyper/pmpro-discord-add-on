<?php
/**
 * Admin setting
 */
class Ets_Pmpro_Admin_Setting
{
	function __construct()
	{
		// Add new menu option in the admin menu.
		add_action('admin_menu', array($this, 'ets_add_new_menu'));

		// Add script for back end.	
		add_action( 'admin_enqueue_scripts', array($this, 'ets_add_script' ));

		// Add script for front end.
		add_action('wp_enqueue_scripts', array($this, 'ets_add_script'));

		//Add new button in pmpro profile
		add_action('pmpro_account_bullets_bottom', array( $this, 'add_connect_discord_button' ));

		//Discord api callback
		add_action('init', array( $this, 'discord_api_callback' ));

		//change hook call on cancel and change
		add_action('pmpro_after_change_membership_level', array($this, 'change_discord_role_from_pmpro'), 10, 3);

		//Pmpro expiry
		add_action('pmpro_membership_post_membership_expiry', array($this, 'pmpro_expiry_membership'), 10 ,2);

		//front ajax function to disconnect from discord
		add_action('wp_ajax_disconnect_from_discord', array($this, 'disconnect_from_discord'));

		//back ajax function to disconnect from discord
        add_action('wp_ajax_nopriv_disconnect_from_discord', array($this, 'disconnect_from_discord'));

        add_action( 'wp_ajax_ets_clear_logs', array($this, 'clear_logs') );
	}

	/**
	 * Description: Localized script and style 
	 * @param None
	 * @return None
	 */
	public function ets_add_script(){

		wp_register_style(
		    'ets_pmpro_add_discord_style',
		    ETS_PMPRO_DISCORD_URL. 'assets/css/ets-pmpro-discord-style.css'
		); 
		wp_enqueue_style( 'ets_pmpro_add_discord_style');
	  
	    wp_register_script(
			'ets_pmpro_add_discord_script',
			ETS_PMPRO_DISCORD_URL . 'assets/js/ets-pmpro-add-discord-script.js',
			array('jquery')
		);
        wp_enqueue_script( 'ets_pmpro_add_discord_script' );
		
	 	$script_params = array(
			'admin_ajax' 		=> admin_url('admin-ajax.php')
		);  

	  	wp_localize_script( 'ets_pmpro_add_discord_script', 'etsPmproParams', $script_params ); 
	}

	/**
	 * Description: Add menu in admin dashboard
	 * @param None
	 * @return None
	 */
	public function ets_add_new_menu(){
		add_menu_page(__( 'ETS Settings', 'ets_pmpro_discord' ), __( 'ETS Settings', 'ets_pmpro_discord' ), 'manage_options', 'discord-options', array( $this, 'ets_setting_page' ), 'dashicons-admin-generic', 59);
	}

	/**
	 * Description: Define plugin settings rules
	 * @param None
	 * @return None 
	 */
	public function ets_setting_page(){
		$ets_discord_client_id = isset($_POST['ets_discord_client_id']) ? sanitize_text_field(trim($_POST['ets_discord_client_id'])) : '';

		$discord_client_secret = isset($_POST['ets_discord_client_secret']) ? sanitize_text_field(trim($_POST['ets_discord_client_secret'])) : '';

		$discord_bot_token = isset($_POST['ets_discord_bot_token']) ? sanitize_text_field(trim($_POST['ets_discord_bot_token'])) : '';

		$ets_discord_redirect_url = isset($_POST['ets_discord_redirect_url']) ? sanitize_text_field(trim($_POST['ets_discord_redirect_url'])) : '';

		$ets_discord_guild_id = isset($_POST['ets_discord_guild_id']) ? sanitize_text_field(trim($_POST['ets_discord_guild_id'])) : '';

		$ets_discord_roles = isset($_POST['ets_discord_role_mapping']) ? sanitize_textarea_field(trim($_POST['ets_discord_role_mapping'])) : '';
		
		if($ets_discord_client_id)
			update_option('ets_discord_client_id',$ets_discord_client_id);
		
		if($discord_client_secret)
			update_option('ets_discord_client_secret', $discord_client_secret);
		
		if($discord_bot_token) {
			update_option('ets_discord_bot_token', $discord_bot_token);
		}

		if($ets_discord_redirect_url) {
			update_option('ets_discord_redirect_url', $ets_discord_redirect_url);
		}

		if ( $ets_discord_guild_id ) {
			update_option('discord_guild_id', $ets_discord_guild_id);
		}

		if ( $ets_discord_roles ) {
			$ets_discord_roles = stripslashes( $ets_discord_roles );
			update_option('ets_discord_role_mapping',$ets_discord_roles);
		}

		$currUserName = "";
		$currentUser = wp_get_current_user();
		if ($currentUser) {
			$currUserName = $currentUser->user_login;
		}
		$ets_discord_client_id = get_option('ets_discord_client_id');
		$discord_client_secret = get_option('ets_discord_client_secret');
		$discord_bot_token = get_option('ets_discord_bot_token');
		$ets_discord_redirect_url = get_option('ets_discord_redirect_url');
		$ets_discord_roles = get_option('ets_discord_role_mapping');
		$ets_discord_guild_id = get_option('discord_guild_id');
		?>
		<h1><?php echo __("Discord App Settings","ets_pmpro_discord");?></h1>
		<div class="tab ets-tabs">
		  <button class="ets_tablinks active" onclick="openTab(event, 'ets_setting')"><?php echo __("Discord Settings", "ets_pmpro_discord"); ?></button>
		  <button class="ets_tablinks" onclick="openTab(event, 'ets_about_us')"><?php echo __("Support", "ets_pmpro_discord"); ?>	
		  </button>
		  <button class="ets_tablinks" onclick="openTab(event, 'ets_logs')"><?php echo __("Logs", "ets_pmpro_discord"); ?>	
		  </button>  
		</div>

		<div id="ets_setting" class="ets_tabcontent">
			<h3><?php echo __("Discord Settings", "ets_pmpro_discord");?></h3>
			<form method="post" action="#">
			  	<div class="ets-input-group">
			  		<label><?php echo __("Client ID", "ets_pmpro_discord");?> :</label>
			  			<input type="text" class="ets-input" name="ets_discord_client_id" value="<?php if(isset($ets_discord_client_id))echo $ets_discord_client_id;?>" required placeholder="Discord Client ID">
			  	</div>
			  	<div class="ets-input-group">
			  		<label><?php echo __( "Client Secret", "ets_pmpro_discord" );?> :</label>
			  			<input type="text" class="ets-input" name="ets_discord_client_secret" value="<?php if(isset($discord_client_secret))echo $discord_client_secret;?>" required placeholder="Discord Client Secret">
			  	</div>
			  	<div class="ets-input-group">
			  		<label><?php echo __( "Bot Token", "ets_pmpro_discord" );?> :</label>
			  			<input type="text" class="ets-input" name="ets_discord_bot_token" value="<?php if(isset($discord_bot_token))echo $discord_bot_token;?>" required placeholder="Discord Bot Token">
			  	</div>
			  	<div class="ets-input-group">
			  		<label><?php echo __( "Redirect URL", "ets_pmpro_discord" );?> :</label>
			  			<input type="text" class="ets-input" name="ets_discord_redirect_url"
			  			placeholder="Discord Redirect Url" value="<?php if(isset($ets_discord_redirect_url))echo $ets_discord_redirect_url;?>" required>
			  			<p class="description"><?php echo __( "Registered discord app url", "ets_pmpro_discord" );?></p>
			  	</div>
			  	<div class="ets-input-group">
			  		<label><?php echo __( "Guild Id", "ets_pmpro_discord" );?> :</label>
			  			<input type="text" class="ets-input" name="ets_discord_guild_id"
			  			placeholder="Discord Guild Id" value="<?php if(isset($ets_discord_guild_id))echo $ets_discord_guild_id;?>" required>
			  	</div>
			  	<div class="ets-input-group">
			  		<label><?php echo __( "Discord Roles PMPRO-Level Mappings", "ets_pmpro_discord" );?> :</label>
		  			<textarea class="ets-input" name="ets_discord_role_mapping"
			  			placeholder="Discord Roles PMPRO-Level Mappings" required><?php if(isset($ets_discord_roles))echo stripslashes($ets_discord_roles);?></textarea>
			  	</div>
			  	<p>
			  		<button type="submit" name="submit" value="ets_submit" class="ets-submit">
			  			<?php echo __("Submit", "ets_pmpro_discord");?>
			  		</button>
			  	</p>
			</form>
		</div>
		<div id="ets_about_us" class="ets_tabcontent">
			<div class="ets-details"> 
				<div class="ets-com-logo">
					<div class="ets-co-logo" > 
						<img src= <?php echo ETS_PMPRO_DISCORD_URL."assets/images/user-original.png;"?> > 
					</div>
				</div>
				<div class="ets-detail-dec"> 
					<h2><?php echo __("ExpressTech Software Solutions Pvt. Ltd.","ets_pmpro_discord"); ?></h2>
					<a href="https://www.expresstechsoftwares.com/">
					<?php echo __("ExpressTech Software Solutions Pvt. Ltd.", "ets_pmpro_discord"); ?></a>
					<?php echo __("is the leading Enterprise Wordpress development company.", "ets_pmpro_discord"); ?>
					<?php echo __("Contact us for any Wordpress Related development project.", "ets_pmpro_discord"); ?>
					<br> 
					<span><b><?php echo __("Email","ets_pmpro_discord"); ?>: </b>
					<a href="mailto:contact@expresstechsoftwares.com">contact@expresstechsoftwares.com</a> , 
					<a href="mailto:business@expresstechsoftwares.com">business@expresstechsoftwares.com</a>
					</span><br>
					<span><b><?php echo __("Skype","ets_pmpro_discord"); ?>: </b>ravi.soni971</span><br>
          <span><b><?php echo __("Phone/WhatsApp","ets_pmpro_discord"); ?>: </b>+91-9806724185</span>
				</div>
			</div>
		   
			<div class="ets-support-lavel">
				<div class="ets-supp-form">
				  	<form accept="#" method="post">
						<table class="form-table">
							<tbody>						
								<tr>
									<th scope="row">
										<?php echo __("Full Name","ets_pmpro_discord"); ?>	 
									</th>
									<td>
										<input type="text" name="ets_user_name" placeholder="Enter Name" class="regular-text" required=""
										value="<?php echo $currUserName;
										 ?>">
										<p class="description">
											<?php echo __("Write your full name","ets_pmpro_discord");?>	
										</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo __("Contact Email","ets_pmpro_discord");?> 
									</th>
									<td>
										<input type="email" name="ets_user_email" placeholder=" Enter email" class="regular-text" required="" value="<?php echo get_option('admin_email');
										 ?>">
										<p class="description"><?php echo __("Write your contact email","ets_pmpro_discord");?></p>
									</td>
								</tr>
								<tr>
									<th scope="row">
										<?php echo __("Subject","ets_pmpro_discord"); ?> 
									</th>
									<td>
										<input type="text" name="ets_support_subject" placeholder=" Enter your subject" class="regular-text" required="">
										<p class="description"><?php echo __("Write your support subject","ets_pmpro_discord");?></p>
								
									</td>
								</tr>
								<tr>
									<th scope="row">
									<?php echo __("Message","ets_pmpro_discord"); ?> 
									</th>
									<td>
										<textarea name="ets_support_msg" rows="5" cols="50" required="" class="ets-regular-text"></textarea>
										<p class="description"><?php echo __("Write your support message","ets_pmpro_discord");?></p>
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
		</div>
		<div id="ets_logs" class="ets_tabcontent">
			<?php include(ETS_PMPRO_DISCORD_PATH.'assets/pages/error_log.php'); ?>
		</div>

		<?php
		$this->get_Support_Data();
	}

	/**
	 * Description: Send mail to support form current user
	 * @param None
	 * @return None 
	*/
	public function get_Support_Data()
	{
		if (isset($_POST['save'])) {
			$etsUserName 	= isset($_POST['ets_user_name']) ? sanitize_text_field(trim($_POST['ets_user_name'])) : "";
			$etsUserEmail 	= isset($_POST['ets_user_email']) ? sanitize_text_field(trim($_POST['ets_user_email'])) : "";
			$message  		= isset($_POST['ets_support_msg']) ? sanitize_text_field(trim($_POST['ets_support_msg'])) : "";
			$sub  			= isset($_POST['ets_support_subject']) ? sanitize_text_field(trim($_POST['ets_support_subject'])) : "";

			if($etsUserName && $etsUserEmail && $message && $sub){
				$subject 		= $sub;
				$to 			= 'contact@expresstechsoftwares.com';
				$content 		= "Name: " .$etsUserName."<br>";
				$content 		.= "Contact Email: " .$etsUserEmail."<br>";
				$content		.=  "Message: ".$message;
			  $headers 		= array();
			  $blogemail 	= get_bloginfo("admin_email");
				$headers[] 		= 'From: '.get_bloginfo("name") .' <'.$blogemail.'>'."\r\n";
				$mail = wp_mail( $to, $subject, $content, $headers );
			} 	
		}
	}

	/**
	 * Description: Show status of PMPro connection with user
	 * @param None
	 * @return New link 
	 */
	public function add_connect_discord_button()
	{	
		$user_id = get_current_user_id();
		$access_token = get_user_meta( $user_id, "discord_access_token", true );
		if ($access_token) {
			?>
			<a href="#" class="ets-btn btn-disconnect" id="disconnect-discord" data-user-id="<?php echo $user_id; ?>"><?php echo __("Disconnect From Discord ", "ets_pmpro_discord");?></a>
			<img id="image-loader" src= <?php echo ETS_PMPRO_DISCORD_URL."assets/images/Spin-Preloader.gif;"?> >
		<?php
		}
		else {
		?>
			<a href="?action=discord-login" class="btn-connect ets-btn" target="_blank"><?php echo __("Connect To Discord", "ets_pmpro_discord");?></a>
		<?php
		}
		
	}

	/**
	 * Description: get pmpro current level id
	 * @param int $user_id
	 * @return int $curr_level_id
	 */
	public function get_current_level_id($user_id)
	{
		if(is_user_logged_in() && function_exists('pmpro_hasMembershipLevel') && pmpro_hasMembershipLevel())
		{
			global $current_user;
			$membership_level = pmpro_getMembershipLevelForUser($user_id);
			$curr_level_id = $membership_level->ID;
			return $curr_level_id;
		}
	}

	/**
	 * Description: Create authentication token for discord API
	 * @param string $code
	 * @return object API response
	 */
	public function create_discord_auth_token($code)
	{
		$discord_token_api_url = ETS_DISCORD_API_URL.'oauth2/token';
		$args = array(
			'method'=> 'POST',
		    'headers' => array(
		        'Content-Type' => 'application/x-www-form-urlencoded'
		    ),
		    'body' => array(
	    		'client_id' => get_option('ets_discord_client_id'),
				  'client_secret' => get_option('ets_discord_client_secret'),
				  'grant_type' => 'authorization_code',
				  'code' => $code,
				  'redirect_uri' =>  get_option('ets_discord_redirect_url'),
				  'scope' => 'identify email connections'
		    )    
		);

		$response = wp_remote_post( $discord_token_api_url, $args );

		$responseArr = json_decode( wp_remote_retrieve_body( $response ), true );
		if(is_array($responseArr) && !empty($responseArr)){
			if(array_key_exists('code', $responseArr) || array_key_exists('error', $responseArr)){
				$Logs = new PMPro_Discord_Logs();
				$Logs->write_api_response_logs($responseArr, debug_backtrace()[0],'api_error');
			}
		}
		return $response;
	}

	/**
	 * Description: Get Discord user details from API
	 * @param string $access_token
	 * @return object API response
	 */
	public function get_discord_current_user( $access_token )
	{
		$discord_cuser_api_url = ETS_DISCORD_API_URL.'users/@me';
		$param = array(
			'headers'      => array(
	        'Content-Type' => 'application/x-www-form-urlencoded',
	        'Authorization' => 'Bearer ' . $access_token
	    	)
	    );
		$user_response = wp_remote_get( $discord_cuser_api_url, $param );
		$responseArr = json_decode( wp_remote_retrieve_body( $user_response ), true );
		if(is_array($responseArr) && !empty($responseArr)){
			if(array_key_exists('code', $responseArr)){
				$Logs = new PMPro_Discord_Logs();
				$Logs->write_api_response_logs($responseArr, debug_backtrace()[0],'api_error');
			}
		}
		$user_body = json_decode( wp_remote_retrieve_body( $user_response ), true );
		return $user_body;
	}

	/**
	 * Description: Add new member into discord guild
	 * @param int $discord_user_id
	 * @param int $user_id
	 * @param string $access_token
	 * @return object API response
	 */
	public function add_discord_member_in_guild( $discord_user_id, $user_id, $access_token )
	{
		$guild_id = get_option('discord_guild_id');
		$discord_bot_token = get_option('ets_discord_bot_token');
		$ets_discord_role_mapping = json_decode(get_option('ets_discord_role_mapping'), true);
		$discord_role = '';
		$curr_level_id = $this->get_current_level_id( $user_id );
		if( $curr_level_id )
		{
			$discord_role = $ets_discord_role_mapping[ 'level_id_'.$curr_level_id ];
		}
		$guilds_memeber_api_url = ETS_DISCORD_API_URL.'guilds/'.$guild_id.'/members/'.$discord_user_id;
		$guild_args = array(
			'method'  => 'PUT',
		    'headers' => array(
		        'Content-Type'  => 'application/json',
		        'Authorization' => 'Bot ' . $discord_bot_token
		    ),
		    'body' => json_encode(
		    	array(
					"access_token" => $access_token,
					"roles"        => [
				            $discord_role
				        ]
				)
	    	)
		);
		update_user_meta($user_id, 'discord_role_id', $discord_role);
		$guild_response = wp_remote_post( $guilds_memeber_api_url, $guild_args );
		$responseArr = json_decode( wp_remote_retrieve_body( $guild_response ), true );
		if(is_array($responseArr) && !empty($responseArr)){
			if(array_key_exists('code', $responseArr)){
				$Logs = new PMPro_Discord_Logs();
				$Logs->write_api_response_logs($responseArr, debug_backtrace()[0],'api_error');
			}
		}
		
		$change_response = $this->change_discord_role_api( $user_id, $discord_role );
		return $guild_response;
	}

	/**
	 * Description: For authorization process call discord API
	 * @param None
	 * @return object API response 
	 */
	public function discord_api_callback()
	{
		if (isset($_GET['action']) && $_GET['action'] == "discord-login" ) {
			$params = array(
			    'client_id' => get_option('ets_discord_client_id'),
			    'redirect_uri' => get_option('ets_discord_redirect_url'),
			    'response_type' => 'code',
			    'scope' => 'identify email connections guilds guilds.join messages.read'
			  );
			$discord_authorise_api_url = ETS_DISCORD_API_URL."oauth2/authorize?".http_build_query($params);

			header('Location: '.$discord_authorise_api_url);
			die();
		}

		if (isset($_GET['code'])) {
			$code = $_GET['code'];
			$user_id = get_current_user_id();
			$response = $this->create_discord_auth_token( $code );
			$res_body = json_decode( wp_remote_retrieve_body( $response ), true );
			
			$discord_exist_user_id = get_user_meta($user_id, "discord_user_id", true );
			
			if (array_key_exists( 'access_token', $res_body )) {				
				$access_token = $res_body['access_token'];
				update_user_meta( $user_id, "discord_access_token", $access_token );
				$user_body = $this->get_discord_current_user( $access_token );
				if ( array_key_exists( 'id', $user_body ) )
				{
					$discord_user_id = $user_body['id'];
					if ( $discord_exist_user_id == $discord_user_id ) {
						$role_delete = $this->delete_discord_role( $user_id );
					}
					update_user_meta($user_id, "discord_user_id", $discord_user_id );
					$guild_response = $this->add_discord_member_in_guild( $discord_user_id, $user_id,$access_token );
				}	
			}
		}
	}

	/**
	 * Description: Delete existing user from guild
	 * @param int $user_id
	 * @return object API response
	 */
	public function delete_member_from_guild($user_id)
	{
		$guild_id = get_option('discord_guild_id');
		$discord_bot_token = get_option('ets_discord_bot_token');
		$discord_user_id = get_user_meta($user_id , 'discord_user_id', true);
		$guilds_delete_memeber_api_url = ETS_DISCORD_API_URL.'guilds/'.$guild_id.'/members/'.$discord_user_id;
		$guild_args = array(
			'method'  => 'DELETE',
		    'headers' => array(
		        'Content-Type'  => 'application/json',
		        'Authorization' => 'Bot ' . $discord_bot_token
		    )   
		);
		$guild_response = wp_remote_post( $guilds_delete_memeber_api_url, $guild_args );
		$responseArr = json_decode( wp_remote_retrieve_body( $guild_response ), true );
		if(is_array($responseArr) && !empty($responseArr)){
			if(array_key_exists('code', $responseArr)){
				$Logs = new PMPro_Discord_Logs();
				$Logs->write_api_response_logs($responseArr, debug_backtrace()[0],'api_error');
			}
		}
		return $guild_response;
	}
	
	/**
	 * Description: API call to change discord user role
	 * @param int $user_id
	 * @param int $role_id
	 * @return object API response
	 */
	public function change_discord_role_api( $user_id, $role_id )
	{
		$access_token = get_user_meta( $user_id, "discord_access_token", true );
		$guild_id = get_option( 'discord_guild_id' );
		$discord_user_id = get_user_meta($user_id, 'discord_user_id', true);
		$discord_bot_token = get_option('ets_discord_bot_token');
    	$discord_change_role_api_url = ETS_DISCORD_API_URL.'guilds/'.$guild_id.'/members/'.$discord_user_id.'/roles/'.$role_id;
		if ( $access_token && $discord_user_id ) {
			$param = array(
						'method'=> 'PUT',
					    'headers' => array(
					        'Content-Type' => 'application/json',
					        'Authorization' => 'Bot ' .$discord_bot_token,
					        'Content-Length' => 0
					    )
					);

			$response = wp_remote_get($discord_change_role_api_url, $param);
			$responseArr = json_decode( wp_remote_retrieve_body( $response ), true );
			if(is_array($responseArr) && !empty($responseArr)){
				if(array_key_exists('code', $responseArr)){
					$Logs = new PMPro_Discord_Logs();
					$Logs->write_api_response_logs($responseArr, debug_backtrace()[0],'api_error');
				}
			}
			update_user_meta( $user_id, 'discord_role_id', $role_id );
			return $response;
		}
	}

	/**
	 * Description: Call API to delete existing discord user role
	 * @param int $user_id
	 * @return object API response 
	 */
	public function delete_discord_role( $user_id )
	{
		$access_token = get_user_meta( $user_id, "discord_access_token", true );
		$guild_id = get_option( 'discord_guild_id' );
		$discord_user_id = get_user_meta( $user_id, 'discord_user_id', true);
		$discord_bot_token = get_option( 'ets_discord_bot_token' );
		$discord_role_id = get_user_meta( $user_id, 'discord_role_id', true );
		$discord_delete_role_api_url = ETS_DISCORD_API_URL.'guilds/'.$guild_id.'/members/'.$discord_user_id.'/roles/'.$discord_role_id;
    
		if ( $discord_user_id ) {
			$param = array(
					'method'=> 'DELETE',
				    'headers' => array(
				        'Content-Type' => 'application/json',
				        'Authorization' => 'Bot ' .$discord_bot_token,
				        'Content-Length' => 0
				    )
				);
			
			$response = wp_remote_request( $discord_delete_role_api_url, $param );
			return $response;
		}
	}

	/**
	 * Description: Change discord role form pmpro role
	 * @param int $level_id
	 * @param int $user_id
	 * @param int $cancel_level
	 * @return object API response
	 */
	public function change_discord_role_from_pmpro( $level_id, $user_id, $cancel_level )
	{
		$discord_user_id = get_user_meta($user_id, 'discord_user_id',true);
		if ( $discord_user_id ) {
			$role_delete = $this->delete_discord_role( $user_id );
			$ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
			$role_id = '';
			$curr_level_id = $this->get_current_level_id($user_id);
			if( $level_id )
			{
				$role_id = $ets_discord_role_mapping['level_id_'.$level_id];
			}
			if ( $cancel_level ) {
				$role_id = $ets_discord_role_mapping['level_id_expired'];
			}
			$role_change = $this->change_discord_role_api($user_id, $role_id);
		}
	}

	/**
	 * Description:disconnect user from discord
	 * @param none
	 * @return Object json response
	 */
	public function disconnect_from_discord()
	{
		$user_id = $_POST['user_id'];
		$ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
		$role_id = '';
		$role_id = $ets_discord_role_mapping['level_id_expired'];
		$res = $this->delete_discord_role( $user_id );
		$response = $this->change_discord_role_api( $user_id.'1', $role_id );
		delete_user_meta( $user_id, 'discord_access_token' );
		$event_res = array(
			"status"  => 1,
			"message" => "Successfully disconnected"
		);
		echo json_encode($event_res);
		die();
	}

	/**
	 * Description: set discord spectator role on pmpro expiry 
	 * @param int $user_id
	 * @param int $level_id
	 * @return None
	 */
	public function pmpro_expiry_membership( $user_id, $level_id )
	{	
		$ets_discord_role_mapping = json_decode(get_option( 'ets_discord_role_mapping' ), true );
		$role_id = '';
		$role_id = $ets_discord_role_mapping['level_id_expired'];
		$role_delete = $this->delete_discord_role( $user_id );
		$response = $this->change_discord_role_api( $user_id, $role_id );
		update_option('expire_pmpro_member_1','expire');
	}

}
$ets_pmpro_admin_setting = new Ets_Pmpro_Admin_Setting();
