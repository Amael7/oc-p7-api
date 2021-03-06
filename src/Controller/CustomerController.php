<?php

namespace App\Controller;

use App\Entity\Customer;
use OpenApi\Annotations as OA;
use App\Repository\ClientRepository;
use App\Repository\CustomerRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
     * 
     * @OA\Parameter(
     *     name="Authorization",
     *     required= true,
     *     in="header",
     *     description="Bearer JWT Token.",
     *     @OA\Schema(type="string")
     * )
     * 
     * @OA\Tag(name="Customers")
     *
     * @param CustomerRepository $customerRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('/api/customers', name: 'customers', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository, ClientRepository $clientRepository, 
                            SerializerInterface $serializer, Request $request, 
                            TagAwareCacheInterface $cachePool): JsonResponse
        {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 5);
            $idCache = "getAllCustomers-" . $page . "-" . $limit;
            $jsoncustomersList = $cachePool->get(
                $idCache, 
                function (ItemInterface $item) use ($customerRepository, $clientRepository, $page, $limit, $serializer) 
                {
                    $item->tag("customersCache");
                    $currentUser = $clientRepository->findOneBy(['email'=> (string)$this->getUser()->getUserIdentifier()]);
                    $customersList = $customerRepository->findAllWithPagination($page, $limit, $currentUser);
                    $context = SerializationContext::create()->setGroups(['getCustomerDetails']);
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
     * @OA\Parameter(
     *     name="id",
     *     required= true,
     *     in="path",
     *     description="The Customer unique identifier.",
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
     * @OA\Tag(name="Customers")
     *
     * @param Customer $customer
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/customers/{id}', name: 'customerShow', methods: ['GET'])]
    public function show(Customer $customer, SerializerInterface $serializer, 
                            ClientRepository $clientRepository): JsonResponse
        {
            // Verification if the customer to the current user client
            $currentUser = $clientRepository->findOneBy(['email'=> (string)$this->getUser()->getUserIdentifier()]);
            $arr = [];
            foreach($customer->getClients() as $client) {
                array_push($arr, $client->getId());
            }
            if (in_array($currentUser->getId(), $arr) !== true) {
                throw new HttpException(JsonResponse::HTTP_FORBIDDEN, "this customer isn't related to you");
            }
            $context = SerializationContext::create()->setGroups(['getCustomerDetails']);
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
     * @OA\RequestBody(
     *     @Model(type=Customer::class)
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
    public function create(Request $request, SerializerInterface $serializer, 
                            EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, 
                            ClientRepository $clientRepository, ValidatorInterface $validator): JsonResponse 
        {
            $newCustomer = $serializer->deserialize($request->getContent(), Customer::class, 'json');
            $customer = new Customer();
            if (null !== $newCustomer->getEmail()) { $customer->setEmail($newCustomer->getEmail()); }
            if (null !== $newCustomer->getLastName()) { $customer->setLastName($newCustomer->getLastName()); }
            if (null !== $newCustomer->getFirstName()) { $customer->setFirstName($newCustomer->getFirstName()); }
            // Link the customer to the current client
            $currentUser = $clientRepository->findOneBy(['email'=> (string)$this->getUser()->getUserIdentifier()]);
            $customer->setClient($currentUser);
            // Errors Check
            $errors = $validator->validate($customer);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $em->persist($customer);
            $em->flush();
            $context = SerializationContext::create()->setGroups(['getCustomerDetails']);
            $jsonCustomer = $serializer->serialize($customer, 'json', $context);
            $location = $urlGenerator->generate('customerShow', ['id' => $customer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse($jsonCustomer, Response::HTTP_CREATED, ["Location" => $location], true); # Response 201 - Created
        }

    /**
     * Update a customer.
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
     * @OA\Parameter(
     *     name="id",
     *     required= true,
     *     in="path",
     *     description="The Customer unique identifier.",
     *     @OA\Schema(type="int")
     * )
     * 
     * @OA\RequestBody(
     *     @Model(type=Customer::class)
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
    public function update(Request $request, SerializerInterface $serializer,
                            Customer $currentCustomer, EntityManagerInterface $em, 
                            ClientRepository $clientRepository, ValidatorInterface $validator, 
                            TagAwareCacheInterface $cachePool): JsonResponse 
        {
            // Verification if the customer to the current user client
            $currentUser = $clientRepository->findOneBy(['email'=> (string)$this->getUser()->getUserIdentifier()]);
            $arr = [];
            foreach($currentCustomer->getClients() as $client) {
                array_push($arr, $client->getId());
            }
            if (in_array($currentUser->getId(), $arr) !== true) {
                throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "this customer isn't related to you");
            }
            $newCustomer = $serializer->deserialize($request->getContent(), Customer::class, 'json');
            if (null !== $newCustomer->getEmail()) { $currentCustomer->setEmail($newCustomer->getEmail()); }
            if (null !== $newCustomer->getLastName()) { $currentCustomer->setLastName($newCustomer->getLastName()); }
            if (null !== $newCustomer->getFirstName()) { $currentCustomer->setFirstName($newCustomer->getFirstName()); }
            // Errors Check
            $errors = $validator->validate($currentCustomer);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
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
     * @OA\Parameter(
     *     name="id",
     *     required= true,
     *     in="path",
     *     description="The Customer unique identifier.",
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
     * @OA\Tag(name="Customers")
     *
     * @param Customer $customer
     * @param EntityManagerInterface $em
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('/api/customers/{id}', name: 'customerDestroy', methods: ['DELETE'])]
    public function destroy(Customer $customer, ClientRepository $clientRepository, 
                            EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            // Verification if the customer to the current user client
            $currentUser = $clientRepository->findOneBy(['email'=> (string)$this->getUser()->getUserIdentifier()]);
            $arr = [];
            foreach($customer->getClients() as $client) {
                array_push($arr, $client->getId());
            }
            if (in_array($currentUser->getId(), $arr) !== true) {
                throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "this customer isn't related to you");
            }
            $cachePool->invalidateTags(["customersCache"]);
            $em->remove($customer);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT); # Response 204 - No content
        }
}
