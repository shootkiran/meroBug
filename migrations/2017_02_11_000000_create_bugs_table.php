<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBugsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mero_bugs', function (Blueprint $table) {
            $table->id();
            $table->text('user')->nullable();
            $table->string('environment');
            $table->string('host');
            $table->string('method');
            $table->text('fullUrl');
            $table->text('exception');
            $table->text('error');
            $table->integer('line');
            $table->string('file');
            $table->string('class');
            $table->string('release')->nullable();
            $table->text('storage');
            $table->text('executor');
            $table->text('project_version')->nullable();
            $table->timestamps();
            $table->softDeletes();
        }); 
        Schema::create('mero_bugs_fixes', function (Blueprint $table) {
            $table->id();
            $table->string('exception');
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('mero_bugs');
        Schema::dropIfExists('mero_bugs_fixes');
    }
}
