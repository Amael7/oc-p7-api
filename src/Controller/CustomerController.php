<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\ClientRepository;
use App\Repository\CustomerRepository;
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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
class CustomerController extends AbstractController
{
    #[Route('/api/customers', name: 'customers', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
        {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 5);
            $idCache = "getAllCustomers-" . $page . "-" . $limit;
            $jsoncustomersList = $cachePool->get($idCache, function (ItemInterface $item) use ($customerRepository, $page, $limit, $serializer) {
                $item->tag("customersCache");
                $customersList = $customerRepository->findAllWithPagination($page, $limit);
                $context = SerializationContext::create()->setGroups(['getCustomerDetails', 'getClientsFromCustomer', 'getClientDetails']);
                return $serializer->serialize($customersList, 'json', $context);
            });
            return new JsonResponse($jsoncustomersList, Response::HTTP_OK, [], true);  # Response 200
        }

    #[Route('/api/customers/{id}', name: 'customerShow', methods: ['GET'])]
        public function show(Customer $customer, SerializerInterface $serializer): JsonResponse
            {
                $context = SerializationContext::create()->setGroups(['getCustomerDetails', 'getClientsFromCustomer', 'getClientDetails']);
                $jsonCustomer = $serializer->serialize($customer, 'json', $context);
                return new JsonResponse($jsonCustomer, Response::HTTP_OK, ['accept' => 'json'], true);  # Response 200 if OK and 404 if not found
            }

    #[Route('/api/customers', name:"customerCreate", methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ClientRepository $clientRepository, ValidatorInterface $validator): JsonResponse 
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

            // On vérifie les erreurs
            $errors = $validator->validate($customer);
            if ($errors->count() > 0) {
                // throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors);
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }

            $em->persist($customer);
            $em->flush();
            $context = SerializationContext::create()->setGroups(['getCustomerDetails', 'getClientsFromCustomer', 'getClientDetails']);
            $jsonCustomer = $serializer->serialize($customer, 'json', $context);
            $location = $urlGenerator->generate('customerShow', ['id' => $customer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            return new JsonResponse($jsonCustomer, Response::HTTP_CREATED, ["Location" => $location], true); # Response 201 - Created
        }

    #[Route('/api/customers/{id}', name: 'customerUpdate', methods: ['PUT'])]
    public function update(Request $request, SerializerInterface $serializer, Customer $currentCustomer, EntityManagerInterface $em, ClientRepository $clientRepository, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse 
        {
            $newCustomer = $serializer->deserialize($request->getContent(), Customer::class, 'json');
            if (null !== $newCustomer->getEmail()) { $currentCustomer->setEmail($newCustomer->getEmail()); }
            if (null !== $newCustomer->getLastName()) { $currentCustomer->setLastName($newCustomer->getLastName()); }
            if (null !== $newCustomer->getFirstName()) { $currentCustomer->setFirstName($newCustomer->getFirstName()); }
            // On vérifie les erreurs
            $errors = $validator->validate($currentCustomer);
            if ($errors->count() > 0) {
                // throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $errors);
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            // Récupération de l'ensemble des données envoyées sous forme de tableau
            $content = $request->toArray();
            // Récupération de l'idClients pour lier des clients. S'il n'est pas défini, alors on null par défaut.
            $idClients = $content['idClients'] ?? null;
            if (isset($idClients)) {
                foreach($idClients as $idClient) {
                    $currentCustomer->setClient($clientRepository->find($idClient));
                }
            }
            // Récupération de removeIdClients pour supprimer la liaison avec des clients. S'il n'est pas défini, alors on null par défaut.
            $removeIdClients = $content['removeIdClients'] ?? null;
            if (isset($removeIdClients)) {
                foreach($removeIdClients as $removeIdClient) {
                    $currentCustomer->removeClient($clientRepository->find($removeIdClient));
                }
            }
            $em->persist($currentCustomer);
            $em->flush();
            $cachePool->invalidateTags(["customersCache"]);
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT); # Response 204 - No content
        }

    #[Route('/api/customers/{id}', name: 'customerDestroy', methods: ['DELETE'])]
        public function destroy(Customer $customer, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse 
            {
                $cachePool->invalidateTags(["customersCache"]);
                $em->remove($customer);
                $em->flush();
                return new JsonResponse(null, Response::HTTP_NO_CONTENT); # Response 204 - No content
            }
}
