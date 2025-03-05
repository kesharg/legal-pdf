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
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            // Add new columns
            $table->string('currency', 4);
            $table->boolean('is_enable')->default(true);
            $table->string('sub_domain_prefix')->nullable()->unique();

            // Add foreign key for existing language_code column
            $table->foreign('language_code')->references('code')->on('languages')->cascadeOnUpdate();

            // Add foreign key for currency column
            //$table->foreign('currency')->references('code')->on('currencies')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['language_code']);
            $table->dropForeign(['currency']);

            // Drop new columns
            //$table->dropColumn('currency');
            $table->dropColumn('is_enable');
            $table->dropColumn('sub_domain_prefix');
        });    }
};
