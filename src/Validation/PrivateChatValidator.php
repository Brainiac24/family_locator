<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;

class PrivateChatValidator
{
    public function validate($data): array
    {
        $validator = Validation::createValidator();

        $constraint = new Assert\Collection([
            'recipient_number' => new Assert\NotNull(),
            'type' => new Assert\NotNull(),
            'content' => new Assert\NotNull(),
        ]);

        $validationErrors = $validator->validate($data, $constraint);
        $errors = [];
        if ($validationErrors->count() > 0) {
            /** @var ConstraintViolation $param */
            foreach ($validationErrors as $param) {
                $errors[$param->getPropertyPath()] = $param->getMessage();
            }
        }

        return $errors;
    }
}
