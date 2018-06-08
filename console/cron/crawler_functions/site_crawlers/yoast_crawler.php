<?php

chdir(__DIR__);
require_once('../mail_providers/temp_mail.php');
require_once('../crawler_factory.php');
require_once('../driver_manager.php');
require_once('../utility_manager.php');
require_once('../../crawler_tasks.php');
require_once('../mail_providers/my_temp_mail.php');

// require_once('C:\xampp\htdocs\ithours-crawler\console\cron\crawler_functions\mail_providers\temp_mail.php');
// require_once('C:\xampp\htdocs\ithours-crawler\console\cron\crawler_functions\crawler_factory.php');
// require_once('C:\xampp\htdocs\ithours-crawler\console\cron\crawler_functions\driver_manager.php');
// require_once('C:\xampp\htdocs\ithours-crawler\console\cron\crawler_functions\utility_manager.php');


class YoastCrawler extends ICrawler {
   


    function register() {
        
        $driverManager = driverManager();
        $bot = $driverManager->bot;
        $webdriver = $driverManager->webdriver;
       
        $dbo        = get_dbo();

        write_log_to_file('Sign-up into site...'."\n");
        $povider_name= 'my_temp';
    
        $MailerObj = (new MailFactory())->GetMailProvider($povider_name);
        $bot['email'] = $MailerObj->getRegisterEmail($webdriver);
          
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


            $error	= $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.error');

            // if((sizeof($error)) > 0)
            // {
            //     sleep(4);
            //     $webdriver->closeWindow();
            //      $webdriver->close();
                
            // }
            sleep(4);


                write_log_to_file('Waiting for activation mail...'."\n");
                $activatio_URL = $MailerObj->getActivationUrl($webdriver);
                sleep(4);

                
            $webdriver->get($activatio_URL);
            //$register_email_input->click();
               
            $sign_up	= $webdriver->findElementBy(LocatorStrategy::id, 'signup-welcome');
            $password_index	= $sign_up->findElementsBy(LocatorStrategy::cssSelector, 'p');
            //$password = $password_index[1]->getText();

            $password = substr($password_index[1]->getText(),10);
            $bot['password'] = $password;

            $bot_insert_into_table = botInsertion($bot);
            write_log_to_file('Activation mail found. Reading...'."\n");
            write_log_to_file('Ok...'."\n");
            sleep(4);
            $webdriver->closeWindow();
				$webdriver->close();
				sleep(1);
            
            

              
           
    }

    function login() {
        echo "abhitesh";

    }


   
}

?>