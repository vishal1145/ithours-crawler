<?php
function driverManager()
{

$dbo        = get_dbo();   
$bot	= $dbo->execute('SELECT * FROM bots ORDER BY rand() LIMIT 1');
$bot	= $bot[0];


// Botun proxy kaydını çek.
$proxy	= $dbo->execute('SELECT * FROM proxies WHERE id='.$bot['proxy_id'].' LIMIT 1');
$proxy	= $proxy[0];


$bot['domain']		= $proxy['domain'];
$bot['http_port']	= $proxy['http_port'];
$bot['country']		= $proxy['country'];

$driverManager->proxy = $proxy;
$driverManager->bot = $bot;

//---------------------------------------------------------------------------------------------
$webdriver	= new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
$webdriver->connect('chrome', '', array
(
    'proxy'=>array
    (
        'proxyType'		=> 'manual',
        'httpProxy'		=> $bot['domain'].':'.$bot['http_port'],
        'sslProxy'		=> $bot['domain'].':'.$bot['http_port']
    ),
    'chromeOptions'=>array('args'=>array('user-data-dir='.$GLOBALS['config']['bots_user_data_dir'].$bot['id'].'/','disable-infobars','ignore-certificate-errors','disable-notifications','load-extension='.$GLOBALS['config']['chrome_multipass_extension_dir']))
));
    
    $webdriver->windowMaximize();
    $webdriver->setImplicitWaitTimeout(10000);
    $webdriver->setSpeed('SLOW');

    
    $driverManager->webdriver = $webdriver;

    return $driverManager;
}
?>