<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;

class FriendValidator
{
    public function validate($data): array
    {
        $validator = Validation::createValidator();

        $constraint = new Assert\Collection([
            'friend_phone' => new Assert\NotNull(),
            'is_approved' => new Assert\NotNull(),
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

    public function validateAddFriend($data): array
    {
        $validator = Validation::createValidator();

        $constraint = new Assert\Collection([
            'friend_phone' => new Assert\NotNull(),
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
