<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Product;
use App\Entity\Configuration;
use App\Repository\ImageRepository;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
use App\Repository\ConfigurationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class ProductController extends AbstractController
{
    
    /**
     * List all the products.
     * 
     * * @OA\Response(
     *     response=200,
     *     description="Successful operation: Returns a list of all products",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"getProductDetails"}))
     *     )
     * )
     * 
     * * @OA\Response(
     *     response=400,
     *     description="Bad Request: This method is not allowed for this route",
     * )
     * 
     * * @OA\Response(
     *     response=401,
     *     description="Unauthorized: Expired JWT Token/JWT Token not found",
     * )
     * 
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="The page you want to retrieve",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="The number of items to be retrieved",
     *     @OA\Schema(type="int")
     * )
     * 
     * @OA\Parameter(
     *     name="Authorization",
     *     required= true,
     *     in="header",
     *     description="Bearer JWT Token.",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Products")
     *
     * @param ProductRepository $productRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function index(ProductRepository $productRepository, SerializerInterface $serializer, 
                            Request $request, TagAwareCacheInterface $cachePool): JsonResponse
        {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 5);
            $idCache = "getAllProducts-" . $page . "-" . $limit;
            $jsonProductsList = $cachePool->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit, $serializer) {
                $item->tag("productsCache");
                $productsList = $productRepository->findAllWithPagination($page, $limit);
                $context = SerializationContext::create()->setGroups(
                    ['getProductDetails', 
                        'getConfigurationFromProduct',
                        'getConfigurationDetails', 
                        'getImagesFromConfiguration', 
                        'getImageDetails'
                    ]);
                return $serializer->serialize($productsList, 'json', $context);
            });
            return new JsonResponse($jsonProductsList, Response::HTTP_OK, [], true);  # Response 200
        }

    
    /**
     * List characteristic of the specified product.
     * 
     * * @OA\Response(
     *     response=200,
     *     description="Successful operation: Return the characteristics of the specified product",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"getProductDetails"}))
     *     )
     * )
     * 
     * * @OA\Response(
     *     response=400,
     *     description="Bad Request: This method is not allowed for this route",
     * )
     * 
     * * @OA\Response(
     *     response=401,
     *     description="Unauthorized: Expired JWT Token/JWT Token not found",
     * )
     * 
     * * @OA\Response(
     *     response=404,
     *     description="Object not found: Invalid route or resource ID",
     * )
     * 
     * @OA\Parameter(
     *     name="id",
     *     required= true,
     *     in="path",
     *     description="The Product unique identifier.",
     *     @OA\Schema(type="int")
     * )
     * 
     * @OA\Parameter(
     *     name="Authorization",
     *     required= true,
     *     in="header",
     *     description="Bearer JWT Token.",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Products")
     *
     * @param Product $product
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/products/{id}', name: 'productShow', methods: ['GET'])]
    public function show(Product $product, SerializerInterface $serializer): JsonResponse
        {
            $context = SerializationContext::create()->setGroups(
                ['getProductDetails',
                    'getConfigurationFromProduct',
                    'getConfigurationDetails',
                    'getImagesFromConfiguration', 
                    'getImageDetails'
                ]);
            $jsonProduct = $serializer->serialize($product, 'json', $context);
            return new JsonResponse($jsonProduct, Response::HTTP_OK, ['accept' => 'json'], true);  # Response 200 if OK and 404 if not found
        }

    /**
     * Create a new product.
     * 
     * * @OA\Response(
     *     response=201,
     *     description="Successful operation: new product created",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"getProductDetails"}))
     *     )
     * )
     * 
     * * @OA\Response(
     *     response=400,
     *     description="Bad Request: This method is not allowed for this route",
     * )
     * 
     * * @OA\Response(
     *     response=401,
     *     description="Unauthorized: Expired JWT Token/JWT Token not found",
     * )
     * 
     * * @OA\Response(
     *     response=403,
     *     description="Forbidden: You are not allowed to access to this page",
     * )
     * 
     * * @OA\Response(
     *     response=404,
     *     description="Object not found: Invalid route or resource ID",
     * )
     * 
     * @OA\RequestBody(
     *     @Model(type=Product::class)
     * )
     * 
     * @OA\Parameter(
     *     name="Authorization",
     *     required= true,
     *     in="header",
     *     description="Bearer JWT Token.",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Products")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('/api/products', name:"productCreate", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to create a product')]
    public function create(Request $request, SerializerInterface $serializer, 
                            EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, 
                            ValidatorInterface $validator): JsonResponse 
        {
            $newProduct = $serializer->deserialize($request->getContent(), Product::class, 'json');
            $product = new Product();
            if (null !== $newProduct->getManufacturer()) { $product->setManufacturer($newProduct->getManufacturer()); }
            if (null !== $newProduct->getName()) { $product->setName($newProduct->getName()); }
            if (null !== $newProduct->getDescription()) { $product->setDescription($newProduct->getDescription()); }
            if (null !== $newProduct->getScreenSize()) { $product->setScreenSize($newProduct->getScreenSize()); }
            if (null !== $newProduct->isCamera()) { $product->setCamera($newProduct->isCamera()); }
            if (null !== $newProduct->isBluetooth()) { $product->setBluetooth($newProduct->isBluetooth()); }
            if (null !== $newProduct->isWifi()) { $product->setWifi($newProduct->isWifi()); }
            if (null !== $newProduct->getLength()) { $product->setLength($newProduct->getLength()); }
            if (null !== $newProduct->getWidth()) { $product->setWidth($newProduct->getWidth()); }
            if (null !== $newProduct->getHeight()) { $product->setHeight($newProduct->getHeight()); }
            if (null !== $newProduct->getWeight()) { $product->setWeight($newProduct->getWeight()); }
            if (null !== $newProduct->getDas()) { $product->setDas($newProduct->getDas()); }
            // Errors Check
            $errors = $validator->validate($product);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            // Get request data
            $content = $request->toArray();
            // Get configurations.
            $configurations = $content['configurations'] ?? null;
            if (isset($configurations)) {
                foreach($configurations as $configuration) {
                    $config = new Configuration();
                    $config->setCapacity($configuration['capacity'])
                            ->setColor($configuration['color'])
                            ->setPrice($configuration['price']);
                            $em->persist($config);
                            $product->addConfiguration($config);
                    $images = $configuration['images'];
                    if (isset($images)) {
                        foreach($images as $image) {
                            $newImage = new Image();
                            $newImage->setUrl($image['url']);
                            $em->persist($newImage);
                            $config->addImage($newImage);
                        }
                    }
                }
            }
            $em->persist($product);
            $em->flush();
            $context = SerializationContext::create()->setGroups(
                ['getProductDetails', 
                    'getConfigurationFromProduct', 
                    'getConfigurationDetails', 
                    'getImagesFromConfiguration', 
                    'getImageDetails'
                ]);
            $jsonproduct = $serializer->serialize($product, 'json', $context);
            $location = $urlGenerator->generate('productShow', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse($jsonproduct, Response::HTTP_CREATED, ["Location" => $location], true); # Response 201 - Created
        }

    /**
     * Update a product.
     * 
     * * @OA\Response(
     *     response=204,
     *     description="Successful operation: Updated",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"getProductDetails"}))
     *     )
     * )
     * 
     * * @OA\Response(
     *     response=400,
     *     description="Bad Request: This method is not allowed for this route",
     * )
     * 
     * * @OA\Response(
     *     response=401,
     *     description="Unauthorized: Expired JWT Token/JWT Token not found",
     * )
     * 
     * * @OA\Response(
     *     response=403,
     *     description="Forbidden: You are not allowed to access to this page",
     * )
     * 
     * * @OA\Response(
     *     response=404,
     *     description="Object not found: Invalid route or resource ID",
     * )
     * 
     * @OA\RequestBody(
     *     @Model(type=Product::class)
     * )
     * 
     * @OA\Parameter(
     *     name="id",
     *     required= true,
     *     in="path",
     *     description="The Product unique identifier.",
     *     @OA\Schema(type="int")
     * )
     * 
     * @OA\Parameter(
     *     name="Authorization",
     *     required= true,
     *     in="header",
     *     description="Bearer JWT Token.",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Products")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param Product $currentProduct
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cachePool
     * @param ConfigurationRepository $configurationRepository
     * @param ImageRepository $imageRepository
     * @return JsonResponse
     */
    #[Route('/api/products/{id}', name: 'productUpdate', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to update a product')]
    public function update(Request $request, SerializerInterface $serializer, 
                            Product $currentProduct, EntityManagerInterface $em, 
                            ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $newProduct = $serializer->deserialize($request->getContent(), Product::class, 'json');
            if (null !== $newProduct->getManufacturer()) { $currentProduct->setManufacturer($newProduct->getManufacturer()); }
            if (null !== $newProduct->getName()) { $currentProduct->setName($newProduct->getName()); }
            if (null !== $newProduct->getDescription()) { $currentProduct->setDescription($newProduct->getDescription()); }
            if (null !== $newProduct->getScreenSize()) { $currentProduct->setScreenSize($newProduct->getScreenSize()); }
            if (null !== $newProduct->isCamera()) { $currentProduct->setCamera($newProduct->isCamera()); }
            if (null !== $newProduct->isBluetooth()) { $currentProduct->setBluetooth($newProduct->isBluetooth()); }
            if (null !== $newProduct->isWifi()) { $currentProduct->setWifi($newProduct->isWifi()); }
            if (null !== $newProduct->getLength()) { $currentProduct->setLength($newProduct->getLength()); }
            if (null !== $newProduct->getWidth()) { $currentProduct->setWidth($newProduct->getWidth()); }
            if (null !== $newProduct->getHeight()) { $currentProduct->setHeight($newProduct->getHeight()); }
            if (null !== $newProduct->getWeight()) { $currentProduct->setWeight($newProduct->getWeight()); }
            if (null !== $newProduct->getDas()) { $currentProduct->setDas($newProduct->getDas()); }
            // Errors check
            $errors = $validator->validate($currentProduct);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            // Get request data
            $content = $request->toArray();
            // Get configurations.
            $configurations = $content['configurations'] ?? null;
            if (isset($configurations)) {
                foreach($configurations as $configuration) {
                    $config = new Configuration();
                    $config->setCapacity($configuration['capacity'])
                            ->setColor($configuration['color'])
                            ->setPrice($configuration['price']);
                            $em->persist($config);
                            $currentProduct->addConfiguration($config);
                    $images = $configuration['images'];
                    if (isset($images)) {
                        foreach($images as $image) {
                            $newImage = new Image();
                            $newImage->setUrl($image['url']);
                            $em->persist($newImage);
                            $config->addImage($newImage);
                        }
                    }
                }
            }
            $em->persist($currentProduct);
            $em->flush();
            $cachePool->invalidateTags(["productsCache"]);
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT); # Response 204 - No content
        }

    /**
     * Delete a product.
     * 
     * * @OA\Response(
     *     response=204,
     *     description="Successful operation: No-Content",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"getProductDetails"}))
     *     )
     * )
     * 
     * * @OA\Response(
     *     response=400,
     *     description="Bad Request: This method is not allowed for this route",
     * )
     * 
     * * @OA\Response(
     *     response=401,
     *     description="Unauthorized: Expired JWT Token/JWT Token not found",
     * )
     * 
     * * @OA\Response(
     *     response=403,
     *     description="Forbidden: You are not allowed to access to this page",
     * )
     * 
     * * @OA\Response(
     *     response=404,
     *     description="Object not found: Invalid route or resource ID",
     * )
     * 
     * @OA\Parameter(
     *     name="id",
     *     required= true,
     *     in="path",
     *     description="The Product unique identifier.",
     *     @OA\Schema(type="int")
     * )
     * 
     * @OA\Parameter(
     *     name="Authorization",
     *     required= true,
     *     in="header",
     *     description="Bearer JWT Token.",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Products")
     *
     * @param Product $product
     * @param EntityManagerInterface $em
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('/api/products/{id}', name: 'productDestroy', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to delete a product')]
    public function destroy(Product $product, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $cachePool->invalidateTags(["productsCache"]);
            $em->remove($product);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT); # Response 204 - No content
        }
}
