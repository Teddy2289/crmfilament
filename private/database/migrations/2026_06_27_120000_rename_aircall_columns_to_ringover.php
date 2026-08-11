<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $this->renameColumnIfNeeded($table, 'appels', 'aircall_call_id', 'ringover_call_id');
            $this->renameColumnIfNeeded($table, 'appels', 'aircall_number_id', 'ringover_number_id');
            $this->renameColumnIfNeeded($table, 'appels', 'aircall_user_id', 'ringover_user_id');
            $this->renameColumnIfNeeded($table, 'appels', 'aircall_agent_nom', 'ringover_agent_nom');
            $this->renameColumnIfNeeded($table, 'appels', 'aircall_email', 'ringover_email');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $this->renameColumnIfNeeded($table, 'tickets', 'aircall_call_id', 'ringover_call_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $this->renameColumnIfNeeded($table, 'users', 'aircall_user_id', 'ringover_user_id');
            $this->renameColumnIfNeeded($table, 'users', 'aircall_email', 'ringover_email');
        });
    }

    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $this->renameColumnIfNeeded($table, 'appels', 'ringover_call_id', 'aircall_call_id');
            $this->renameColumnIfNeeded($table, 'appels', 'ringover_number_id', 'aircall_number_id');
            $this->renameColumnIfNeeded($table, 'appels', 'ringover_user_id', 'aircall_user_id');
            $this->renameColumnIfNeeded($table, 'appels', 'ringover_agent_nom', 'aircall_agent_nom');
            $this->renameColumnIfNeeded($table, 'appels', 'ringover_email', 'aircall_email');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $this->renameColumnIfNeeded($table, 'tickets', 'ringover_call_id', 'aircall_call_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $this->renameColumnIfNeeded($table, 'users', 'ringover_user_id', 'aircall_user_id');
            $this->renameColumnIfNeeded($table, 'users', 'ringover_email', 'aircall_email');
        });
    }

    private function renameColumnIfNeeded(Blueprint $table, string $tableName, string $from, string $to): void
    {
        if (Schema::hasColumn($tableName, $from) && ! Schema::hasColumn($tableName, $to)) {
            $table->renameColumn($from, $to);
        }
    }
};
