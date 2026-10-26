<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026040400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the author-grouped book report view.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE VIEW vw_relatorio_livros_autor AS
            SELECT
                a.CodAu AS autor_id,
                COALESCE(a.Nome, 'Sem autor') AS autor_nome,
                l.Codl AS livro_id,
                l.Titulo AS livro_titulo,
                l.Editora AS editora,
                l.Edicao AS edicao,
                l.AnoPublicacao AS ano_publicacao,
                l.Valor AS valor,
                COALESCE(
                    GROUP_CONCAT(DISTINCT s.Descricao ORDER BY s.Descricao SEPARATOR ', '),
                    'Sem assunto'
                ) AS assuntos
            FROM Livro l
            LEFT JOIN Livro_Autor la ON la.Livro_Codl = l.Codl
            LEFT JOIN Autor a ON a.CodAu = la.Autor_CodAu
            LEFT JOIN Livro_Assunto ls ON ls.Livro_Codl = l.Codl
            LEFT JOIN Assunto s ON s.codAs = ls.Assunto_CodAs
            GROUP BY
                a.CodAu,
                a.Nome,
                l.Codl,
                l.Titulo,
                l.Editora,
                l.Edicao,
                l.AnoPublicacao,
                l.Valor",
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW vw_relatorio_livros_autor');
    }
}
