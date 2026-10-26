<?php

declare(strict_types=1);

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class RelatorioController extends AbstractController
{
    #[Route(
        '/api/relatorios/livros-por-autor',
        name: 'api_relatorio_livros_por_autor',
        methods: ['GET'],
    )]
    public function livrosPorAutor(Connection $connection): Response
    {
        $registros = $connection->fetchAllAssociative(
            'SELECT autor_id, autor_nome, livro_id, livro_titulo, editora,
                    edicao, ano_publicacao, valor, assuntos
             FROM vw_relatorio_livros_autor
             ORDER BY autor_nome, livro_titulo, livro_id',
        );

        $autores = [];
        foreach ($registros as $registro) {
            $autorId = $registro['autor_id'] === null
                ? 'sem-autor'
                : (string) $registro['autor_id'];
            $autores[$autorId]['nome'] = $registro['autor_nome'];
            $autores[$autorId]['livros'][] = $registro;
        }

        $html = $this->renderReport($autores);
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                'relatorio-livros-por-autor.pdf',
            ),
        );

        return $response;
    }

    /**
     * @param array<string, array{nome: string, livros: list<array<string, mixed>>}> $autores
     */
    private function renderReport(array $autores): string
    {
        $html = '<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8">
            <style>
                @page { margin: 28px 30px; }
                body { color: #202b3c; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
                h1 { color: #21486d; font-size: 20px; margin: 0 0 5px; }
                .subtitle { color: #6d7787; margin: 0 0 18px; }
                h2 { background: #eaf2f9; border-left: 4px solid #2c5d8a; font-size: 13px; margin: 18px 0 0; padding: 8px; }
                table { border-collapse: collapse; margin: 0 0 12px; width: 100%; }
                th { background: #f4f6f9; color: #4d5b6b; text-align: left; }
                th, td { border: 1px solid #dce3ea; padding: 6px; vertical-align: top; }
                .empty { color: #6d7787; padding: 18px 0; }
            </style></head><body>
            <h1>Acervo de livros por autor</h1>
            <p class="subtitle">Relatório do catálogo com editora, edição, ano, valor e assuntos.</p>';

        if ($autores === []) {
            $html .= '<p class="empty">Nenhum livro cadastrado.</p>';
        }

        foreach ($autores as $autor) {
            $html .= '<h2>'.$this->escape((string) $autor['nome']).'</h2>
                <table><thead><tr>
                    <th>Livro</th><th>Editora</th><th>Edição</th><th>Ano</th>
                    <th>Valor</th><th>Assuntos</th>
                </tr></thead><tbody>';

            foreach ($autor['livros'] as $livro) {
                $valor = number_format((float) $livro['valor'], 2, ',', '.');
                $html .= '<tr>
                    <td>'.$this->escape((string) $livro['livro_titulo']).'</td>
                    <td>'.$this->escape((string) $livro['editora']).'</td>
                    <td>'.$this->escape((string) $livro['edicao']).'</td>
                    <td>'.$this->escape((string) $livro['ano_publicacao']).'</td>
                    <td>R$ '.$valor.'</td>
                    <td>'.$this->escape((string) $livro['assuntos']).'</td>
                </tr>';
            }

            $html .= '</tbody></table>';
        }

        return $html.'</body></html>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
