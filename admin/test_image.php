<?php
require '../vendor/autoload.php';

use Intervention\Image\ImageManagerStatic as Image;
$image = Image::make('../images/1_1700223010_65575822cbaff.jpeg')->resize(300, 200);
$image->save('../images/output9.jpg');
echo 'Image processed successfully!';
