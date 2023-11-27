<?php
namespace MuhammadSabri1306\PhpChartSvg\Graphics;

class Chart
{
    protected array $dataX;
    protected array $dataY;

    protected $width = 0;
    protected $height = 0;

    protected $legendTitle;
    
    protected $drawBox = [
        'l' => 0,
        't' => 0,
        'r' => 0,
        'b' => 0
    ];

    public function __construct(array $dataY, $dataX = null)
    {
        if(is_array($dataX)) {

            $this->dataX = $dataX;
            $this->dataY = $dataY;

        } else {

            $this->dataX = [];
            $this->dataY = [];

            foreach($dataX as $row) {
                if(!is_array($row)) {
                    array_push($this->dataX, $row);
                } elseif(count($row) == 2) {
                    array_push($this->dataX, $row[0]);
                    array_push($this->dataY, $row[1]);
                }
            }

        }
    }

    public function setLegendTitle(string $legendTitle)
    {
        $this->legendTitle = $legendTitle;
    }

    public function getLegend()
    {
        return [
            'title' => $this->legendTitle,
            'color' => $this->style()->getColor()
        ];
    }

    public function setDrawBox($nodeLeft, $nodeTop, $nodeRight, $nodeBottom)
    {
        $this->drawBox['l'] = $nodeLeft;
        $this->drawBox['t'] = $nodeTop;
        $this->drawBox['r'] = $nodeRight;
        $this->drawBox['b'] = $nodeBottom;

        $this->width = $this->drawBox['r'] - $this->drawBox['l'];
        $this->height = $this->drawBox['b'] - $this->drawBox['t'];
    }

    public function getData(): array
    {
        $dataX = $this->dataX ?? [];
        $dataY = $this->dataY ?? [];
        $count = min([ count($dataX), count($dataY) ]);
        $data = [];
        for($i=0; $i<$count; $i++) {
            array_push($data, [ $dataX[$i], $dataY[$i] ]);
        }
        return $data;
    }

    public function getDataX(): array
    {
        return $this->dataX ?? [];
    }

    public function getDataY(): array
    {
        return $this->dataY ?? [];
    }

    public function setDataX(array $dataX)
    {
        $this->dataX = $dataX;
    }

    public function setDataY(array $dataY)
    {
        $this->dataY = $dataY;
    }
}