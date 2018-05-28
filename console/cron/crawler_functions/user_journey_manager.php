<?php

function user_journey_function($task)
{
    $dbo        = get_dbo();
$journey_id = $task['crawler_journey_id'] ; 

$Query         = 'SELECT * FROM `user_journey` where journey_id = '.$journey_id.' ORDER BY step';
$Query_Results = $dbo->execute($Query);
$driverManager = driverManager();

    $link = $Query_Results[0]['start'];
    $driverManager->webdriver->get($link);
    $sleep_time = rand($task['min_wait_factor'],$task['max_wait_factor']);
    sleep($sleep_time);
 
    for( $i = 1; $i<count($Query_Results); $i++ ) {
        $css_selector = $Query_Results[$i]['cssselector'];
        $link = $driverManager->webdriver->findElementBy(LocatorStrategy::xpath, $css_selector);
        $link->click();
        $sleep_time = rand($task['min_wait_factor'],$task['max_wait_factor']);
        sleep($sleep_time);
     }
   
  
    $driverManager->webdriver->closeWindow();
    $driverManager->webdriver->close();
}

?>