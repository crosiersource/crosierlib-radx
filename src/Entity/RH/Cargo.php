<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\RH;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
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
 *          "get"={"path"="/rh/cargo/{id}", "security"="is_granted('ROLE_RH')"},
 *          "put"={"path"="/rh/cargo/{id}", "security"="is_granted('ROLE_RH')"},
 *          "delete"={"path"="/rh/cargo/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/rh/cargo", "security"="is_granted('ROLE_RH')"},
 *          "post"={"path"="/rh/cargo", "security"="is_granted('ROLE_RH')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"descricao": "partial", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "descricao", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\RH\CargoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\RH\CargoRepository")
 * @ORM\Table(name="rh_cargo")
 */
class Cargo implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="descricao", type="string", nullable=false, length=200)
     * @Groups("entity")
     */
    private ?string $descricao;

}
    
