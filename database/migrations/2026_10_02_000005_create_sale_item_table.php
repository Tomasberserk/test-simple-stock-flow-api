<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE sale_item (
              id             CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              sale_id        CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              product_id     CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
              product_name   VARCHAR(200) NOT NULL,
              category_name  VARCHAR(120) NOT NULL,
              quantity       INT NOT NULL,
              unit_price     DECIMAL(12,2) NOT NULL,
              PRIMARY KEY (id),
              CONSTRAINT uq_sale_item_sale_product UNIQUE (sale_id, product_id),
              KEY idx_sale_item_product_id (product_id),
              CONSTRAINT fk_sale_item_sale_id FOREIGN KEY (sale_id)
                REFERENCES sale (id) ON DELETE CASCADE ON UPDATE NO ACTION,
              CONSTRAINT fk_sale_item_product_id FOREIGN KEY (product_id)
                REFERENCES product (id) ON DELETE RESTRICT ON UPDATE NO ACTION,
              CONSTRAINT ck_sale_item_quantity_positive   CHECK (quantity > 0),
              CONSTRAINT ck_sale_item_unit_price_positive CHECK (unit_price > 0)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS sale_item;");
    }
};
