<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;

class PdfWithRotation extends Fpdi
{
    protected $angle = 0;

    public function Rotate($angle, $x = null, $y = null)
    {
        if ($this->angle != 0) {
            $this->_out('Q'); // end previous rotation
        }

        if ($angle != 0) {
            if ($x === null) $x = $this->w / 2;
            if ($y === null) $y = $this->h / 2;

            $x *= $this->k;
            $y = ($this->h - $y) * $this->k;

            $this->_out(
                sprintf(
                    'q %.5F %.5F %.5F %.5F %.5F %.5F cm',
                    cos(deg2rad($angle)),
                    sin(deg2rad($angle)),
                    -sin(deg2rad($angle)),
                    cos(deg2rad($angle)),
                    $x - $x * cos(deg2rad($angle)) + $y * sin(deg2rad($angle)),
                    $y - $x * sin(deg2rad($angle)) - $y * cos(deg2rad($angle))
                )
            );
        }

        $this->angle = $angle;
    }

    public function _endpage()
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }

        parent::_endpage();
    }
}
