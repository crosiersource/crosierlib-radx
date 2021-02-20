<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Vendas;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
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
 *     normalizationContext={"groups"={"entity","entityId"}},
 *     denormalizationContext={"groups"={"entity"}},
 *
 *     itemOperations={
 *          "get"={"path"="/ven/planoPagto/{id}", "security"="is_granted('ROLE_VENDAS')"},
 *          "put"={"path"="/ven/planoPagto/{id}", "security"="is_granted('ROLE_VENDAS')"},
 *          "delete"={"path"="/ven/planoPagto/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/ven/planoPagto", "security"="is_granted('ROLE_VENDAS')"},
 *          "post"={"path"="/ven/planoPagto", "security"="is_granted('ROLE_VENDAS')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\PlanoPagtoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Vendas\PlanoPagtoRepository")
 * @ORM\Table(name="ven_plano_pagto")
 *
 * @author Carlos Eduardo Pauluk
 */
class PlanoPagto implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="codigo", type="string")
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $codigo = null;

    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
     *
     * @var null|string
     */
    public ?string $descricao = null;

    /**
     *
     * @ORM\Column(name="ativo", type="boolean")
     * @Groups("entity")
     *
     * @var bool|null
     */
    public ?bool $ativo = true;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("entity")
     */
    public ?array $jsonData = null;


}