<?php
namespace MuhammadSabri1306\PhpChartSvg\Graphics;

use MuhammadSabri1306\PhpChartSvg\Entities\StyleChartLine;
use SVG\Nodes\Shapes\SVGPolyline;

class ChartLine extends Chart
{
    protected StyleChartLine $styles;

    protected array $xAxisTicks;
    protected array $yAxisTicks;

    // protected 

    public function __construct(array $dataY, $dataX = null)
    {
        parent::__construct($dataY, $dataX);

        $this->styles = new StyleChartLine([
            'color' => '#000',
            'lineStyle' => 'solid',
            'lineWeight' => 1
        ]);
    }

    public function style()
    {
        return $this->styles;
    }

    public function getStyle()
    {
        return $this->styles->getAll();
    }

    public function setXAxisTicks(array $ticks)
    {
        $this->xAxisTicks = $ticks;
    }

    public function setYAxisTicks(array $ticks)
    {
        $this->yAxisTicks = $ticks;
    }

    protected function valueToPoint($value, array $axisTicks)
    {
        if(count($axisTicks) < 1) return 0;

        if(is_numeric($axisTicks[0])) {
            $valueLimitHigh = end($axisTicks);
            $valueLimitLow = $axisTicks[0];
            
            $point = ($value - $valueLimitLow) / ($valueLimitHigh - $valueLimitLow);
            return $point;
        }
        
        $axisTicksCount = count($axisTicks);
        for($i=0; $i<$axisTicksCount; $i++) {

            if($value == $axisTicks[$i]) {
                $point = ($i + 1) / $axisTicksCount;
                $i = $axisTicksCount;
            }

        }

        return $point;
    }

    protected function valueToNode($valueX, $valueY)
    {
        $nodeY = 0;
        $nodeX = 0;

        $heightPoint = $this->valueToPoint($valueY, $this->yAxisTicks);
        if($heightPoint > 0) {
            $nodeY = $this->drawBox['t'] + ($this->height * (1 - $heightPoint));
        }

        $widthPoint = $this->valueToPoint($valueX, $this->xAxisTicks);
        if($widthPoint > 0) {
            $nodeX = $this->drawBox['l'] + ($this->width * $widthPoint);
        }

        return [$nodeX, $nodeY];
    }

    public function getSvg()
    {
        $data = $this->getData();
        $dataCount = count($data);
        $nodes = [];

        foreach($data as $index => $item) {

            $node = $this->valueToNode($item[0], $item[1]);
            array_push($nodes, $node);

        }

        // dd_json([
        //     'width' => $this->width,
        //     'height' => $this->height,
        //     'data' => $data,
        //     'axisTicks' => [
        //         'x' => $this->xAxisTicks,
        //         'y' => $this->yAxisTicks
        //     ],
        //     'nodes' => $nodes
        // ]);
        // exit();

        $svg = new SVGPolyline($nodes);
        $styles = $this->getStyle();

        $svg->setStyle('fill', 'none');
        $svg->setStyle('stroke', $styles['color']);
        $svg->setStyle('stroke-width', $styles['lineWeight']);
        if($styles['rounded']) {
            $svg->setStyle('stroke-linecap', 'round');
        }

        return $svg;
    }
}