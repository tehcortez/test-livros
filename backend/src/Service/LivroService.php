<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\LivroInput;
use App\Entity\Livro;
use App\Repository\AssuntoRepository;
use App\Repository\AutorRepository;
use App\Repository\LivroRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class LivroService
{
    public function __construct(
        private LivroRepository $livroRepository,
        private AutorRepository $autorRepository,
        private AssuntoRepository $assuntoRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<Livro>
     */
    public function findAll(): array
    {
        return $this->livroRepository->findAll();
    }

    public function findById(int $id): ?Livro
    {
        return $this->livroRepository->find($id);
    }

    public function create(LivroInput $input): Livro
    {
        $livro = new Livro();

        return $this->save($livro, $input, true);
    }

    public function update(Livro $livro, LivroInput $input): Livro
    {
        return $this->save($livro, $input, false);
    }

    public function delete(Livro $livro): void
    {
        foreach ($livro->getAutores()->toArray() as $autor) {
            $livro->removeAutor($autor);
        }
        foreach ($livro->getAssuntos()->toArray() as $assunto) {
            $livro->removeAssunto($assunto);
        }

        $this->entityManager->remove($livro);
        $this->entityManager->flush();
    }

    private function save(Livro $livro, LivroInput $input, bool $new): Livro
    {
        $autores = $this->autorRepository->findBy(['codAu' => $input->autores]);
        $assuntos = $this->assuntoRepository->findBy(['codAs' => $input->assuntos]);

        if (count($autores) !== count(array_unique($input->autores))
            || count($assuntos) !== count(array_unique($input->assuntos))) {
            throw new InvalidLivroReferences();
        }

        $livro
            ->setTitulo($input->titulo)
            ->setEditora($input->editora)
            ->setEdicao($input->edicao)
            ->setAnoPublicacao($input->anoPublicacao)
            ->setValor(self::normalizeValor($input->valor));

        foreach ($livro->getAutores()->toArray() as $autor) {
            $livro->removeAutor($autor);
        }
        foreach ($livro->getAssuntos()->toArray() as $assunto) {
            $livro->removeAssunto($assunto);
        }
        foreach ($autores as $autor) {
            $livro->addAutor($autor);
        }
        foreach ($assuntos as $assunto) {
            $livro->addAssunto($assunto);
        }

        if ($new) {
            $this->entityManager->persist($livro);
        }
        $this->entityManager->flush();

        return $livro;
    }

    private static function normalizeValor(string $valor): string
    {
        [$inteiro, $decimal] = array_pad(explode('.', $valor, 2), 2, '');
        $inteiro = ltrim($inteiro, '0');

        return ($inteiro === '' ? '0' : $inteiro)
            .'.'.str_pad($decimal, 2, '0');
    }
}
