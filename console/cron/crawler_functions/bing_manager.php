<?php
function clickLinkBing($site_domain, $webdriver)
{
    $link_clicked = false;
    $class_data   = $webdriver->findElementsBy(LocatorStrategy::cssSelector, '.b_algo');
    foreach ($class_data as $one_block) {
        
        $h2        = $one_block->findElementBy(LocatorStrategy::cssSelector, 'h2');
        $a_eleamnt = $h2->findElementBy(LocatorStrategy::cssSelector, 'a');
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

function openNextPageBing($pageNo, $webdriver, $is_first_page)
{
    if ($is_first_page == true) {
        $Parent_ul_data  = $webdriver->findElementBy(LocatorStrategy::cssSelector, '.sb_pagF');
        $child_li_data   = $Parent_ul_data->findElementsBy(LocatorStrategy::cssSelector, 'li');
        $next_page_index = $pageNo;
        $is_first_page   = false;
    } else {
        $next_page_index = $pageNo;
        $Parent_li_data  = $webdriver->findElementBy(LocatorStrategy::cssSelector, '.b_pag');
        $nav_data        = $Parent_li_data->findElementBy(LocatorStrategy::cssSelector, 'nav');
        $ul_data         = $nav_data->findElementBy(LocatorStrategy::cssSelector, 'ul');
        $child_li_data   = $ul_data->findElementsBy(LocatorStrategy::cssSelector, 'li');
    }
    
    $next_page = $child_li_data[$next_page_index]->findElementBy(LocatorStrategy::cssSelector, 'a');
    $next_page->click();
    sleep(5);
    
}

?>