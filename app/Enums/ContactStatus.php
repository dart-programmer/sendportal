<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactStatus: string
{
    case InvalidContact = 'invalid_contact';
    case UnsubscribedAll = 'unsubscribed_all';
    case UnsubscribedTopic = 'unsubscribed_topic';
}

