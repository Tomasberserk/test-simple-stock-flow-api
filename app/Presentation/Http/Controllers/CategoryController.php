<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\Ports\Outbound\CategoryRepository;
use App\Domain\Model\Category;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CategoryController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {}

    public function index(): JsonResponse
    {
        $categories = $this->categoryRepository->findAll();

        $data = array_map(function (Category $category) {
            return [
                'id' => $category->getId()->getValue(),
                'name' => $category->getName(),
            ];
        }, $categories);

        return response()->json($data, Response::HTTP_OK);
    }
}
