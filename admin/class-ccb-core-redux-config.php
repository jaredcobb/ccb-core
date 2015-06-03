<?php
/**
 * Config settings for the ReduxFramework
 *
 * @link       http://jaredcobb.com/ccb-core
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 */

/**
 * Config settings for the ReduxFramework
 *
 * Defines the fields that will be used in the plugin settings
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/admin
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Redux_Config extends CCB_Core_Plugin {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.9.0
	 */

	/**
	 * A hash of style and message for the latest sync results
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      array    $latest_sync_message
	 */
	protected $latest_sync_message;

	/**
	 * Initialize the ReduxFramework
	 *
	 * @since    0.9.0
	 */
	public function initialize() {

		$option_name = $this->plugin_options_name;
		$this->latest_sync_message = $this->get_latest_sync_results();

		$args = array(
			'admin_bar' => false,
			'class' => $this->plugin_name,
			'dev_mode' => false,
			'display_name' => $this->plugin_display_name,
			'display_version' => $this->version,
			'footer_text' => '',
			'footer_credit' => $this->plugin_display_name,
			'hints' => array(
				'icon' => 'el el-question-sign',
				'icon_position' => 'left',
				'icon_color' => '#999',
				'icon_size' => 'normal',
				'tip_style' => array(
					'color' => 'light',
					'shadow' => true,
					'rounded' => true,
					'style' => '',
				),
				'tip_position' => array(
					'my' => 'top left',
					'at' => 'bottom right',
				),
				'tip_effect' => array(
					'show' => array(
						'effect' => 'slide',
						'duration' => '500',
						'event' => 'mouseover',
					),
					'hide' => array(
						'effect' => 'slide',
						'duration' => '500',
						'event' => 'click mouseleave',
					),
				),
			),
			'intro_text' => '',
			'menu_title' => $this->plugin_display_name,
			'menu_type' => 'submenu',
			'open_expanded' => false,
			'option_name' => $option_name,
			'page_parent' => 'options-general.php',
			'page_slug' => $this->plugin_name,
			'page_title' => $this->plugin_display_name,
			'show_import_export' => false,
		);

		Redux::setArgs( $option_name, $args );

		$tabs = array(
			array(
				'id' => $this->plugin_name . '-help-tab',
				'title' => __( 'Settings FAQ', $this->plugin_name ),
				'content' => __( '<p>Coming soon</p>', $this->plugin_name )
			),
		);
		Redux::setHelpTab( $option_name, $tabs );

		Redux::setSection( $option_name,
			array(
				'title' => __( 'About', $this->plugin_name ),
				'id' => 'about',
				'desc' => __( '', $this->plugin_name ),
				'icon' => 'el el-home',
				'fields' => array(
					array(
						'id' => 'about',
						'type' => 'raw',
						'class' => 'about',
						'content' => $this->get_about_content(),
					),
				)
			)
		);

		Redux::setSection( $option_name,
			array(
				'title' => __( 'API Settings', $this->plugin_name ),
				'id' => 'api-user',
				'desc' => __( '', $this->plugin_name ),
				'icon' => 'el el-adjust-alt',
				'fields' => array(
					array(
						'id' => 'subdomain',
						'type' => 'text',
						'title' => __( 'Software Subdomain', $this->plugin_name ),
						'subtitle' => __( 'http://<strong><em>your-church-subdomain</em></strong>.ccbchurch.com', $this->plugin_name ),
						'placeholder' => 'your-chuch-subdomain',
						'validate' => 'preg_replace',
						'preg' => array(
							'pattern' => '/(http:\/\/)|(.ccbchurch.com.*)/s',
							'replacement' => '',
						),
						'msg' => 'Please enter your subdomain without "http://" or ".ccbchurch.com"',
						'hint' => array(
							'title' => "What's a subdomain?",
							'content' => 'We just need the first part of your software URL (without <em>http://</em> and without <em>ccbchurch.com</em>).',
						),
					),
					array(
						'id' => 'password',
						'type' => 'password',
						'username' => true,
						'title' => 'API Credentials',
						'subtitle' => __( 'The Church Community Builder API credentials are set in your software solution. See <a href="https://support.churchcommunitybuilder.com/customer/portal/articles/640595-creating-an-api-user-and-assigning-services" target="blank">Creating an API User and Assigning Services</a>.', $this->plugin_name ),
						'validate_callback' => array( $this, 'encrypt_credentials' ),
						'msg' => 'There was a problem saving your password',
						'callback' => array( $this, 'password_callback' ),
					),
					array(
						'id' => 'test-login',
						'type' => 'raw',
						'title' => __( 'Test Credentials', $this->plugin_name ),
						'subtitle' => __( 'See if you can connect to the Church Community Builder API.<br><em>(Ensure you have already clicked <strong>Save Changes</strong> if adding / editing your credentials).</em>', $this->plugin_name ),
						'class' => 'ccb-core-test-login',
						'content' => $this->get_test_credentials_content(),
					),
				)
			)
		);

		Redux::setSection( $option_name,
			array(
				'title' => __( 'Groups', $this->plugin_name ),
				'id' => 'groups',
				'desc' => __( '', $this->plugin_name ),
				'icon' => 'el el-group',
				'fields' => array(
					array(
						'id' => 'groups-enabled',
						'type' => 'switch',
						'title' => __( 'Groups', $this->plugin_name ),
						'subtitle' => __( 'Enable the `group_profiles` service.', $this->plugin_name ),
						'default' => false,
					),
					array(
						'id' => 'groups-name',
						'type' => 'text',
						'title' => __( 'Groups Display Name', $this->plugin_name ),
						'subtitle' => __( 'What do you call your groups?<br><em>(Small Groups, Connection Groups, Core Groups, etc).<em>', $this->plugin_name ),
						'required' => array( 'groups-enabled', 'equals', '1' ),
						'default' => __( 'Groups', $this->plugin_name ),
						'validate_callback' => array( $this, 'validate_name' ),
						'msg' => __( "Oops, please don't use special characters in your name.", $this->plugin_name ),
					),
					array(
						'id' => 'groups-slug',
						'type' => 'text',
						'title' => __( 'Groups URL Name', $this->plugin_name ),
						'subtitle' => __( 'WordPress calls this a slug. What word would you like to use in your URL strucutre?<br><em>For example:<br>www.yoursite.org/<strong>groups</strong>/group-name <br> www.yoursite.org/<strong>connections</strong>/group-name.<em>', $this->plugin_name ),
						'required' => array( 'groups-enabled', 'equals', '1' ),
						'default' => __( 'groups', $this->plugin_name ),
						'validate_callback' => array( $this, 'validate_slug' ),
						'msg' => __( "Oops, please don't use special characters in your slug name (but dashes and underscores are okay).", $this->plugin_name ),
					),
					array(
						'id' => 'groups-show-advanced',
						'type' => 'switch',
						'title' => __( 'Show Advanced Settings?', $this->plugin_name ),
						'subtitle' => __( "We've already configured these for you based on the most common settings.", $this->plugin_name ),
						'required' => array( 'groups-enabled', 'equals', '1' ),
						'default' => false,
						'on' => 'Yes',
						'off' => 'No',
					),
					array(
						'id' => 'groups-advanced',
						'type' => 'section',
						'title' => __( 'Advanced Settings <em>(Defaults are usually just fine)</em>', $this->plugin_name ),
						'required' => array(
							array( 'groups-enabled', 'equals', '1' ),
							array( 'groups-show-advanced', 'equals', '1' ),
						),
						'indent' => true,
					),
					array(
						'id' => 'groups-exclude-from-search',
						'type' => 'switch',
						'title' => __( 'Exclude From Search?', $this->plugin_name ),
						'subtitle' => __( 'Should these Groups be hidden from the search results on the front end of your site?', $this->plugin_name ),
						'required' => array(
							array( 'groups-enabled', 'equals', '1' ),
							array( 'groups-show-advanced', 'equals', '1' ),
						),
						'default' => false,
						'on' => __( 'Yes', $this->plugin_name ),
						'off' => __( 'No', $this->plugin_name ),
					),
					array(
						'id' => 'groups-publicly-queryable',
						'type' => 'switch',
						'title' => __( 'Publicly Queryable?', $this->plugin_name ),
						'subtitle' => __( 'Should these Groups be available on the front end of your site?<br><em>(Most widgets and themes require this to be Yes)<em>', $this->plugin_name ),
						'required' => array(
							array( 'groups-enabled', 'equals', '1' ),
							array( 'groups-show-advanced', 'equals', '1' ),
						),
						'default' => true,
						'on' => __( 'Yes', $this->plugin_name ),
						'off' => __( 'No', $this->plugin_name ),
					),
					array(
						'id' => 'groups-show-ui',
						'type' => 'switch',
						'title' => __( 'Show In Admin UI?', $this->plugin_name ),
						'subtitle' => __( 'Should we be able to see these groups in the WordPress Admin sidebar?', $this->plugin_name ),
						'required' => array(
							array( 'groups-enabled', 'equals', '1' ),
							array( 'groups-show-advanced', 'equals', '1' ),
						),
						'default' => true,
						'on' => __( 'Yes', $this->plugin_name ),
						'off' => __( 'No', $this->plugin_name ),
					),
					array(
						'id' => 'groups-show-in-nav-menus',
						'type' => 'switch',
						'title' => __( 'Show In Navigation Menus?', $this->plugin_name ),
						'subtitle' => __( 'Should individual groups be available to navigation menus?', $this->plugin_name ),
						'required' => array(
							array( 'groups-enabled', 'equals', '1' ),
							array( 'groups-show-advanced', 'equals', '1' ),
						),
						'default' => false,
						'on' => __( 'Yes', $this->plugin_name ),
						'off' => __( 'No', $this->plugin_name ),
					),
					array(
						'id' => 'groups-advanced-end',
						'type' => 'section',
						'required' => array(
							array( 'groups-enabled', 'equals', '1' ),
							array( 'groups-show-advanced', 'equals', '1' ),
						),
						'indent' => false,
					),
				)
			)
		);

		Redux::setSection( $option_name,
			array(
				'title' => __( 'Public Events', $this->plugin_name ),
				'id' => 'calendar',
				'desc' => __( '', $this->plugin_name ),
				'icon' => 'el el-calendar',
				'fields' => array(
					array(
						'id' => 'calendar-enabled',
						'type' => 'switch',
						'title' => __( 'Public Events', $this->plugin_name ),
						'subtitle' => __( 'Enable the `public_calendar_listing` service.', $this->plugin_name ),
						'default' => false,
					),
					array(
						'id' => 'calendar-name',
						'type' => 'text',
						'title' => __( 'Events Display Name', $this->plugin_name ),
						'subtitle' => __( 'What do you call your events?<br><em>(Meetups, Hangouts, etc).<em>', $this->plugin_name ),
						'required' => array( 'calendar-enabled', 'equals', '1' ),
						'default' => __( 'Events', $this->plugin_name ),
						'validate_callback' => array( $this, 'validate_name' ),
						'msg' => __( "Oops, please don't use special characters in your name.", $this->plugin_name ),
					),
					array(
						'id' => 'calendar-slug',
						'type' => 'text',
						'title' => __( 'Events URL Name', $this->plugin_name ),
						'subtitle' => __( 'WordPress calls this a slug. What word would you like to use in your URL strucutre?<br><em>For example:<br>www.yoursite.org/<strong>calendar</strong>/group-name <br> www.yoursite.org/<strong>events</strong>/group-name.<em>', $this->plugin_name ),
						'required' => array( 'calendar-enabled', 'equals', '1' ),
						'default' => __( 'events', $this->plugin_name ),
						'validate_callback' => array( $this, 'validate_slug' ),
						'msg' => __( "Oops, please don't use special characters in your slug name (but dashes and underscores are okay).", $this->plugin_name ),
					),
					array(
						'id' => 'calendar-show-advanced',
						'type' => 'switch',
						'title' => __( 'Show Advanced Settings?', $this->plugin_name ),
						'subtitle' => __( "We've already configured these for you based on the most common settings.", $this->plugin_name ),
						'required' => array( 'calendar-enabled', 'equals', '1' ),
						'default' => false,
						'on' => 'Yes',
						'off' => 'No',
					),
					array(
						'id' => 'calendar-advanced',
						'type' => 'section',
						'title' => __( 'Advanced Settings <em>(Defaults are usually just fine)</em>', $this->plugin_name ),
						'required' => array(
							array( 'calendar-enabled', 'equals', '1' ),
							array( 'calendar-show-advanced', 'equals', '1' ),
						),
						'indent' => true,
					),
					array(
						'id' => 'calendar-date-range-type',
						'type' => 'button_set',
						'title' => __( 'Date Range Type', $this->plugin_name ),
						'subtitle' => __( 'Which events should we get?', $this->plugin_name ),
						'required' => array(
							array( 'calendar-enabled', 'equals', '1' ),
							array( 'calendar-show-advanced', 'equals', '1' ),
						),
						'options' => array( 'relative' => 'Relative Range', 'specific' => 'Specific Range' ),
						'default' => 'relative',
						'hint' => array(
							'title' => "What's Relative & Specific?",
							'content' => "<strong>Relative:</strong> For example, always get the events from <em>'One week ago'</em>, up to <em>'Eight weeks from now'</em>. This is the best setting for most churches.<br><br><strong>Specific:</strong> For example, only get events from <em>'6/1/2015'</em> to <em>'12/1/2015'</em>. This setting is best if you want to tightly manage the events that get published.",
						),
					),
					array(
						'id' => 'calendar-relative-weeks-past',
						'type' => 'slider',
						'title' => __( 'How Far Back?', $this->plugin_name ),
						'subtitle' => __( 'Every time we synchronize, how many <strong>weeks</strong> in the past should we look?<br><em>(0 would be "today")</em>', $this->plugin_name ),
						'required' => array(
							array( 'calendar-enabled', 'equals', '1' ),
							array( 'calendar-show-advanced', 'equals', '1' ),
							array( 'calendar-date-range-type', 'equals', 'relative' ),
						),
						'default' => '1',
						'min' => 0,
						'max' => 26,
						'step' => 1
					),
					array(
						'id' => 'calendar-relative-weeks-future',
						'type' => 'slider',
						'title' => __( 'How Far Into The Future?', $this->plugin_name ),
						'subtitle' => __( 'Every time we synchronize, how many <strong>weeks</strong> in the future should we look?', $this->plugin_name ),
						'required' => array(
							array( 'calendar-enabled', 'equals', '1' ),
							array( 'calendar-show-advanced', 'equals', '1' ),
							array( 'calendar-date-range-type', 'equals', 'relative' ),
						),
						'default' => '16',
						'min' => 1,
						'max' => 52,
						'step' => 1
					),
					array(
						'id' => 'calendar-specific-start',
						'type' => 'date',
						'title' => __( 'Specific Start Date', $this->plugin_name ),
						'subtitle' => __( 'When synchronizing, we should get events that start <strong>after</strong> this date.<br><em>(Leave empty to always start "today")</em>', $this->plugin_name ),
						'required' => array(
							array( 'calendar-enabled', 'equals', '1' ),
							array( 'calendar-show-advanced', 'equals', '1' ),
							array( 'calendar-date-range-type', 'equals', 'specific' ),
						),
					),
					array(
						'id' => 'calendar-specific-end',
						'type' => 'date',
						'title' => __( 'Specific End Date', $this->plugin_name ),
						'subtitle' => __( "When synchronizing, we should get events that start <strong>before</strong> this date.<br><em>(If you leave this empty, we'll still limit the maximum end date to a year from now. Be careful that you don't set it too far into the future or the API may timeout.)</em>", $this->plugin_name ),
						'required' => array(
							array( 'calendar-enabled', 'equals', '1' ),
							array( 'calendar-show-advanced', 'equals', '1' ),
							array( 'calendar-date-range-type', 'equals', 'specific' ),
						),
					),
					array(
						'id' => 'calendar-exclude-from-search',
						'type' => 'switch',
						'title' => __( 'Exclude From Search?', $this->plugin_name ),
						'subtitle' => __( 'Should these events be hidden from the search results on the front end of your site?', $this->plugin_name ),
						'required' => array(
							array( 'calendar-enabled', 'equals', '1' ),
							array( 'calendar-show-advanced', 'equals', '1' ),
						),
						'default' => false,
						'on' => __( 'Yes', $this->plugin_name ),
						'off' => __( 'No', $this->plugin_name ),
					),
					array(
						'id' => 'calendar-publicly-queryable',
						'type' => 'switch',
						'title' => __( 'Publicly Queryable?', $this->plugin_name ),
						'subtitle' => __( 'Should these events be available on the front end of your site?<br><em>(Most widgets and themes require this to be Yes)<em>', $this->plugin_name ),
						'required' => array(
							array( 'calendar-enabled', 'equals', '1' ),
							array( 'calendar-show-advanced', 'equals', '1' ),
						),
						'default' => true,
						'on' => __( 'Yes', $this->plugin_name ),
						'off' => __( 'No', $this->plugin_name ),
					),
					array(
						'id' => 'calendar-show-ui',
						'type' => 'switch',
						'title' => __( 'Show In Admin UI?', $this->plugin_name ),
						'subtitle' => __( 'Should we be able to see these events in the WordPress Admin sidebar?', $this->plugin_name ),
						'required' => array(
							array( 'calendar-enabled', 'equals', '1' ),
							array( 'calendar-show-advanced', 'equals', '1' ),
						),
						'default' => true,
						'on' => __( 'Yes', $this->plugin_name ),
						'off' => __( 'No', $this->plugin_name ),
					),
					array(
						'id' => 'calendar-show-in-nav-menus',
						'type' => 'switch',
						'title' => __( 'Show In Navigation Menus?', $this->plugin_name ),
						'subtitle' => __( 'Should individual events be available to navigation menus?', $this->plugin_name ),
						'required' => array(
							array( 'calendar-enabled', 'equals', '1' ),
							array( 'calendar-show-advanced', 'equals', '1' ),
						),
						'default' => false,
						'on' => __( 'Yes', $this->plugin_name ),
						'off' => __( 'No', $this->plugin_name ),
					),
					array(
						'id' => 'calendar-advanced-end',
						'type' => 'section',
						'required' => array(
							array( 'calendar-enabled', 'equals', '1' ),
							array( 'calendar-show-advanced', 'equals', '1' ),
						),
						'indent' => false,
					),
				)
			)
		);

		Redux::setSection( $option_name,
			array(
				'title' => __( 'Synchronize', $this->plugin_name ),
				'id' => 'sync',
				'desc' => __( '', $this->plugin_name ),
				'icon' => 'el el-cogs',
				'fields' => array(
					array(
						'id' => 'auto-sync',
						'type' => 'switch',
						'title' => __( 'Auto Sync', $this->plugin_name ),
						'subtitle' => __( 'Automatically synchronize with your Church Community Builder software.', $this->plugin_name ),
						'default' => false,
					),
					array(
						'id' => 'auto-sync-timeout',
						'type' => 'slider',
						'title' => __( 'Cache Expiration', $this->plugin_name ),
						'subtitle' => __( 'We keep a local copy (cache) of your Church Community Builder data for the best performance.<br><br>How often (in minutes) should we check for new data?<br><em>90 minutes is recommended.</em>', $this->plugin_name ),
						'class' => 'ccb-core-auto-sync-timeout',
						'required' => array( 'auto-sync', 'equals', 1 ),
						'min' => 10,
						'max' => 180,
						'step' => 10,
						'default' => 90
					),
					array(
						'id' => 'manual-sync',
						'type' => 'raw',
						'title' => __( 'Manual Sync', $this->plugin_name ),
						'subtitle' => __( 'You can manually synchronize your Church Community Builder data right now.', $this->plugin_name ),
						'class' => 'ccb-core-manual-sync',
						'content' => $this->get_sync_content(),
					),
					array(
						'id' => 'latest-results',
						'type' => 'info',
						'title' => __( 'Latest Sync Results', $this->plugin_name ),
						'subtitle' => __( '', $this->plugin_name ),
						'class' => 'ccb-core-latest-results',
						'style' => $this->latest_sync_message['style'],
						'desc' => $this->latest_sync_message['description'],

					),
				)
			)
		);

	}

	/**
	 * Callback method to render the manual sync button and status
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    string
	 */
	protected function get_sync_content() {

		$sync_in_progress = get_transient( $this->plugin_name . '-sync-in-progress' );
		$sync_message = '';
		$button_disabled = '';
		$spinner_active = '';

		if ( $sync_in_progress ) {
			$sync_message = '<div class="in-progress-message redux-notice-field redux-field-info redux-info">Syncronization in progress... You can safely navigate away from this page while we work hard in the background. (It should be just a moment).</div>';
			$button_disabled = 'disabled';
			$spinner_active = 'is-active';
		}
		return <<<HTML
			<div class="sync-wrapper">
				<input type="button" name="manual_sync" id="manual-sync" class="button button-primary {$button_disabled}" value="Synchronize" />
				<div class="spinner {$spinner_active}"></div>
				{$sync_message}
			</div>
HTML;
	}

	/**
	 * Callback method to get the test credentials button and spinner
	 *
	 * @access    protected
	 * @since     0.9.0
	 * @return    void
	 */
	protected function get_test_credentials_content() {

		return <<<HTML
			<div class="test-login-wrapper">
				<input type="button" name="test_login" id="test-login" class="button button-primary" value="Test Credentials" />
				<div class="spinner"></div>
			</div>
HTML;
	}

	/**
	 * Callback method to get the about page
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function get_about_content() {
		// this unfortunately includes a dirty hack to prevent chrome from autopopulating username/password
		// so this will inject a fake login panel because chrome ignores autocomplete="off"
		return <<<HTML

			<span style="display:none;visibility:hidden"><input name="username" readonly /><input type="password" name="password" readonly /></span>

			<p>
				Church Community Builder Core API <em>synchronizes</em> your church data to WordPress custom post types.
				This plugin is geared toward developers (or advanced WordPress users who aren't afraid to get into a little bit of code).
			</p>

			<h4>Why Use This Plugin?</h4>

			<p>
				One of the biggest challenges with getting your Church Community Builder data onto your site is the actual API integration.
				This plugin does all of the heavy lifting for you. Once your church data is securely synchronized you can use it freely in
				your theme, widgets, or even your own plugins!
			</p>

			<h4>Features</h4>

			<ul>
				<li>
					Get your Public Groups
				</li>
				<li>
					Get your Public Events
				</li>
				<li>
					Auto-synchronize (set it and forget it)
				</li>
				<li>
					Manually synchronize anytime
				</li>
				<li>
					Cached data (extremely fast)
				</li>
				<li>
					Works in the background (never interrupts you or your visitors)
				</li>
				<li>
					Secure (API communication is encypted, and so are your credentials)
				</li>
			</ul>

			<h4>Frequently Asked Questions</h4>

			<p>
				<strong>I installed this plugin and my site doesn't look any different</strong>
			</p>

			<blockquote>
				This plugin has a very specific task: It gets some of your Church Community Builder data and imports it into your
				WordPress database (as custom post types). A developer (or advanced WordPress administrator) will need to
				alter your theme to <em>take advantage</em> of this data.
			</blockquote>

			<p>
				<strong>
				Some of my groups in Church Community Builder aren't being synchronized
				</strong>
			</p>

			<blockquote>
				You'll need to ensure your <a href="https://support.churchcommunitybuilder.com/customer/portal/articles/361764-editing-groups" target="_blank">group settings</a>
				allow the group to be publicly listed. A great way to cross reference if your group is publicly visible is to visit
				<em style="white-space:nowrap;">http://[your-site].ccbchurch.com/w_group_list.php</em> and see if the missing group shows up there.
			</blockquote>
HTML;
	}

	/**
	 * Callback method to encrypt the password before saving to database
	 *
	 * @param     array    $field
	 * @param     array    $value
	 * @param     array    $existing_value
	 * @access    public
	 * @since     0.9.0
	 * @return    array
	 */
	public function encrypt_credentials( $field, $value, $existing_value ) {

		$response = array();
		$value['password'] = $this->encrypt( $value['password'] );

		if ( $value['password'] == false ) {
			$response['error'] = $field;
		}

		$response['value'] = $value;
		return $response;

	}

	/**
	 * Callback method to validate a custom post type name
	 *
	 * @param     array    $field
	 * @param     array    $value
	 * @param     array    $existing_value
	 * @access    public
	 * @since     0.9.0
	 * @return    array
	 */
	public function validate_name( $field, $value, $existing_value ) {

		$response = array();

		$value = preg_replace( "/[^0-9a-zA-Z _-]/", "", $value );

		if ( empty( $value ) ) {
			$response['error'] = $field;
			$response['value'] = $existing_value;
		}
		else {
			$response['value'] = $value;
		}

		return $response;

	}

	/**
	 * Callback method to validate a custom post type slug
	 *
	 * @param     array    $field
	 * @param     array    $value
	 * @param     array    $existing_value
	 * @access    public
	 * @since     0.9.0
	 * @return    array
	 */
	public function validate_slug( $field, $value, $existing_value ) {

		$response = array();

		$value = strtolower( preg_replace( "/[^0-9a-zA-Z_-]/", "", $value ) );

		if ( empty( $value ) ) {
			$response['error'] = $field;
			$response['value'] = $existing_value;
		}
		else {
			$response['value'] = $value;
		}

		return $response;

	}

}
