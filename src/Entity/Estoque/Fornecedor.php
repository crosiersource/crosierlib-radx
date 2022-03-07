<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Estoque;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"fornecedor","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"fornecedor"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/est/fornecedor/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "put"={"path"="/est/fornecedor/{id}", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "delete"={"path"="/est/fornecedor/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/est/fornecedor", "security"="is_granted('ROLE_ESTOQUE')"},
 *          "post"={"path"="/est/fornecedor", "security"="is_granted('ROLE_ESTOQUE')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={"nome": "partial", "nomeFantasia": "partial", "documento": "exact", "id": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "documento", "nome", "nomeFantasia", "updated"}, arguments={"orderParameterName"="order"})
 * @ApiFilter(BooleanFilter::class, properties={"utilizado": "exact"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\FornecedorEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Estoque\FornecedorRepository")
 * @ORM\Table(name="est_fornecedor")
 *
 * @author Carlos Eduardo Pauluk
 */
class Fornecedor implements EntityId
{

    use EntityIdTrait;


    /**
     *
     * @ORM\Column(name="codigo", type="string")
     * @var null|string
     *
     * @Groups("fornecedor")
     */
    public ?string $codigo = null;
    
    /**
     *
     * @ORM\Column(name="nome", type="string")
     * @var null|string
     *
     * @Groups("fornecedor")
     */
    public ?string $nome = null;

    /**
     *
     * @ORM\Column(name="nome_fantasia", type="string")
     * @var null|string
     *
     * @Groups("fornecedor")
     */
    public ?string $nomeFantasia = null;

    /**
     * CPF ou CNPJ.
     *
     * @ORM\Column(name="documento", type="string")
     * @var null|string
     *
     * @Groups("fornecedor")
     */
    public ?string $documento = null;

    /**
     *
     * @ORM\Column(name="inscricao_estadual", type="string")
     * @var null|string
     *
     * @Groups("fornecedor")
     */
    public ?string $inscricaoEstadual = null;


    /**
     * @ORM\Column(name="utilizado", type="boolean")
     * @var null|bool
     *
     * @Groups("fornecedor")
     */
    public ?bool $utilizado = null;
    
    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("fornecedor")
     */
    public ?array $jsonData = null;



    /**
     * @return string
     * @Groups("fornecedor")
     * @SerializedName("nomeFantasiaMontado")
     */
    public function getNomeFantasiaMontado(): string
    {
        return trim($this->nomeFantasia) ?: $this->nome;
    }
    


    /**
     * @return string
     * @Groups("fornecedor")
     */
    public function getNomeMontadoComDocumento(): string
    {
        $r = StringUtils::mascararCnpjCpf($this->documento) . ' - ' . $this->nome;
        if ($this->nomeFantasia ?? false) {
            $r .= ' (' . $this->nomeFantasia ?? '' . ')';
        }
        return $r;
    }

    


}
