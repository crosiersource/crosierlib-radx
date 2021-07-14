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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"subgrupo","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"subgrupo"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/subgrupo/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/subgrupo/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/subgrupo/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/subgrupo", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/subgrupo", "security"="is_granted('ROLE_ESTOQUE')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\SubgrupoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\SubgrupoRepository")
 * @ORM\Table(name="est_subgrupo")
 *
 * @author Carlos Eduardo Pauluk
 */
class Subgrupo implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="uuid", type="string", nullable=false, length=36)
     * @NotUppercase()
     * @Groups("subgrupo")
     *
     * @var string|null
     */
    public ?string $UUID = null;

    /**
     *
     * @ORM\Column(name="codigo", type="string", nullable=false)
     * @NotUppercase()
     * @Groups("subgrupo")
     *
     * @var string|null
     */
    public ?string $codigo = null;

    /**
     *
     * @ORM\Column(name="nome", type="string", nullable=false)
     * @Groups("subgrupo")
     * @NotUppercase()
     *
     * @var string|null
     */
    public ?string $nome = null;

    /**
     * Transient.
     * @Groups("subgrupo")
     * @var string|null
     */
    public ?string $descricaoMontada = null;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Grupo")
     * @ORM\JoinColumn(name="grupo_id", nullable=false)
     * @Groups("subgrupo")
     * @MaxDepth(1)
     * @var $grupo Grupo
     */
    public ?Grupo $grupo = null;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("subgrupo")
     */
    public ?array $jsonData = null;

    /**
     * @return string|null
     * @Groups("subgrupo")
     */
    public function getDescricaoMontada(): ?string
    {
        return $this->codigo . ' - ' . $this->nome;
    }

    public function __toString()
    {
        return $this->getId() . ' (' . $this->getDescricaoMontada() . ')';
    }


}
