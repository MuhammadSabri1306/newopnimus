<?php
namespace MuhammadSabri1306\PhpChartSvg\Entities;

class AxisTimestamp extends Axis
{
    public function setTicks(array $axisData)
    {
        if(isset($this->ticks)) {
            $axisData = [ ...$axisData, ...$this->ticks ];
        } else {
            $this->ticks = [0];
        }

        if(count($axisData) < 1) return;

        $timestamps = array_unique($axisData);
        asort($timestamps);
        $this->ticks = array_values($timestamps);
    }
}