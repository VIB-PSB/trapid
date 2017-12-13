<?php
/**
 * Index
 *
 * The Front Controller for handling every request
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.webroot
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * Use the DS to separate the directories in other defines
 */
	if (!defined('DS')) {
		define('DS', DIRECTORY_SEPARATOR);
	}
/**
 * These defines should only be edited if you have cake installed in
 * a directory layout other than the way it is distributed.
 * When using custom settings be sure to use the DS and do not add a trailing DS.
 */

/**
 * The full path to the directory which holds "app", WITHOUT a trailing DS.
 *
 */
	if (!defined('ROOT')) {
		define('ROOT', dirname(dirname(dirname(__FILE__))));
	}
/**
 * The actual directory name for the "app".
 *
 */
	if (!defined('APP_DIR')) {
		define('APP_DIR', basename(dirname(dirname(__FILE__))));
	}


/**
 * Manually added configuration added here
 * We can set TMP (and many other configuration variables) here before the bootstrapping starts.
 * These variables should be modified per TRAPID instance.
 *
 * TODO: switch to configuration files if there are too many variables to deal with.
 *
 * */


/**
 * Default title on pages
 */
if(!defined('WEBSITE_TITLE')){
    define('WEBSITE_TITLE','TRAPID: Rapid Analysis of Transcriptome Data');
}

/**
 * Path/URL to the temporary files directory. Prefix needs to be the same for `TMP` and `TMP_WEB`
 */
// if (!defined('TMP')) {define('TMP', '/www/bioapp/trapid2/');}
if (!defined('TMP')) {define('TMP', '/www/bioapp/trapid_frbuc/');}
if(!defined('TMP_WEB')){define('TMP_WEB', 'http://bioinformatics.psb.ugent.be/readwrite/trapid_frbuc/');}

/**
 * TRAPID database information
 */
if(!defined('TRAPID_DB_SERVER')) {define('TRAPID_DB_SERVER', "psbsql01");}
if(!defined('TRAPID_DB_NAME')) {define('TRAPID_DB_NAME', "db_trapid_02");}
if(!defined('TRAPID_DB_PORT')) {define('TRAPID_DB_PORT', "3306");}
if(!defined('TRAPID_DB_USER')) {define('TRAPID_DB_USER', "trapid_website");}
if(!defined('TRAPID_DB_PASSWORD')) {define('TRAPID_DB_PASSWORD', "@Z%28ZwABf5pZ3jMUz");}

/**
 * Reference databases information
 * TODO: update these variables (so far I just made things work by using the TRAPID_DB_* variables everywhere).
 */
if(!defined('PLAZA_DB_SERVER')) {define('PLAZA_DB_SERVER', "psbsql03");}
if(!defined('PLAZA_DB_PORT')) {define('PLAZA_DB_PORT', "3306");}
if(!defined('PLAZA_DB_USER')) {define('PLAZA_DB_USER', "plaza_web");}
if(!defined('PLAZA_DB_PASSWORD')) {define('PLAZA_DB_PASSWORD', "plaza_web_roxor");}

/*
 * Location of BLAST databases on webserver & midas
 * Subdirectories are named following reference database names available within this TRAPID instance
 */
if(!defined('BLAST_DB_DIR')){
    define('BLAST_DB_DIR','/www/blastdb/biocomp/moderated/trapid_02/');
    // define('BLAST_DB_DIR','/www/blastdb/biocomp/moderated/trapid/');
}
if(!defined('BLAST_DB_DIR_MIDAS')){
    define('BLAST_DB_DIR_MIDAS','/blastdb/webdb/moderated/trapid_02/');
    // define('BLAST_DB_DIR_MIDAS','/blastdb/webdb/moderated/trapid/');
}

/*
 * Maximum number of allowed jobs per experiment on the cluster system.
 * Defined to prevent overloading and abuse
 */
if(!defined('MAX_CLUSTER_JOBS')){
    define('MAX_CLUSTER_JOBS', 2);
}


/**
 * The absolute path to the "cake" directory, WITHOUT a trailing DS.
 *
 * Un-comment this line to specify a fixed path to CakePHP.
 * This should point at the directory containg `Cake`.
 *
 * For ease of development CakePHP uses PHP's include_path.  If you
 * cannot modify your include_path set this value.
 *
 * Leaving this constant undefined will result in it being defined in Cake/bootstrap.php
 */
	//define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'lib');

/**
 * Editing below this line should NOT be necessary.
 * Change at your own risk.
 *
 */
	if (!defined('WEBROOT_DIR')) {
		define('WEBROOT_DIR', basename(dirname(__FILE__)));
	}
	if (!defined('WWW_ROOT')) {
		define('WWW_ROOT', dirname(__FILE__) . DS);
	}

	if (!defined('CAKE_CORE_INCLUDE_PATH')) {
		if (function_exists('ini_set')) {
			ini_set('include_path', ROOT . DS . 'lib' . PATH_SEPARATOR . ini_get('include_path'));
		}
		if (!include('Cake' . DS . 'bootstrap.php')) {
			$failed = true;
		}
	} else {
		if (!include(CAKE_CORE_INCLUDE_PATH . DS . 'Cake' . DS . 'bootstrap.php')) {
			$failed = true;
		}
	}
	if (!empty($failed)) {
		trigger_error("CakePHP core could not be found.  Check the value of CAKE_CORE_INCLUDE_PATH in APP/webroot/index.php.  It should point to the directory containing your " . DS . "cake core directory and your " . DS . "vendors root directory.", E_USER_ERROR);
	}

	if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/favicon.ico') {
		return;
	}

	App::uses('Dispatcher', 'Routing');

	$Dispatcher = new Dispatcher();
	$Dispatcher->dispatch(new CakeRequest(), new CakeResponse(array('charset' => Configure::read('App.encoding'))));
