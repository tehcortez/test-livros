<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AssuntoInput
{
    #[Assert\NotBlank(message: 'O campo descricao é obrigatório.')]
    #[Assert\Length(
        max: 20,
        maxMessage: 'A descrição deve ter no máximo {{ limit }} caracteres.',
    )]
    public string $descricao;

    public function __construct(mixed $descricao)
    {
        $this->descricao = is_string($descricao) ? trim($descricao) : '';
    }
}
