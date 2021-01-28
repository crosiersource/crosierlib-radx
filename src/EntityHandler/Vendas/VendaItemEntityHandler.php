<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Business\Vendas\VendaBusiness;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaItem;
use CrosierSource\CrosierLibRadxBundle\Repository\Vendas\VendaItemRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class VendaItemEntityHandler
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas
 *
 * @author Carlos Eduardo Pauluk
 */
class VendaItemEntityHandler extends EntityHandler
{

    public VendaBusiness $vendaBusiness;

    /**
     * VendaItemEntityHandler constructor.
     * @param EntityManagerInterface $doctrine
     * @param Security $security
     * @param ParameterBagInterface $parameterBag
     * @param SyslogBusiness $syslog
     * @param VendaBusiness $vendaBusiness
     */
    public function __construct(EntityManagerInterface $doctrine,
                                Security $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness $syslog,
                                VendaBusiness $vendaBusiness)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog->setApp('radx')->setComponent(self::class));
        $this->vendaBusiness = $vendaBusiness;
    }


    public function getEntityClass(): string
    {
        return VendaItem::class;
    }

    public function beforeSave(/** @var VendaItem $vendaItem */ $vendaItem)
    {
        $vendaItem->subtotal = bcmul($vendaItem->qtde, $vendaItem->precoVenda, 2);
        $vendaItem->desconto = $vendaItem->desconto ?? 0.0;
        $vendaItem->total = bcsub($vendaItem->subtotal, $vendaItem->desconto, 2);

        if (!$vendaItem->ordem) {
            /** @var Connection $conn */
            $conn = $this->getDoctrine()->getConnection();
            $prox = $conn->fetchAssoc('SELECT (max(ordem) + 1) as prox FROM ven_venda_item WHERE venda_id = :vendaId', ['vendaId' => $vendaItem->venda->getId()]);
            $vendaItem->ordem = $prox['prox'] ?? 1;
        }

        if (!$vendaItem->descricao && $vendaItem->produto) {
            $vendaItem->descricao = $vendaItem->produto->nome;
        }

        if (!$vendaItem->unidade) {
            $vendaItem->unidade = $vendaItem->produto->unidadePadrao;
        }

        $vendaItem->qtde = $vendaItem->devolucao ? (abs($vendaItem->qtde) * -1) : abs($vendaItem->qtde);

        $this->vendaBusiness->recalcularTotais($vendaItem->venda->getId());
    }

    /**
     * @param array $ids
     * @return array
     * @throws ViewException
     */
    public function salvarOrdens(array $ids): array
    {
        /** @var VendaItemRepository $repo */
        $repo = $this->getDoctrine()->getRepository(VendaItem::class);
        $i = 1;
        $ordens = [];
        foreach ($ids as $id) {
            if (!$id) continue;
            /** @var VendaItem $vendaItem */
            $item = $repo->find($id);
            $ordens[$id] = $i;
            $item->ordem = $i++;
            $this->save($item);
        }
        return $ordens;
    }


}
