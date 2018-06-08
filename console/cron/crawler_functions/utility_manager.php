<?php
function botInsertion($bot)
{

    $dbo        = get_dbo();
           $sql = "INSERT INTO siteBots (first_name, last_name,password,username,email,proxy_id,country_code,created_time,status,SessionId)
        VALUES ('".$bot['first_name']."', '".$bot['last_name']."','".$bot['password']."','".$bot['username']."','".$bot['email']."', '".$bot['proxy_id']."', '".$bot['country_code']."','".$bot['created_time']."', '".$bot['status']."','".$bot['id']."')";
         
         echo $sql;
         $row_inserted = $dbo->execute($sql);
         echo "Bot data inserted";

         

}
?>