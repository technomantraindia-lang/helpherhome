<?php

namespace App\Enums;

enum CustomerGeneratedDocumentType: string
{
    case RegistrationForm = 'customer_registration_form';

    public function label(): string { return 'Customer Registration Form'; }
}
