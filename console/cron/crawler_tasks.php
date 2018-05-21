<?php
chdir(__DIR__);
set_time_limit(0);
ini_set('display_errors', true);
error_reporting(E_ALL);
ob_implicit_flush(true);
mb_internal_encoding('UTF-8');

require_once('../../includes/config.php');
require_once('../../includes/prepend.php');
require_once '../../includes/common.php';
require_once '../../includes/class/simple_html_dom.php';
require_once '../../includes/class/phpwebdriver/WebDriver.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';

require_once('./crawler_functions/yahoo_manager.php');
require_once('./crawler_functions/bing_manager.php');
require_once('./crawler_functions/google_manager.php');


require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$dbo        = get_dbo();
$html       = new simple_html_dom();
$_mail_auto = false;


// write_log_to_file("Closing all Chrome Drivers if working backround...\n");
// `taskkill /im chromedriver.exe /f`;

// write_log_to_file("Closing all Chrome if working backround...\n");
// `taskkill /im chrome.exe /f`;

sleep(1);

$browser ='firefox';


//$engines =[""]
while (true) {
    write_log_to_file('Before Executing query .\n');
    
    $Query         = 'SELECT * FROM `crawler_task` ORDER BY rand() LIMIT 1';
    $Query_Results = $dbo->execute($Query);
    if (!sizeof($Query_Results))
        break;
    
    $task = $Query_Results[0];
    echo 'task id ' . $task['id'];
    
    $engine_array = array(
        "google",
        "yahoo",
        "bing"
    );
    
    $array_index = array_rand($engine_array);
    $engine      = $engine_array[$array_index];

    $engine == 'bing';
    
    if ($task['type'] == 'search') {
        
        if ($engine == 'google') {
            $webdriver = new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
            $webdriver->connect($browser, '', array(
                'proxy' => array(
                    'proxyType' => 'manual',
                    'httpProxy' => $bot['domain'] . ':' . $bot['http_port'],
                    'sslProxy' => $bot['domain'] . ':' . $bot['http_port']
                ),
                'chromeOptions' => array(
                    'args' => array(
                        'user-data-dir=' . $GLOBALS['config']['bots_user_data_dir'] . $bot['id'] . '/',
                        'disable-infobars',
                        'ignore-certificate-errors',
                        'disable-notifications',
                        'load-extension=' . $GLOBALS['config']['chrome_multipass_extension_dir']
                    )
                )
            ));
            
            $webdriver->windowMaximize();
            $webdriver->setImplicitWaitTimeout(10000);
            $webdriver->setSpeed('SLOW');
            
            $query = "https://www.google.com/search?q=" . $task['query'];
            
            $webdriver->get($query);
            
            sleep(15);
            
            $site_domain = $task['target_domain'];
            
            $link_clicked = clickLinkGoogle($site_domain, $webdriver);
            
            if ($link_clicked == false) {
                openNextPageGoogle(2, $webdriver);
                $link_clicked = clickLinkGoogle($site_domain, $webdriver);
            }
            
            if ($link_clicked == false) {
                openNextPageGoogle(3, $webdriver);
                $link_clicked = clickLinkGoogle($site_domain, $webdriver);
                
            }
        } elseif ($engine == 'yahoo') {
            
            $webdriver = new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
            $webdriver->connect($browser, '', array(
                'proxy' => array(
                    'proxyType' => 'manual',
                    'httpProxy' => $bot['domain'] . ':' . $bot['http_port'],
                    'sslProxy' => $bot['domain'] . ':' . $bot['http_port']
                ),
                'chromeOptions' => array(
                    'args' => array(
                        'user-data-dir=' . $GLOBALS['config']['bots_user_data_dir'] . $bot['id'] . '/',
                        'disable-infobars',
                        'ignore-certificate-errors',
                        'disable-notifications',
                        'load-extension=' . $GLOBALS['config']['chrome_multipass_extension_dir']
                    )
                )
            ));
            
            $webdriver->windowMaximize();
            $webdriver->setImplicitWaitTimeout(10000);
            $webdriver->setSpeed('SLOW');
            
            $query = "https://in.search.yahoo.com/search?p=" . $task['query'];
            
            $webdriver->get($query);
            
            //sleep(15);
            
            $site_domain   = $task['target_domain'];
            $site_domain   = "php.net";
            $link_clicked  = clickLinkYahoo($site_domain, $webdriver);
            $is_first_page = true;
            
            if ($link_clicked == false) {
                openNextPageYahoo(2, $webdriver, $is_first_page);
                $link_clicked  = clickLinkYahoo($site_domain, $webdriver);
                $is_first_page = false;
                
            }
            
            if ($link_clicked == false) {
                openNextPageYahoo(3, $webdriver, $is_first_page);
                $link_clicked = clickLinkYahoo($site_domain, $webdriver);
                
            }
            
        } elseif ($engine == 'bing') {
            
            $webdriver = new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
            $webdriver->connect($browser, '', array(
                'proxy' => array(
                    'proxyType' => 'manual',
                    'httpProxy' => $bot['domain'] . ':' . $bot['http_port'],
                    'sslProxy' => $bot['domain'] . ':' . $bot['http_port']
                ),
                'chromeOptions' => array(
                    'args' => array(
                        'user-data-dir=' . $GLOBALS['config']['bots_user_data_dir'] . $bot['id'] . '/',
                        'disable-infobars',
                        'ignore-certificate-errors',
                        'disable-notifications',
                        'load-extension=' . $GLOBALS['config']['chrome_multipass_extension_dir']
                    )
                )
            ));
            
            $webdriver->windowMaximize();
            $webdriver->setImplicitWaitTimeout(10000);
            $webdriver->setSpeed('SLOW');
            
            $query = "https://www.bing.com/search?q=" . $task['query'];
            
            $webdriver->get($query);
            sleep(5);
            //sleep(15);
            
            $site_domain = $task['target_domain'];
            $site_domain = "nordvpn.com";
            
            
            $link_clicked  = clickLinkBing($site_domain, $webdriver);
            $is_first_page = true;
            
            if ($link_clicked == false) {
                openNextPageBing(2, $webdriver, $is_first_page);
                $link_clicked  = clickLinkBing($site_domain, $webdriver);
                $is_first_page = false;
                
            }
            
            if ($link_clicked == false) {
                openNextPageBing(3, $webdriver, $is_first_page);
                $link_clicked = clickLinkBing($site_domain, $webdriver);
                sleep(5);
            }
            
            
            
            
            
            
            
            
            
        }
    }
}
?>