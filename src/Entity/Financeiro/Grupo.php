<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidade 'Grupo de Movimentações'.
 *
 * Para movimentações que são agrupadas e pagas através de outra movimentação (como Cartão de Crédito, conta em postos, etc).
 * 
 * @ApiResource(
 *     normalizationContext={"groups"={"grupo","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"grupo"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/grupo/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/grupo/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fin/grupo/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/grupo", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/grupo", "security"="is_granted('ROLE_FINAN')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 *
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"codigo": "exact", "descricao": "partial", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "descricao", "dtConsolidado", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\GrupoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\GrupoRepository")
 * @ORM\Table(name="fin_grupo")
 *
 * @author Carlos Eduardo Pauluk
 */
class Grupo implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="descricao", type="string")
     * @Groups("grupo")
     */
    public ?string $descricao = null;

    /**
     * Dia de vencimento no mês.
     *
     * 32 para sempre último (FIXME: meio burro isso).
     *
     * @ORM\Column(name="dia_vencto", type="integer")
     * @Groups("grupo")
     */
    public ?int $diaVencto = null;

    /**
     * Dia a partir do qual as movimentações são consideradas com vencimento
     * para próximo mês.
     *
     * @ORM\Column(name="dia_inicio", type="integer")
     * @Groups("grupo")
     */
    public ?int $diaInicioAprox = 1;

    /**
     * Informa se esta carteira pode conter movimentações com status ABERTA.
     * útil principalmente para o relatório de contas a pagar/receber, para não considerar movimentações de outras carteiras.
     *
     * @ORM\Column(name="ativo", type="boolean", nullable=false)
     * @Groups("grupo")
     */
    public ?bool $ativo = true;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_pagante_id", nullable=true)
     *
     * @Groups("grupo")
     */
    public ?Carteira $carteiraPagantePadrao = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria")
     * @ORM\JoinColumn(name="categoria_padrao_id", nullable=true)
     *
     * @Groups("grupo")
     */
    public ?Categoria $categoriaPadrao = null;

    /**
     *
     * @ORM\OneToMany(
     *      targetEntity="GrupoItem",
     *      mappedBy="pai",
     *      orphanRemoval=true
     * )
     * @var GrupoItem[]|ArrayCollection|null
     */
    public $itens;


    public function __construct()
    {
        $this->itens = new ArrayCollection();
    }

    /**
     * @return GrupoItem[]|ArrayCollection|null
     */
    public function getItens()
    {
        return $this->itens;
    }

    /**
     * @param GrupoItem[]|ArrayCollection|null $itens
     * @return Grupo
     */
    public function setItens($itens): Grupo
    {
        $this->itens = $itens;
        return $this;
    }


}

