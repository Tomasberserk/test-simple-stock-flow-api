<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            INSERT INTO category (id, name) VALUES
            ('11111111-1111-4111-8111-111111111111', 'General'),
            ('22222222-2222-4222-8222-222222222222', 'Herramientas'),
            ('33333333-3333-4333-8333-333333333333', 'Electricidad'),
            ('44444444-4444-4444-8444-444444444444', 'Fontanería'),
            ('55555555-5555-4555-8555-555555555555', 'Pinturas');
        ");
    }

    public function down(): void
    {
        DB::statement("DELETE FROM category WHERE id IN (
            '11111111-1111-4111-8111-111111111111',
            '22222222-2222-4222-8222-222222222222',
            '33333333-3333-4333-8333-333333333333',
            '44444444-4444-4444-8444-444444444444',
            '55555555-5555-4555-8555-555555555555'
        );");
    }
};
