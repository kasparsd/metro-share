<?php
/*
Plugin Name: Metro Share
Plugin URI: http://metronet.no
Description: Super fast and super customizable social sharing
Version: 0.5
Author: Metronet AS
Author URI: http://metronet.no
Text Domain: metroshare
*/

/*
 * Temporary function added until this plugin is set to handle auto-updates
 * This prevents malicious code from being pushed to sites via the
 * WordPress.org plugin update API. This should be moved when auto-updating is
 * implemented in the plugin, or when it is uploaded to WordPress.org.
 *
 * Based on code from Mark Jaquith (http://markjaquith.wordpress.com/2009/12/14/excluding-your-plugin-or-theme-from-update-checks/)
 *
 * @author Ryan Hellyer <ryan@metronet.no>
 * @since 0.5
 * @param array $r    http request header
 * @param string $url The update URL being checked
 */
function metroshare_hidden_plugin( $r, $url ) {
	if ( 0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check' ) )
		return $r; // Not a plugin update request. Bail immediately.
	$plugins = unserialize( $r['body']['plugins'] );
	unset( $plugins->plugins[ plugin_basename( __FILE__ ) ] );
	unset( $plugins->active[ array_search( plugin_basename( __FILE__ ), $plugins->active ) ] );
	$r['body']['plugins'] = serialize( $plugins );
	return $r;
}
add_filter( 'http_request_args', 'metroshare_hidden_plugin', 5, 2 );



new Metro_Share;

/**
 * Metro Share class
 * 
 * @copyright Copyright (c), Metronet
 * @author Kaspars Dambis <kaspars@metronet.no>
 * @since 0.4
 */
class Metro_Share {

	var $settings = array();
	var $destinations = array();

	/*
	 * Class constructor
	 *
	 * @since 0.4
	 * @author Ryan Hellyer <ryan@metronet.no>
	 */
	public function __construct() {

		// Admin
		add_action( 'init',                  array( $this, 'load_settings' ) );
		add_action( 'admin_menu',            array( $this, 'register_admin_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Frontend
		add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'metroshare',            array( $this, 'show_sharing_icons' ) );
		add_action( 'wp_footer',             array( $this, 'maybe_close_facebook_redirect' ) );
		add_action( 'the_content',           array( $this, 'show_the_content' ) );
	}

	/*
	 * Close Facebook ... 
	 *
	 * @since 0.4
	 * @author Kaspars Dambis <kaspars@metronet.no>
	 */
	public function maybe_close_facebook_redirect() {

		if ( ! isset( $_GET['metroshare'] ) )
			return;

		if ( $_GET['metroshare'] == 'done' )
			echo '<script type="text/javascript">self.close();</script>';

	}

	/*
	 * Load Javascript and CSS into admin
	 *
	 * @since 0.4
	 * @author Kaspars Dambis <kaspars@metronet.no>
	 */
	public function enqueue_admin_scripts( $page ) {
		if ( strstr( $page, 'metro-share' ) == -1 )
			return;

		wp_enqueue_script( 'metroshare-admin-js', plugins_url( '/assets/metroshare-admin.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable' ) );
		wp_enqueue_style( 'metroshare-admin-css', plugins_url( '/assets/metroshare-admin.css', __FILE__ ) );
	}

	/*
	 * Load Javascript and CSS into frontend of site
	 *
	 * @since 0.4
	 * @author Kaspars Dambis <kaspars@metronet.no>
	 */
	public function enqueue_frontend_scripts() {
		wp_enqueue_style( 'metroshare-css', plugins_url( '/assets/metroshare.css', __FILE__ ) );
		wp_enqueue_script( 'metroshare', plugins_url( '/assets/metroshare.js', __FILE__ ), array( 'jquery' ), null, true );
		// wp_localize_script( 'metroshare', 'metroshare', array( 'true' => true ) );
	}

	/*
	 * Register admin page settings
	 *
	 * @since 0.4
	 * @author Kaspars Dambis <kaspars@metronet.no>
	 */
	public function register_admin_settings() {
		register_setting(
			'metroshare_settings',
			'metroshare_settings',
			array( $this, 'validate' )
		);
		add_submenu_page( 'options-general.php', 'Metroshare Settings', 'Metroshare', 'administrator', __FILE__, array( $this, 'metroshare_settings_display' ) );
	}
	
	/*
	 * Sanitising each chunk of data submitted from the admin page
	 *
	 * @since 0.5
	 * @author Ryan Hellyer <ryan@metronet.no>
	 */
	public function sanitise_chunk( $input ) {
		$output = array();
		if ( ! empty( $input['enabled'] ) )
			$output['enabled']  = sanitize_title( $input['enabled'] );
		if ( isset( $input['message'] ) )
			$output['message']  = wp_kses( $input['message'], '', '' );
		if ( isset( $input['username'] ) )
			$output['username'] = sanitize_user( $input['username'] );
		if ( isset( $input['app_id'] ) ) {
			$output['app_id'] = abs( $input['app_id'] );
			if ( 0 == $output['app_id'] )
				$output['app_id']  = '';
		}
		return $output;
	}

	/*
	 * Validation/sanitisation of data inputted from admin page
	 *
	 * @since 0.5
	 * @author Ryan Hellyer <ryan@metronet.no>
	 */
	public function validate( $input ) {

		$output = array();
		if ( isset( $input['prefix'] ) )
			$output['prefix'] = wp_kses( $input['prefix'], '', '' );
		if ( isset( $input['allposts'] ) )
			$output['allposts'] = (bool) $input['allposts'];
		$destinations = $input['destinations'];

		foreach( $destinations as $destination => $value ) {
			$output['destinations'][$destination] = $this->sanitise_chunk( $destinations[$destination] );
		}

		return $output;
	}

	/*
	 * Load settings for admin pages
	 *
	 * @since 0.4
	 * @author Kaspars Dambis <kaspars@metronet.no>
	 */
	public function load_settings() {
		// Load user settings
		$this->settings = get_option( 'metroshare_settings' );

		$this->destinations['twitter'] = array(
				'title' => 'Twitter',
				'action' => 'https://twitter.com/share',
				'fields' => array(
					'message' => array( 
						'type' => 'textarea',
						'label' => __( 'Default message:', 'metroshare' ),
						'help' => __( 'You can use the following tags: <code>{{title}}</code>, <code>{{link}}</code>.', 'metroshare' )
					),
					'username' => array( 
						'type' => 'text',
						'label' => __( 'Your Twitter handle:', 'metroshare' ),
						'help' => __( 'This will be appended to the tweet automatically.', 'metroshare' )
					)
				),
				'hidden' => array(
					'url' => '{{link}}',
					'via' => '{{username}}',
					'text' => '{{message}}'
				)
			);

		// https://developers.facebook.com/docs/reference/dialogs/feed/
		$this->destinations['facebook'] = array(
				'title' => 'Facebook',
				'action' => 'https://www.facebook.com/dialog/feed',
				'fields' => array(
					'app_id' => array( 
						'type' => 'text',
						'label' => __( 'App ID:', 'metroshare' ),
						'help' => __( 'Facebook requires an Application ID which you can create at the <a href="https://developers.facebook.com/apps/">developers center</a>.', 'metroshare' )
					)
				),
				'hidden' => array(
					'app_id' => '{{app_id}}',
					'link' => '{{link}}',
					'redirect_uri' => '{{link}}?metroshare=done',
					'display' => 'popup'
				)
			);

		// https://developers.google.com/+/plugins/share/
		$this->destinations['google-plus'] = array(
				'title' => 'Google+',
				'description' => __( 'There is nothing to configure. Google+ automatically extracts all content meta data from the open graph tags on the page.', 'metroshare' ),
				'action' => 'https://plus.google.com/share',
				'hidden' => array(
					'url' => '{{link}}'
				)
			);

		// https://developer.linkedin.com/documents/share-linkedin
		$this->destinations['linkedin'] = array(
				'title' => 'LinkedIn',
				'description' => __( 'There is nothing to configure.', 'metroshare' ),
				'action' => 'http://www.linkedin.com/shareArticle',
				'fields' => array(),
				'hidden' => array(
					'title' => '{{post_title}}',
					'url' => '{{link}}',
					'mini' => true
				)			
			);

		$this->destinations['email'] = array(
				'title' => 'Email',
				'action' => 'http://api.addthis.com/oexchange/0.8/forward/email/offer',
				'fields' => array(
					'message' => array( 
						'type' => 'textarea',
						'label' => __( 'Default message:', 'metroshare' )
					),
				),
				'hidden' => array(
					'note' => '{{message}}',
					'url' => '{{link}}'
				)
			);

		$this->destinations = apply_filters( 'metroshare_destinations', $this->destinations );

		// Sort destinations according to user preferences
		if ( isset( $this->settings['destinations'] ) )
			$this->destinations = array_merge( $this->settings['destinations'], $this->destinations );
		
	}

	/*
	 * Primary function used for displaying share icon via the_content() filter
	 *
	 * @since 0.5
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @param string $content The post content
	 * @global int   $post    The primary post object
	 * @return string
	 */
	public function show_the_content( $content = '' ) {
		global $post;

		// If not the current post, then move along ...
		$this->settings = get_option( 'metroshare_settings' );
		if ( is_singular() && $post->ID == get_queried_object_id() || $this->settings['allposts'] == true ) {
			$icons = $this->get_sharing_icons();
			$content .= $icons;
		}

		// Finally, return the content
		return $content;
	}

	/*
	 * Primary function used for displaying share icon
	 *
	 * @since 0.5
	 * @author Ryan Hellyer <ryan@metronet.no>
	 */
	public function show_sharing_icons() {
		echo $this->get_sharing_icons();
	}

	/*
	 * Generate the sharing icons HTML
	 *
	 * @since 0.5
	 * @author Ryan Hellyer <ryan@metronet.no>
	 * @return string or null
	 */
	public function get_sharing_icons() {
		$items = array();
		$tabs = array();

		// Process each potential sharing destination
		foreach ( $this->settings['destinations'] as $d => $destination ) {

			$replace = array(
				'{{title}}'      => get_the_title(),
				'{{post_title}}' => get_the_title(),
				'{{link}}'       => get_permalink(),
				'{{shortlink}}'  => wp_get_shortlink(),
			);

			// Add custom destination settings fields to the replace variables
			if ( isset( $this->destinations[ $d ]['fields'] ) )
				foreach ( $this->destinations[ $d ]['fields'] as $field_name => $field_settings )
					if ( isset( $destination[ $field_name ] ) )
						$replace[ sprintf( '{{%s}}', $field_name ) ] = strtr( $destination[ $field_name ], $replace );

			$hidden_fields = array();

			// Append hidden fields to the form
			if ( isset( $this->destinations[ $d ]['hidden'] ) )
				foreach ( $this->destinations[ $d ]['hidden'] as $field_name => $field_value ) {
					if ( ! empty( $field_value ) )
						$hidden_fields[] = sprintf( '<input type="hidden" name="%s" value="%s" />', $field_name, strtr( $field_value, $replace ) );
				}

			// If sharing destination is enabled, then display list item and link
			if ( isset( $destination['enabled'] ) )
				$tabs[] = sprintf( 
						'<li class="metroshare-%s"><a href="#destination-%s"><span class="icon"></span>%s</a></li>',  
						$d,
						$d,
						esc_html( $this->destinations[ $d ]['title'] )
					);

			// If sharing destination is enabled, then display form
			if ( isset( $destination['enabled'] ) ) {
				$items[] = sprintf( 
						'<form id="destination-%s" class="destination-tab" action="%s" method="get">
							%s
						</form>',
						$d,
						$this->destinations[ $d ]['action'],
						implode( '', $hidden_fields )
					);
			}
		}

		// Generate final HTML and add it to the main post content
		if ( ! empty( $items ) ) {
			$share_html = sprintf( 
					'<div class="metroshare">
						<h4 class="share-prefix">%s</h4>
						<ul class="metro-tabs">%s</ul>
						%s
					</div>',
					esc_html( $this->settings['prefix'] ),
					implode( '', $tabs ),
					implode( '', $items ) 
				);
			return $share_html;
		}

	}

	/*
	 * Displays the admin page settings content
	 *
	 * @since 0.4
	 * @author Kaspars Dambis <kaspars@metronet.no>
	 */
	public function metroshare_settings_display() {
		$network_settings = array();
		$enabled_settings = array(); 

		foreach ( $this->destinations as $n => $network ) {
			$enabled_settings[ $n ] = sprintf( 
					'<li>
						<label>
							<input name="metroshare_settings[destinations][%s][enabled]" type="checkbox" value="%s" %s /> 
							%s
						</label>
					</li>',
					esc_attr( $n ),
					esc_attr( $n ),
					checked( $this->settings['destinations'][ $n ]['enabled'], esc_attr( $n ), false ),
					esc_html( $network['title'] )
				);

			$network_settings[ $n ] = sprintf( 
					'<tr id="destination-%s" class="metroshare-destination">
						<th>%s</th>
						<td>%s</td>
					</tr>',
					$n,
					$network['title'],
					implode( '', $this->network_settings_fields( $n ) )
				);

		}

		echo '<form action="options.php" method="post">';
		settings_fields( 'metroshare_settings' );

		printf( 
				'<div class="wrap metroshare-wrap">
					<h2>%s</h2>
					
					<table class="form-table metroshare-global">
						<tr class="metroshare-prefix">
							<th>%s</th>
							<td>
								<input class="regular-text" type="text" name="metroshare_settings[allposts]" value="%s" />
							</td>
						</tr>
						<tr class="submit">
							<th>%s</th>
							<td>
								<p>
									<input type="checkbox" name="metroshare_settings[allposts]" value="1" %s />
								</p>
							</td>
						</tr>
						<tr class="metroshare-tabs">
							<th>%s</th>
							<td>
								<p>%s</p>
								<ul>%s</ul>
							</td>
						</tr>
						<tr class="submit">
							<th></th>
							<td>
								<p><input type="submit" class="button-primary" value="%s" /></p>
							</td>
						</tr>
					</table>

					<h3>%s</h3>
					<table class="form-table metroshare-destinations">
						%s
						<tr class="submit">
							<th></th>
							<td>
								<p><input type="submit" class="button-primary" value="%s" /></p>
							</td>
						</tr>
					</table>
				</div>',
				__( 'Metroshare Settings', 'metroshare' ),
				__( 'Invitation Text', 'metroshare' ),
				esc_attr( $this->settings['prefix'] ),
				'Display in <strong>all</strong> post areas?',
				checked( $this->settings['allposts'], true, false ),
				__( 'Sharing Destinations', 'metroshare' ),
				__( 'Select which sharing destinations you want to enable and use drag and drop to change their order:', 'metroshare' ),
				implode( '', $enabled_settings ),
				__( 'Update', 'metroshare' ),
				__( 'Destination Settings', 'metroshare' ),
				implode( '', $network_settings ),
				__( 'Update', 'metroshare' )
			);

		echo '</form>';
	}

	/*
	 * Adding fields to admin page
	 *
	 * @since 0.4
	 * @author Kaspars Dambis <kaspars@metronet.no>
	 * @todo Document @param here
	 * @return array
	 */
	public function network_settings_fields( $n ) {
		$fields = array();

		$inputs = array(
			'text' => '<input type="text" class="input-text" name="metroshare_settings[destinations][%1$s][%2$s]" value="%3$s" />',
			'textarea' => '<textarea class="input-textarea" name="metroshare_settings[destinations][%1$s][%2$s]">%3$s</textarea>'
		);

		if ( isset( $this->destinations[ $n ]['description'] ) )
			$fields['destination-desc'] = sprintf( '<p class="destination-desc">%s</p>', $this->destinations[ $n ]['description'] );

		if ( ! empty( $this->destinations[ $n ]['fields'] ) )
			foreach ( $this->destinations[ $n ]['fields'] as $f => $custom_field ) {

				if ( isset( $custom_field['help'] ) )
					$help = sprintf( '<span class="field-help">%s</span>', $custom_field['help'] );
				else
					$help = '';

				$fields[ $f ] = sprintf( 
						'<p class="input-wrap field-%s">
							<label class="label-%s">
								<strong class="label">%s</strong>
								%s
							</label>
						</p>',
						esc_attr( $f ),
						esc_attr( $f ),
						esc_html( $custom_field['label'] ),
						sprintf( 
							$inputs[ $custom_field['type'] ], 
							esc_attr( $n ), 
							esc_attr( $f ), 
							esc_attr( $this->settings['destinations'][ $n ][ $f ] ) 
						) . $help
					);
			}

		return apply_filters( 'metroshare-settings-fields', $fields, $n );
	}

}

