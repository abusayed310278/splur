<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHeaderColumnsToSettingsTable extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('border_color')->nullable();
            $table->string('bg_color')->nullable();
            $table->string('menu_item_color')->nullable();
            $table->string('menu_item_active_color')->nullable();
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'border_color',
                'bg_color',
                'menu_item_color',
                'menu_item_active_color',
            ]);
        });
    }
}
