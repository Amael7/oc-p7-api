<?php

namespace App\Controller;

use App\Entity\Client;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class ClientController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer l'ensemble des clients.
     * 
     * * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des clients",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClientDetails"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'éléments que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
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
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour voir la liste des clients')]
    public function index(ClientRepository $clientRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
        {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 5);
            $idCache = "getAllClients-" . $page . "-" . $limit;
            $jsonClientsList = $cachePool->get($idCache, function (ItemInterface $item) use ($clientRepository, $page, $limit, $serializer) {
                $item->tag("clientsCache");
                $clientsList = $clientRepository->findAllWithPagination($page, $limit);
                $context = SerializationContext::create()->setGroups(['getClientDetails', 'getCustomersFromClient', 'getCustomerDetails']);
                return $serializer->serialize($clientsList, 'json', $context);
            });
            return new JsonResponse($jsonClientsList, Response::HTTP_OK, [], true);  # Response 200
        }

    
    /**
     * Cette méthode permet de récupérer l'ensemble des details d'un client.
     * 
     * * @OA\Response(
     *     response=200,
     *     description="Retourne l'ensemble des details d'un client",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClientDetails"}))
     *     )
     * )
     * 
     * @OA\Tag(name="Clients")
     *
     * @param Client $client
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/api/clients/{id}', name: 'clientShow', methods: ['GET'])]
    public function show(Client $client, SerializerInterface $serializer): JsonResponse
        {
            $context = SerializationContext::create()->setGroups(['getClientDetails', 'getCustomersFromClient', 'getCustomerDetails']);
            $jsonClient = $serializer->serialize($client, 'json', $context);
            return new JsonResponse($jsonClient, Response::HTTP_OK, ['accept' => 'json'], true);  # Response 200 if OK and 404 if not found
        }

    /**
     * Cette méthode permet de créer un client.
     * 
     * * @OA\Response(
     *     response=200,
     *     description="Retourne une response 201 - Created",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClientDetails"}))
     *     )
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
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un client')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): JsonResponse 
        {
            $newClient = $serializer->deserialize($request->getContent(), Client::class, 'json');
            $client = new Client();
            if (null !== $newClient->getCreatedAt()) { $client->setCreatedAt($newClient->getCreatedAt()); }
            if (null !== $newClient->getCompany()) { $client->setCompany($newClient->getCompany()); }
            if (null !== $newClient->getEmail()) { $client->setEmail($newClient->getEmail()); }
            if (null !== $newClient->getPassword()) { $client->setPassword($passwordHasher->hashPassword($client, $newClient->getPassword())); }
            if (null !== $newClient->getCreatedAt()) { $client->setCreatedAt($newClient->getCreatedAt()); }
            if (null !== $newClient->getRoles()) { $client->setRoles($newClient->getRoles()); }
            // // Récupération de l'ensemble des données envoyées sous forme de tableau
            $content = $request->toArray();
            $customers = $content['customers'];
            if (isset($customers)) { 
                foreach($customers as $customer) {
                    $newCustomer = new Customer();
                    $newCustomer->setEmail($customer['email'])
                                ->setFirstName($customer['firstName'])
                                ->setLastName($customer['lastName']);
                    $client->addCustomer($newCustomer);
                }
            }
            // On vérifie les erreurs
            $errors = $validator->validate($client);
            if ($errors->count() > 0) {
                // throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors);
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $em->persist($client);
            $em->flush();
            $context = SerializationContext::create()->setGroups(['getClientDetails', 'getCustomersFromClient', 'getCustomerDetails']);
            $jsonClient = $serializer->serialize($client, 'json', $context);
            $location = $urlGenerator->generate('clientShow', ['id' => $client->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse($jsonClient, Response::HTTP_CREATED, ["Location" => $location], true); # Response 201 - Created
        }

    /**
     * Cette méthode permet de mettre à jour un client.
     * 
     * * @OA\Response(
     *     response=204,
     *     description="Retourne une response 204 - No Content",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClientDetails"}))
     *     )
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
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour un client')]
    public function update(Request $request, SerializerInterface $serializer, UserPasswordHasherInterface $passwordHasher, Client $currentClient, EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cachePool, CustomerRepository $customerRepository): JsonResponse 
        {
            $newClient = $serializer->deserialize($request->getContent(), Client::class, 'json');
            if (null !== $newClient->getCreatedAt()) { $currentClient->setCreatedAt($newClient->getCreatedAt()); }
            if (null !== $newClient->getCompany()) { $currentClient->setCompany($newClient->getCompany()); }
            if (null !== $newClient->getEmail()) { $currentClient->setEmail($newClient->getEmail()); }
            if (null !== $newClient->getPassword()) { $currentClient->setPassword($passwordHasher->hashPassword($currentClient, $newClient->getPassword())); }
            if (null !== $newClient->getCreatedAt()) { $currentClient->setCreatedAt($newClient->getCreatedAt()); }
            if (null !== $newClient->getRoles()) { $currentClient->setRoles($newClient->getRoles()); }
            // // Récupération de l'ensemble des données envoyées sous forme de tableau
            $content = $request->toArray();
            $customers = $content['customers'];
            if (isset($customers)) { 
                foreach($customers as $customer) {
                    $newCustomer = new Customer();
                    $newCustomer->setEmail($customer['email'])
                                ->setFirstName($customer['firstName'])
                                ->setLastName($customer['lastName']);
                    $currentClient->addCustomer($newCustomer);
                }
            }
            // Récupération de l'idCustomers pour lier des customers. S'il n'est pas défini, alors on null par défaut.
            $idCustomers = $content['idCustomers'] ?? null;
            if (isset($idCustomers)) {
                foreach($idCustomers as $idCustomer) {
                    $currentClient->setCustomer($customerRepository->find($idCustomer));
                }
            }
            // Récupération de removeIdCustomers pour supprimer la liaison avec des Customers. S'il n'est pas défini, alors on null par défaut.
            $removeIdCustomers = $content['removeIdCustomers'] ?? null;
            if (isset($removeIdCustomers)) {
                foreach($removeIdCustomers as $removeIdCustomer) {
                    $currentClient->removeCustomer($customerRepository->find($removeIdCustomer));
                }
            }
            // On vérifie les erreurs
            $errors = $validator->validate($currentClient);
            if ($errors->count() > 0) {
                // throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors);
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $em->persist($currentClient);
            $em->flush();
            $cachePool->invalidateTags(["clientsCache"]);
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT); # Response 204 - No content
        }

    /**
     * Cette méthode permet de supprimer un client.
     * 
     * * @OA\Response(
     *     response=204,
     *     description="Retourne une response 204 - No Content",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClientDetails"}))
     *     )
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
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un client')]
    public function destroy(Client $client, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $cachePool->invalidateTags(["clientsCache"]);
            $em->remove($client);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT); # Response 204 - No content
        }
}
