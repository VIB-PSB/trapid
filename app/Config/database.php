<?php
/**
 * This is core configuration file.
 *
 * Use it to configure core behaviour of Cake.
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
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
/**
 * In this file you set up your database connection details.
 *
 * @package       cake.config
 */
/**
 * Database configuration class.
 * You can specify multiple configurations for production, development and testing.
 *
 * driver => The name of a supported driver; valid options are as follows:
 *		Database/Mysql 		- MySQL 4 & 5,
 *		Database/Sqlite		- SQLite (PHP5 only),
 *		Database/Postgres	- PostgreSQL 7 and higher,
 *		Database/Sqlserver	- Microsoft SQL Server 2005 and higher
 *
 * You can add custom database drivers (or override existing drivers) by adding the
 * appropriate file to app/Model/Datasource/Database.  Drivers should be named 'MyDriver.php',
 *
 *
 * persistent => true / false
 * Determines whether or not the database should use a persistent connection
 *
 * host =>
 * the host you connect to the database. To add a socket or port number, use 'port' => #
 *
 * prefix =>
 * Uses the given prefix for all the tables in this database.  This setting can be overridden
 * on a per-table basis with the Model::$tablePrefix property.
 *
 * schema =>
 * For Postgres specifies which schema you would like to use the tables in. Postgres defaults to 'public'.
 *
 * encoding =>
 * For MySQL, Postgres specifies the character encoding to use when connecting to the
 * database. Uses database default not specified.
 *
 * unix_socket =>
 * For MySQL to connect via socket specify the `unix_socket` parameter instead of `host` and `port`
 */

 // TODO: Update reference db information
class DATABASE_CONFIG {
	public $default = array(
		'datasource' => 'Database/Mysql',
		'persistent' 	=> false,
		'host' 		=> TRAPID_DB_SERVER,
		'port' 		=> '',
		'login' 	=> TRAPID_DB_USER,
		'password' 	=> TRAPID_DB_PASSWORD,
		'database' 	=> TRAPID_DB_NAME,
		'schema' 	=> '',
		'prefix' 	=> '',
		'encoding' 	=> ''//,
		// Not compatible with cakephp 1.2 ? Check how it is done in v3
      	// Better to modify the queries themselves
		// 'init' => "SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';"
	);

	// Removed as of 2016-01-03 (merged `full_taxonomy` table with main TRAPID db)
	/* var $db_trapid_01_taxonomy = array(
		'datasource' => 'Database/Mysql',
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

	public $db_trapid_ref_plaza_monocots_03_test	= array(
		'datasource' => 'Database/Mysql',
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

    public $db_trapid_ref_plaza_dicots_03_test	= array(
        'datasource' => 'Database/Mysql',
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

    public $db_trapid_ref_plaza_pico_02_test	= array(
        'datasource' => 'Database/Mysql',
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

    public $db_trapid_ref_plaza_dicots_04_test	= array(
        'datasource' => 'Database/Mysql',
        'persistent' 	=> false,
        'host' 		=> TRAPID_DB_SERVER,
        'port' 		=> TRAPID_DB_PORT,
        'login' 	=> TRAPID_DB_USER,
        'password' 	=> TRAPID_DB_PASSWORD,
        'database' 	=> 'db_trapid_ref_plaza_dicots_04_test',
        'schema' 	=> '',
        'prefix' 	=> '',
        'encoding' 	=> ''
    );

    public $db_trapid_ref_plaza_monocots_04_test	= array(
        'datasource' => 'Database/Mysql',
        'persistent' 	=> false,
        'host' 		=> TRAPID_DB_SERVER,
        'port' 		=> TRAPID_DB_PORT,
        'login' 	=> TRAPID_DB_USER,
        'password' 	=> TRAPID_DB_PASSWORD,
        'database' 	=> 'db_trapid_ref_plaza_monocots_04_test',
        'schema' 	=> '',
        'prefix' 	=> '',
        'encoding' 	=> ''
    );

    public $db_plaza_public_02_5	= array(
		'datasource' => 'Database/Mysql',
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


	public $db_plaza_public_03	= array(
		'datasource' => 'Database/Mysql',
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

	public $db_orthomcldb_r5  = array(
		'datasource' => 'Database/Mysql',
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



    public $db_trapid_ref_eggnog_test_02  = array(
        'datasource' => 'Database/Mysql',
        'persistent' 	=> false,
        'host' 		=> TRAPID_DB_SERVER,
        'port' 		=> TRAPID_DB_PORT,
        'login' 	=> TRAPID_DB_USER,
        'password' 	=> TRAPID_DB_PASSWORD,
        'database' 	=> 'db_trapid_ref_eggnog_test_02',
        'schema' 	=> '',
        'prefix' 	=> '',
        'encoding' 	=> ''
    );



    public $db_trapid_ref_plaza_singek_02_test  = array(
        'datasource' => 'Database/Mysql',
        'persistent' 	=> false,
        'host' 		=> TRAPID_DB_SERVER,
        'port' 		=> TRAPID_DB_PORT,
        'login' 	=> TRAPID_DB_USER,
        'password' 	=> TRAPID_DB_PASSWORD,
        'database' 	=> 'db_trapid_ref_plaza_singek_02_test',
        'schema' 	=> '',
        'prefix' 	=> '',
        'encoding' 	=> ''
    );
}

