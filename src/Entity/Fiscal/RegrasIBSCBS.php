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

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"regrasIbscbs","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"regrasIbscbs"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/regrasIbscbs/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "put"={"path"="/fis/regrasIbscbs/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "delete"={"path"="/fis/regrasIbscbs/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/regrasIbscbs", "security"="is_granted('ROLE_FISCAL')"},
 *          "post"={"path"="/fis/regrasIbscbs", "security"="is_granted('ROLE_FISCAL')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 *
 * @ApiFilter(PropertyFilter::class)
 * @ApiFilter(SearchFilter::class, properties={
 *     "descricao": "partial",
 *     "ncm": "exact",
 *     "cfop": "exact",
 *     "cst": "exact"
 * })
 * @ApiFilter(OrderFilter::class, properties={"id","descricao","updated"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\RegrasIBSCBSEntityHandler")
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\RegrasIBSCBSRepository")
 * @ORM\Table(name="fis_regras_ibscbs")
 */
class RegrasIBSCBS implements EntityId
{
    use EntityIdTrait;

    /** @ORM\Column(name="descricao", type="string", length=200, nullable=false) */
    public ?string $descricao = null;

    /** @ORM\Column(name="status", type="string", length=20, nullable=true) */
    public ?string $status = null;

    /** @ORM\Column(name="regime_crt", type="string", length=20, nullable=true) */
    public ?string $regimeCrt = null;

    /** @ORM\Column(name="modelo", type="string", length=10, nullable=true) */
    public ?string $modelo = null;

    /** @ORM\Column(name="operacao", type="string", length=20, nullable=true) */
    public ?string $operacao = null;

    /** @ORM\Column(name="uf_ori", type="string", length=2, nullable=true) */
    public ?string $ufOri = null;

    /** @ORM\Column(name="uf_des", type="string", length=2, nullable=true) */
    public ?string $ufDes = null;

    /** @ORM\Column(name="codmun_ori", type="integer", nullable=true) */
    public ?int $codmunOri = null;

    /** @ORM\Column(name="codmun_des", type="integer", nullable=true) */
    public ?int $codmunDes = null;

    /** @ORM\Column(name="ncm_grupo", type="string", length=20, nullable=true) */
    public ?string $ncmGrupo = null;

    /** @ORM\Column(name="ncm", type="integer", nullable=true) */
    public ?int $ncm = null;

    /** @ORM\Column(name="cfop", type="integer", nullable=true) */
    public ?int $cfop = null;

    /** @ORM\Column(name="cst", type="string", length=10, nullable=true) */
    public ?string $cst = null;

    /** @ORM\Column(name="cclasstrib", type="string", length=20, nullable=true) */
    public ?string $cClassTrib = null;

    /** @ORM\Column(name="aliq_ibs_est", type="decimal", precision=10, scale=4, nullable=true) */
    public ?string $aliqIbsEst = null;

    /** @ORM\Column(name="aliq_ibs_mun", type="decimal", precision=10, scale=4, nullable=true) */
    public ?string $aliqIbsMun = null;

    /** @ORM\Column(name="aliq_cbs_fed", type="decimal", precision=10, scale=4, nullable=true) */
    public ?string $aliqCbsFed = null;

    /** @ORM\Column(name="aliq_is_fed", type="decimal", precision=10, scale=4, nullable=true) */
    public ?string $aliqIsFed = null;

    /** @ORM\Column(name="tem_cred_ibs", type="boolean", nullable=true) */
    public ?bool $temCredIbs = null;

    /** @ORM\Column(name="tem_cred_cbs", type="boolean", nullable=true) */
    public ?bool $temCredCbs = null;

    /** @ORM\Column(name="prioridade", type="integer", nullable=true) */
    public ?int $prioridade = null;

    /** @ORM\Column(name="observacao", type="text", nullable=true) */
    public ?string $observacao = null;
}
