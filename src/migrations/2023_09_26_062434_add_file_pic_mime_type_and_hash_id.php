<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFilePicMimeTypeAndHashId extends Migration
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
            $columns = DB::select('show columns from qs_file_pic');

            $count = collect($columns)->filter(function ($column) {
                return $column->Field == 'mime_type';
            })->count();

            if(!!$count){
                $table->string('mime_type', 200)->default('')->change();
            }
            else{
                $table->string("mime_type", 200)->default("")->after("cate");
            }
            $table->string("hash_id", 200)->default("")
                ->comment("文件哈希值，除了空串，此值应该唯一")
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
            $table->string('mime_type', 200)->default('')->change();
            $table->dropColumn('hash_id');
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
