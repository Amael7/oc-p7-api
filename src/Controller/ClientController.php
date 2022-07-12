<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientController extends AbstractController
{
    #[Route('/api/clients', name: 'clients', methods: ['GET'])]
    public function index(ClientRepository $clientRepository, SerializerInterface $serializer, Request $request, PaginatorInterface $paginator): JsonResponse
        {
            $clientsList = $clientRepository->findAll();
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 5);
            $clientsList = $paginator->paginate(
                $clientsList, /* query NOT result */
                $page, /*page number*/
                $limit /*limit per page*/
            );
            // $clientsList = $clientRepository->findAllWithPagination($page, $limit);
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
            $jsonClient = $serializer->serialize($client, 'json', ['groups' => ['getClientDetails', 'getCustomersFromClient', 'getCustomerDetails']]);
            $location = $urlGenerator->generate('clientShow', ['id' => $client->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse($jsonClient, Response::HTTP_CREATED, ["Location" => $location], true); # Response 201 - Created
        }

    #[Route('/api/clients/{id}', name: 'clientUpdate', methods: ['PUT'])]
    public function update(Request $request, SerializerInterface $serializer, Client $currentClient, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse 
        {
            $updatedClient = $serializer->deserialize($request->getContent(), 
                    Client::class, 
                    'json', 
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $currentClient]);

            // On vérifie les erreurs
            $errors = $validator->validate($updatedClient);
            if ($errors->count() > 0) {
                // throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors);
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }

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
