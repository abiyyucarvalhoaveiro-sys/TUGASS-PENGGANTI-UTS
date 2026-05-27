<?php

class Validator
{
    public static function validateDosen(array $data): array
    {
        $errors = [];

        if (empty($data['nidn'])) {
            $errors['nidn'] = 'NIDN wajib diisi';
        }

        if (empty($data['nama'])) {
            $errors['nama'] = 'Nama wajib diisi';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email tidak valid';
        }

        return $errors;
    }
}