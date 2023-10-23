<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Messenger\CrosierQueueHandler;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Fatura;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Modo;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\FaturaEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\MovimentacaoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalEntityHandler;

class FaturaBusiness
{

    public FaturaEntityHandler $faturaEntityHandler;

    public MovimentacaoEntityHandler $movimentacaoEntityHandler;

    public NotaFiscalEntityHandler $notaFiscalEntityHandler;

    public CrosierQueueHandler $crosierQueueHandler;

    public function __construct(
        FaturaEntityHandler       $faturaEntityHandler,
        MovimentacaoEntityHandler $movimentacaoEntityHandler,
        NotaFiscalEntityHandler   $notaFiscalEntityHandler,
        CrosierQueueHandler       $crosierQueueHandler)
    {
        $this->faturaEntityHandler = $faturaEntityHandler;
        $this->movimentacaoEntityHandler = $movimentacaoEntityHandler;
        $this->notaFiscalEntityHandler = $notaFiscalEntityHandler;
        $this->crosierQueueHandler = $crosierQueueHandler;
    }


    public function lancarDuplicatasPorNotaFiscal(
        NotaFiscal $notaFiscal,
        Carteira   $carteira,
        Categoria  $categoria
    ): void
    {
        if ($notaFiscal->jsonData['fin_fatura_id'] ?? false) {
            throw new ViewException("Nota Fiscal já possui fatura vinculada (id=" . $notaFiscal->jsonData['fin_fatura_id'] . ")");
        }
        $this->faturaEntityHandler->getDoctrine()->beginTransaction();

        $fatura = new Fatura();

        $this->faturaEntityHandler->save($fatura);

        $repoModo = $this->faturaEntityHandler->getDoctrine()->getRepository(Modo::class);
        $modoBoleto = $repoModo->findOneBy(['codigo' => Modo::BOLETO_GUIA_DDA]);

        foreach ($notaFiscal->getDadosDuplicatas() as $duplicata) {
            $movimentacao = new Movimentacao();
            $movimentacao->descricao = 'LANCTO REF NF ' . $notaFiscal->numero;
            $movimentacao->carteira = $carteira;
            $movimentacao->categoria = $categoria;
            $movimentacao->dtMoviment = $notaFiscal->dtSaiEnt ?? $notaFiscal->dtEmissao;
            $movimentacao->dtVencto = DateTimeUtils::parseDateStr($duplicata['vencimento']);
            $movimentacao->valor = $duplicata['valor'];
            $movimentacao->fatura = $fatura;

            $movimentacao->sacadoNome = $notaFiscal->xNomeDestinatario;
            $movimentacao->sacadoDocumento = $notaFiscal->documentoDestinatario;

            $movimentacao->cedenteDocumento = $notaFiscal->documentoEmitente;
            $movimentacao->cedenteNome = $notaFiscal->xNomeEmitente;

            if (count($notaFiscal->getDadosDuplicatas()) > 1) {
                $movimentacao->qtdeParcelas = count($notaFiscal->getDadosDuplicatas());
                $movimentacao->parcelaNum = $duplicata['numero'];
                $movimentacao->parcelamento = true;
            }

            $movimentacao->modo = $modoBoleto;

            $this->movimentacaoEntityHandler->save($movimentacao, false);
        }
        $fatura->jsonData['nota_fiscal_id'] = $notaFiscal->getId();
        $this->faturaEntityHandler->save($fatura);

        $notaFiscal->jsonData['fin_fatura_id'] = $fatura->getId();

        $this->notaFiscalEntityHandler->save($notaFiscal);

        $this->faturaEntityHandler->getDoctrine()->commit();

        $this->crosierQueueHandler->post('radx.fiscal.fis_nf_2_fin_fatura', $fatura->getId());
    }


    public function enviarFaturaParaReprocessamento(NotaFiscal $notaFiscal): void
    {
        $this->crosierQueueHandler->post('radx.fiscal.fis_nf_2_fin_fatura', $notaFiscal->jsonData['fin_fatura_id']);
    }

}
