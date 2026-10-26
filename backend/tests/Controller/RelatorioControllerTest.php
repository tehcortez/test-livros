<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RelatorioControllerTest extends WebTestCase
{
    public function testReportReadsTheViewAndIncludesEachBookAuthor(): void
    {
        $client = static::createClient();
        $this->clearDatabase();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $autorA = (new Autor())->setNome('Autora A');
        $autorB = (new Autor())->setNome('Autor B');
        $assuntoA = (new Assunto())->setDescricao('Assunto A');
        $assuntoB = (new Assunto())->setDescricao('Assunto B');
        $livro = (new Livro())
            ->setTitulo('Livro compartilhado')
            ->setEditora('Editora Central')
            ->setEdicao(2)
            ->setAnoPublicacao('2025')
            ->setValor('39.90')
            ->addAutor($autorA)
            ->addAutor($autorB)
            ->addAssunto($assuntoA)
            ->addAssunto($assuntoB);

        foreach ([$autorA, $autorB, $assuntoA, $assuntoB, $livro] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        $registros = static::getContainer()->get(Connection::class)->fetchAllAssociative(
            'SELECT autor_nome, livro_titulo, assuntos
             FROM vw_relatorio_livros_autor
             WHERE livro_id = ?
             ORDER BY autor_nome',
            [$livro->getCodL()],
        );

        self::assertCount(2, $registros);
        self::assertSame(['Autor B', 'Autora A'], array_column($registros, 'autor_nome'));
        self::assertSame(
            ['Assunto A, Assunto B', 'Assunto A, Assunto B'],
            array_column($registros, 'assuntos'),
        );

        $client->request('GET', '/api/relatorios/livros-por-autor');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertStringStartsWith('%PDF-', $client->getResponse()->getContent());
        self::assertStringContainsString(
            'relatorio-livros-por-autor.pdf',
            $client->getResponse()->headers->get('Content-Disposition'),
        );
    }

    private function clearDatabase(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $connection->executeStatement('DELETE FROM Livro_Autor');
        $connection->executeStatement('DELETE FROM Livro_Assunto');
        $entityManager->createQuery('DELETE FROM App\Entity\Livro')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Autor')->execute();
        $entityManager->createQuery('DELETE FROM App\Entity\Assunto')->execute();
        $entityManager->clear();
    }
}
