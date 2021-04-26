# Discord Add-on for PaidMembershipPro
Contributors: www.expresstechsoftwares.com  
Tags: paid memberships pro, discord, discord-server  
Requires at least: 4.9  
Tested up to: 5.4  
Requires PHP: 7.1  
License: GPLv2  
License URI: http://www.gnu.org/licenses/gpl  
## Description
This add-on enables connecting your PMPRO enabled website to your discord server.
Now you can add/remove PMPRO members directly to your discord server roles, assign roles according to your member levels, unassign roles when membership expire, change role when member change membership.

## Some features
  - Add "Connect to Discord" button inside the profile of the member.
  - Auto assign the discord role to your discord members.
  - Admin assign/change/remove discord roles by removing pmpro level access.
  - Assign default role upon expiry/cancellation of membership.
  - Allow members to join discord group even they do not have active membership.
  - Intuitive UI to map discord roles and levels.
  - Error log management to track issues.
## Installation
- You can find the plugin inside the PMPRO settings Add-ons
- OR Upload the `pmpro-discord-add-on` folder to the `/wp-content/plugins/` directory.
- Activate the plugin through the 'Installed Plugins' page in WordPress admin.

## Connecting the plugin to your Discord.
- Inside WP Admin, you will find Discord Settings sub-menu under top-level PMPRRO Memberships menu in the left hand side.
- Login to your dsicord account and open this url: https://discord.com/developers/applications
- Click Top right button "New Appliaction", and name your Application and do create.
- New screen will load, you need to look at left hand side and see "oAuth"
- See right hand side, you will see "CLIENT ID and CLIENT SECRET" values copy them.
- Open this link in your browser {yoursiteurl}/wp-admin/admin.php?page=discord-options
- Paste the copied ClientID and ClientSecret.
- If the PMPRO is already stored you will see redirect URL inside plugin settings. Just copy it and paste into Discord "Redirect URL" then save settings in Discord.
- Now again see inside discord left hand side menu, you will see "Bot"
- This is very important, you need to name your bot and click generate, this will generate "Bot Token".
- Copy the "Bot Token" and paste into "Bot Token" setting of WP Plugin.
- Now the last and most important setting, "Server Guild ID".
-- Open https://discord.com/ and go inside your server.
-- You will see URL like `https://discord.com/channels/807607432821604352/807607432821604355` the second long series of numbers is the guild Id of the server.
-- In our example, it is `807607432821604352`
-- You can follow this tutorial to get Guild ID as well.
-- View our video tutorial over here. 
