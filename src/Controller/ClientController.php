<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientController extends AbstractController
{
    #[Route('/api/clients', name: 'clients', methods: ['GET'])]
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

    #[Route('/api/clients/{id}', name: 'clientShow', methods: ['GET'])]
    public function show(Client $client, SerializerInterface $serializer): JsonResponse
        {
            $context = SerializationContext::create()->setGroups(['getClientDetails', 'getCustomersFromClient', 'getCustomerDetails']);
            $jsonClient = $serializer->serialize($client, 'json', $context);
            return new JsonResponse($jsonClient, Response::HTTP_OK, ['accept' => 'json'], true);  # Response 200 if OK and 404 if not found
        }

    #[Route('/api/clients', name:"clientCreate", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un client')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator): JsonResponse 
        {
            $client = $serializer->deserialize($request->getContent(), Client::class, 'json');
            // Récupération de l'ensemble des données envoyées sous forme de tableau
            $content = $request->toArray();
            $password = $content['password'];
            $client->setPassword($passwordHasher->hashPassword($client, $password));

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

    #[Route('/api/clients/{id}', name: 'clientUpdate', methods: ['PUT'])]
    public function update(Request $request, SerializerInterface $serializer, UserPasswordHasherInterface $passwordHasher, Client $currentClient, EntityManagerInterface $em, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $newClient = $serializer->deserialize($request->getContent(), Client::class, 'json');
            if (null !== $newClient->getCompany()) { $currentClient->setCompany($newClient->getCompany()); }
            if (null !== $newClient->getEmail()) { $currentClient->setEmail($newClient->getEmail()); }
            if (null !== $newClient->getPassword()) { $currentClient->setPassword($passwordHasher->hashPassword($currentClient, $newClient->getPassword())); }
            if (null !== $newClient->getCreatedAt()) { $currentClient->setCreatedAt($newClient->getCreatedAt()); }
            if (null !== $newClient->getRoles()) { $currentClient->setRoles($newClient->getRoles()); }
            
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

    #[Route('/api/clients/{id}', name: 'clientDestroy', methods: ['DELETE'])]
    public function destroy(Client $client, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $cachePool->invalidateTags(["clientsCache"]);
            $em->remove($client);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT); # Response 204 - No content
        }
}
