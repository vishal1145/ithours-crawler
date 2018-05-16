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
$settings	= get_settings();
$html		= new simple_html_dom();
$_mail_auto = false;

if (!$settings['system_working']) {
	exit;
}

write_log_to_file("Closing all Chrome Drivers if working backround...\n");
`taskkill /im chromedriver.exe /f`;

write_log_to_file("Closing all Chrome if working backround...\n");
`taskkill /im chrome.exe /f`;

sleep(1);

while (true)
{
	write_log_to_file('Before Executing query .\n');	


	$task_google_cred_count = 'SELECT email,password,username FROM bot_google_account where used = 0 ORDER BY rand() LIMIT 1';
	$google_temp	= $dbo->execute($task_google_cred_count);
	if (!sizeof($google_temp)){
		$task_query = 'SELECT * FROM `tasks` WHERE `due`>='.time().' AND status ="NEW" and type="like_article" ORDER BY due asc LIMIT 1';

		 echo $task_query;
		 sleep(5);
		//$task_query = 'SELECT * FROM `tasks` WHERE `due`>='.time().' AND status ="NEW" AND type="visit_article" ORDER BY due asc LIMIT 1';
		//$task_query = 'SELECT * FROM `tasks` WHERE `due`>='.time().' AND status ="NEW" AND author_id= 44 ORDER BY due asc LIMIT 1';
	}else{
		$task_query = 'SELECT * FROM `tasks` WHERE type="create_bot_gmail" ORDER BY due asc LIMIT 1';		
	}
		


	// Yapılma zamanı gelmiş görevlerden en eski olanını veritabanından çek.
	//$task_query = 'SELECT * FROM `tasks` WHERE `due`>='.time().' AND status ="NEW" and type="like_article" AND author_id= 44 ORDER BY due asc LIMIT 1';
	//$task_query = 'SELECT * FROM `tasks` WHERE `due`>='.time().' AND status ="NEW" AND type="visit_article" ORDER BY due asc LIMIT 1';
	//$task_query = 'SELECT * FROM `tasks` WHERE `due`>='.time().' AND type="like_article" ORDER BY due asc LIMIT 1';
	//$task_query = 'SELECT * FROM `tasks` WHERE  status ="NEW" and type!="visit_article"  and type!="like_article" and type!="follow_author" and type!="comment_article" and type!="follow_bot" ORDER BY due asc LIMIT 1';
	//$task_query = 'SELECT * FROM `tasks` WHERE  status ="NEW"  ORDER BY due asc LIMIT 1';




	echo $task_query;
	
	
	// $task_query = "SELECT * FROM `tasks` WHERE `due`>='.time().' ORDER BY due asc LIMIT 1";
	write_log_to_file('Executing query '.$task_query."\n");	
	
	$tasks	= $dbo->execute($task_query);
	write_log_to_file('after Executing '."\n");	
		

 
				
   echo (sizeof($tasks));
  
	// Eğer şu an için yapılacak hiçbir görev yoksa çık.
	if (!sizeof($tasks))
		break;
	

	$task	= $tasks[0];

    echo 'task id '.$task['id'];


	
	$completed_time=time();
	
	$sql = "UPDATE tasks SET status ='RUNNING',completed_time='.$completed_time.' WHERE id= ".$task['id'];

	echo $sql;
	sleep(3);
	$update_query_response	= $dbo->execute($sql);

	write_log_to_file('Executing query '.$task_query);
	// Ekstra görev bilgileri varsa, JSON'dan dönüştür.
	if (strlen($task['extra'])) {
		$task['extra']	= json_decode($task['extra'], true);
	}

	write_log_to_file('Executing type '.$task['type']."\n");
	//$task['type'] = 'create_bot';

	//-------------------------------------------------------------------------------------------------------------
	// CREATE BOT
	//-------------------------------------------------------------------------------------------------------------
	if ($task['type'] == 'create_bot') {

		write_log_to_file('Creating a new bot...'."\n");

		// Rastgele bir proxy seç.
		$proxy			= $dbo->execute('SELECT * FROM proxies ORDER BY rand() LIMIT 1');
		$proxy			= $proxy[0];
		$country_code	= $proxy['country_code'];

		//-------------------------------------------------------------------------------------------------------------
		// Yeni üretilecek bot için ad ve soyad bul.
		//-------------------------------------------------------------------------------------------------------------
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

		//print_r($bot);

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

		$element	= $webdriver->findElementBy(LocatorStrategy::id, 'password');
		$element->sendKeys(str_split($GLOBALS['config']['proxy']['password']));

		$element	= $webdriver->findElementBy(LocatorStrategy::id, 'analytics-enabled');
		$element->click();

		$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.credential-form-submit');
		$element->click();

		


		/*
		//$webdriver->moveTo($extensionoptions, 300, 630);
		$extensionoptions->click();
		$str	= '[{"url":".*", "username":"'.$GLOBALS['config']['proxy']['username'].'", "password":"'.$GLOBALS['config']['proxy']['password'].'", "priority":"1"}]';
		sleep(2);
		$extensionoptions->sendKeys(str_split($str));
		sleep(2);

		// Multipass eklentisine proxy kullanıcı adı ve şifre bilgilerini gir.
		$str	= '[{"url":".*", "username":"'.$GLOBALS['config']['proxy']['username'].'", "password":"'.$GLOBALS['config']['proxy']['password'].'", "priority":"1"}]';

		$extensionoptions->sendKeys(str_split($str));
		$extensionoptions->sendKeys(array("\xEE\x80\x84"));		// Tab
		$extensionoptions->sendKeys(array("\xEE\x80\x87"));		// Enter
		sleep(1);

		$extensionoptions->sendKeys(array("\xEE\x80\x84"));
		$extensionoptions->sendKeys(array("\xEE\x80\x84"));
		$extensionoptions->sendKeys(array("\xEE\x80\x84"));
		$extensionoptions->sendKeys(array("\xEE\x80\x8D"));		// Space
		$extensionoptions->sendKeys(array("\xEE\x80\x8C"));		// ESC

		exit();
		*/

		// Değişikliklerin etkin olması için pencereyi kapat.
		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
		sleep(1);
		

		//-------------------------------------------------------------------------------------------------------------
		// Yeni proxy ayarlarıyla yeni bir Chrome bağlantısı aç.
		//-------------------------------------------------------------------------------------------------------------
		$webdriver	= new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
		$webdriver->connect('chrome', '', array
		(
			'proxy'=>array
			(
				'proxyType'		=> 'manual',
				'httpProxy'		=> $proxy['domain'].':'.$proxy['http_port'],
				'sslProxy'		=> $proxy['domain'].':'.$proxy['http_port']
			),
			'chromeOptions'=>array('args'=>array('user-data-dir='.$GLOBALS['config']['bots_user_data_dir'].$bot['id'].'/','disable-infobars','ignore-certificate-errors','disable-notifications','load-extension='.$GLOBALS['config']['chrome_multipass_extension_dir']))
		));

		$webdriver->windowMaximize();
		$webdriver->setImplicitWaitTimeout(3000);
		$webdriver->setSpeed('SLOW');
		sleep(2);

		//-------------------------------------------------------------------------------------------------------------
		// Kullanılacak geçici e-posta servisini seç.
		//-------------------------------------------------------------------------------------------------------------
		if ($settings['mail_source'] =='auto' || $_mail_auto) {
			//$settings['mail_source'] = $config['mail_sources'][rand(0, 1)];
			$settings['mail_source'] = $config['mail_sources'][1];
			$_mail_auto = true;
		}


      
		//-------------------------------------------------------------------------------------------------------------
		// temp-mail.org üzerinde yeni bir e-posta hesabı oluştur.
		//-------------------------------------------------------------------------------------------------------------
		if ($settings['mail_source'] == 'temp-mail.org') {

			

			write_log_to_file('Creating temp-mail.org account...'."\n");

			// mytemp.email için çağrı yap.
			$webdriver->get('https://temp-mail.org/en/');

			// input#mail görünene kadar bekle.
			try {
				$bot['email']	= wait_until(function() use ($webdriver) {
					$mail	= $webdriver->findElementsBy(LocatorStrategy::cssSelector, 'input#mail');
					if (sizeof($mail)) {
						return $mail[0]->getAttribute('value');
					}
				});
			} catch (exception $e) {
				// E-posta adresi oluşturulamadı. Bot işlemlerini iptal et, yeniden denensin.
				$dbo->delete('bots', $bot['id']);

				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				sleep(1);

				continue;
			}

			// Botun kullanıcı adını e-posta adresinden türet.
			$bot['username']	= substr($bot['email'], 0, strpos($bot['email'], '@')).generate_username(rand($settings['bot_username_min'], $settings['bot_username_max']));

			// Botun veritabanı bilgilerini güncelle.
			$dbo->update('bots', array('username'=>$bot['username'], 'email'=>$bot['email']), $bot['id']);

			write_log_to_file('Created temp-email.org account: ('.$bot['email'].")\n");


		//-------------------------------------------------------------------------------------------------------------
		// mytemp.email üzerinde yeni bir e-posta hesabı oluştur.
		//-------------------------------------------------------------------------------------------------------------
		} elseif ($settings['mail_source'] == 'mytemp.email') {

			

			write_log_to_file('Creating Mytemp.email account...'."\n");

			// mytemp.email için çağrı yap.
			$webdriver->get('http://mytemp.email/2');

			// Pencere başlığı içinde @ karakteri geçene kadar bekle.
			try {
				wait_until(function() use ($webdriver) {
					if (strpos($webdriver->getTitle(), '@') !== false)
						return true;
				});
			} catch (exception $e) {
				// E-posta adresi oluşturulamadı. Bot işlemlerini iptal et, yeniden denensin.
				$dbo->delete('bots', $bot['id']);

				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				sleep(1);

				continue;
			}

			// E-posta adresini bul.
			$bot['email']		= substr($webdriver->getTitle(), 15);
			$bot['email']		= substr($bot['email'], 0, strpos($bot['email'], ' '));

			// Botun kullanıcı adını e-posta adresinden türet.
			$bot['username']	= substr($bot['email'], 0, strpos($bot['email'], '@')).generate_username(rand($settings['bot_username_min'], $settings['bot_username_max']));

			// Botun veritabanı bilgilerini güncelle.
			$dbo->update('bots', array('username'=>$bot['username'], 'email'=>$bot['email']), $bot['id']);

			write_log_to_file('Created Mytemp.email account: ('.$bot['email'].")\n");

		}

		//-------------------------------------------------------------------------------------------------------------
		// Botu TradingView sitesine kayıt yap.
		//-------------------------------------------------------------------------------------------------------------
		write_log_to_file('Registering the bot...'."\n");
		sleep(2);
		 
		$bot['email'] ="raedyn.arhaan@its0k.com";
		$bot['username'] ="raedynarhaan";
		
		 $webdriver->get('https://www.tradingview.com/#signup');
		$webdriver->executeScript('$(".js-tv-expected-language.tv-expected-language").remove();', array());

		// Kayıt düğmesi görünene kadar bekle.
		$register_submit_button	= wait_until(function() use ($webdriver) {
			$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/signup/"] button');
			if ($element and $element->isDisplayed())
				return $element;
		});

		$register_email_input = $webdriver->findElementBy(LocatorStrategy::xpath, '//*[@id="signup-form"]/div[1]/div[1]/input');
		//$register_email_input		= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/signup/"] input[name="email"]');
		$register_username_input	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/signup/"] input[name="username"]');
		$register_password_input	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/signup/"] input[name="password"]');

		$captcha_token_input	    = $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/signup/"] textarea[name="g-recaptcha-response"]');
		$google_frame		        = $webdriver->findElementBy(LocatorStrategy::cssSelector, 'iframe'); 
		$rep_url                    = $google_frame->getAttribute("src");


		$recaptch_api_key = "eca4b054dd7be2a04bb8c5c29e5bc0a0";

		$cap_id_string= str_replace('https://www.google.com/recaptcha/api2/anchor?k=','',$rep_url);
		$google_captcha_id = substr($cap_id_string,0,strrpos($cap_id_string, "&co",0));
        $captcha_service_url_to_hit = "http://2captcha.com/in.php?key=".$recaptch_api_key."&method=userrecaptcha&googlekey=".$google_captcha_id."&pageurl=https://www.tradingview.com/";
		$captcha_request_id_string = file_get_contents($captcha_service_url_to_hit );
		$captcha_request_id = substr($captcha_request_id_string ,3);

		sleep(30);


		$captcha_res_url_to_hit ="http://2captcha.com/res.php?key=".$recaptch_api_key."&action=get&id=".$captcha_request_id;
		$captcha_token_id ="";

		$counter=0;
		while( $counter<10) {
			$captcha_token_id_string = file_get_contents($captcha_res_url_to_hit );
		 	if( $captcha_token_id_string == "CAPCHA_NOT_READY") { 
		 		$counter = $counter+1;
				echo "sleep 30 and counter is ".$counter;
				sleep(30);
		 	} 
		 	else {
		   		$captcha_token_id = substr($captcha_token_id_string ,3);
		  		$counter = 10;
		}
		}

		//$register_email_input->sendKeys(str_split($bot['email']));
		$scriptregister_email_input = 'document.querySelectorAll(\'form[action="/accounts/signup/"]\')[0][0].value="'.$bot['email'].'"';
		$webdriver->executeScript($scriptregister_email_input, array());
		sleep(2);
		
		//$register_username_input->sendKeys(str_split($bot['username']));
		$scriptregister_username_input = 'document.querySelectorAll(\'form[action="/accounts/signup/"]\')[0][1].value="'.$bot['username'].'"';
		$webdriver->executeScript($scriptregister_username_input, array());
		sleep(2);

		//$register_password_input->sendKeys(str_split($bot['password']));
		$scriptregister_password_input = 'document.querySelectorAll(\'form[action="/accounts/signup/"]\')[0][2].value="'.$bot['password'].'"';
		$webdriver->executeScript($scriptregister_password_input, array());
		sleep(2);

		/************* G CAPCTHA *********************/

		// $register_password_input->sendKeys(array("\xEE\x80\x84"));		// Tab
		// sleep(3);
		// $captcha = $webdriver->findActiveElement();
		// print_r($captcha);
		// $captcha->click();	
		// //$captcha->sendKeys(array("\xEE\x80\x8D"));	
		// //$register_password_input->sendKeys(array("\xEE\x80\x8D"));		// Space
		// sleep(10);

		/*********************************************/

		$webdriver->executeScript('document.getElementById("g-recaptcha-response").innerHTML="'.$captcha_token_id.'";', array());
		sleep(5);
		
		$register_submit_button->click();

		wait_until(function() use ($webdriver) {
			 return $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-signin-dialog__resend');
		});

		write_log_to_file('Register okay.'."\n");
		
		sleep(30);
		
		unset($register_link, $register_submit_button, $register_email_input, $register_username_input, $register_password_input);

		//-------------------------------------------------------------------------------------------------------------
		// temp-mail.org üzerinden e-posta aktivasyon linkini bul.
		//-------------------------------------------------------------------------------------------------------------
		if ($settings['mail_source'] == 'temp-mail.org') {

   			$webdriver->get('https://temp-mail.org/en/');

			   write_log_to_file('Waiting for activation mail...'."\n");

			try {
				$activation_message_link	= wait_until(function() use ($webdriver) {
					$messages	= $webdriver->findElementsBy(LocatorStrategy::cssSelector, 'table#mails .title-subject');
						foreach ($messages as $message) {
							$text	= trim($message->getText());
							if (strpos($text, 'TradingView activation') !== false) {
								return $message;
							}
						}
				});

			} catch (exception $e) {
				// E-posta adresi oluşturulamadı. Bot işlemlerini iptal et, yeniden denensin.
				$dbo->delete('bots', $bot['id']);

				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				add_activity_log($settings['mail_source'].' mail services error, system restarting', 'mail_service_error');
				sleep(1);

				continue;
			}

			write_log_to_file('Activation mail found. Reading...'."\n");
			$activation_message_link->click();

			wait_until(function() use ($webdriver) {
				return sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, 'div[data-x-div-type="body"]'));
			});

			$activation_link	= trim($webdriver->findElementBy(LocatorStrategy::cssSelector, 'div[data-x-div-type="body"] a[href*="https://www.tradingview.com/accounts/activate/"]')->getText());

			write_log_to_file('Activation link is: '.$activation_link."\n");

			unset($activation_message_link, $body);


		//-------------------------------------------------------------------------------------------------------------
		// mytemp.email üzerinden e-posta aktivasyon linkini bul.
		//-------------------------------------------------------------------------------------------------------------
		} elseif ($settings['mail_source'] == 'mytemp.email') {

   			$webdriver->back();

			   write_log_to_file('Waiting for activation mail...'."\n");

			try {

				$activation_message_link	= wait_until(function() use ($webdriver) {
					$message	= $webdriver->findElementBy(LocatorStrategy::xpath, ".//*[@class='truncate hide-sm ng-binding flex-25'][contains(., 'TradingView')]");
						$text	= trim($message->getText());
							if (strpos($text, 'TradingView') !== false) {
								return $message;
							}
				});

			} catch (exception $e) {
				// E-posta adresi oluşturulamadı. Bot işlemlerini iptal et, yeniden denensin.
				$dbo->delete('bots', $bot['id']);

				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				add_activity_log($settings['mail_source'].' mail services error, system restarting', 'mail_service_error');
				sleep(1);

				continue;
			}

			write_log_to_file('Activation mail found. Reading...'."\n");
			$activation_message_link->click();

			wait_until(function() use ($webdriver) {
				
				return sizeof($webdriver->findElementBy(LocatorStrategy::cssSelector, '#eml-parts'));
			});

			$url_ = $webdriver->getCurrentUrl();
			//echo "{$url_} bulundu... cikti.. goo...\n";
			sleep(1);

			wait_until(function() use ($webdriver) {
				return sizeof($webdriver->findElementBy(LocatorStrategy::cssSelector, '#eml-parts'));
			});

			$url_ = $webdriver->getCurrentUrl();
			//echo "{$url_} bulundu... cikti.. goo...\n";
			sleep(1);

			$url_ar = explode('/eml/', $url_);
			$url_parts = explode('/', $url_ar[1]);
			$url_json = 'https://api.mytemp.email/1/eml/get?eml='.$url_parts[0].'&eml_hash='.$url_parts[1];

			//echo $url_json." URL JSON \n\n";
			$response = ApiRequestCookie($url_json, $url_json, array(), null, null);
			$mailJs = json_decode($response);

			$mailText = $mailJs->body_html;

			//$activation_link_a	= trim($webdriver->findElementBy(LocatorStrategy::cssSelector, "#eml-part-raw > pre")->getText());
			//print_r($mailJs->body_html);
			preg_match_all("/https\:\/\/www\.tradingview(.*)campaign\=activation_email/", $mailJs->body_html, $activation_link_go);

			$activation_link = $activation_link_go[0][0];

			write_log_to_file('Activation link is: '.$activation_link."\n");

			unset($activation_message_link, $body);
		}


		//---------------------------------------------------------------------------------------------
		// Aktivasyon linkine git.
		//---------------------------------------------------------------------------------------------
		write_log_to_file('Going to the activation link...'."\n");
		$webdriver->get($activation_link);
		$webdriver->executeScript('$(".js-tv-expected-language.tv-expected-language").remove();', array());
		sleep(1);

		write_log_to_file('Ok...'."\n");

		// Devam düğmesi görünene kadar bekle.
		$continue_submit_button	= wait_until(function() use ($webdriver) {
			$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/first_login_data/"] button[type="submit"]');
			if ($element and $element->isDisplayed())
				return $element;
		});

		$continue_firstname_input	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/first_login_data/"] input[name="firstname"]');
		$continue_lastname_input	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/first_login_data/"] input[name="lastname"]');
		
		$continue_firstname_input->sendKeys(str_split($bot['first_name']));
		$continue_lastname_input->sendKeys(str_split($bot['last_name']));

		$continue_submit_button->click();
		
		// Kullanıcı adı görünene kadar bekle.
		$session_username	= wait_until(function() use ($webdriver, $bot) {
			$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
			if ($element and trim($element->getText()) == $bot['username'])
				return true;
		});

		// Botun veritabanı bilgilerini güncelle.
		$dbo->update('bots', array('status'=>'active'), $bot['id']);

		write_log_to_file('Bot created'."\n");


		//---------------------------------------------------------------------
		// Bu botu takip edecek botlar için görevler oluştur.
		//---------------------------------------------------------------------
		for ($i = 0; $i < $bot['target_followers']; $i ++) {

			// En erken takip zamanını önümüzdeki 12 saat ve en geç takip zamanını da sonraki 48 saat olarak belirle.
			$dbo->insert('tasks', array('type'=>'follow_bot', 'extra'=>json_encode(array('target_bot_id'=>$bot['id'])), 'due'=>time()+rand(86400/2, 86400*2)));

		}


		add_activity_log('Bot has been created: <b>'.$bot['username'].'</b> ('.$bot['email'].') from '.$proxy['country'], 'bot_create');

		unset($activation_link, $continue_submit_button, $continue_firstname_input, $continue_lastname_input, $session_username);

		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
		sleep(1);

	//-------------------------------------------------------------------------------------------------------------
	// CREATE BOT GMAIL
	//-------------------------------------------------------------------------------------------------------------
	} elseif ($task['type'] == 'create_bot_gmail') {

		write_log_to_file('Creating a new bot...'."\n");

		// Rastgele bir proxy seç.
		$proxy			= $dbo->execute('SELECT * FROM proxies ORDER BY rand() LIMIT 1');
		$proxy			= $proxy[0];
		$country_code	= $proxy['country_code'];

		//-------------------------------------------------------------------------------------------------------------
		// Yeni üretilecek bot için ad ve soyad bul.
		//-------------------------------------------------------------------------------------------------------------
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
			'password'			=> '',
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

		//print_r($bot);

        echo "bot id - ".$bot['id'];
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


		// $element	= $webdriver->findElementBy(LocatorStrategy::id, 'password');
		// $element->sendKeys(str_split($GLOBALS['config']['proxy']['password']));


		$password_id_value=$GLOBALS['config']['proxy']['password'];
		$webdriver->executeScript('document.getElementById("password").value="'.$password_id_value.'";', array());

		$element	= $webdriver->findElementBy(LocatorStrategy::id, 'analytics-enabled');
		$element->click();

		$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.credential-form-submit');
		$element->click();

		


		/*
		//$webdriver->moveTo($extensionoptions, 300, 630);
		$extensionoptions->click();
		$str	= '[{"url":".*", "username":"'.$GLOBALS['config']['proxy']['username'].'", "password":"'.$GLOBALS['config']['proxy']['password'].'", "priority":"1"}]';
		sleep(2);
		$extensionoptions->sendKeys(str_split($str));
		sleep(2);

		// Multipass eklentisine proxy kullanıcı adı ve şifre bilgilerini gir.
		$str	= '[{"url":".*", "username":"'.$GLOBALS['config']['proxy']['username'].'", "password":"'.$GLOBALS['config']['proxy']['password'].'", "priority":"1"}]';

		$extensionoptions->sendKeys(str_split($str));
		$extensionoptions->sendKeys(array("\xEE\x80\x84"));		// Tab
		$extensionoptions->sendKeys(array("\xEE\x80\x87"));		// Enter
		sleep(1);

		$extensionoptions->sendKeys(array("\xEE\x80\x84"));
		$extensionoptions->sendKeys(array("\xEE\x80\x84"));
		$extensionoptions->sendKeys(array("\xEE\x80\x84"));
		$extensionoptions->sendKeys(array("\xEE\x80\x8D"));		// Space
		$extensionoptions->sendKeys(array("\xEE\x80\x8C"));		// ESC

		exit();
		*/

		// Değişikliklerin etkin olması için pencereyi kapat.
		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
		sleep(1);
		

		//-------------------------------------------------------------------------------------------------------------
		// Yeni proxy ayarlarıyla yeni bir Chrome bağlantısı aç.
		//-------------------------------------------------------------------------------------------------------------
		$webdriver	= new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
		$webdriver->connect('chrome', '', array
		(
			'proxy'=>array
			(
				'proxyType'		=> 'manual',
				'httpProxy'		=> $proxy['domain'].':'.$proxy['http_port'],
				'sslProxy'		=> $proxy['domain'].':'.$proxy['http_port']
			),
			'chromeOptions'=>array('args'=>array('user-data-dir='.$GLOBALS['config']['bots_user_data_dir'].$bot['id'].'/','disable-infobars','ignore-certificate-errors','disable-notifications','load-extension='.$GLOBALS['config']['chrome_multipass_extension_dir']))
		));

		$webdriver->windowMaximize();
		$webdriver->setImplicitWaitTimeout(3000);
		$webdriver->setSpeed('SLOW');
		sleep(2);

		//-------------------------------------------------------------------------------------------------------------
		// Kullanılacak geçici e-posta servisini seç.
		//-------------------------------------------------------------------------------------------------------------
		


       //get usrname  , email and password from new table 

		//$bot['email'], $bot['psswod'], $bot['username']
		$getQueryValue = $dbo->execute('SELECT email,password,username FROM bot_google_account where used = 0 ORDER BY rand() LIMIT 1');
		$bot['email']=$getQueryValue[0]['email'];
		$bot['password']=$getQueryValue[0]['password'];
		$bot['username']=$getQueryValue[0]['username'];

		sendEmail($bot,'STARTING');
		//$bot['username']	= substr($bot['email'], 0, strpos($bot['email'], '@')).generate_username(rand($settings['bot_username_min'], $settings['bot_username_max']));

			// Botun veritabanı bilgilerini güncelle.
		$dbo->update('bots', array('username'=>$bot['username'], 'email'=>$bot['email'],'password'=>$bot['password']), $bot['id']);
		
		
		//$used = "UPDATE bot_google_account SET used = 1 WHERE username='" .$bot['username']."'";




		//echo $used;
	    //$update_used_Task_Completed_Response	= $dbo->execute($used);

		//-------------------------------------------------------------------------------------------------------------
		// Botu TradingView sitesine kayıt yap.
		//-------------------------------------------------------------------------------------------------------------
		write_log_to_file('Registering the bot...'."\n");
		sleep(2);
		 
		
		
		$webdriver->get('https://www.tradingview.com/#signup');
		$webdriver->executeScript('$(".js-tv-expected-language.tv-expected-language").remove();', array());

		// Kayıt düğmesi görünene kadar bekle.
		$register_submit_button	= wait_until(function() use ($webdriver) {
			$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/signup/"] button');
			if ($element and $element->isDisplayed())
				return $element;
		});

		$register_email_input = $webdriver->findElementBy(LocatorStrategy::xpath, '//*[@id="signup-form"]/div[1]/div[1]/input');
		//$register_email_input		= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/signup/"] input[name="email"]');
		$register_username_input	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/signup/"] input[name="username"]');
		$register_password_input	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/signup/"] input[name="password"]');

		$captcha_token_input	    = $webdriver->findElementBy(LocatorStrategy::cssSelector, 'form[action="/accounts/signup/"] textarea[name="g-recaptcha-response"]');
		$google_frame		        = $webdriver->findElementBy(LocatorStrategy::cssSelector, 'iframe'); 
		$rep_url                    = $google_frame->getAttribute("src");


		$recaptch_api_key = "eca4b054dd7be2a04bb8c5c29e5bc0a0";

		//$cap_id_string= str_replace('https://www.google.com/recaptcha/api2/anchor?k=','',$rep_url);
		$cap_id_string= str_replace('https://www.google.com/recaptcha/api2/anchor?ar=1&k=','',$rep_url);
		$google_captcha_id = substr($cap_id_string,0,strrpos($cap_id_string, "&co",0));
        $captcha_service_url_to_hit = "http://2captcha.com/in.php?key=".$recaptch_api_key."&method=userrecaptcha&googlekey=".$google_captcha_id."&pageurl=https://www.tradingview.com/";

        write_log_to_file('$captcha_service_url_to_hit -- '.$captcha_service_url_to_hit."\n");

		$captcha_request_id_string = file_get_contents($captcha_service_url_to_hit );
		$captcha_request_id = substr($captcha_request_id_string ,3);

		sleep(30);

 		write_log_to_file('$captcha_request_id -- '.$captcha_request_id."\n");


		$captcha_res_url_to_hit ="http://2captcha.com/res.php?key=".$recaptch_api_key."&action=get&id=".$captcha_request_id;
		$captcha_token_id ="";

		write_log_to_file('$captcha_res_url_to_hit -- '.$captcha_res_url_to_hit."\n");
		
		$counter=0;
		while( $counter<10) {
			$captcha_token_id_string = file_get_contents($captcha_res_url_to_hit );
		 	if( $captcha_token_id_string == "CAPCHA_NOT_READY") { 
		 		$counter = $counter+1;
				echo "sleep 30 and counter is ".$counter;
				sleep(30);
		 	} 
		 	else {
		   		$captcha_token_id = substr($captcha_token_id_string ,3);
		  		$counter = 10;
		}
		}

		write_log_to_file('$captcha_token_id -- '.$captcha_token_id."\n");
		
		//$register_email_input->sendKeys(str_split($bot['email']));
		$scriptregister_email_input = 'document.querySelectorAll(\'form[action="/accounts/signup/"]\')[0][0].value="'.$bot['email'].'"';
		$webdriver->executeScript($scriptregister_email_input, array());
		sleep(2);
		
		//$register_username_input->sendKeys(str_split($bot['username']));
		$scriptregister_username_input = 'document.querySelectorAll(\'form[action="/accounts/signup/"]\')[0][1].value="'.$bot['username'].'"';
		$webdriver->executeScript($scriptregister_username_input, array());
		sleep(2);

		//$register_password_input->sendKeys(str_split($bot['password']));
		$scriptregister_password_input = 'document.querySelectorAll(\'form[action="/accounts/signup/"]\')[0][2].value="'.$bot['password'].'"';
		$webdriver->executeScript($scriptregister_password_input, array());
		sleep(2);

		/************* G CAPCTHA *********************/

		// $register_password_input->sendKeys(array("\xEE\x80\x84"));		// Tab
		// sleep(3);
		// $captcha = $webdriver->findActiveElement();
		// print_r($captcha);
		// $captcha->click();	
		// //$captcha->sendKeys(array("\xEE\x80\x8D"));	
		// //$register_password_input->sendKeys(array("\xEE\x80\x8D"));		// Space
		// sleep(10);

		/*********************************************/

		$webdriver->executeScript('document.getElementById("g-recaptcha-response").innerHTML="'.$captcha_token_id.'";', array());
		sleep(5);
		
		$register_submit_button->click();

		wait_until(function() use ($webdriver) {
			 return $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-signin-dialog__resend');
		});

		write_log_to_file('Register okay.'."\n");

		//$used = "UPDATE bot_google_account SET used = 1 WHERE username='".$bot['username']."'";
		//echo $used;
	    // $update_used_Task_Completed_Response = $dbo->execute($used);
		
		sleep(30);
		
		unset($register_link, $register_submit_button, $register_email_input, $register_username_input, $register_password_input);

		//-------------------------------------------------------------------------------------------------------------
		// temp-mail.org üzerinden e-posta aktivasyon linkini bul.
		//-------------------------------------------------------------------------------------------------------------
		


		//---------------------------------------------------------------------------------------------
		// Aktivasyon linkine git.
		//---------------------------------------------------------------------------------------------
		write_log_to_file('Going to the activation link...'."\n");

		write_log_to_file('Ok...'."\n");

		//manul step
		//open gmail with that accout and click on activation link , put first naame abd lasetame 

		echo 'register bot id = '.$bot['id'];

	

		// Botun veritabanı bilgilerini güncelle.

		//activate bot comment
		//$dbo->update('bots', array('status'=>'active'), $bot['id']);

		write_log_to_file('Bot created'."\n");

		sendEmail($bot,'REGISTERED');

		//---------------------------------------------------------------------
		// Bu botu takip edecek botlar için görevler oluştur.
		//---------------------------------------------------------------------
		for ($i = 0; $i < $bot['target_followers']; $i ++) {

			// En erken takip zamanını önümüzdeki 12 saat ve en geç takip zamanını da sonraki 48 saat olarak belirle.
			$dbo->insert('tasks', array('type'=>'follow_bot', 'extra'=>json_encode(array('target_bot_id'=>$bot['id'])), 'due'=>time()+rand(86400/2, 86400*2)));

		}


		add_activity_log('Bot has been created: <b>'.$bot['username'].'</b> ('.$bot['email'].') from '.$proxy['country'], 'bot_create');

		unset($activation_link, $continue_submit_button, $continue_firstname_input, $continue_lastname_input, $session_username);

		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
		sleep(1);
	//-------------------------------------------------------------------------------------------------------------
	// FOLLOW AUTHOR
	//-------------------------------------------------------------------------------------------------------------
	} elseif ($task['type'] == 'follow_author') {

		// Yazarı veritabanından çek.
		$author		= $dbo->seek('authors', $task['author_id']);

		// Yazar bulunamadıysa silinmiş olabilir.
		if (!$author)
			goto end_of_task;

		// Bu yazarı hali hazırda takip etmeyen botlardan birini rastgele çek.
		$bot	= $dbo->execute('SELECT bot.*, proxy.domain, proxy.http_port, proxy.country FROM bots AS bot LEFT JOIN proxies AS proxy ON proxy.id=bot.proxy_id WHERE bot.status=\'active\' AND bot.id NOT IN(SELECT bot_id FROM author_follows WHERE author_id='.$author['id'].') ORDER BY rand() LIMIT 1');
		$bot	= $bot[0];

		//---------------------------------------------------------------------------------------------
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
		//---------------------------------------------------------------------------------------------
		$webdriver->get('https://www.tradingview.com/u/'.$author['name'].'/');
		$webdriver->executeScript('$(".js-tv-expected-language.tv-expected-language").remove();', array());

		// Oturum durumunu kontrol et.
		$session_status	= wait_until(function() use ($webdriver, $bot) {
			$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
			if ($element) {
				if (trim($element->getText()) == $bot['username'])
					return 1;
				return -1;
			}
		});
		//---------------------------------------------------------------------------------------------
		// Kullanıcı girişi yapılması gerekiyorsa;
		//---------------------------------------------------------------------------------------------
		if ($session_status == -1) {
			write_log_to_file('login required'."\n");
		
			$signin_link = $webdriver->findElementsBy(LocatorStrategy::cssSelector, 'a[href="#signin"].tv-header__link--signin');
			$signin_link[0]->click();
			sleep(1);
		
			$signin_submit_button	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form button[type="submit"]');
				if ($element and $element->isDisplayed())
					return $element;
			});
		
			// $signin_username	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="username"]');
			// $signin_username->click();
			// $signin_username->sendKeys(str_split($bot['email']));
			// sleep(1);
			
			// $signin_password	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="password"]');
			// $signin_password->click();
			// $signin_password->sendKeys(str_split($bot['password']));
			// sleep(1);

			$scriptregister_email_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][0].value="'.$bot['email'].'"';
		    $webdriver->executeScript($scriptregister_email_input, array());

			sleep(1);
			
			$scriptregister_password_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][1].value="'.$bot['password'].'"';
		    $webdriver->executeScript($scriptregister_password_input, array());

			sleep(1);

		
			$signin_submit_button->click();
		
			// Kullanıcı adı görünene kadar bekle.
			$session_status	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
				$error		= $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog__error--dark');

				if(sizeof($error))
					return -1;

				if ($element and trim($element->getText()) == $bot['username'])
					return 1;
			});

			if ($session_status == -1) {
				//botu sil
				$dbo->delete('bots', $bot['id']);
				add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
				
				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				sleep(1);
				continue;
			}
			
			write_log_to_file('Giris yapildi'."\n");

		}

		$webdriver->executeScript('$(".layout__area--right").remove();', array());

		//---------------------------------------------------------------------------------------------

		$follow_button	= wait_until(function() use ($webdriver, $bot) {
			return $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-profile__actions .tv-button.js-follow-user');
		});

		// Zaten takip ediliyor mu kontrol et.
		if (!sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-profile__actions .tv-button.js-follow-user.js-follow-user--followed'))) {
			$follow_button->click();
			sleep(1);
		}

		//---------------------------------------------------------------------------------------------
		// Bot banlanmış mı kontrol et.
		//---------------------------------------------------------------------------------------------
		sleep(1);
		if (sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog a[href="/house-rules/"]'))) {
			echo 'Bot permanently banned.'."\n";
			//botu sil
			$dbo->delete('bots', $bot['id']);
			add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
			$webdriver->closeWindow();
			sleep(2);
			$webdriver->close();
			sleep(1);
			echo 'Bot removed.'."\n";
			continue;
		}
		//---------------------------------------------------------------------------------------------

		$dbo->insert('author_follows', array('author_id'=>$author['id'], 'bot_id'=>$bot['id'], 'created_time'=>date('Y-m-d H:i:s')));
		$dbo->update('authors', array('current_follows'=>++$author['current_follows']), $author['id']);

		add_activity_log('Bot <b>'.$bot['username'].'</b> following <b>'.$author['name'].'</b>', 'author_follow');

		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
		sleep(1);

	//-------------------------------------------------------------------------------------------------------------
	// UNFOLLOW AUTHOR
	//-------------------------------------------------------------------------------------------------------------
	} elseif ($task['type'] == 'unfollow_author') {

		// Yazarı veritabanından çek.
		$author		= $dbo->seek('authors', $task['author_id']);

		// Yazar bulunamadıysa silinmiş olabilir.
		if (!$author)
			goto end_of_task;

		// Bu yazarı takip eden botlardan birini rastgele çek.
		$bot	= $dbo->execute('SELECT bot.*, proxy.domain, proxy.http_port, proxy.country FROM bots AS bot LEFT JOIN proxies AS proxy ON proxy.id=bot.proxy_id WHERE bot.status=\'active\' AND bot.id IN(SELECT bot_id FROM author_follows WHERE author_id='.$author['id'].') ORDER BY rand() LIMIT 1');
		$bot	= $bot[0];

		//---------------------------------------------------------------------------------------------
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
		//---------------------------------------------------------------------------------------------
		$webdriver->get('https://www.tradingview.com/u/'.$author['name'].'/');
		$webdriver->executeScript('$(".js-tv-expected-language.tv-expected-language").remove();', array());

		// Oturum durumunu kontrol et.
		$session_status	= wait_until(function() use ($webdriver, $bot) {
			$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
			if ($element) {
				if (trim($element->getText()) == $bot['username'])
					return 1;
				return -1;
			}
		});
		//---------------------------------------------------------------------------------------------
		// Kullanıcı girişi yapılması gerekiyorsa;
		//---------------------------------------------------------------------------------------------
		if ($session_status == -1) {
			echo "login required\n";
		
			$signin_link = $webdriver->findElementsBy(LocatorStrategy::cssSelector, 'a[href="#signin"].tv-header__link--signin');
			$signin_link[0]->click();
			sleep(1);
		
			$signin_submit_button	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form button[type="submit"]');
				if ($element and $element->isDisplayed())
					return $element;
			});
		
			// $signin_username	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="username"]');
			// $signin_username->click();
			// $signin_username->sendKeys(str_split($bot['email']));
			// sleep(1);
			
			// $signin_password	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="password"]');
			// $signin_password->click();
			// $signin_password->sendKeys(str_split($bot['password']));
			// sleep(1);

			$scriptregister_email_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][0].value="'.$bot['email'].'"';
		    $webdriver->executeScript($scriptregister_email_input, array());

			sleep(1);
			
			$scriptregister_password_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][1].value="'.$bot['password'].'"';
		    $webdriver->executeScript($scriptregister_password_input, array());

			sleep(1);

		
			$signin_submit_button->click();
		
			// Kullanıcı adı görünene kadar bekle.
			$session_status	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
				$error		= $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog__error--dark');

				if(sizeof($error))
					return -1;

				if ($element and trim($element->getText()) == $bot['username'])
					return 1;
			});

			if ($session_status == -1) {
				//botu sil
				$dbo->delete('bots', $bot['id']);
				add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
				
				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				sleep(1);
				continue;
			}
			
			echo "Giris yapildi.\n";
		}

		$webdriver->executeScript('$(".layout__area--right").remove();', array());

		//---------------------------------------------------------------------------------------------

		$follow_button	= wait_until(function() use ($webdriver, $bot) {
			return $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-profile__actions .tv-button.js-follow-user');
		});

		// Zaten takip ediliyor mu kontrol et.
		if (sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-profile__actions .tv-button.js-follow-user.js-follow-user--followed'))) {
			$follow_button->click();
			sleep(1);
		}

		//---------------------------------------------------------------------------------------------
		// Bot banlanmış mı kontrol et.
		//---------------------------------------------------------------------------------------------
		sleep(1);
		if (sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog a[href="/house-rules/"]'))) {
			write_log_to_file('Bot permanently banned'."\n");
			//botu sil
			$dbo->delete('bots', $bot['id']);
			add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
			$webdriver->closeWindow();
			sleep(2);
			$webdriver->close();
			sleep(1);
			write_log_to_file('Bot removed.'."\n");
			continue;
		}
		//---------------------------------------------------------------------------------------------

		$dbo->execute('DELETE FROM author_follows WHERE author_id='.$author['id'].' AND bot_id='.$bot['id']);
		$dbo->update('authors', array('current_follows'=>--$author['current_follows']), $author['id']);

		add_activity_log('Bot <b>'.$bot['username'].'</b> unfollow <b>'.$author['name'].'</b> .', 'author_unfollow');



	//-------------------------------------------------------------------------------------------------------------
	// LIKE ARTICLE
	//-------------------------------------------------------------------------------------------------------------
	} elseif ($task['type'] == 'like_article') {

		$article	= $dbo->seek('articles', $task['article_id']);
		$author		= $dbo->seek('authors', $task['author_id']);

		// Makale veya yazar bulunamadıysa silinmiş olabilir.
		if (!$article or !$author)
			goto end_of_task;

		// Bu makaleye hali hazırda beğeni vermemiş botlardan birini rasgele çek.
		$bot	= $dbo->execute('SELECT * FROM bots WHERE status=\'active\' AND isBotClean=1 AND id NOT IN(SELECT bot_id FROM article_likes WHERE article_id='.$article['id'].') ORDER BY rand() LIMIT 1');
		$bot	= $bot[0];

		// Botun proxy kaydını çek.
		$proxy	= $dbo->execute('SELECT * FROM proxies WHERE id='.$bot['proxy_id'].' LIMIT 1');
		$proxy	= $proxy[0];

		$bot['domain']		= $proxy['domain'];
		$bot['http_port']	= $proxy['http_port'];
		$bot['country']		= $proxy['country'];

		//---------------------------------------------------------------------------------------------
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

		$webdriver->get('https://www.tradingview.com'.$article['link']);
		//$webdriver->get('https://www.tradingview.com/u/ICmarkets/');
		$webdriver->executeScript('$(".js-tv-expected-language.tv-expected-language").remove();', array());

		// Oturum durumunu kontrol et.
		$session_status	= wait_until(function() use ($webdriver, $bot) {
			$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
			if ($element) {
				if (trim($element->getText()) == $bot['username'])
					return 1;
				return -1;
			}
		});

		// Kullanıcı girişi yapılması gerekiyorsa;
		if ($session_status == -1) {
			write_log_to_file('login required'."\n");
		
			$signin_link = $webdriver->findElementsBy(LocatorStrategy::cssSelector, 'a[href="#signin"].tv-header__link--signin');
			$signin_link[0]->click();
			sleep(1);
		
			$signin_submit_button	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form button[type="submit"]');
				if ($element and $element->isDisplayed())
					return $element;
			});
		
			// $signin_username	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="username"]');
			// $signin_username->click();
			// $signin_username->sendKeys(str_split($bot['email']));

			$scriptregister_email_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][0].value="'.$bot['email'].'"';
		    $webdriver->executeScript($scriptregister_email_input, array());

			sleep(1);
			
			// $signin_password	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="password"]');
			// $signin_password->click();
			// $signin_password->sendKeys(str_split($bot['password']));

			$scriptregister_password_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][1].value="'.$bot['password'].'"';
		    $webdriver->executeScript($scriptregister_password_input, array());

			sleep(1);
		
			$signin_submit_button->click();
		
			// Kullanıcı adı görünene kadar bekle.
			$session_status	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
				$error		= $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog__error--dark');

				if(sizeof($error))
					return -1;

				if ($element and trim($element->getText()) == $bot['username'])
					return 1;
			});

			if($session_status == -1){
				//botu sil
				$dbo->delete('bots', $bot['id']);
				add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				sleep(1);
				continue;
			}
		
			write_log_to_file('Giris yapildi.'."\n");
		}

		$webdriver->executeScript('$(".layout__area--right").remove();', array());
		//---------------------------------------------------------------------------------------------

		write_log_to_file('Bot sayfaya girdi: '.$bot['username']."\n");

		$dbo->update('articles', array('current_views'=>++$article['current_views']), $article['id']);
		write_log_to_file('Gosterim sayisi artirildi'."\n");

		$like_button			= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'span[data-name="agrees"]');
		$like_button_position	= $like_button->getLocation();

		$webdriver->executeScript('window.scrollTo(0, '.$like_button_position->y.'-50);', array());
		unset($like_button_position);

		sleep(1);
		$like_button->click();

		//---------------------------------------------------------------------------------------------
		// Bot banlanmış mı kontrol et.
		//---------------------------------------------------------------------------------------------
		sleep(1);
		if (sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog a[href="/house-rules/"]'))) {
			write_log_to_file('Bot permanently banned.'."\n");
			//botu sil
			$dbo->delete('bots', $bot['id']);
			add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
			$webdriver->closeWindow();
			sleep(2);
			$webdriver->close();
			sleep(1);
			write_log_to_file('Bot removed.'."\n");
			continue;
		}
		//---------------------------------------------------------------------------------------------

		$dbo->begin();
		$dbo->insert('article_likes', array('bot_id'=>$bot['id'], 'author_id'=>$article['author_id'], 'article_id'=>$article['id'], 'created_time'=>date('Y-m-d H:i:s')));
		$dbo->update('articles', array('current_likes'=>++$article['current_likes']), $article['id']);
		$dbo->commit();

		write_log_to_file('Bot begendi.'."\n");

		add_activity_log('Bot <b>'.$bot['username'].'</b> liked an idea for <b> '.$author['name'].'</b>', 'article_like');

		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
		sleep(1);

		sleep(1);


	//-------------------------------------------------------------------------------------------------------------
	// COMMENT ARTICLE
	//-------------------------------------------------------------------------------------------------------------
	} elseif ($task['type'] == 'comment_article') {

		$article	= $dbo->seek('articles', $task['article_id']);
		$author		= $dbo->seek('authors', $task['author_id']);

		// Makale veya yazar bulunamadıysa silinmiş olabilir.
		if (!$article or !$author)
			goto end_of_task;

		// Bu makaleye hali hazırda yorum vermemiş botlardan birini rasgele çek.
		$bot	= $dbo->execute('SELECT * FROM bots WHERE status=\'active\' AND id NOT IN(SELECT bot_id FROM article_comments WHERE article_id='.$article['id'].') ORDER BY rand() LIMIT 1');
		$bot	= $bot[0];

		// Botun proxy kaydını çek.
		$proxy	= $dbo->execute('SELECT * FROM proxies WHERE id='.$bot['proxy_id'].' LIMIT 1');
		$proxy	= $proxy[0];

		$bot['domain']		= $proxy['domain'];
		$bot['http_port']	= $proxy['http_port'];
		$bot['country']		= $proxy['country'];

		//---------------------------------------------------------------------------------------------
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

		$webdriver->get('https://www.tradingview.com'.$article['link']);
		$webdriver->executeScript('$(".js-tv-expected-language.tv-expected-language").remove();', array());

		// Oturum durumunu kontrol et.
		$session_status	= wait_until(function() use ($webdriver, $bot) {
			$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
			if ($element) {
				if (trim($element->getText()) == $bot['username'])
					return 1;
				return -1;
			}
		});

		// Kullanıcı girişi yapılması gerekiyorsa;
		if ($session_status == -1) {
			write_log_to_file('login required'."\n");
		
			$signin_link = $webdriver->findElementsBy(LocatorStrategy::cssSelector, 'a[href="#signin"].tv-header__link--signin');
			$signin_link[0]->click();
			sleep(1);
		
			$signin_submit_button	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form button[type="submit"]');
				if ($element and $element->isDisplayed())
					return $element;
			});
		
			// $signin_username	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="username"]');
			// $signin_username->click();
			// $signin_username->sendKeys(str_split($bot['email']));
			// sleep(1);
			
			// $signin_password	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="password"]');
			// $signin_password->click();
			// $signin_password->sendKeys(str_split($bot['password']));
			// sleep(1);
		
			$scriptregister_email_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][0].value="'.$bot['email'].'"';
		    $webdriver->executeScript($scriptregister_email_input, array());

			sleep(1);
			
			$scriptregister_password_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][1].value="'.$bot['password'].'"';
		    $webdriver->executeScript($scriptregister_password_input, array());

			sleep(1);

			$signin_submit_button->click();
		
			// Kullanıcı adı görünene kadar bekle.
			$session_status	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
				$error		= $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog__error--dark');

				if(sizeof($error))
					return -1;

				if ($element and trim($element->getText()) == $bot['username'])
					return 1;
			});

			if ($session_status == -1) {
				//botu sil
				$dbo->delete('bots', $bot['id']);
				add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				sleep(1);
				continue;
			}
		
			write_log_to_file('Giris yapildi'."\n");
		}

		$webdriver->executeScript('$(".layout__area--right").remove();', array());
		//---------------------------------------------------------------------------------------------

		write_log_to_file('Bot sayfaya girdi: '.$bot['username']."\n");

		$dbo->update('articles', array('current_views'=>++$article['current_views']), $article['id']);
			write_log_to_file('Gosterim sayisi artirildi'."\n");

		//---------------------------------------------------------------------------------------------
		// Yorum şablonlarından birini rasgele çek.
		//---------------------------------------------------------------------------------------------
		while (true) {
			$comment_template	= $dbo->execute('SELECT * FROM comment_templates WHERE parent_id IS NULL ORDER BY rand() LIMIT 1');
			$comment_template	= $comment_template[0];
			$comment			= $comment_template['template'];

			// Yorum şablon metninin içinde "position" değişkeni varsa, ancak makalede position yoksa, başka bir yorum şablonu seçilsin.
			if (strpos($comment, '[position]') !== false and !strlen($article['position'])) {
				continue;

			// Aksi halde bu yorum şablonu kullanılsın.
			} else {
				break;
			}
		}

		//---------------------------------------------------------------------------------------------
		// Yorum şablon değişkenlerini işle.
		//---------------------------------------------------------------------------------------------
		$comment	= str_replace('[author]',			$author['name'],		$comment);
		$comment	= str_replace('[bot_username]',		$bot['username'],		$comment);
		$comment	= str_replace('[bot_country]',		$bot['country'],		$comment);
		$comment	= str_replace('[bot_first_name]',	$bot['first_name'],		$comment);
		$comment	= str_replace('[bot_last_name]',	$bot['last_name'],		$comment);
		$comment	= str_replace('[currency]',			$article['currency'],	$comment);
		$comment	= str_replace('[position]',			$article['position'],	$comment);
		$comment	= preg_replace('/(\[.*\])/U',		'',						$comment);

		//---------------------------------------------------------------------------------------------
		// Yorumu gönder.
		//---------------------------------------------------------------------------------------------
		$comment_textarea	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'textarea[name="comment"]');
		$webdriver->moveTo($comment_textarea);
		sleep(1);

		$comment_textarea->click();
		$comment_textarea->sendKeys(str_split($comment));
		sleep(1);

		$comment_submit_button	= $webdriver->findElementBy(LocatorStrategy::cssSelector, 'button.tv-chart-comment__action.js-chart-comment-form__submit');
		$comment_submit_button->click();
		sleep(2);

		//---------------------------------------------------------------------------------------------
		// Bot banlanmış mı kontrol et.
		//---------------------------------------------------------------------------------------------
		sleep(1);
		if (sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog a[href="/house-rules/"]'))) {
			write_log_to_file('Bot permanently banned.'."\n");
			//botu sil
			$dbo->delete('bots', $bot['id']);
			add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
			$webdriver->closeWindow();
			sleep(2);
			$webdriver->close();
			sleep(1);
			write_log_to_file('Bot removed.'."\n");
			continue;
		}
		//---------------------------------------------------------------------------------------------


		$dbo->begin();
		$dbo->insert('article_comments', array('bot_id'=>$bot['id'], 'author_id'=>$article['author_id'], 'article_id'=>$article['id'], 'comment'=>$comment, 'created_time'=>date('Y-m-d H:i:s')));
		$dbo->update('articles', array('current_comments'=>++$article['current_comments']), $article['id']);
		$dbo->commit();

		// Kullanılmış olan yorum şablonunun alt yorumları varsa;
		if ($dbo->count('comment_templates', array('parent_id'=>$comment_template['id']))) {

			// Alt yorum işlemi için ekstra görev bilgileri oluştur.
			$extra	= json_encode(array
			(
				'target_bot_id'					=> $bot['id'],
				'target_bot_username'			=> $bot['username'],
				'parent_comment_template_id'	=> $comment_template['id']
			));

			// Alt yorum görevi oluştur. (En erken 10 dakika, en geç 4 saat içinde)
			$dbo->insert('tasks', array('type'=>'reply_comment', 'author_id'=>$author['id'], 'article_id'=>$article['id'], 'extra'=>$extra, 'due'=>time()+rand(600, 86400/6)));

			unset($extra);
		}


		write_log_to_file('Bot yorum birakti.'."\n");

		add_activity_log('Bot <b>'.$bot['username'].'</b> commented to an idea for <b>'.$author['name'].'</b>', 'article_comment');

		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
		sleep(1);


	//-------------------------------------------------------------------------------------------------------------
	// VISIT ARTICLE
	//-------------------------------------------------------------------------------------------------------------
	} elseif ($task['type'] == 'visit_article') {



		$article	= $dbo->seek('articles', $task['article_id']);
		$author		= $dbo->seek('authors', $task['author_id']);

		// Makale veya yazar bulunamadıysa silinmiş olabilir.
		if (!$article or !$author)
			goto end_of_task;


		// Bu makaleye hali hazırda yorum vermemiş botlardan birini rasgele çek.
		$bot	= $dbo->execute('SELECT * FROM bots WHERE status=\'active\' AND id NOT IN(SELECT bot_id FROM article_comments WHERE article_id='.$article['id'].') ORDER BY rand() LIMIT 1');
		$bot	= $bot[0];

		// Rastgele bir proxy çek.
		$proxy		= $dbo->execute('SELECT * FROM proxies ORDER BY rand() LIMIT 1');
		$proxy		= $proxy[0];

		write_log_to_file('Visitling article...'."\n");
		write_log_to_file("	Link: ".$article['link']."\n");
		write_log_to_file("	Proxy: ".$proxy['domain']."\n");

		ApiRequestCookie('https://www.tradingview.com/'.$article['link'].'/', 'https://www.tradingview.com/', array(), null, array('domain'=>$proxy['domain'], 'username'=>$config['proxy']['username'], 'password'=>$config['proxy']['password'], 'port'=>$proxy['http_port']));

		$dbo->update('articles', array('current_views'=>++$article['current_views']), $article['id']);

		write_log_to_file('	OK'."\n");

		write_log_to_file('Bot yorum birakti.'."\n");
		add_activity_log('Bot <b>'.$bot['username'].'</b> visited to an article of <b>'.$author['name'].'</b>', 'article_visit');

	//-------------------------------------------------------------------------------------------------------------
	// FOLLOW BOT
	//-------------------------------------------------------------------------------------------------------------
	} elseif ($task['type'] == 'follow_bot') {

		// Takip edilecek botu veritabanından çek.
		$author_bot		= $dbo->seek('bots', $task['extra']['target_bot_id']);

		// Hedef bot bulunamadıysa;
		if (!$author_bot)
			goto end_of_task;

		// Bu botu hali hazırda takip etmeyen botlardan birini rastgele çek. (Kendisi hariç)
		$bot	= $dbo->execute('SELECT bot.*, proxy.domain, proxy.http_port, proxy.country FROM bots AS bot LEFT JOIN proxies AS proxy ON proxy.id=bot.proxy_id WHERE bot.id !='.$author_bot['id'].' AND bot.status=\'active\' AND bot.id NOT IN(SELECT follower_bot_id FROM bot_follows WHERE following_bot_id='.$author_bot['id'].') ORDER BY rand() LIMIT 1');
		$bot	= $bot[0];

		//---------------------------------------------------------------------------------------------
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
		//---------------------------------------------------------------------------------------------
		$webdriver->get('https://www.tradingview.com/u/'.$author_bot['username'].'/');
		$webdriver->executeScript('$(".js-tv-expected-language.tv-expected-language").remove();', array());

		// Oturum durumunu kontrol et.
		$session_status	= wait_until(function() use ($webdriver, $bot) {
			$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
			if ($element) {
				if (trim($element->getText()) == $bot['username'])
					return 1;
				return -1;
			}
		});

		//---------------------------------------------------------------------------------------------
		// Kullanıcı girişi yapılması gerekiyorsa;
		//---------------------------------------------------------------------------------------------
		if ($session_status == -1) {
			write_log_to_file('login required'."\n");
		
			$signin_link = $webdriver->findElementsBy(LocatorStrategy::cssSelector, 'a[href="#signin"].tv-header__link--signin');
			$signin_link[0]->click();
			sleep(1);
		
			$signin_submit_button	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form button[type="submit"]');
				if ($element and $element->isDisplayed())
					return $element;
			});
		
			// $signin_username	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="username"]');
			// $signin_username->click();
			// $signin_username->sendKeys(str_split($bot['email']));
			// sleep(1);
			
			// $signin_password	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="password"]');
			// $signin_password->click();
			// $signin_password->sendKeys(str_split($bot['password']));
			// sleep(1);

			$scriptregister_email_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][0].value="'.$bot['email'].'"';
		    $webdriver->executeScript($scriptregister_email_input, array());

			sleep(1);
			
			$scriptregister_password_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][1].value="'.$bot['password'].'"';
		    $webdriver->executeScript($scriptregister_password_input, array());

			sleep(1);

		
			$signin_submit_button->click();
		
			// Kullanıcı adı görünene kadar bekle.
			$session_status	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
				$error		= $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog__error--dark');

				if(sizeof($error))
					return -1;

				if ($element and trim($element->getText()) == $bot['username'])
					return 1;
			});

			if ($session_status == -1) {
				//botu sil
				$dbo->delete('bots', $bot['id']);
				add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
				
				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				sleep(1);
				continue;
			}
			
			write_log_to_file('Giris yapildi'."\n");
		}

		$webdriver->executeScript('$(".layout__area--right").remove();', array());

		//---------------------------------------------------------------------------------------------

		$follow_button	= wait_until(function() use ($webdriver, $bot) {
			return $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-profile__actions .tv-button.js-follow-user');
		});

		// Zaten takip ediliyor mu kontrol et.
		if (!sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-profile__actions .tv-button.js-follow-user.js-follow-user--followed'))) {
			$follow_button->click();
			sleep(1);
		}

		//---------------------------------------------------------------------------------------------
		// Bot banlanmış mı kontrol et.
		//---------------------------------------------------------------------------------------------
		sleep(1);
		if (sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog a[href="/house-rules/"]'))) {
			write_log_to_file('Bot permanently banned.'."\n");
			//botu sil
			$dbo->delete('bots', $bot['id']);
			add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
			$webdriver->closeWindow();
			sleep(2);
			$webdriver->close();
			sleep(1);
			write_log_to_file('Bot removed.'."\n");
			continue;
		}
		//---------------------------------------------------------------------------------------------

		$dbo->insert('bot_follows', array('following_bot_id'=>$author_bot['id'], 'follower_bot_id'=>$bot['id'], 'created_time'=>date('Y-m-d H:i:s')));
		$dbo->update('bots', array('current_followers'=>++$author_bot['current_followers']), $author_bot['id']);

		add_activity_log('Bot <b>'.$bot['username'].'</b> following other bot <b>'.$author_bot['username'].'</b>', 'bot_follow');

		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
		sleep(1);


	//-------------------------------------------------------------------------------------------------------------
	// REPLY COMMENT
	//-------------------------------------------------------------------------------------------------------------
	} elseif ($task['type'] == 'reply_comment') {

		$article	= $dbo->seek('articles', $task['article_id']);
		$author		= $dbo->seek('authors', $task['author_id']);

		// Makale veya yazar bulunamadıysa silinmiş olabilir.
		if (!$article or !$author)
			goto end_of_task;

		// Bu makaleye hali hazırda yorum vermemiş botlardan birini rasgele çek.
		$bot	= $dbo->execute('SELECT * FROM bots WHERE status=\'active\' AND id NOT IN(SELECT bot_id FROM article_comments WHERE article_id='.$article['id'].') ORDER BY rand() LIMIT 1');
		$bot	= $bot[0];

		// Botun proxy kaydını çek.
		$proxy	= $dbo->execute('SELECT * FROM proxies WHERE id='.$bot['proxy_id'].' LIMIT 1');
		$proxy	= $proxy[0];

		$bot['domain']		= $proxy['domain'];
		$bot['http_port']	= $proxy['http_port'];
		$bot['country']		= $proxy['country'];

		// Daha önce kullanılan yorum şablonuna bağlı alt yorum şablonlarından birini rasgele çek.
		$comment_template	= $dbo->execute('SELECT * FROM comment_templates WHERE parent_id='.$task['extra']['parent_comment_template_id'].' ORDER BY rand() LIMIT 1');

		// Eğer alt yorum şablonu bulunamamışsa görev iptal.
		if (!sizeof($comment_template))
			goto end_of_task;

		$comment_template	= $comment_template[0];
		$comment			= $comment_template['template'];

		//---------------------------------------------------------------------------------------------
		// Yorum şablon değişkenlerini işle.
		//---------------------------------------------------------------------------------------------
		$comment	= str_replace('[author]',			$author['name'],		$comment);
		$comment	= str_replace('[bot_username]',		$bot['username'],		$comment);
		$comment	= str_replace('[bot_country]',		$bot['country'],		$comment);
		$comment	= str_replace('[bot_first_name]',	$bot['first_name'],		$comment);
		$comment	= str_replace('[bot_last_name]',	$bot['last_name'],		$comment);
		$comment	= str_replace('[currency]',			$article['currency'],	$comment);
		$comment	= str_replace('[position]',			$article['position'],	$comment);
		$comment	= preg_replace('/(\[.*\])/U',		'',						$comment);

		//---------------------------------------------------------------------------------------------
		// Makale sayfasına git ve gerekiyorsa oturum açma işlemlerini yap.
		//---------------------------------------------------------------------------------------------
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

		$webdriver->get('https://www.tradingview.com'.$article['link']);
		$webdriver->executeScript('$(".js-tv-expected-language.tv-expected-language").remove();', array());

		// Oturum durumunu kontrol et.
		$session_status	= wait_until(function() use ($webdriver, $bot) {
			$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
			if ($element) {
				if (trim($element->getText()) == $bot['username'])
					return 1;
				return -1;
			}
		});

		// Kullanıcı girişi yapılması gerekiyorsa;
		if ($session_status == -1) {
			write_log_to_file('login required'."\n");
		
			$signin_link = $webdriver->findElementsBy(LocatorStrategy::cssSelector, 'a[href="#signin"].tv-header__link--signin');
			$signin_link[0]->click();
			sleep(1);
		
			$signin_submit_button	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form button[type="submit"]');
				if ($element and $element->isDisplayed())
					return $element;
			});
		
			// $signin_username	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="username"]');
			// $signin_username->click();
			// $signin_username->sendKeys(str_split($bot['email']));
			// sleep(1);
			
			// $signin_password	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="password"]');
			// $signin_password->click();
			// $signin_password->sendKeys(str_split($bot['password']));
			// sleep(1);

			$scriptregister_email_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][0].value="'.$bot['email'].'"';
		    $webdriver->executeScript($scriptregister_email_input, array());

			sleep(1);
			
			$scriptregister_password_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][1].value="'.$bot['password'].'"';
		    $webdriver->executeScript($scriptregister_password_input, array());

			sleep(1);

		
			$signin_submit_button->click();
		
			// Kullanıcı adı görünene kadar bekle.
			$session_status	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
				$error		= $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog__error--dark');

				if(sizeof($error))
					return -1;

				if ($element and trim($element->getText()) == $bot['username'])
					return 1;
			});

			if($session_status == -1){
				//botu sil
				$dbo->delete('bots', $bot['id']);
				add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				sleep(1);
				continue;
			}
		
			write_log_to_file('Giris yapildi'."\n");
		}

		$webdriver->executeScript('$(".layout__area--right").remove();', array());
		//---------------------------------------------------------------------------------------------

		write_log_to_file('Bot sayfaya girdi: '.$bot['username']."\n");

		$dbo->update('articles', array('current_views'=>++$article['current_views']), $article['id']);
		write_log_to_file('Gosterim sayisi artirildi'."\n");

		//---------------------------------------------------------------------------------------------
		// Yorumu gönder.
		//---------------------------------------------------------------------------------------------

		write_log_to_file('Cevap verilecek bot yorumu araniyor: '.$task['extra']['target_bot_username'].'...'."\n");


		$comment_blocks_count	= 0;
		while (true) {
			$comment_blocks = $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-chart-comment');

			// Aradığımız bot yorumu bulunduysa;
			if (sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, 'a[data-username="'.$task['extra']['target_bot_username'].'"]'))) {
		
				write_log_to_file('Hedef bot yorumu bulundu: '.$task['extra']['target_bot_username']."\n");
		
				$reply_button			= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-chart-comment > a[data-username="'.$task['extra']['target_bot_username'].'"] + .tv-chart-comment__wrap > .tv-chart-comment__controls > .tv-chart-comment__control');
				$reply_button_position	= $reply_button->getLocation();
		
				$webdriver->executeScript('window.scrollTo(0, '.$reply_button_position->y.'-50);', array());
				$reply_button->click();
				sleep(1);
		
				$reply_textarea	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-chart-comment > a[data-username="'.$task['extra']['target_bot_username'].'"] + .tv-chart-comment__wrap + form textarea.tv-chart-comment-form__textarea');
				$reply_textarea->click();
				$reply_textarea->sendKeys(str_split($comment));
				sleep(1);
		
				$post_comment_button	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-chart-comment > a[data-username="'.$task['extra']['target_bot_username'].'"] + .tv-chart-comment__wrap + form button.tv-chart-comment__action');
				$post_comment_button->click();
				sleep(3);

				unset($reply_button, $reply_button_position, $reply_textarea, $post_comment_button);

				//---------------------------------------------------------------------------------------------
				// Bot banlanmış mı kontrol et.
				//---------------------------------------------------------------------------------------------
				sleep(1);
				if (sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog a[href="/house-rules/"]'))) {
					write_log_to_file('Bot permanently banned.'."\n");
					//botu sil
					$dbo->delete('bots', $bot['id']);
					add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
					$webdriver->closeWindow();
					sleep(2);
					$webdriver->close();
					sleep(1);
					write_log_to_file('Bot removed.'."\n");
					continue 2;
				}
				//---------------------------------------------------------------------------------------------

				$dbo->insert('article_comments', array('bot_id'=>$bot['id'], 'author_id'=>$article['author_id'], 'article_id'=>$article['id'], 'comment'=>$comment, 'is_reply'=>1, 'created_time'=>date('Y-m-d H:i:s')));

				// Kullanılmış olan yorum şablonunun 3. seviye alt yorum şablonları varsa;
				if ($dbo->count('comment_templates', array('parent_id'=>$comment_template['id']))) {

					// Alt yorum işlemi için ekstra görev bilgileri oluştur.
					$extra	= json_encode(array
					(
						'target_bot_id'					=> $bot['id'],
						'target_bot_username'			=> $bot['username'],
						'parent_comment_template_id'	=> $comment_template['id'],
						'first_bot_id'					=> $extra['target_bot_id'],
						'first_bot_username'			=> $extra['target_bot_username']
					));

					// 3. seviye alt yorum görevi oluştur. (En erken 10 dakika, en geç 4 saat içinde)
					$dbo->insert('tasks', array('type'=>'rereply_comment', 'author_id'=>$author['id'], 'article_id'=>$article['id'], 'extra'=>$extra, 'due'=>time()+rand(600, 86400/6)));

					unset($extra);
				}
				//-------------------------------------------------------------------------------------------------------------------------

				write_log_to_file('Bot, diger botun yorumuna cevap verdi'."\n");

				add_activity_log('Bot <b>'.$bot['username'].'</b> replied to a comment.', 'reply_comment');

				break;
			}
		
			if ($comment_blocks_count == sizeof($comment_blocks)) {
				write_log_to_file('Hedef bot yorumu bulunamadi'."\n");

				break;
			}

			$comment_blocks_count	= sizeof($comment_blocks);

			// Sayfayı aşağıya kaydır, yeni yorumlar yüklensin.
			$webdriver->executeScript('window.scrollTo(0,document.body.scrollHeight);', array());

			sleep(1);
		}
		unset($comment_blocks_count, $comment_blocks);

		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
		sleep(1);

	//-------------------------------------------------------------------------------------------------------------
	// REREPLY COMMENT
	//-------------------------------------------------------------------------------------------------------------
	} elseif ($task['type'] == 'rereply_comment') {

		$article	= $dbo->seek('articles', $task['article_id']);
		$author		= $dbo->seek('authors', $task['author_id']);

		// Makale veya yazar bulunamadıysa silinmiş olabilir.
		if (!$article or !$author)
			goto end_of_task;

		// Daha önce kullanılan yorum şablonuna bağlı alt yorum şablonlarından birini rasgele çek.
		$comment_template	= $dbo->execute('SELECT * FROM comment_templates WHERE parent_id='.$task['extra']['parent_comment_template_id'].' ORDER BY rand() LIMIT 1');

		// Eğer alt yorum şablonu bulunamamışsa görev iptal.
		if (!sizeof($comment_template))
			goto end_of_task;

		$comment_template	= $comment_template[0];

		// Eğer cevabı herhangi bir botun değil de, ilk yorum yapan botun vermesi gerekiyorsa;
		if ($comment_template['must_be_first_bot']) {

			// İlk yorum yapan botu veritabanından çek.
			$bot	= $dbo->execute('SELECT * FROM bots WHERE status=\'active\' AND id='.$task['extra']['first_bot_id'].' LIMIT 1');

		// Aksi halde bu makaleye hali hazırda yorum vermemiş botlardan birini rasgele çek.
		} else {
			$bot	= $dbo->execute('SELECT * FROM bots WHERE status=\'active\' AND id NOT IN(SELECT bot_id FROM article_comments WHERE article_id='.$article['id'].') ORDER BY rand() LIMIT 1');
		}

		// Bot herhangi bir sebeple bulunamamışsa görev iptal.
		if (!sizeof($bot))
	    	goto end_of_task;


		$bot	= $bot[0];

		// Botun proxy kaydını çek.
		$proxy	= $dbo->execute('SELECT * FROM proxies WHERE id='.$bot['proxy_id'].' LIMIT 1');
		$proxy	= $proxy[0];

		$bot['domain']		= $proxy['domain'];
		$bot['http_port']	= $proxy['http_port'];
		$bot['country']		= $proxy['country'];

		//---------------------------------------------------------------------------------------------
		// Yorum şablon değişkenlerini işle.
		//---------------------------------------------------------------------------------------------
		$comment	= $comment_template['template'];
		$comment	= str_replace('[author]',			$author['name'],		$comment);
		$comment	= str_replace('[bot_username]',		$bot['username'],		$comment);
		$comment	= str_replace('[bot_country]',		$bot['country'],		$comment);
		$comment	= str_replace('[bot_first_name]',	$bot['first_name'],		$comment);
		$comment	= str_replace('[bot_last_name]',	$bot['last_name'],		$comment);
		$comment	= str_replace('[currency]',			$article['currency'],	$comment);
		$comment	= str_replace('[position]',			$article['position'],	$comment);
		$comment	= preg_replace('/(\[.*\])/U',		'',						$comment);

		//---------------------------------------------------------------------------------------------
		// Makale sayfasına git ve gerekiyorsa oturum açma işlemlerini yap.
		//---------------------------------------------------------------------------------------------
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

		$webdriver->get('https://www.tradingview.com'.$article['link']);
		$webdriver->executeScript('$(".js-tv-expected-language.tv-expected-language").remove();', array());

		// Oturum durumunu kontrol et.
		$session_status	= wait_until(function() use ($webdriver, $bot) {
			$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
			if ($element) {
				if (trim($element->getText()) == $bot['username'])
					return 1;
				return -1;
			}
		});

		// Kullanıcı girişi yapılması gerekiyorsa;
		if ($session_status == -1) {
			write_log_to_file('login required'."\n");
		
			$signin_link = $webdriver->findElementsBy(LocatorStrategy::cssSelector, 'a[href="#signin"].tv-header__link--signin');
			$signin_link[0]->click();
			sleep(1);
		
			$signin_submit_button	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form button[type="submit"]');
				if ($element and $element->isDisplayed())
					return $element;
			});
		
			// $signin_username	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="username"]');
			// $signin_username->click();
			// $signin_username->sendKeys(str_split($bot['email']));
			// sleep(1);
			
			// $signin_password	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '#signin-form input[name="password"]');
			// $signin_password->click();
			// $signin_password->sendKeys(str_split($bot['password']));
			// sleep(1);

			$scriptregister_email_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][0].value="'.$bot['email'].'"';
		    $webdriver->executeScript($scriptregister_email_input, array());

			sleep(1);
			
			$scriptregister_password_input = 'document.querySelectorAll(\'form[action="/accounts/signin/"]\')[0][1].value="'.$bot['password'].'"';
		    $webdriver->executeScript($scriptregister_password_input, array());

			sleep(1);

		
			$signin_submit_button->click();
		
			// Kullanıcı adı görünene kadar bekle.
			$session_status	= wait_until(function() use ($webdriver, $bot) {
				$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-header__dropdown-text--username.js-username');
				$error		= $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog__error--dark');

				if(sizeof($error))
					return -1;

				if ($element and trim($element->getText()) == $bot['username'])
					return 1;
			});

			if($session_status == -1){
				//botu sil
				$dbo->delete('bots', $bot['id']);
				add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				sleep(1);
				continue;
			}
		
			write_log_to_file('Giris yapildi'."\n");
		}

		$webdriver->executeScript('$(".layout__area--right").remove();', array());
		//---------------------------------------------------------------------------------------------

		write_log_to_file('Bot sayfaya girdi: '.$bot['username']."\n");

		$dbo->update('articles', array('current_views'=>++$article['current_views']), $article['id']);
		write_log_to_file('Gosterim sayisi artirildi'."\n");

		//---------------------------------------------------------------------------------------------
		// Yorumu gönder.
		//---------------------------------------------------------------------------------------------

		write_log_to_file('Cevap verilecek bot yorumu araniyor: '.$task['extra']['target_bot_username'].'...'."\n");

		$comment_blocks_count	= 0;
		while (true) {
			$comment_blocks = $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-chart-comment');
		
			// Aradığımız bot yorumu bulunduysa;
			if (sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, 'a[data-username="'.$task['extra']['target_bot_username'].'"]'))) {
		
				write_log_to_file('Hedef bot yorumu bulundu: '.$task['extra']['target_bot_username']."\n");



		
				$reply_button			= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-chart-comment > a[data-username="'.$task['extra']['target_bot_username'].'"] + .tv-chart-comment__wrap > .tv-chart-comment__controls > .tv-chart-comment__control');
				$reply_button_position	= $reply_button->getLocation();
		
				$webdriver->executeScript('window.scrollTo(0, '.$reply_button_position->y.'-50);', array());
				$reply_button->click();
				sleep(1);
		
				$reply_textarea	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-chart-comment > a[data-username="'.$task['extra']['target_bot_username'].'"] + .tv-chart-comment__wrap + form textarea.tv-chart-comment-form__textarea');
				$reply_textarea->click();
				$reply_textarea->sendKeys(str_split($comment));
				sleep(1);
		
				$post_comment_button	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.tv-chart-comment > a[data-username="'.$task['extra']['target_bot_username'].'"] + .tv-chart-comment__wrap + form button.tv-chart-comment__action');
				$post_comment_button->click();
				sleep(3);

				unset($reply_button, $reply_button_position, $reply_textarea, $post_comment_button);

				//---------------------------------------------------------------------------------------------
				// Bot banlanmış mı kontrol et.
				//---------------------------------------------------------------------------------------------
				sleep(1);
				if (sizeof($webdriver->findElementsBy(LocatorStrategy::cssSelector, '.tv-dialog a[href="/house-rules/"]'))) {
					write_log_to_file('Bot permanently banned.'."\n");
					//botu sil
					$dbo->delete('bots', $bot['id']);
					add_activity_log('Bot <b>'.$bot['username'].'</b> login error and bot is deleted.', 'system');
					$webdriver->closeWindow();
					sleep(2);
					$webdriver->close();
					sleep(1);
					write_log_to_file('Bot removed.'."\n");
					continue;
				}
				//---------------------------------------------------------------------------------------------

				$dbo->insert('article_comments', array('bot_id'=>$bot['id'], 'author_id'=>$article['author_id'], 'article_id'=>$article['id'], 'comment'=>$comment, 'is_reply'=>1, 'created_time'=>date('Y-m-d H:i:s')));

				write_log_to_file('Bot, diger botun yorumuna cevap verdi'."\n");

				add_activity_log('Bot <b>'.$bot['username'].'</b> re-replied to a comment.', 'rereply_comment');

				break;
			}
		
			if ($comment_blocks_count == sizeof($comment_blocks)) {
				write_log_to_file('Hedef bot yorumu bulunamadi'."\n");
				break;
			}

			$comment_blocks_count	= sizeof($comment_blocks);

			// Sayfayı aşağıya kaydır, yeni yorumlar yüklensin.
			$webdriver->executeScript('window.scrollTo(0,document.body.scrollHeight);', array());

			sleep(1);
		}
		unset($comment_blocks_count, $comment_blocks);

		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
		sleep(1);

	}

	end_of_task:

	// Görevi sil.
	//$dbo->delete('tasks', $task['id']);
	$completed_time=time();
	$sql = "UPDATE tasks SET status ='COMPLETED',completed_time='.$completed_time.' WHERE id= ".$task['id'];
	echo $sql;
	
	$update_query_Task_Completed_Response	= $dbo->execute($sql);


	$sleep	= rand(1,5);

	write_log_to_file("Sleeping ".$sleep." seconds...");

	sleep($sleep);
	write_log_to_file("\n\n");
	

}

write_log_to_file("\nAll DONE\n");

//-------------------------------------------------------------------------------------------------
function add_activity_log($str, $type='system') {

	$dbo	= get_dbo();
	$dbo->insert('activity_logs', array('log_text'=>$str, 'created_time'=>date('Y-m-d H:i:s'), 'type'=>$type));
}
//-------------------------------------------------------------------------------------------------


?>