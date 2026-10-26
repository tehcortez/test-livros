<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Autor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AutorControllerTest extends WebTestCase
{
    public function testListReturnsSuccessfulResponseWithJson(): void
    {
        $client = static::createClient();

        $this->clearDatabase();

        $client->request('GET', '/api/autores');

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');
        self::assertJson($client->getResponse()->getContent());
        self::assertSame([], json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        ));
    }

    public function testApiPreflightAllowsFrontendRequests(): void
    {
        $client = static::createClient();

        $client->request(
            'OPTIONS',
            '/api/livros',
            server: [
                'HTTP_ORIGIN' => 'http://localhost:8080',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
            ],
        );

        self::assertResponseStatusCodeSame(204);
        self::assertResponseHeaderSame('Access-Control-Allow-Origin', '*');
        self::assertResponseHeaderSame(
            'Access-Control-Allow-Methods',
            'GET, POST, PUT, DELETE, OPTIONS',
        );
    }

    public function testListReturnsExistingAuthors(): void
    {
        $client = static::createClient();

        $this->clearDatabase();

        $entityManager = static::getContainer()->get(
            EntityManagerInterface::class,
        );

        $autor = new Autor();
        $autor->setNome('Machado de Assis');

        $entityManager->persist($autor);
        $entityManager->flush();

        $client->request('GET', '/api/autores');

        self::assertResponseIsSuccessful();

        $dados = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertCount(1, $dados);
        self::assertSame('Machado de Assis', $dados[0]['nome']);
        self::assertSame($autor->getCodAu(), $dados[0]['id']);
    }

    public function testShowReturnsAuthorById(): void
    {
        $client = static::createClient();

        $this->clearDatabase();

        $entityManager = static::getContainer()->get(
            EntityManagerInterface::class,
        );

        $autor = new Autor();
        $autor->setNome('Machado de Assis');

        $entityManager->persist($autor);
        $entityManager->flush();

        $client->request('GET', '/api/autores/'.$autor->getCodAu());

        self::assertResponseStatusCodeSame(200);
        self::assertResponseFormatSame('json');

        $dados = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertSame($autor->getCodAu(), $dados['id']);
        self::assertSame('Machado de Assis', $dados['nome']);
    }

    public function testShowReturnsNotFoundForUnknownAuthor(): void
    {
        $client = static::createClient();

        $this->clearDatabase();

        $client->request('GET', '/api/autores/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateAuthorReturnsCreatedResponse(): void
    {
        $client = static::createClient();

        $this->clearDatabase();

        $client->request(
            'POST',
            '/api/autores',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(
                ['nome' => 'Clarice Lispector'],
                JSON_THROW_ON_ERROR,
            ),
        );

        self::assertResponseStatusCodeSame(201);
        self::assertResponseFormatSame('json');

        $dados = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertArrayHasKey('id', $dados);
        self::assertIsInt($dados['id']);
        self::assertSame('Clarice Lispector', $dados['nome']);
    }

    public function testCreateAuthorReturnsBadRequestForInvalidJson(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/autores',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"nome":',
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testCreateAuthorReturnsUnprocessableContentWhenNameIsMissing(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/autores',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testUpdateAuthorReturnsUpdatedAuthor(): void
    {
        $client = static::createClient();

        $this->clearDatabase();

        $entityManager = static::getContainer()->get(
            EntityManagerInterface::class,
        );

        $autor = new Autor();
        $autor->setNome('Nome antigo');

        $entityManager->persist($autor);
        $entityManager->flush();
        $id = $autor->getCodAu();

        $client->request(
            'PUT',
            '/api/autores/'.$id,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(
                ['nome' => 'Nome atualizado'],
                JSON_THROW_ON_ERROR,
            ),
        );

        self::assertResponseStatusCodeSame(200);
        self::assertResponseFormatSame('json');

        $dados = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertSame($id, $dados['id']);
        self::assertSame('Nome atualizado', $dados['nome']);
        self::assertSame('Nome atualizado', $autor->getNome());
    }

    public function testUpdateAuthorReturnsUnprocessableContentWhenNameIsMissing(): void
    {
        $client = static::createClient();

        $this->clearDatabase();

        $entityManager = static::getContainer()->get(
            EntityManagerInterface::class,
        );

        $autor = new Autor();
        $autor->setNome('Nome original');

        $entityManager->persist($autor);
        $entityManager->flush();

        $client->request(
            'PUT',
            '/api/autores/'.$autor->getCodAu(),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame('Nome original', $autor->getNome());
    }

    public function testDeleteAuthorReturnsNoContentAndRemovesAuthor(): void
    {
        $client = static::createClient();

        $this->clearDatabase();

        $entityManager = static::getContainer()->get(
            EntityManagerInterface::class,
        );

        $autor = new Autor();
        $autor->setNome('Autor para excluir');

        $entityManager->persist($autor);
        $entityManager->flush();
        $id = $autor->getCodAu();

        $client->request('DELETE', '/api/autores/'.$id);

        self::assertResponseStatusCodeSame(204);
        self::assertSame('', $client->getResponse()->getContent());
        self::assertNull($entityManager->getRepository(Autor::class)->find($id));
    }

    public function testDeleteAuthorKeepsBookAndRemovesAssociation(): void
    {
        $client = static::createClient();
        $this->clearDatabase();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $autor = new Autor();
        $autor->setNome('Autor relacionado');
        $livro = new \App\Entity\Livro();
        $livro
            ->setTitulo('Livro relacionado')
            ->setEditora('Editora')
            ->setEdicao(1)
            ->setAnoPublicacao('2024')
            ->setValor('10.00')
            ->addAutor($autor);
        $entityManager->persist($autor);
        $entityManager->persist($livro);
        $entityManager->flush();
        $autorId = $autor->getCodAu();
        $livroId = $livro->getCodL();

        $client->request('DELETE', '/api/autores/'.$autorId);

        self::assertResponseStatusCodeSame(204);
        $entityManager->clear();
        self::assertSame(
            [],
            $entityManager->getRepository(\App\Entity\Livro::class)
                ->find($livroId)->getAutores()->toArray(),
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
