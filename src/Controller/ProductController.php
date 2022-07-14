<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Product;
use App\Entity\Configuration;
use JMS\Serializer\Serializer;
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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class ProductController extends AbstractController
{
    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function index(ProductRepository $productRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
        {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 5);
            $idCache = "getAllProducts-" . $page . "-" . $limit;
            $jsonProductsList = $cachePool->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit, $serializer) {
                $item->tag("productsCache");
                $productsList = $productRepository->findAllWithPagination($page, $limit);
                $context = SerializationContext::create()->setGroups(['getProductDetails', 'getConfigurationFromProduct', 'getConfigurationDetails', 'getImagesFromConfiguration', 'getImageDetails']);
                return $serializer->serialize($productsList, 'json', $context);
            });
            return new JsonResponse($jsonProductsList, Response::HTTP_OK, [], true);  # Response 200
        }

    #[Route('/api/products/{id}', name: 'productShow', methods: ['GET'])]
    public function show(Product $product, SerializerInterface $serializer): JsonResponse
        {
            $context = SerializationContext::create()->setGroups(['getProductDetails', 'getConfigurationFromProduct', 'getConfigurationDetails', 'getImagesFromConfiguration', 'getImageDetails']);
            $jsonProduct = $serializer->serialize($product, 'json', $context);
            return new JsonResponse($jsonProduct, Response::HTTP_OK, ['accept' => 'json'], true);  # Response 200 if OK and 404 if not found
        }

    #[Route('/api/products', name:"productCreate", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un produit')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse 
        {
            $newProduct = $serializer->deserialize($request->getContent(), Product::class, 'json');
            $product = new Product();
            if (null !== $newProduct->getCreatedAt()) { $product->setCreatedAt($newProduct->getCreatedAt()); }
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
            // On vérifie les erreurs
            $errors = $validator->validate($product);
            if ($errors->count() > 0) {
                // throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors);
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            // Récupération de l'ensemble des données envoyées sous forme de tableau
            $content = $request->toArray();
            // Récupération des configurations. S'il n'est pas défini, alors on met -1 par défaut.
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
            $context = SerializationContext::create()->setGroups(['getProductDetails', 'getConfigurationFromProduct', 'getConfigurationDetails', 'getImagesFromConfiguration', 'getImageDetails']);
            $jsonproduct = $serializer->serialize($product, 'json', $context);
            $location = $urlGenerator->generate('productShow', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse($jsonproduct, Response::HTTP_CREATED, ["Location" => $location], true); # Response 201 - Created
        }

    #[Route('/api/products/{id}', name: 'productUpdate', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour un produit')]
    public function update(Request $request, SerializerInterface $serializer, Product $currentProduct, EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cachePool, ConfigurationRepository $configurationRepository, ImageRepository $imageRepository): JsonResponse 
        {
            $newProduct = $serializer->deserialize($request->getContent(), Product::class, 'json');
            if (null !== $newProduct->getCreatedAt()) { $currentProduct->setCreatedAt($newProduct->getCreatedAt()); }
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
            // On vérifie les erreurs
            $errors = $validator->validate($currentProduct);
            if ($errors->count() > 0) {
                // throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors);
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            // Récupération de l'ensemble des données envoyées sous forme de tableau
            $content = $request->toArray();
            // Récupération des configurations. S'il n'est pas défini, alors on met -1 par défaut.
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
            // Récupération de idConfigurations pour supprimer la liaison avec des configurations. S'il n'est pas défini, alors on null par défaut.
            $dataConfigurations = $content['dataConfigurations'] ?? null;
            if (isset($dataConfigurations)) {
                foreach($dataConfigurations as $dataConfiguration) {
                    $idConfiguration = $dataConfiguration['id'] ?? null;
                    $config = $configurationRepository->find($idConfiguration);
                    if (null === $config) { 
                        throw new HttpException(JsonResponse::HTTP_NOT_FOUND, "l'id configuration $idConfiguration n'existe pas");
                    }
                    $capacity = $dataConfiguration['capacity'] ?? null;
                    $color = $dataConfiguration['color'] ?? null;
                    $price = $dataConfiguration['price'] ?? null;
                    if (null !== $capacity) { $config->setCapacity($capacity); }
                    if (null !== $color) { $config->setColor($color); }
                    if (null !== $price) { $config->setPrice($price); }
                    if (null !== $config) { $em->persist($config); }
                    // Récupération de removeIdImages pour supprimer la liaison avec des images. S'il n'est pas défini, alors on null par défaut.
                    $removeIdImages = $dataConfiguration['removeIdImages'] ?? null;
                    if (isset($removeIdImages)) {
                        foreach($removeIdImages as $removeIdImage) {
                            $image = $imageRepository->find($removeIdImage);
                            $config->removeImage($image);
                        }
                    }
                    // Récupération de remove pour supprimer la liaison avec des configurations. S'il n'est pas défini, alors on null par défaut.
                    $deleteConfig = $dataConfiguration['remove'] ?? null;
                    if ($deleteConfig === true) { 
                        $currentProduct->removeConfiguration($config); 
                    }
                }
            }
            $em->persist($currentProduct);
            $em->flush();
            $cachePool->invalidateTags(["productsCache"]);
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT); # Response 204 - No content
        }

    #[Route('/api/products/{id}', name: 'productDestroy', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un produit')]
    public function destroy(Product $product, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $cachePool->invalidateTags(["productsCache"]);
            $em->remove($product);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT); # Response 204 - No content
        }
}
