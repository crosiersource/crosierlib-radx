<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Business\Vendas\VendaBusiness;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaItem;
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

    private VendaBusiness $vendaBusiness;

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

        $this->vendaBusiness->recalcularTotais($vendaItem->venda);
    }

}