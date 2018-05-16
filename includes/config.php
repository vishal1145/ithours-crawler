<?php
set_time_limit(0);
ini_set('display_errors',	true);
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');

ini_set('session.use_only_cookies', true);
session_set_cookie_params(0, '/');
session_name('tradingview');

$_tempLocal  = '';


// $config['db']['name']			= 'ithoursh_tradingview';
// $config['db']['user']			= 'ithoursh_trading';
// $config['db']['password']		= 'tradingview';

//$config['db']['name']			= 'ithoursh_tradingviewbot';
//$config['db']['user']			= 'ithoursh_ithours';
//$config['db']['password']		= '@ithours';

//$config['db']['name']			= 'ithoursh_tradingviewbot1';
//$config['db']['user']			= 'ithoursh_ithour1';
//$config['db']['password']		= '@ithour1';

$config['db']['name']			= 'crawler';
$config['db']['user']			= 'root';
$config['db']['password']		= 'abhi';

$config['db']['host']			= 'localhost';
$config['db']['port']			= '3306';
$config['db']['charset']		= 'UTF8MB4';

$config['mail_sources']			= array ("temp-mail.org", "mytemp.email");



$config['default_timezone']		= 'Europe/Istanbul';


$config['db']['local']['name']			= 'tradingview';
$config['db']['local']['user']			= 'root';
$config['db']['local']['password']		= 'deneme';
$config['db']['local']['host']			= 'localhost';
$config['db']['local']['port']			= '3306';
$config['db']['local']['charset']		= 'UTF8MB4';

$config['proxy']['username']			= 'user8629278';
$config['proxy']['password']			= 'user123123';

$config['webdriver']['host']			= 'localhost';
$config['webdriver']['port']			= '4444';

$config['bots_user_data_dir']				= 'C:\\Chrome_User_Data\\';
$config['chrome_multipass_extension_dir']	= 'C:\\xampp\\htdocs\\tradingviewbot\\chrome_plugin\\enhldmjbphoeibbpdhmjkchohnidgnah\\0.7.4_0\\';


$config['date_format']			= 'd.m.Y';
$config['long_date_format']		= 'd F Y l';
$config['datetime_format']		= 'd.m.Y H:i';
$config['long_datetime_format']	= 'd F Y l H:i';
$config['time_format']			= 'H:i';
$config['long_time_format']		= 'H:i:s';
$config['month_year_format']	= 'm/Y';
$config['decimal_symbol']		= ',';
$config['thousands_symbol']		= '.';
$config['percent_left_symbol']	= '%';
$config['percent_right_symbol']	= '';

//mailer_tasks values


$config['MAILSETTINGS']['HOST'] = 'smtp.gmail.com'; // Specify main and backup SMTP servers
$config['MAILSETTINGS']['UserName']='vishal.test123456@gmail.com'; // SMTP username
$config['MAILSETTINGS']['Password']='vishal987654';  // SMTP password
$config['MAILSETTINGS']['Set_From_Email']='vishal.test123456@gmail.com';
$config['MAILSETTINGS']['Set_From_DisplayName']='Vishal';

$config['ServerIP'] = '222.222.20.222';






?>