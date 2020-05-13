<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas;

use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibRadxBundle\Business\Vendas\VendaBusiness;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class VendaEntityHandler
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas
 *
 * @author Carlos Eduardo Pauluk
 */
class VendaEntityHandler extends EntityHandler
{

    private VendaBusiness $vendaBusiness;

    /**
     * VendaItemEntityHandler constructor.
     * @param EntityManagerInterface $doctrine
     * @param Security $security
     * @param ParameterBagInterface $parameterBag
     * @param VendaBusiness $vendaBusiness
     */
    public function __construct(EntityManagerInterface $doctrine, Security $security, ParameterBagInterface $parameterBag, VendaBusiness $vendaBusiness)
    {
        parent::__construct($doctrine, $security, $parameterBag);
        $this->vendaBusiness = $vendaBusiness;
    }

    public function beforeSave(/** @var Venda $venda */ $venda)
    {
        $this->vendaBusiness->recalcularTotais($venda);
    }

    public function getEntityClass(): string
    {
        return Venda::class;
    }
}