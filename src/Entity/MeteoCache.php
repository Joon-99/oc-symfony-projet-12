<?php

namespace App\Entity;

use App\Repository\MeteoCacheRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MeteoCacheRepository::class)]
#[ORM\Table(name: 'meteo_caches')]
class MeteoCache extends BaseEntity
{
    #[ORM\ManyToOne(targetEntity: City::class)]
    #[ORM\JoinColumn(nullable: false)]
    private City $city;

    /**
     * Raw data coming from the weather API, stored as JSON.
     */
    #[ORM\Column(type: 'json')]
    private array $data = [];

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $fetchedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;


    public function __construct()
    {
        parent::__construct();
        $this->fetchedAt = new \DateTimeImmutable();
    }
    

    public function getCity(): City
    {
        return $this->city;
    }
    public function setCity(City $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getFetchedAt(): \DateTimeInterface
    {
        return $this->fetchedAt;
    }
    public function setFetchedAt(\DateTimeInterface $fetchedAt): self
    {
        $this->fetchedAt = $fetchedAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }
    public function setExpiresAt(?\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }
}
