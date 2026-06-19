<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Enum\UserRole;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User extends BaseEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $username;

    #[ORM\Column(type: 'string', length: 50)]
    private string $role;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\ManyToOne(targetEntity: City::class)]
    #[ORM\JoinColumn(nullable: false)]
    private City $city;


    public function __construct()
    {
        parent::__construct();
        $this->role = UserRole::USER->value;
    }


    public function getUsername(): string
    {
        return $this->username;
    }
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }
    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }
    public function getRoleEnum(): UserRole
    {
        try {
            return UserRole::from($this->role);
        } catch (\ValueError $e) {
            return UserRole::USER;
        }
    }
    public function setRoleEnum(UserRole $role): self
    {
        $this->role = $role->value;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
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

    
    /**
     * Required by UserInterface. Returns an array of roles granted to the user.
     * The roles should be strings like 'ROLE_USER' or 'ROLE_ADMIN'.
     */
    public function getRoles(): array
    {
        return [$this->role];
    }
    /**
     * Required by UserInterface. Returns a unique identifier for the user.
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}
