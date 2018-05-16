<?php
// capture the screen
sleep(2);
$img = imagegrabscreen();
imagepng($img, 'C:\xampp\htdocs\assets\images\screenshot.png');
?>