<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdminUsersPlatformAuth extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_users_platform_auths', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('oauth_name');
            $table->string('unionid')->nullable();
            $table->string('openid')->nullable();
            $table->string('access_token')->nullable();
            $table->integer('expires_in')->nullable();
            $table->text('detail')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admin_users_platform_auths');
    }
}
