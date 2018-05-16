<?php

/*
	TradingView üzerindeki makalelere yapılmış yorumları toplar.
	Bu işi yaparken WebDriver yerine curl kullanır. Bu yüzden,
	yorumlar tamamı toplanmaz, yalnızca sayfaya ilk girildiğinde hali hazırda
	HTML'in içine çıktılanmış yorumlar toplanır. Sayfanın Ajax ile yüklediği yorumlar
	kaale alınmaz.

	Günde birkaç sefer veya her saat başı çalıştırılabilir.
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

$dbo		= get_dbo();
$settings	= get_settings();
$html		= new simple_html_dom();

if (!$settings['system_working']) {
	exit;
}

$proxy		= $dbo->execute('SELECT * FROM proxies ORDER BY rand() LIMIT 1');
$proxy		= $proxy[0];


echo 'Fetching: https://www.tradingview.com/'."\n";

$html->load(ApiRequestCookie('https://www.tradingview.com/', null, array(), null, array('domain'=>$proxy['domain'], 'username'=>$config['proxy']['username'], 'password'=>$config['proxy']['password'], 'port'=>$proxy['http_port'])));

$article_links	= $html->find('a.tv-widget-idea__title');

foreach ($article_links as $article_link) {

	// Rastgele bir proxy çek.
	$proxy		= $dbo->execute('SELECT * FROM proxies ORDER BY rand() LIMIT 1');
	$proxy		= $proxy[0];

	echo 'Fetching: https://www.tradingview.com'.$article_link->href."\n";

	$article_html	= new simple_html_dom();
	$article_html->load(ApiRequestCookie('https://www.tradingview.com'.$article_link->href, null, array(), null, array('domain'=>$proxy['domain'], 'username'=>$config['proxy']['username'], 'password'=>$config['proxy']['password'], 'port'=>$proxy['http_port'])));

	$comments	= $article_html->find('.tv-chart-comment');
	foreach ($comments as $comment) {

		// Yorum sahibi kullanıcı adını bul.
		$username	= trim(current($comment->find('.tv-chart-comment__user-name'))->innertext);

		// Eğer bizim botlardan biriyse, sonraki yoruma geç.
		if ($dbo->exists('bots', array('username'=>$username))) {
			echo 'This cuckold is our bot: '.$username."\n";
			continue;
		}

		// Yorum metnini bul.
		$comment_text	= current($comment->find('.tv-chart-comment__text'));

		// HTML etiketlerini yoket.
		foreach ($comment_text->children() as $tag) {
			$tag->outertext	= '';
		}

		$comment_text	= trim($comment_text->innertext);
		if (substr($comment_text, 0, 1) == ',')
			$comment_text	= trim(substr($comment_text, 1));

		$checksum	= md5($comment_text);

		// Eğer boş ise, sonrakine geç.
		if (!strlen($comment_text))
			continue;

		// Eğer veritabanında zaten kayıtlıysa, sonrakine geç.
		if ($dbo->exists('collected_comments', array('checksum'=>$checksum)))
			continue;

		$dbo->insert('collected_comments', array('comment'=>$comment_text, 'checksum'=>$checksum, 'created_time'=>date('Y-m-d H:i:s')));

		echo $comment_text;
		echo "\n----------------------------------------------------------------------\n";

	}

	sleep(rand(2,8));
}

echo "\n\nDONE\n\n";
exit;

?>