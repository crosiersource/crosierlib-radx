<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;


/**
 * Entidade 'Fatura'.
 *
 * Agrupa diversas movimentações que são pagas com referência a um documento fiscal.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"fatura","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"fatura"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fin/fatura/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fin/fatura/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fin/fatura/{id}", "security"="is_granted('ROLE_FINAN_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fin/fatura", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fin/fatura", "security"="is_granted('ROLE_FINAN')"}
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
 * @ApiFilter(DateFilter::class, properties={"dtFatura"})
 *
 * @ApiFilter(BooleanFilter::class, properties={
 *     "fechada",
 *     "quitada",
 *     "cancelada"
 * })
 *
 * @ApiFilter(SearchFilter::class, properties={
 *     "sacadoDocumento": "exact",
 *     "sacadoNome": "partial",
 *     "cedenteDocumento": "exact",
 *     "cedenteNome": "partial"
 * })
 *
 * @ApiFilter(OrderFilter::class, properties={
 *     "id",
 *     "updated",
 *     "dtFatura"
 * }, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Financeiro\FaturaEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Financeiro\FaturaRepository")
 * @ORM\Table(name="fin_fatura")
 *
 * @author Carlos Eduardo Pauluk
 */
class Fatura implements EntityId
{

    use EntityIdTrait;


    /**
     * @ORM\Column(name="descricao", type="string", nullable=true)
     * @Groups("fatura")
     */
    public ?string $descricao = null;


    /**
     * CPF/CNPJ de quem paga esta movimentação.
     *
     * @ORM\Column(name="sacado_documento", type="string", nullable=true)
     * @Groups("fatura")
     */
    public ?string $sacadoDocumento = null;


    /**
     * Razão Social / Nome de quem paga esta movimentação.
     *
     * @ORM\Column(name="sacado_nome", type="string", nullable=true)
     * @Groups("fatura")
     */
    public ?string $sacadoNome = null;


    /**
     * CPF/CNPJ de quem recebe esta movimentação.
     *
     * @ORM\Column(name="cedente_documento", type="string", nullable=true)
     * @Groups("fatura")
     */
    public ?string $cedenteDocumento = null;


    /**
     * Razão Social / Nome de quem recebe esta movimentação.
     *
     * @ORM\Column(name="cedente_nome", type="string", nullable=true)
     * @Groups("fatura")
     */
    public ?string $cedenteNome = null;


    /**
     * Data em que a movimentação efetivamente aconteceu.
     *
     * @ORM\Column(name="dt_fatura", type="datetime")
     * @Groups("fatura")
     *
     * @var DateTime|null
     */
    public ?DateTime $dtFatura = null;


    /**
     * Data em que a movimentação efetivamente aconteceu.
     *
     * @ORM\Column(name="dt_vencto", type="datetime")
     * @Groups("fatura")
     *
     * @var DateTime|null
     */
    public ?DateTime $dtVencto = null;


    /**
     *
     * Se for fechada, não é possível incluir outras movimentações na fatura.
     *
     * @ORM\Column(name="fechada", type="boolean")
     * @Groups("fatura")
     *
     * @var bool|null
     */
    public ?bool $fechada = false;


    /**
     *
     * @ORM\Column(name="quitada", type="boolean")
     * @Groups("fatura")
     *
     * @var bool|null
     */
    public ?bool $quitada = false;


    /**
     *
     * @ORM\Column(name="cancelada", type="boolean")
     * @Groups("fatura")
     *
     * @var bool|null
     */
    public ?bool $cancelada = false;


    /**
     * @ORM\Column(name="obs", type="string", nullable=true)
     * @Groups("fatura")
     */
    public ?string $obs = null;


    /**
     *
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("fatura")
     */
    public ?array $jsonData = null;


    /**
     *
     * @var Movimentacao[]|ArrayCollection|null
     *
     * @ORM\OneToMany(targetEntity="Movimentacao", mappedBy="fatura")
     */
    public $movimentacoes = null;


    public function __construct()
    {
        $this->movimentacoes = new ArrayCollection();
    }

    
    /**
     * @Groups("fatura")
     * @return null|string
     */
    public function getSacado(): ?string {
        if ($this->sacadoDocumento && $this->sacadoNome) {
            return StringUtils::mascararCnpjCpf($this->sacadoDocumento) . ' - ' . $this->sacadoNome;   
        }
        return null;
    }


    /**
     * @Groups("fatura")
     * @return null|string
     */
    public function getCedente(): ?string {
        if ($this->cedenteDocumento && $this->cedenteNome) {
            return StringUtils::mascararCnpjCpf($this->cedenteDocumento) . ' - ' . $this->cedenteNome;
        }
        return null;
    }
    

    /**
     * @return Movimentacao[]|ArrayCollection|null
     */
    public function getMovimentacoes()
    {
        return $this->movimentacoes;
    }


    /**
     * @param Movimentacao[]|ArrayCollection|null $movimentacoes
     * @return Fatura
     */
    public function setMovimentacoes($movimentacoes): Fatura
    {
        $this->movimentacoes = $movimentacoes;
        return $this;
    }


    /**
     * @param Movimentacao $movimentacao
     * @return $this
     */
    public function addMovimentacao(Movimentacao $movimentacao): self
    {
        if (!$this->movimentacoes->contains($movimentacao)) {
            $this->movimentacoes[] = $movimentacao;
            $movimentacao->fatura = $this;
        }
        return $this;
    }


    /**
     * @param Movimentacao $movimentacao
     * @return $this
     */
    public function removeMovimentacao(Movimentacao $movimentacao): self
    {
        if ($this->movimentacoes->contains($movimentacao)) {
            $this->movimentacoes->removeElement($movimentacao);
            if ($movimentacao->fatura === $this) {
                $movimentacao->fatura = null;
            }
        }
        return $this;
    }


    /**
     * @param int $codigo
     * @return Movimentacao|null
     */
    public function getPrimeiraMovimentacaoByCategoriaCodigo(int $codigo): ?Movimentacao
    {
        foreach ($this->getMovimentacoes() as $m) {
            if ($m->categoria->codigo === $codigo) {
                return $m;
            }
        }
        return null;
    }


    /**
     * @Groups("fatura")
     * @SerializedName("dtQuitacao")
     * @return null|\DateTime
     */
    public function getDtQuitacao(): ?\DateTime
    {
        $maior = null;
        if ($this->quitada) {
            if ($this->movimentacoes) {
                foreach ($this->movimentacoes as $movimentacao) {
                    if (!$maior || ($movimentacao->dtPagto && DateTimeUtils::diffInDias($movimentacao->dtPagto, $maior) > 0)) {
                        $maior = clone $movimentacao->dtPagto;
                    }
                }
            }
        }
        return $maior;
    }


    public function getValorTotalCobrancaFatura(?bool $apenasRealizadas = false): float
    {
        $total = 0.0;
        if ($this->movimentacoes) {
            $statuss = $apenasRealizadas ? ['REALIZADA'] : ['ABERTA', 'REALIZADA'];
            foreach ($this->movimentacoes as $movimentacao) {
                if (in_array($movimentacao->status, $statuss, true) &&
                    in_array($movimentacao->categoria->codigo, [110, 210], true)) {
                    $total = bcadd($total, $movimentacao->valorTotal, 2);
                }
            }
        }
        return $total;
    }


    /**
     * @Groups("fatura")
     * @var float
     */
    public function getValorTotal(): float
    {
        $total = 0.0;
        if ($this->movimentacoes) {
            foreach ($this->movimentacoes as $movimentacao) {
                if (!in_array($movimentacao->categoria->codigo, [110, 210], true)) {
                    $total = bcadd($total, $movimentacao->valorTotal, 2);
                }
            }
        }
        return $total;
    }


    /**
     * @Groups("fatura")
     * @var float
     */
    public function getSaldo(): float {
        return (float)bcsub($this->getValorTotal(), $this->getValorTotalCobrancaFatura(true), 2);
    }

}
