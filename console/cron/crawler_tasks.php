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

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$dbo        = get_dbo();
$html       = new simple_html_dom();
$_mail_auto = false;


write_log_to_file("Closing all Chrome Drivers if working backround...\n");
`taskkill /im chromedriver.exe /f`;

write_log_to_file("Closing all Chrome if working backround...\n");
`taskkill /im chrome.exe /f`;

sleep(1);

//Function to click on the Link

function clickLinkGoogle($site_domain, $webdriver)
{
	$link_clicked = false;
	$res_array = $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.rc');
        foreach ($res_array as $one_block) {

    $h3_element = $one_block->findElementBy(LocatorStrategy::cssSelector, 'h3');
    $a_eleamnt  = $h3_element->findElementBy(LocatorStrategy::cssSelector, 'a');
    $is_domain_exists = strpos($a_eleamnt->getAttribute('href'), $site_domain);
    if ($is_domain_exists == true) {
        $a_eleamnt->click();
        sleep(15);
        $webdriver->closeWindow();
        $webdriver->close();
		$link_clicked = true;
		break;
	}
	
}

return $link_clicked;

}

function openNextPageGoogle($pageNo, $webdriver)
{
    $table_data = $webdriver->findElementBy(LocatorStrategy::id, 'nav');
    $tbody_data = $table_data->findElementBy(LocatorStrategy::cssSelector, 'tbody');
    $tr_data    = $tbody_data->findElementBy(LocatorStrategy::cssSelector, 'tr');
    $td_data    = $tr_data->findElementsBy(LocatorStrategy::cssSelector, 'td');
    $a_eleamnt  = $td_data[$pageNo]->findElementBy(LocatorStrategy::cssSelector, 'a');
    $a_eleamnt->click();
    sleep(15);
}

function clickLinkYahoo($site_domain, $webdriver)
{
		$link_clicked = false;
	    
	    $class_data = $webdriver->findElementsBy(LocatorStrategy::cssSelector, "div[class='compTitle options-toggle']");

        foreach ($class_data as $one_block) {

            $h3 = $one_block->findElementBy(LocatorStrategy::cssSelector, 'h3');
	    	$a_eleamnt = $h3->findElementBy(LocatorStrategy::cssSelector, 'a');
            $link = $a_eleamnt->getAttribute('href');
			
            $is_domain_exists = strpos($a_eleamnt->getAttribute('href'), $site_domain);
            
			if ($is_domain_exists == true) {
                $a_eleamnt->click();
                sleep(6);
                $webdriver->closeWindow();
                $webdriver->close();
                $link_clicked = true;
                
                break;
			}
		
	}

return $link_clicked;

}

function openNextPageYahoo($pageNo, $webdriver,$is_first_page)
{
    
           
    $Parent_div_data = $webdriver->findElementBy(LocatorStrategy::cssSelector, "div[class='dd pagination fst lst Pgntn']");
    $child_div_data = $Parent_div_data->findElementBy(LocatorStrategy::cssSelector, '.compPagination');
    $a_eleamnt    = $child_div_data->findElementsBy(LocatorStrategy::cssSelector, 'a');

    if($is_first_page == true)
    {
        $next_page_index = $pageNo-2;
    }
    else
    {
        $next_page_index = $pageNo-1;
    }
    
    $next_page = $a_eleamnt[$next_page_index];
    $read_link = $next_page->getAttribute('href');
    $next_page->click();
    

}

function clickLinkBing($site_domain, $webdriver)
{
		$link_clicked = false;
	    $class_data = $webdriver->findElementsBy(LocatorStrategy::cssSelector,'.b_algo');
        foreach ($class_data as $one_block) {

            $h2 = $one_block->findElementBy(LocatorStrategy::cssSelector, 'h2');
	    	$a_eleamnt = $h2->findElementBy(LocatorStrategy::cssSelector, 'a');
            $link = $a_eleamnt->getAttribute('href');
			
            $is_domain_exists = strpos($a_eleamnt->getAttribute('href'), $site_domain);
            
			if ($is_domain_exists == true) {
                $a_eleamnt->click();
                sleep(6);
                $webdriver->closeWindow();
                $webdriver->close();
                $link_clicked = true;
                break;
			}
		
	}

return $link_clicked;

}

function openNextPageBing($pageNo, $webdriver,$is_first_page)
{
    if($is_first_page == true)
    {
        $Parent_ul_data = $webdriver->findElementBy(LocatorStrategy::cssSelector,'.sb_pagF');
        $child_li_data = $Parent_ul_data->findElementsBy(LocatorStrategy::cssSelector, 'li');
        $next_page_index = $pageNo;
        $is_first_page = false;
    }
    else
    {
        $next_page_index = $pageNo;
        $Parent_li_data = $webdriver->findElementBy(LocatorStrategy::cssSelector,'.b_pag');
        $nav_data = $Parent_li_data->findElementBy(LocatorStrategy::cssSelector,'nav');
        $ul_data = $nav_data->findElementBy(LocatorStrategy::cssSelector,'ul');
        $child_li_data = $ul_data->findElementsBy(LocatorStrategy::cssSelector,'li');
    }
    
    $next_page = $child_li_data[$next_page_index]->findElementBy(LocatorStrategy::cssSelector, 'a');
    $next_page->click();
    sleep(6);

}



//$engines =[""]
while (true) {
    write_log_to_file('Before Executing query .\n');
    
    $Query         = 'SELECT * FROM `crawler_task` ORDER BY rand() LIMIT 1';
    $Query_Results = $dbo->execute($Query);
    if (!sizeof($Query_Results))
        break;
    
    $task = $Query_Results[0];
    echo 'task id ' . $task['id'];
    
    $engine_array = array("google", "yahoo", "bing");

    $array_index = array_rand($engine_array);
    $engine = $engine_array[$array_index];
	
    if ($task['type'] == 'search') {

	if($engine == 'google'){
        $webdriver = new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
        $webdriver->connect('chrome', '', array(
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
		
		$query =  "https://www.google.com/search?q=".$task['query'];

        $webdriver->get($query);
        
        sleep(15);
        
        $site_domain = $task['target_domain'];
        
        $link_clicked = clickLinkGoogle($site_domain,$webdriver);
		
        if ($link_clicked == false) {
            openNextPageGoogle(2, $webdriver);
			$link_clicked = clickLinkGoogle($site_domain,$webdriver);
        }
        
        if ($link_clicked == false) {
            openNextPageGoogle(3, $webdriver);
            $link_clicked = clickLinkGoogle($site_domain,$webdriver);
            
		}
	}elseif($engine == 'yahoo'){

		$webdriver = new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
        $webdriver->connect('chrome', '', array(
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
		
		$query =  "https://in.search.yahoo.com/search?p=".$task['query'];

		$webdriver->get($query);

		//sleep(15);
        
		$site_domain = $task['target_domain'];
        $site_domain = "php.net";
        $link_clicked = clickLinkYahoo($site_domain,$webdriver);
		$is_first_page = true;
        
        if ($link_clicked == false) {
             openNextPageYahoo(2, $webdriver,$is_first_page);
             $link_clicked = clickLinkYahoo($site_domain,$webdriver);
             $is_first_page = false;
    
        }
        
        if ($link_clicked == false) {
            openNextPageYahoo(3, $webdriver,$is_first_page);
            $link_clicked = clickLinkYahoo($site_domain,$webdriver);
            
		}

}  elseif($engine == 'bing'){

		$webdriver = new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
        $webdriver->connect('chrome', '', array(
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
		
		$query =  "https://www.bing.com/search?q=".$task['query'];

		$webdriver->get($query);
        sleep(5);
		//sleep(15);
        
		$site_domain = $task['target_domain'];
        $site_domain = "www.digitalocean.com";
        

        $link_clicked = clickLinkBing($site_domain,$webdriver);
        $is_first_page = true;
        
        if ($link_clicked == false) {
             openNextPageBing(2, $webdriver,$is_first_page);
             $link_clicked = clickLinkBing($site_domain,$webdriver);
             $is_first_page = false;
             
       }
       
       if ($link_clicked == false) {
           openNextPageBing(3, $webdriver,$is_first_page);
           $link_clicked = clickLinkBing($site_domain,$webdriver);
           sleep(5);
       }

     
      

       
        
		
        

}  
}
}
?>