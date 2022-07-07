<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ClientController extends AbstractController
{
    #[Route('/api/clients', name: 'clients', methods: ['GET'])]
    public function index(ClientRepository $clientRepository): JsonResponse
    {

        $clientsList = $clientRepository->findAll();

        return new JsonResponse([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ClientController.php',
            'clients' => $clientsList,
        ]);
    }
}
