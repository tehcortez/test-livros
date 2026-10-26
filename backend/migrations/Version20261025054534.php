<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261025054534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Assunto (codAs INT AUTO_INCREMENT NOT NULL, Descricao VARCHAR(20) NOT NULL, PRIMARY KEY (codAs)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Autor (CodAu INT AUTO_INCREMENT NOT NULL, Nome VARCHAR(40) NOT NULL, PRIMARY KEY (CodAu)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Livro (Codl INT AUTO_INCREMENT NOT NULL, Titulo VARCHAR(40) NOT NULL, Editora VARCHAR(40) NOT NULL, Edicao INT NOT NULL, AnoPublicacao VARCHAR(4) NOT NULL, Valor NUMERIC(10, 2) NOT NULL, PRIMARY KEY (Codl)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Livro_Autor (Livro_Codl INT NOT NULL, Autor_CodAu INT NOT NULL, INDEX IDX_412939414A5AFC39 (Livro_Codl), INDEX IDX_41293941B44F3F36 (Autor_CodAu), PRIMARY KEY (Livro_Codl, Autor_CodAu)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Livro_Assunto (Livro_Codl INT NOT NULL, Assunto_CodAs INT NOT NULL, INDEX IDX_2F01B7434A5AFC39 (Livro_Codl), INDEX IDX_2F01B74364209C06 (Assunto_CodAs), PRIMARY KEY (Livro_Codl, Assunto_CodAs)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE Livro_Autor ADD CONSTRAINT Livro_Autor_FKIndex1 FOREIGN KEY (Livro_Codl) REFERENCES Livro (Codl)');
        $this->addSql('ALTER TABLE Livro_Autor ADD CONSTRAINT Livro_Autor_FKIndex2 FOREIGN KEY (Autor_CodAu) REFERENCES Autor (CodAu)');
        $this->addSql('ALTER TABLE Livro_Assunto ADD CONSTRAINT Livro_Assunto_FKIndex1 FOREIGN KEY (Livro_Codl) REFERENCES Livro (Codl)');
        $this->addSql('ALTER TABLE Livro_Assunto ADD CONSTRAINT Livro_Assunto_FKIndex2 FOREIGN KEY (Assunto_CodAs) REFERENCES Assunto (codAs)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Livro_Autor DROP FOREIGN KEY Livro_Autor_FKIndex1');
        $this->addSql('ALTER TABLE Livro_Autor DROP FOREIGN KEY Livro_Autor_FKIndex2');
        $this->addSql('ALTER TABLE Livro_Assunto DROP FOREIGN KEY Livro_Assunto_FKIndex1');
        $this->addSql('ALTER TABLE Livro_Assunto DROP FOREIGN KEY Livro_Assunto_FKIndex2');
        $this->addSql('DROP TABLE Assunto');
        $this->addSql('DROP TABLE Autor');
        $this->addSql('DROP TABLE Livro');
        $this->addSql('DROP TABLE Livro_Autor');
        $this->addSql('DROP TABLE Livro_Assunto');
    }
}
