<?php
namespace MuhammadSabri1306\PhpChartSvg\Entities;

class StyleAxis extends Style
{
    public function __construct(array $styles)
    {
        $availableStyles = ['lineColor', 'lineStyle', 'lineWeight',
            'fontFamily', 'fontSize', 'fontWeight', 'fontStyle'];
        foreach($availableStyles as $key) {
            if(isset($styles[$key])) {
                $this->styles[$key] = $styles[$key];
            }
        }
    }

    public function setLineColor(string $lineColor)
    {
        $this->styles['lineColor'] = $lineColor;
        return $this;
    }
    
    public function getLineColor()
    {
        return $this->styles['lineColor'];
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

    public function setFontFamily(bool $fontFamily)
    {
        $this->styles['fontFamily'] = $fontFamily;
        return $this;
    }

    public function getFontFamily()
    {
        return $this->styles['fontFamily'];
    }

    public function setFontSize(bool $fontSize)
    {
        $this->styles['fontSize'] = $fontSize;
        return $this;
    }

    public function getFontSize()
    {
        return $this->styles['fontSize'];
    }

    public function setFontWeight(bool $fontWeight)
    {
        $this->styles['fontWeight'] = $fontWeight;
        return $this;
    }

    public function getFontWeight()
    {
        return $this->styles['fontWeight'];
    }

    public function setFontStyle(bool $fontStyle)
    {
        $this->styles['fontStyle'] = $fontStyle;
        return $this;
    }

    public function getFontStyle()
    {
        return $this->styles['fontStyle'];
    }
}