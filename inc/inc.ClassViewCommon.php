<?php
/**
 * Implementation of view class
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Parent class for all view classes
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Common {
	protected $theme;

	protected $params;

	function __construct($params, $theme='blue') {
		$this->theme = $theme;
		$this->params = $params;
	}

	function __invoke($get=array()) {
		if(isset($get['action']) && $get['action']) {
			if(method_exists($this, $get['action'])) {
				$this->{$get['action']}();
			} else {
				echo "Missing action '".$get['action']."'";
			}
		} else
			$this->show();
	}

	function setParams($params) {
		$this->params = $params;
	}

	function setParam($name, $value) {
		$this->params[$name] = $value;
	}

	function unsetParam($name) {
		if(isset($this->params[$name]))
			unset($this->params[$name]);
	}

	function show() {
	}
}
?>
