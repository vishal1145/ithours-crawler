<?php

/*
	Günlük oluşturulacak bot görevlerini üretir.
	Gece saat 00:00 gibi bir defa çalıştırılmalıdır.
*/

chdir(__DIR__);
set_time_limit(0);
ini_set('display_errors',	true);
error_reporting(E_ALL);
ob_implicit_flush(true);
mb_internal_encoding('UTF-8');

require_once('../../includes/config.php');
require_once('../../includes/prepend.php');

$dbo		= get_dbo();
$settings	= get_settings();
$html		= new simple_html_dom();

sleep(2);
echo "checking ysemk";

if (!$settings['system_working']) {
	exit;
}


echo "checking ysemk 2";
sleep(2);

// Henüz tamamlanmamış bot oluşturma görev sayısını al.
//$waiting_count	= $dbo->count('tasks', array('type'=>'create_bot'));
$bot_creation_count_query="select count(*) as cnt from tasks where type='create_bot' AND status='NEW'";
echo $bot_creation_count_query;
sleep(2);
$count_tobe_created_bot  = $dbo->execute($bot_creation_count_query); 
    

// Eklenecek bot oluşturma görev sayısını min ve maks arasında rasgele seç.
$add_count		= rand($settings['daily_bot_creation_min'], $settings['daily_bot_creation_max']);

// Eklenecek bot oluşturma görev sayısından, tamamlanmamış görev sayısını çıkart.
//$add_count	-= $waiting_count;
$add_count	-= $count_tobe_created_bot[0]['cnt'];


echo "add_count ".$add_count;

// Görevleri sonraki 24 saat içine dağıt.
for ($i = 0; $i < $add_count; $i ++) {
	echo "inserting ";
	sleep(1);
	$dbo->insert('tasks', array('type'=>'create_bot', 'due'=>time() + rand(600, 86000)));
	sleep(2);
}

sleep(2);
echo "\n\nDONE\n\n";
exit;

?>