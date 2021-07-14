<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Vendas;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"vendaPagto","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"vendaPagto"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/ven/vendaPagto/{id}", "security"="is_granted('ROLE_VENDAS')"},
 *          "put"={"path"="/ven/vendaPagto/{id}", "security"="is_granted('ROLE_VENDAS')"},
 *          "delete"={"path"="/ven/vendaPagto/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/ven/vendaPagto", "security"="is_granted('ROLE_VENDAS')"},
 *          "post"={"path"="/ven/vendaPagto", "security"="is_granted('ROLE_VENDAS')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "documento": "exact", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "documento", "nome", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaPagtoEntityHandler")
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
     * @Groups("vendaPagto")
     *
     * @var null|PlanoPagto
     */
    public ?PlanoPagto $planoPagto = null;

    /**
     *
     * @ORM\Column(name="valor_pagto", type="decimal")
     * @Groups("vendaPagto")
     *
     * @var null|float
     */
    public ?float $valorPagto = null;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("vendaPagto")
     */
    public ?array $jsonData = null;


}