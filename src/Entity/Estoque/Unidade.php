<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

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
 *     normalizationContext={"groups"={"unidade","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"unidade"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/unidade/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/unidade/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/unidade/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/unidade", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/unidade", "security"="is_granted('ROLE_ESTOQUE')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\UnidadeEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\UnidadeRepository")
 * @ORM\Table(name="est_unidade")
 *
 * @author Carlos Eduardo Pauluk
 */
class Unidade implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="descricao", type="string")
     * @Groups("unidade")
     * @var null|string
     */
    public ?string $descricao = null;

    /**
     *
     * @ORM\Column(name="label", type="string")
     * @Groups("unidade")
     * @NotUppercase()
     * @var null|string
     */
    public ?string $label = null;

    /**
     *
     * @ORM\Column(name="casas_decimais", type="integer")
     * @Groups("unidade")
     * @var null|integer
     */
    public ?int $casasDecimais = null;

    /**
     *
     * @ORM\Column(name="json_info", type="string")
     * @var null|string
     * @NotUppercase()
     * @Groups("unidade")
     */
    public ?string $jsonInfo = null;

    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("unidade")
     */
    public ?array $jsonData = null;

    /**
     *
     * @ORM\Column(name="atual", type="boolean")
     * @Groups("unidade")
     *
     * @var bool|null
     */
    public ?bool $atual = false;


}