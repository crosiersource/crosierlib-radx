<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"categoria","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"categoria"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/categoria/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/categoria/{id}", "security"="is_granted('ROLE_FINAN_MASTER')"},
 *          "delete"={"path"="/fin/categoria/{id}", "security"="is_granted('ROLE_FINAN_MASTER')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/categoria", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/categoria", "security"="is_granted('ROLE_FINAN_MASTER')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 *
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={
 *     "codigo": "exact",
 *     "codigoSuper": "exact",
 *     "descricao": "partial",
 *     "id": "exact",
 *     "categoria": "exact"
 * })
 * @ApiFilter(OrderFilter::class, properties={"id", "codigoOrd", "descricao", "dtConsolidado", "updated"}, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\CategoriaEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CategoriaRepository")
 * @ORM\Table(name="fin_categoria")
 *
 * @author Carlos Eduardo Pauluk
 */
class Categoria implements EntityId
{
    use EntityIdTrait;

    public const MASK = '0.00.000.000.000';


    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria", inversedBy="subCategs")
     * @ORM\JoinColumn(name="pai_id",nullable=true)
     * @Groups("categoria")
     * @MaxDepth(1)
     */
    public ?Categoria $pai = null;

    /**
     * @ORM\OneToMany(
     *      targetEntity="Categoria",
     *      mappedBy="pai"
     * )
     * @Groups({"public"})
     *
     * @var Categoria[]|ArrayCollection|null
     */
    public $subCategs = null;

    /**
     * @ORM\Column(name="descricao", type="string")
     * @Groups("categoria")
     */
    public ?string $descricao = null;

    /**
     * Para os casos onde a movimentação é importada automaticamente, define qual a descrição padrão.
     *
     * @ORM\Column(name="descricao_padrao_moviment", type="string")
     */
    public ?string $descricaoPadraoMoviment = null;

    /**
     * @ORM\Column(name="codigo", type="bigint", nullable=false)
     * @Groups("categoria")
     */
    public ?int $codigo = null;

    /**
     * A fim de relatórios.
     *
     * @ORM\Column(name="totalizavel", type="boolean", nullable=false)
     */
    public ?bool $totalizavel = false;

    /**
     * Informa se esta categoria necessita que o CentroCusto seja informado (ou se ele será automático).
     *
     * @ORM\Column(name="centro_custo_dif", type="boolean", nullable=false)
     */
    public ?bool $centroCustoDif = false;

    /**
     * Informa quais ROLES possuem acesso as informações (categoria.descricao e movimentacao.descricao).
     * Para mais de uma, informar separado por vírgula.
     *
     * @ORM\Column(name="roles_acess", type="string", nullable=true, length=2000)
     */
    public ?string $rolesAcess = null;

    /**
     * Caso o usuário logado não possua nenhuma das "rolesAcess", então a descrição alternativa deve ser exibida.
     *
     * @ORM\Column(name="descricao_alternativa", type="string", nullable=true, length=200)
     */
    public ?string $descricaoAlternativa = null;

    /**
     * Atalho para não precisar ficar fazendo parse.
     *
     * @ORM\Column(name="codigo_super", type="bigint", nullable=true)
     * @Groups("categoria")
     */
    public ?int $codigoSuper = null;

    /**
     * Atalho para não precisar ficar fazendo parse.
     *
     * @ORM\Column(name="codigo_ord", type="bigint", nullable=true)
     * @Groups("categoria")
     */
    public ?int $codigoOrd = null;


    /**
     */
    public function __construct()
    {
        $this->subCategs = new ArrayCollection();
    }

    /**
     * @return Categoria[]|ArrayCollection|null
     */
    public function getSubCategs()
    {
        return $this->subCategs;
    }

    /**
     * @param Categoria[]|ArrayCollection|null $subCategs
     * @return Categoria
     */
    public function setSubCategs($subCategs): Categoria
    {
        $this->subCategs = $subCategs;
        return $this;
    }

    /**
     * Retorna a descrição de uma Categoria no formato codigo + descricao (Ex.:
     * 2.01 - DESPESAS PESSOAIS).
     * @Groups("categoria")
     */
    public function getDescricaoMontada(): ?string
    {
        return $this->getCodigoM() . ' - ' . $this->descricao;
    }

    /**
     *
     * Retorna a descrição de uma Categoria no formato codigo + descricao (Ex.:
     * 2.01 - DESPESAS PESSOAIS).
     * @Groups("categoria")
     * @return string|null
     */
    public function getDescricaoMontadaTree(): ?string
    {
        return str_pad('', (strlen($this->codigo) - 1) * 2, '.') . ' ' . $this->getCodigoM() . ' - ' . $this->descricao;
    }

    /**
     * @return string|null
     * @Groups("categoria")
     */
    public function getCodigoM(): ?string
    {
        try {
            return StringUtils::mascarar($this->codigo, self::MASK);
        } catch (Exception $e) {
            return $this->codigo;
        }
    }

    /**
     * Retorna somente o último 'bloco' do código.
     * @return string|null
     */
    public function getCodigoSufixo(): ?string
    {
        if ($this->codigo) {
            if (!$this->pai) {
                return $this->codigo;
            }
            // else
            // Se tem pai, é o restante do código, removendo a parte do pai:
            return substr($this->pai->getCodigoM(), strlen($this->pai->getCodigoM()) + 1);

        }
        return null;
    }


    /**
     * @return string
     * @Groups("categoria")
     */
    public function getMascaraDoFilho(): ?string
    {
        if (!$this->codigo) {
            return null;
        }
        $tam = strlen($this->codigo);
        if ($tam === 1) {
            return '99';
        } elseif ($tam <= 12) {
            return '999';
        } else {
            return null;
        }
    }

}

