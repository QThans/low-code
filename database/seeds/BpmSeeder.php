<?php

use Dcat\Admin\Models\Menu;
use Illuminate\Database\Seeder;

class BpmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdAt = date('Y-m-d H:i:s');

        Menu::insert([

            [
                'parent_id'     => 2,
                'order'         => 4,
                'title'         => '部门',
                'icon'          => '',
                'uri'       => 'movies/top250',
                'created_at'    => $createdAt,
            ]
        ]);

        (new Menu())->flushCache();
    }
}
