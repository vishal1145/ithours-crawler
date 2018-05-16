<?php

/*
    Sisteme eklenmiş yazarlara ait yeni makale olup olmadığını kontrol eder.
    Yeni makale bulunduğunda veritabanına kaydeder.
    Like, comment ve visit görevlerini oluşturur.
    Günde birkaç sefer otomatik olarak çalıştırılabilir.
*/

chdir(__DIR__);
set_time_limit(0);
ini_set('display_errors',   true);
error_reporting(E_ALL);
ob_implicit_flush(true);
mb_internal_encoding('UTF-8');

require_once('../../includes/config.php');
require_once('../../includes/prepend.php');
require_once '../../includes/common.php';
require_once '../../includes/class/simple_html_dom.php';
require_once '../../includes/class/phpwebdriver/WebDriver.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

$dbo        = get_dbo();
$settings   = get_settings();
$html       = new simple_html_dom();


if (!$settings['system_working']) {
    exit;
}

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function

write_log_to_file('starting smtp connection ');


$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
try {
    //Server settings
    //$mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->isSMTP();    
                                      // Set mailer to use SMTP
    $mail->Host = $config['MAILSETTINGS']['HOST'];                  // Specify main and backup SMTP servers
    $mail->SMTPAuth =true;                            // Enable SMTP authentication
    $mail->Username = $config['MAILSETTINGS']['UserName'];                 // SMTP username
    $mail->Password = $config['MAILSETTINGS']['Password'];                           // SMTP password
    $mail->SMTPSecure = 'tls';                           // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587;
                                       // TCP port to connect to
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    

    $mail->setFrom($config['MAILSETTINGS']['Set_From_Email'],$config['MAILSETTINGS']['Set_From_DisplayName']);
    write_log_to_file('after sender address  ');
    $mail->addAddress(' vishal.test123456@gmail.com', 'Trading View Bot Manager'); 
    $mail->AddCC('vishal.kumar1145@gmail.com', 'Trading View Bot Manager');    // Add a recipient
    write_log_to_file('after receiver address  ');
    
    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Oops! Something went wrong | Server - '.$config['ServerIP'];
   
   
    

    $start_time = time()-900;
    $current_time = time();
    $current_running_task_query ='select count(*) as cnt from tasks where status = "RUNNING" AND completed_time >= '.$start_time.'  AND completed_time <= '.$current_time;
    //$current_running_task_query ='select count(*) as cnt from tasks where status = "RUNNING" ';
    $running_task_activity  = $dbo->execute($current_running_task_query); 
    echo $current_running_task_query;
    
    
    
    

    if(($running_task_activity[0]['cnt']) > 5)
    {
     $mail->Body    = 'Services are not working properly on this server. please look into it</b>'; 
     $mail->send(); 
     write_log_to_file('there are more than 5 tasks in running status ');
    
    
    
    }
    else
    {
       $completed_task_query ='select count(*) as cnt from tasks where status = "COMPLETED" AND completed_time >= '.$start_time.'  AND completed_time <= '.$current_time;
       $completed_task_activity  = $dbo->execute($completed_task_query); 
       echo $completed_task_query;
       
       

       if(($completed_task_activity[0]['cnt']) <= 10)
       {
         $mail->Body    = 'Services are not working properly on this server. please look into it</b>'; 
         $mail->send(); 
         write_log_to_file('there are less than 10 completed services ');
         
         
    
       }
    


    }
    

    } catch (Exception $e) {
        write_log_to_file ('Message could not be sent. Mailer Error: ', $mail->ErrorInfo);
    }

    
  
?>