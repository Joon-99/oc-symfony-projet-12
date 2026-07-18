<?php

namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use App\Dto\UserData;

class UniqueUsernameValidator extends ConstraintValidator
{
    public function __construct(
        private UserRepository $userRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueUsername) {
            throw new UnexpectedTypeException($constraint, UniqueUsername::class);
        }

        if (!$value instanceof UserData) {
            throw new UnexpectedValueException($value, UserData::class);
        }

        if (null === $value->username || '' === $value->username) {
            return;
        }

        // for PUT route because we want to be able to update a user even without a change of username
        $id = $this->requestStack->getCurrentRequest()?->attributes->get('id');

        $existingUsers = $this->userRepository->findByUsernameField([
            'username' => $value->username,
            'id' => $id,
        ]);

        if (!empty($existingUsers)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('username')
                ->addViolation();
        }
    }
}
