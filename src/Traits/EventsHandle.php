<?php

namespace Thans\Bpm\Traits;

trait EventsHandle
{
    protected $events;
    public function eventsInit()
    {
        $this->events = $this->selfModel()->with('events');
    }

    //数据事件：新增前
    public function beforeDataStroe($data)
    {
        //查询事件代码

        //执行事件
        eval('');
        return $data;
    }
}
