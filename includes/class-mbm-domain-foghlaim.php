<?php

class Mbm_Domain_Foghlaim {

	/**
	 * Contains the one instance of the domain handler
	 *
	 * @var Mbm_Domain_Foghlaim
	 */
	static private $instance;

	/**
	 * A quiet constructor
	 */
	private function __construct() {}

	/**
	 * Maintain and return the one instance of the domain handler
	 *
	 * @return Mbm_Domain_Foghlaim
	 */
	public static function get_instance() {
		if ( ! self::$instance )
			self::$instance = new Mbm_Domain_Foghlaim();

		return self::$instance;
	}
}
$mbm_domain_foghlaim = Mbm_Domain_Foghlaim::get_instance();