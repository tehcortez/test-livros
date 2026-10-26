<?php

declare(strict_types=1);

namespace App\Service;

final class InvalidLivroReferences extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Um ou mais autores ou assuntos não existem.');
    }
}
