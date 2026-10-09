<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Exception\ConcurrencyConflict;
use App\Application\Ports\Inbound\Authenticate;
use App\Application\Ports\Inbound\AuthResult;
use App\Application\Ports\Inbound\GetSales;
use App\Application\Ports\Inbound\GetSalesReport;
use App\Application\Ports\Inbound\ManageProducts;
use App\Application\Ports\Inbound\PagedResult;
use App\Application\Ports\Inbound\PlaceSale;
use App\Application\Ports\Inbound\ProductView;
use App\Application\Ports\Inbound\SaleItemView;
use App\Application\Ports\Inbound\SalesReport;
use App\Application\Ports\Inbound\SalesReportRow;
use App\Application\Ports\Inbound\SaleView;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\ValueObject\Role;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\Username;
use App\Infrastructure\Security\JwtTokenGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

final class ApiEndpointsTest extends TestCase
{
    private JwtTokenGenerator $tokenGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenGenerator = new JwtTokenGenerator();
    }

    private function generateToken(string $role, string $userId = '11111111-1111-4111-8111-111111111111', string $username = 'admin'): string
    {
        $auth = $this->tokenGenerator->generate(
            UserId::fromString($userId),
            new Username($username),
            new Role($role)
        );
        return $auth->token;
    }

    public function test_health_check_endpoint(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertStatus(503) // DB desconectada en entorno de pruebas sin contenedor MySQL
            ->assertJson([
                'status' => 'degraded',
                'service' => 'simple-stock-flow-api',
                'database' => 'disconnected',
            ]);
    }

    public function test_categories_endpoint_uses_inbound_port(): void
    {
        $mockProducts = Mockery::mock(ManageProducts::class);
        $mockProducts->shouldReceive('listCategories')->once()->andReturn([
            ['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'Herramientas'],
            ['id' => '22222222-2222-4222-8222-222222222222', 'name' => 'Pinturas'],
        ]);
        $this->app->instance(ManageProducts::class, $mockProducts);

        $response = $this->getJson('/api/categories');
        $response->assertStatus(200)
            ->assertJson([
                ['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'Herramientas'],
                ['id' => '22222222-2222-4222-8222-222222222222', 'name' => 'Pinturas'],
            ]);
    }

    public function test_login_validation_failure_returns_400(): void
    {
        $response = $this->postJson('/api/auth/login', []);
        $response->assertStatus(400)
            ->assertJsonStructure(['title', 'status', 'detail', 'errors']);
    }

    public function test_login_success(): void
    {
        $mockAuth = Mockery::mock(Authenticate::class);
        $mockAuth->shouldReceive('login')->with('admin', 'secret123')->once()->andReturn(
            new AuthResult(
                token: 'mock-jwt-token',
                expiresAt: '2026-10-09T20:00:00.000Z',
                userId: '11111111-1111-4111-8111-111111111111',
                username: 'admin',
                role: 'admin'
            )
        );
        $this->app->instance(Authenticate::class, $mockAuth);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'token' => 'mock-jwt-token',
                'username' => 'admin',
                'role' => 'admin',
            ]);
    }

    public function test_login_invalid_credentials_returns_empty_401(): void
    {
        $mockAuth = Mockery::mock(Authenticate::class);
        $mockAuth->shouldReceive('login')->andThrow(new InvalidCredentialsException('Credenciales inválidas'));
        $this->app->instance(Authenticate::class, $mockAuth);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401);
        $this->assertEmpty($response->getContent());
        $response->assertHeader('WWW-Authenticate');
    }

    public function test_register_seller_endpoint_requires_admin(): void
    {
        // Sin token -> 401 vacío
        $unauth = $this->postJson('/api/auth/register', [
            'username' => 'carlos',
            'password' => 'secret123',
        ]);
        $unauth->assertStatus(401);
        $this->assertEmpty($unauth->getContent());

        // Con token admin -> 201
        $adminToken = $this->generateToken('admin');
        $mockAuth = Mockery::mock(Authenticate::class);
        $mockAuth->shouldReceive('registerSeller')->with('carlos', 'secret123')->once()->andReturn(
            new AuthResult(
                token: 'seller-jwt-token',
                expiresAt: '2026-10-09T20:00:00.000Z',
                userId: '22222222-2222-4222-8222-222222222222',
                username: 'carlos',
                role: 'seller'
            )
        );
        $this->app->instance(Authenticate::class, $mockAuth);

        $response = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->postJson('/api/auth/register', [
                'username' => 'carlos',
                'password' => 'secret123',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'username' => 'carlos',
                'role' => 'seller',
            ]);
    }

    public function test_products_list_public(): void
    {
        $mockProducts = Mockery::mock(ManageProducts::class);
        $mockProducts->shouldReceive('listProducts')->once()->andReturn(
            new PagedResult(
                items: [
                    new ProductView(
                        id: '33333333-3333-4333-8333-333333333333',
                        name: 'Martillo',
                        price: '25.50',
                        stock: 10,
                        categoryId: '11111111-1111-4111-8111-111111111111',
                        imageKey: null
                    )
                ],
                total: 1,
                page: 1,
                perPage: 20,
                totalPages: 1
            )
        );
        $this->app->instance(ManageProducts::class, $mockProducts);

        $response = $this->getJson('/api/products');
        $response->assertStatus(200)
            ->assertJson([
                'total' => 1,
                'items' => [
                    [
                        'name' => 'Martillo',
                        'price' => 25.50,
                        'stock' => 10,
                    ]
                ]
            ]);
    }

    public function test_create_product_requires_admin_empty_bodies(): void
    {
        // Sin token -> 401 vacío
        $res401 = $this->postJson('/api/products', []);
        $res401->assertStatus(401);
        $this->assertEmpty($res401->getContent());

        // Con token seller -> 403 vacío
        $sellerToken = $this->generateToken('seller');
        $res403 = $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->postJson('/api/products', [
                'name' => 'Taladro',
                'price' => 100,
                'stock' => 5,
                'categoryId' => '11111111-1111-4111-8111-111111111111',
            ]);
        $res403->assertStatus(403);
        $this->assertEmpty($res403->getContent());
    }

    public function test_create_product_success_with_admin(): void
    {
        $adminToken = $this->generateToken('admin');

        $mockProducts = Mockery::mock(ManageProducts::class);
        $mockProducts->shouldReceive('createProduct')->once()->andReturn(
            new ProductView(
                id: '44444444-4444-4444-8444-444444444444',
                name: 'Taladro',
                price: '120.00',
                stock: 5,
                categoryId: '11111111-1111-4111-8111-111111111111',
                imageKey: null
            )
        );
        $this->app->instance(ManageProducts::class, $mockProducts);

        $response = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->postJson('/api/products', [
                'name' => 'Taladro',
                'price' => 120.00,
                'stock' => 5,
                'categoryId' => '11111111-1111-4111-8111-111111111111',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'id' => '44444444-4444-4444-8444-444444444444',
                'name' => 'Taladro',
                'price' => 120.00,
                'stock' => 5,
            ]);
    }

    public function test_upload_product_image_endpoint(): void
    {
        Storage::fake('public');
        $adminToken = $this->generateToken('admin');

        $mockProducts = Mockery::mock(ManageProducts::class);
        $mockProducts->shouldReceive('uploadImage')->once()->andReturn(
            new ProductView(
                id: '44444444-4444-4444-8444-444444444444',
                name: 'Taladro',
                price: '120.00',
                stock: 5,
                categoryId: '11111111-1111-4111-8111-111111111111',
                imageKey: 'abc-123.jpg'
            )
        );
        $this->app->instance(ManageProducts::class, $mockProducts);

        $file = UploadedFile::fake()->image('taladro.jpg');

        $response = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->postJson('/api/products/44444444-4444-4444-8444-444444444444/image', [
                'image' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'imageKey' => 'abc-123.jpg',
            ]);
    }

    public function test_place_sale_success_with_seller(): void
    {
        $sellerToken = $this->generateToken('seller', username: 'carlos');

        $mockSale = Mockery::mock(PlaceSale::class);
        $mockSale->shouldReceive('execute')->once()->andReturn(
            new SaleView(
                id: '55555555-5555-4555-8555-555555555555',
                soldAt: '2026-10-09T17:00:00.000Z',
                soldByUserId: '11111111-1111-4111-8111-111111111111',
                soldBy: 'carlos',
                items: [
                    new SaleItemView(
                        id: '66666666-6666-4666-8666-666666666666',
                        productId: '44444444-4444-4444-8444-444444444444',
                        productName: 'Taladro',
                        categoryName: 'Herramientas',
                        quantity: 2,
                        unitPrice: '120.00',
                        subtotal: '240.00'
                    )
                ],
                total: '240.00'
            )
        );
        $this->app->instance(PlaceSale::class, $mockSale);

        $response = $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->postJson('/api/sales', [
                'items' => [
                    [
                        'productId' => '44444444-4444-4444-8444-444444444444',
                        'quantity' => 2,
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'id' => '55555555-5555-4555-8555-555555555555',
                'total' => 240.00,
                'soldBy' => 'carlos',
            ]);
    }

    public function test_concurrency_conflict_returns_problem_details_409(): void
    {
        $sellerToken = $this->generateToken('seller');

        $mockSale = Mockery::mock(PlaceSale::class);
        $mockSale->shouldReceive('execute')->andThrow(new ConcurrencyConflict('Conflicto de concurrencia'));
        $this->app->instance(PlaceSale::class, $mockSale);

        $response = $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->postJson('/api/sales', [
                'items' => [
                    [
                        'productId' => '44444444-4444-4444-8444-444444444444',
                        'quantity' => 2,
                    ]
                ]
            ]);

        $response->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJson([
                'status' => 409,
                'detail' => 'Conflicto de concurrencia',
            ]);
    }

    public function test_sales_report_requires_admin_and_supports_from_to(): void
    {
        $sellerToken = $this->generateToken('seller');
        $res403 = $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->getJson('/api/reports/sales?from=2026-10-01&to=2026-10-31');
        $res403->assertStatus(403);
        $this->assertEmpty($res403->getContent());

        $adminToken = $this->generateToken('admin');
        $mockReport = Mockery::mock(GetSalesReport::class);
        $mockReport->shouldReceive('execute')->with('2026-10-01', '2026-10-31')->once()->andReturn(
            new SalesReport(
                startDate: '2026-10-01',
                endDate: '2026-10-31',
                totalSalesCount: 1,
                grandTotal: '240.00',
                items: [
                    new SalesReportRow(
                        productId: '44444444-4444-4444-8444-444444444444',
                        productName: 'Taladro',
                        categoryName: 'Herramientas',
                        unitsSold: 2,
                        revenue: '240.00'
                    )
                ]
            )
        );
        $this->app->instance(GetSalesReport::class, $mockReport);

        $response = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->getJson('/api/reports/sales?from=2026-10-01&to=2026-10-31');

        $response->assertStatus(200)
            ->assertJson([
                'totalSalesCount' => 1,
                'grandTotal' => 240.00,
                'items' => [
                    [
                        'productName' => 'Taladro',
                        'unitsSold' => 2,
                        'revenue' => 240.00,
                    ]
                ]
            ]);
    }
}
