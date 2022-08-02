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
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entidade 'Banco'.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"banco","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"banco"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/banco/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/banco/{id}", "security"="is_granted('ROLE_FINAN_MASTER')"},
 *          "delete"={"path"="/fin/banco/{id}", "security"="is_granted('ROLE_FINAN_MASTER')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/banco", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/banco", "security"="is_granted('ROLE_FINAN_MASTER')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 *
 * )
 *
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "codigoBanco": "exact", "id": "exact"})
 * @ApiFilter(BooleanFilter::class, properties={"utilizado": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "codigoBanco", "nome", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\BancoEntityHandler")
 *
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\BancoRepository")
 * @ORM\Table(name="fin_banco")
 *
 * @author Carlos Eduardo Pauluk
 */
class Banco implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="codigo_banco", type="integer", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Range(min = 1)
     * @Groups("banco")
     */
    public ?int $codigoBanco = null;

    /**
     * @ORM\Column(name="nome", type="string", nullable=false, length=200)
     * @Assert\NotBlank()
     * @Groups("banco")
     */
    public ?string $nome = null;

    /**
     * @ORM\Column(name="utilizado", type="boolean", nullable=false)
     * @Assert\NotNull()
     * @Groups("banco")
     */
    public ?bool $utilizado = false;


    /**
     * @param bool $format
     * @return string|null
     */
    public function getCodigoBanco(bool $format = false): ?string
    {
        if ($format) {
            return str_pad($this->codigoBanco, 3, '0', STR_PAD_LEFT);
        }

        return $this->codigoBanco;
    }

    /**
     * @return string
     * @Groups("banco")
     */
    public function getDescricaoMontada(): string
    {
        return $this->getCodigoBanco(true) . ' - ' . $this->nome;
    }

}

