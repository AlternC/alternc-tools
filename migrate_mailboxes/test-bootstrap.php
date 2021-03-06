<?php
// *****************************************************************************
// 
// Alternc bootstrapping                  
// bureau/class/config.php file is -not- test friendly
// 
// *****************************************************************************

// Autoloading 
// ***********
//
//AutoLoader::registerDirectory('lib');
//AutoLoader::registerDirectory('../bureau/class');
//AutoLoader::registerDirectory('.');
chdir(__DIR__);
if( ! is_file("tests/config.php")){
    die("For testing, please copy config.php.dist as config.php and edit");
}
require_once 'tests/config.php';
require_once ALTERNC_PANEL."/class/db_mysql.php";
require_once ALTERNC_PANEL."/class/functions.php";


// General variables setup
// *********************
if( ! is_file("tests/local.sh")){
    die("For testing, please copy local.sh.dist as local.sh and edit");
}
if(is_readable('tests/local.sh')){
    $configFile                         = file_get_contents('tests/local.sh');
} else {
    throw new Exception("You must provide a local.sh file", 1 );
}
$configFile                             = explode("\n",$configFile);
$compat                                 = array('DEFAULT_MX'   => 'MX',
    'MYSQL_USER'   => 'MYSQL_LOGIN',
    'MYSQL_PASS'   => 'MYSQL_PWD',
    'NS1_HOSTNAME' => 'NS1',
    'NS2_HOSTNAME' => 'NS2'
);
foreach ($configFile as $line) {
    if (preg_match('/^([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $matches)) {
        //$GLOBALS['L_'.$matches[1]]      = $matches[2];
        eval('$L_'.$matches[1].' = $matches[2];'); # Ugly, but work with phpunit...
        if (isset($compat[$matches[1]])) {
            $GLOBALS['L_'.$compat[$matches[1]]]  =      $matches[2];
        }
    }
}


// Class list global array
//***********************
$dirroot= ALTERNC_PANEL;
$classes=array();
global $classes;
/* CLASSES PHP : automatic include : */
foreach ( glob( $dirroot."/class/m_*.php") as $di ) {
  if (preg_match("#${dirroot}/class/m_(.*)\\.php$#",$di,$match)) { // $
    $classes[]=$match[1];
  }
}



// Constants and globals
// ********************

// Define constants from vars of local.sh
if( !defined("ALTERNC_MAIL") ) { define('ALTERNC_MAIL', "$L_ALTERNC_MAIL"); };
if( !defined("ALTERNC_HTML") ) { define('ALTERNC_HTML', "$L_ALTERNC_HTML"); };
if( !defined("ALTERNC_LOGS") ) { define('ALTERNC_LOGS', "$L_ALTERNC_LOGS"); };
if(isset($L_ALTERNC_LOGS_ARCHIVE)){
 define('ALTERNC_LOGS_ARCHIVE', "$L_ALTERNC_LOGS_ARCHIVE");
}
if( !defined("ALTERNC_LOCALES") ) { define('ALTERNC_LOCALES', ALTERNC_PANEL."/locales"); };
if( !defined("ALTERNC_LOCK_JOBS") ) { define('ALTERNC_LOCK_JOBS', '/var/run/alternc/jobs-lock'); };
if( !defined("ALTERNC_LOCK_PANEL") ) { define('ALTERNC_LOCK_PANEL', '/var/lib/alternc/panel/nologin.lock'); };
if( !defined("ALTERNC_APACHE2_GEN_TMPL_DIR") ) { define('ALTERNC_APACHE2_GEN_TMPL_DIR', '/etc/alternc/templates/apache2/'); };
if( !defined("ALTERNC_VHOST_DIR") ) { define('ALTERNC_VHOST_DIR', "/var/lib/alternc/apache-vhost/"); };
if( !defined("ALTERNC_VHOST_FILE") ) { define('ALTERNC_VHOST_FILE', ALTERNC_VHOST_DIR."vhosts_all.conf"); };
if( !defined("ALTERNC_VHOST_MANUALCONF") ) { define('ALTERNC_VHOST_MANUALCONF', ALTERNC_VHOST_DIR."manual/"); };
define("THROW_EXCEPTIONS", TRUE);

$root = ALTERNC_PANEL."/";

// Create test directory in /tmp
foreach (array(ALTERNC_MAIL, ALTERNC_HTML, ALTERNC_LOGS) as $crdir ) {
  if (! is_dir($crdir)) {
    mkdir($crdir, 0777, true);
  }
}

// Database variables setup
// ***********************
// Default values
$database                               = "alternc_test";
$user                                   = "root";
$password                               = "";

// Local override

if( ! is_file("tests/my.cnf")){
    die("For testing, please copy my.cnf.dist as my.cnf and edit");
}
if(is_readable('tests/my.cnf')){
    $mysqlConfigFile                         = file_get_contents('tests/my.cnf');
} else {
    throw new Exception("You must provide a my.cnf file", 1 );
}
$mysqlConfigFile                             = explode("\n",$mysqlConfigFile);

foreach ($mysqlConfigFile as $line) {
  if (preg_match('/^([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $matches)) {
      switch ($matches[1]) {
      case "user":
        $user                           = $matches[2];
      break;
      case "password":
        $password                       = $matches[2];
      break;
      case "database":
        $database                       = $matches[2];
      break;
    }
  }
  if (preg_match('/^#alternc_var ([A-Za-z0-9_]*) *= *"?(.*?)"?$/', trim($line), $matches)) {
    $$matches[1]                        = $matches[2];
  }
}


/**
* Class for MySQL management in the bureau 
*
* This class heriting from the db class of the phplib manages
* the connection to the MySQL database.
*/
class DB_system extends DB_Sql { 
  function __construct($database, $user, $password) { 
    parent::connect($database, '127.0.0.1', $user, $password);
  } 
} 

// Creates database from schema 
// *********************************************
//
//echo "*** In progress: importing mysql.sql\n";
//$queryList = array(
//    "mysql -u $user --password='$password' -e 'DROP DATABASE IF EXISTS $database '",
//    "mysql -u $user --password='$password' -e 'CREATE DATABASE $database'",
//    "mysql -u $user --password='$password' $database < ".__DIR__."/../install/mysql.sql"
//);
//foreach ($queryList as $exec_command) {
//    exec($exec_command,$output,$return_var);
//    if(  $return_var){
//        throw new \Exception("[!] Mysql exec error : $exec_command \n Error : \n ".print_r($output,true));
//    }
//}
//echo "*** In progress: mysql.sql imported\n";

global $db;
global $cuid;
global $variables;
global $err;
global $mem;
global $admin;
global $mysql;
global $ftp;
global $quota;
global $db;

// this class is defined above ^^^
$db                                     = new \DB_system($database, $user, $password);

$cuid                                   = 0;
//$variables                              = new \m_variables();
//$mem                                    = new \m_mem();
//$err                                    = new \m_err();
//$authip                                 = new \m_authip();
//$hooks                                  = new \m_hooks();
//$bro                                    = new \m_bro();
//$admin                                    = new \m_admin();
//$mysql                                    = new \m_mysql();
//$ftp                                    = new \m_ftp();
//$quota                                    = new \m_quota();
