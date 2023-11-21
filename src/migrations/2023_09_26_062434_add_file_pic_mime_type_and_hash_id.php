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
            $columns = DB::select("show columns from qs_file_pic WHERE FIELD in ('mime_type','hash_id');");

            $exists_mime_type = false;
            $exists_hash_id = false;

            $columns && collect($columns)->each(function ($column) use(&$exists_mime_type, &$exists_hash_id) {
                if ($column->Field === 'mime_type'){
                    $exists_mime_type = true;
                }
                if ($column->Field === 'hash_id'){
                    $exists_hash_id = true;
                }
            });

            if($exists_mime_type){
                $table->string('mime_type', 200)->default('')->change();
            }
            else{
                $table->string("mime_type", 200)->default("")->after("cate");
            }

            if ($exists_hash_id){
                $table->string("hash_id", 200)->default("")
                    ->comment("文件哈希值，除了空串，此值应该唯一")
                    ->after("cate")->change();
            }else{
                $table->string("hash_id", 200)->default("")
                    ->comment("文件哈希值，除了空串，此值应该唯一")
                    ->after("cate");
            }

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
