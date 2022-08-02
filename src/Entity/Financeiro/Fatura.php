<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Financeiro;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


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
 * @ApiFilter(SearchFilter::class, properties={"codigo": "exact", "descricao": "partial", "id": "exact", "fatura": "exact"})
 * @ApiFilter(OrderFilter::class, properties={"id", "descricao", "dtConsolidado", "updated"}, arguments={"orderParameterName"="order"})
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
     * Data em que a movimentação efetivamente aconteceu.
     *
     * @ORM\Column(name="dt_fatura", type="datetime")
     * @Groups("fatura")
     *
     * @var DateTime|null
     */
    public ?DateTime $dtFatura = null;

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
     * @ORM\Column(name="transacional", type="boolean")
     * @Groups("fatura")
     *
     * @var bool|null
     */
    public ?bool $transacional = false;

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


}

