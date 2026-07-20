<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('smtp')
            ->where('authentication', 'crammd5')
            ->update(['authentication' => 'cram-md5']);

        DB::table('smtp')
            ->where('authentication', 'no')
            ->update(['authentication' => 'login']);
    }

    public function down(): void
    {
        // Data normalization is intentionally not reverted.
    }
};
