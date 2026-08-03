<?php

namespace App\Support\IdCards;

use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeRenderer
{
    public function base64Png(string $value): ?string
    {
        if (! class_exists(BarcodeGeneratorPNG::class)) {
            return null;
        }

        $generator = new BarcodeGeneratorPNG;

        return base64_encode(
            $generator->getBarcode($value, $generator::TYPE_CODE_128, 2, 70)
        );
    }

    public function code128Svg(string $value): string
    {
        $patterns = [
            '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
            '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
            '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
            '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
            '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
            '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
            '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
            '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
            '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
            '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
            '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
        ];

        $codes = [104];
        foreach (str_split($value) as $char) {
            $codes[] = ord($char) - 32;
        }

        $checksum = $codes[0];
        foreach (array_slice($codes, 1) as $index => $code) {
            $checksum += $code * ($index + 1);
        }

        $codes[] = $checksum % 103;
        $codes[] = 106;

        $x = 10;
        $height = 70;
        $bars = '';

        foreach ($codes as $code) {
            $pattern = $patterns[$code];

            foreach (str_split($pattern) as $index => $width) {
                $width = (int) $width * 2;

                if ($index % 2 === 0) {
                    $bars .= '<rect x="'.$x.'" y="10" width="'.$width.'" height="'.$height.'" fill="#111827" />';
                }

                $x += $width;
            }
        }

        $svgWidth = $x + 10;

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$svgWidth.'" height="90" viewBox="0 0 '.$svgWidth.' 90" role="img" aria-label="Barcode '.$value.'">'.$bars.'</svg>';
    }
}
