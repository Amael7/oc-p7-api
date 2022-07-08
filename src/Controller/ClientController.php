<?php

namespace App\Controller;

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
        $jsonClientsList = $serializer->serialize($clientsList, 'json');
        return new JsonResponse($jsonClientsList, Response::HTTP_OK, [], true);  # Response 200
    }

    #[Route('/api/clients/{id}', name: 'clientShow', methods: ['GET'])]
    public function Show(int $id, ClientRepository $clientRepository, SerializerInterface $serializer): JsonResponse
    {
        $client = $clientRepository->find($id);
        if ($client) {
            $jsonClient = $serializer->serialize($client, 'json');
            return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND); # Response 404
    }
}
