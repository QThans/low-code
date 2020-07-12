<?php

namespace Thans\Bpm\Observers;

use Dcat\Admin\Models\Menu;
use Dcat\Admin\Models\Permission;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Models\Form;
use Thans\Bpm\Models\Apps;

class FormsObserver
{
    /**
     * 处理 Form「creating」事件
     *
     * @param  \Thans\Bpm\Models\Form  $form
     * @return void
     */
    public function creating(Form $form)
    {
    }
    /**
     * 处理 Form「created」事件
     *
     * @param  \Thans\Bpm\Models\Form  $form
     * @return void
     */
    public function created(Form $form)
    {
        $this->createMenu($form);
    }

    /**
     * 处理 Form「updated」事件
     *
     * @param  \Thans\Bpm\Models\Form  $form
     * @return void
     */
    public function updated(Form $form)
    {
        if ($form->getOriginal('apps_id') != $form->apps_id) {
            $this->deleteMenu($form);
            $this->createMenu($form);
        } else {
            if (!$form->menu_id) {
                $this->createMenu($form);
            }
        }
    }

    /**
     * 处理 Form「deleted」事件
     *
     * @param  \Thans\Bpm\Models\Form  $form
     * @return void
     */
    public function deleted(Form $form)
    {
        $this->deleteMenu($form);
    }

    /**
     * 处理 Form「forceDeleted」事件
     *
     * @param  \Thans\Bpm\Models\Form  $form
     * @return void
     */
    public function forceDeleted(Form $form)
    {
        $this->deleteMenu($form);
    }

    protected function deleteMenu(Form $form)
    {
        Permission::whereIn('slug', ['form_index_' . $form->id, 'form_create_' . $form->id, 'form_save_' . $form->id, 'form_edit_' . $form->id, 'form_put_' . $form->id, 'form_delete_' . $form->id])->delete();
        Menu::where('id', $form->menu_id)->delete();
    }

    protected function createMenu(Form $form)
    {
        $apps = Apps::where('id', $form->apps_id)->first();
        $appsPermission = Permission::where('slug', 'apps_' . $form->apps_id)->first();
        $menu = Menu::create([
            'parent_id'     => $apps->menu_id,
            'title'         => $form->name,
            'icon'          => $form->icon,
            'uri'           => 'bpm/' . $form->alias . '/form',
        ]);
        $permission = $menu->permissions()->create([
            'name' => $form->name . '-列表',
            'slug' => 'form_index_' . $form->id,
            'http_method' => 'GET',
            'http_path'   => 'admin/bpm/' . $form->alias . '/form',
        ]);
        $permission->parent_id = $appsPermission->id;
        $permission->save();
        $permissions = [];
        $createdAt = date('Y-m-d H:i:s');
        $permissions[] = [
            'name' => $form->name . '-创建',
            'slug' => 'form_create_' . $form->id,
            'http_method' => 'GET',
            'http_path'   => 'admin/bpm/' . $form->alias . '/form/create',
            'parent_id'   => $appsPermission->id,
            'created_at'  => $createdAt
        ];
        $permissions[] = [
            'name' => $form->name . '-新增',
            'slug' => 'form_save_' . $form->id,
            'http_method' => 'POST',
            'http_path'   => 'admin/bpm/' . $form->alias . '/form',
            'parent_id'   => $appsPermission->id,
            'created_at'  => $createdAt
        ];
        $permissions[] = [
            'name' => $form->name . '-编辑',
            'slug' => 'form_edit_' . $form->id,
            'http_method' => 'GET',
            'http_path'   => 'admin/bpm/' . $form->alias . '/form/*/edit',
            'parent_id'   => $appsPermission->id,
            'created_at'  => $createdAt
        ];
        $permissions[] = [
            'name' => $form->name . '-更新',
            'slug' => 'form_put_' . $form->id,
            'http_method' => 'PUT',
            'http_path'   => 'admin/bpm/' . $form->alias . '/form/*',
            'parent_id'   => $appsPermission->id,
            'created_at'  => $createdAt
        ];
        $permissions[] = [
            'name' => $form->name . '-删除',
            'slug' => 'form_delete_' . $form->id,
            'http_method' => 'DELETE',
            'http_path'   => 'admin/bpm/' . $form->alias . '/form/*',
            'parent_id'   => $appsPermission->id,
            'created_at'  => $createdAt
        ];
        Permission::insert($permissions);
        DB::select('SELECT nextval(\'"admin_permissions_id_seq"\'::regclass)');
        DB::select('SELECT setval(\'"admin_permissions_id_seq"\', (SELECT MAX(id) FROM "admin_permissions")+1);');
        DB::table('forms')->where('id', $form->id)->update(['menu_id' => $menu->id]);
    }
}
