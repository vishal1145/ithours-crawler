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

$dbo		= get_dbo();
//$settings	= get_settings();
$html		= new simple_html_dom();
$_mail_auto = false;

// if (!$settings['system_working']) {
// 	exit;
// }

write_log_to_file("Closing all Chrome Drivers if working backround...\n");
`taskkill /im chromedriver.exe /f`;

write_log_to_file("Closing all Chrome if working backround...\n");
`taskkill /im chrome.exe /f`;

sleep(1);

//Function to click on the Link

 function checkLink($one_block,$site_domain,$webdriver){

	$h3_element =    $one_block->findElementBy(LocatorStrategy::cssSelector, 'h3');
	$a_eleamnt =  $h3_element->findElementBy(LocatorStrategy::cssSelector, 'a');

	$is_domain_exists = strpos($a_eleamnt->getAttribute('href'), $site_domain);

	$link_clicked = false;
	if($is_domain_exists==true)
			{
							$ithours_link = $a_eleamnt->getAttribute('href');
							$a_eleamnt->click();
							$link_clicked = true;
							sleep(15);
						    $webdriver->closeWindow();
							$webdriver->close();
							$link_clicked = true;
							
				
			}

			return $link_clicked;

 }


while(true)
{
	write_log_to_file('Before Executing query .\n');	

		$Query = 'SELECT * FROM `crawler_task` WHERE  status ="NEW" ORDER BY rand() LIMIT 1';
		$Query_Results	= $dbo->execute($Query);

		if (!sizeof($Query_Results))
		break;

		$task = $Query_Results[0];

		echo 'task id '.$task['id'];
		if ($task['type'] == 'search') {

	    $webdriver	= new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
		$webdriver->connect('chrome', '', array
		(
			'proxy'=>array
			(
				'proxyType'		=> 'manual',
				'httpProxy'		=> $bot['domain'].':'.$bot['http_port'],
				'sslProxy'		=> $bot['domain'].':'.$bot['http_port']
			),
			'chromeOptions'=>array('args'=>array('user-data-dir='.$GLOBALS['config']['bots_user_data_dir'].$bot['id'].'/','disable-infobars','ignore-certificate-errors','disable-notifications','load-extension='.$GLOBALS['config']['chrome_multipass_extension_dir']))
		));

		$webdriver->windowMaximize();
		$webdriver->setImplicitWaitTimeout(10000);
		$webdriver->setSpeed('SLOW');
		
		$webdriver->get($task['query']);

		sleep(15);

		$site_domain = $task['target_domain'];

		$link_clicked= false;

		$res_array = $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.rc');
		
		foreach ($res_array as $one_block) {
			
			checkLink($one_block,$site_domain,$webdriver);
			
		}
		if($link_clicked == false)
			{
			//$next_pages = $webdriver->findElementBy(LocatorStrategy::cssSelector, '.f1');

			$table_data	= $webdriver->findElementBy(LocatorStrategy::id, 'nav');
			$tbody_data	= $table_data->findElementBy(LocatorStrategy::cssSelector, 'tbody');
			$tr_data	= $tbody_data->findElementBy(LocatorStrategy::cssSelector, 'tr');
			$td_data	= $tr_data->findElementsBy(LocatorStrategy::cssSelector, 'td');
			// $td_1st_data_deleted = array_shift($td_data);
			// $td_2nd_data_deleted = array_shift($td_data);

				 $a_eleamnt =    $td_data[2]->findElementBy(LocatorStrategy::cssSelector, 'a');
				 $ithours_link = $a_eleamnt->getAttribute('href');
				 $a_eleamnt->click();
				 sleep(15);

				 $res_array = $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.rc');
		
		    foreach ($res_array as $one_block){

			if($link_clicked == true)
				break;

			checkLink($one_block,$site_domain,$webdriver);
			
		    }
			
			}

			if($link_clicked == false)
			{
			//$next_pages = $webdriver->findElementBy(LocatorStrategy::cssSelector, '.f1');

			$table_data	= $webdriver->findElementBy(LocatorStrategy::id, 'nav');
			$tbody_data	= $table_data->findElementBy(LocatorStrategy::cssSelector, 'tbody');
			$tr_data	= $tbody_data->findElementBy(LocatorStrategy::cssSelector, 'tr');
			$td_data	= $tr_data->findElementsBy(LocatorStrategy::cssSelector, 'td');
			// $td_1st_data_deleted = array_shift($td_data);
			// $td_2nd_data_deleted = array_shift($td_data);

				 $a_eleamnt =    $td_data[3]->findElementBy(LocatorStrategy::cssSelector, 'a');
				 $ithours_link = $a_eleamnt->getAttribute('href');
				 $a_eleamnt->click();

				 sleep(15);

				 $res_array = $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.rc');
		
		    foreach ($res_array as $one_block){

			if($link_clicked == true)
				break;

				$link_clicked = checkLink($one_block,$site_domain,$webdriver);

				echo $link_clicked;
			
		    }
			
			}

			
		

	}

	
	}

?>