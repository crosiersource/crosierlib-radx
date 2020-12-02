<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\CategoriaRepository")
 * @ORM\Table(name="fin_categoria")
 *
 * @author Carlos Eduardo Pauluk
 */
class Categoria implements EntityId
{
    use EntityIdTrait;

    public const MASK = '0.00.000.000.0000.00000';


    /**
     * @ORM\ManyToOne(targetEntity="CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Categoria", inversedBy="subCategs")
     * @ORM\JoinColumn(name="pai_id",nullable=true)
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
    public $subCategs;

    /**
     * @ORM\Column(name="descricao", type="string")
     * @Groups("entity")
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
     * @Groups("entity")
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
     * @Groups("entity")
     */
    public ?int $codigoSuper = null;

    /**
     * Atalho para não precisar ficar fazendo parse.
     *
     * @ORM\Column(name="codigo_ord", type="bigint", nullable=true)
     * @Groups("entity")
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
    public function setSubCategs($subCategs)
    {
        $this->subCategs = $subCategs;
        return $this;
    }

    /**
     * Retorna a descrição de uma Categoria no formato codigo + descricao (Ex.:
     * 2.01 - DESPESAS PESSOAIS).
     * @Groups("entity")
     */
    public function getDescricaoMontada(): ?string
    {
        return $this->getCodigoM() . ' - ' . $this->descricao;
    }

    /**
     *
     * Retorna a descrição de uma Categoria no formato codigo + descricao (Ex.:
     * 2.01 - DESPESAS PESSOAIS).
     * @Groups("entity")
     * @return string|null
     */
    public function getDescricaoMontadaTree(): ?string
    {
        return str_pad('', (strlen($this->codigo) - 1) * 2, '.') . ' ' . $this->getCodigoM() . ' - ' . $this->descricao;
    }

    /**
     * @return string|null
     * @Groups("entity")
     */
    public function getCodigoM(): ?string
    {
        try {
            return StringUtils::mascarar($this->codigo, self::MASK);
        } catch (\Exception $e) {
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
}

