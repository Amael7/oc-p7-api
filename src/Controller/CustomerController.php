<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\ClientRepository;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CustomerController extends AbstractController
{
    #[Route('/api/customers', name: 'customers', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository, SerializerInterface $serializer): JsonResponse
        {
            $customersList = $customerRepository->findAll();
            $jsoncustomersList = $serializer->serialize($customersList, 'json', ['groups' => ['getCustomerDetails', 'getClientsFromCustomer', 'getClientDetails']]);
            return new JsonResponse($jsoncustomersList, Response::HTTP_OK, [], true);  # Response 200
        }

    #[Route('/api/customers/{id}', name: 'customerShow', methods: ['GET'])]
        public function show(Customer $customer, SerializerInterface $serializer): JsonResponse
            {
                $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' => ['getCustomerDetails', 'getClientsFromCustomer', 'getClientDetails']]);
                return new JsonResponse($jsonCustomer, Response::HTTP_OK, ['accept' => 'json'], true);  # Response 200 if OK and 404 if not found
            }

    #[Route('/api/customers', name:"customerCreate", methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ClientRepository $clientRepository): JsonResponse 
        {
            $customer = $serializer->deserialize($request->getContent(), Customer::class, 'json');
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
            $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' => ['getCustomerDetails', 'getClientsFromCustomer', 'getClientDetails']]);
            $location = $urlGenerator->generate('customerShow', ['id' => $customer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse($jsonCustomer, Response::HTTP_CREATED, ["Location" => $location], true); # Response 201 - Created
        }

    #[Route('/api/customers/{id}', name: 'customerUpdate', methods: ['PUT'])]
    public function update(Request $request, SerializerInterface $serializer, Customer $currentCustomer, EntityManagerInterface $em, ClientRepository $clientRepository): JsonResponse 
        {
            $updatedCustomer = $serializer->deserialize($request->getContent(), 
                    Customer::class, 
                    'json', 
                    [AbstractNormalizer::OBJECT_TO_POPULATE => $currentCustomer]);
            // Récupération de l'ensemble des données envoyées sous forme de tableau
            $content = $request->toArray();
            // Récupération de l'idClients pour lier des clients. S'il n'est pas défini, alors on null par défaut.
            $idClients = $content['idClients'] ?? null;
            if (isset($idClients)) {
                foreach($idClients as $idClient) {
                    $updatedCustomer->setClient($clientRepository->find($idClient));
                }
            }
            // Récupération de removeIdClients pour supprimer la liaison avec des clients. S'il n'est pas défini, alors on null par défaut.
            $removeIdClients = $content['removeIdClients'] ?? null;
            if (isset($removeIdClients)) {
                foreach($removeIdClients as $removeIdClient) {
                    $updatedCustomer->removeClient($clientRepository->find($removeIdClient));
                }
            }
            $em->persist($updatedCustomer);
            $em->flush();
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT); # Response 204 - No content
        }
}
