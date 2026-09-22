<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('workers')->restrictOnDelete();
            $table->string('document_type')->index();
            $table->string('document_number')->nullable()->index();
            $table->unsignedInteger('version_number')->default(1);
            $table->string('status')->default('draft')->index();
            $table->json('snapshot_json');
            $table->string('pdf_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('shared_at')->nullable();
            $table->timestamps();
            $table->index(['worker_id', 'document_type', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_generated_documents');
    }
};
