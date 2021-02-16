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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidade 'Centro de Custo'.
 * 
 * @ApiResource(
 *     normalizationContext={"groups"={"entity","entityId"}},
 *     denormalizationContext={"groups"={"entity"}},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/centroCusto/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/centroCusto/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fin/centroCusto/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/centroCusto", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/centroCusto", "security"="is_granted('ROLE_FINAN')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 *
 * )
 *
 * @ApiFilter(SearchFilter::class, properties={"codigo": "exact", "descricao": "partial", "id": "exact", "centroCusto": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "descricao", "dtConsolidado", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\CentroCustoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CentroCustoRepository")
 * @ORM\Table(name="fin_centrocusto")
 *
 * @author Carlos Eduardo Pauluk
 */
class CentroCusto implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="codigo", type="integer", nullable=false)
     * @Groups("entity")
     */
    public ?int $codigo = null;

    /**
     * @ORM\Column(name="descricao", type="string", nullable=false, length=40)
     * @Groups("entity")
     */
    public ?string $descricao;


    /**
     * @Groups("entity")
     * @return string
     */
    public function getDescricaoMontada(): string
    {
        return str_pad($this->codigo, 2, '0', STR_PAD_LEFT) . ' - ' . $this->descricao;
    }

}
