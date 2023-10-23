<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

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
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use SimpleXMLElement;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

/**
 * Entidade Nota Fiscal.
 *
 * @ApiResource(
 *     normalizationContext={"groups"={"notaFiscal","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"notaFiscal"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/fis/notaFiscal/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/fis/notaFiscal/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/fis/notaFiscal/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/fis/notaFiscal", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/fis/notaFiscal", "security"="is_granted('ROLE_FINAN')"}
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
 *     "serie": "exact",
 *     "cidadeEmitente": "partial",
 *     "cidadeDestinatario": "partial",
 *     "valorTotal": "exact",
 *     "nsu": "exact",
 *     "manifestDest": "exact",
 *     "entradaSaida": "exact",
 *     "id": "exact"
 * })
 *
 * @ApiFilter(DateFilter::class, properties={"dtEmissao"})
 *
 * @ApiFilter(BooleanFilter::class, properties={"resumo"})
 *
 * ApiFilter(NotLikeFilter::class, properties={"documentoDestinatario"})
 *
 * @ApiFilter(OrderFilter::class, properties={
 *     "id",
 *     "numero",
 *     "documentoDestinatario",
 *     "valorTotal",
 *     "dtEmissao",
 *     "updated",
 *     "cStat"
 * }, arguments={"orderParameterName"="order"})
 *
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\Fiscal\NotaFiscalEntityHandler")
 *
 *
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalRepository")
 * @ORM\Table(name="fis_nf")
 *
 * @author Carlos Eduardo Pauluk
 */
class NotaFiscal implements EntityId
{

    use EntityIdTrait;

    /**
     * @ORM\Column(name="uuid", type="string", nullable=true, length=32)
     * @var null|string
     * @NotUppercase()
     * @Groups("notaFiscal")
     */
    public ?string $uuid = null;

    /**
     * @ORM\Column(name="ambiente", type="string", nullable=true, length=4)
     * @Groups("notaFiscal")
     * @var null|string
     */
    public ?string $ambiente = null;

    /**
     * Número randômico utilizado na geração do nome do arquivo XML, para poder saber qual foi o nome do último arquivo gerado, evitando duplicidades.
     * @ORM\Column(name="rand_faturam", type="string", nullable=true)
     * @var null|string
     */
    public ?string $randFaturam = null;


    /**
     * $cNF = rand(10000000, 99999999);
     * @ORM\Column(name="cnf", type="string", nullable=true, length=8)
     * @Groups("notaFiscal")
     * @var null|string
     */
    public ?string $cnf = null;

    /**
     * @ORM\Column(name="natureza_operacao", type="string", nullable=true, length=60)
     * @Groups("notaFiscal")
     * @var null|string
     */
    public ?string $naturezaOperacao = null;

    /**
     * @ORM\Column(name="finalidade_nf", type="string", nullable=false, length=30)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $finalidadeNf = null;

    /**
     * @ORM\Column(name="chave_acesso", type="string", nullable=true, length=44)
     * @Groups("notaFiscal")
     * @var null|string
     */
    public ?string $chaveAcesso = null;

    /**
     * @ORM\Column(name="protocolo_autoriz", type="string", nullable=true, length=255)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $protocoloAutorizacao = null;

    /**
     * @ORM\Column(name="dt_protocolo_autoriz", type="datetime", nullable=true)
     * @var null|DateTime
     * @Groups("notaFiscal")
     */
    public ?DateTime $dtProtocoloAutorizacao = null;

    /**
     * @ORM\Column(name="dt_emissao", type="datetime", nullable=true)
     * @var null|DateTime
     * @Groups("notaFiscal")
     */
    public ?DateTime $dtEmissao = null;

    /**
     * @ORM\Column(name="dt_saient", type="datetime", nullable=true)
     * @var null|DateTime
     * @Groups("notaFiscal")
     */
    public ?DateTime $dtSaiEnt = null;

    /**
     * @ORM\Column(name="numero", type="integer", nullable=false)
     * @var null|int
     * @Groups("notaFiscal")
     */
    public ?int $numero = null;

    /**
     * @ORM\Column(name="serie", type="integer", nullable=false)
     * @var null|int
     * @Groups("notaFiscal")
     */
    public ?int $serie = null;

    /**
     * @ORM\Column(name="tipo", type="string", nullable=true, length=30)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $tipoNotaFiscal = null;

    /**
     * @ORM\Column(name="entrada_saida", nullable=false)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $entradaSaida = null;

    /**
     * @ORM\Column(name="documento_emitente", type="string", nullable=false)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $documentoEmitente = null;

    /**
     * @ORM\Column(name="xnome_emitente", type="string", nullable=false)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $xNomeEmitente = null;

    /**
     * @ORM\Column(name="inscr_est_emitente", type="string", nullable=true)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $inscricaoEstadualEmitente = null;

    /**
     * @ORM\Column(name="cep_emitente", type="string", nullable=true, length=9)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $cepEmitente = null;

    /**
     * @ORM\Column(name="logradouro_emitente", type="string", nullable=true, length=200)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $logradouroEmitente = null;

    /**
     * @ORM\Column(name="numero_emitente", type="string", nullable=true, length=200)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $numeroEmitente = null;

    /**
     * @ORM\Column(name="complemento_emitente", type="string", nullable=true, length=60)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $complementoEmitente = null;

    /**
     * @ORM\Column(name="fone_emitente", type="string", nullable=true, length=50)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $foneEmitente = null;

    /**
     * @ORM\Column(name="bairro_emitente", type="string", nullable=true, length=120)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $bairroEmitente = null;

    /**
     * @ORM\Column(name="cidade_emitente", type="string", nullable=true, length=120)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $cidadeEmitente = null;

    /**
     * @ORM\Column(name="estado_emitente", type="string", nullable=true, length=2)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $estadoEmitente = null;

    /**
     * @ORM\Column(name="documento_destinatario", type="string", nullable=false)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $documentoDestinatario = null;

    /**
     * @ORM\Column(name="xnome_destinatario", type="string", nullable=false)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $xNomeDestinatario = null;

    /**
     * @ORM\Column(name="inscr_est", type="string", nullable=false)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $inscricaoEstadualDestinatario = null;

    /**
     * @ORM\Column(name="logradouro_destinatario", type="string", nullable=true, length=200)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $logradouroDestinatario = null;

    /**
     * @ORM\Column(name="numero_destinatario", type="string", nullable=true, length=200)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $numeroDestinatario = null;

    /**
     * @ORM\Column(name="complemento_destinatario", type="string", nullable=true, length=60)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $complementoDestinatario = null;

    /**
     * @ORM\Column(name="bairro_destinatario", type="string", nullable=true, length=120)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $bairroDestinatario = null;

    /**
     * @ORM\Column(name="cidade_destinatario", type="string", nullable=true, length=120)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $cidadeDestinatario = null;

    /**
     * @ORM\Column(name="estado_destinatario", type="string", nullable=true, length=2)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $estadoDestinatario = null;

    /**
     * @ORM\Column(name="cep_destinatario", type="string", nullable=true, length=9)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $cepDestinatario = null;

    /**
     * @ORM\Column(name="fone_destinatario", type="string", nullable=true, length=50)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $foneDestinatario = null;

    /**
     * @ORM\Column(name="email_destinatario", type="string", nullable=true, length=200)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $emailDestinatario = null;

    /**
     * @ORM\Column(name="motivo_cancelamento", type="string", nullable=true, length=3000)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $motivoCancelamento = null;

    /**
     * @ORM\Column(name="info_compl", type="string", nullable=true, length=3000)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $infoCompl = null;

    /**
     * @ORM\Column(name="total_descontos", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $totalDescontos = null;

    /**
     * @ORM\Column(name="subtotal", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $subtotal = null;

    /**
     * @ORM\Column(name="valor_total", type="decimal", nullable=false, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $valorTotal = null;

    /**
     * @ORM\Column(name="transp_documento", type="string", nullable=false)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $transpDocumento = null;

    /**
     * @ORM\Column(name="transp_nome", type="string", nullable=false)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $transpNome = null;

    /**
     * @ORM\Column(name="transp_inscr_est", type="string", nullable=true)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $transpInscricaoEstadual = null;

    /**
     * @ORM\Column(name="transp_endereco", type="string", nullable=true)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $transpEndereco = null;

    /**
     * @ORM\Column(name="transp_cidade", type="string", nullable=true, length=120)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $transpCidade = null;

    /**
     * @ORM\Column(name="transp_estado", type="string", nullable=true, length=2)
     * @var string|null
     * @Groups("notaFiscal")
     */
    public ?string $transpEstado = null;

    /**
     * @ORM\Column(name="transp_especie_volumes", type="string", nullable=true, length=200)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $transpEspecieVolumes = null;

    /**
     * @ORM\Column(name="transp_marca_volumes", type="string", nullable=true, length=200)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $transpMarcaVolumes = null;

    /**
     * @ORM\Column(name="transp_modalidade_frete", type="string", nullable=false, length=30)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $transpModalidadeFrete = null;

    /**
     * @ORM\Column(name="transp_numeracao_volumes", type="string", nullable=true, length=200)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $transpNumeracaoVolumes = null;

    /**
     * @ORM\Column(name="transp_peso_bruto", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $transpPesoBruto = null;

    /**
     * @ORM\Column(name="transp_peso_liquido", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $transpPesoLiquido = null;

    /**
     * @ORM\Column(name="transp_qtde_volumes", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $transpQtdeVolumes = null;

    /**
     * @ORM\Column(name="transp_valor_total_frete", type="decimal", nullable=true, precision=15, scale=2)
     * @Groups("N")
     * @Assert\Type(type="string")
     */
    public ?string $transpValorTotalFrete = null;

    /**
     * @ORM\Column(name="indicador_forma_pagto", type="string", nullable=false, length=30)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $indicadorFormaPagto = null;

    /**
     * @ORM\Column(name="a03id_nf_referenciada", type="string", nullable=true, length=100)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $a03idNfReferenciada = null;

    /**
     * @ORM\Column(name="xml_nota", type="string", nullable=true)
     * @var null|string
     * @NotUppercase()
     */
    private ?string $xmlNota = null;

    /**
     * Informa se o XML é de um resumo <resNFe> (ainda não foi baixada o XML da nota completa).
     * @ORM\Column(name="resumo", type="boolean", nullable=true)
     * @var null|bool
     * @Groups("notaFiscal")
     */
    public ?bool $resumo = null;

    /**
     * @ORM\Column(name="nrec", type="string", length=30, nullable=true)
     * @var null|string
     */
    public ?string $nRec = null;

    /**
     * @ORM\Column(name="cstat_lote", type="integer", nullable=true)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $cStatLote = null;

    /**
     * @ORM\Column(name="xmotivo_lote", type="string", length=255, nullable=true)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $xMotivoLote = null;

    /**
     * @ORM\Column(name="cstat", type="integer", nullable=true)
     * @var null|int
     * @Groups("notaFiscal")
     */
    public ?int $cStat = null;

    /**
     * @ORM\Column(name="xmotivo", type="string", length=255, nullable=true)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $xMotivo = null;

    /**
     * @ORM\Column(name="manifest_dest", type="string", length=255, nullable=true)
     * @var null|string
     * @Groups("notaFiscal")
     */
    public ?string $manifestDest = null;

    /**
     * Informa quando foi alterado o status do último $manifestDest.
     * @ORM\Column(name="dt_manifest_dest", type="datetime", nullable=true)
     * @var null|DateTime
     * @Groups("notaFiscal")
     */
    public ?DateTime $dtManifestDest = null;

    /**
     * @ORM\Column(name="nsu", type="integer", nullable=false)
     * @var null|int
     * @Groups("notaFiscal")
     */
    public ?int $nsu = null;

    /**
     * @ORM\Column(name="json_data", type="json")
     * @var null|array
     * @NotUppercase()
     * @Groups("notaFiscal")
     */
    public ?array $jsonData = null;

    /**
     * Transient (ver getters e setters).
     * @var int|null
     * @Groups("notaFiscal")
     */
    private ?int $idDest = null;

    /**
     * @var NotaFiscalItem[]|ArrayCollection
     * @ORM\OneToMany(
     *      targetEntity="NotaFiscalItem",
     *      mappedBy="notaFiscal"
     * )
     * @ORM\OrderBy({"ordem" = "ASC"})
     */
    public $itens;

    /**
     * @var NotaFiscalEvento[]|ArrayCollection
     * @ORM\OneToMany(
     *      targetEntity="NotaFiscalEvento",
     *      cascade={"all"},
     *      mappedBy="notaFiscal",
     *      orphanRemoval=true
     * )
     */
    public $eventos;

    /**
     * @var NotaFiscalCartaCorrecao[]|ArrayCollection
     * @ORM\OneToMany(
     *      targetEntity="NotaFiscalCartaCorrecao",
     *      cascade={"all"},
     *      mappedBy="notaFiscal",
     *      orphanRemoval=true
     * )
     */
    public $cartasCorrecao;

    /**
     * @var NotaFiscalHistorico[]|ArrayCollection
     * @ORM\OneToMany(
     *      targetEntity="NotaFiscalHistorico",
     *      cascade={"all"},
     *      mappedBy="notaFiscal",
     *      orphanRemoval=true
     * )
     */
    public $historicos;


    public function __construct()
    {
        $this->itens = new ArrayCollection();
        $this->eventos = new ArrayCollection();
        $this->historicos = new ArrayCollection();
    }


    /**
     * @return NotaFiscalItem[]|ArrayCollection
     */
    public function getItens()
    {
        return $this->itens;
    }


    /**
     * @param NotaFiscalItem[]|ArrayCollection $itens
     * @return NotaFiscal
     */
    public function setItens($itens): NotaFiscal
    {
        $this->itens = $itens;
        return $this;
    }


    public function deleteAllItens(): NotaFiscal
    {
        if ($this->itens) {
            foreach ($this->itens as $item) {
                $item->notaFiscal = null;
            }
            $this->itens->clear();
        }
        return $this;
    }


    /**
     * @return NotaFiscalEvento[]|ArrayCollection
     */
    public function getEventos()
    {
        return $this->eventos;
    }


    /**
     * @param NotaFiscalEvento[]|ArrayCollection $eventos
     * @return NotaFiscal
     */
    public function setEventos($eventos): NotaFiscal
    {
        $this->eventos = $eventos;
        return $this;
    }


    /**
     * @param NotaFiscalItem $item
     */
    public function addItem(NotaFiscalItem $item): void
    {
        if (!$this->itens->contains($item)) {
            $this->itens->add($item);
            $item->notaFiscal = $this;
        }
    }


    /**
     * @return NotaFiscalCartaCorrecao[]|ArrayCollection
     */
    public function getCartasCorrecao()
    {
        return $this->cartasCorrecao;
    }


    /**
     * @param NotaFiscalCartaCorrecao[]|ArrayCollection $cartaCorrecaos
     * @return NotaFiscal
     */
    public function setCartasCorrecao($cartaCorrecaos): NotaFiscal
    {
        $this->cartasCorrecao = $cartaCorrecaos;
        return $this;
    }


    /**
     * @param NotaFiscalCartaCorrecao $cartaCorrecao
     */
    public function addCartaCorrecao(NotaFiscalCartaCorrecao $cartaCorrecao): void
    {
        if (!$this->cartasCorrecao->contains($cartaCorrecao)) {
            $this->cartasCorrecao->add($cartaCorrecao);
        }
    }


    /**
     * @return NotaFiscalHistorico[]|ArrayCollection
     */
    public function getHistoricos()
    {
        return $this->historicos;
    }


    /**
     * @param NotaFiscalHistorico[]|ArrayCollection $historicos
     * @return NotaFiscal
     */
    public function setHistoricos($historicos): NotaFiscal
    {
        $this->historicos = $historicos;
        return $this;
    }


    /**
     * @param NotaFiscalHistorico $historico
     */
    public function addHistorico(NotaFiscalHistorico $historico): void
    {
        if (!$this->historicos->contains($historico)) {
            $this->historicos->add($historico);
        }
    }


    /**
     * @return string
     * @SerializedName("infoStatus")
     * @Groups("notaFiscal")
     */
    public function getInfoStatus(): string
    {
        $infoStatus = '';
        if ($this->cStat) {
            $infoStatus .= $this->cStat . ' - ' . $this->xMotivo;
        }
        if ($this->cStatLote) {
            $infoStatus .= ' (' . $this->cStatLote . ' - ' . $this->xMotivoLote . ')';
        }
        if ($this->ambiente === 'HOM') {
            $infoStatus .= ' *** EMITIDA EM HOMOLOGAÇÃO';
        }
        return $infoStatus ?: 'SEM STATUS';
    }


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
     * @Groups("notaFiscal")
     * @return bool
     */
    public function isPossuiXml(): bool
    {
        return $this->xmlNota !== null;
    }


    /**
     * @Groups("notaFiscal")
     * @return bool
     */
    public function isPossuiXmlAssinado(): bool
    {
        return $this->xmlNota !== null && $this->getXMLDecoded()->getName() === 'nfeProc';
    }


    /**
     * @return string|null
     */
    public function getXMLDecodedAsString(): ?string
    {
        if ($this->xmlNota) {
            try {
                // No PHP 7.4.25 não estava gerando exceção, apenas warning e retornando vazio
                $decoded = @gzdecode(@base64_decode($this->xmlNota));
                return $decoded ?: $this->xmlNota;
            } catch (Throwable $e) {
                // Caso não tenha conseguido decodificar...
                return $this->xmlNota;
            }
        } else {
            return null;
        }

    }


    /**
     * @return null|string
     */
    public function getXmlNota(): ?string
    {
        return $this->xmlNota;
    }


    /**
     * @param null|string $xmlNota
     * @return NotaFiscal
     * @noinspection PhpUndefinedFieldInspection
     */
    public function setXmlNota(?string $xmlNota): NotaFiscal
    {
        $this->xmlNota = $xmlNota;
        // força o reload nos transients.
        $this->xmlDecoded = null;
        $this->xmlDecodedAsString = null;
        return $this;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("totalDescontos")
     * @return float
     */
    public function getTotalDescontosFormatted(): float
    {
        return (float)$this->totalDescontos;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("totalDescontos")
     * @param float $totalDescontos
     */
    public function setTotalDescontosFormatted(float $totalDescontos)
    {
        $this->totalDescontos = $totalDescontos;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("subtotal")
     * @return float
     */
    public function getSubtotalFormatted(): float
    {
        return (float)$this->subtotal;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("subtotal")
     * @param float $subtotal
     */
    public function setSubtotalFormatted(float $subtotal)
    {
        $this->subtotal = $subtotal;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("valorTotal")
     * @return float
     */
    public function getValorTotalFormatted(): float
    {
        return (float)$this->valorTotal;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("valorTotal")
     * @param float $valorTotal
     */
    public function setValorTotalFormatted(float $valorTotal)
    {
        $this->valorTotal = $valorTotal;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("transpPesoBruto")
     * @return float
     */
    public function getTranspPesoBrutoFormatted(): float
    {
        return (float)$this->transpPesoBruto;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("transpPesoBruto")
     * @param float $transpPesoBruto
     */
    public function setTranspPesoBrutoFormatted(float $transpPesoBruto)
    {
        $this->transpPesoBruto = $transpPesoBruto;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("transpPesoLiquido")
     * @return float
     */
    public function getTranspPesoLiquidoFormatted(): float
    {
        return (float)$this->transpPesoLiquido;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("transpPesoLiquido")
     * @param float $transpPesoLiquido
     */
    public function setTranspPesoLiquidoFormatted(float $transpPesoLiquido)
    {
        $this->transpPesoLiquido = $transpPesoLiquido;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("transpQtdeVolumes")
     * @return float
     */
    public function getTranspQtdeVolumesFormatted(): float
    {
        return (float)$this->transpQtdeVolumes;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("transpQtdeVolumes")
     * @param float $transpQtdeVolumes
     */
    public function setTranspQtdeVolumesFormatted(float $transpQtdeVolumes)
    {
        $this->transpQtdeVolumes = $transpQtdeVolumes;
    }


    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("transpValorTotalFrete")
     * @return float
     */
    public function getTranspValorTotalFreteFormatted(): float
    {
        return (float)$this->transpValorTotalFrete;
    }

    /**
     * Para aceitar tanto em string quanto em double.
     * @Groups("notaFiscal")
     * @SerializedName("transpValorTotalFrete")
     * @param float $transpValorTotalFrete
     */
    public function setTranspValorTotalFreteFormatted(float $transpValorTotalFrete)
    {
        $this->transpValorTotalFrete = $transpValorTotalFrete;
    }

    /**
     * @Groups("notaFiscal")
     */
    public function getDadosDuplicatas(): array
    {
        if (
            $this->getXMLDecoded() &&
            $this->getXMLDecoded()->NFe &&
            $this->getXMLDecoded()->NFe->infNFe &&
            $this->getXMLDecoded()->NFe->infNFe->cobr &&
            $this->getXMLDecoded()->NFe->infNFe->cobr->dup
        ) {
            $duplicatas = [];
            foreach ($this->getXMLDecoded()->NFe->infNFe->cobr->dup as $dup) {
                $duplicatas[] = [
                    'numero' => (string)$dup->nDup,
                    'vencimento' => (string)$dup->dVenc,
                    'valor' => (string)$dup->vDup,
                ];
            }
            return $duplicatas;
        } else {
            return [];
        }
    }

    /**
     * @Groups("notaFiscal")
     */
    public function getEmitenteCompleto(): string
    {
        return StringUtils::mascararCnpjCpf($this->documentoEmitente) . ' - ' . $this->xNomeEmitente;
    }

    /**
     * @Groups("notaFiscal")
     */
    public function isNossaEmissao(): bool
    {
        return in_array($this->documentoEmitente, explode(',', $_SERVER['FISCAL_EMITENTES']), true);
    }

    public function getIdDest(): ?int
    {
        $idDest = $this->jsonData['idDest'] ?? null;

        if (
            !$idDest && (
                $this->getXMLDecoded() &&
                $this->getXMLDecoded()->NFe &&
                $this->getXMLDecoded()->NFe->infNFe &&
                $this->getXMLDecoded()->NFe->infNFe->ide &&
                $this->getXMLDecoded()->NFe->infNFe->ide->idDest)
        ) {
            $idDest = $this->getXMLDecoded()->NFe->infNFe->ide->idDest->__toString();
        }
        $this->idDest = (int)$idDest;
        return $this->idDest;
    }

    public function setIdDest(?int $idDest): ?int
    {
        $this->jsonData['idDest'] = $idDest;
        $this->idDest = $idDest;
        return $this->idDest;
    }

    /**
     * @Groups("notaFiscal")
     */
    public function isPermiteSalvar(): bool
    {
        $this->msgPermiteSalvar = 'Sim';
        if (!$this->getId()) {
            return true;
        }
        if ($this->isNossaEmissao()) {
            if (substr($this->cStat, 0, 1) !== '1') {
                return true;
            }
        }
        return false;
    }

    /**
     * @Groups("notaFiscal")
     */
    public function getMsgPermiteSalvar(): string
    {
        if (!$this->getId()) {
            return 'Sim';
        }
        if ($this->isNossaEmissao()) {
            if (substr($this->cStat, 0, 1) !== '1') {
                return 'Sim';
            }
        }
        return 'Não';
    }

    /**
     * @Groups("notaFiscal")
     */
    public function isPermiteReimpressao(): bool
    {
        if ($this->getId()) {
            if (in_array((int)$this->cStat, [100, 204, 135], true)) {
                return true;
            }
            // else
            if ($this->cStat == 0 && strpos($this->xMotivo, 'DUPLICIDADE DE NF') !== FALSE) {
                return true;
            }
            // else
            if ($this->getXMLDecoded() && $this->getXMLDecoded()->getName() === 'nfeProc') {
                return true;
            }
        }
        return false;
    }

    /**
     * @Groups("notaFiscal")
     */
    public function getMsgPermiteReimpressao(): string
    {
        if ($this->getId()) {
            if (in_array((int)$this->cStat, [100, 204, 135], true)) {
                return 'Sim (cStat em 100, 204 ou 135)';
            }
            // else
            if ($this->cStat == 0 && strpos($this->xMotivo, 'DUPLICIDADE DE NF') !== FALSE) {
                return 'Sim (cStat em 0 e motivo "DUPLICIDADE DE NF")';
            }
            // else
            if ($this->getXMLDecoded() && $this->getXMLDecoded()->getName() === 'nfeProc') {
                return 'Sim (com nfeProc)';
            }
        }
        return 'Não';
    }

    /**
     * @Groups("notaFiscal")
     */
    public function isPermiteReimpressaoCancelamento(): bool
    {
        return $this->isNossaEmissao() && ($this->getId() && $this->cStatLote == 101);
    }

    /**
     * @Groups("notaFiscal")
     */
    public function getMsgPermiteReimpressaoCancelamento(): string
    {
        if ($this->getId() && $this->cStatLote == 101) {
            return 'Sim (cStatLote em 101)';
        }
        return 'Não';
    }

    /**
     * @Groups("notaFiscal")
     */
    public function isPermiteCancelamento(): bool
    {
        return $this->isNossaEmissao() && ($this->getId() && (int)$this->cStat === 100);
    }

    /**
     * @Groups("notaFiscal")
     */
    public function getMsgPermiteCancelamento(): string
    {
        if ($this->getId() && (int)$this->cStat === 100) {
            return 'Sim (cStat em 100)';
        }
        return 'Não';
    }

    /**
     * @Groups("notaFiscal")
     */
    public function isPermiteCartaCorrecao(): bool
    {
        return $this->isNossaEmissao() && ($this->getId() && (int)$this->cStat === 100);
    }

    /**
     * @Groups("notaFiscal")
     */
    public function getMsgPermiteCartaCorrecao(): string
    {
        if ($this->getId() && (int)$this->cStat === 100) {
            return 'Sim (cStat em 100)';
        }
        return 'Não';
    }

    /**
     * @Groups("notaFiscal")
     * @var string
     */
    public function getMsgPermiteFaturamento(): string
    {
        return $this->jsonData['msgPermiteFaturamento'] ?? false;
    }

    /**
     * @Groups("notaFiscal")
     * @var bool
     */
    public function isPermiteFaturamento(): bool
    {
        // verificar o beforeSave() para entender o motivo
        // é lá também onde é setado o msgPermiteFaturamento
        return $this->isNossaEmissao() && ($this->jsonData['permiteFaturamento'] ?? false);
    }

    /**
     * @Groups("notaFiscal")
     */
    public function getVendaId(): ?int
    {
        return $this->jsonData['venda_id'] ?? null;
    }

    /**
     * @Groups("notaFiscal")
     */
    public function getFinFaturaId(): ?int
    {
        return $this->jsonData['fin_fatura_id'] ?? null;
    }

}
