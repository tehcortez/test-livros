<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\AssuntoInput;
use App\Entity\Assunto;
use App\Service\AssuntoService;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AssuntoController extends AbstractController
{
    #[Route('/api/assuntos', name: 'api_assuntos_index', methods: ['GET'], format: 'json')]
    public function index(AssuntoService $assuntoService): JsonResponse
    {
        return $this->json(array_map(
            self::formatAssunto(...),
            $assuntoService->findAll(),
        ));
    }

    #[Route('/api/assuntos/{id}', name: 'api_assuntos_show', requirements: ['id' => '\d+'], methods: ['GET'], format: 'json')]
    public function show(int $id, AssuntoService $assuntoService): JsonResponse
    {
        $assunto = $assuntoService->findById($id);

        if ($assunto === null) {
            return $this->notFound();
        }

        return $this->json(self::formatAssunto($assunto));
    }

    #[Route('/api/assuntos', name: 'api_assuntos_create', methods: ['POST'], format: 'json')]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        AssuntoService $assuntoService,
    ): JsonResponse {
        $input = $this->readInput($request, $validator);

        if ($input instanceof JsonResponse) {
            return $input;
        }

        return $this->json(
            self::formatAssunto($assuntoService->create($input->descricao)),
            Response::HTTP_CREATED,
        );
    }

    #[Route('/api/assuntos/{id}', name: 'api_assuntos_update', requirements: ['id' => '\d+'], methods: ['PUT'], format: 'json')]
    public function update(
        int $id,
        Request $request,
        ValidatorInterface $validator,
        AssuntoService $assuntoService,
    ): JsonResponse {
        $assunto = $assuntoService->findById($id);

        if ($assunto === null) {
            return $this->notFound();
        }

        $input = $this->readInput($request, $validator);

        if ($input instanceof JsonResponse) {
            return $input;
        }

        return $this->json(self::formatAssunto(
            $assuntoService->update($assunto, $input->descricao),
        ));
    }

    #[Route('/api/assuntos/{id}', name: 'api_assuntos_delete', requirements: ['id' => '\d+'], methods: ['DELETE'], format: 'json')]
    public function delete(int $id, AssuntoService $assuntoService): Response
    {
        $assunto = $assuntoService->findById($id);

        if ($assunto === null) {
            return $this->notFound();
        }

        $assuntoService->delete($assunto);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function readInput(
        Request $request,
        ValidatorInterface $validator,
    ): AssuntoInput|JsonResponse {
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

        $input = new AssuntoInput(
            is_array($dados) ? ($dados['descricao'] ?? null) : null,
        );
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
            ['erro' => 'Assunto não encontrado.'],
            Response::HTTP_NOT_FOUND,
        );
    }

    /**
     * @return array{id: int|null, descricao: string|null}
     */
    private static function formatAssunto(Assunto $assunto): array
    {
        return [
            'id' => $assunto->getCodAs(),
            'descricao' => $assunto->getDescricao(),
        ];
    }
}
