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

`taskkill /im chromedriver.exe /f`;

`taskkill /im chrome.exe /f`;

sleep(3);

$dbo		= get_dbo();
$settings	= get_settings();
$html		= new simple_html_dom();


sleep(1);


$webdriver	= new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
		$webdriver->connect('chrome', '', array
		(
			'chromeOptions'=>array('args'=>array('user-data-dir='.$GLOBALS['config']['bots_user_data_dir'].$bot['id'].'/','disable-infobars','ignore-certificate-errors','disable-notifications','load-extension='.$GLOBALS['config']['chrome_multipass_extension_dir']))
		));

		$webdriver->windowMaximize();
		$webdriver->setImplicitWaitTimeout(10000);
		$webdriver->setSpeed('SLOW');

$webdriver->get("https://accounts.google.com/AccountChooser?service=mail");
//$webdriver->get("https://accounts.google.com/AccountChooser/identifier?service=mail&amp%3Bcontinue=https%3A%2F%2Fmail.google.com%2Fmail%2F&flowName=GlifWebSignIn&flowEntry=AddSession");
sleep(5);

$continue_element1	= $webdriver->findElementBy(LocatorStrategy::id, 'identifierLink');
$continue_element1->click();

//document.getElementById("identifierLink").click()

// $element	= $webdriver->findElementBy(LocatorStrategy::id, 'identifierId');
// $element->sendKeys(str_split("aksingh@ithours.com"));

$email = "winettima@gmail.com";
$webdriver->executeScript('document.getElementById("identifierId").value="'.$email.'";', array());

$continue_element	= $webdriver->findElementBy(LocatorStrategy::id, 'identifierNext');
$continue_element->click();


sleep(3);

$password = "8S5zuby4Lq";
$webdriver->executeScript('document.getElementsByClassName("whsOnd zHQkBf")[0].value="'.$password.'";', array());

// $continue_element	= $webdriver->findElementBy(LocatorStrategy::id, 'identifierNext');
// $continue_element->click();

//$continue_element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'ZFr60d CeoRYc');
$webdriver->executeScript('document.getElementsByClassName("ZFr60d CeoRYc")[0].click();', array());

$elements_blocks = $webdriver->findElements(LocatorStrategy::cssSelector, "#container_id > div");
foreach ($elements_blocks as $element_block) {
	echo $element_block->getText();
}


//$continue_element->click();

sleep(10);


?>
