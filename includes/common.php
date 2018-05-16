<?php

// Insert the path where you unpacked log4php
include('../../log4php/Logger.php');

// Tell log4php to use our configuration file.
Logger::configure('../../log4php/config.xml');
 
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

//-------------------------------------------------------------------------------------------------
function write_log_to_file($log_string, $type='DEBUG') {
    // Fetch a logger, it will inherit settings from the root logger
    $forex_army_logger = Logger::getLogger('forexarmy');
    echo $log_string;
    if( $type == 'TRACE'){
        $forex_army_logger->trace($log_string);
    } else if( $type == 'DEBUG'){
        $forex_army_logger->debug($log_string);
    } else if( $type == 'INFO'){
        $forex_army_logger->info($log_string);
    } else if( $type == 'WARN'){
        $forex_army_logger->warn($log_string);
    } else if( $type == 'ERROR'){
        $forex_army_logger->error($log_string);
    } else if( $type == 'FATAL'){
        $forex_army_logger->fatal($log_string);
    } 
}
//-------------------------------------------------------------------------------------------------


function sendEmail($bot, $status){
///send email code
$mail = new PHPMailer(true);  
try {
                                   
    $mail->isSMTP();    
    $mail->Host = 'smtp.gmail.com';          
    $mail->SMTPAuth =true;                            
    $mail->Username = 'vishal.test123456@gmail.com';                 
    $mail->Password = 'vishal987654';                          
    $mail->SMTPSecure = 'tls';                          
    $mail->Port = 587;
                                      
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $botid = $bot['id'];
    $botemail = $bot['email'];
    $password = $bot['password'];
    $FirstName = $bot['first_name'];
    $LastName = $bot['last_name'];
	$mail->setFrom('vishal.test123456@gmail.com','Vishal');
	$mail->addAddress('vishal.test123456@gmail.com', 'Trading View Bot Manager');     // Add a recipient
    //$mail->AddCC('vishal.kumar1145@gmail.com', 'Trading View Bot Manager');
	$mail->AddCC('aksingh@ithours.com', 'Trading View Bot Manager');
	$mail->isHTML(true);                                  
    $mail->Subject = $status.'| Registration for email - '.$botemail.' | '.$password;
    $mail->Body    = '<table> <tr><td>Bot ID</td><td>'.$botid .'</td></tr><tr><td>Email</td><td>'.$botemail .'</td></tr><tr><td>Password</td><td>'.$password .'</td></tr><tr><td>first Name</td><td>'.$FirstName .'</td></tr><tr><td>Last Name</td><td>'.$LastName .'</td></tr></table>';
    
    try
    {
       $mail->send();
       //write_log_to_file('send function running successfully ');
       echo "success";
    }
    catch (Exception $e) {
       //write_log_to_file ('Message could not be sent. Mailer Error 123: ', $mail->ErrorInfo);
       echo "not success";
    }

	} catch (Exception $e) {
        //write_log_to_file ('Message could not be sent. Mailer Error: ', $mail->ErrorInfo);
        echo "not success";

    }
}




?>