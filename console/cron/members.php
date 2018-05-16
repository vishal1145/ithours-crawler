<?php
chdir(__DIR__);

require_once('../../includes/config.php');
require_once('../../includes/prepend.php');

$dbo	= get_dbo();


//---------------------------------------------------------------------------------------
// Son etkinliği 20 dakikadan daha eski olan üyeleri online üyeler tablosundan sil.
//---------------------------------------------------------------------------------------
$dbo->execute('DELETE FROM online_members WHERE last_activity < \''.date('Y-m-d H:i:s', time() - 60 * 20).'\'');



//-----------------------------------------------------------------------------
// E-posta aktivasyonu tamamlanmamış üyeleri veritabanından sil.
//-----------------------------------------------------------------------------
$members	= $dbo->execute('SELECT `id`,`email_activation_code` FROM `members` WHERE `status`=\'waiting_activation\'');

foreach ($members as $member) {
	$timeout	= substr($member['email_activation_code'], 41);

	if ($timeout < time()) {
		$dbo->delete('members', $member['id']);
	}
}


//-----------------------------------------------------------------------------
// Süresi dolmuş şifre resetleme kodlarını temizle.
//-----------------------------------------------------------------------------
$members	= $dbo->execute('SELECT `id`,`password_reset_code` FROM `members` WHERE `password_reset_code` != \'\'');

foreach ($members as $member) {
	$timeout	= substr($member['password_reset_code'], 41);

	if ($timeout < time()) {
		$dbo->update('members', array('password_reset_code'=>''), $member['id']);
	}
}

//-----------------------------------------------------------------------------
// Süresi dolmuş e-posta değiştirme kodlarını temizle.
//-----------------------------------------------------------------------------
$members	= $dbo->execute('SELECT `id`,`email_activation_code` FROM `members` WHERE `email_activation_code` != \'\' AND `status` != \'waiting_activation\'');

foreach ($members as $member) {
	$timeout	= substr($member['email_activation_code'], 41);

	if ($timeout < time()) {
		$dbo->update('members', array('email_activation_code'=>''), $member['id']);
	}
}


?>