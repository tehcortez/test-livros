<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AutorInput
{
    #[Assert\NotBlank(message: 'O campo nome é obrigatório.')]
    #[Assert\Length(
        max: 40,
        maxMessage: 'O nome deve ter no máximo {{ limit }} caracteres.',
    )]
    public string $nome;

    public function __construct(mixed $nome)
    {
        $this->nome = is_string($nome) ? trim($nome) : '';
    }
}
