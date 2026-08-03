<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('emailable');
            $table->string('type')->default('received'); // sent, received, draft
            $table->string('message_id')->nullable()->unique();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->text('to_email')->nullable();
            $table->text('cc_email')->nullable();
            $table->text('bcc_email')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('folder')->default('inbox'); // inbox, sent, drafts, trash, archive
            $table->string('priority')->default('normal'); // low, normal, high
            $table->json('labels')->nullable();
            $table->string('in_reply_to')->nullable(); // message_id de l'email parent
            $table->timestamps();
            
            // Indexes
            $table->index(['type', 'folder']);
            $table->index(['user_id', 'folder']);
            $table->index('received_at');
            $table->index('sent_at');
            $table->index('read_at');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
