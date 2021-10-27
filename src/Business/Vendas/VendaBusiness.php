<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Vendas;

use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
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

    public MovimentacaoEntityHandler $movimentacaoEntityHandler;

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
            $rsTotais = $conn->fetchAllAssociative('SELECT sum(subtotal) as subtotal, sum(desconto) as desconto, sum(total) as total FROM ven_venda_item WHERE venda_id = :vendaId', ['vendaId' => $vendaId]);
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
        if ($venda->status === 'PV FINALIZADO') {
            throw new ViewException('PV já finalizado');
        }
        try {
            if (in_array($venda->jsonData['canal'], ['ECOMMERCE', 'MERCADOLIVRE'], true)) {
                $this->finalizarPVECommerce($venda);
            } else {
                $this->finalizarPVSimples($venda);
            }
        } catch (\Throwable $e) {
            throw new ViewException('Não foi possível finalizar o PV', 0, $e);
        }
    }

    /**
     * @param Venda $venda
     * @throws ViewException
     */
    private function finalizarPVSimples(Venda $venda)
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
    private function finalizarPVECommerce(Venda $venda)
    {
        try {
            $fatura = null;
            if (!$venda->pagtos || $venda->pagtos->count() < 1) {
                throw new \LogicException('Venda sem pagtos');
            }
            foreach ($venda->pagtos as $pagto) {
                $integrador = $pagto->jsonData['integrador'] ?? '';
                $formaPagamento = $pagto->jsonData['nomeFormaPagamento'] ?? '';
                if ($integrador === 'Mercado Pago') {
                    $fatura = $this->finalizarPVComPagtoPeloMercadoPago($pagto);
                } elseif (in_array($formaPagamento, ['Depósito Bancário', 'Pix'], true)) {
                    // Pagamentos que precisarão de conferência se 'caíram' na conta
                    $fatura = $this->finalizarPVComPagtoPorDepositoEmAberto($pagto);
                } else {
                    throw new \LogicException('integrador não implementado');
                }
            }
            $venda->jsonData['fatura_id'] = $fatura->getId();
            $venda->status = 'PV FINALIZADO';
            $this->vendaEntityHandler->save($venda);
        } catch (\Throwable $e) {
            $errMsg = 'Erro ao finalizar PV e-commerce';
            $msg = ExceptionUtils::treatException($e);
            throw new ViewException($errMsg . ($msg ? '(' . $msg . ')' : ''), 0, $e);
        }
    }

    /**
     * @param VendaPagto $pagto
     * @throws ViewException
     */
    private function finalizarPVComPagtoPeloMercadoPago(VendaPagto $pagto): Fatura
    {
        try {
            $fatura = new Fatura();
            $fatura->jsonData['venda_id'] = $pagto->venda->getId();
            $fatura->dtFatura = clone $pagto->venda->dtVenda;
            /** @var Fatura $fatura */
            $fatura = $this->faturaEntityHandler->save($fatura);
            
            $venda = $pagto->venda;
            $repoCategoria = $this->doctrine->getRepository(Categoria::class);
            $categoria101 = $repoCategoria->findOneBy(['codigo' => 101]);
            $repoModo = $this->doctrine->getRepository(Modo::class);
            $modo7 = $repoModo->findOneBy(['codigo' => 7]);
            $repoCarteira = $this->doctrine->getRepository(Carteira::class);
            if ($pagto->jsonData['carteira_id']) {
                $carteiraMercadoPago = $repoCarteira->find($pagto->jsonData['carteira_id']);
            } else {
                $repoAppConfig = $this->doctrine->getRepository(AppConfig::class);
                $rs = $repoAppConfig->findOneByFiltersSimpl([['chave', 'EQ', 'ecomm_info_mercadopago_site_carteira_id'], ['appUUID', 'EQ', $_SERVER['CROSIERAPPRADX_UUID']]]);
                $ecomm_info_mercadopago_site_carteira_id = $rs->getValor();
                $carteiraMercadoPago = $repoCarteira->find($ecomm_info_mercadopago_site_carteira_id);
            }

            $movimentacao = new Movimentacao();
            $movimentacao->carteira = $carteiraMercadoPago;
            $movimentacao->dtMoviment = $venda->dtVenda;
            $movimentacao->dtVencto = $venda->dtVenda;
            // A data em que o mercadopago realmente pagou
            // em alguns casos pode não ser, é necessário verificar aqui um dia...
            if ($pagto->jsonData['mercadopago_retorno']['date_approved'] ?? false) {
                $movimentacao->dtPagto = DateTimeUtils::parseDateStr($pagto->jsonData['mercadopago_retorno']['date_approved']);    
            } else {
                $movimentacao->dtPagto = $venda->dtVenda;
            }
            
            $movimentacao->valor = $pagto->valorPagto;
            $movimentacao->categoria = $categoria101;
            $movimentacao->modo = $modo7;
            
            $movimentacao->descricao = 'RECEB VENDA MERCADOPAGO ' .
                str_pad($venda->jsonData['ecommerce_numeroPedido'] ?? '0', 9, 0, STR_PAD_LEFT) . ' - Id: ' .
                str_pad($venda->getId(), 9, 0, STR_PAD_LEFT) . ' (' . $venda->jsonData['infoPagtos'] . ')';
            $sacado = '';
            if (($venda->cliente->documento ?? false) && ($venda->cliente->nome ?? false)) {
                $sacado .= StringUtils::mascararCnpjCpf($venda->cliente->documento) . ' - ' . mb_strtoupper($venda->cliente->nome);
            }
            $movimentacao->sacado = $sacado;
            $movimentacao->jsonData['venda_id'] = $pagto->venda->getId();

            $movimentacao = $this->movimentacaoEntityHandler->save($movimentacao);

            $fatura->addMovimentacao($movimentacao);
            
            $paymentMethodId = ($pagto->jsonData['mercadopago_retorno']['payment_method_id'] ?? '');

            $taxas = [];
            if (($pagto->jsonData['mercadopago_retorno']['status'] ?? '') === 'approved') {
                foreach ($pagto->jsonData['mercadopago_retorno']['fee_details'] as $fee_detail) {
                    if ($fee_detail['type'] === 'application_fee') {
                        continue; // não sei o que é, mas não é cobrado do vendedor
                    }
                    if (($fee_detail['fee_payer'] ?? '') === 'collector') {
                        $taxas[] = [
                            'valor' => $fee_detail['amount'],
                            'descricao' => 'TAXA MERCADOPAGO (' . $paymentMethodId . ') ' . ($fee_detail['type'] ?? ''),
                            'categoria_codigo' => 202005001, // FIXME: deve ser dinâmico pelo cfg_app_config
                        ];
                    }
                }
            }

            foreach ($taxas as $taxa) {
                /** @var Movimentacao $mov_taxa */
                $mov_taxa = $this->movimentacaoEntityHandler->cloneEntityId($movimentacao);
                $categ_taxa = $repoCategoria->findOneBy(['codigo' => $taxa['categoria_codigo']]);
                $mov_taxa->carteiraDestino = null;
                $mov_taxa->sacado = null;
                $mov_taxa->cedente = null;
                $mov_taxa->categoria = $categ_taxa;
                $mov_taxa->valor = $taxa['valor'];
                $mov_taxa->descontos = null;
                $mov_taxa->acrescimos = null;
                $mov_taxa->valorTotal = null;
                $mov_taxa->descricao = $taxa['descricao'];
                $this->movimentacaoEntityHandler->save($mov_taxa);
                $fatura->addMovimentacao($mov_taxa);
            }
            $fatura = $this->faturaEntityHandler->save($fatura);
                        
            return $fatura;
        } catch (\Throwable $e) {
            throw new ViewException('Erro ao finalizarPVComPagtoPeloMercadoPago', 0, $e);
        }
    }


    /**
     * @param VendaPagto $pagto
     * @throws ViewException
     */
    private function finalizarPVComPagtoPorDepositoEmAberto(VendaPagto $pagto): Fatura
    {
        $venda = $pagto->venda;

        $fatura = new Fatura();
        $fatura->jsonData['venda_id'] = $pagto->venda->getId();
        $fatura->dtFatura = clone $pagto->venda->dtVenda;
        /** @var Fatura $fatura */
        $fatura = $this->faturaEntityHandler->save($fatura);
        
        $repoCategoria = $this->doctrine->getRepository(Categoria::class);
        $categoria101 = $repoCategoria->findOneBy(['codigo' => 101]);

        $repoModo = $this->doctrine->getRepository(Modo::class);
        $modoId = $pagto->jsonData['modo_id'];
        $modo = $repoModo->findOneBy(['codigo' => $modoId]);

        $repoCarteira = $this->doctrine->getRepository(Carteira::class);
        $carteiraIndefinida = $repoCarteira->findOneBy(['codigo' => 99]);

        $movimentacao = new Movimentacao();
        $movimentacao->status = 'ABERTA';
        $movimentacao->carteira = $repoCarteira->find($pagto->jsonData['carteira_id']);
        $movimentacao->dtMoviment = $venda->dtVenda;
        $movimentacao->dtVencto = $venda->dtVenda;
        $movimentacao->valor = $pagto->valorPagto;
        $movimentacao->categoria = $categoria101;
        $movimentacao->modo = $modo;
        
        $movimentacao->descricao = 'RECEB VENDA MERCADOPAGO ' .
            str_pad($venda->jsonData['ecommerce_numeroPedido'] ?? '0', 9, 0, STR_PAD_LEFT) . ' - Id: ' .
            str_pad($venda->getId(), 9, 0, STR_PAD_LEFT) . ' (' . $venda->jsonData['infoPagtos'] . ')';
        $sacado = '';
        if (($venda->cliente->documento ?? false) && ($venda->cliente->nome ?? false)) {
            $sacado .= StringUtils::mascararCnpjCpf($venda->cliente->documento) . ' - ' . mb_strtoupper($venda->cliente->nome);
        }
        $movimentacao->sacado = $sacado;
        $movimentacao->jsonData['venda_id'] = $pagto->venda->getId();

        $fatura->addMovimentacao($movimentacao);
        
        $this->movimentacaoEntityHandler->save($movimentacao);

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
            if ($venda->status !== 'PV ABERTO') {
                return false;
            }
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
