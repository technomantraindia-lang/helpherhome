<?php

namespace App\Enums;

enum WorkerGeneratedDocumentType: string
{
    case Resume = 'worker_resume';
    case RegistrationForm = 'worker_registration_form';

    public function label(): string
    {
        return match ($this) {
            self::Resume => 'Worker Resume',
            self::RegistrationForm => 'Worker Registration Form',
        };
    }

    public function shortCode(): string
    {
        return match ($this) {
            self::Resume => 'RES',
            self::RegistrationForm => 'WRF',
        };
    }
}
