<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\Ports\Inbound\ManageProducts;
use App\Application\Ports\Inbound\ProductView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProductController
{
    public function __construct(
        private readonly ManageProducts $manageProducts
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $request->query('query');
        $categoryId = $request->query('categoryId');
        $page = (int) $request->query('page', '1');
        $perPage = (int) $request->query('perPage', '20');

        $result = $this->manageProducts->listProducts(
            query: is_string($query) && trim($query) !== '' ? trim($query) : null,
            categoryId: is_string($categoryId) && trim($categoryId) !== '' ? trim($categoryId) : null,
            page: max(1, $page),
            perPage: min(100, max(1, $perPage))
        );

        return response()->json([
            'items' => array_map(fn(ProductView $p) => self::formatProduct($p), $result->items),
            'total' => $result->total,
            'page' => $result->page,
            'perPage' => $result->perPage,
            'totalPages' => $result->totalPages,
        ], Response::HTTP_OK);
    }

    public function show(string $id): JsonResponse|\Illuminate\Http\Response
    {
        $product = $this->manageProducts->getProductById($id);

        if ($product === null) {
            return response('', Response::HTTP_NOT_FOUND, ['Content-Length' => '0']);
        }

        return response()->json(self::formatProduct($product), Response::HTTP_OK);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|min:1|max:255',
            'price' => 'required|numeric|gt:0',
            'stock' => 'required|integer|gte:0',
            'categoryId' => 'required|string|uuid',
            'imageKey' => 'nullable|string|max:255',
        ], [
            'name.required' => 'El nombre del producto es obligatorio',
            'price.required' => 'El precio es obligatorio',
            'price.gt' => 'El precio debe ser mayor a cero',
            'stock.required' => 'El stock es obligatorio',
            'stock.gte' => 'El stock no puede ser negativo',
            'categoryId.required' => 'La categoría es obligatoria',
            'categoryId.uuid' => 'El identificador de categoría no es un UUID válido',
        ]);

        $product = $this->manageProducts->createProduct(
            name: $data['name'],
            price: (string) $data['price'],
            stock: (int) $data['stock'],
            categoryId: $data['categoryId'],
            imageKey: $data['imageKey'] ?? null
        );

        return response()->json(self::formatProduct($product), Response::HTTP_CREATED);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|min:1|max:255',
            'price' => 'required|numeric|gt:0',
            'stock' => 'required|integer|gte:0',
            'categoryId' => 'required|string|uuid',
            'imageKey' => 'nullable|string|max:255',
        ], [
            'name.required' => 'El nombre del producto es obligatorio',
            'price.required' => 'El precio es obligatorio',
            'price.gt' => 'El precio debe ser mayor a cero',
            'stock.required' => 'El stock es obligatorio',
            'stock.gte' => 'El stock no puede ser negativo',
            'categoryId.required' => 'La categoría es obligatoria',
            'categoryId.uuid' => 'El identificador de categoría no es un UUID válido',
        ]);

        $product = $this->manageProducts->updateProduct(
            id: $id,
            name: $data['name'],
            price: (string) $data['price'],
            stock: (int) $data['stock'],
            categoryId: $data['categoryId'],
            imageKey: $data['imageKey'] ?? null
        );

        return response()->json(self::formatProduct($product), Response::HTTP_OK);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->manageProducts->deleteProduct($id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function uploadImage(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|image|max:5120',
        ], [
            'image.required' => 'El archivo de imagen es obligatorio',
            'image.image' => 'El archivo debe ser una imagen válida',
        ]);

        $file = $request->file('image');
        $content = file_get_contents($file->getRealPath());
        $extension = $file->getClientOriginalExtension() ?: 'jpg';

        $product = $this->manageProducts->uploadImage($id, $content, $extension);

        return response()->json(self::formatProduct($product), Response::HTTP_OK);
    }

    private static function formatProduct(ProductView $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (float) $p->price,
            'stock' => $p->stock,
            'categoryId' => $p->categoryId,
            'imageKey' => $p->imageKey,
        ];
    }
}
