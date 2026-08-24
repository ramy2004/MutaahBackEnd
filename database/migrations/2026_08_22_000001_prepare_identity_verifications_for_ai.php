<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('IdentityVerifications', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        $columns = [
            'failure_reason' => fn(Blueprint $table) => $table->text('failure_reason')->nullable(),
            'votes' => fn(Blueprint $table) => $table->json('votes')->nullable(),
            'distances' => fn(Blueprint $table) => $table->json('distances')->nullable(),
            'model_response' => fn(Blueprint $table) => $table->json('model_response')->nullable(),
            'admin_note' => fn(Blueprint $table) => $table->text('admin_note')->nullable(),
        ];

        foreach ($columns as $name => $definition) {
            if (!Schema::hasColumn('IdentityVerifications', $name)) {
                Schema::table('IdentityVerifications', $definition);
            }
        }
    }

    public function down(): void
    {
        Schema::table('IdentityVerifications', function (Blueprint $table) {
            $columns = array_filter([
                'failure_reason',
                'votes',
                'distances',
                'model_response',
                'admin_note',
            ], fn(string $column) => Schema::hasColumn('IdentityVerifications', $column));

            if ($columns) {
                $table->dropColumn($columns);
            }

            $table->enum('status', ['pending', 'accepted', 'rejected'])
                ->default('pending')
                ->change();
        });
    }
};
