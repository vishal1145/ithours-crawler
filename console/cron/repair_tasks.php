<?php

/*
	Sisteme eklenmiş yazarlara ait yeni makale olup olmadığını kontrol eder.
	Yeni makale bulunduğunda veritabanına kaydeder.
	Like, comment ve visit görevlerini oluşturur.
	Günde birkaç sefer otomatik olarak çalıştırılabilir.
*/

chdir(__DIR__);
set_time_limit(0);
ini_set('display_errors',	true);
error_reporting(E_ALL);
ob_implicit_flush(true);
mb_internal_encoding('UTF-8');

require_once('../../includes/config.php');
require_once('../../includes/prepend.php');
require_once '../../includes/class/simple_html_dom.php';
require_once '../../includes/class/phpwebdriver/WebDriver.php';

$dbo		= get_dbo();
$settings	= get_settings();
$html		= new simple_html_dom();


if (!$settings['system_working']) {
	exit;
}


echo 'Checking repair tasks for articles...'."\n";

$query_delete = 'DELETE FROM `tasks` WHERE `due`<='.time();
$dbo->execute($query_delete);

$time_to_compare = time() - (2*86400) ;
echo 'time_to_compare of artickes...'.$time_to_compare.''."\n";
$article_query = 'SELECT * FROM `articles` WHERE `due`>='.$time_to_compare.' ORDER BY due asc';

$articles	= $dbo->execute($article_query);

foreach ($articles as $article) {

	echo 'Checking repair tasks for articles...'."\n";

	$article_id = $article['id'];
	$author_id = $article['author_id'];
	$article_due = $article['due'];

	echo 'Checking article '.$article_id.'...'."\n";
	

	$end_time = $article_due + (2*86400);

	$remain_time = $end_time - time();

	$required = $article['target_likes'] - $article['current_likes'];
	
	//echo 'Checking article '.$article_id.'...'."\n";

	echo '$required number of articles are '.$required."\n";

    $query_exist_tasks_count = 'select type , count(1) as cnt from tasks where `type` = \'like_article\' and article_id ='.$article_id.' group by type';
	$exist_tasks = $dbo->execute($query_exist_tasks_count);
	
	if (sizeof($exist_tasks))
	$required = $required - $exist_tasks[0]['cnt'];

	echo '$required number of articles are '.$required."\n";
	
	
	
	if($required > 0){
		$range = 86400 / $required;
		for ($i = 0; $i < $required; $i ++) {
			$start = $range * $i;
			$end = $start + $range;
			$due_add = mt_rand($start, $end);
			echo '$due_add value is...'.$due_add.''."\n";
			$dbo->insert('tasks', array('type'=>'like_article', 'author_id'=>$author_id, 'article_id'=>$article_id, 'due'=>time() + $due_add));
		}
	}
	echo 'required...'.$required.''."\n";

	sleep(rand(2,8));
}



//-------------------------------------------------------------------------------------------------
function add_activity_log($str, $type='system') {
	$dbo	= get_dbo();
	$dbo->insert('activity_logs', array('log_text'=>$str, 'created_time'=>date('Y-m-d H:i:s'), 'type'=>$type));
}
//-------------------------------------------------------------------------------------------------

?>