<?php

namespace App\Validation;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;

class LocationValidator
{
    public function validate($data): array
    {
        $validator = Validation::createValidator();

        $constraint = new Assert\Collection([
            'lat' => new Assert\NotNull(),
            'lon' => new Assert\NotNull(),
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
