<?php
//require_once('./site_crawlers/yoast_crawler.php');
//require_once('./crawler_functions/user_journey_manager.php');



abstract class ICrawler {
    abstract function register();
    abstract function login();
}


class CrawlerFactory {
    private $context = "OReilly";  
   function GetCrawler($site) {
    $crawler = NULL;   
        switch ($site) {
            case "YOAST":
                $crawler = new YoastCrawler;
            break;
            case "other":
                $crawler = new SamsPHPBook;
            break;
            default:
                $crawler = new OReillyPHPBook;
            break;        
        }     
    return $crawler;
    }
}






?>