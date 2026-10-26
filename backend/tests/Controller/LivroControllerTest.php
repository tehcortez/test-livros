<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LivroControllerTest extends WebTestCase
{
    public function testBookCrudManagesAuthorAndSubjectRelations(): void
    {
        $client = static::createClient();
        $this->clearDatabase();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $autor = new Autor();
        $autor->setNome('Machado de Assis');
        $assunto = new Assunto();
        $assunto->setDescricao('Literatura');
        $entityManager->persist($autor);
        $entityManager->persist($assunto);
        $entityManager->flush();

        $client->request(
            'POST',
            '/api/livros',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'titulo' => 'Dom Casmurro',
                'editora' => 'Editora A',
                'edicao' => 1,
                'anoPublicacao' => '1899',
                'valor' => '49.90',
                'autores' => [$autor->getCodAu()],
                'assuntos' => [$assunto->getCodAs()],
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $livro = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame('Dom Casmurro', $livro['titulo']);
        self::assertSame([$autor->getCodAu()], $livro['autores']);
        self::assertSame([$assunto->getCodAs()], $livro['assuntos']);
        $id = $livro['id'];

        $client->request('GET', '/api/livros');
        self::assertResponseIsSuccessful();
        self::assertCount(1, json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        ));

        $client->request('GET', '/api/livros/'.$id);
        self::assertResponseStatusCodeSame(200);

        $client->request(
            'PUT',
            '/api/livros/'.$id,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'titulo' => 'Dom Casmurro - edição revista',
                'editora' => 'Editora B',
                'edicao' => 2,
                'anoPublicacao' => '1900',
                'valor' => '60',
                'autores' => [],
                'assuntos' => [],
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(200);
        $livroAtualizado = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame('Dom Casmurro - edição revista', $livroAtualizado['titulo']);
        self::assertSame([], $livroAtualizado['autores']);
        self::assertSame([], $livroAtualizado['assuntos']);

        $client->request(
            'PUT',
            '/api/livros/'.$id,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'titulo' => 'Dom Casmurro - edição revista',
                'editora' => 'Editora B',
                'edicao' => 2,
                'anoPublicacao' => '1900',
                'valor' => '60',
                'autores' => [$autor->getCodAu()],
                'assuntos' => [$assunto->getCodAs()],
            ], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(200);

        $client->request('DELETE', '/api/livros/'.$id);
        self::assertResponseStatusCodeSame(204);
        self::assertSame('', $client->getResponse()->getContent());
        $entityManager->clear();
        self::assertNull($entityManager->getRepository(Livro::class)->find($id));
        self::assertCount(0, $entityManager->getRepository(Autor::class)
            ->find($autor->getCodAu())->getLivros());
        self::assertCount(0, $entityManager->getRepository(Assunto::class)
            ->find($assunto->getCodAs())->getLivros());
    }

    public function testBookRejectsInvalidReferencesAndInvalidData(): void
    {
        $client = static::createClient();
        $this->clearDatabase();
        $body = [
            'titulo' => 'Livro',
            'editora' => 'Editora',
            'edicao' => 1,
            'anoPublicacao' => '2024',
            'valor' => '12.50',
            'autores' => [999999],
            'assuntos' => [],
        ];

        $client->request(
            'POST',
            '/api/livros',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(422);

        $body['autores'] = [];
        $body['edicao'] = 0;
        $client->request(
            'POST',
            '/api/livros',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(422);
    }

    public function testBookAcceptsNumericPublicationYearAndPersistsIt(): void
    {
        $client = static::createClient();
        $this->clearDatabase();

        $client->request(
            'POST',
            '/api/livros',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"titulo":"Dom Casmurro","editora":"Editora A","edicao":1,"anoPublicacao":1899,"valor":"49.90"}',
        );

        self::assertResponseStatusCodeSame(201);
        $dados = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame('1899', $dados['anoPublicacao']);

        $livro = static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(Livro::class)->find($dados['id']);
        self::assertNotNull($livro);
        self::assertSame('1899', $livro->getAnoPublicacao());
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
