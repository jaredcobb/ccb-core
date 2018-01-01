<?php
/**
 * Everything related to the plugin settings sections
 *
 * @link       https://www.wpccb.com
 * @since      0.9.0
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 */

/**
 * Object to manage the plugin settings sections
 *
 * @package    CCB_Core
 * @subpackage CCB_Core/includes
 * @author     Jared Cobb <wordpress@jaredcobb.com>
 */
class CCB_Core_Settings_Section {

	/**
	 * The key for the section in the settings array
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      string    $section_id
	 */
	protected $section_id;

	/**
	 * An array of field sections and their settings
	 *
	 * @since    0.9.0
	 * @access   protected
	 * @var      array    $section
	 */
	protected $section;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string $section_id The slug of the section.
	 * @param   array  $section An array of section settings.
	 * @return void
	 */
	public function __construct( $section_id, $section ) {
		$this->section_id = $section_id;
		$this->section = $section;
	}

	/**
	 * Render the About page content
	 *
	 * This unfortunately also renders a fake hidden credentials section that can
	 * be removed when Google decides to let Chrome behave.
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function render_section_about() {

		// This unfortunately includes a dirty hack to prevent chrome from autopopulating username/password
		// so this will inject a fake login panel because chrome ignores autocomplete="off".
		echo '

			<span style="display:none;visibility:hidden"><input name="username" readonly /><input type="password" name="password" readonly /></span>

			<p>
				Church Community Builder Core API <em>synchronizes</em> your church data to WordPress custom post types.
				This plugin is geared toward developers (or advanced WordPress users who aren\'t afraid to get into a little bit of code).
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
				<strong>I installed this plugin and my site doesn\'t look any different</strong>
			</p>

			<blockquote>
				This plugin has a very specific task: It gets some of your Church Community Builder data and imports it into your
				WordPress database (as custom post types). A developer (or advanced WordPress administrator) will need to
				alter your theme to <em>take advantage</em> of this data.
			</blockquote>

			<p>
				<strong>
				Some of my groups in Church Community Builder aren\'t being synchronized
				</strong>
			</p>';

		echo '
			<blockquote>
				You\'ll need to ensure your <a href="https://churchcommunitybuilder.force.com/s/article/2102903" target="_blank">group settings</a>
				have <strong>Public Search</strong> enabled (see the Options tab). A great way to cross reference if your group is publicly visible is to visit ';

		// If the user has set their subdomain, use it for the url to w_group_list.php.
		$options = CCB_Core_Helpers::instance()->get_options();
		if ( ! empty( $options['subdomain'] ) ) {
			echo sprintf(
				'<em style="white-space:nowrap;">
					<a href="%s" target="_blank">your public search page</a>
				</em>',
				esc_url( "https://{$options['subdomain']}.ccbchurch.com/w_group_list.php" )
			);
		} else {
			echo '<em style="white-space:nowrap;">https://[yoursite].ccbchurch.com/w_group_list.php</em>';
		}

		echo ' and see if the missing group shows up there.
			</blockquote>

			<h3>Documentation</h3>
			<p>
				The <a href="https://www.wpccb.com/documentation/" target="_blank">official documentation</a> has more information, including code samples, hooks &amp; filters, and links to tutorials.
			</p>

			<h3>Support</h3>
			<p>
				Support is limited, but if you have questions as a <strong>user</strong> of the plugin you can submit them on the official <a href="https://wordpress.org/support/plugin/church-community-builder-core-api" target="_blank">WordPress plugin support forum</a>.
				If you\'re a Developer and would like to submit a bug report or pull request, you can do that on <a href="https://github.com/jaredcobb/ccb-core" target="_blank">GitHub</a>.
			</p>';
	}

	/**
	 * Render the other section titles
	 *
	 * @access    public
	 * @since     0.9.0
	 * @return    void
	 */
	public function render_section() {
		if ( 'about' === $this->section_id ) {
			$this->render_section_about();
		}
	}

}
