<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactStatusSource: string
{
    case Mailjet = 'mailjet';

    case D7Network = 'd7_network';
}
