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
require_once('./crawler_functions/driver_manager.php');
require_once('./crawler_functions/user_journey_manager.php');
require_once('./crawler_functions/site_crawlers/yoast_crawler.php');

require_once('./crawler_functions/crawler_factory.php');

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$dbo        = get_dbo();
$html       = new simple_html_dom();
$_mail_auto = false;

$browser ='chrome';

while (true) {
    write_log_to_file('Before Executing query .\n');
    
    write_log_to_file("Closing all Chrome Drivers if working backround...\n");
    `taskkill /im chromedriver.exe /f`;
   
    write_log_to_file("Closing all Chrome if working backround...\n");
    `taskkill /im chrome.exe /f`;

    sleep(1);

    //$Query         = 'SELECT * FROM `crawler_task` ORDER BY rand() LIMIT 1';
    $Query         = 'SELECT * FROM `crawler_task` where type ="user_journey" ORDER BY rand() LIMIT 1';
    
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
    $proxy	= $dbo->execute('SELECT * FROM proxies ORDER BY rand() LIMIT 1');
    $proxy	= $proxy[0];
        

    $array_index = array_rand($engine_array);
    $engine      = $engine_array[$array_index];

    //$engine = 'google';
    //$task['type'] = 'search';
    //$task['type'] = 'direct_hit';
    //$task['type'] = 'user_journey';
    //$task['type'] = 'quora';
     $task['type'] = 'siteCrawling';


    if ($task['type'] == 'create_bot') {

		write_log_to_file('Creating a new bot...'."\n");

		// Rastgele bir proxy seç.
		$proxy			= $dbo->execute('SELECT * FROM proxies ORDER BY rand() LIMIT 1');
		$proxy			= $proxy[0];
		$country_code	= $proxy['country_code'];
        
        //name should be random
        $first_name	="";
        $last_name="";
        while (true) {

			// Proxy ülke koduna göre rastgele ad bulmaya çalış.
			$first_name	= $dbo->execute('SELECT * FROM first_names WHERE country_code=\''.$country_code.'\' ORDER BY rand() LIMIT 1');

			// Bu ülke için ad yoksa, global (*) adlar arasından seç.
			if (!sizeof($first_name))
				$first_name	= $dbo->execute('SELECT * FROM first_names WHERE country_code=\'*\' ORDER BY rand() LIMIT 1');

			// Proxy ülke koduna göre rastgele soyad bulmaya çalış.
			$last_name	= $dbo->execute('SELECT * FROM last_names WHERE country_code=\''.$country_code.'\' ORDER BY rand() LIMIT 1');

			// Bu ülke için soyad yoksa, global (*) soyadlar arasından seç.
			if (!sizeof($last_name))
				$last_name	= $dbo->execute('SELECT * FROM last_names WHERE country_code=\'*\' ORDER BY rand() LIMIT 1');

			$first_name	= $first_name[0]['name'];
			$last_name	= $last_name[0]['name'];

			// Bu ad soyad ikilisi herhangi bir bot için daha önce kullanılmış mı kontrol et.
			if (!$dbo->exists('bots', array('first_name'=>$first_name, 'last_name'=>$last_name)))
				break;

			// Eğer bir bot tarafından kullanılmışsa, bu sefer yalnızca global isimler üzerinden dene.
			$first_name	= $dbo->execute('SELECT * FROM first_names WHERE country_code=\'*\' ORDER BY rand() LIMIT 1');
			$last_name	= $dbo->execute('SELECT * FROM last_names WHERE country_code=\'*\' ORDER BY rand() LIMIT 1');
			$first_name	= $first_name[0]['name'];
			$last_name	= $last_name[0]['name'];

			// Bu ad soyad ikilisi herhangi bir bot için daha önce kullanılmış mı kontrol et.
			if (!$dbo->exists('bots', array('first_name'=>$first_name, 'last_name'=>$last_name)))
				break;

		}
            
		//-------------------------------------------------------------------------------------------------------------
		// Bot bilgilerini oluştur.
		//-------------------------------------------------------------------------------------------------------------
		$bot = array
		(
			'email'				=> '',
			'username'			=> '',
			'password'			=> generate_key(rand(11, 16)),
			'first_name'		=> $first_name,
			'last_name'			=> $last_name,
			'proxy_id'			=> $proxy['id'],
			'country_code'		=> $country_code,
			'target_followers'	=> rand(2, 10),				// Bu botu takip edecek bot sayısı. (2 ile 10 arasında rasgele)
			'current_followers'	=> 0,
			'created_time'		=> date('Y-m-d H:i:s'),
			'status'			=> 'creating'
		);

		// Botu veritabanına kaydet.
		$bot['id']	= $dbo->insert('bots', $bot);

		//-------------------------------------------------------------------------------------------------------------
		// Chrome multipass eklenti ayarlarını yapılandır.
		//-------------------------------------------------------------------------------------------------------------

		$webdriver	= new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
		$webdriver->connect('chrome', '', array
		(
			'chromeOptions'=>array('args'=>array('user-data-dir='.$GLOBALS['config']['bots_user_data_dir'].$bot['id'].'/','disable-infobars','ignore-certificate-errors','disable-notifications','load-extension='.$GLOBALS['config']['chrome_multipass_extension_dir']))
		));

		$webdriver->windowMaximize();
		$webdriver->setImplicitWaitTimeout(10000);
		$webdriver->setSpeed('SLOW');

		// 320 - 530
		// id extension-options-overlay-guest
		
		// Chrome multipass eklentisinin ayar sayfasına git.
		// chrome://extensions-frame/?options=enhldmjbphoeibbpdhmjkchohnidgnah
		$webdriver->get('chrome-extension://enhldmjbphoeibbpdhmjkchohnidgnah/options.html');
		sleep(3);


		//echo $webdriver->getPageSource();

		//echo "\n\n\n";

		// $element	= $webdriver->findElementBy(LocatorStrategy::id, 'url');
		// $element->sendKeys(str_split('.*'));
		$url_id_value=".*";
		$webdriver->executeScript('document.getElementById("url").value="'.$url_id_value.'";', array());

		// $element	= $webdriver->findElementBy(LocatorStrategy::id, 'username');
		// $element->sendKeys(str_split($GLOBALS['config']['proxy']['username']));
	
		$username_id_value=$GLOBALS['config']['proxy']['username'];
		$webdriver->executeScript('document.getElementById("username").value="'.$username_id_value.'";', array());

        $password_id_value=$GLOBALS['config']['proxy']['password'];
		$webdriver->executeScript('document.getElementById("password").value="'.$password_id_value.'";', array());
		// $element	= $webdriver->findElementBy(LocatorStrategy::id, 'password');
		// $element->sendKeys(str_split($GLOBALS['config']['proxy']['password']));

		$element	= $webdriver->findElementBy(LocatorStrategy::id, 'analytics-enabled');
		$element->click();

		$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.credential-form-submit');
		$element->click();

		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
        sleep(1);
        
    }elseif($task['type'] == 'search') {
        
        if ($engine == 'google') {

        $driverManager = driverManager();
            
            $query = "https://www.google.com/search?q=" . $task['query'];
            
            $driverManager->webdriver->get($query);
            
            $sleep_time = rand($task['min_wait_factor'],$task['max_wait_factor']);
            sleep($sleep_time);
            
            //$site_domain = $task['target_domain'];
            
            $link_clicked = clickLinkGoogle($task, $driverManager->webdriver);
            
            if ($link_clicked == false) {
                openNextPageGoogle(2, $driverManager->webdriver);
                $link_clicked = clickLinkGoogle($task, $driverManager->webdriver);
            }
            
            if ($link_clicked == false) {
                openNextPageGoogle(3, $driverManager->webdriver);
                $link_clicked = clickLinkGoogle($task, $driverManager->webdriver);
                
            }
            if ($link_clicked == false) {
                $driverManager->webdriver->closeWindow();
                $driverManager->webdriver->close();
                  break;
                
            }
        } elseif ($engine == 'yahoo') {
            
            $driverManager = driverManager();
            
            $query = "https://in.search.yahoo.com/search?p=" . $task['query']."&fr=yfp-t&fp=1&toggle=1&cop=mss&ei=UTF-8";
            
            $driverManager->webdriver->get($query);
            
            $sleep_time = rand($task['min_wait_factor'],$task['max_wait_factor']);
            sleep($sleep_time);
            
            //$site_domain   = $task['target_domain'];
            //$site_domain   = "php.net";
            $link_clicked  = clickLinkYahoo($task,$driverManager->webdriver);
            $is_first_page = true;
            
            if ($link_clicked == false) {
                openNextPageYahoo(2,$driverManager->webdriver, $is_first_page);
                $link_clicked  = clickLinkYahoo($task,$driverManager->webdriver);
                $is_first_page = false;
                
            }
            
            if ($link_clicked == false) {
                openNextPageYahoo(3, $driverManager->webdriver, $is_first_page);
                $link_clicked = clickLinkYahoo($task, $driverManager->webdriver);
                
            }
            if ($link_clicked == false) {
                $driverManager->webdriver->closeWindow();
                $driverManager->webdriver->close();
                break;
              
          }
            
        } elseif ($engine == 'bing') {
            
            $driverManager = driverManager();
            
            $query = "https://www.bing.com/search?q=" . $task['query'];
            
            $driverManager->webdriver->get($query);
            $sleep_time = rand($task['min_wait_factor'],$task['max_wait_factor']);
            sleep($sleep_time);
            
            $link_clicked  = clickLinkBing($task, $driverManager->webdriver);
            $is_first_page = true;
            
            if ($link_clicked == false) {
                openNextPageBing(2, $driverManager->webdriver, $is_first_page);
                $link_clicked  = clickLinkBing($task, $driverManager->webdriver);
                $is_first_page = false;
                
            }
            
            if ($link_clicked == false) {
                openNextPageBing(3, $driverManager->webdriver, $is_first_page);
                $link_clicked = clickLinkBing($task, $driverManager->webdriver);
                sleep($task['sleep_time']);
            }
            if ($link_clicked == false) {
                $driverManager->webdriver->closeWindow();
                $driverManager->webdriver->close();
                break;
              
          }
            
        }
    }elseif($task['type'] == 'direct_hit'){
        $driverManager = driverManager();
            $query = $task['target_domain'];
            $driverManager->webdriver->get($query);
            $sleep_time = rand($task['min_wait_factor'],$task['max_wait_factor']);
            sleep($sleep_time);
            $driverManager->webdriver->closeWindow();
            $driverManager->webdriver->close();

    }
    elseif($task['type'] == 'user_journey')
    {

        $user_journey_function_call = user_journey_function($task);

    }
    elseif($task['type'] == 'quora')    {
        $driverManager = driverManager();
            $query ="https://www.quora.com/";
            $driverManager->webdriver->get($query);
           //$sleep_time = rand($task['min_wait_factor'],$task['max_wait_factor']);
            //sleep(5);
            
            // $div_id    = $driverManager->webdriver->findElementBy(LocatorStrategy::id, '__w2_OWY3YUm_connect_explanation');
            // $a_element = $div_id->findElementBy(LocatorStrategy::cssSelector, 'a');
            // $a_element->click();
            $css_selector = '//*[@id="__w2_JbwLNZ3_continue_with_email"]';
            $link = $driverManager->webdriver->findElementBy(LocatorStrategy::xpath, $css_selector);
        $link->click();
            sleep(5);
            

    }
    elseif($task['type'] == 'siteCrawling')
     {
    //     //$k=siteCrawling();
    //     //echo "Abhitesh";
        
         $type= 'YOAST';

    //     $crawler =   CrawlerFactory()->GetCrawler($type);

    //     $process ='REGISTRATION';
        
    //     if($process == 'REGISTRATION')
    //      $crawler->REGISTRATION();
    //     if($process == 'LOGIN')
    //      $crawler->REGISTRATION();

    // function testFactoryMethod($SiteCrawlingInstance) {
       
    
    //   } 

    $type= 'YOAST';
    $process = 'REGISTRATION';
    

$crawlerObj = (new CrawlerFactory())->GetCrawler($type);

     if($process == 'REGISTRATION')
     {
         echo "abhitesh";
      $crawlerObj->register();
      echo "abhitesh";
     }
      else if($process == 'LOGIN')
         $crawlerObj->login();


    }
}
?>