<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LivroInput;
use App\Entity\Livro;
use App\Service\InvalidLivroReferences;
use App\Service\LivroService;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class LivroController extends AbstractController
{
    #[Route('/api/livros', name: 'api_livros_index', methods: ['GET'], format: 'json')]
    public function index(LivroService $livroService): JsonResponse
    {
        return $this->json(array_map(
            self::formatLivro(...),
            $livroService->findAll(),
        ));
    }

    #[Route('/api/livros/{id}', name: 'api_livros_show', requirements: ['id' => '\d+'], methods: ['GET'], format: 'json')]
    public function show(int $id, LivroService $livroService): JsonResponse
    {
        $livro = $livroService->findById($id);

        if ($livro === null) {
            return $this->notFound();
        }

        return $this->json(self::formatLivro($livro));
    }

    #[Route('/api/livros', name: 'api_livros_create', methods: ['POST'], format: 'json')]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        LivroService $livroService,
    ): JsonResponse {
        $input = $this->readInput($request, $validator);

        if ($input instanceof JsonResponse) {
            return $input;
        }

        try {
            $livro = $livroService->create($input);
        } catch (InvalidLivroReferences $exception) {
            return $this->invalidReferences($exception);
        }

        return $this->json(
            self::formatLivro($livro),
            Response::HTTP_CREATED,
        );
    }

    #[Route('/api/livros/{id}', name: 'api_livros_update', requirements: ['id' => '\d+'], methods: ['PUT'], format: 'json')]
    public function update(
        int $id,
        Request $request,
        ValidatorInterface $validator,
        LivroService $livroService,
    ): JsonResponse {
        $livro = $livroService->findById($id);

        if ($livro === null) {
            return $this->notFound();
        }

        $input = $this->readInput($request, $validator);

        if ($input instanceof JsonResponse) {
            return $input;
        }

        try {
            $livro = $livroService->update($livro, $input);
        } catch (InvalidLivroReferences $exception) {
            return $this->invalidReferences($exception);
        }

        return $this->json(self::formatLivro($livro));
    }

    #[Route('/api/livros/{id}', name: 'api_livros_delete', requirements: ['id' => '\d+'], methods: ['DELETE'], format: 'json')]
    public function delete(int $id, LivroService $livroService): Response
    {
        $livro = $livroService->findById($id);

        if ($livro === null) {
            return $this->notFound();
        }

        $livroService->delete($livro);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function readInput(
        Request $request,
        ValidatorInterface $validator,
    ): LivroInput|JsonResponse {
        try {
            $dados = json_decode(
                $request->getContent(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            return $this->json(['erro' => 'JSON inválido.'], Response::HTTP_BAD_REQUEST);
        }

        $input = new LivroInput(is_array($dados) ? $dados : []);
        $violations = $validator->validate($input);

        if (count($violations) > 0) {
            return $this->json([
                'erro' => 'Dados inválidos.',
                'detalhes' => array_map(
                    static fn ($violation): string => $violation->getMessage(),
                    iterator_to_array($violations),
                ),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $input;
    }

    private function notFound(): JsonResponse
    {
        return $this->json(
            ['erro' => 'Livro não encontrado.'],
            Response::HTTP_NOT_FOUND,
        );
    }

    private function invalidReferences(InvalidLivroReferences $exception): JsonResponse
    {
        return $this->json(
            ['erro' => $exception->getMessage()],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    /**
     * @return array{
     *     id: int|null,
     *     titulo: string|null,
     *     editora: string|null,
     *     edicao: int|null,
     *     anoPublicacao: string|null,
     *     valor: string|null,
     *     autores: list<int|null>,
     *     assuntos: list<int|null>
     * }
     */
    private static function formatLivro(Livro $livro): array
    {
        return [
            'id' => $livro->getCodL(),
            'titulo' => $livro->getTitulo(),
            'editora' => $livro->getEditora(),
            'edicao' => $livro->getEdicao(),
            'anoPublicacao' => $livro->getAnoPublicacao(),
            'valor' => $livro->getValor(),
            'autores' => array_values(array_map(
                static fn ($autor): ?int => $autor->getCodAu(),
                $livro->getAutores()->toArray(),
            )),
            'assuntos' => array_values(array_map(
                static fn ($assunto): ?int => $assunto->getCodAs(),
                $livro->getAssuntos()->toArray(),
            )),
        ];
    }
}
