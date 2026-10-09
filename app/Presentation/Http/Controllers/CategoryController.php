<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\Ports\Inbound\ManageProducts;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CategoryController
{
    public function __construct(
        private readonly ManageProducts $manageProducts
    ) {}

    public function index(): JsonResponse
    {
        $categories = $this->manageProducts->listCategories();

        return response()->json($categories, Response::HTTP_OK);
    }
}
