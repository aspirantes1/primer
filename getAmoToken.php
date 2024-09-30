<?

use CoreAmo\CoreAmo;

date_default_timezone_set("Europe/Moscow");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 'on');
ini_set('error_log', __DIR__ . '/php_error.log');

require 'autoload.php';

CoreAmo::token();
die;
