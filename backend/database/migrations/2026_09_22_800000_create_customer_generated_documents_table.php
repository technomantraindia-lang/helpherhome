<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('customer_requirement_id')->nullable()->constrained('customer_requirements')->nullOnDelete();
            $table->string('document_type')->default('customer_registration_form')->index();
            $table->string('document_number')->nullable()->unique();
            $table->unsignedInteger('version_number')->default(1);
            $table->string('status')->default('draft')->index();
            $table->json('snapshot_json');
            $table->string('pdf_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('shared_at')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'customer_requirement_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_generated_documents');
    }
};
