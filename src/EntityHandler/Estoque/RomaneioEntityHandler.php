<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Romaneio;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\RomaneioItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 *
 * @author Carlos Eduardo Pauluk
 */
class RomaneioEntityHandler extends EntityHandler
{

    private FornecedorEntityHandler $fornecedorEntityHandler;

    public function __construct(EntityManagerInterface $doctrine,
                                Security $security,
                                ParameterBagInterface $parameterBag,
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
        $romaneio->dtEmissao = $notaFiscal->getDtEmissao();
        $romaneio->total = $notaFiscal->getValorTotal();
        $romaneio->notaFiscal = $notaFiscal;

        /** @var NotaFiscalItem $nfItem */
        foreach ($notaFiscal->getItens() as $nfItem) {
            $romaneioItem = new RomaneioItem();
            $romaneio->addItem($romaneioItem);
            $romaneioItem->descricao = $nfItem->codigo . ' - ' . $nfItem->descricao;
            $romaneioItem->ordem = $nfItem->getOrdem();
            $romaneioItem->precoCusto = $nfItem->getValorUnit();
            $romaneioItem->qtde = $nfItem->getQtde();
            $romaneioItem->total = bcmul($romaneioItem->qtde, $romaneioItem->precoCusto);
            $romaneioItem->jsonData = [
                'ncm' => $nfItem->getNcm(),
                'unidade' => $nfItem->getUnidade(),
                'ean' => $nfItem->getEan(),
            ];
            $this->handleSavingEntityId($romaneioItem);
        }
        $romaneio = $this->save($romaneio);
        return $romaneio;
    }
}
