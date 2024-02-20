<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use SimpleXMLElement;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Throwable;

/**
 * Entidade Cte.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"cte","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"cte"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/cte/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "put"={"path"="/fis/cte/{id}", "security"="is_granted('ROLE_FISCAL')"},
 *          "delete"={"path"="/fis/cte/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/cte", "security"="is_granted('ROLE_FISCAL')"},
 *          "post"={"path"="/fis/cte", "security"="is_granted('ROLE_FISCAL')"}
 *     },
 *
 *     attributes={
 *          "pagination_items_per_page"=10,
 *          "formats"={"jsonld", "csv"={"text/csv"}}
 *     }
 * )
 * @ApiFilter(PropertyFilter::class)
 *
 * @ApiFilter(SearchFilter::class, properties={
 *     "documentoEmitente": "exact",
 *     "xNomeEmitente": "partial",
 *     "documentoDestinatario": "exact",
 *     "xNomeDestinatario": "partial",
 *     "chaveAcesso": "exact",
 *     "numero": "exact",
 *     "id": "exact"
 * })
 *
 * @ApiFilter(DateFilter::class, properties={"dtEmissao"})
 *
 * @ApiFilter(OrderFilter::class, properties={
 *     "id",
 *     "numero",
 *     "documentoDestinatario",
 *     "dtEmissao",
 *     "updated"
 * }, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\CteEntityHandler")
 * *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\CteRepository")
 * @ORM\Table(name="fis_cte")
 *
 * @author Carlos Eduardo Pauluk
 */
class Cte implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="uuid", type="string", nullable=true, length=32)
     * @var null|string
     * @NotUppercase()
     * @Groups("cte")
     */
    public ?string $uuid = null;

    /**
     * @ORM\Column(name="cct", type="string", nullable=true, length=8)
     * @Groups("cte")
     * @var null|string
     */
    public ?string $cct = null;

    /**
     * @ORM\Column(name="natureza_operacao", type="string", nullable=true, length=60)
     * @Groups("cte")
     * @var null|string
     */
    public ?string $naturezaOperacao = null;


    /**
     * @ORM\Column(name="chave_acesso", type="string", nullable=true, length=44)
     * @Groups("cte")
     * @var null|string
     */
    public ?string $chaveAcesso = null;

    /**
     * @ORM\Column(name="dt_emissao", type="datetime", nullable=true)
     * @var null|DateTime
     * @Groups("cte")
     */
    public ?DateTime $dtEmissao = null;

    /**
     * @ORM\Column(name="ambiente", type="string", nullable=true, length=4)
     * @Groups("cte")
     * @var null|string
     */
    public ?string $ambiente = null;

    /**
     * @ORM\Column(name="numero", type="integer", nullable=false)
     * @var null|int
     * @Groups("cte")
     */
    public ?int $numero = null;

    /**
     * @ORM\Column(name="xml", type="string", nullable=true)
     * @var null|string
     * @NotUppercase()
     */
    private ?string $xml = null;

    /**
     * @ORM\Column(name="documento_emitente", type="string", nullable=false)
     * @var null|string
     * @Groups("cte")
     */
    public ?string $documentoEmitente = null;

    /**
     * @ORM\Column(name="xnome_emitente", type="string", nullable=false)
     * @var null|string
     * @Groups("cte")
     */
    public ?string $xNomeEmitente = null;

    /**
     * @ORM\Column(name="inscr_est_emitente", type="string", nullable=true)
     * @var null|string
     * @Groups("cte")
     */
    public ?string $inscricaoEstadualEmitente = null;

    /**
     * @ORM\Column(name="cep_emitente", type="string", nullable=true, length=9)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $cepEmitente = null;

    /**
     * @ORM\Column(name="logradouro_emitente", type="string", nullable=true, length=200)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $logradouroEmitente = null;

    /**
     * @ORM\Column(name="numero_emitente", type="string", nullable=true, length=200)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $numeroEmitente = null;

    /**
     * @ORM\Column(name="complemento_emitente", type="string", nullable=true, length=60)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $complementoEmitente = null;

    /**
     * @ORM\Column(name="fone_emitente", type="string", nullable=true, length=50)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $foneEmitente = null;

    /**
     * @ORM\Column(name="bairro_emitente", type="string", nullable=true, length=120)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $bairroEmitente = null;

    /**
     * @ORM\Column(name="cidade_emitente", type="string", nullable=true, length=120)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $cidadeEmitente = null;

    /**
     * @ORM\Column(name="estado_emitente", type="string", nullable=true, length=2)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $estadoEmitente = null;

    /**
     * @ORM\Column(name="documento_destinatario", type="string", nullable=false)
     * @var null|string
     * @Groups("cte")
     */
    public ?string $documentoDestinatario = null;

    /**
     * @ORM\Column(name="xnome_destinatario", type="string", nullable=false)
     * @var null|string
     * @Groups("cte")
     */
    public ?string $xNomeDestinatario = null;

    /**
     * @ORM\Column(name="inscr_est", type="string", nullable=false)
     * @var null|string
     * @Groups("cte")
     */
    public ?string $inscricaoEstadualDestinatario = null;

    /**
     * @ORM\Column(name="logradouro_destinatario", type="string", nullable=true, length=200)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $logradouroDestinatario = null;

    /**
     * @ORM\Column(name="numero_destinatario", type="string", nullable=true, length=200)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $numeroDestinatario = null;

    /**
     * @ORM\Column(name="complemento_destinatario", type="string", nullable=true, length=60)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $complementoDestinatario = null;

    /**
     * @ORM\Column(name="bairro_destinatario", type="string", nullable=true, length=120)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $bairroDestinatario = null;

    /**
     * @ORM\Column(name="cidade_destinatario", type="string", nullable=true, length=120)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $cidadeDestinatario = null;

    /**
     * @ORM\Column(name="estado_destinatario", type="string", nullable=true, length=2)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $estadoDestinatario = null;

    /**
     * @ORM\Column(name="cep_destinatario", type="string", nullable=true, length=9)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $cepDestinatario = null;

    /**
     * @ORM\Column(name="fone_destinatario", type="string", nullable=true, length=50)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $foneDestinatario = null;

    /**
     * @ORM\Column(name="email_destinatario", type="string", nullable=true, length=200)
     * @var string|null
     * @Groups("cte")
     */
    public ?string $emailDestinatario = null;


    /**
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("cte")
     */
    public ?array $jsonData = null;


    /**
     * @return SimpleXMLElement|null
     */
    public function getXMLDecoded(): ?SimpleXMLElement
    {
        $xmlDecodedAsString = $this->getXMLDecodedAsString();
        if ($xmlDecodedAsString) {
            $r = simplexml_load_string($xmlDecodedAsString);
            return ($r === null || $r instanceof SimpleXMLElement) ? $r : null;
        } else {
            return null;
        }
    }


    /**
     * @Groups("cte")
     * @return bool
     */
    public function isPossuiXml(): bool
    {
        return $this->xml !== null;
    }


    /**
     * @Groups("cte")
     * @return bool
     */
    public function isPossuiXmlAssinado(): bool
    {
        return $this->xml !== null && $this->getXMLDecoded()->getName() === 'nfeProc';
    }


    /**
     * @return string|null
     */
    public function getXMLDecodedAsString(): ?string
    {
        if ($this->xml) {
            try {
                // No PHP 7.4.25 não estava gerando exceção, apenas warning e retornando vazio
                $decoded = @gzdecode(@base64_decode($this->xml));
                return $decoded ?: $this->xml;
            } catch (Throwable $e) {
                // Caso não tenha conseguido decodificar...
                return $this->xml;
            }
        } else {
            return null;
        }

    }


    /**
     * @return null|string
     */
    public function getXml(): ?string
    {
        return $this->xml;
    }


    /**
     * @param null|string $xml
     * @return cte
     * @noinspection PhpUndefinedFieldInspection
     */
    public function setXml(?string $xml): cte
    {
        $this->xml = $xml;
        // força o reload nos transients.
        $this->xmlDecoded = null;
        $this->xmlDecodedAsString = null;
        return $this;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("cte")
     * @SerializedName("valorTotal")
     * @return float
     */
    public function getValorTotalFormatted(): float
    {
        return (float)$this->valorTotal;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("cte")
     * @SerializedName("valorTotal")
     * @param float $valorTotal
     */
    public function setValorTotalFormatted(float $valorTotal)
    {
        $this->valorTotal = $valorTotal;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("cte")
     * @SerializedName("transpPesoBruto")
     * @return float
     */
    public function getTranspPesoBrutoFormatted(): float
    {
        return (float)$this->transpPesoBruto;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("cte")
     * @SerializedName("transpPesoBruto")
     * @param float $transpPesoBruto
     */
    public function setTranspPesoBrutoFormatted(float $transpPesoBruto)
    {
        $this->transpPesoBruto = $transpPesoBruto;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("cte")
     * @SerializedName("transpPesoLiquido")
     * @return float
     */
    public function getTranspPesoLiquidoFormatted(): float
    {
        return (float)$this->transpPesoLiquido;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("cte")
     * @SerializedName("transpPesoLiquido")
     * @param float $transpPesoLiquido
     */
    public function setTranspPesoLiquidoFormatted(float $transpPesoLiquido)
    {
        $this->transpPesoLiquido = $transpPesoLiquido;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("cte")
     * @SerializedName("transpQtdeVolumes")
     * @return float
     */
    public function getTranspQtdeVolumesFormatted(): float
    {
        return (float)$this->transpQtdeVolumes;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("cte")
     * @SerializedName("transpQtdeVolumes")
     * @param float $transpQtdeVolumes
     */
    public function setTranspQtdeVolumesFormatted(float $transpQtdeVolumes)
    {
        $this->transpQtdeVolumes = $transpQtdeVolumes;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("cte")
     * @SerializedName("transpValorTotalFrete")
     * @return float
     */
    public function getTranspValorTotalFreteFormatted(): float
    {
        return (float)$this->transpValorTotalFrete;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("cte")
     * @SerializedName("transpValorTotalFrete")
     * @param float $transpValorTotalFrete
     */
    public function setTranspValorTotalFreteFormatted(float $transpValorTotalFrete)
    {
        $this->transpValorTotalFrete = $transpValorTotalFrete;
    }


    /**
     * @Groups("cte")
     */
    public function getEmitenteCompleto(): string
    {
        return StringUtils::mascararCnpjCpf($this->documentoEmitente) . ' - ' . $this->xNomeEmitente;
    }


}
