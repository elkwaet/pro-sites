<?php

/*
For handling modules and gateways
*/

class ProSites_PluginLoader {

	public static $modules = array(
		'ProSites_Module_Ads' => 'ads',
		'ProSites_Module_ProWidget' => 'badge-widget',
		'ProSites_Module_BP' => 'buddypress',
		'ProSites_Module_BulkUpgrades' => 'bulk-upgrades',
		'ProSites_Module_MarketPress_Global' => 'marketpress-filter',
		'ProSites_Module_PayToBlog' => 'pay-to-blog',
		'ProSites_Module_PostingQuota' => 'posting-quota',
		'ProSites_Module_Plugins' => 'premium-plugins',
		'ProSites_Module_Support' => 'premium-support',
		'ProSites_Module_PremiumThemes' => 'premium-themes',
		'ProSites_Module_Quota' => 'quota',
		'ProSites_Module_UnfilterHtml' => 'unfiltered-html',
		'ProSites_Module_Writing' => 'write',
		'ProSites_Module_XMLRPC' => 'xmlrpc',
	);

	function __construct() {
		//load modules
		add_action( 'plugins_loaded', array( &$this, 'load_modules' ), 11 ); //load after translation

		//load gateways
		add_action( 'plugins_loaded', array( &$this, 'load_gateways' ), 11 ); //load after translation
	}

	public static function require_module( $module ) {
		//get modules dir
		$dir = plugin_dir_path( ProSites::$plugin_file ) . 'pro-sites-files/modules/';

		require_once( $dir . self::$modules[$module] . '.php' );
	}

	function load_modules() {
		global $psts;

		//get modules dir
		$dir = $psts->plugin_dir . 'modules/';

		// Avoiding file scan
		$modules = apply_filters( 'prosites_modules', self::$modules );

		//search the dir for files
//		$modules = array();
//		if ( ! is_dir( $dir ) ) {
//			return;
//		}
//		if ( ! $dh = opendir( $dir ) ) {
//			return;
//		}
//		while ( ( $module = readdir( $dh ) ) !== false ) {
//			if ( substr( $module, - 4 ) == '.php' ) {
//				$modules[] = $dir . $module;
//			}
//		}
//		closedir( $dh );
//		sort( $modules );

		ksort( $modules );

		//include them suppressing errors
		foreach ( $modules as $file ) {
//			include_once( $file );
			require_once( $dir . $file . '.php');
		}

		//allow plugins from an external location to register themselves
		do_action( 'psts_load_modules' );

		//load chosen plugin classes
		foreach ( array_keys( $modules ) as $class ) {
			$name = $class::get_name();
			$description = $class::get_description();
			$restriction = '';

			if( method_exists( $class, 'get_class_restriction' ) ) {
				$restriction = $class::get_class_restriction();
			}

			if ( empty( $restriction ) || ( ! empty( $restriction) && class_exists( $restriction ) ) ) {
				psts_register_module( $class, $name, $description );
			}

			if ( class_exists( $class ) && in_array( $class, (array) $psts->get_setting( 'modules_enabled' ) ) ) {
				global $$class;
				$$class = new $class;
			}

		}
//		foreach ( (array) $psts_modules as $class => $module ) {
//			if ( class_exists( $class ) && in_array( $class, (array) $psts->get_setting( 'modules_enabled' ) ) ) {
//				global $$class;
//				$$class = new $class;
//			}
//		}
	}

	function load_gateways() {
		global $psts;

		//get gateways dir
		$dir = $psts->plugin_dir . 'gateways/';

		//search the dir for files
		$gateways = array();
		if ( ! is_dir( $dir ) ) {
			return;
		}
		if ( ! $dh = opendir( $dir ) ) {
			return;
		}
		while ( ( $gateway = readdir( $dh ) ) !== false ) {
			if ( substr( $gateway, - 4 ) == '.php' ) {
				$gateways[] = $dir . $gateway;
			}
		}
		closedir( $dh );
		sort( $gateways );

		//include them suppressing errors
		foreach ( $gateways as $file ) {
			include_once( $file );
		}

		//allow plugins from an external location to register themselves
		do_action( 'psts_load_gateways' );

		//load chosen plugin class
		global $psts_gateways, $psts_active_gateways;
		foreach ( (array) $psts_gateways as $class => $gateway ) {
			if ( class_exists( $class ) && in_array( $class, (array) $psts->get_setting( 'gateways_enabled' ) ) ) {
				$psts_active_gateways[] = new $class;
			}
		}
	}

}

//load the class
$psts_plugin_loader = new ProSites_PluginLoader();

/**
 * Use this function to register your gateway plugin class
 *
 * @param string $class_name - the case sensitive name of your plugin class
 * @param string $name - the nice name for your plugin
 * @param string $description - Short description of your gateway, for the admin side.
 */
function psts_register_gateway( $class_name, $name, $description, $demo = false ) {
	global $psts_gateways;

	if ( ! is_array( $psts_gateways ) ) {
		$psts_gateways = array();
	}

	if ( class_exists( $class_name ) ) {
		$psts_gateways[ $class_name ] = array( $name, $description, $demo );
	} else {
		return false;
	}
}

/**
 * Use this function to register your module class
 *
 * @param string $class_name - the case sensitive name of your plugin class
 * @param string $name - the nice name for your plugin
 * @param string $description - Short description of the module, for the admin side.
 */
function psts_register_module( $class_name, $name, $description, $demo = false ) {
	global $psts_modules;

	if ( ! is_array( $psts_modules ) ) {
		$psts_modules = array();
	}

	if ( class_exists( $class_name ) ) {
		$psts_modules[ $class_name ] = array( $name, $description, $demo );
	} else {
		return false;
	}
}