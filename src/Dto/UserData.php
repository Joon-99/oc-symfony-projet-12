<?php

namespace App\Dto;

use App\Validator\UniqueUsername;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueUsername]
class UserData
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public readonly ?string $username = null,
        #[Assert\NotBlank]
        #[Assert\PasswordStrength(minScore: Assert\PasswordStrength::STRENGTH_MEDIUM)]
        public readonly ?string $password = null,
        #[Assert\NotBlank]
        #[Assert\Length(5)]
        #[Assert\Type(type: 'digit')]
        public readonly ?string $zipCode = null,
    ) {
    }
}