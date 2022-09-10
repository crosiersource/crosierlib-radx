<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Romaneio;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\RomaneioItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class RomaneioEntityHandler extends EntityHandler
{

    private FornecedorEntityHandler $fornecedorEntityHandler;

    public function __construct(ManagerRegistry         $doctrine,
                                Security                $security,
                                ParameterBagInterface   $parameterBag,
                                FornecedorEntityHandler $fornecedorEntityHandler)
    {
        parent::__construct($doctrine, $security, $parameterBag);
        $this->fornecedorEntityHandler = $fornecedorEntityHandler;
    }


    public function getEntityClass(): string
    {
        return Romaneio::class;
    }


    /**
     * @param NotaFiscal $notaFiscal
     * @return \CrosierSource\CrosierLibBaseBundle\Entity\EntityId|Romaneio
     * @throws \CrosierSource\CrosierLibBaseBundle\Exception\ViewException
     */
    public function buildFromNotaFiscal(NotaFiscal $notaFiscal)
    {
        /** @var Romaneio $romaneio */
        $romaneio = $this->getDoctrine()->getRepository(Romaneio::class)->findOneBy(['notaFiscal' => $notaFiscal]);
        if ($romaneio) {
            return $romaneio;
        }

        $romaneio = new Romaneio();
        $romaneio->fornecedor = $this->fornecedorEntityHandler->fornecedorFromNotaFiscal($notaFiscal);
        $romaneio->dtEmissao = $notaFiscal->dtEmissao;
        $romaneio->total = $notaFiscal->valorTotal;
        $romaneio->notaFiscal = $notaFiscal;

        /** @var NotaFiscalItem $nfItem */
        foreach ($notaFiscal->getItens() as $nfItem) {
            $romaneioItem = new RomaneioItem();
            $romaneio->addItem($romaneioItem);
            $romaneioItem->descricao = $nfItem->codigo . ' - ' . $nfItem->descricao;
            $romaneioItem->ordem = $nfItem->ordem;
            $romaneioItem->precoCusto = $nfItem->valorUnit;
            $romaneioItem->qtde = $nfItem->qtde;
            $romaneioItem->total = bcmul($romaneioItem->qtde, $romaneioItem->precoCusto);
            $romaneioItem->jsonData = [
                'ncm' => $nfItem->ncm,
                'unidade' => $nfItem->unidade,
                'ean' => $nfItem->ean,
            ];
            $this->handleSavingEntityId($romaneioItem);
        }
        $romaneio = $this->save($romaneio);
        return $romaneio;
    }
}
