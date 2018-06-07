<?php
//require_once('../crawler_factory.php');
//require_once('./crawler_functions/mail_provider/temp_mail.php');
require_once('C:\xampp\htdocs\ithours-crawler\console\cron\crawler_functions\mail_providers\temp_mail.php');
require_once('C:\xampp\htdocs\ithours-crawler\console\cron\crawler_functions\crawler_factory.php');


class YoastCrawler extends ICrawler {
   
    function register() {
        
        $dbo        = get_dbo();

        write_log_to_file('Sign-up into site...'."\n");

        $proxy			= $dbo->execute('SELECT * FROM proxies ORDER BY rand() LIMIT 1');
		$proxy			= $proxy[0];
        $country_code	= $proxy['country_code'];

        $bots			= $dbo->execute('SELECT * FROM bots ORDER BY rand() LIMIT 1');
        $bot			= $bots[0];
       
        $password     =    '';
        $email        =    '';
        $username     =    '';
        $first_name   =     $bot['first_name'];
        $last_name    =     $bot['last_name'];
        $proxy_id     =     $bot['proxy_id'];
        $country_code =     $bot['country_code'];
        $created_time =     $bot['created_time'];
        $status       =     $bot['status'];
        $siteName     =    '';
        $SessionId    =     $bot['id'];

        
        $sql = "INSERT INTO siteBots (first_name, last_name,proxy_id,country_code,created_time,status,SessionId)
        VALUES ('$first_name', '$last_name', '$proxy_id', '$country_code','$created_time', '$status','$SessionId')";
        $row_inserted = $dbo->execute($sql);
      
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


        $povider_name= 'temp_mail';
        $LINK ='REGISTRATION URL';
        
    
    $crawlerObj = (new MailFactory())->GetMailProvider($povider_name);
    
             $bot['email'] = $crawlerObj->getRegisterEmail($webdriver);
          


			

			// Botun kullanıcı adını e-posta adresinden türet.
			$bot['username']	= substr($bot['email'], 0, strpos($bot['email'], '@')).generate_username(rand($settings['bot_username_min'], $settings['bot_username_max']));

            $email        =  $bot['email'];
            $username     =  $bot['username'];

            write_log_to_file('Created temp-email.org account: ('.$bot['email'].")\n");

            write_log_to_file('Sign-up the Account...'."\n");
            
            $webdriver->get('https://yoast.com/wp-signup.php');

            $webdriver->executeScript('document.getElementById("user_name").value="'.$username.'";', array());
            sleep(3);

            $webdriver->executeScript('document.getElementById("user_email").value="'.$email.'";', array());
            sleep(3);

            $click_on_next = 'document.getElementsByClassName("submit")[1].click()';
            $webdriver->executeScript($click_on_next, array());
            sleep(4);


           
               write_log_to_file('Waiting for activation mail...'."\n");

               if($LINK == 'REGISTRATION URL')
                 $activatio_URL = $crawlerObj->getActivationUrl($webdriver);
           
            $webdriver->get($activatio_URL);
            //$register_email_input->click();
               
            $sign_up	= $webdriver->findElementBy(LocatorStrategy::id, 'signup-welcome');
            $password_index	= $sign_up->findElementsBy(LocatorStrategy::cssSelector, 'p');
            //$password = $password_index[1]->getText();

            $password = substr($password_index[1]->getText(),10);

            $sql1 = "update siteBots set email = '".$email."',username = '".$username."',password = '".$password."' where SessionId =".$SessionId;
		
			echo $sql1;
	
			$update_bot_Task_Completed_Response	= $dbo->execute($sql1);
            
            write_log_to_file('Activation mail found. Reading...'."\n");
            
            write_log_to_file('Ok...'."\n");
            sleep(4);
            $webdriver->closeWindow();
				
				$webdriver->close();
				sleep(1);

    
    
    }


    function login() {echo "login";}
}

?>