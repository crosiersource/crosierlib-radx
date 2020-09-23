<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Vendas;

use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Fatura;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\TipoLancto;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaItem;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\FaturaEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\MovimentacaoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CarteiraRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CategoriaRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\ModoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\TipoLanctoRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class VendaBusiness
 * @package App\Business\Vendas
 *
 * @author Carlos Eduardo Pauluk
 */
class VendaBusiness
{

    private EntityManagerInterface $doctrine;

    private MovimentacaoEntityHandler $movimentacaoEntityHandler;

    private FaturaEntityHandler $faturaEntityHandler;

    private VendaEntityHandler $vendaEntityHandler;

    public function __construct(EntityManagerInterface $doctrine,
                                MovimentacaoEntityHandler $movimentacaoEntityHandler,
                                FaturaEntityHandler $faturaEntityHandler,
                                VendaEntityHandler $vendaEntityHandler)
    {
        $this->doctrine = $doctrine;
        $this->movimentacaoEntityHandler = $movimentacaoEntityHandler;
        $this->faturaEntityHandler = $faturaEntityHandler;
        $this->vendaEntityHandler = $vendaEntityHandler;
    }

    /**
     *
     * @param int $vendaId
     * @throws ViewException
     * @throws \Throwable
     */
    public function recalcularTotais(int $vendaId): void
    {
        try {
            $conn = $this->doctrine->getConnection();
            $rsTotais = $conn->fetchAll('SELECT sum(subtotal) as subtotal, sum(desconto) as desconto, sum(total) as total FROM ven_venda_item WHERE venda_id = :vendaId', ['vendaId' => $vendaId]);
            if (!$rsTotais) {
                throw new ViewException('Erro ao buscar totais da venda');
            }
            $venda = [];
            $venda['subtotal'] = $rsTotais[0]['subtotal'] ?? 0.0;
            $venda['desconto'] = $rsTotais[0]['desconto'] ?? 0.0;
            $venda['valor_total'] = $rsTotais[0]['total'] ?? 0.0;
            $conn->update('ven_venda', $venda, ['id' => $vendaId]);
        } catch (\Throwable $e) {
            if ($e instanceof ViewException) {
                throw $e;
            }
            throw new ViewException('Erro ao recalcular totais para venda (id = "' . $vendaId . '")');
        }
    }

    /**
     * @param Venda $venda
     */
    public function gerarInfoPagtos(Venda $venda): void
    {
        $infoPagtos = '';
        foreach ($venda->pagtos as $pagto) {
            $infoPagtos .= $pagto->planoPagto->descricao . ': ' . number_format($pagto->valorPagto, 2, ',', '.') . '<br>';
        }
        $venda->jsonData['infoPagtos'] = $infoPagtos;
    }


    /**
     * @param Venda $venda
     * @throws ViewException
     */
    public function finalizarPV(Venda $venda)
    {
        try {
            if ($venda->pagtos->count() < 1) {
                throw new ViewException('Nenhuma informação de pagto na venda');
            }
            $this->movimentacaoEntityHandler->getDoctrine()->beginTransaction();
            $fatura = $this->gerarFaturaPorVenda($venda);
            $venda->jsonData['fatura_id'] = $fatura->getId();
            $venda->status = 'PV FINALIZADO';
            $this->vendaEntityHandler->save($venda);
            $this->movimentacaoEntityHandler->getDoctrine()->commit();
        } catch (ViewException $e) {
            $this->movimentacaoEntityHandler->getDoctrine()->rollback();
            throw $e;
        }

    }

    /**
     * @param Venda $venda
     * @return Fatura
     * @throws ViewException
     */
    private function gerarFaturaPorVenda(Venda $venda)
    {
        try {

            $fatura = new Fatura();
            $fatura->dtFatura = $venda->dtVenda;
            $fatura->fechada = true;
            $fatura->jsonData['venda_id'] = $venda->getId();
            $this->faturaEntityHandler->save($fatura);

            /** @var TipoLanctoRepository $repoTipoLancto */
            $repoTipoLancto = $this->doctrine->getRepository(TipoLancto::class);
            $tipoLancto_aPagarReceber = $repoTipoLancto->find(20);

            /** @var ModoRepository $repoModo */
            $repoModo = $this->doctrine->getRepository(Modo::class);
            /** @var CarteiraRepository $repoCarteira */
            $repoCarteira = $this->doctrine->getRepository(Carteira::class);

            /** @var CategoriaRepository $repoCategoria */
            $repoCategoria = $this->doctrine->getRepository(Categoria::class);
            $categoria101 = $repoCategoria->findOneBy(['codigo' => 101]);

            foreach ($venda->pagtos as $pagto) {

                $movimentacao = new Movimentacao();
                $movimentacao->fatura = $fatura;

                $modo = $repoModo->find($pagto->planoPagto->jsonData['modo_id']);
                $movimentacao->modo = $modo;
                $movimentacao->quitado = true;
                $movimentacao->tipoLancto = $tipoLancto_aPagarReceber;

                /** @var Carteira $carteira */
                $carteira = $repoCarteira->find($pagto->jsonData['carteira_id']);
                $movimentacao->carteira = $carteira;
                $movimentacao->categoria = $categoria101;

                $movimentacao->status = $carteira->abertas ? 'ABERTA' : 'REALIZADA';

                $movimentacao->descricao = 'RECEB VENDA ' . $venda->getId();

                $movimentacao->dtMoviment = $venda->dtVenda;
                $movimentacao->valor = $pagto->valorPagto;

                $this->movimentacaoEntityHandler->save($movimentacao);
            }

            return $fatura;

        } catch (\Throwable $e) {
            if ($e instanceof ViewException) {
                /** @var ViewException $ve */
                $ve = $e;
                throw $ve;
            }
            throw new ViewException('Erro ao gerar fatura para venda (id = "' . $venda->getId() . '")');
        }
    }


    /**
     * Regras: se for venda do ecommerce, só permite faturar se status estiver "Pedido em Separação" e possuir saldo
     * em estoque atendível para ecommerce.
     * @param Venda $venda
     * @throws ViewException
     */
    public function verificarPermiteFaturamento(Venda $venda): void
    {
        if ($venda->jsonData['canal'] === 'ECOMMERCE') {

            if (($venda->jsonData['ecommerce_status_descricao'] ?? '') !== 'Pedido em Separação') {
                throw new ViewException('Status difere de "Pedido em Separação". Impossível faturar!');
            }
        }
    }

}