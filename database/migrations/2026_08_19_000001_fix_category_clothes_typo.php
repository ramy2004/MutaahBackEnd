<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تحديث القيم الموجودة في قاعدة البيانات
        DB::table('Products')
            ->where('category', 'clouthes')
            ->update(['category' => 'clothes']);
    }

    public function down(): void
    {
        DB::table('Products')
            ->where('category', 'clothes')
            ->update(['category' => 'clouthes']);
    }
};
