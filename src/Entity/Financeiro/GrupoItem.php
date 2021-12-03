<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * Entidade que representa um 'item de um Grupo de Movimentações' (como a fatura
 * de um mês do cartão de crédito, por exemplo).
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"grupoItem","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"grupoItem"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/grupoItem/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/grupoItem/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fin/grupoItem/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/grupoItem", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/grupoItem", "security"="is_granted('ROLE_FINAN')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 *
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "codigoGrupoItem": "exact", "id": "exact"})
 * @ApiFilter(BooleanFilter::class, properties={"utilizado": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "codigoGrupoItem", "nome", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\GrupoItemEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\GrupoItemRepository")
 * @ORM\Table(name="fin_grupo_item")
 *
 * @author Carlos Eduardo Pauluk
 */
class GrupoItem implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Grupo", inversedBy="itens")
     * @ORM\JoinColumn(name="grupo_pai_id", nullable=true)
     * @Groups("grupoItem")
     * @MaxDepth(1)
     */
    public ?Grupo $pai = null;

    /**
     * @ORM\Column(name="descricao", type="string", nullable=false, length=40)
     * @Groups("grupoItem")
     */
    public ?string $descricao = null;

    /**
     * Movimentações desta carteira não poderão ter suas datas alteradas para antes desta.
     *
     * @ORM\Column(name="dt_vencto", type="date", nullable=false)
     * @Groups("grupoItem")
     */
    public ?DateTime $dtVencto = null;

    /**
     * Para efeitos de navegação.
     *
     * @ORM\OneToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem")
     * @ORM\JoinColumn(name="anterior_id", referencedColumnName="id")
     * @Groups("grupoItem")
     * @MaxDepth(1)
     */
    public ?GrupoItem $anterior = null;

    /**
     * Para efeitos de navegação.
     *
     * @ORM\OneToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\GrupoItem")
     * @ORM\JoinColumn(name="proximo_id", referencedColumnName="id")
     * @Groups("grupoItem")
     * @MaxDepth(1)
     */
    public ?GrupoItem $proximo = null;

    /**
     * Utilizado para informar o limite disponível.
     *
     * @ORM\Column(name="valor_informado", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("grupoItem")
     */
    public ?float $valorInformado = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Carteira")
     * @ORM\JoinColumn(name="carteira_pagante_id", nullable=true)
     * @Groups("grupoItem")
     */
    public ?Carteira $carteiraPagante = null;

    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Movimentacao")
     * @ORM\JoinColumn(name="movimentacao_pagante_id", nullable=true)
     * @Groups("grupoItem")
     */
    public ?Movimentacao $movimentacaoPagante = null;

    /**
     * @ORM\Column(name="fechado", type="boolean", nullable=false)
     * @Groups("grupoItem")
     */
    public ?bool $fechado = false;

    /**
     *
     * @ORM\OneToMany(targetEntity="Movimentacao", mappedBy="grupoItem")
     *
     * @var Movimentacao[]|ArrayCollection|null
     */
    public ?$movimentacoes = null;


    /**
     */
    public function __construct()
    {
        $this->movimentacoes = new ArrayCollection();
    }

    /**
     * Método auxiliar para cálculo.
     *
     * @return number
     */
    public function getValorLanctos(): float
    {
        if ($this->movimentacoes && count($this->movimentacoes) > 0) {
            $bdValor = 0.0;
            foreach ($this->movimentacoes as $m) {
                if (strpos($m->getCategoria()->getCodigo(), 0) === 1) {
                    $bdValor += $m->getValorTotal();
                } else {
                    $bdValor -= $m->getValorTotal();
                }
            }
            return abs($bdValor);
        }
        return 0.0;
    }

    /**
     * Método auxiliar para view.
     *
     * @return number
     */
    public function getDiferenca(): ?float
    {
        return $this->getValorLanctos() - $this->valorInformado;
    }
}
