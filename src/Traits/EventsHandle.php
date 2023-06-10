<?php

namespace Thans\Bpm\Traits;

use Dcat\Admin\Admin;

trait EventsHandle
{
    protected $events;

    public function eventsInit()
    {
        foreach ($this->formData->events as $key => $value) {
            $this->events[$value['name']][] = $value['event'];
        }
    }

    /**
     * 在表单提交前调用，在此事件中可以修改、删除用户提交的数据或者中断提交操作
     * @param mixed $form
     * @return void
     */
    public function submitted($form)
    {
        $form->submitted(function ($form) {
            $data = $form->model()->toArray();
            if (isset($this->events['before_submit']) && $this->events['before_submit']) {
                $code = implode(PHP_EOL, $this->events['before_submit']);
                return eval($code);
            }
        });
    }

    /**
     * 保存前回调，在此事件中可以修改、删除用户提交的数据或者中断提交操作
     * @param mixed $form
     * @return void
     */
    public function saving($form)
    {
        $form->saving(function ($form) {
            $data = $form->model()->toArray();
            if (isset($this->events['bofore_save']) && $this->events['bofore_save']) {
                $code = implode(PHP_EOL, $this->events['bofore_save']);
                return eval($code);
            }
        });
    }

    /**
     * 保存后回调，此事件新增和修改操作共用，通过第二个参数 $result 可以判断数据是否保存成功。
     * @param mixed $form
     * @return void
     */
    public function saved($form)
    {
        $form->saved(function ($form, $result) {
            $data = $form->model()->toArray();
            if (isset($this->events['after_save']) && $this->events['after_save']) {
                $code = implode(PHP_EOL, $this->events['after_save']);
                return eval($code);
            }
        });
    }

    //删除前事件
    public function deleting($form)
    {
        $form->deleting(function ($form) {
            $data = $form->model()->toArray();
            if (isset($this->events['before_delete']) && $this->events['before_delete']) {
                $code = implode(PHP_EOL, $this->events['before_delete']);
                return eval($code);
            }
        });
    }

    //删除后事件
    public function deleted($form)
    {
        $form->deleted(function ($form) {
            $data = $form->model()->toArray();
            if (isset($this->events['after_delete']) && $this->events['after_delete']) {
                $code = implode(PHP_EOL, $this->events['after_delete']);
                return eval($code);
            }
        });
    }

    public function error($message)
    {
        return $this->form()->error($message);
    }

    public function success($message)
    {
        return $this->form()->success($message);
    }

    /**
     * 在新增页面调用（非提交操作）
     * @param mixed $form
     * @return void
     */
    public function creating($form)
    {
        $form->creating(function ($form) {
            $data = $form->model()->toArray();
            if (isset($this->events['create_page']) && $this->events['create_page']) {
                $code = implode(PHP_EOL, $this->events['create_page']);
                return eval($code);
            }
        });
    }

    /**
     * 在编辑页面调用（非提交操作）
     * @param mixed $form
     * @return void
     */
    public function editing($form)
    {
        $form->editing(function ($form) {
            $data = $form->model()->toArray();
            if (isset($this->events['edit_page']) && $this->events['edit_page']) {
                $code = implode(PHP_EOL, $this->events['edit_page']);
                return eval($code);
            }
        });
    }

    /**
     * 在查看页面调用（非提交操作）
     * @param mixed $form
     * @return void
     */
    public function viewing($form)
    {
        $form->editing(function ($form) {
            $data = $form->model()->toArray();
            if (isset($this->events['view_page']) && $this->events['view_page']) {
                $code = implode(PHP_EOL, $this->events['view_page']);
                return eval($code);
            }
        });
    }
}
