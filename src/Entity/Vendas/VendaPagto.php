<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Vendas;

use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Vendas\VendaPagtoRepository")
 * @ORM\Table(name="ven_venda_pagto")
 *
 * @author Carlos Eduardo Pauluk
 */
class VendaPagto implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda", inversedBy="itens")
     * @ORM\JoinColumn(name="venda_id", nullable=false)     *
     *
     * @var null|Venda
     */
    public ?Venda $venda = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Vendas\PlanoPagto")
     * @ORM\JoinColumn(name="plano_pagto_id")
     * @Groups("entity")
     *
     * @var null|PlanoPagto
     */
    public ?PlanoPagto $planoPagto = null;

    /**
     *
     * @ORM\Column(name="valor_pagto", type="decimal")
     * @Groups("entity")
     *
     * @var null|float
     */
    public ?float $valorPagto = null;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;


}