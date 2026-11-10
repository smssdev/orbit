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
        Schema::table('tasks', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('title');
            $table->foreignId('assignee_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->date('due_on')->nullable()->after('description');
            $table->string('priority', 10)->nullable()->after('due_on');
        });

        // A project's labels, shared by all of its cards.
        Schema::create('labels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30);
            $table->string('color', 10);
            $table->timestamps();

            $table->unique(['project_id', 'name']);
        });

        Schema::create('label_task', function (Blueprint $table): void {
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();

            $table->primary(['label_id', 'task_id']);
            $table->index('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('label_task');
        Schema::dropIfExists('labels');

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assignee_id');
            $table->dropColumn(['description', 'due_on', 'priority']);
        });
    }
};
