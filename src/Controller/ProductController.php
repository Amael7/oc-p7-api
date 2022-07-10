<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/api/products/{id}', name: 'productShow', methods: ['GET'])]
        public function show(Product $product, SerializerInterface $serializer): JsonResponse
            {
                $jsonProduct = $serializer->serialize($product, 'json', ['groups' => ['getProductDetails', 'getConfigurationFromProduct', 'getConfigurationDetails', 'getImagesFromConfiguration', 'getImageDetails']]);
                return new JsonResponse($jsonProduct, Response::HTTP_OK, ['accept' => 'json'], true);  # Response 200 if OK and 404 if not found
            }

    #[Route('/api/products/{id}', name: 'productDestroy', methods: ['DELETE'])]
        public function destroy(Product $product, EntityManagerInterface $em): JsonResponse 
            {
                $em->remove($product);
                $em->flush();
                return new JsonResponse(null, Response::HTTP_NO_CONTENT); # Response 204 - No content
            }
}
