<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE sale (
              id                CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              sold_at           DATETIME(6) NOT NULL,
              sold_by_username  VARCHAR(120) NOT NULL,
              sold_by_user_id   CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              PRIMARY KEY (id),
              KEY idx_sale_sold_at (sold_at),
              KEY idx_sale_sold_by_user_id (sold_by_user_id),
              CONSTRAINT fk_sale_sold_by_user_id FOREIGN KEY (sold_by_user_id)
                REFERENCES `user` (id) ON DELETE RESTRICT ON UPDATE NO ACTION
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS sale;");
    }
};
