<?php

declare(strict_types=1);

namespace App\Repositories;

use MongoDB\Collection;
use MongoDB\Model\BSONDocument;

final class MongoContactRepository
{
    public function __construct(private readonly ?Collection $contacts = null)
    {
    }

    public function isEnabled(): bool
    {
        return $this->contacts instanceof Collection;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $document = $this->contacts->findOne(['email' => strtolower($email)]);

        if ($document instanceof BSONDocument) {
            return $document->getArrayCopy();
        }

        if (is_array($document)) {
            return $document;
        }

        return null;
    }
}
