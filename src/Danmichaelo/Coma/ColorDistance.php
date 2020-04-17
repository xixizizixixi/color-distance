<?php

namespace Danmichaelo\Coma;

/**
 * References used:
 * - https://en.wikipedia.org/wiki/Color_difference
 * - https://en.wikipedia.org/wiki/Lab_color_space
 * - https://en.wikipedia.org/wiki/SRGB_color_space
 * - https://github.com/THEjoezack/ColorMine (.NET example code)
 * - https://gist.github.com/mikelikespie/641528 (JS example code).
 */
class ColorDistance
{
    protected function toLab($color)
    {
        if ($color instanceof Lab) {
            return $color;
        }
        if ($color instanceof sRGB) {
            return $color->toLab();
        }
        throw new \Exception('color of unknown class');
        // Finnes det noe ala InvalidArgumentException?
    }

    /**
     * DeltaE calculation using the CIE76 formula.
     * Delta = 2.3 corresponds to a just noticeable difference.
     *
     * 1. assume that your RGB values are in the sRGB colorspace
     * 2. convert sRGB colors to L*a*b*
     * 3. compute deltaE between your two L*a*b* values.
     */
    public function cie76($color1, $color2)
    {
        $f1 = $this->toLab($color1);
        $f2 = $this->toLab($color2);

        $deltaL = $f2->l - $f1->l;
        $deltaA = $f2->a - $f1->a;
        $deltaB = $f2->b - $f1->b;

        $deltaE = $deltaL * $deltaL + $deltaA * $deltaA + $deltaB * $deltaB;

        return $deltaE < 0 ? 0 : sqrt($deltaE);
    }

    /**
     * DeltaE calculation using the CIE94 formula.
     * Delta = 2.3 corresponds to a just noticeable difference.
     */
    public function cie94($color1, $color2)
    {
        $Kl = 1.0;
        $K1 = .045;
        $K2 = 0.015;

        $Kc = 1.0;
        $Kh = 1.0;

        $f1 = $this->toLab($color1);
        $f2 = $this->toLab($color2);

        $deltaL = $f2->l - $f1->l;
        $deltaA = $f2->a - $f1->a;
        $deltaB = $f2->b - $f1->b;

        $c1 = sqrt($f1->a * $f1->a + $f1->b * $f1->b);
        $c2 = sqrt($f2->a * $f2->a + $f2->b * $f2->b);
        $deltaC = $c2 - $c1;

        $deltaH = $deltaA * $deltaA + $deltaB * $deltaB - $deltaC * $deltaC;
        $deltaH = $deltaH < 0 ? 0 : sqrt($deltaH);

        $Sl = 1.0;
        $Sc = 1 + $K1 * $c1;
        $Sh = 1 + $K2 * $c1;

        $deltaLKlsl = $deltaL / ($Kl * $Sl);
        $deltaCkcsc = $deltaC / ($Kc * $Sc);
        $deltaHkhsh = $deltaH / ($Kh * $Sh);

        $deltaE = $deltaLKlsl * $deltaLKlsl + $deltaCkcsc * $deltaCkcsc + $deltaHkhsh * $deltaHkhsh;

        return $deltaE < 0 ? 0 : sqrt($deltaE);
    }


    public function ciede2000($color1,$color2)
    {
        $lab1 = $this->toLab($color1);
        $lab2 = $this->toLab($color2);

        $kl = $kc = $kh = 1.0;

        $barL = ($lab1->l + $lab2->l) / 2.0;

        //(Numbers corrispond to http://www.ece.rochester.edu/~gsharma/ciede2000/ciede2000noteCRNA.pdf eq)
        //2
        $helperB1Sq = pow($lab1->b, 2);
        $helperB2Sq = pow($lab2->b, 2);
        $c1 = sqrt(pow($lab1->a, 2) + $helperB1Sq);
        $c2 = sqrt(pow($lab2->a, 2) + $helperB2Sq);
        //3
        $barC = ($c1 + $c2) / 2.0;
        //4
        $helperPow7 = sqrt(pow($barC, 7) / (pow($barC, 7) + 6103515625));
        $g = 0.5*(1 - $helperPow7);
        //5
        $primeA1 = (1+$g)*$lab1->a;
        $primeA2 = (1+$g)*$lab2->a;
        //6
        $primeC1 = sqrt(pow($primeA1, 2) + $helperB1Sq);
        $primeC2 = sqrt(pow($primeA2, 2) + $helperB2Sq);

        //7
        if($lab1->b === 0 && $primeA1 === 0){
            $primeH1 = 0;
        }
        else{
            $primeH1 = (atan2($lab1->b, $primeA1) + 2 * M_PI) * (180 / M_PI);
        }
        if($lab2->b === 0 && $primeA2 === 0){
            $primeH2 = 0;
        }
        else{
            $primeH2 = (atan2($lab2->b, $primeA2) + 2 * M_PI) * (180 / M_PI);
        }

        //8
        $deltaLPrime = $lab2->l - $lab1->l;
        //9
        $deltaCPrime = $primeC2 - $primeC1;
        //10
        $helperH = $primeH2 - $primeH1;
        if($primeC1 * $primeC2 === 0){
            $deltahPrime = 0;
        }
        else if(abs($helperH) <= 180){
            $deltahPrime = $helperH;
        }
        else if($helperH > 180){
            $deltahPrime = $helperH - 360.0;
        }
        else if($helperH < - 180){
            $deltahPrime = $helperH + 360.0;
        }
        else{
            throw new Exception('Invalid delta h\'');
        }
        //11
        $deltaHPrime = 2 * sqrt($primeC1 * $primeC2) * sin(($deltahPrime / 2.0) * (M_PI/180));

        //12
        $barLPrime = ($lab1->l + $lab2->l) / 2.0;
        //13
        $barCPrime = ($primeC1 + $primeC2) / 2.0;
        //14
        $helperH = abs($primeH1 - $primeH2);
        if($primeC1 * $primeC2 === 0){
            $barHPrime = $primeH1 + $primeH2;
        }
        else if($helperH <= 180){
            $barHPrime = ($primeH1 + $primeH2) / 2.0;
        }
        else if($helperH > 180 && ($primeH1 + $primeH2) < 360){
            $barHPrime = ($primeH1 + $primeH2 + 360) / 2.0;
        }
        else if($helperH > 180 && ($primeH1 + $primeH2) >= 360){
            $barHPrime = ($primeH1 + $primeH2 - 360) / 2.0;
        }
        else{
            throw new Exception('Invalid bar h\'');
        }
        //15
        $t = 1 - .17 * cos(($barHPrime - 30) * (M_PI/180)) + .24 * cos((2 * $barHPrime) * (M_PI/180)) + .32 * cos((3 * $barHPrime + 6) * (M_PI/180)) - .2 * cos((4 * $barHPrime - 63) * (M_PI/180));
        //16
        $deltaTheta = 30 * exp(-1 * pow((($barHPrime-275)/25), 2));
        //17
        $rc = 2 * $helperPow7;
        //18
        $slHelper = pow($barLPrime - 50, 2);
        $sl = 1 + ((0.015*$slHelper) / sqrt(20+$slHelper));
        //19
        $sc = 1 + 0.046*$barCPrime;
        //20
        $sh = 1 + 0.015*$barCPrime*$t;
        //21
        $rt = -1 * sin((2 * $deltaTheta) * (M_PI/180)) * $rc;

        //22
        $deltaESquared= pow($deltaLPrime / ($kl * $sl), 2) +
            pow($deltaCPrime / ($kc * $sc), 2) +
            pow($deltaHPrime / ($kh * $sh), 2) +
            ($rt * ($deltaCPrime / ($kc * $sc)) * ($deltaHPrime / ($kh * $sh)));

        $deltaE = sqrt($deltaESquared);
        
        return $deltaE;
    }


    /**
     * Not very useful, but interesting to compare.
     */
    public function simpleRgbDistance($color1, $color2)
    {
        $deltaR = ($color2->r - $color1->r) / 255;
        $deltaG = ($color2->g - $color1->g) / 255;
        $deltaB = ($color2->b - $color1->b) / 255;
        $deltaE = $deltaR * $deltaR + $deltaG * $deltaG + $deltaB * $deltaB;

        return ($deltaE < 0)
            ? $deltaE
            : sqrt($deltaE) * 57.73502691896258;  //  / sqrt(3) * 100;
    }
}
