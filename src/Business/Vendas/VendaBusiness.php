<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Vendas;

use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\ExceptionUtils\ExceptionUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Fatura;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\TipoLancto;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaPagto;
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
            $this->movimentacaoEntityHandler->getDoctrine()->beginTransaction();
            $venda->recalcularTotais();

            if ($venda->pagtos->count() < 1) {
                throw new ViewException('Nenhuma informação de pagto na venda');
            }

            if ($venda->getTotalPagtos() !== $venda->valorTotal) {
                throw new ViewException('Total de pagtos difere do valor da venda');
            }

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
     * @throws ViewException
     */
    public function finalizarPVECommerce(Venda $venda)
    {
        try {
            $this->doctrine->beginTransaction();

            $fatura = null;
            foreach ($venda->pagtos as $pagto) {
                $integrador = $pagto->jsonData['integrador'] ?? '';
                $formaPagamento = $pagto->jsonData['nomeFormaPagamento'] ?? '';
                if ($integrador === 'Mercado Pago') {
                    $fatura = $this->finalizarPVComPagtoPeloMercadoPago($pagto);
                } elseif ($formaPagamento === 'Depósito Bancário') {
                    $fatura = $this->finalizarPVComPagtoPorDepositoEmAberto($pagto);
                } else {
                    throw new \LogicException('integrador não implementado');
                }
            }
            $venda->jsonData['fatura_id'] = $fatura->getId();
            $venda->status = 'PV FINALIZADO';
            $this->vendaEntityHandler->save($venda);

            $this->doctrine->commit();
        } catch (\Throwable $e) {
            $errMsg = 'Erro ao finalizar PV e-commerce';
            $msg = ExceptionUtils::treatException($e);
            $this->doctrine->rollback();
            throw new ViewException($errMsg . ($msg ? '(' . $msg . ')' : ''), 0, $e);
        }
    }

    /**
     * @param VendaPagto $pagto
     * @throws ViewException
     */
    private function finalizarPVComPagtoPeloMercadoPago(VendaPagto $pagto): Fatura
    {
        $venda = $pagto->venda;
        $repoCategoria = $this->doctrine->getRepository(Categoria::class);
        $categoria101 = $repoCategoria->findOneBy(['codigo' => 101]);

        $repoModo = $this->doctrine->getRepository(Modo::class);
        $modo7 = $repoModo->findOneBy(['codigo' => 7]);


        $repoAppConfig = $this->doctrine->getRepository(AppConfig::class);
        $rs = $repoAppConfig->findOneByFiltersSimpl([['chave', 'EQ', 'ecomm_info_mercadopago_site_carteira_id'], ['appUUID', 'EQ', $_SERVER['CROSIERAPPRADX_UUID']]]);
        $ecomm_info_mercadopago_site_carteira_id = $rs->getValor();

        $repoCarteira = $this->doctrine->getRepository(Carteira::class);
        $carteiraMercadoPago = $repoCarteira->find($ecomm_info_mercadopago_site_carteira_id);


        $movimentacao = new Movimentacao();
        $movimentacao->carteira = $repoCarteira->find($pagto->jsonData['carteira_id']);
        $movimentacao->dtPagto = $venda->dtVenda;
        $movimentacao->valor = $pagto->valorPagto;
        $movimentacao->categoria = $categoria101;
        $movimentacao->modo = $modo7;
        $movimentacao->carteiraDestino = $carteiraMercadoPago;
        $movimentacao->descricao = 'RECEB VENDA ECOMMERCE ' . str_pad($venda->getId(), 9, 0, STR_PAD_LEFT);
        $sacado = '';
        if (($venda->cliente->documento ?? false) && ($venda->cliente->nome ?? false)) {
            $sacado .= StringUtils::mascararCnpjCpf($venda->cliente->documento) . ' - ' . mb_strtoupper($venda->cliente->nome);
        }
        $movimentacao->sacado = $sacado;
        $movimentacao->jsonData['venda_id'] = $pagto->venda->getId();

        $this->movimentacaoEntityHandler->saveFaturaTransacional($movimentacao);


        $paymentMethodId = ($pagto->jsonData['mercadopago_retorno']['payment_method_id'] ?? '');

        if (($pagto->jsonData['mercadopago_retorno']['status'] ?? '') === 'approved') {
            foreach ($pagto->jsonData['mercadopago_retorno']['fee_details'] as $fee_detail) {
                if (($fee_detail['fee_payer'] ?? '') === 'collector') {
                    if ($paymentMethodId === 'credit_card') {
                        $taxa = [
                            'valor' => $fee_detail['amount'],
                            'descricao' => 'TAXA MERCADOPAGO (CREDIT CARD)',
                            'categoria_codigo' => 202005001,
                        ];
                    } elseif (strpos($paymentMethodId, 'bol') === 0) {
                        $taxa = [
                            'valor' => $fee_detail['amount'],
                            'descricao' => 'TAXA MERCADOPAGO (BOLBRADESCO)',
                            'categoria_codigo' => 202005001,
                        ];
                    }
                    $this->movimentacaoEntityHandler->lancarQuitamentoEmFaturaTransacional($movimentacao->fatura, $movimentacao->valorTotal, [$taxa]);

                }
            }
        }

        return $movimentacao->fatura;
    }


    /**
     * @param VendaPagto $pagto
     * @throws ViewException
     */
    private function finalizarPVComPagtoPorDepositoEmAberto(VendaPagto $pagto): Fatura
    {
        $venda = $pagto->venda;
        $repoCategoria = $this->doctrine->getRepository(Categoria::class);
        $categoria101 = $repoCategoria->findOneBy(['codigo' => 101]);

        $repoModo = $this->doctrine->getRepository(Modo::class);
        $modo5_boleto = $repoModo->findOneBy(['codigo' => 5]);

        $repoCarteira = $this->doctrine->getRepository(Carteira::class);
        $carteiraIndefinida = $repoCarteira->findOneBy(['codigo' => 99]);

        $movimentacao = new Movimentacao();
        $movimentacao->carteira = $repoCarteira->find($pagto->jsonData['carteira_id']);
        $movimentacao->dtPagto = $venda->dtVenda;
        $movimentacao->valor = $pagto->valorPagto;
        $movimentacao->categoria = $categoria101;
        $movimentacao->modo = $modo5_boleto;
        $movimentacao->carteiraDestino = $carteiraIndefinida;
        $movimentacao->descricao = 'RECEB VENDA ECOMMERCE ' . str_pad($venda->getId(), 9, 0, STR_PAD_LEFT);
        $sacado = '';
        if (($venda->cliente->documento ?? false) && ($venda->cliente->nome ?? false)) {
            $sacado .= StringUtils::mascararCnpjCpf($venda->cliente->documento) . ' - ' . mb_strtoupper($venda->cliente->nome);
        }
        $movimentacao->sacado = $sacado;
        $movimentacao->jsonData['venda_id'] = $pagto->venda->getId();

        $this->movimentacaoEntityHandler->saveFaturaTransacional($movimentacao, false);

        return $movimentacao->fatura;
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
            $tipoLancto_transferenciaDeEntradaDeCaixa = $repoTipoLancto->find(61);


            /** @var ModoRepository $repoModo */
            $repoModo = $this->doctrine->getRepository(Modo::class);

            /** @var CarteiraRepository $repoCarteira */
            $repoCarteira = $this->doctrine->getRepository(Carteira::class);

            /** @var CategoriaRepository $repoCategoria */
            $repoCategoria = $this->doctrine->getRepository(Categoria::class);
            $categoria101 = $repoCategoria->findOneBy(['codigo' => 101]);
            $categoria251 = $repoCategoria->findOneBy(['codigo' => 251]); //SAÍDA - AJUSTE DE CAIXA

            foreach ($venda->pagtos as $pagto) {

                $movimentacao = new Movimentacao();
                $movimentacao->fatura = $fatura;

                $modo = $repoModo->find($pagto->planoPagto->jsonData['modo_id']);
                $movimentacao->modo = $modo;

                /** @var Carteira $carteiraOrigem */
                $carteiraOrigem = $repoCarteira->find($pagto->jsonData['carteira_id']);
                $movimentacao->carteira = $carteiraOrigem;

                $carteiraDestinoId = $pagto->jsonData['carteira_destino_id'] ?? null;

                if ($pagto->planoPagto->jsonData['tipo_carteiras'] === 'caixa') {
                    // Os ven_venda_pagto que não tem carteira_destino_id são aqueles onde a movimentação é somente no caixa
                    if (!$carteiraDestinoId) {
                        $movimentacao->tipoLancto = $tipoLancto_aPagarReceber;
                    } else {
                        $movimentacao->tipoLancto = $tipoLancto_transferenciaDeEntradaDeCaixa;
                        /** @var Carteira $carteiraDestino */
                        $carteiraDestino = $repoCarteira->find($pagto->jsonData['carteira_destino_id']);
                        $movimentacao->carteiraDestino = $carteiraDestino;
                    }
                } else {
                    $movimentacao->tipoLancto = $tipoLancto_aPagarReceber;
                }

                if ((int)$pagto->planoPagto->codigo === 999) {
                    $movimentacao->categoria = $categoria251;
                } else {
                    $movimentacao->categoria = $categoria101;
                }

                $movimentacao->status = $carteiraOrigem->abertas ? 'ABERTA' : 'REALIZADA';
                $movimentacao->quitado = $movimentacao->status === 'REALIZADA';

                $movimentacao->descricao = 'RECEB VENDA ' . str_pad($venda->getId(), '10', '0', STR_PAD_LEFT);
                $movimentacao->descricao .= ' (CLIENTE: ' . StringUtils::mascararCnpjCpf($venda->cliente->documento) . ' - ' .
                    $venda->cliente->nome . ')';
                if ((int)$pagto->planoPagto->codigo === 999) {
                    $movimentacao->descricao .= ' *** DESCONTO';
                }

                $movimentacao->dtMoviment = $venda->dtVenda;
                $movimentacao->dtVencto = $venda->dtVenda;
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

    /**
     * Regras para permitir a finalização da venda.
     * @param Venda $venda
     * @return bool
     * @throws ViewException
     */
    public function permiteFinalizarVenda(Venda $venda): bool
    {
        try {
            $repoAppConfig = $this->doctrine->getRepository(AppConfig::class);
            /** @var AppConfig $rs */
            $rs = $repoAppConfig->findOneByFiltersSimpl([['chave', 'EQ', 'vendas.config.json'], ['appUUID', 'EQ', $_SERVER['CROSIERAPPRADX_UUID']]]);
            if (!$rs) return false;
            $vendasConfig = $rs->getValorJsonDecoded();
            if (!($vendasConfig['integra_venda_ao_financeiro'] ?? false)) {
                return false;
            }
            if ($venda->jsonData['canal'] === 'ECOMMERCE') {

                $statusQuePermitemFinaliz = explode(',', $vendasConfig['ecommerce_status_que_permite_finalizar_venda']);
                return (
                    $vendasConfig &&
                    (in_array($venda->jsonData['ecommerce_status'], $statusQuePermitemFinaliz, true))
                );
            } else {
                return ($venda->status === 'PV ABERTO');
            }
        } catch (\Throwable $e) {
            $msg = ExceptionUtils::treatException($e);
            throw new ViewException('Erro em permiteFinalizarVenda' . ($msg ? ' (' . $msg . ')' : ''), 0, $e);
        }
    }


}