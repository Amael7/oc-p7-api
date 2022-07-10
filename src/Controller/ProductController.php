<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function index(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
        {
            $productsList = $productRepository->findAll();
            $jsonProductsList = $serializer->serialize($productsList, 'json', ['groups' => ['getProductDetails', 'getConfigurationFromProduct', 'getConfigurationDetails', 'getImagesFromConfiguration', 'getImageDetails']]);
            return new JsonResponse($jsonProductsList, Response::HTTP_OK, [], true);  # Response 200
        }
}
