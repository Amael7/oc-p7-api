<?php

namespace App\Controller;

use App\Entity\Client;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientController extends AbstractController
{
    /**
     * List all the BileMo's clients.
     * 
     * * @OA\Response(
     *     response=200,
     *     description="Successful operation: Returns a list of all clients",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClientDetails"}))
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
     * @OA\Tag(name="Clients")
     * 
     *
     * @param ClientRepository $clientRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('/api/clients', name: 'clients', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to see the list of clients')]
    public function index(ClientRepository $clientRepository, SerializerInterface $serializer, 
                            Request $request, TagAwareCacheInterface $cachePool): JsonResponse
        {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 5);
            $idCache = "getAllClients-" . $page . "-" . $limit;
            $jsonClientsList = $cachePool->get(
                $idCache,
                function (ItemInterface $item) use ($clientRepository, $page, $limit, $serializer) 
                {
                    $item->tag("clientsCache");
                    $clientsList = $clientRepository->findAllWithPagination($page, $limit);
                    $context = SerializationContext::create()->setGroups(['getClientDetails', 'getCustomersFromClient', 'getCustomerDetails']);
                    return $serializer->serialize($clientsList, 'json', $context);
            });
            return new JsonResponse($jsonClientsList, Response::HTTP_OK, [], true);  # Response 200
        }

    
    /**
     * List characteristic of the specified BileMo's client.
     * 
     * * @OA\Response(
     *     response=200,
     *     description="Successful operation: Return the characteristics of the specified client",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClientDetails"}))
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
     *     description="The Client unique identifier.",
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
     * @OA\Tag(name="Clients")
     *
     * @param Client $client
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/clients/{id}', name: 'clientShow', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to see the details of a clients')]
    public function show(Client $client, SerializerInterface $serializer): JsonResponse
        {
            $context = SerializationContext::create()->setGroups(
                ['getClientDetails', 'getCustomersFromClient', 'getCustomerDetails']
            );
            $jsonClient = $serializer->serialize($client, 'json', $context);
            return new JsonResponse($jsonClient, Response::HTTP_OK, ['accept' => 'json'], true);  # Response 200 if OK and 404 if not found
        }

    /**
     * Create a new BileMo's client.
     * 
     * * @OA\Response(
     *     response=201,
     *     description="Successful operation: new client created",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClientDetails"}))
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
     *     @Model(type=Client::class)
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
     * @OA\Tag(name="Clients")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param UserPasswordHasherInterface $passwordHasher
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('/api/clients', name:"clientCreate", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to create a client')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
                            UrlGeneratorInterface $urlGenerator, 
                            UserPasswordHasherInterface $passwordHasher, 
                            ValidatorInterface $validator): JsonResponse 
        {
            $newClient = $serializer->deserialize($request->getContent(), Client::class, 'json');
            $client = new Client();
            if (null !== $newClient->getCompany()) { $client->setCompany($newClient->getCompany()); }
            if (null !== $newClient->getEmail()) { $client->setEmail($newClient->getEmail()); }
            if (null !== $newClient->getPassword()) { $client->setPassword($passwordHasher->hashPassword($client, $newClient->getPassword())); }
            // Get the request data
            $content = $request->toArray();
            $customers = $content['customers'] ?? null;
            if (isset($customers)) { 
                foreach($customers as $customer) {
                    $newCustomer = new Customer();
                    $newCustomer->setEmail($customer['email'])
                                ->setFirstName($customer['firstName'])
                                ->setLastName($customer['lastName']);
                    $client->addCustomer($newCustomer);
                }
            }
            // Errors Check
            $errors = $validator->validate($client);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $em->persist($client);
            $em->flush();
            $context = SerializationContext::create()->setGroups(
                ['getClientDetails', 'getCustomersFromClient', 'getCustomerDetails']
            );
            $jsonClient = $serializer->serialize($client, 'json', $context);
            $location = $urlGenerator->generate('clientShow', ['id' => $client->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse($jsonClient, Response::HTTP_CREATED, ["Location" => $location], true); # Response 201 - Created
        }

    /**
     * Update a BileMo's client.
     * 
     * * @OA\Response(
     *     response=204,
     *     description="Successful operation: Updated",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClientDetails"}))
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
     *     @Model(type=Client::class)
     * )
     * 
     * @OA\Parameter(
     *     name="id",
     *     required= true,
     *     in="path",
     *     description="The Client unique identifier.",
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
     * @OA\Tag(name="Clients")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param UserPasswordHasherInterface $passwordHasher
     * @param Client $currentClient
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cachePool
     * @param CustomerRepository $customerRepository
     * @return JsonResponse
     */
    #[Route('/api/clients/{id}', name: 'clientUpdate', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to update a client')]
    public function update(Request $request, SerializerInterface $serializer, 
                            Client $currentClient, EntityManagerInterface $em, 
                            ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $newClient = $serializer->deserialize($request->getContent(), Client::class, 'json');
            if (null !== $newClient->getCompany()) { $currentClient->setCompany($newClient->getCompany()); }
            if (null !== $newClient->getEmail()) { $currentClient->setEmail($newClient->getEmail()); }
            // Get Request Data
            $content = $request->toArray();
            $customers = $content['customers'] ?? null;
            if (isset($customers)) { 
                foreach($customers as $customer) {
                    $newCustomer = new Customer();
                    $newCustomer->setEmail($customer['email'])
                                ->setFirstName($customer['firstName'])
                                ->setLastName($customer['lastName']);
                    $currentClient->addCustomer($newCustomer);
                }
            }
            // Errors Check
            $errors = $validator->validate($currentClient);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $em->persist($currentClient);
            $em->flush();
            $cachePool->invalidateTags(["clientsCache"]);
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT); # Response 204 - No content
        }

    /**
     * Delete a BileMo's client.
     * 
     * * @OA\Response(
     *     response=204,
     *     description="Successful operation: No-Content",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClientDetails"}))
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
     *     description="The Client unique identifier.",
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
     * @OA\Tag(name="Clients")
     *
     * @param Client $client
     * @param EntityManagerInterface $em
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('/api/clients/{id}', name: 'clientDestroy', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to delete a client')]
    public function destroy(Client $client, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $cachePool->invalidateTags(["clientsCache"]);
            $em->remove($client);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT); # Response 204 - No content
        }

    
    /**
     * Update password BileMo's client.
     * 
     * * @OA\Response(
     *     response=204,
     *     description="Successful operation: Updated",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClientDetails"}))
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
     *     @Model(type=Client::class)
     * )
     * 
     * @OA\Parameter(
     *     name="id",
     *     required= true,
     *     in="path",
     *     description="The Client unique identifier.",
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
     * @OA\Tag(name="Clients")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param UserPasswordHasherInterface $passwordHasher
     * @param Client $currentClient
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $cachePool
     * @param CustomerRepository $customerRepository
     * @return JsonResponse
     */
    #[Route('/api/clients/{id}/password', name: 'clientPassword', methods: ['Put'])]
    public function updatePassword(Request $request, SerializerInterface $serializer, 
                                    UserPasswordHasherInterface $passwordHasher, 
                                    Client $client, EntityManagerInterface $em, 
                                    ValidatorInterface $validator, TagAwareCacheInterface $cachePool, 
                                    ClientRepository $clientRepository): JsonResponse 
        {
            // Verification if the client is the current user
            $currentUser = $clientRepository->findOneBy(['email'=> (string)$this->getUser()->getUserIdentifier()]);
            $currentUserId = $currentUser->getId();
            if ($currentUserId !== $client->getId()) {
                throw new HttpException(
                    JsonResponse::HTTP_BAD_REQUEST,
                    "You can only consult your own client information. Your id is $currentUserId"
                );
            }
            $newClient = $serializer->deserialize($request->getContent(), Client::class, 'json');
            if (null !== $newClient->getPassword()) { $client->setPassword($passwordHasher->hashPassword($client, $newClient->getPassword())); }
            // Errors Check
            $errors = $validator->validate($client);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $em->persist($client);
            $em->flush();
            $cachePool->invalidateTags(["clientsCache"]);
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT); # Response 204 - No content
        }
}
