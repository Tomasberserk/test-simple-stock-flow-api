<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE product (
              id           CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              name         VARCHAR(200) NOT NULL,
              price        DECIMAL(12,2) NOT NULL,
              stock        INT NOT NULL,
              category_id  CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              image_key    VARCHAR(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
              deleted_at   DATETIME(6) NULL,
              version      INT NOT NULL,
              PRIMARY KEY (id),
              KEY idx_product_category_active_name (category_id, deleted_at, name),
              KEY idx_product_active_name (deleted_at, name),
              CONSTRAINT fk_product_category_id FOREIGN KEY (category_id)
                REFERENCES category (id) ON DELETE RESTRICT ON UPDATE NO ACTION,
              CONSTRAINT ck_product_name_not_blank  CHECK (CHAR_LENGTH(TRIM(name)) > 0),
              CONSTRAINT ck_product_price_positive  CHECK (price > 0),
              CONSTRAINT ck_product_stock_non_negative CHECK (stock >= 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS product;");
    }
};
