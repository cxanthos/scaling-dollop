<?php

declare(strict_types=1);

namespace App\Domains\Users\Models;

enum UserRole: string
{
    case Manager = 'manager';
    case Employee = 'employee';
}
