<?php

namespace Thans\Bpm\Observers;

use Dcat\Admin\Models\Menu;
use Dcat\Admin\Models\Permission;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Models\Apps;
use Illuminate\Support\Facades\Request;

class AppsObserver
{
    /**
     * 处理 Apps「creating」事件
     *
     * @param  \Thans\Bpm\Models\Apps  $apps
     * @return void
     */
    public function creating(Apps $apps)
    {
    }
    /**
     * 处理 Apps「created」事件
     *
     * @param  \Thans\Bpm\Models\Apps  $apps
     * @return void
     */
    public function created(Apps $apps)
    {
        $this->createMenu($apps);
    }

    /**
     * 处理 Apps「updated」事件
     *
     * @param  \Thans\Bpm\Models\Apps  $apps
     * @return void
     */
    public function updated(Apps $apps)
    {
        if (!$apps->menu_id) {
            $this->createMenu($apps);
        }
        if ($apps->getOriginal('name') != $apps->name) {
            Permission::whereIn('slug', 'apps_' . $apps->id)
                ->update(['name' => $apps->name]);
            Menu::where('id', $apps->menu_id)->update(['title' => $apps->name]);
        }
        if ($apps->getOriginal('icon') != $apps->icon) {
            Menu::where('id', $apps->menu_id)->update(['icon' => $apps->icon]);
        }
    }

    /**
     * 处理 Apps「deleted」事件
     *
     * @param  \Thans\Bpm\Models\Apps  $apps
     * @return void
     */
    public function deleted(Apps $apps)
    {
        $this->deleteMenu($apps);
    }

    /**
     * 处理 Apps「forceDeleted」事件
     *
     * @param  \Thans\Bpm\Models\Apps  $apps
     * @return void
     */
    public function forceDeleted(Apps $apps)
    {
        $this->deleteMenu($apps);
    }

    protected function deleteMenu(Apps $apps)
    {
        Permission::where('slug', 'apps_' . $apps->id)->delete();
        Menu::where('id', $apps->menu_id)->delete();
        DB::table('apps')->where('id', $apps->id)->update(['menu_id' => null]);
    }

    protected function createMenu(Apps $apps)
    {
        $permissionsParentId = 0;
        $menuParentId = 0;
        if ($apps->parent_id) {
            $parentApps = Apps::where('id', $apps->parent_id)->first();
            $appsPermission = Permission::where('slug', 'apps_' . $parentApps->id)->first();
            $permissionsParentId = $appsPermission->id;
            $menuParentId = $parentApps->menu_id;
        }
        $menu = Menu::create([
            'parent_id'     => $menuParentId,
            'title'         => $apps->name,
            'icon'          => $apps->icon,
            'uri'           => '',
        ]);
        $permission = $menu->permissions()->create([
            'name' => $apps->name,
            'slug' => 'apps_' . $apps->id,
            'http_method' => '',
            'http_path'   => '',
        ]);
        $permission->parent_id = $permissionsParentId;
        $permission->save();
        DB::table('apps')->where('id', $apps->id)->update(['menu_id' => $menu->id]);
    }
}
