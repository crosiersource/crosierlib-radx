<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"ncm","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"ncm"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/ncm/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fis/ncm/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fis/ncm/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/ncm", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fis/ncm", "security"="is_granted('ROLE_FINAN')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "documento": "exact", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "documento", "nome", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NcmEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NCMRepository")
 * @ORM\Table(name="fis_ncm")
 *
 * @author Carlos Eduardo Pauluk
 */
class NCM implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="codigo", type="integer", nullable=false)
     * @Assert\NotBlank(message="O campo 'codigo' deve ser informado")
     * @Assert\Range(min = 0)
     * @var int|null
     */
    public ?int $codigo;

    /**
     * @ORM\Column(name="descricao", type="string", nullable=false, length=200)
     * @Assert\NotBlank(message="O campo 'descricao' deve ser informado")
     * @var string|null
     */
    public ?string $descricao;


    public function getCodigo(): ?int
    {
        return $this->codigo;
    }

    public function setCodigo($codigo): NCM
    {
        $this->codigo = $codigo;
        return $this;
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function setDescricao($descricao): NCM
    {
        $this->descricao = $descricao;
        return $this;
    }


}