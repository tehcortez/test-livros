<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\AutorInput;
use App\Entity\Autor;
use App\Service\AutorService;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AutorController extends AbstractController
{
    #[Route('/api/autores', name: 'api_autores_index', methods: ['GET'], format: 'json')]
    public function index(AutorService $autorService): JsonResponse
    {
        return $this->json(array_map(
            self::formatAutor(...),
            $autorService->findAll(),
        ));
    }

    #[Route('/api/autores/{id}', name: 'api_autores_show', requirements: ['id' => '\d+'], methods: ['GET'], format: 'json')]
    public function show(int $id, AutorService $autorService): JsonResponse
    {
        $autor = $autorService->findById($id);

        if ($autor === null) {
            return $this->json(
                ['erro' => 'Autor não encontrado.'],
                Response::HTTP_NOT_FOUND,
            );
        }

        return $this->json(self::formatAutor($autor));
    }

    #[Route('/api/autores', name: 'api_autores_create', methods: ['POST'], format: 'json')]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        AutorService $autorService,
    ): JsonResponse {
        $input = $this->readInput($request, $validator);

        if ($input instanceof JsonResponse) {
            return $input;
        }

        $autor = $autorService->create($input->nome);

        return $this->json(
            self::formatAutor($autor),
            Response::HTTP_CREATED,
        );
    }

    #[Route('/api/autores/{id}', name: 'api_autores_update', requirements: ['id' => '\d+'], methods: ['PUT'], format: 'json')]
    public function update(
        int $id,
        Request $request,
        ValidatorInterface $validator,
        AutorService $autorService,
    ): JsonResponse {
        $autor = $autorService->findById($id);

        if ($autor === null) {
            return $this->json(
                ['erro' => 'Autor não encontrado.'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $input = $this->readInput($request, $validator);

        if ($input instanceof JsonResponse) {
            return $input;
        }

        return $this->json(self::formatAutor(
            $autorService->update($autor, $input->nome),
        ));
    }

    #[Route('/api/autores/{id}', name: 'api_autores_delete', requirements: ['id' => '\d+'], methods: ['DELETE'], format: 'json')]
    public function delete(
        int $id,
        AutorService $autorService,
    ): Response {
        $autor = $autorService->findById($id);

        if ($autor === null) {
            return $this->json(
                ['erro' => 'Autor não encontrado.'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $autorService->delete($autor);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function readInput(
        Request $request,
        ValidatorInterface $validator,
    ): AutorInput|JsonResponse {
        try {
            $dados = json_decode(
                $request->getContent(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            return $this->json(
                ['erro' => 'JSON inválido.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $input = new AutorInput(
            is_array($dados) ? ($dados['nome'] ?? null) : null,
        );
        $violations = $validator->validate($input);

        if (count($violations) > 0) {
            return $this->json(
                [
                    'erro' => 'Dados inválidos.',
                    'detalhes' => array_map(
                        static fn ($violation): string => $violation->getMessage(),
                        iterator_to_array($violations),
                    ),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $input;
    }

    /**
     * @return array{id: int|null, nome: string|null}
     */
    private static function formatAutor(Autor $autor): array
    {
        return [
            'id' => $autor->getCodAu(),
            'nome' => $autor->getNome(),
        ];
    }
}
