<?php

namespace Thans\Bpm\Traits;

use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Form;
use Dcat\Admin\Form\Builder;
use Thans\Bpm\BpmRenderFormField;

trait ShowHandle
{
    public function show($formId, $id)
    {
        $this->showMode = true;
        return $this->content
            ->body($this->detail($id));
    }

    public function detail($id)
    {
        BpmRenderFormField::collectAssets();

        $form = $this->form();
        $form->edit($id);
        $this->createdUser($form->model()->user['username']);
        $this->createdAt($form->model()->created_at);
        $this->updatedUser($form->model()->updated_user['username']);
        $this->updatedAt($form->model()->updated_at);
        $form->title('详情');
        $form->disableFooter();
        $destroyUrl = $this->destroyUrl($id);
        $indexUrl = $this->indexUrl();
        $editUrl = $this->editUrl($id);
        $form->tools(function (Form\Tools $tools) use ($indexUrl, $destroyUrl, $editUrl) {
            $tools->disableView();
            $tools->disableDelete();
            $tools->disableList();
            $tools->prepend('<a href="' . $indexUrl . '" class="btn btn-sm btn-white "><i class="feather icon-list"></i><span class="d-none d-sm-inline">&nbsp;列表</span></a>');
            $tools->prepend('<a href="' . $editUrl . '" class="btn btn-sm btn-primary">
            <i class="feather icon-edit-1"></i><span class="d-none d-sm-inline"> 编辑</span>
        </a>');
            $tools->prepend('<button class="btn btn-sm btn-danger " data-action="delete" data-url="' . $destroyUrl . '" data-redirect="' . $indexUrl . '">
            <i class="feather icon-trash"></i><span class="d-none d-sm-inline">  删除</span>
        </button>');
        });
        Admin::style(<<<CSS
        .form-control:disabled, .form-control[readonly]{
            background-color:#fff;
        }
CSS);
        return $form;
    }
    protected function createdUser($user)
    {
        $this->formComponents['components'][] = [
            "type" => "textfield",
            "input" => true,
            "label" => "创建用户",
            "tableView" => true,
            "defaultValue" => $user,
        ];
    }
    protected function createdAt($createdAt)
    {
        $this->formComponents['components'][] = [
            "type" => "textfield",
            "input" => true,
            "label" => "创建时间",
            "tableView" => true,
            "defaultValue" => $createdAt,
        ];
    }
    protected function updatedUser($user)
    {
        $this->formComponents['components'][] = [
            "type" => "textfield",
            "input" => true,
            "label" => "更新用户",
            "tableView" => true,
            "defaultValue" => $user,
        ];
    }
    protected function updatedAt($updatedAt)
    {
        $this->formComponents['components'][] = [
            "type" => "textfield",
            "input" => true,
            "label" => "更新时间",
            "tableView" => true,
            "defaultValue" => $updatedAt,
        ];
    }
}
