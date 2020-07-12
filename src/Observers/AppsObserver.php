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
        $menu = Menu::create([
            'parent_id'     => 0,
            'title'         => $apps->name,
            'icon'          => $apps->icon,
            'uri'           => '',
        ]);
        $menu->permissions()->create([
            'name' => $apps->name,
            'slug' => 'apps_' . $apps->id,
            'http_method' => '',
            'http_path'   => '',
            'parent_id'   => 0,
        ]);
        DB::table('apps')->where('id', $apps->id)->update(['menu_id' => $menu->id]);
    }
}
