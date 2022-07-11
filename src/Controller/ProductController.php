<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Product;
use App\Entity\Configuration;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConfigurationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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

    #[Route('/api/products', name:"productCreate", methods: ['POST'])]
        public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse 
            {
                $product = $serializer->deserialize($request->getContent(), Product::class, 'json');

                $em->persist($product);
                $em->flush();
                $jsonproduct = $serializer->serialize($product, 'json', ['groups' => ['getProductDetails', 'getConfigurationFromProduct', 'getConfigurationDetails', 'getImagesFromConfiguration', 'getImageDetails']]);
                $location = $urlGenerator->generate('productShow', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                return new JsonResponse($jsonproduct, Response::HTTP_CREATED, ["Location" => $location], true); # Response 201 - Created
            }

    #[Route('/api/products/{id}', name: 'productUpdate', methods: ['PUT'])]
        public function update(Request $request, SerializerInterface $serializer, Product $currentProduct, EntityManagerInterface $em, ConfigurationRepository $configurationRepository): JsonResponse 
            {
                $updatedProduct = $serializer->deserialize($request->getContent(), 
                        Product::class, 
                        'json', 
                        [AbstractNormalizer::OBJECT_TO_POPULATE => $currentProduct]);
                $em->persist($updatedProduct);
                $em->flush();
                return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT); # Response 204 - No content
            }

    #[Route('/api/products/{id}', name: 'productDestroy', methods: ['DELETE'])]
        public function destroy(Product $product, EntityManagerInterface $em): JsonResponse 
            {
                $em->remove($product);
                $em->flush();
                return new JsonResponse(null, Response::HTTP_NO_CONTENT); # Response 204 - No content
            }
}
