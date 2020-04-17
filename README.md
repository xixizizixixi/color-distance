

```php
//based on : https://github.com/danmichaelo/php-coma
//wiki : https://en.wikipedia.org/wiki/Color_difference

use Danmichaelo\Coma\ColorDistance;
use Danmichaelo\Coma\sRGB;

$color1 = new sRGB(255, 0, 0); //OR $color1 = new sRGB('#ff0000');
$color2 = new sRGB(0, 0, 255); //OR $color2 = new sRGB('#0000ff');


$colorDistance = new ColorDistance();

//CIE94 
$cie94 = $colorDistance->cie94($color1, $color2);

//CIEDE2000
$ciede2000 = $colorDistance->ciede2000($color1, $color2);



```
