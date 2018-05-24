<?php

function clickLinkGoogle($task, $webdriver)
{
    $link_clicked = false;
    $res_array    = $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.rc');
    foreach ($res_array as $one_block) {
        
        $h3_element       = $one_block->findElementBy(LocatorStrategy::cssSelector, 'h3');
        $a_eleamnt        = $h3_element->findElementBy(LocatorStrategy::cssSelector, 'a');
        $is_domain_exists = strpos($a_eleamnt->getAttribute('href'), $task['target_domain']);
        if ($is_domain_exists == true) {
            $a_eleamnt->click();
            sleep($task['wait_factor']);
            $webdriver->closeWindow();
            $webdriver->close();
            $link_clicked = true;
            break;
        }
        
    }
    
    return $link_clicked;
    
}

function openNextPageGoogle($pageNo, $webdriver)
{
    $table_data = $webdriver->findElementBy(LocatorStrategy::id, 'nav');
    $tbody_data = $table_data->findElementBy(LocatorStrategy::cssSelector, 'tbody');
    $tr_data    = $tbody_data->findElementBy(LocatorStrategy::cssSelector, 'tr');
    $td_data    = $tr_data->findElementsBy(LocatorStrategy::cssSelector, 'td');
    $a_eleamnt  = $td_data[$pageNo]->findElementBy(LocatorStrategy::cssSelector, 'a');
    $a_eleamnt->click();
    sleep(5);
}

?>