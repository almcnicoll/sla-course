<?php
/**
 * Plugin Name:       SLA Course
 * Description:       Useful site tweaks for hosting training course materials
 * Version:           0.0.7
 * Author:            Al McNicoll
 * Author URI:        http://almcnicoll.co.uk
 * Text Domain:       sla-course
 * License:           © 2019 Al McNicoll & Salt & Light Advance
 * License URI:       
 * GitHub Plugin URI: https://github.com/almcnicoll/sla-course
 */


/*
 * Plugin constants
 */
if(!defined('SLACOURSE_PLUGIN_VERSION'))
	define('SLACOURSE_PLUGIN_VERSION', '0.0.7');
if(!defined('SLACOURSE_URL'))
	define('SLACOURSE_URL', plugin_dir_url( __FILE__ ));
if(!defined('SLACOURSE_PATH'))
	define('SLACOURSE_PATH', plugin_dir_path( __FILE__ ));
if(!defined('SLACOURSE_PROTOCOL'))
	define('SLACOURSE_PROTOCOL', 'https');
if(!defined('SLACOURSE_TEXTDOMAIN'))
	define('SLACOURSE_TEXTDOMAIN', 'sla-course');
	
/**
Filters etc.
**/
function menu_function($atts, $content = null) {
	extract(
	  shortcode_atts(
		 array( 'name' => null, ),
		 $atts
	  )
	);
	return wp_nav_menu(
	  array(
		  'menu' => $name,
		  'echo' => false
		  )
	);
}

function custom_styles_and_scripts() {
	
	//wp_register_style( SLACOURSE_TEXTDOMAIN, plugins_url('added.css',__FILE__ ) );
	wp_register_style( SLACOURSE_TEXTDOMAIN, plugins_url('assets/css/added.css',__FILE__ ) );
	//wp_register_style( SLACOURSE_TEXTDOMAIN, '/css/added.css' );
	wp_enqueue_style(SLACOURSE_TEXTDOMAIN);
	
	//wp_register_style( 'namespace', 'http://locationofcss.com/mycss.css' );
	//wp_enqueue_style( 'namespace' );
	//wp_enqueue_script( 'namespaceformyscript', 'http://locationofscript.com/myscript.js', array( 'jquery' ) );
}

/*
 * Main class
 */
/**
 * Class sla_course
 *
 * This class creates the option page and add the web app script
 */
class sla_course
{
	/**
	 * The security nonce
	 *
	 * @var string
	 */
	private $_nonce = 'sla_course_admin';
	
	
	/**
	 * constructor.
     *
     * The main plugin actions registered for WordPress
	 */
	public function __construct()
    {

	    add_action('wp_footer',                 array($this,'addFooterCode'));

		// Admin page calls
		add_action('admin_menu',                array($this,'addAdminMenu'));
		add_action('wp_ajax_store_admin_data',  array($this,'storeAdminData'));
		add_action('admin_enqueue_scripts',     array($this,'addAdminScripts'));

		// Add menu shortcode
		add_shortcode('menu', 'menu_function');
		
		// Add custom CSS
		add_action('wp_enqueue_scripts', 'custom_styles_and_scripts');

	}
	
	/**
	 * Returns the saved options data as an array
     *
     * @return array
	 */
	private function getData()
    {
	    return get_option($this->option_name, array());
    }

	/**
	 * Callback for the Ajax request
	 *
	 * Updates the options data
     *
     * @return void
	 */
	public function storeAdminData()
    {

		if (wp_verify_nonce($_POST['security'], $this->_nonce ) === false)
			die('Invalid Request! Reload your page please.');

		$data = $this->getData();

		foreach ($_POST as $field=>$value) {

		    if (substr($field, 0, 11) !== "sla_course_")
				continue;

		    if (empty($value))
		        unset($data[$field]);

		    // We remove the sla_course_ prefix to clean things up
		    $field = substr($field, 11);

			$data[$field] = esc_attr__($value);

		}

		update_option($this->option_name, $data);

		echo __('Saved!', 'sla_course');
		die();

	}

	/**
	 * Adds Admin Scripts for the Ajax call
	 */
	public function addAdminScripts()
    {

	    wp_enqueue_style('sla-course-admin', SLACOURSE_URL. 'assets/css/admin.css', false, 1.0);

		wp_enqueue_script('sla-course-admin', SLACOURSE_URL. 'assets/js/admin.js', array(), 1.0);

		$admin_options = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'_nonce'   => wp_create_nonce( $this->_nonce ),
		);

		wp_localize_script('sla-course-admin', 'sla_course_exchanger', $admin_options);

	}

	/**
	 * Adds the SLA Course label to the WordPress Admin Sidebar Menu
	 */
	public function addAdminMenu()
    {
		add_menu_page(
			__( 'Course Tweaks', SLACOURSE_TEXTDOMAIN ),
			__( 'Course Tweaks', SLACOURSE_TEXTDOMAIN ),
			'manage_options',
			SLACOURSE_TEXTDOMAIN,
			array($this, 'adminLayout'),
			'dashicons-admin-settings',
			10
		);
	}

	/**
     * Get a Dashicon for a given status
     *
	 * @param $valid boolean
     *
     * @return string
	 */
    private function getStatusIcon($valid)
    {

        return ($valid) ? '<span class="dashicons dashicons-yes success-message"></span>' : '<span class="dashicons dashicons-no-alt error-message"></span>';

    }

	/**
	 * Outputs the Admin Dashboard layout containing the form with all its options
     *
     * @return void
	 */
	public function adminLayout()
    {

		$data = $this->getData();

	    ?>

		<div class="wrap">

            <h1><?php _e('Salt & Light Advance Course Settings', SLACOURSE_TEXTDOMAIN); ?></h1>

			<h2>Version <?php echo SLACOURSE_PLUGIN_VERSION; ?></h2>

            <form id="sla_course-admin-form" class="postbox">
<!--
                <div class="form-group inside">

	                <?php
	                /*
					 * --------------------------
					 * API Settings
					 * --------------------------
					 */
	                ?>

                    <h3>
		                <?php echo $this->getStatusIcon(!$not_ready); ?>
		                <?php _e('Feedier API Settings', SLACOURSE_TEXTDOMAIN); ?>
                    </h3>


                    <table class="form-table">
                        <tbody>
                            <tr>
                                <td scope="row">
                                    <label><?php _e( 'Public key', SLACOURSE_TEXTDOMAIN ); ?></label>
                                </td>
                                <td>
                                    <input name="sla_course_public_key"
                                           id="sla_course_public_key"
                                           class="regular-text"
                                           type="text"
                                           value="<?php echo (isset($data['public_key'])) ? $data['public_key'] : ''; ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td scope="row">
                                    <label><?php _e( 'Private key', SLACOURSE_TEXTDOMAIN ); ?></label>
                                </td>
                                <td>
                                    <input name="sla_course_private_key"
                                           id="sla_course_private_key"
                                           class="regular-text"
                                           type="text"
                                           value="<?php echo (isset($data['private_key'])) ? $data['private_key'] : ''; ?>"/>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>

                <hr>

                <div class="inside">

                    <button class="button button-primary" id="sla_course-admin-save" type="submit">
                        <?php _e( 'Save', SLACOURSE_TEXTDOMAIN ); ?>
                    </button>

                </div>
-->
            </form>

		</div>

		<?php

	}
}

/*
 * Starts our plugin class, easy!
 */
new sla_course();

