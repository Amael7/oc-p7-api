<?php

namespace App\Entity;

use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['getProductDetails'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['getProductDetails'])]
    private $manufacturer;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le nom du produit est obligatoire.")]
    #[Assert\Length(min: 3, max:255)]
    #[Groups(['getProductDetails'])]
    private $name;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2)]
    #[Groups(['getProductDetails'])]
    private $description;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull]
    #[Groups(['getProductDetails'])]
    private $createdAt;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull]
    #[Groups(['getProductDetails'])]
    private $screenSize;

    #[ORM\Column(type: 'boolean')]
    #[Assert\NotNull]
    #[Groups(['getProductDetails'])]
    private $camera;

    #[ORM\Column(type: 'boolean')]
    #[Assert\NotNull]
    #[Groups(['getProductDetails'])]
    private $bluetooth;

    #[ORM\Column(type: 'boolean')]
    #[Assert\NotNull]
    #[Groups(['getProductDetails'])]
    private $wifi;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull]
    #[Assert\Positive]
    #[Groups(['getProductDetails'])]
    private $length;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull]
    #[Assert\Positive]
    #[Groups(['getProductDetails'])]
    private $width;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull]
    #[Assert\Positive]
    #[Groups(['getProductDetails'])]
    private $height;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull]
    #[Assert\Positive]
    #[Groups(['getProductDetails'])]
    private $weight;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull]
    #[Assert\Positive]
    #[Groups(['getProductDetails'])]
    private $das;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Configuration::class, orphanRemoval: true, cascade:['persist'])]
    #[Groups(['getConfigurationFromProduct'])]
    private $configurations;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now', new DateTimeZone('Europe/Paris'));
        $this->configurations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(string $manufacturer): self
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getScreenSize(): ?float
    {
        return $this->screenSize;
    }

    public function setScreenSize(float $screenSize): self
    {
        $this->screenSize = $screenSize;

        return $this;
    }

    public function isCamera(): ?bool
    {
        return $this->camera;
    }

    public function setCamera(bool $camera): self
    {
        $this->camera = $camera;

        return $this;
    }

    public function isBluetooth(): ?bool
    {
        return $this->bluetooth;
    }

    public function setBluetooth(bool $bluetooth): self
    {
        $this->bluetooth = $bluetooth;

        return $this;
    }

    public function isWifi(): ?bool
    {
        return $this->wifi;
    }

    public function setWifi(bool $wifi): self
    {
        $this->wifi = $wifi;

        return $this;
    }

    public function getLength(): ?float
    {
        return $this->length;
    }

    public function setLength(float $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function getWidth(): ?float
    {
        return $this->width;
    }

    public function setWidth(float $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(float $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getDas(): ?float
    {
        return $this->das;
    }

    public function setDas(float $das): self
    {
        $this->das = $das;

        return $this;
    }

    /**
     * @return Collection<int, Configuration>
     */
    public function getConfigurations(): Collection
    {
        return $this->configurations;
    }

    public function addConfiguration(Configuration $configuration): self
    {
        if (!$this->configurations->contains($configuration)) {
            $this->configurations[] = $configuration;
            $configuration->setProduct($this);
        }

        return $this;
    }

    public function removeConfiguration(Configuration $configuration): self
    {
        if ($this->configurations->removeElement($configuration)) {
            // set the owning side to null (unless already changed)
            if ($configuration->getProduct() === $this) {
                $configuration->setProduct(null);
            }
        }

        return $this;
    }
}
