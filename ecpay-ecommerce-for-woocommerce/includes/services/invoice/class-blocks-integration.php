<?php
/**
 * 
 */

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

define('ORDD_BLOCK_VERSION', '1.0.0');

class Blocks_Integration_Invoice_Dev implements IntegrationInterface {

    /**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'woo-ecpay-invoice-dev';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->register_block_frontend_scripts();
		$this->register_block_editor_scripts();
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array('ecpay-checkout-block-frontend');
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array('ecpay-checkout-block-editor');
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return array();
	}

	/**
	 * Register scripts for delivery date block editor.
	 *
	 * @return void
	 */
	public function register_block_editor_scripts() {
		$script_url        = WOOECPAY_PLUGIN_URL . 'build/index.js';
		$script_asset_path = WOOECPAY_PLUGIN_URL . 'build/index.asset.php';

		$script_asset      = file_exists($script_asset_path)
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version($script_asset_path),
			);

		wp_register_script(
			'ecpay-checkout-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	/**
	 * Register scripts for frontend block.
	 *
	 * @return void
	 */
	public function register_block_frontend_scripts() {
		$script_url        = WOOECPAY_PLUGIN_URL . 'build/checkout-block-frontend.js';
		$script_asset_path = WOOECPAY_PLUGIN_DIR . 'build/checkout-block-frontend.asset.php';
		
		$script_asset = file_exists($script_asset_path)
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version($script_asset_path),
			);

		wp_register_script(
			'ecpay-checkout-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version($file) {
		if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && file_exists($file)) {
			return filemtime($file);
		}
		return ORDD_BLOCK_VERSION;
	}

}