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
//$dbo->execute($query_delete);

$time_to_compare = time() - (2*86400) ;
echo 'time_to_compare of artickes...'.$time_to_compare.''."\n";
$article_query = 'SELECT * FROM `articles` WHERE `due`>='.$time_to_compare.' and id = 5894 ORDER BY due asc';

$articles	= $dbo->execute($article_query);

foreach ($articles as $article) {

	echo 'Checking repair tasks for articles...'."\n";

	$article_id = $article['id'];
	$author_id = $article['author_id'];
	$article_due = $article['due'];

	if($author_id !=44)
		continue;

	echo 'Checking article '.$article_id.'...'."\n";
	

	$end_time = $article_due + (2*86400);

	$remain_time = $end_time - time();

	$required = $article['target_likes'] - $article['current_likes'];
	
	//echo 'Checking article '.$article_id.'...'."\n";

	echo '$required number of likes according to article table are '.$required."\n";

    $query_exist_tasks_count = 'select type , count(1) as cnt from tasks where  `due`>='.time().' and `type` = \'like_article\' and status = \'NEW\' and article_id ='.$article_id.' group by type';

    echo $query_exist_tasks_count;
   
	$exist_like_tasks = $dbo->execute($query_exist_tasks_count);

	$upcoming_like_task_count_in_tasks_table = $exist_like_tasks[0]['cnt'];

	echo $upcoming_like_task_count_in_tasks_table;
	 sleep(2);
	
	echo 'size '.$upcoming_like_task_count_in_tasks_table."\n";
	
	if (sizeof($exist_like_tasks))
	$required_like = $required - $upcoming_like_task_count_in_tasks_table;

	echo '$required number of likes according to current data are '.$required_like."\n";


	$difference_in_table = $article['target_views'] - $article['current_views'];
	
	//echo 'Checking article '.$article_id.'...'."\n";

	echo '$required number of visites according to article table are '.$difference_in_table."\n";

    $query_exist_visit_tasks_count = 'select type , count(1) as cnt from tasks where  `due`>='.time().' and `type` = \'visit_article\' and status = \'NEW\' and article_id ='.$article_id.' group by type';

     echo $query_exist_visit_tasks_count;
   
	
	$exist_visit_tasks = $dbo->execute($query_exist_visit_tasks_count);

	$upcoming_visit_task_count_in_tasks_table = $exist_visit_tasks[0]['cnt'];
	echo $upcoming_visit_task_count_in_tasks_table;

	 sleep(2);

	
	if (sizeof($exist_visit_tasks))
	$difference_visit = $difference_in_table - $upcoming_visit_task_count_in_tasks_table;

	echo '$required number of visites according to current data are '.$difference_visit."\n";

	sleep(2);
		
		$current_count_of_like_to_be_run = $required_like + $upcoming_like_task_count_in_tasks_table;
		$current_count_of_visit_to_be_run = $difference_visit + $upcoming_visit_task_count_in_tasks_table;
		echo $current_count_of_like_to_be_run;
		echo $current_count_of_visit_to_be_run;

	if(($current_count_of_like_to_be_run*10) < $current_count_of_visit_to_be_run)
	{
		
			$having_like_task_count = $upcoming_like_task_count_in_tasks_table;
			$like_require_for_ratio = $current_count_of_visit_to_be_run/10;
			$new_required_like = $like_require_for_ratio - $having_like_task_count;

			if($new_required_like > 0)
	       {
		    $range = 86400 / $new_required_like;
		    for ($i = 0; $i < $new_required_like; $i ++) {
			$start = $range * $i;
			$end = $start + $range;
			$due_add = mt_rand($start, $end);
			echo '$due_add value is...'.$due_add.''."\n";
			$dbo->insert('tasks', array('type'=>'like_article', 'author_id'=>$author_id, 'article_id'=>$article_id, 'due'=>time() + $due_add));
			 echo '$number of  like task inserted ='.$i."\n";

		   }
		  
	      }
	      sleep(5);

	}
	else
	{
			
			$having_visit_task_count = $upcoming_visit_task_count_in_tasks_table;
			$visit_require_for_ratio = $current_count_of_like_to_be_run*10;
			$new_required_visit = $visit_require_for_ratio - $having_visit_task_count;

			if($new_required_visit < 0)
				break;

			if($new_required_visit > 0)
	       {
		    
		    $range = 86400 / $new_required_visit;
		    for ($i = 0; $i < $new_required_visit; $i ++) {
			$start = $range * $i;
			$end = $start + $range;
			$due_add = mt_rand($start, $end);
			echo '$due_add value is...'.$due_add.''."\n";
			$dbo->insert('tasks', array('type'=>'visit_article', 'author_id'=>$author_id, 'article_id'=>$article_id, 'due'=>time() + $due_add));
			 echo '$number of  like task inserted ='.$i."\n";

		   }
		  
	      }
	      
	     
	}

	//echo 'required...'.$required.''."\n";

	sleep(rand(2,8));


}

//-------------------------------------------------------------------------------------------------
function add_activity_log($str, $type='system') {
	$dbo	= get_dbo();
	$dbo->insert('activity_logs', array('log_text'=>$str, 'created_time'=>date('Y-m-d H:i:s'), 'type'=>$type));
}
//-------------------------------------------------------------------------------------------------

?>