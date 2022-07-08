<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientController extends AbstractController
{
    #[Route('/api/clients', name: 'clients', methods: ['GET'])]
    public function index(ClientRepository $clientRepository, SerializerInterface $serializer): JsonResponse
        {
            $clientsList = $clientRepository->findAll();
            $jsonClientsList = $serializer->serialize($clientsList, 'json', ['groups' => ['getClientDetails', 'getCustomersFromClient', 'getCustomerDetails']]);
            return new JsonResponse($jsonClientsList, Response::HTTP_OK, [], true);  # Response 200
        }

    #[Route('/api/clients/{id}', name: 'clientShow', methods: ['GET'])]
    public function show(Client $client, SerializerInterface $serializer): JsonResponse
        {
            $jsonClient = $serializer->serialize($client, 'json', ['groups' => ['getClientDetails', 'getCustomersFromClient', 'getCustomerDetails']]);
            return new JsonResponse($jsonClient, Response::HTTP_OK, ['accept' => 'json'], true);  # Response 200 if OK and 404 if not found
        }

    #[Route('/api/clients', name:"clientCreate", methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, UserPasswordHasherInterface $passwordHasher): JsonResponse 
        {
            $client = $serializer->deserialize($request->getContent(), Client::class, 'json');

            // Récupération de l'ensemble des données envoyées sous forme de tableau
            $content = $request->toArray();

            $password = $content['password'];
            $client->setPassword($passwordHasher->hashPassword($client, $password));

            $em->persist($client);
            $em->flush();

            $jsonClient = $serializer->serialize($client, 'json', ['groups' => ['getClientDetails', 'getCustomersFromClient', 'getCustomerDetails']]);
            
            $location = $urlGenerator->generate('clientShow', ['id' => $client->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse($jsonClient, Response::HTTP_CREATED, ["Location" => $location], true); # Response 201 - Created
        }

    #[Route('/api/clients/{id}', name: 'clientUpdate', methods: ['PUT'])]
    public function update(Request $request, SerializerInterface $serializer, Client $currentclient, EntityManagerInterface $em): JsonResponse 
        {
            $updatedClient = $serializer->deserialize($request->getContent(), 
                    Client::class, 
                    'json', 
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $currentclient]);
            
            $em->persist($updatedClient);
            $em->flush();

            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT); # Response 204 - No content
        }

    #[Route('/api/clients/{id}', name: 'clientDestroy', methods: ['DELETE'])]
    public function destroy(Client $client, EntityManagerInterface $em): JsonResponse 
        {
            $em->remove($client);
            $em->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT); # Response 204 - No content
        }
}
