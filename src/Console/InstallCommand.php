<?php

namespace Thans\Bpm\Console;

use Dcat\Admin\Admin;
use Dcat\Admin\Models\Permission;
use Dcat\Admin\Models\AdminTablesSeeder;
use Dcat\Admin\Models\Menu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Thans\Bpm\Bpm;

class InstallCommand extends Command
{

    protected $needRelate = [
        ['auth/users', 'users'],
        ['auth/roles', 'roles'],
        ['auth/permissions', 'permissions'],
        ['auth/menu', 'menu'],
        ['bpm/department', 'department'],
        ['bpm/apps', 'create-apps'],
        ['bpm/forms', 'form-builder'],
    ];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'bpm:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the bpm';

    /**
     * Install directory.
     *
     * @var string
     */
    protected $directory = '';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        //install
        $this->call('admin:publish');
        $this->register();
        $this->call('bpm:publish', ['--force'=>'true']);
        $this->call('admin:install');
        $this->call('migrate');
        $this->initDatabase();
        $this->info('Done.');
    }
    protected function register()
    {
        $this->laravel->register((new Bpm())->serviceProvider);
    }
    /**
     * Create tables and seed it.
     *
     * @return void
     */
    public function initDatabase()
    {
        //应用表单创建、权限创建关联 菜单创建
        $this->setval();
        $createdAt = date('Y-m-d H:i:s');
        Menu::insert([
            [
                'id' => 8,
                'parent_id' => 2,
                'order' => 4,
                'title' => 'Departments',
                'icon' => '',
                'uri' => 'bpm/department',
                'created_at' => $createdAt
            ],
            [
                'id' => 9,
                'parent_id' => 0,
                'order' => 9,
                'title' => 'Applications',
                'icon' => 'fa-indent',
                'uri' => '',
                'created_at' => $createdAt
            ],
            [
                'id' => 10,
                'parent_id' => 9,
                'order' => 10,
                'title' => 'Create Apps',
                'icon' => '',
                'uri' => 'bpm/apps',
                'created_at' => $createdAt
            ],
            [
                'id' => 11,
                'parent_id' => 9,
                'order' => 11,
                'title' => 'Form Builder',
                'icon' => '',
                'uri' => 'bpm/forms',
                'created_at' => $createdAt
            ]
        ]);
        $this->setval();
        Permission::insert([
            [
                "id" => 7,
                'name' => 'Departments',
                'slug' => 'department',
                'http_method' => '',
                'http_path' => '/bpm/department*',
                'order' => 7,
                'parent_id' => 2,
                'created_at' => $createdAt
            ],
            [
                "id" => 8,
                'name' => 'Applications',
                'slug' => 'apps-management',
                'http_method' => '',
                'http_path' => '',
                'order' => 8,
                'parent_id' => 0,
                'created_at' => $createdAt
            ],
            [
                "id" => 9,
                'name' => 'Create Apps',
                'slug' => 'create-apps',
                'http_method' => '',
                'http_path' => '/bpm/apps*',
                'order' => 9,
                'parent_id' => 8,
                'created_at' => $createdAt
            ],
            [
                "id" => 10,
                'name' => 'Form Builder',
                'slug' => 'form-builder',
                'http_method' => '',
                'http_path' => '/bpm/forms*',
                'order' => 10,
                'parent_id' => 8,
                'created_at' => $createdAt
            ]
        ]);
        (new Menu())->flushCache();
        $this->setval();
        //为默认菜单关联权限。
        foreach ($this->needRelate as $key => $value) {
            $permissionId = DB::table(config('admin.database.menu_table'))->where('uri', $value[0])->first()->id;
            $menuId = DB::table(config('admin.database.permissions_table'))->where('slug', $value[1])->first()->id;
            if (DB::table(config('admin.database.permission_menu_table'))->where('permission_id', $permissionId)->where('menu_id', $menuId)->first()) {
                continue;
            }
            DB::table(config('admin.database.permission_menu_table'))->insert([
                'permission_id' => $permissionId,
                'menu_id' => $menuId,
            ]);
        }
    }
    protected function setval()
    {
        $key = DB::select("select pg_get_serial_sequence('admin_permissions', 'id')");
        DB::select('SELECT setval(\'' . $key[0]->pg_get_serial_sequence . '\', (SELECT MAX(id) FROM "admin_permissions"));');
        $key = DB::select("select pg_get_serial_sequence('admin_menu', 'id')");
        DB::select('SELECT setval(\'' . $key[0]->pg_get_serial_sequence . '\', (SELECT MAX(id) FROM "admin_menu"));');
    }
}
