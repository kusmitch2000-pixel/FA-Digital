<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workstations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('hourly_rate', 10, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('work_plan_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workstation_id')->constrained();
            $table->unsignedInteger('sequence');
            $table->string('name');
            $table->decimal('setup_minutes', 10, 2)->default(0);
            $table->decimal('minutes_per_unit', 10, 3)->default(0);
            $table->timestamps();
            $table->unique(['article_id', 'sequence']);
        });

        Schema::create('manufacturing_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('article_id')->constrained();
            $table->unsignedInteger('quantity');
            $table->date('due_date');
            $table->string('customer')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('angelegt');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('order_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workstation_id')->constrained();
            $table->unsignedInteger('sequence');
            $table->string('name');
            $table->decimal('planned_setup_minutes', 10, 2);
            $table->decimal('planned_run_minutes', 10, 2);
            $table->decimal('hourly_rate', 10, 2);
            $table->string('status')->default('offen');
            $table->unsignedInteger('good_quantity')->default(0);
            $table->unsignedInteger('scrap_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['manufacturing_order_id', 'sequence']);
        });

        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('kind'); // setup, run, or interruption
            $table->string('reason')->nullable();
            $table->string('resume_kind')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('order_operations');
        Schema::dropIfExists('manufacturing_orders');
        Schema::dropIfExists('work_plan_steps');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('workstations');
    }
};
