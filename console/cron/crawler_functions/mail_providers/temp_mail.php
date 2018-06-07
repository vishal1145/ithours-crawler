<?php
//require_once('../crawler_factory.php');
require_once('C:\xampp\htdocs\ithours-crawler\console\cron\crawler_functions\site_crawlers\mail_factory.php');


class TempMailProvider extends MailProvider {
   
    function getRegisterEmail($webdriver) {
        
        $email ="";
		
			$webdriver->get('https://temp-mail.org/en/');

			// input#mail görünene kadar bekle.
			try {
				$email 	= wait_until(function() use ($webdriver) {
					$mail	= $webdriver->findElementsBy(LocatorStrategy::cssSelector, 'input#mail');
					if (sizeof($mail)) {
						return $mail[0]->getAttribute('value');
					}
				});
			} catch (exception $e) {
				// E-posta adresi oluşturulamadı. Bot işlemlerini iptal et, yeniden denensin.
				
				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				sleep(1);

				continue;
			}

			

            return  $email;

        }  

        function getActivationUrl($webdriver) {


            $webdriver->get('https://temp-mail.org/en/');

             
               
            $click_on_subject = 'document.getElementsByClassName("title-subject")[0].click()';
            $webdriver->executeScript($click_on_subject, array());
            sleep(2);

            $register_email_input = $webdriver->findElementBy(LocatorStrategy::xpath, '/html/body/div[1]/div/div/div[2]/div[1]/div[1]/div[3]/div/div/div/table/tbody/tr/td/table/tbody/tr[2]/td/table/tbody/tr/td/table/tbody/tr/td/div/p[4]/a');
            $register_email_input_url = $register_email_input->getText();
            //$webdriver->get($register_email_input_url);
           
            
           return  $register_email_input_url;
            sleep(4);
           

    
        }
    


    
}

?>