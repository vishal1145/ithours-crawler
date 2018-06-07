<?php

class TestCrawler extends ICrawler {
    
  function register() {
      echo "registeration";
      $k=siteCrawling();
      echo "Abhitesh";
  
  }
  function login() {echo "login";}
}

?>