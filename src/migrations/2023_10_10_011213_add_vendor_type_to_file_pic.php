<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVendorTypeToFilePic extends Migration
{

    public function beforeCmmUp()
    {
        //
    }

    public function beforeCmmDown()
    {
        //
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qs_file_pic', function (Blueprint $table) {
            //
            $table->string("vendor_type", 50)->default("")
                ->comment("对象存储云服务商，aliyun_oss 阿里云；tengxun_cos 腾讯云； volcengine_tos 火山引擎")
                ->after("cate");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qs_file_pic', function (Blueprint $table) {
            //
            $table->dropColumn('vendor_type');
        });
    }

    public function afterCmmUp()
    {
        //
    }

    public function afterCmmDown()
    {
        //
    }
}
