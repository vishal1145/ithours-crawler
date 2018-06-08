<?php

chdir(__DIR__);

//require_once('C:\xampp\htdocs\ithours-crawler\console\cron\crawler_functions\site_crawlers\mail_factory.php');
require_once('../site_crawlers/mail_factory.php');

class MyTempMailProvider extends MailProvider {
   
    function getRegisterEmail($webdriver) {
        
        $email ="";
		

			$webdriver->get('http://mytemp.email/2');
			// $webdriver->get('https://mytemp.email/');
			// $click_on_start = 'document.getElementsByClassName("orange darken-4 btn-large waves-effect waves-light")[0].click()';
            // $webdriver->executeScript($click_on_start, array());
			
			

			// try {
			// 	wait_until(function() use ($webdriver) {
			// 		if (strpos($webdriver->getTitle(), '@') !== false)
			// 			return true;
			// 	});
			// } catch (exception $e) {
			// 	// E-posta adresi oluşturulamadı. Bot işlemlerini iptal et, yeniden denensin.
				
			// 	$webdriver->closeWindow();
			// 	sleep(2);
			// 	$webdriver->close();
			// 	sleep(1);

			
			// }

			$email_link = $webdriver->findElementBy(LocatorStrategy::cssSelector, "span[class='truncate ng-binding flex']");
			$email = $email_link->getText();

			// $email		= substr($webdriver->getTitle(), 15);
			// $email		= substr($email, 0, strpos($email, ' '));

			

            return  $email;

        }  

        function getActivationUrl($webdriver) {


			$webdriver->get('http://mytemp.email/2');
			sleep(2);
			
			write_log_to_file('Waiting for activation mail...'."\n");

			// $click_on_inbox = 'document.getElementsByClassName("truncate ng-binding flex")[0].click()';
			// $webdriver->executeScript($click_on_inbox, array());
			// sleep(2);

			// $click_on_message_link = 'document.getElementsByClassName("truncate hide-sm ng-binding flex-25")[0].click()';
			// $webdriver->executeScript($click_on_message_link, array());
			// sleep(2);

			$inbox_click = $webdriver->findElementsBy(LocatorStrategy::cssSelector, "span[class='truncate ng-binding flex']");
			$inbox_click[0]->click();

			$click_on_message_link = $webdriver->findElementsBy(LocatorStrategy::cssSelector, "span[class='truncate hide-sm ng-binding flex-25']");
			$click_on_message_link[0]->click();

			$div_parent   = $webdriver->findElementBy(LocatorStrategy::id, 'body_content_inner');
			sleep(10);

			// $get_div_parent = 'document.getElementById("body_content_inner")';
		    // $webdriver->executeScript($get_div_parent, array());
			// sleep(2);
			//*[@id="body_content_inner"]/p[4]/a

			$p_parent   = $div_parent[0]->findElementsBy(LocatorStrategy::cssSelector, 'p');

			foreach($p_parent as $p)
			{
				$a_element = $p->findElementsBy(LocatorStrategy::cssSelector, 'a');
				if((sizeof($a_element)) > 0)
				{
					$register_email_input_url = $a_element[0]->getAttribute('href');
				}

			
			}
			//$register_email_input = $webdriver->findElementsBy(LocatorStrategy::xpath, '//*[@id="body_content_inner"]/p[4]/a');
           return  $register_email_input_url;
            sleep(4);
           

    
        }
    


    
}

?>