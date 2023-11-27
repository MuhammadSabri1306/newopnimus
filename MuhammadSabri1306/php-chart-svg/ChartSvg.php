<?php
namespace MuhammadSabri1306\PhpChartSvg;

use SVG\SVG;
use SVG\Nodes\Shapes\SVGRect;
use MuhammadSabri1306\PhpChartSvg\Entities\Axis;
use MuhammadSabri1306\PhpChartSvg\Entities\AxisNumeric;
use MuhammadSabri1306\PhpChartSvg\Entities\AxisTimestamp;
use MuhammadSabri1306\PhpChartSvg\Graphics\Chart;
use MuhammadSabri1306\PhpChartSvg\Graphics\AxisX;

class ChartSvg
{
    protected $imgWidth;
    protected $imgHeight;

    public SVGRect $background;

    public AxisX $xAxis;
    protected string $xAxisType;
    
    public AxisX $yAxis;
    protected string $yAxisType;

    protected $chartContents = [];

    public function __construct($imgWidth, $imgHeight)
    {
        $this->imgWidth = $imgWidth;
        $this->imgHeight = $imgHeight;

        $this->background = new SVGRect(0, 0, $imgWidth, $imgHeight);
    }

    public function setAxisType(string $orient, string $type)
    {
        $orient = strtoupper($orient);

        if($orient != 'X' && $orient != 'Y') {
            throw new Exception("Error to set axis type, axis '$orient', type '$type'");
        } elseif(!in_array($type, [ 'numeric', 'category', 'timestamp' ])) {
            throw new Exception("Error to set axis type, axis '$orient', type '$type'");
        }

        if($type == 'numeric') {
            $axis = new AxisNumeric();
        } elseif($type == 'timestamp') {
            $axis = new AxisTimestamp();
        }

        if($orient == 'X') {
            $this->xAxis = new AxisX($axis);
        } else {
            $this->yAxis = new AxisX($axis);
        }

    }

    protected function getCalcPosts()
    {
        $posts = [

            'wrapperL' => 0,
            'wrapperT' => 0,
            'wrapperR' => $this->imgWidth,
            'wrapperB' => $this->imgHeight,
    
            'chartL' => 0,
            'chartT' => 0,
            'chartR' => $this->imgWidth,
            'chartB' => $this->imgHeight,

        ];

        return $posts;
    }

    public function addContent(string $key, Chart $content)
    {
        $this->yAxis->getAxis()->setTicks($content->getDataY());
        $this->xAxis->getAxis()->setTicks($content->getDataX());

        if(!$content->getLegend()) {
            $content->setLegend($key);
        }

        array_push($this->chartContents, [
            'key' => $key,
            'chart' => $content
        ]);

        return array_key_last($this->chartContents);
    }

    public function getContent($key)
    {
        $content = null;
        if(is_int($key)) {
            if(isset($this->chartContents[$key])) {
                $content = $this->chartContents[$key];
            }
        } else {
            for($i=0; $i<count($this->chartContents); $i++) {
                if($key == $this->chartContents[$i]['key']) {
                    $content = $this->chartContents[$i];
                    $i = count($this->chartContents);
                }
            }
        }

        return $content ? $content['chart'] : null;
    }

    public function getSvg()
    {
        $xAxisTick = $this->xAxis->getAxis()->getTicks();
        $yAxisTick = $this->yAxis->getAxis()->getTicks();
        $drawBox = $this->getCalcPosts();

        $image = new SVG($this->imgWidth, $this->imgHeight);
        $doc = $image->getDocument();

        $doc->addChild($this->background);

        for($i=0; $i<count($this->chartContents); $i++) {
            
            $chart = $this->chartContents[$i]['chart'];
            $chart->setDrawBox($drawBox['chartL'], $drawBox['chartT'], $drawBox['chartR'], $drawBox['chartB']);
            $chart->setYAxisTicks($yAxisTick);
            $chart->setXAxisTicks($xAxisTick);

            $doc->addChild($chart->getSvg());

        }

        return $image;
    }
}