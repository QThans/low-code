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
        $this->createMenuAndPermission($form);
    }

    /**
     * 处理 Form「updated」事件
     *
     * @param  \Thans\Bpm\Models\Form  $form
     * @return void
     */
    public function updated(Form $form)
    {

        if (!$form->menu_id) {
            $this->createMenuAndPermission($form);
        } elseif ($form->getOriginal('apps_id') != $form->apps_id) {
            //TODO Permission和menu的关系，重新添加，旧有删除
            //更新菜单和权限父级关系
            $menu = Menu::where('id', $form->menu_id)->first();
            $apps = Apps::where('id', $form->apps_id)->first();
            $menu->parent_id = $apps->menu_id;
            $menu->save();
            $permission = Permission::where('slug', 'form_' . $form->id)->first();
            $parentPermission = Permission::where('slug', 'apps_' . $apps->id)->first();
            $permission->parent_id =  $parentPermission->id;
            $permission->save();
        }
        if ($form->getOriginal('name') != $form->name) {
            Permission::where('slug', 'form_' . $form->id)
                ->update(['name' => $form->name]);
            Menu::where('id', $form->menu_id)->update(['title' => $form->name]);
        }
    }

    protected function getPermissionSlug($id)
    {
        return [
            'form_index_' . $id,
            'form_view_' . $id,
            'form_' . $id,
            'form_create_' . $id,
            'form_save_' . $id,
            'form_edit_' . $id,
            'form_put_' . $id,
            'form_delete_' . $id
        ];
    }

    /**
     * 处理 Form「deleted」事件
     *
     * @param  \Thans\Bpm\Models\Form  $form
     * @return void
     */
    public function deleted(Form $form)
    {
        $this->deleteMenuAndPermission($form);
    }

    /**
     * 处理 Form「forceDeleted」事件
     *
     * @param  \Thans\Bpm\Models\Form  $form
     * @return void
     */
    public function forceDeleted(Form $form)
    {
        $this->deleteMenuAndPermission($form);
    }

    protected function deleteMenuAndPermission(Form $form)
    {
        Permission::whereIn('slug', $this->getPermissionSlug($form->id))->delete();
        Menu::where('id', $form->menu_id)->delete();
    }

    protected function createMenuAndPermission(Form $form)
    {
        $apps = Apps::where('id', $form->apps_id)->first();
        $appsPermission = Permission::where('slug', 'apps_' . $form->apps_id)->first();
        $menu = Menu::create([
            'parent_id'     => $apps->menu_id,
            'title'         => $form->name,
            'icon'          => $form->icon,
            'uri'           => 'bpm/' . $form->alias . '/form',
        ]);
        $permission = new Permission();
        $permission->forceFill([
            'name' => $form->name,
            'slug' => 'form_' . $form->id,
            'http_method' => '',
            'http_path'   => '',
            'parent_id'   => $appsPermission->id
        ])->save();
        $permissions = [];
        $permissions[] = [
            'name' => '列表',
            'slug' => 'form_index_' . $form->id,
            'http_method' => ['GET'],
            'http_path'   => '/bpm/' . $form->alias . '/form',
        ];
        $permissions[] = [
            'name' => '查看',
            'slug' => 'form_view_' . $form->id,
            'http_method' => ['GET'],
            'http_path'   => '/bpm/' . $form->alias . '/form/*',
        ];
        $permissions[] = [
            'name' => '新增页面',
            'slug' => 'form_create_' . $form->id,
            'http_method' => ['GET'],
            'http_path'   => '/bpm/' . $form->alias . '/form/create',
        ];
        $permissions[] = [
            'name' => '提交新增',
            'slug' => 'form_save_' . $form->id,
            'http_method' => ['POST'],
            'http_path'   => '/bpm/' . $form->alias . '/form',
        ];
        $permissions[] = [
            'name' => '编辑页面',
            'slug' => 'form_edit_' . $form->id,
            'http_method' => ['GET'],
            'http_path'   => '/bpm/' . $form->alias . '/form/*/edit',
        ];
        $permissions[] = [
            'name' => '提交编辑',
            'slug' => 'form_put_' . $form->id,
            'http_method' => ['PUT'],
            'http_path'   => '/bpm/' . $form->alias . '/form/*',
        ];
        $permissions[] = [
            'name' => '删除',
            'slug' => 'form_delete_' . $form->id,
            'http_method' => ['DELETE'],
            'http_path'   => '/bpm/' . $form->alias . '/form/*',
        ];
        $permissions = $menu->permissions()->createMany($permissions);
        $parentPermissionMenu = [];
        foreach ($permissions as $key => $value) {
            $value->parent_id = $permission->id;
            $value->save();
            $parentPermissionMenu[] = [
                'permission_id' => $value->id,
                'menu_id' => $apps->menu_id,
            ];
        }        
        DB::table(config('admin.database.permission_menu_table'))->insert($parentPermissionMenu);
        DB::select('SELECT nextval(\'"admin_permissions_id_seq"\'::regclass)');
        DB::select('SELECT setval(\'"admin_permissions_id_seq"\', (SELECT MAX(id) FROM "admin_permissions")+1);');
        DB::table('forms')->where('id', $form->id)->update(['menu_id' => $menu->id]);
    }
}
