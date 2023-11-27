<?php
namespace MuhammadSabri1306\PhpChartSvg\Graphics;

use MuhammadSabri1306\PhpChartSvg\Entities;

class Axis
{
    protected Entities\Axis $axis;

    protected Entities\StyleAxis $styles;

    public function __construct(Entities\Axis $axis)
    {
        $this->axis = $axis;
    }

    public function getAxis(): Entities\Axis
    {
        return $this->axis;
    }

    public function style()
    {
        return $this->styles;
    }

    public function getStyle()
    {
        return $this->styles->getAll();
    }
}