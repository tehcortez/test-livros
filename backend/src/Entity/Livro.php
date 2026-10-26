<?php

namespace App\Entity;

use App\Repository\LivroRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LivroRepository::class)]
#[ORM\Table(name: 'Livro')]
class Livro
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Codl')]
    private ?int $codL = null;

    #[ORM\Column(name: 'Titulo', length: 40)]
    private ?string $titulo = null;

    #[ORM\Column(name: 'Editora', length: 40)]
    private ?string $editora = null;

    #[ORM\Column(name: 'Edicao')]
    private ?int $edicao = null;

    #[ORM\Column(name: 'AnoPublicacao', length: 4)]
    private ?string $anoPublicacao = null;

    /**
     * @var Collection<int, Autor>
     */
    #[ORM\ManyToMany(targetEntity: Autor::class, inversedBy: 'livros')]
    #[ORM\JoinTable(
        name: 'Livro_Autor',
        joinColumns: [
            new ORM\JoinColumn(
                name: 'Livro_Codl',
                referencedColumnName: 'Codl',
            ),
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'Autor_CodAu',
                referencedColumnName: 'CodAu',
            ),
        ],
        foreignKeyName: 'Livro_Autor_FKIndex1',
        inverseForeignKeyName: 'Livro_Autor_FKIndex2',
    )]
    private Collection $autores;

    /**
     * @var Collection<int, Assunto>
     */
    #[ORM\ManyToMany(targetEntity: Assunto::class, inversedBy: 'livros')]
    #[ORM\JoinTable(
        name: 'Livro_Assunto',
        joinColumns: [
            new ORM\JoinColumn(
                name: 'Livro_Codl',
                referencedColumnName: 'Codl',
            ),
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'Assunto_CodAs',
                referencedColumnName: 'codAs',
            ),
        ],
        foreignKeyName: 'Livro_Assunto_FKIndex1',
        inverseForeignKeyName: 'Livro_Assunto_FKIndex2',
    )]
    private Collection $assuntos;

    #[ORM\Column(name: 'Valor', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $valor = null;

    public function __construct()
    {
        $this->autores = new ArrayCollection();
        $this->assuntos = new ArrayCollection();
    }

    public function getCodL(): ?int
    {
        return $this->codL;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function getEditora(): ?string
    {
        return $this->editora;
    }

    public function setEditora(string $editora): static
    {
        $this->editora = $editora;

        return $this;
    }

    public function getEdicao(): ?int
    {
        return $this->edicao;
    }

    public function setEdicao(int $edicao): static
    {
        $this->edicao = $edicao;

        return $this;
    }

    public function getAnoPublicacao(): ?string
    {
        return $this->anoPublicacao;
    }

    public function setAnoPublicacao(string $anoPublicacao): static
    {
        $this->anoPublicacao = $anoPublicacao;

        return $this;
    }

    /**
     * @return Collection<int, Autor>
     */
    public function getAutores(): Collection
    {
        return $this->autores;
    }

    public function addAutor(Autor $autor): static
    {
        if (!$this->autores->contains($autor)) {
            $this->autores->add($autor);
            $autor->addLivro($this);
        }

        return $this;
    }

    public function removeAutor(Autor $autor): static
    {
        if ($this->autores->removeElement($autor)) {
            $autor->removeLivro($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Assunto>
     */
    public function getAssuntos(): Collection
    {
        return $this->assuntos;
    }

    public function addAssunto(Assunto $assunto): static
    {
        if (!$this->assuntos->contains($assunto)) {
            $this->assuntos->add($assunto);
            $assunto->addLivro($this);
        }

        return $this;
    }

    public function removeAssunto(Assunto $assunto): static
    {
        if ($this->assuntos->removeElement($assunto)) {
            $assunto->removeLivro($this);
        }

        return $this;
    }

    public function getValor(): ?string
    {
        return $this->valor;
    }

    public function setValor(string $valor): static
    {
        $this->valor = $valor;

        return $this;
    }
}
