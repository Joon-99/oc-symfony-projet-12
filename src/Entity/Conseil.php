<?php

namespace App\Entity;

use App\Repository\ConseilRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConseilRepository::class)]
#[ORM\Table(name: 'conseils')]
class Conseil extends BaseEntity
{
    #[ORM\Column(type: 'text', nullable: false)]
    private string $content;

    /**
     * Array of month numbers (1..12) when this conseil applies.
     */
    #[ORM\Column(type: 'json', nullable: false)]
    private array $months = [];


    public function __construct()
    {
        parent::__construct();
    }


    public function getContent(): string
    {
        return $this->content;
    }
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getMonths(): array
    {
        return $this->months;
    }
    /**
     * @param int[] $months
     */
    public function setMonths(array $months): self
    {
        $this->months = array_values(array_unique($months));
        return $this;
    }
}
