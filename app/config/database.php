<?php
/* SVN FILE: $Id: database.php.default 6311 2008-01-02 06:33:52Z phpnut $ */
/**
 * This is core configuration file.
 *
 * Use it to configure core behaviour ofCake.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.app.config
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision: 6311 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2008-01-02 00:33:52 -0600 (Wed, 02 Jan 2008) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * In this file you set up your database connection details.
 *
 * @package		cake
 * @subpackage	cake.config
 */
/**
 * Database configuration class.
 * You can specify multiple configurations for production, development and testing.
 *
 * driver => The name of a supported driver; valid options are as follows:
 *		mysql 		- MySQL 4 & 5,
 *		mysqli 		- MySQL 4 & 5 Improved Interface (PHP5 only),
 *		sqlite		- SQLite (PHP5 only),
 *		postgres	- PostgreSQL 7 and higher,
 *		mssql		- Microsoft SQL Server 2000 and higher,
 *		db2			- IBM DB2, Cloudscape, and Apache Derby (http://php.net/ibm-db2)
 *		oracle		- Oracle 8 and higher
 *		adodb-[drivername]	- ADOdb interface wrapper (see below),
 *		pear-[drivername]	- PEAR::DB wrapper
 *
 * You can add custom database drivers (or override existing drivers) by adding the
 * appropriate file to app/models/datasources/dbo.  Drivers should be named 'dbo_x.php',
 * where 'x' is the name of the database.
 *
 * persistent => true / false
 * Determines whether or not the database should use a persistent connection
 *
 * connect =>
 * ADOdb set the connect to one of these
 *	(http://phplens.com/adodb/supported.databases.html) and
 *	append it '|p' for persistent connection. (mssql|p for example, or just mssql for not persistent)
 * For all other databases, this setting is deprecated.
 *
 * host =>
 * the host you connect to the database
 * To add a port number use 'port' => #
 *
 * prefix =>
 * Uses the given prefix for all the tables in this database.  This setting can be overridden
 * on a per-table basis with the Model::$tablePrefix property.
 *
 */
 // TODO: Update reference db information
class DATABASE_CONFIG {
	var $default = array(
		'driver' 	=> 'mysql',
		'persistent' 	=> false,
		'host' 		=> TRAPID_DB_SERVER,
		'port' 		=> '',
		'login' 	=> TRAPID_DB_USER,
		'password' 	=> TRAPID_DB_PASSWORD,
		'database' 	=> TRAPID_DB_NAME,
		'schema' 	=> '',
		'prefix' 	=> '',
		'encoding' 	=> '' //,
		// Not compatible with cakephp 1.2 ? Check how it is done in v3
  	// Better to modify the queries themselves
		// 'init' => "SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';"
	);

	// Removed as of 2016-01-03 (merged `full_taxonomy` table with main TRAPID db)
	/* var $db_trapid_01_taxonomy = array(
		'driver' 	=> 'mysql',
		'persistent' 	=> false,
		'host' 		=> PLAZA_DB_SERVER,
		'port' 		=> '',
		'login' 	=> PLAZA_DB_USER,
		'password' 	=> PLAZA_DB_PASSWORD,
		'database' 	=> "db_trapid_01_taxonomy",
		'schema' 	=> '',
		'prefix' 	=> '',
		'encoding' 	=> ''
	); */

	var $db_trapid_ref_plaza_monocots_03_test	= array(
		'driver' 	=> 'mysql',
		'persistent' 	=> false,
		'host' 		=> TRAPID_DB_SERVER,
		'port' 		=> TRAPID_DB_PORT,
		'login' 	=> TRAPID_DB_USER,
		'password' 	=> TRAPID_DB_PASSWORD,
		'database' 	=> 'db_trapid_ref_plaza_monocots_03_test',
		'schema' 	=> '',
		'prefix' 	=> '',
		'encoding' 	=> ''
	);

    var $db_trapid_ref_plaza_dicots_03_test	= array(
        'driver' 	=> 'mysql',
        'persistent' 	=> false,
        'host' 		=> TRAPID_DB_SERVER,
        'port' 		=> TRAPID_DB_PORT,
        'login' 	=> TRAPID_DB_USER,
        'password' 	=> TRAPID_DB_PASSWORD,
        'database' 	=> 'db_trapid_ref_plaza_dicots_03_test',
        'schema' 	=> '',
        'prefix' 	=> '',
        'encoding' 	=> ''
    );

    var $db_trapid_ref_plaza_pico_02_test	= array(
        'driver' 	=> 'mysql',
        'persistent' 	=> false,
        'host' 		=> TRAPID_DB_SERVER,
        'port' 		=> TRAPID_DB_PORT,
        'login' 	=> TRAPID_DB_USER,
        'password' 	=> TRAPID_DB_PASSWORD,
        'database' 	=> 'db_trapid_ref_plaza_pico_02_test',
        'schema' 	=> '',
        'prefix' 	=> '',
        'encoding' 	=> ''
    );

	var $db_plaza_public_02_5	= array(
		'driver' 	=> 'mysql',
		'persistent' 	=> false,
		'host' 		=> PLAZA_DB_SERVER,
		'port' 		=> '',
		'login' 	=> PLAZA_DB_USER,
		'password' 	=> PLAZA_DB_PASSWORD,
		'database' 	=> 'db_plaza_public_02_5',
		'schema' 	=> '',
		'prefix' 	=> '',
		'encoding' 	=> ''
	);


	var $db_plaza_public_03	= array(
		'driver' 	=> 'mysql',
		'persistent' 	=> false,
		'host' 		=> PLAZA_DB_SERVER,
		'port' 		=> '',
		'login' 	=> PLAZA_DB_USER,
		'password' 	=> PLAZA_DB_PASSWORD,
		'database' 	=> 'db_plaza_public_03',
		'schema' 	=> '',
		'prefix' 	=> '',
		'encoding' 	=> ''
	);

	var $db_orthomcldb_r5  = array(
		'driver' 	=> 'mysql',
		'persistent' 	=> false,
		'host' 		=> PLAZA_DB_SERVER,
		'port' 		=> '',
		'login' 	=> PLAZA_DB_USER,
		'password' 	=> PLAZA_DB_PASSWORD,
		'database' 	=> 'db_orthomcldb_r5',
		'schema' 	=> '',
		'prefix' 	=> '',
		'encoding' 	=> ''
	);

	/*
	var $workbench = array(
		'driver'	=> 'mysql',
		'persistent'	=> false,
		'host'		=> 'psbsql03',
		'port'		=> '',
		'login'		=> 'plaza_workbench',
		'password'	=> 'wb_plaza_roxor',
		'database'	=> DB_WORKBENCH_NAME,	//defined in /cake/config/paths.php
		'schema'       	=> '',
		'prefix'	=> '',
	     	'encoding'	=> ''
	);
	*/

}
?>
