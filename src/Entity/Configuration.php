<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ConfigurationRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: ConfigurationRepository::class)]
class Configuration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['getConfigurationDetails'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "La couleur est obligatoire.")]
    #[Assert\Length(min: 3, max:255, minMessage: "La couleur doit faire au moins {{ limit }} caractères", maxMessage: "La couleur ne peut pas faire plus de {{ limit }} caractères")]
    #[Groups(['getConfigurationDetails'])]
    private $color;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "La capacité de mémoire est obligatoire.")]
    #[Assert\Length(min: 1, max:255, minMessage: "La capacité de mémoire doit faire au moins {{ limit }} caractères", maxMessage: "La capacité de mémoire ne peut pas faire plus de {{ limit }} caractères")]
    #[Groups(['getConfigurationDetails'])]
    private $capacity;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: "Le prix est obligatoire.")]
    #[Assert\Positive(message: "Le prix doit obligatoire être positif.")]
    #[Groups(['getConfigurationDetails'])]
    private $price;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'configurations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['getProductsFromConfiguration'])]
    private $product;

    #[ORM\OneToMany(mappedBy: 'configuration', targetEntity: Image::class, orphanRemoval: true, cascade:['persist'])]
    #[Groups(['getImagesFromConfiguration'])]
    private $images;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getCapacity(): ?string
    {
        return $this->capacity;
    }

    public function setCapacity(string $capacity): self
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setConfiguration($this);
        }

        return $this;
    }

    public function removeImage(Image $image): self
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getConfiguration() === $this) {
                $image->setConfiguration(null);
            }
        }

        return $this;
    }
}
