<?php
 $dbo        = get_dbo();
write_log_to_file('Registering a Bot...'."\n");

// Rastgele bir proxy seç.
$proxy			= $dbo->execute('SELECT * FROM proxies ORDER BY rand() LIMIT 1');
$proxy			= $proxy[0];
$country_code	= $proxy['country_code'];

//-------------------------------------------------------------------------------------------------------------
// Yeni üretilecek bot için ad ve soyad bul.
//-------------------------------------------------------------------------------------------------------------
while (true) {

    // Proxy ülke koduna göre rastgele ad bulmaya çalış.
    $first_name	= $dbo->execute('SELECT * FROM first_names WHERE country_code=\''.$country_code.'\' ORDER BY rand() LIMIT 1');

    // Bu ülke için ad yoksa, global (*) adlar arasından seç.
    if (!sizeof($first_name))
        $first_name	= $dbo->execute('SELECT * FROM first_names WHERE country_code=\'*\' ORDER BY rand() LIMIT 1');

    // Proxy ülke koduna göre rastgele soyad bulmaya çalış.
    $last_name	= $dbo->execute('SELECT * FROM last_names WHERE country_code=\''.$country_code.'\' ORDER BY rand() LIMIT 1');

    // Bu ülke için soyad yoksa, global (*) soyadlar arasından seç.
    if (!sizeof($last_name))
        $last_name	= $dbo->execute('SELECT * FROM last_names WHERE country_code=\'*\' ORDER BY rand() LIMIT 1');

    $first_name	= $first_name[0]['name'];
    $last_name	= $last_name[0]['name'];

    // Bu ad soyad ikilisi herhangi bir bot için daha önce kullanılmış mı kontrol et.
    if (!$dbo->exists('bots', array('first_name'=>$first_name, 'last_name'=>$last_name)))
        break;

    // Eğer bir bot tarafından kullanılmışsa, bu sefer yalnızca global isimler üzerinden dene.
    $first_name	= $dbo->execute('SELECT * FROM first_names WHERE country_code=\'*\' ORDER BY rand() LIMIT 1');
    $last_name	= $dbo->execute('SELECT * FROM last_names WHERE country_code=\'*\' ORDER BY rand() LIMIT 1');
    $first_name	= $first_name[0]['name'];
    $last_name	= $last_name[0]['name'];

    // Bu ad soyad ikilisi herhangi bir bot için daha önce kullanılmış mı kontrol et.
    if (!$dbo->exists('bots', array('first_name'=>$first_name, 'last_name'=>$last_name)))
        break;

}
    //-------------------------------------------------------------------------------------------------------------
		// Bot bilgilerini oluştur.
		//-------------------------------------------------------------------------------------------------------------
		$bot = array
		(
			'email'				=> '',
			'username'			=> '',
			'password'			=> generate_key(rand(11, 16)),
			'first_name'		=> $first_name,
			'last_name'			=> $last_name,
			'proxy_id'			=> $proxy['id'],
			'country_code'		=> $country_code,
			'target_followers'	=> rand(2, 10),				// Bu botu takip edecek bot sayısı. (2 ile 10 arasında rasgele)
			'current_followers'	=> 0,
			'created_time'		=> date('Y-m-d H:i:s'),
			'status'			=> 'creating'
		);

		// Botu veritabanına kaydet.
		$bot['id']	= $dbo->insert('bots', $bot);

		//print_r($bot);

		//-------------------------------------------------------------------------------------------------------------
		// Chrome multipass eklenti ayarlarını yapılandır.
		//-------------------------------------------------------------------------------------------------------------

		$webdriver	= new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
		$webdriver->connect('chrome', '', array
		(
			'chromeOptions'=>array('args'=>array('user-data-dir='.$GLOBALS['config']['bots_user_data_dir'].$bot['id'].'/','disable-infobars','ignore-certificate-errors','disable-notifications','load-extension='.$GLOBALS['config']['chrome_multipass_extension_dir']))
		));

		$webdriver->windowMaximize();
		$webdriver->setImplicitWaitTimeout(10000);
		$webdriver->setSpeed('SLOW');

		$webdriver->get('chrome-extension://enhldmjbphoeibbpdhmjkchohnidgnah/options.html');
		sleep(3);

		$url_id_value=".*";
		$webdriver->executeScript('document.getElementById("url").value="'.$url_id_value.'";', array());

		$username_id_value=$GLOBALS['config']['proxy']['username'];
		$webdriver->executeScript('document.getElementById("username").value="'.$username_id_value.'";', array());

		$password_id_value=$GLOBALS['config']['proxy']['password'];
		$webdriver->executeScript('document.getElementById("password").value="'.$password_id_value.'";', array());

		sleep(2);

		$element	= $webdriver->findElementBy(LocatorStrategy::id, 'analytics-enabled');
		$element->click();

		$element	= $webdriver->findElementBy(LocatorStrategy::cssSelector, '.credential-form-submit');
		$element->click();

		// Değişikliklerin etkin olması için pencereyi kapat.
		$webdriver->closeWindow();
		sleep(2);
		$webdriver->close();
        sleep(1);
        
		// Yeni proxy ayarlarıyla yeni bir Chrome bağlantısı aç.
		//-------------------------------------------------------------------------------------------------------------
		$webdriver	= new WebDriver($GLOBALS['config']['webdriver']['host'], $GLOBALS['config']['webdriver']['port']);
		$webdriver->connect('chrome', '', array
		(
			'proxy'=>array
			(
				'proxyType'		=> 'manual',
				'httpProxy'		=> $proxy['domain'].':'.$proxy['http_port'],
				'sslProxy'		=> $proxy['domain'].':'.$proxy['http_port']
			),
			'chromeOptions'=>array('args'=>array('user-data-dir='.$GLOBALS['config']['bots_user_data_dir'].$bot['id'].'/','disable-infobars','ignore-certificate-errors','disable-notifications','load-extension='.$GLOBALS['config']['chrome_multipass_extension_dir']))
		));

		$webdriver->windowMaximize();
		$webdriver->setImplicitWaitTimeout(3000);
		$webdriver->setSpeed('SLOW');
        sleep(2);
        
        
			write_log_to_file('Creating temp-mail.org account...'."\n");

			// mytemp.email için çağrı yap.
			$webdriver->get('https://yoast.com/wp-signup.php');

			// input#mail görünene kadar bekle.
			try {
				$bot['email']	= wait_until(function() use ($webdriver) {
					$mail	= $webdriver->findElementsBy(LocatorStrategy::cssSelector, 'input#mail');
					if (sizeof($mail)) {
						return $mail[0]->getAttribute('value');
					}
				});
			} catch (exception $e) {
				// E-posta adresi oluşturulamadı. Bot işlemlerini iptal et, yeniden denensin.
				$dbo->delete('bots', $bot['id']);

				$webdriver->closeWindow();
				sleep(2);
				$webdriver->close();
				sleep(1);

				continue;
			}

			// Botun kullanıcı adını e-posta adresinden türet.
			$bot['username']	= substr($bot['email'], 0, strpos($bot['email'], '@')).generate_username(rand($settings['bot_username_min'], $settings['bot_username_max']));

			// Botun veritabanı bilgilerini güncelle.
			$dbo->update('bots', array('username'=>$bot['username'], 'email'=>$bot['email']), $bot['id']);

            write_log_to_file('Created temp-email.org account: ('.$bot['email'].")\n");
            
            
		//-------------------------------------------------------------------------------------------------------------
		// Botu TradingView sitesine kayıt yap.
		//-------------------------------------------------------------------------------------------------------------
		write_log_to_file('Registering the bot...'."\n");
		sleep(2);
		 
		$bot['email'] ="raedyn.arhaan@its0k.com";
		$bot['username'] ="raedynarhaan";
		
		 $webdriver->get('https://www.tradingview.com/#signup');
		$webdriver->executeScript('$(".js-tv-expected-language.tv-expected-language").remove();', array());

		



?>