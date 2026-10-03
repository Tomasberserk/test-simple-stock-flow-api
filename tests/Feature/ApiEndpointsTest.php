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
use App\Application\Ports\Outbound\CategoryRepository;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Model\Category;
use App\Domain\ValueObject\CategoryId;
use App\Infrastructure\Security\JwtTokenGenerator;
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
            \App\Domain\ValueObject\UserId::fromString($userId),
            new \App\Domain\ValueObject\Username($username),
            new \App\Domain\ValueObject\Role($role)
        );
        return $auth->token;
    }

    public function test_health_check_endpoint(): void
    {
        $response = $this->getJson('/api/health');
        $response->assertStatus(503) // DB not connected in test environment
            ->assertJson([
                'status' => 'degraded',
                'service' => 'simple-stock-flow-api',
                'database' => 'disconnected',
            ]);
    }

    public function test_categories_endpoint(): void
    {
        $mockRepo = Mockery::mock(CategoryRepository::class);
        $mockRepo->shouldReceive('findAll')->once()->andReturn([
            new Category(CategoryId::fromString('11111111-1111-4111-8111-111111111111'), 'Herramientas'),
            new Category(CategoryId::fromString('22222222-2222-4222-8222-222222222222'), 'Pinturas'),
        ]);
        $this->app->instance(CategoryRepository::class, $mockRepo);

        $response = $this->getJson('/api/categories');
        $response->assertStatus(200)
            ->assertJson([
                ['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'Herramientas'],
                ['id' => '22222222-2222-4222-8222-222222222222', 'name' => 'Pinturas'],
            ]);
    }

    public function test_login_validation_failure(): void
    {
        $response = $this->postJson('/api/auth/login', []);
        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors']);
    }

    public function test_login_success(): void
    {
        $mockAuth = Mockery::mock(Authenticate::class);
        $mockAuth->shouldReceive('login')->with('admin', 'secret123')->once()->andReturn(
            new AuthResult(
                token: 'mock-jwt-token',
                expiresAt: '2026-10-03T20:00:00.000Z',
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

    public function test_login_invalid_credentials_returns_401(): void
    {
        $mockAuth = Mockery::mock(Authenticate::class);
        $mockAuth->shouldReceive('login')->andThrow(new InvalidCredentialsException('Credenciales inválidas'));
        $this->app->instance(Authenticate::class, $mockAuth);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Credenciales inválidas']);
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

    public function test_create_product_requires_admin(): void
    {
        // Sin token -> 401
        $this->postJson('/api/products', [])->assertStatus(401);

        // Con token seller -> 403
        $sellerToken = $this->generateToken('seller');
        $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->postJson('/api/products', [
                'name' => 'Taladro',
                'price' => 100,
                'stock' => 5,
                'categoryId' => '11111111-1111-4111-8111-111111111111',
            ])->assertStatus(403);
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

    public function test_place_sale_success_with_seller(): void
    {
        $sellerToken = $this->generateToken('seller', username: 'carlos');

        $mockSale = Mockery::mock(PlaceSale::class);
        $mockSale->shouldReceive('execute')->once()->andReturn(
            new SaleView(
                id: '55555555-5555-4555-8555-555555555555',
                soldAt: '2026-10-03T17:00:00.000Z',
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

    public function test_concurrency_conflict_returns_409(): void
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
            ->assertJson(['error' => 'Conflicto de concurrencia']);
    }

    public function test_sales_report_requires_admin(): void
    {
        $sellerToken = $this->generateToken('seller');
        $this->withHeader('Authorization', "Bearer {$sellerToken}")
            ->getJson('/api/reports/sales?startDate=2026-10-01&endDate=2026-10-31')
            ->assertStatus(403);

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
            ->getJson('/api/reports/sales?startDate=2026-10-01&endDate=2026-10-31');

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
