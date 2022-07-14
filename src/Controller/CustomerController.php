<?php

namespace App\Controller;

use App\Entity\Customer;
use JMS\Serializer\Serializer;
use App\Repository\ClientRepository;
use App\Repository\CustomerRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class CustomerController extends AbstractController
{
    /**
     * List all the customers.
     * 
     * * @OA\Response(
     *     response=200,
     *     description="Successful operation: Returns a list of all customers",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Customer::class, groups={"getCustomerDetails"}))
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
     * @OA\Tag(name="Customers")
     *
     * @param CustomerRepository $customerRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('/api/customers', name: 'customers', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
        {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 5);
            $idCache = "getAllCustomers-" . $page . "-" . $limit;
            $jsoncustomersList = $cachePool->get($idCache, function (ItemInterface $item) use ($customerRepository, $page, $limit, $serializer) {
                $item->tag("customersCache");
                $customersList = $customerRepository->findAllWithPagination($page, $limit);
                $context = SerializationContext::create()->setGroups(['getCustomerDetails', 'getClientsFromCustomer', 'getClientDetails']);
                return $serializer->serialize($customersList, 'json', $context);
            });
            return new JsonResponse($jsoncustomersList, Response::HTTP_OK, [], true);  # Response 200
        }

    /**
     * List characteristic of the specified customer.
     * 
     * * @OA\Response(
     *     response=200,
     *     description="Successful operation: Return the characteristics of the specified customer",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Customer::class, groups={"getCustomerDetails"}))
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
     * @OA\Tag(name="Customers")
     *
     * @param Customer $customer
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/customers/{id}', name: 'customerShow', methods: ['GET'])]
    public function show(Customer $customer, SerializerInterface $serializer): JsonResponse
        {
            $context = SerializationContext::create()->setGroups(['getCustomerDetails', 'getClientsFromCustomer', 'getClientDetails']);
            $jsonCustomer = $serializer->serialize($customer, 'json', $context);
            return new JsonResponse($jsonCustomer, Response::HTTP_OK, ['accept' => 'json'], true);  # Response 200 if OK and 404 if not found
        }

    /**
     * Create a new customer.
     * 
     * * @OA\Response(
     *     response=201,
     *     description="Successful operation: new customer created",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Customer::class, groups={"getCustomerDetails"}))
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
     * @OA\Tag(name="Customers")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param ClientRepository $clientRepository
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('/api/customers', name:"customerCreate", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un customer')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ClientRepository $clientRepository, ValidatorInterface $validator): JsonResponse 
        {
            $newCustomer = $serializer->deserialize($request->getContent(), Customer::class, 'json');
            $customer = new Customer();
            if (null !== $newCustomer->getCreatedAt()) { $customer->setCreatedAt($newCustomer->getCreatedAt()); }
            if (null !== $newCustomer->getEmail()) { $customer->setEmail($newCustomer->getEmail()); }
            if (null !== $newCustomer->getLastName()) { $customer->setLastName($newCustomer->getLastName()); }
            if (null !== $newCustomer->getFirstName()) { $customer->setFirstName($newCustomer->getFirstName()); }
            // On vérifie les erreurs
            $errors = $validator->validate($customer);
            if ($errors->count() > 0) {
                // throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors);
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            // Récupération de l'ensemble des données envoyées sous forme de tableau
            $content = $request->toArray();
            // Récupération de l'idClients. S'il n'est pas défini, alors on met -1 par défaut.
            $idClients = $content['idClients'] ?? null;
            if (isset($idClients)) {
                foreach($idClients as $idClient) {
                    $customer->setClient($clientRepository->find($idClient));
                }
            }
            $em->persist($customer);
            $em->flush();
            $context = SerializationContext::create()->setGroups(['getCustomerDetails', 'getClientsFromCustomer', 'getClientDetails']);
            $jsonCustomer = $serializer->serialize($customer, 'json', $context);
            $location = $urlGenerator->generate('customerShow', ['id' => $customer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse($jsonCustomer, Response::HTTP_CREATED, ["Location" => $location], true); # Response 201 - Created
        }

    /**
     * Update a ustomer.
     * 
     * * @OA\Response(
     *     response=204,
     *     description="Successful operation: Updated",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Customer::class, groups={"getCustomerDetails"}))
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
     * @OA\Tag(name="Customers")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param Customer $currentCustomer
     * @param EntityManagerInterface $em
     * @param ClientRepository $clientRepository
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('/api/customers/{id}', name: 'customerUpdate', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour un customer')]
    public function update(Request $request, SerializerInterface $serializer, Customer $currentCustomer, EntityManagerInterface $em, ClientRepository $clientRepository, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $newCustomer = $serializer->deserialize($request->getContent(), Customer::class, 'json');
            if (null !== $newCustomer->getCreatedAt()) { $currentCustomer->setCreatedAt($newCustomer->getCreatedAt()); }
            if (null !== $newCustomer->getEmail()) { $currentCustomer->setEmail($newCustomer->getEmail()); }
            if (null !== $newCustomer->getLastName()) { $currentCustomer->setLastName($newCustomer->getLastName()); }
            if (null !== $newCustomer->getFirstName()) { $currentCustomer->setFirstName($newCustomer->getFirstName()); }
            // On vérifie les erreurs
            $errors = $validator->validate($currentCustomer);
            if ($errors->count() > 0) {
                // throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors);
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            // Récupération de l'ensemble des données envoyées sous forme de tableau
            $content = $request->toArray();
            // Récupération de l'idClients pour lier des clients. S'il n'est pas défini, alors on null par défaut.
            $idClients = $content['idClients'] ?? null;
            if (isset($idClients)) {
                foreach($idClients as $idClient) {
                    $currentCustomer->setClient($clientRepository->find($idClient));
                }
            }
            // Récupération de removeIdClients pour supprimer la liaison avec des clients. S'il n'est pas défini, alors on null par défaut.
            $removeIdClients = $content['removeIdClients'] ?? null;
            if (isset($removeIdClients)) {
                foreach($removeIdClients as $removeIdClient) {
                    $currentCustomer->removeClient($clientRepository->find($removeIdClient));
                }
            }
            $em->persist($currentCustomer);
            $em->flush();
            $cachePool->invalidateTags(["customersCache"]);
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT); # Response 204 - No content
        }

    
    /**
     * Delete a customer.
     * 
     * * @OA\Response(
     *     response=204,
     *     description="Successful operation: No-Content",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Customer::class, groups={"getCustomerDetails"}))
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
     * @OA\Tag(name="Customers")
     *
     * @param Customer $customer
     * @param EntityManagerInterface $em
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('/api/customers/{id}', name: 'customerDestroy', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un customer')]
    public function destroy(Customer $customer, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $cachePool->invalidateTags(["customersCache"]);
            $em->remove($customer);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT); # Response 204 - No content
        }
}
