<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE category (
              id    CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              name  VARCHAR(120) NOT NULL,
              PRIMARY KEY (id),
              CONSTRAINT uq_category_name UNIQUE (name),
              CONSTRAINT ck_category_name_not_blank CHECK (CHAR_LENGTH(TRIM(name)) > 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS category;");
    }
};
