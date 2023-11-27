<?php
namespace MuhammadSabri1306\PhpChartSvg\Entities;

class AxisNumeric extends Axis
{
    protected $step = 10;

    public function setStep($step)
    {
        $this->step = $step;
    }

    public function setTicks(array $axisData)
    {
        if(isset($this->ticks)) {
            $axisData = [ ...$axisData, ...$this->ticks ];
        } else {
            $this->ticks = [0];
        }

        if(count($axisData) < 1) return;

        $amount = $this->step;
        $min = floor(min($axisData) / $amount) * $amount;
        $max = ceil(max($axisData) / $amount) * $amount;
        $this->ticks = range($min, $max, $amount);
    }
}