<?php
//require_once('./site_crawlers/yoast_crawler.php');
//require_once('./crawler_functions/user_journey_manager.php');



abstract class MailProvider {
    abstract function getRegisterEmail($webdriver);
    abstract function getActivationUrl();
}
class MailFactory {
    private $context = "OReilly";  
   function GetMailProvider($povider_name) {
    $mailer = NULL;   
        switch ($povider_name) {
            case "temp_mail":
                $mailer = new TempMailProvider;
            break;
            case "other":
                $mailer = new SamsPHPBook;
            break;
            default:
                $mailer = new OReillyPHPBook;
            break;        
        }     
    return $mailer;
    }
}






?>