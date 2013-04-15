<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WooThemes Updater Class
 *
 * Base class for the WooThemes Updater.
 *
 * @package WordPress
 * @subpackage WooThemes Updater
 * @category Core
 * @author WooThemes
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * public $updater
 * public $admin
 * private $token
 * public $plugin_url
 * public $plugin_path
 * public $version
 * private $file
 *
 * - __construct()
 * - load_localisation()
 * - load_plugin_textdomain()
 * - activation()
 * - register_plugin_version()
 * - add_product()
 * - remove_product()
 * - get_product()
 */
class WooThemes_Updater {
	public $updater;
	public $admin;
	private $token = 'woothemes-updater';
	private $plugin_url;
	private $plugin_path;
	public $version;
	private $file;
	private $products;

	/**
	 * Constructor.
	 * @param string $file The base file of the plugin.
	 * @since  1.0.0
	 * @return  void
	 */
	public function __construct ( $file ) {
		$this->file = $file;
		$this->plugin_url = trailingslashit( plugins_url( '', $plugin = $file ) );
		$this->plugin_path = trailingslashit( dirname( $file ) );

		$this->products = array();

		$this->load_plugin_textdomain();
		add_action( 'init', array( &$this, 'load_localisation' ), 0 );

		// Run this on activation.
		register_activation_hook( $this->file, array( &$this, 'activation' ) );

		if ( is_admin() ) {
			// Load the self-updater.
			require_once( 'class-woothemes-updater-self-updater.php' );
			$this->updater = new WooThemes_Updater_Self_Updater( $file );

			// Load the admin.
			require_once( 'class-woothemes-updater-admin.php' );
			$this->admin = new WooThemes_Updater_Admin( $file );

			// Get queued plugin updates
			add_action( 'plugins_loaded', array( &$this, 'load_queued_updates' ) );
		}
	} // End __construct()

	/**
	 * Load the plugin's localisation file.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'woothemes-updater', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation()

	/**
	 * Load the plugin textdomain from the main WordPress "languages" folder.
	 * @since  1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'woothemes-updater';
	    // The "plugin_locale" filter is also used in load_plugin_textdomain()
	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain()

	/**
	 * Run on activation.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function activation () {
		$this->register_plugin_version();
	} // End activation()

	/**
	 * Register the plugin's version.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	private function register_plugin_version () {
		if ( $this->version != '' ) {
			update_option( 'woothemes-updater' . '-version', $this->version );
		}
	} // End register_plugin_version()

	/**
	 * load_queued_updates function.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function load_queued_updates() {
		global $woothemes_queued_updates;

		if ( ! empty( $woothemes_queued_updates ) && is_array( $woothemes_queued_updates ) )
			foreach ( $woothemes_queued_updates as $plugin )
				if ( is_object( $plugin ) && ! empty( $plugin->file ) && ! empty( $plugin->file_id ) && ! empty( $plugin->product_id ) )
					$this->add_product( $plugin->file, $plugin->file_id, $plugin->product_id );

	} // END load_queued_updates()

	/**
	 * Add a product to await a license key for activation.
	 *
	 * Add an product into the array, to be processed with the other products.
	 *
	 * @since  1.0.0
	 * @param string $file The base file of the product to be activated.
	 * @param string $file_id The unique file ID of the product to be activated.
	 * @return  void
	 */
	public function add_product ( $file, $file_id, $product_id ) {
		if ( $file != '' && ! isset( $this->products[$file] ) ) { $this->products[$file] = array( 'file_id' => $file_id, 'product_id' => $product_id ); }
	} // End add_product()

	/**
	 * Remove a product from the available array of products.
	 *
	 * @since     1.0.0
	 * @param     string $key The key to be removed.
	 * @return    boolean
	 */
	public function remove_product ( $file ) {
		$response = false;
		if ( $file != '' && in_array( $file, array_keys( $this->products ) ) ) { unset( $this->products[$file] ); $response = true; }
		return $response;
	} // End remove_product()

	/**
	 * Return an array of the available product keys.
	 * @since  1.0.0
	 * @return array Product keys.
	 */
	public function get_products () {
		return (array) $this->products;
	} // End get_products()
} // End Class
?>