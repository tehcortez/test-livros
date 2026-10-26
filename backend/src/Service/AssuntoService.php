<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Assunto;
use App\Repository\AssuntoRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AssuntoService
{
    public function __construct(
        private AssuntoRepository $assuntoRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<Assunto>
     */
    public function findAll(): array
    {
        return $this->assuntoRepository->findAll();
    }

    public function findById(int $id): ?Assunto
    {
        return $this->assuntoRepository->find($id);
    }

    public function create(string $descricao): Assunto
    {
        $assunto = new Assunto();
        $assunto->setDescricao($descricao);
        $this->entityManager->persist($assunto);
        $this->entityManager->flush();

        return $assunto;
    }

    public function update(Assunto $assunto, string $descricao): Assunto
    {
        $assunto->setDescricao($descricao);
        $this->entityManager->flush();

        return $assunto;
    }

    public function delete(Assunto $assunto): void
    {
        foreach ($assunto->getLivros()->toArray() as $livro) {
            $livro->removeAssunto($assunto);
        }

        $this->entityManager->remove($assunto);
        $this->entityManager->flush();
    }
}
