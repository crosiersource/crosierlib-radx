<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"depto","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"depto"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/depto/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/depto/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/depto/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/depto", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/depto", "security"="is_granted('ROLE_ESTOQUE')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "codigo": "exact", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "codigo", "nome", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\DeptoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\DeptoRepository")
 * @ORM\Table(name="est_depto")
 *
 * @author Carlos Eduardo Pauluk
 */
class Depto implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="uuid", type="string", nullable=false, length=36)
     * @NotUppercase()
     * @Groups("depto")
     *
     * @var string|null
     */
    public ?string $UUID = null;

    /**
     *
     * @ORM\Column(name="codigo", type="string", nullable=false)
     * @NotUppercase()
     * @Groups("depto")
     *
     * @var string|null
     */
    public ?string $codigo = null;

    /**
     *
     * @ORM\Column(name="nome", type="string", nullable=false)
     * @Groups("depto")
     * @NotUppercase()
     *
     * @var string|null
     */
    public ?string $nome = null;

    /**
     *
     * @var Grupo[]|ArrayCollection|null
     *
     * @ORM\OneToMany(
     *      targetEntity="Grupo",
     *      mappedBy="depto",
     *      orphanRemoval=true
     * )
     */
    public $grupos;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("depto")
     */
    public ?array $jsonData = null;


    public function __construct()
    {
        $this->grupos = new ArrayCollection();
    }


    /**
     * @return string|null
     * @Groups("depto")
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
