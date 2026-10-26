<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Autor;
use App\Repository\AutorRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AutorService
{
    public function __construct(
        private AutorRepository $autorRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<Autor>
     */
    public function findAll(): array
    {
        return $this->autorRepository->findAll();
    }

    public function findById(int $id): ?Autor
    {
        return $this->autorRepository->find($id);
    }

    public function create(string $nome): Autor
    {
        $autor = new Autor();
        $autor->setNome($nome);

        $this->entityManager->persist($autor);
        $this->entityManager->flush();

        return $autor;
    }

    public function update(Autor $autor, string $nome): Autor
    {
        $autor->setNome($nome);
        $this->entityManager->flush();

        return $autor;
    }

    public function delete(Autor $autor): void
    {
        foreach ($autor->getLivros()->toArray() as $livro) {
            $livro->removeAutor($autor);
        }

        $this->entityManager->remove($autor);
        $this->entityManager->flush();
    }
}
