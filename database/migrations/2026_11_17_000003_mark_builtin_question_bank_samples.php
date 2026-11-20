<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('questions')
            ->whereNull('import_batch_id')
            ->whereIn('source', [
                'CSC Professional Verbal Reviewer',
                'CSC Analytical & Logic Reviewer',
                'CSC Numerical Reviewer',
                'Philippine Constitution & RA 6713 Reviewer',
            ])
            ->update(['origin' => 'sample']);
    }

    public function down(): void
    {
        DB::table('questions')
            ->where('origin', 'sample')
            ->update(['origin' => 'manual']);
    }
};
