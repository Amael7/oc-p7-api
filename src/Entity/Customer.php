<?php

namespace App\Entity;

use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\Collection;
use Hateoas\Configuration\Annotation as Hateoas;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Hateoas\Relation(
 *      "list",
 *      href = @Hateoas\Route(
 *          "customers"
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getCustomerDetails")
 * )
 * 
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "customerShow",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getCustomerDetails")
 * )
 * 
 * * * @Hateoas\Relation(
 *      "create",
 *      href = @Hateoas\Route(
 *          "customerCreate",
 *          parameters = {},
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getCustomerDetails"),
 * )
 * 
 * * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "customerDestroy",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getCustomerDetails"),
 * )
 *
 * @Hateoas\Relation(
 *      "update",
 *      href = @Hateoas\Route(
 *          "customerUpdate",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getCustomerDetails"),
 * )
 *
 */
#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['getCustomerDetails'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "The email is required.")]
    #[Assert\Length(max:255, maxMessage: "The email can't be more than {{ limit }} characteres")]
    #[Assert\Email(message: "The email has to be valid.")]
    #[Groups(['getCustomerDetails'])]
    private $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "The lastName is required.")]
    #[Assert\Length(min: 2, max:255, minMessage: "The lastName must be at least {{ limit }} characteres", maxMessage: "The lastName can't be more than {{ limit }} characteres")]
    #[Groups(['getCustomerDetails'])]
    private $lastName;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "The firstName is required.")]
    #[Assert\Length(min: 3, max:255, minMessage: "The firstName must be at least {{ limit }} characteres", maxMessage: "The firstName can't be more than {{ limit }} characteres")]
    #[Groups(['getCustomerDetails'])]
    private $firstName;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['getCustomerDetails'])]
    private $createdAt;

    #[ORM\ManyToMany(targetEntity: Client::class, inversedBy: 'customers', cascade:['persist'])]
    #[ORM\JoinColumn(onDelete:"CASCADE")]
    #[Groups(['getClientsFromCustomer'])]
    private $clients;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now', new DateTimeZone('Europe/Paris'));
        $this->clients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): self
    {
        if (!$this->clients->contains($client)) {
            $this->clients[] = $client;
        }

        return $this;
    }

    public function removeClient(Client $client): self
    {
        $this->clients->removeElement($client);

        return $this;
    }

    public function setClient(?Client $client): self
    {
        $this->clients[] = $client;

        return $this;
    }
}
