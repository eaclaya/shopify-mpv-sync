<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_bot', function (Blueprint $table) {
            $table->id();
            $table->integer('social_network');
            $table->string('instance');
            $table->string('contact');
            $table->integer('status');
            $table->integer('message_type');
            $table->string('message_id');
            $table->string('referenced_message_id')->nullable();
            $table->text('received_message_text')->nullable();
            $table->text('response_message')->nullable();
            $table->text('media_file')->nullable();
            $table->integer('is_download')->default(0);
            $table->string('thread')->nullable();
            $table->integer('verify_response')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_bot');
    }
};
