<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ConseilData
{
    public const GROUP_CREATE = 'conseil:create';
    public const GROUP_UPDATE = 'conseil:update';

    public function __construct(
        #[Assert\NotBlank(groups: [self::GROUP_CREATE])]
        #[Assert\Length(min: 1, max: 5000, groups: [self::GROUP_CREATE, self::GROUP_UPDATE])]
        public readonly ?string $content = null,

        #[Assert\NotBlank(groups: [self::GROUP_CREATE])]
        public readonly ?array $months = null,
    ) {
    }

    #[Assert\Callback(groups: [self::GROUP_CREATE, self::GROUP_UPDATE])]
    public function validateMonths(ExecutionContextInterface $context): void
    {
        if ($this->months === null) {
            return;
        }

        if ($this->months === []) {
            $context->buildViolation('months cannot be empty')
                ->atPath('months')
                ->addViolation();
            return;
        }

        foreach ($this->months as $index => $month) {
            if (!is_int($month) || $month < 1 || $month > 12) {
                $context->buildViolation('months must be an array of integers between 1 and 12')
                    ->atPath(sprintf('months[%d]', $index))
                    ->addViolation();
            }
        }
    }

    #[Assert\Callback(groups: [self::GROUP_UPDATE])]
    public function validateUpdatePayload(ExecutionContextInterface $context): void
    {
        if ($this->months === null && $this->content === null) {
            $context->buildViolation('Either months or content must be provided')
                ->addViolation();
        }
    }
}