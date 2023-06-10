<?php

namespace Thans\Bpm\Grid\Components;


class DateTimeGrid extends FieldGrid
{
    public function render()
    {
        return $this->column ? date($this->parse(), strtotime($this->column)) : '';
    }
    public function parse()
    {
        $format = $this->options['format'];
        $format = str_replace('yyyy', 'Y', $format);
        $format = str_replace('dd', 'd', $format);
        $format = str_replace('HH', 'H', $format);
        $format = str_replace('hh', 'h', $format);
        $format = str_replace('mm', 'i', $format);
        $format = str_replace('m', 'i', $format);
        $format = str_replace('sss', 's', $format);
        $format = str_replace('ss', 's', $format);
        $format = str_replace('s', 's', $format);
        $format = str_replace('MM', 'm', $format);
        $format = str_replace('M', 'm', $format);
        return $format;
    }
    public function export()
    {
        return $this->column ? date($this->parse(), strtotime($this->column)) : '';
    }
}
