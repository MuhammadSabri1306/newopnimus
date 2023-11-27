<?php
namespace MuhammadSabri1306\PhpChartSvg\Entities;

abstract class Axis
{
    protected array $ticks;

    public function getTicks(): array
    {
        return $this->ticks;
    }

    abstract public function setTicks(array $axisData);
}