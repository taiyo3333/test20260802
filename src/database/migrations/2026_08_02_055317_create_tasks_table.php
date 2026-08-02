<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tasks テーブルのマイグレーション。
 *
 * マイグレーションは「テーブル定義をPHPコードで管理する仕組み」。
 * up()   … migrate したときに実行される（テーブルを作る）
 * down() … rollback したときに実行される（テーブルを消す）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();                                  // 主キー（自動採番）
            $table->string('title');                       // タスク名（必須）
            $table->text('description')->nullable();       // 詳細メモ（任意）
            $table->boolean('is_done')->default(false);    // 完了フラグ
            $table->date('due_date')->nullable();          // 期限（任意）
            $table->timestamps();                          // created_at / updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
