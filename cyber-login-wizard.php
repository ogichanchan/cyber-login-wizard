<?php
/**
 * Plugin Name: Cyber Login Wizard
 * Plugin URI: https://github.com/ogichanchan/cyber-login-wizard
 * Description: A unique PHP-only WordPress utility. A cyber style login plugin acting as a wizard. Focused on simplicity and efficiency.
 * Version: 1.0.0
 * Author: ogichanchan
 * Author URI: https://github.com/ogichanchan
 * License: GPLv2 or later
 * Text Domain: cyber-login-wizard
 */

// Disallow direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cyber_Login_Wizard_Plugin Class.
 *
 * Manages the custom cyber-style login page and its settings in a single PHP file.
 * All CSS and JavaScript are embedded inline to adhere to the "no external files" rule.
 */
class Cyber_Login_Wizard_Plugin {

	/**
	 * Option group name for settings.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'clw_settings_group';

	/**
	 * Option name for all plugin settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'clw_options';

	/**
	 * Constructor.
	 *
	 * Initializes the plugin by adding necessary hooks for admin and login page modifications.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Only apply login page modifications if the cyber theme is enabled in settings.
		$options = get_option( self::OPTION_NAME );
		if ( isset( $options['enable_theme'] ) && true === (bool) $options['enable_theme'] ) {
			add_action( 'login_head', array( $this, 'custom_login_head' ) );
			add_filter( 'login_message', array( $this, 'custom_login_message' ) );
			add_filter( 'login_title', array( $this, 'custom_login_title' ) );
		}
	}

	/**
	 * Static method to initialize the plugin.
	 *
	 * This ensures the plugin class is instantiated only once.
	 */
	public static function init() {
		new self();
	}

	/**
	 * Handles plugin activation.
	 *
	 * Sets default options for the plugin if they don't already exist.
	 * This ensures the cyber theme is active immediately upon plugin activation.
	 */
	public static function activate() {
		$default_options = array(
			'enable_theme'    => true, // Enable theme by default.
			'welcome_message' => esc_html__( 'Access Granted. Welcome, Operator.', 'cyber-login-wizard' ),
		);
		// Use add_option to avoid overwriting existing options if they were somehow set.
		add_option( self::OPTION_NAME, $default_options );
	}

	/**
	 * Handles plugin deactivation.
	 *
	 * Cleans up by deleting all plugin options from the database.
	 */
	public static function deactivate() {
		delete_option( self::OPTION_NAME );
	}

	/**
	 * Adds the plugin settings page to the WordPress admin menu under 'Settings'.
	 */
	public function add_admin_menu_page() {
		add_options_page(
			esc_html__( 'Cyber Login Wizard Settings', 'cyber-login-wizard' ),
			esc_html__( 'Cyber Login Wizard', 'cyber-login-wizard' ),
			'manage_options',
			'cyber-login-wizard',
			array( $this, 'settings_page_content' )
		);
	}

	/**
	 * Displays the content of the plugin settings page.
	 *
	 * Renders the form for configuring plugin options.
	 */
	public function settings_page_content() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Cyber Login Wizard Settings', 'cyber-login-wizard' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( 'cyber-login-wizard' );
				submit_button( esc_html__( 'Save Changes', 'cyber-login-wizard' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers plugin settings and fields with WordPress.
	 */
	public function register_settings() {
		register_setting( self::OPTION_GROUP, self::OPTION_NAME, array( $this, 'sanitize_options' ) );

		add_settings_section(
			'clw_main_section', // ID.
			esc_html__( 'Login Page Customization', 'cyber-login-wizard' ), // Title.
			null, // No callback function needed for section description.
			'cyber-login-wizard' // Page slug.
		);

		add_settings_field(
			'clw_enable_theme_field', // ID.
			esc_html__( 'Enable Cyber Theme', 'cyber-login-wizard' ), // Title.
			array( $this, 'enable_theme_callback' ), // Callback to render field.
			'cyber-login-wizard', // Page slug.
			'clw_main_section' // Section ID.
		);

		add_settings_field(
			'clw_welcome_message_field', // ID.
			esc_html__( 'Welcome Message', 'cyber-login-wizard' ), // Title.
			array( $this, 'welcome_message_callback' ), // Callback to render field.
			'cyber-login-wizard', // Page slug.
			'clw_main_section' // Section ID.
		);
	}

	/**
	 * Sanitizes plugin options before saving them to the database.
	 *
	 * @param array $input The raw input array from the form.
	 * @return array The sanitized options array.
	 */
	public function sanitize_options( $input ) {
		$sanitized_input = array();
		$options         = get_option( self::OPTION_NAME ); // Get existing options to merge.

		$sanitized_input['enable_theme']    = isset( $input['enable_theme'] ) ? (bool) $input['enable_theme'] : false;
		$sanitized_input['welcome_message'] = isset( $input['welcome_message'] ) ? sanitize_textarea_field( $input['welcome_message'] ) : '';

		// Merge with existing options to avoid deleting settings that might be added in the future.
		return array_merge( (array) $options, $sanitized_input );
	}

	/**
	 * Renders the 'Enable Cyber Theme' checkbox field on the settings page.
	 */
	public function enable_theme_callback() {
		$options = get_option( self::OPTION_NAME );
		$checked = isset( $options['enable_theme'] ) ? (bool) $options['enable_theme'] : false;
		?>
		<label for="clw_enable_theme">
			<input type="checkbox" id="clw_enable_theme" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enable_theme]" value="1" <?php checked( $checked, true ); ?> />
			<?php esc_html_e( 'Check to enable the custom cyber-style login page theme.', 'cyber-login-wizard' ); ?>
		</label>
		<?php
	}

	/**
	 * Renders the 'Welcome Message' textarea field on the settings page.
	 */
	public function welcome_message_callback() {
		$options = get_option( self::OPTION_NAME );
		$message = isset( $options['welcome_message'] ) ? $options['welcome_message'] : '';
		?>
		<textarea id="clw_welcome_message" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[welcome_message]" rows="4" cols="50" class="large-text"><?php echo esc_textarea( $message ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Enter a custom welcome message to display at the top of the login page.', 'cyber-login-wizard' ); ?>
		</p>
		<?php
	}

	/**
	 * Injects custom inline CSS and JavaScript into the WordPress login page header.
	 *
	 * This method provides the "cyber" look and feel and a simple typing effect.
	 */
	public function custom_login_head() {
		$options = get_option( self::OPTION_NAME );
		// Re-check if theme is enabled for robustness, though checked in constructor.
		if ( ! isset( $options['enable_theme'] ) || true !== (bool) $options['enable_theme'] ) {
			return;
		}

		$current_year = gmdate( 'Y' );
		$blog_name    = get_bloginfo( 'name' );

		// Inline CSS for the cyber theme.
		$custom_css = "
            body.login {
                background: #0d0d0d url('data:image/svg+xml;utf8,<svg width=\"100%\" height=\"100%\" xmlns=\"http://www.w3.org/2000/svg\"><defs><pattern id=\"grid\" width=\"20\" height=\"20\" patternUnits=\"userSpaceOnUse\"><path d=\"M 20 0 L 0 0 0 20\" fill=\"none\" stroke=\"%231a1a1a\" stroke-width=\"1\"></path></pattern></defs><rect width=\"100%\" height=\"100%\" fill=\"url(%23grid)\" /></svg>') repeat;
                font-family: 'Consolas', 'Monaco', 'Lucida Console', monospace;
                color: #00ff00;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
            }
            .login h1 {
                padding: 0;
            }
            .login h1 a {
                background-image: none !important; /* Remove default WP logo */
                width: 100%;
                height: auto;
                font-size: 2em;
                line-height: 1.2;
                color: #00ff00;
                text-align: center;
                text-shadow: 0 0 5px #00ff00;
                padding: 20px 0;
                margin-bottom: 20px;
                display: block;
                box-sizing: border-box;
            }
            .login h1 a:before {
                content: attr(data-title);
            }
            #login {
                background: rgba(0, 0, 0, 0.8);
                border: 1px solid #00ff00;
                box-shadow: 0 0 15px rgba(0, 255, 0, 0.5);
                padding: 30px;
                max-width: 400px;
                width: 90%;
                box-sizing: border-box;
                position: relative;
                z-index: 10;
            }
            #login form {
                background: none;
                border: none;
                box-shadow: none;
                padding: 0;
            }
            #login label {
                color: #00ff00;
                font-weight: normal;
                margin-bottom: 5px;
                display: block;
                text-shadow: 0 0 3px rgba(0, 255, 0, 0.5);
            }
            #login input[type='text'],
            #login input[type='password'],
            #login input[type='email'],
            #login input[type='url'] {
                background: #0d0d0d;
                border: 1px solid #00ff00;
                color: #00ff00;
                padding: 10px;
                width: 100%;
                box-sizing: border-box;
                margin-bottom: 15px;
                font-size: 1em;
                text-shadow: 0 0 3px rgba(0, 255, 0, 0.5);
            }
            #login input[type='text']:focus,
            #login input[type='password']:focus,
            #login input[type='email']:focus,
            #login input[type='url']:focus {
                outline: none;
                box-shadow: 0 0 8px rgba(0, 255, 0, 0.8);
            }
            #login .forgetmenot {
                margin-bottom: 15px;
            }
            #login .forgetmenot label {
                color: #00ff00;
                display: inline-block;
                text-shadow: none;
            }
            #login input[type='checkbox'] {
                accent-color: #00ff00;
                border: 1px solid #00ff00;
            }
            #login .submit input[type='submit'] {
                background: #00ff00;
                border: 1px solid #00ff00;
                color: #0d0d0d;
                padding: 12px 20px;
                text-shadow: none;
                font-weight: bold;
                cursor: pointer;
                transition: background-color 0.3s ease, color 0.3s ease;
                width: 100%;
                box-sizing: border-box;
            }
            #login .submit input[type='submit']:hover {
                background: #00cc00;
                border-color: #00cc00;
                color: #0d0d0d;
            }
            #nav, #backtoblog {
                text-align: center;
                margin-top: 20px;
            }
            #nav a, #backtoblog a, .privacy-policy-page-link {
                color: #00ff00 !important;
                text-shadow: 0 0 5px rgba(0, 255, 0, 0.5);
                text-decoration: none;
                transition: color 0.3s ease;
            }
            #nav a:hover, #backtoblog a:hover, .privacy-policy-page-link:hover {
                color: #00cc00 !important;
            }
            .message {
                border-left: 4px solid #00ff00;
                padding: 12px;
                background: rgba(0, 255, 0, 0.1);
                color: #00ff00;
                margin-bottom: 20px;
                box-shadow: 0 0 8px rgba(0, 255, 0, 0.5);
            }
            .interim-login-wrap {
                background-color: transparent !important; /* For two-factor auth etc. */
            }
            .cyber-footer-message {
                text-align: center;
                margin-top: 20px;
                color: #00cc00;
                font-size: 0.9em;
                text-shadow: 0 0 3px rgba(0, 255, 0, 0.5);
            }
            .clw-welcome-message {
                color: #00ff00;
                text-align: center;
                margin-bottom: 20px;
                font-size: 1.2em;
                text-shadow: 0 0 5px rgba(0, 255, 0, 0.7);
                border: 1px dashed #00ff00;
                padding: 10px;
                background: rgba(0, 0, 0, 0.6);
            }
        ";

		// Inline JavaScript for dynamic effects.
		$custom_js = "
            document.addEventListener('DOMContentLoaded', function() {
                var loginHeader = document.querySelector('.login h1 a');
                if (loginHeader) {
                    // Set data-title attribute to display blog name as login header text.
                    loginHeader.setAttribute('data-title', '" . esc_js( $blog_name ) . "');
                    // Ensure the link leads back to the main site.
                    loginHeader.href = '" . esc_js( get_home_url() ) . "';
                }

                // Simple typing effect for the welcome message.
                var welcomeMessageDiv = document.querySelector('.clw-welcome-message p');
                if (welcomeMessageDiv) {
                    var originalText = welcomeMessageDiv.textContent;
                    welcomeMessageDiv.textContent = ''; // Clear text for typing effect.
                    var i = 0;
                    var speed = 50; // Milliseconds per character.

                    function typeWriter() {
                        if (i < originalText.length) {
                            welcomeMessageDiv.textContent += originalText.charAt(i);
                            i++;
                            setTimeout(typeWriter, speed);
                        }
                    }
                    typeWriter();
                }

                // Add a dynamic footer message with copyright info.
                var loginBox = document.getElementById('login');
                if (loginBox && !document.querySelector('.cyber-footer-message')) {
                    var footerMessage = document.createElement('div');
                    footerMessage.className = 'cyber-footer-message';
                    footerMessage.innerHTML = '<?php echo esc_js( sprintf( '&copy; %s %s. All Rights Reserved.', $current_year, $blog_name ) ); ?>';
                    loginBox.parentNode.insertBefore(footerMessage, loginBox.nextSibling);
                }
            });
        ";

		echo '<style type="text/css">' . $custom_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is generated internally, safe.
		echo '<script type="text/javascript">' . $custom_js . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JS is generated internally, safe.
	}

	/**
	 * Customizes the login page message, adding the configurable welcome message.
	 *
	 * @param string $message The default login message content.
	 * @return string The modified login message, including the custom welcome message.
	 */
	public function custom_login_message( $message ) {
		$options = get_option( self::OPTION_NAME );
		if ( isset( $options['enable_theme'] ) && true === (bool) $options['enable_theme'] ) {
			$welcome_msg = isset( $options['welcome_message'] ) ? $options['welcome_message'] : '';
			if ( ! empty( $welcome_msg ) ) {
				// Wrap the custom message in a div with a class for styling/JS typing effect.
				return '<div class="clw-welcome-message"><p>' . esc_html( $welcome_msg ) . '</p></div>' . $message;
			}
		}
		return $message;
	}

	/**
	 * Customizes the browser tab title for the login page.
	 *
	 * @param string $title The default page title.
	 * @return string The modified page title.
	 */
	public function custom_login_title( $title ) {
		$options = get_option( self::OPTION_NAME );
		if ( isset( $options['enable_theme'] ) && true === (bool) $options['enable_theme'] ) {
			return esc_html__( 'System Login - Cyber Console', 'cyber-login-wizard' );
		}
		return $title;
	}
}

// Initialize the plugin.
Cyber_Login_Wizard_Plugin::init();

// Register activation and deactivation hooks.
register_activation_hook( __FILE__, array( 'Cyber_Login_Wizard_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Cyber_Login_Wizard_Plugin', 'deactivate' ) );