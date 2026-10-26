<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Assunto;
use App\Entity\Autor;
use App\Entity\Livro;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AssuntoControllerTest extends WebTestCase
{
    public function testSubjectCrud(): void
    {
        $client = static::createClient();
        $this->clearDatabase();

        $client->request(
            'POST',
            '/api/assuntos',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['descricao' => 'Literatura'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $assunto = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame('Literatura', $assunto['descricao']);
        $id = $assunto['id'];

        $client->request('GET', '/api/assuntos');
        self::assertResponseIsSuccessful();
        self::assertCount(1, json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        ));

        $client->request('GET', '/api/assuntos/'.$id);
        self::assertResponseStatusCodeSame(200);

        $client->request(
            'PUT',
            '/api/assuntos/'.$id,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['descricao' => 'Romance'], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(200);
        self::assertSame(
            'Romance',
            json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['descricao'],
        );

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $autor = new Autor();
        $autor->setNome('Autor relacionado');
        $livro = new Livro();
        $livro
            ->setTitulo('Livro relacionado')
            ->setEditora('Editora')
            ->setEdicao(1)
            ->setAnoPublicacao('2024')
            ->setValor('10.00')
            ->addAutor($autor)
            ->addAssunto($entityManager->getRepository(Assunto::class)->find($id));
        $entityManager->persist($autor);
        $entityManager->persist($livro);
        $entityManager->flush();
        $livroId = $livro->getCodL();

        $client->request('DELETE', '/api/assuntos/'.$id);
        self::assertResponseStatusCodeSame(204);
        self::assertSame('', $client->getResponse()->getContent());
        $entityManager->clear();
        self::assertNull($entityManager->getRepository(Assunto::class)->find($id));
        self::assertSame(
            [],
            $entityManager->getRepository(Livro::class)
                ->find($livroId)->getAssuntos()->toArray(),
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
