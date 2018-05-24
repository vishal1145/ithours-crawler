<?php
function clickLinkYahoo($task, $webdriver)
{
    $link_clicked = false;
    
    $class_data = $webdriver->findElementsBy(LocatorStrategy::cssSelector, "div[class='compTitle options-toggle']");
    
    foreach ($class_data as $one_block) {
        
        $h3        = $one_block->findElementBy(LocatorStrategy::cssSelector, 'h3');
        $a_eleamnt = $h3->findElementBy(LocatorStrategy::cssSelector, 'a');
        $link      = $a_eleamnt->getAttribute('href');
        
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

function openNextPageYahoo($pageNo, $webdriver, $is_first_page)
{
    
    
    $Parent_div_data = $webdriver->findElementBy(LocatorStrategy::cssSelector, "div[class='dd pagination fst lst Pgntn']");
    $child_div_data  = $Parent_div_data->findElementBy(LocatorStrategy::cssSelector, '.compPagination');
    $a_eleamnt       = $child_div_data->findElementsBy(LocatorStrategy::cssSelector, 'a');
    
    if ($is_first_page == true) {
        $next_page_index = $pageNo - 2;
    } else {
        $next_page_index = $pageNo - 1;
    }
    
    $next_page = $a_eleamnt[$next_page_index];
    $read_link = $next_page->getAttribute('href');
    $next_page->click();
    sleep(5);
    
    
}
?>