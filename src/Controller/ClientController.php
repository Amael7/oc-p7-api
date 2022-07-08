<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ClientController extends AbstractController
{
    #[Route('/api/clients', name: 'clients', methods: ['GET'])]
    public function index(ClientRepository $clientRepository, SerializerInterface $serializer): JsonResponse
    {
        $clientsList = $clientRepository->findAll();
        $jsonClientsList = $serializer->serialize($clientsList, 'json', ['groups' => 'getClients']);
        return new JsonResponse($jsonClientsList, Response::HTTP_OK, [], true);  # Response 200
    }

    #[Route('/api/clients/{id}', name: 'clientShow', methods: ['GET'])]
    public function Show(Client $client, ClientRepository $clientRepository, SerializerInterface $serializer): JsonResponse
    {
        $jsonClient = $serializer->serialize($client, 'json');
        return new JsonResponse($jsonClient, Response::HTTP_OK, ['accept' => 'json'], true);  # Response 200 if OK and 404
    }
}
