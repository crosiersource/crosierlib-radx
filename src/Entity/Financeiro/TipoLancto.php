<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

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
 * Entidade Tipo de Lançamento.
 * 
 * @ApiResource(
 *     normalizationContext={"groups"={"entity","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"entity"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/tipoLancto/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/tipoLancto/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fin/tipoLancto/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/tipoLancto", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/tipoLancto", "security"="is_granted('ROLE_FINAN')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 *
 * )
 *
 * @ApiFilter(SearchFilter::class,
 *     properties={
 *     "id": "exact",
 *     "descricao": "partial"
 * })
 * @ApiFilter(OrderFilter::class, properties={"id", "descricao", "dtVencto", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\TipoLanctoEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\TipoLanctoRepository")
 * @ORM\Table(name="fin_tipo_lancto")
 *
 * @author Carlos Eduardo Pauluk
 */
class TipoLancto implements EntityId
{

    use EntityIdTrait;

    /**
     *
     * @ORM\Column(name="codigo", type="integer", nullable=false)
     * @Groups("entity")
     *
     * @var int|null
     */
    public ?int $codigo = null;

    /**
     *
     * @ORM\Column(name="descricao", type="string", nullable=false, length=40)
     * @Groups("entity")
     *
     * @var string|null
     */
    public ?string $descricao = null;

    /**
     * Transient.
     *
     * @var string|null
     */
    public ?string $descricaoMontada = null;


    /**
     * @param bool|null $format
     * @return int|string|null
     */
    public function getCodigo(?bool $format = false)
    {
        if ($format) {
            return str_pad($this->codigo, 2, '0', STR_PAD_LEFT);
        }
        return $this->codigo;
    }

    /**
     * @Groups("entity")
     * @return null|string
     */
    public function getDescricaoMontada(): ?string
    {
        return $this->getCodigo(true) . ' - ' . $this->descricao;
    }


}
