<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
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
}
