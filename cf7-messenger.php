<?php
/*
  Plugin Name: Cf7 Messenger
  Plugin URI: http://wpclever.net
  Description: Send the Contact Form 7 data to your Facebook Messenger immediately.
  Version: 1.3
  Author: CleverWP
  Author URI: http://wpclever.net/about
 */
//redirect after active plugin
register_activation_hook( __FILE__, 'cf7_messenger_activate' );
add_action( 'admin_init', 'cf7_messenger_redirect' );
function cf7_messenger_activate() {
	add_option( 'cf7_messenger_do_activation_redirect', true );
}

function cf7_messenger_redirect() {
	if ( get_option( 'cf7_messenger_do_activation_redirect', false ) ) {
		delete_option( 'cf7_messenger_do_activation_redirect' );
		wp_redirect( 'options-general.php?page=cf7-messenger' );
	}
}

//add admin menu
add_action( 'admin_menu', 'cf7_messenger_admin_menu' );
function cf7_messenger_admin_menu() {
	add_submenu_page( 'options-general.php', 'Cf7 Messenger', 'Cf7 Messenger', 'manage_options', 'cf7-messenger', 'cf7_messenger_settings_page' );
}

function cf7_messenger_settings_page() {
	?>
	<div class="wrap vpxb_welcome">
		<h1>Welcome to Cf7 Messenger</h1>

		<div class="about-text">
			Send the Contact Form 7 data to your Facebook Messenger immediately.
		</div>

		<form method="post" action="options.php" novalidate="novalidate">
			<?php wp_nonce_field( 'update-options' ) ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="cf7_messenger_connect">Connect Code</label></th>
					<td>
						<input name="cf7_messenger_connect" type="text" id="cf7_messenger_connect"
						       value="<?php echo get_option( 'cf7_messenger_connect' ); ?>" placeholder="wpxxxxxxxxxxxxxxx" />
						<p>To get connect code, please open this link
							<a href="http://m.me/wpchatbot" target="_blank">http://m.me/wpchatbot</a> then type
							<strong>#connect</strong>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Don't Send Detail</th>
					<td>
						<p>
							<input name="cf7_messenger_nodetail" type="checkbox" id="cf7_messenger_nodetail"
							       value="1" <?php checked( '1', get_option( 'cf7_messenger_nodetail' ) ); ?>/>
							<label for="cf7_messenger_nodetail">Check this box if you want to receive the notification without contact detail</label>
						</p>
						<p>The message is:
							<i>"<?php echo 'Have a new message for you at ' . get_bloginfo( 'name' ) . ' (' . get_site_url() . ')'; ?>"</i>
						</p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options"
				       value="cf7_messenger_connect, cf7_messenger_nodetail" />
				<input type="submit" name="submit" id="submit" class="button button-primary"
				       value="Save Changes" />
			</p>
		</form>
	</div>
	<?php
}

//send messenger before send email
function cf7_messenger_send( $contact_form ) {
	if ( get_option( 'cf7_messenger_connect', null ) !== null ) {
		$cf7_messenger_connect = get_option( 'cf7_messenger_connect' );
		if ( get_option( 'cf7_messenger_nodetail' ) == '1' ) {
			$cf7_mailbody = 'Have a new message for you at ' . get_bloginfo( 'name' ) . ' (' . get_site_url() . ')';
		} else {
			$wpcf7        = WPCF7_ContactForm::get_current();
			$submission   = WPCF7_Submission::get_instance();
			$cf7_mail     = $wpcf7->prop( 'mail' );
			$cf7_mailbody = $cf7_mail['body'];
			if ( $submission ) {
				$posted_data = $submission->get_posted_data();
				foreach ( $posted_data as $key => $value ) {
					$cf7_mailbody = str_replace( '[' . $key . ']', $value, $cf7_mailbody );
				}
			}
		}
		$content = array(
			'to'      => $cf7_messenger_connect,
			'content' => $cf7_mailbody
		);
		$ch      = curl_init();
		curl_setopt( $ch, CURLOPT_URL, "https://wpclever.net/fa/index.php" );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $content ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_exec( $ch );
		curl_close( $ch );
	}
}

add_action( "wpcf7_before_send_mail", "cf7_messenger_send" );
?>