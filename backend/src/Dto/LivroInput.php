<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LivroInput
{
    #[Assert\NotBlank(message: 'O campo titulo é obrigatório.')]
    #[Assert\Length(max: 40, maxMessage: 'O título deve ter no máximo {{ limit }} caracteres.')]
    public string $titulo;

    #[Assert\NotBlank(message: 'O campo editora é obrigatório.')]
    #[Assert\Length(max: 40, maxMessage: 'A editora deve ter no máximo {{ limit }} caracteres.')]
    public string $editora;

    #[Assert\Positive(message: 'A edição deve ser um número inteiro positivo.')]
    public int $edicao;

    #[Assert\Regex(
        pattern: '/^\d{4}$/',
        message: 'O ano de publicação deve conter exatamente quatro dígitos.',
    )]
    public string $anoPublicacao;

    #[Assert\Regex(
        pattern: '/^\d{1,8}(?:\.\d{1,2})?$/',
        message: 'O valor deve ser positivo e ter no máximo duas casas decimais.',
    )]
    public string $valor;

    /** @var list<mixed> */
    #[Assert\All([
        new Assert\Type('integer'),
        new Assert\Positive(),
    ])]
    public array $autores;

    /** @var list<mixed> */
    #[Assert\All([
        new Assert\Type('integer'),
        new Assert\Positive(),
    ])]
    public array $assuntos;

    #[Assert\IsTrue(message: 'Autores e assuntos devem ser listas de IDs.')]
    public bool $associacoesValidas;

    /**
     * @param array<string, mixed> $dados
     */
    public function __construct(array $dados)
    {
        $this->titulo = is_string($dados['titulo'] ?? null)
            ? trim($dados['titulo'])
            : '';
        $this->editora = is_string($dados['editora'] ?? null)
            ? trim($dados['editora'])
            : '';
        $this->edicao = is_int($dados['edicao'] ?? null)
            ? $dados['edicao']
            : 0;
        $anoPublicacao = $dados['anoPublicacao'] ?? null;
        $this->anoPublicacao = is_string($anoPublicacao) || is_int($anoPublicacao)
            ? trim((string) $anoPublicacao)
            : '';
        $valor = $dados['valor'] ?? null;
        $this->valor = is_string($valor) || is_int($valor) || is_float($valor)
            ? (string) $valor
            : '';

        $autoresValidos = !isset($dados['autores']) || is_array($dados['autores']);
        $assuntosValidos = !isset($dados['assuntos']) || is_array($dados['assuntos']);
        $this->autores = $autoresValidos
            ? array_values($dados['autores'] ?? [])
            : [];
        $this->assuntos = $assuntosValidos
            ? array_values($dados['assuntos'] ?? [])
            : [];
        $this->associacoesValidas = $autoresValidos && $assuntosValidos;
    }
}
