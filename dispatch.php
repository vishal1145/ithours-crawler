<?php

require_once('includes/config.php');
require_once('includes/prepend.php');
require_once('console/cron/crawler_tasks.php');
//require_once('includes/prepend.php');
//require_once('console/cron/mailer_tasks.php');
//require_once('console/cron/perform_tasks.php');
//require_once('console/cron/gmail_test.php');
//require_once('console/cron/hotmail_test.php');


//-------------------------------------------------------------------------------------------------
// Adresi ayrıştır.
//-------------------------------------------------------------------------------------------------
$request_uri	= substr($_SERVER['REDIRECT_URL'], strlen($_tempLocal.$_SERVER['BASE_PATH']));
$url_parts		= array();

/*
print_r($_SERVER);
print_r($request_uri);
exit();
*/

foreach (explode('/', $request_uri) as $url_part) {
	if (!strlen($url_part))
		continue;

	$url_parts[]	= $url_part;
}

//-------------------------------------------------------------------------------------------------
// Ayrıştırılmış adres ile gönderilen adres arasında fark varsa düzeltilmiş adrese yönlendir.
//-------------------------------------------------------------------------------------------------
if ($request_uri != '/' and $request_uri != '/'.implode('/', $url_parts).'/') {
	header ('Location: '.$_SERVER['BASE_PATH'].'/'.implode('/', $url_parts).'/');
	exit;
}


//-------------------------------------------------------------------------------------------------
// Oturumu yapılandır.
//-------------------------------------------------------------------------------------------------
session_start();

if (!array_key_exists('member', $_SESSION)) {
	$_SESSION['member']	= array('id'=>null, 'username'=>'', 'go_after_login'=>'');
}

if (!array_key_exists('admin', $_SESSION)) {
	$_SESSION['admin']	= array('id'=>null, 'username'=>'', 'go_after_login'=>'');
}

//-------------------------------------------------------------------------------------------------
// Otomatik giriş çerezlerini kontrol et.
//-------------------------------------------------------------------------------------------------
$dbo	= get_dbo();

if (!$_SESSION['member']['id'] and array_key_exists('member_auto_login', $_COOKIE)) {

	// Üye otomatik giriş kodu ve üye durumu geçerliyse;
	if ($member = $dbo->seek('members', array('auto_login_code'=>$_COOKIE['member_auto_login'], 'status'=>'active'), array('id','username'))) {
		$_SESSION['member']['id']		= $member['id'];
		$_SESSION['member']['username']	= $member['username'];

	// Aksi hâlde çerezi sil.
	} else {
		setcookie('member_auto_login', '', time() - 3600, '/');
	}
	unset($member);
}

if (!$_SESSION['admin']['id'] and array_key_exists('admin_auto_login', $_COOKIE)) {

	// Admin otomatik giriş kodu ve kullanıcı durumu geçerliyse;
	if ($admin = $dbo->seek('users', array('auto_login_code'=>$_COOKIE['admin_auto_login'], 'status'=>'active'), array('id','username'))) {
		$_SESSION['admin']['id']		= $admin['id'];
		$_SESSION['admin']['username']	= $admin['username'];

	// Aksi hâlde çerezi sil.
	} else {
		setcookie('admin_auto_login', '', time() - 3600, '/');
	}
	unset($admin);

}

//-------------------------------------------------------------------------------------------------
// Ayrıştırılmış adres ile sayfa dizinlerini eşleştir ve işle.
//-------------------------------------------------------------------------------------------------
$outline		= 'landing';
$content		= '';
$exception		= null;
$page_directory	= $_SERVER['PROJECT_DIRECTORY'].'pages/';


try {
	$page			= new page($page_directory, '/');
	$page->check();
	
	$current_url_part_index	= 0;
	foreach ($url_parts as $current_url_part) {
	
		if (is_dir($page_directory.$current_url_part.'/')) {
			$page_directory	= $page_directory.$current_url_part.'/';
	
		} elseif (is_dir($page_directory.'__variant/')) {
			$page_directory	= $page_directory.'__variant/';
	
		} else {
			throw new exception('PAGE_NOT_FOUND');
		}
	
		$page = new page($page_directory, '/'.implode('/', array_slice($url_parts, 0, $current_url_part_index+1)).'/');
		$page->check();
	
		$current_url_part_index	++;
	}

	// Sayfayı çalıştır.
	$content	= $page();

} catch (exception $exception) {}




//-------------------------------------------------------------------------------------------------
// İstisnaları kontrol et ve işle.
//-------------------------------------------------------------------------------------------------
if ($exception) {
	$outline	= 'error';

	switch ($exception->getMessage()) {
		case 'PAGE_NOT_FOUND':
		case 'ENTRY_NOT_FOUND':
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);
			$tpl	= new template($_SERVER['PROJECT_DIRECTORY'].'templates/404.html');
			
		break;
		default:
			header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
			$tpl	= new template($_SERVER['PROJECT_DIRECTORY'].'templates/500.html');

		break;
	}
	$tpl->parse('main');
	$content	= $tpl->text('main');
	unset($tpl);
}

if(@$_POST['action']==='systemUpdate' && $_SESSION['member']['id']){ 

	$dbo->update('settings', array('value'=>$_POST['value']), ' name=\'system_working\'');
	
	echo "200";
	exit();
} else if(@$_POST['action'] === 'botLogs' && $_SESSION['member']['id']){ 

	$entries1 = $dbo->execute("SELECT MAX(id) AS 'last_id' FROM activity_logs ORDER BY id DESC");
		//print_r($entries[0]);

		if(@$_POST['last_id'] > 0 ){
			$last_id = (int)$_POST['last_id'];
		} else {
			$last_id = (int)$entries1[0]['last_id'];
		}
		
		$out['last_id'] = $last_id;
		

		//"SELECT * FROM activity_logs WHERE id > '"+LAST_UPDATE_TIME+"' ORDER BY id ASC"

		$entries = $dbo->execute("SELECT * FROM activity_logs WHERE id > '".$last_id."' ORDER BY id DESC");

		//echo sizeof($entries)."\n\n";

		if (sizeof($entries)) {
			$i	= 0;
			foreach ($entries as $entry) {

				$out['data'][$i]['id']					= $entry['id'];
				$out['data'][$i]['type']				= $entry['type'];
				$out['data'][$i]['log_text']			= $entry['log_text'];
				$out['data'][$i]['created_time']		= date('d-m-Y H:i:s', $entry['created_time']);

				$i++;
			}
			$out['last_id'] = $entry['id'];
		}

		echo json_encode($out);
		exit();

}



//-------------------------------------------------------------------------------------------------
// Dış sayfa tasarımını ve sayfayı çıktıla.
//-------------------------------------------------------------------------------------------------
header('Content-type:text/html; charset=UTF-8');
$outline_tpl	= new template('outlines/'.$outline.'.html');

switch ($outline) {
	case 'landing':

		if ($_SESSION['member']['id']) {
			$outline_tpl->assign('username', $_SESSION['member']['username']);
			$outline_tpl->assign('user_id', $_SESSION['member']['id']);
			

			/*** Huseyin ek ****/

			$entries	= $dbo->execute('SELECT value FROM settings WHERE  name=\'system_working\'');
			//print_r($entries[0]['value']);

			$system_shalter_checked = '';
			if($entries[0]['value']){
				$system_shalter_checked = 'checked';
			}
			
			$outline_tpl->assign('system_shalter_checked', $system_shalter_checked);
			
			$outline_tpl->parse('main.topnav');
			$outline_tpl->parse('main.userpanel');

			/****************/
			$outline_tpl->parse('main.member');
		} else {

			header ('Location: '.$_SERVER['BASE_PATH'].'/uye-girisi');
			exit;
			/*** Huseyin ek ****/

			// {BASE_PATH}/uye-kayit
			//$outline_tpl->parse('main.topnav_guest');

			/****************/

			$outline_tpl->parse('main.guest');
		}

	break;
	case 'member':

		$outline_tpl->assign('username', $_SESSION['member']['username']);

	break;
	case 'admin':

		$outline_tpl->assign('username', $_SESSION['admin']['username']);

	break;
}

if (!$exception) {
	$outline_tpl->assign('title', $page->title);

	// META
	if (sizeof($page->meta)) {
		$meta	= '';
		foreach ($page->meta as $meta_item) {
			$meta	.= '<meta ';
			foreach ($meta_item as $key => $val) {
				$meta	.= $key.'="'.xml_encode($val).'" ';
			}
			$meta	.= '/>'."\n";
		}
		$outline_tpl->assign('meta', $meta);
		unset($meta);
	}

	// LINKS (CSS, ALTERNATE ...)
	if (sizeof($page->links)) {
		$links	= '';
		foreach ($page->links as $link_item) {
			$links	.= '<link ';
			foreach ($link_item as $key => $val) {
				$links	.= $key.'="'.xml_encode(str_replace('{BASE_PATH}', $_SERVER['BASE_PATH'], $val)).'" ';
			}
			$links	.= '/>'."\n";
		}
		$outline_tpl->assign('links', $links);
		unset($links);
	}

	// SCRIPT FILES
	if (sizeof($page->scripts)) {
		$scripts	= '';
		foreach ($page->scripts as $script_item) {
			$scripts	.= '<script ';
			foreach ($script_item as $key => $val) {
				$scripts	.= $key.'="'.xml_encode(str_replace('{BASE_PATH}', $_SERVER['BASE_PATH'], $val)).'" ';
			}
			$scripts	.= '></script>'."\n";
		}
		$outline_tpl->assign('scripts', $scripts);
		unset($scripts);
	}

	// EVENTS
	$events	= get_events();
	if (sizeof($events)) {
		foreach ($events as $event) {
			$outline_tpl->assign('message', escape($event['message']));
			$outline_tpl->assign('type', $event['type']);
			$outline_tpl->parse('main.events.event');
		}
		unset($events, $event);
		$outline_tpl->parse('main.events');
	}

	// Menu Countries

	//print_r($_countries);

	/* Huseyin EK */

	/*

	$_countries  = get_matches();

	if (sizeof($_countries)) {

	for ($i=0; $i < sizeof($_countries); $i++) { 
		
		@$outline_tpl->assign('c_short', $_countries[$i]->cc->a2 == 'en' ? 'gb': $_countries[$i]->cc->a2);
		$outline_tpl->assign('c_name', $_countries[$i]->name);

		for ($k=0; $k < sizeof($_countries[$i]->tournaments); $k++) { 
			# code...
			$outline_tpl->assign('t_name', $_countries[$i]->tournaments[$k]->name);
			$outline_tpl->parse('main.countries.tournaments');
			}


			$outline_tpl->parse('main.countries');
		}

	}

	*/

	/*    Huseyin Ek sonu ****************/
}

$outline_tpl->assign('content', $content);
$outline_tpl->parse('main');
echo $outline_tpl->text('main');

// Üye oturumu açıksa, online üyeler tablosuna ekle ya da güncelle.
if ($_SESSION['member']['id']) {
	$dbo->insert('online_members', array('id'=>$_SESSION['member']['id'], 'last_activity'=>date('Y-m-d H:i:s')), array('last_activity'));
}

// GEÇİCİ (buradan kaldırılacak, test amaçlı)
$dbo->execute('DELETE FROM online_members WHERE last_activity < \''.date('Y-m-d H:i:s', time() - 180).'\'');

?>