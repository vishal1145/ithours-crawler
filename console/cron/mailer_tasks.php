<?php



chdir(__DIR__);
set_time_limit(0);
ini_set('display_errors', true);
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

$dbo    = get_dbo();
$settings = get_settings();
$html   = new simple_html_dom();


if (!$settings['system_working']) {
  exit;
}
echo "strithfg jgng";
    sleep(2);

write_log_to_file('starting smtp connection ');


$mail = new PHPMailer(true);                              
try {
                                   
    $mail->isSMTP();    
                                      
    $mail->Host = $config['MAILSETTINGS']['HOST'];          
    $mail->SMTPAuth =true;                            
    $mail->Username = $config['MAILSETTINGS']['UserName'];                 
    $mail->Password = $config['MAILSETTINGS']['Password'];                          
    $mail->SMTPSecure = 'tls';                          
    $mail->Port = 587;
                                      
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
     
   $start_time = time()-3600;
     $current_time = time();
    $execution_summary_activity_query ='select type, status,count(*) as cnt from tasks      where completed_time >= '.$start_time.' AND completed_time <= '.$current_time.' group by type, status      union      select "new articles","NA" ,count(1) as cnt      from articles art          where art.created_on >= '.$start_time.' AND art.created_on <= '.$current_time;   


    echo ($execution_summary_activity_query);
    sleep(5);
 
    $execution_summary_activity = $dbo->execute($execution_summary_activity_query);

    $spreadsheet = new Spreadsheet();
    
    $summarySheet = new Worksheet($spreadsheet, 'Execution Summary');
    $spreadsheet->addSheet($summarySheet, 0);
    $spreadsheet->setActiveSheetIndex(0);

    $summarySheet->setCellValue('A1', 'TYPE'); 
    $summarySheet->setCellValue('B1' , 'STATUS');
    $summarySheet->setCellValue('C1', 'COUNT');
    for ($i = 0; $i < sizeof($execution_summary_activity); $i++){
       $summarySheet->setCellValue('A'.(string)($i + 2) , $execution_summary_activity[$i]['type']); 
       $summarySheet->setCellValue('B' . (string)($i + 2), $execution_summary_activity[$i]['status']);
       $summarySheet->setCellValue('C' . (string)($i + 2), $execution_summary_activity[$i]['cnt']);
    }


    $article_likes_activity_query ='select art.link as art_link, art.currency as art_currency, art.position as art_position, aut.name as art_name, bot.email as bot_email, bot.first_name as bot_first_name, bot.last_name as bot_last_name from article_likes lik
    inner join articles art on art.id = lik.article_id
    inner join authors aut on aut.id = lik.author_id
    inner join bots bot on bot.id = lik.bot_id where lik.created_on >= NOW() - INTERVAL 1 HOUR';

    $article_likes_activity = $dbo->execute($article_likes_activity_query);
    

   $likeSheet = new Worksheet($spreadsheet, 'Article Likes');
   $spreadsheet->addSheet($likeSheet, 1);
   $likeSheet = $spreadsheet->setActiveSheetIndex(1);

    $likeSheet->setCellValue('A1', 'Article Link'); 
    $likeSheet->setCellValue('B1' , 'CURRENCY');
    $likeSheet->setCellValue('C1' , 'POSITION');
    $likeSheet->setCellValue('D1' , 'Author Name');
    $likeSheet->setCellValue('E1' , 'EMAIL');
    $likeSheet->setCellValue('F1' , 'Bot First Nam');
    $likeSheet->setCellValue('G1' , 'Bot Last Name');
    
    for ($i = 0; $i < sizeof($article_likes_activity); $i++){
       $likeSheet->setCellValue('A'.(string)($i + 2) , 'https://www.tradingview.com'.$article_likes_activity[$i]['art_link']);
       $url = str_replace('http://', '','tradingview.com'.$article_likes_activity[$i]['art_link']);
       $likeSheet->getCellByColumnAndRow(1,$i)->getHyperlink()->setUrl('http://www.'.$url);
       $likeSheet->setCellValue('B' . (string)($i + 2), $article_likes_activity[$i]['art_currency']);
       $likeSheet->setCellValue('C' . (string)($i + 2), $article_likes_activity[$i]['art_position']);
       $likeSheet->setCellValue('D' . (string)($i + 2), $article_likes_activity[$i]['art_name']);
       $likeSheet->setCellValue('E' . (string)($i + 2), $article_likes_activity[$i]['bot_email']);
       $likeSheet->setCellValue('F' . (string)($i + 2), $article_likes_activity[$i]['bot_first_name']);
       $likeSheet->setCellValue('G' . (string)($i + 2), $article_likes_activity[$i]['bot_last_name']);
   }
    

     $article_comments_activity_query ='select art.link as art_link, art.currency as art_currency, art.position as art_position, aut.name as art_name, bot.email as bot_email, bot.first_name as bot_first_name, bot.last_name as bot_last_name,cmt.comment as article_comment from article_comments cmt
    inner join articles art on art.id = cmt.article_id
    inner join authors aut on aut.id = cmt.author_id
    inner join bots bot on bot.id = cmt.bot_id where cmt.created_on >= NOW() - INTERVAL 1 HOUR';


    $article_comment_activity = $dbo->execute($article_comments_activity_query);
    
    
   $CommentSheet = new Worksheet($spreadsheet, 'Article Comments');
   $spreadsheet->addSheet($CommentSheet, 2);
   $CommentSheet = $spreadsheet->setActiveSheetIndex(2);

    $CommentSheet->setCellValue('A1', 'Article Link'); 
    $CommentSheet->setCellValue('B1' , 'CURRENCY');
    $CommentSheet->setCellValue('C1' , 'POSITION');
    $CommentSheet->setCellValue('D1' , 'Author Name');
    $CommentSheet->setCellValue('E1' , 'EMAIL');
    $CommentSheet->setCellValue('F1' , 'Bot First Name');
    $CommentSheet->setCellValue('G1' , 'Bot Last Name');
    $CommentSheet->setCellValue('H1' , 'ARTICLE COMMENTS');
    
    for ($i = 0; $i < sizeof($article_comment_activity); $i++){
       $CommentSheet->setCellValue('A'.(string)($i + 2) ,'https://www.tradingview.com'.$article_comment_activity[$i]['art_link']); 
       $url = str_replace('http://', '','tradingview.com'.$article_comment_activity[$i]['art_link']);
       $CommentSheet->getCellByColumnAndRow(1,$i)->getHyperlink()->setUrl('http://www.'.$url);
       $CommentSheet->setCellValue('B' . (string)($i + 2), $article_comment_activity[$i]['art_currency']);
       $CommentSheet->setCellValue('C' . (string)($i + 2), $article_comment_activity[$i]['art_position']);
       $CommentSheet->setCellValue('D' . (string)($i + 2), $article_comment_activity[$i]['art_name']);
       $CommentSheet->setCellValue('E' . (string)($i + 2), $article_comment_activity[$i]['bot_email']);
       $CommentSheet->setCellValue('F' . (string)($i + 2), $article_comment_activity[$i]['bot_first_name']);
       $CommentSheet->setCellValue('G' . (string)($i + 2), $article_comment_activity[$i]['bot_last_name']);
       $CommentSheet->setCellValue('H' . (string)($i + 2), $article_comment_activity[$i]['article_comment']);
   }
   
   
    $new_article_activity_query ='select auth.name as auth_name,art.currency as art_currency,art.position as art_position,
    art.target_likes as art_target_likes,art.target_comments as art_target_comments,
    art.target_views as art_target_views,art.link as art_link from articles art
    inner join authors auth 
    on auth.id=art.author_id
    where art.created_on >= NOW() - INTERVAL 1 HOUR';

    $new_article_activity = $dbo->execute($new_article_activity_query);

   $NewArticleSheet = new Worksheet($spreadsheet, 'New Articles');
   $spreadsheet->addSheet($NewArticleSheet, 3);
   $NewArticleSheet = $spreadsheet->setActiveSheetIndex(3);

    $NewArticleSheet->setCellValue('A1', 'Author Name'); 
    $NewArticleSheet->setCellValue('B1' , 'CURRENCY');
    $NewArticleSheet->setCellValue('C1' , 'POSITION');
    $NewArticleSheet->setCellValue('D1' , 'TARGET LIKES');
    $NewArticleSheet->setCellValue('E1' , 'TARGET COMMENTS');
    $NewArticleSheet->setCellValue('F1' , 'TARGET VIEWS');
    $NewArticleSheet->setCellValue('G1' , 'Article Link');
   
    
    for ($i = 0; $i < sizeof($new_article_activity); $i++){
       $NewArticleSheet->setCellValue('A'.(string)($i + 2) , $new_article_activity[$i]['auth_name']); 
       $NewArticleSheet->setCellValue('B' . (string)($i + 2), $new_article_activity[$i]['art_currency']);
       $NewArticleSheet->setCellValue('C' . (string)($i + 2), $new_article_activity[$i]['art_position']);
       $NewArticleSheet->setCellValue('D' . (string)($i + 2), $new_article_activity[$i]['art_target_likes']);
       $NewArticleSheet->setCellValue('E' . (string)($i + 2), $new_article_activity[$i]['art_target_comments']);
       $NewArticleSheet->setCellValue('F' . (string)($i + 2), $new_article_activity[$i]['art_target_views']);
       $NewArticleSheet->setCellValue('G' . (string)($i + 2), 'https://www.tradingview.com'.$new_article_activity[$i]['art_link']);
       $url = str_replace('http://', '','tradingview.com'.$new_article_activity[$i]['art_link']);
       $NewArticleSheet->getCellByColumnAndRow(7,$i)->getHyperlink()->setUrl('http://www.'.$url);
       
   }



    $writer = new Xlsx($spreadsheet);

   
    $filename ='C:/TradingViewFile'.'/'.date("j F").' '.date("h-i A").'.xlsx';

     $writer->save($filename );


  
    //Send the email with attahcment

    //Recipients
    
    $mail->setFrom($config['MAILSETTINGS']['Set_From_Email'],$config['MAILSETTINGS']['Set_From_DisplayName']);
    write_log_to_file('after sender address  ');
    $mail->addAddress('vishal.test123456@gmail.com', 'Trading View Bot Manager');     // Add a recipient
    $mail->AddCC('vishal.kumar1145@gmail.com', 'Trading View Bot Manager'); 
    write_log_to_file('after receiver address  ');

     //Attachments
    $mail->addAttachment($filename);         // Add attachments
    write_log_to_file('after mail attachment ');
   
      //Content
    $mail->isHTML(true);                                  
    $mail->Subject = 'Trading View Bot Activity | Server - '.$config['ServerIP'];
    $mail->Body    = 'Please find the attached bot execution</b>';
    write_log_to_file('after subject and body details sending mail  ');
    
    try
    {
       $mail->send();
       write_log_to_file('send function running successfully ');
    }
    catch (Exception $e) {
       write_log_to_file ('Message could not be sent. Mailer Error 123: ', $mail->ErrorInfo);
    }

    write_log_to_file('after sending mail  ');
   write_log_to_file ('We should start getting email');
    } catch (Exception $e) {
        write_log_to_file ('Message could not be sent. Mailer Error: ', $mail->ErrorInfo);
    }

   
?>