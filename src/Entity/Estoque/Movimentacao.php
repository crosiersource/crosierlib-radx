<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     shortName="Estoque/Movimentacao",
 *     normalizationContext={"groups"={"movimentacao","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"movimentacao"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/movimentacao/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/movimentacao/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/movimentacao/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/movimentacao", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/movimentacao", "security"="is_granted('ROLE_ESTOQUE')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\MovimentacaoEntityHandler")
 * 
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\MovimentacaoRepository")
 * @ORM\Table(name="est_movimentacao")
 *
 * @author Carlos Eduardo Pauluk
 */
class Movimentacao implements EntityId
{

    use EntityIdTrait;

    /**
     * E/S (Entrada/Saída)
     * 
     * @ORM\Column(name="direcao", type="string")
     * @Groups("movimentacao")
     *
     * @var null|string
     */
    public ?string $direcao = null;

    /**
     *
     * @ORM\Column(name="dt_lote", type="datetime")
     * @Groups("movimentacao")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtLote = null;


    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @Groups("movimentacao")
     *
     * @var null|string
     */
    public ?string $descricao = null;


    /**
     *
     * @ORM\Column(name="responsavel", type="string")
     * @Groups("movimentacao")
     *
     * @var null|string
     */
    public ?string $responsavel = null;


    /**
     *
     * @ORM\Column(name="status", type="string")
     * @Groups("movimentacao")
     *
     * @var null|string
     */
    public ?string $status = null;


    /**
     *
     * @ORM\Column(name="dt_integracao", type="datetime")
     * @Groups("movimentacao")
     *
     * @var null|\DateTime
     */
    public ?\DateTime $dtIntegracao = null;


    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("movimentacao")
     */
    public ?array $jsonData = null;

    /**
     *
     * @var null|MovimentacaoItem[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="MovimentacaoItem",
     *      cascade={"persist"},
     *      mappedBy="movimentacao",
     *      orphanRemoval=true)
     * @ORM\OrderBy({"updated" = "DESC"})
     * @Groups("movimentacao")
     */
    public $itens;


    public function __construct()
    {
        $this->itens = new ArrayCollection();
    }

    public function addItem(?MovimentacaoItem $item): void
    {
        if (!$this->itens->contains($item)) {
            $this->itens->add($item);
        }
    }
}
    