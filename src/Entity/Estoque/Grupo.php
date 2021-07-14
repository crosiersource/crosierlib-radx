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
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"grupo","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"grupo"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/grupo/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/grupo/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/grupo/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/grupo", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/grupo", "security"="is_granted('ROLE_ESTOQUE')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "codigo": "exact", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "codigo", "nome", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\GrupoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\GrupoRepository")
 * @ORM\Table(name="est_grupo")
 *
 * @author Carlos Eduardo Pauluk
 */
class Grupo implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="uuid", type="string", nullable=false, length=36)
     * @NotUppercase()
     * @Groups("grupo")
     *
     * @var string|null
     */
    public ?string $UUID = null;

    /**
     *
     * @ORM\Column(name="codigo", type="string", nullable=false)
     * @NotUppercase()
     * @Groups("grupo")
     *
     * @var string|null
     */
    public ?string $codigo = null;

    /**
     *
     * @ORM\Column(name="nome", type="string", nullable=false)
     * @Groups("grupo")
     * @NotUppercase()
     *
     * @var string|null
     */
    public ?string $nome = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Depto")
     * @ORM\JoinColumn(name="depto_id", nullable=false)
     * @Groups("grupo")
     * @MaxDepth(1)
     * @var $depto Depto
     */
    public ?Depto $depto = null;

    /**
     *
     * @var Subgrupo[]|ArrayCollection|null
     *
     * @ORM\OneToMany(
     *      targetEntity="Subgrupo",
     *      mappedBy="grupo",
     *      orphanRemoval=true
     * )
     */
    public $subgrupos;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("grupo")
     */
    public ?array $jsonData = null;


    public function __construct()
    {
        $this->subgrupos = new ArrayCollection();
    }

    /**
     * @return string|null
     * @Groups("grupo")
     */
    public function getDescricaoMontada(): ?string
    {
        return $this->codigo . ' - ' . $this->nome;
    }


}