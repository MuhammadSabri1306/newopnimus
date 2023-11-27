<?php
namespace MuhammadSabri1306\PhpChartSvg\Entities;

class StyleChartLine extends Style
{
    public function __construct(array $styles)
    {
        Style::checkRequiredStyles($this->getName(), $styles, [ 'color', 'lineStyle', 'lineWeight' ]);

        $this->styles['color'] = $styles['color'];
        $this->styles['lineStyle'] = $styles['lineStyle'];
        $this->styles['lineWeight'] = $styles['lineWeight'];
        $this->styles['rounded'] = isset($styles['rounded']) ? $styles['rounded'] : false;
    }

    public function setColor(string $color)
    {
        $this->styles['color'] = $color;
        return $this;
    }
    
    public function getColor()
    {
        return $this->styles['color'];
    }

    public function setLineStyle(string $lineStyle)
    {
        $this->styles['lineStyle'] = $lineStyle;
        return $this;
    }
    
    public function getLineStyle()
    {
        return $this->styles['lineStyle'];
    }

    public function setLineWeight(int $lineWeight)
    {
        $this->styles['lineWeight'] = $lineWeight;
        return $this;
    }
    
    public function getLineWeight()
    {
        return $this->styles['lineWeight'];
    }

    public function setRounded(bool $rounded)
    {
        $this->styles['rounded'] = $rounded;
        return $this;
    }

    public function getRounded()
    {
        return $this->styles['rounded'];
    }
}