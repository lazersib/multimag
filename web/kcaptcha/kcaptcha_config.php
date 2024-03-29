<?php

# KCAPTCHA configuration file

$alphabet = "0123456789abcdefghijklmnopqrstuvwxyz"; # do not change without changing font files!

# symbols used to draw CAPTCHA
//$allowed_symbols = "0123456789"; #digits
//$allowed_symbols = "23456789abcdegkmnpqsuvxyz"; #alphabet without similar symbols (o=0, 1=l, i=j, t=f)
$allowed_symbols = "23456789abcdegikpqsvxyz"; #alphabet without similar symbols (o=0, 1=l, i=j, t=f)

# folder with fonts
$fontsdir = 'fonts';	

# CAPTCHA string length
$length = mt_rand(6,8); # random 5 or 6 or 7
//$length = 6;

# CAPTCHA image size (you do not need to change it, this parameters is optimal)
$width = 180;
$height = 60;

# symbol's vertical fluctuation amplitude
$fluctuation_amplitude = 9;

#noise
//$white_noise_density=0; // no white noise
$white_noise_density=1/5;
//$black_noise_density=0; // no black noise
$black_noise_density=1/10;

# increase safety by prevention of spaces between symbols
$no_spaces = true;

# show credits
$show_credits = false; # set to false to remove credits line. Credits adds 12 pixels to image height
$credits = 'www.captcha.ru'; # if empty, HTTP_HOST will be shown

# CAPTCHA image colors (RGB, 0-255)
//$foreground_color = array(0, 0, 0);
//$background_color = array(220, 230, 255);
$foreground_color = array(mt_rand(100,150), mt_rand(100,150), mt_rand(100,150));
$background_color = array(mt_rand(240,255), mt_rand(240,255), mt_rand(240,255));


# JPEG quality of CAPTCHA image (bigger is better quality, but larger file size)
$jpeg_quality = 75;
