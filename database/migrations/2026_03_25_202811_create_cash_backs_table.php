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

            Schema::create('cash_backs', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('tenant_user_id')->nullable()->index();

                $table->double('amount')->default(0);

                $table->string('sub_title')->nullable();
                $table->text('description')->nullable();

                $table->dateTime('fired_at')->nullable();

                $table->timestamps();
                $table->softDeletes();

                // Foreign keys
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                $table->foreign('tenant_user_id')
                    ->references('id')
                    ->on('tenant_users')
                    ->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_backs');
    }
};
