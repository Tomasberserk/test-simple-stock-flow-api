<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE `user` (
              id             CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              username       VARCHAR(120) NOT NULL,
              password_hash  VARCHAR(512) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              role           VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              PRIMARY KEY (id),
              CONSTRAINT uq_user_username UNIQUE (username),
              CONSTRAINT ck_user_username_normalized CHECK (
                CHAR_LENGTH(username) > 0
                AND CAST(username AS BINARY) = CAST(LOWER(TRIM(username)) AS BINARY)),
              CONSTRAINT ck_user_role_allowed CHECK (role IN ('admin','seller')),
              CONSTRAINT ck_user_password_hash_not_blank CHECK (CHAR_LENGTH(password_hash) > 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS `user`;");
    }
};
