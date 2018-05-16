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


echo 'Checking new articles of authors...'."\n";

$authors	= $dbo->execute('SELECT * FROM authors ORDER BY rand()');

foreach ($authors as $author) {
	if($author[id] !=44)
		continue;

	echo 'https://www.tradingview.com/u/'.$author['name'].'/'."\n";

	// Rastgele bir proxy çek.
	$proxy		= $dbo->execute('SELECT * FROM proxies ORDER BY rand() LIMIT 1');
	$proxy		= $proxy[0];

	// Yazar sayfasını çek.
	$html->load(ApiRequestCookie('https://www.tradingview.com/u/'.$author['name'].'/', null, array(), null, array('domain'=>$proxy['domain'], 'username'=>$config['proxy']['username'], 'password'=>$config['proxy']['password'], 'port'=>$proxy['http_port'])));

	// Makale bloklarını bul.
	$article_blocks	= $html->find('div[data-widget-type="idea"]');

	foreach ($article_blocks as $article_block) {
		$article_link		= trim(current($article_block->find('a.tv-widget-idea__title'))->href);
		$article_currency	= trim(current($article_block->find('a[href*="/symbols/"]'))->plaintext);

		if ($dbo->exists('articles', array('link'=>$article_link)))
			continue;

		echo '	New article found: '.$article_link."\n";

		if (sizeof($article_block->find('span.tv-idea-label--short')))
			$article_position	= 'short';
		elseif (sizeof($article_block->find('span.tv-idea-label--long')))
			$article_position	= 'long';
		else
			$article_position	= '';

		$target_likes		= mt_rand($author['target_article_likes_min'], $author['target_article_likes_max']);
		$target_comments	= mt_rand($author['target_article_comments_min'], $author['target_article_comments_max']);
		$target_views		= mt_rand($author['target_article_views_min'], $author['target_article_views_max']);
		$threshold_min		= $author['threshold_percent_min'];
		$threshold_max		= $author['threshold_percent_max'];

		// Makaleyi veritabanına ekle.
		$article_id	= $dbo->insert('articles', array('author_id'=>$author['id'], 'link'=>$article_link, 'target_likes'=>$target_likes, 'target_comments'=>$target_comments, 'target_views'=>$target_views, 'currency'=>$article_currency, 'position'=>$article_position, 'due'=>time()));

		//-----------------------------------------------------------------------------------------
		// Makale için beğeni görevlerini oluştur.
		//-----------------------------------------------------------------------------------------
		$remain_count	= $target_likes;

		for ($i = 0; $i < $remain_count; $i ++) {
			$dbo->insert('tasks', array('type'=>'like_article', 'author_id'=>$author['id'], 'article_id'=>$article_id, 'due'=>time() + mt_rand(600, 3600*3)));
		}

		//-----------------------------------------------------------------------------------------
		// Makale için yorum görevlerini oluştur.
		//-----------------------------------------------------------------------------------------
		$remain_count	= $target_comments;

		for ($i = 0; $i < $remain_count; $i ++) {
			$dbo->insert('tasks', array('type'=>'comment_article', 'author_id'=>$author['id'], 'article_id'=>$article_id, 'due'=>time() + mt_rand(600, 3600*3)));
		}

		//-----------------------------------------------------------------------------------------
		// Makale için sayfa ziyareti görevlerini oluştur.
		//-----------------------------------------------------------------------------------------
		$remain_count	= $target_views - $target_likes - $target_comments;

		for ($i = 0; $i < $remain_count; $i ++) {
			$dbo->insert('tasks', array('type'=>'visit_article', 'author_id'=>$author['id'], 'article_id'=>$article_id, 'due'=>time() + mt_rand(600, 86400)));
		}

		add_activity_log('New idea published by <b>'.$author['name'].'</b>', 'article_new');
	}

	sleep(rand(2,8));
}



//-------------------------------------------------------------------------------------------------
function add_activity_log($str, $type='system') {
	$dbo	= get_dbo();
	$dbo->insert('activity_logs', array('log_text'=>$str, 'created_time'=>date('Y-m-d H:i:s'), 'type'=>$type));
}
//-------------------------------------------------------------------------------------------------

?>