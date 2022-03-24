<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\CRM;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Doctrine\Annotations\NotUppercase;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityId;
use CrosierSource\CrosierLibBaseBundle\Entity\EntityIdTrait;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"cliente","entityId"},"enable_max_depth"=true},
 *     denormalizationContext={"groups"={"cliente"},"enable_max_depth"=true},
 *
 *     itemOperations={
 *          "get"={"path"="/crm/cliente/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "put"={"path"="/crm/cliente/{id}", "security"="is_granted('ROLE_FINAN')"},
 *          "delete"={"path"="/crm/cliente/{id}", "security"="is_granted('ROLE_ADMIN')"}
 *     },
 *     collectionOperations={
 *          "get"={"path"="/crm/cliente", "security"="is_granted('ROLE_FINAN')"},
 *          "post"={"path"="/crm/cliente", "security"="is_granted('ROLE_FINAN')"}
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
 * @EntityHandler(entityHandlerClass="CrosierSource\CrosierLibRadxBundle\EntityHandler\CRM\ClienteEntityHandler")
 * @ORM\Entity(repositoryClass="CrosierSource\CrosierLibRadxBundle\Repository\CRM\ClienteRepository")
 * @ORM\Table(name="crm_cliente")
 *
 * @author Carlos Eduardo Pauluk
 */
class Cliente implements EntityId
{

    use EntityIdTrait;


    /**
     * @ORM\Column(name="nome", type="string", nullable="false", length=255)
     * @Groups("cliente")
     */
    public ?string $nome = null;


    /**
     * @ORM\Column(name="documento", type="string", nullable="true", length=14)
     * @Groups("cliente")
     */
    public ?string $documento = null;


    /**
     * PF/PJ
     * @ORM\Column(name="tipo_pessoa", type="string", nullable="true", length=2)
     * @Groups("cliente")
     */
    public ?string $tipoPessoa = null;


    /**
     * @ORM\Column(name="nome_fantasia", type="string", nullable="true")
     * @Groups("cliente")
     */
    public ?string $nomeFantasia = null;

    /**
     * @ORM\Column(name="ie", type="string", nullable="true", length=30)
     * @Groups("cliente")
     */
    public ?string $ie = null;

    /**
     * @ORM\Column(name="dt_nascimento", type="datetime", nullable=true)
     * @Groups("cliente")
     * @Assert\Type("\DateTime")
     * @Assert\NotNull()
     */
    public ?\DateTime $dtNascimento = null;

    /**
     * @ORM\Column(name="fone1", type="string", nullable=true, length=50)
     * @Groups("cliente")
     * @var string|null
     */
    public ?string $fone1 = null;

    /**
     * @ORM\Column(name="fone2", type="string", nullable=true, length=50)
     * @Groups("cliente")
     * @var string|null
     */
    public ?string $fone2 = null;

    /**
     * @ORM\Column(name="fone3", type="string", nullable=true, length=50)
     * @Groups("cliente")
     * @var string|null
     */
    public ?string $fone3 = null;

    /**
     * @ORM\Column(name="fone4", type="string", nullable=true, length=50)
     * @Groups("cliente")
     * @var string|null
     */
    public ?string $fone4 = null;

    /**
     * @ORM\Column(name="cep", type="string", nullable=true, length=8)
     * @var string|null
     * @Groups("cliente")
     */
    public ?string $cep = null;

    /**
     *
     * @ORM\Column(name="logradouro", type="string", nullable=true, length=100)
     * @var string|null
     * @Groups("cliente")
     */
    public ?string $logradouro = null;

    /**
     * @ORM\Column(name="numero", type="string", nullable=true, length=60)
     * @var string|null
     * @Groups("cliente")
     */
    public ?string $numero = null;

    /**
     * @ORM\Column(name="complemento", type="string", nullable=true, length=60)
     * @var string|null
     * @Groups("cliente")
     */
    public ?string $complemento = null;

    /**
     * @ORM\Column(name="bairro", type="string", nullable=true, length=60)
     * @var string|null
     * @Groups("cliente")
     */
    public ?string $bairro = null;

    /**
     * @ORM\Column(name="cidade", type="string", nullable=true, length=60)
     * @var string|null
     * @Groups("cliente")
     */
    public ?string $cidade = null;

    /**
     * @ORM\Column(name="estado", type="string", nullable=true, length=2)
     * @var string|null
     * @Groups("cliente")
     */
    public ?string $estado = null;

    /**
     * @ORM\Column(name="json_data", type="json")
     * @NotUppercase()
     * @Groups("cliente")
     */
    public ?array $jsonData = null;


    /**
     * @return string
     * @Groups("cliente")
     */
    public function getNomeMontadoComDocumento(): string
    {
        $r = StringUtils::mascararCnpjCpf($this->documento) . ' - ';
        if ($this->jsonData['nomeFantasia'] ?? false) {
            $r .= $this->nome . ' (' . $this->jsonData['nomeFantasia'] . ')';
        } else {
            $r .= $this->nome;
        }
        return $r;
    }


    /**
     * @return string
     * @Groups("cliente")
     */
    public function getIdDocumentoNome(): string
    {
        $r = '(' . str_pad($this->id, 7, '0', STR_PAD_LEFT) . ') ';

        if (!($this->documento ?: false)) {
            $r .= ' [DOCUMENTO_ND]';
        } else {
            $r .= StringUtils::mascararCnpjCpf($this->documento);
        }

        if ($this->tipoPessoa === 'PJ') {
            $r .= ' - ' . ($this->nomeFantasia ?: $this->nome);
        } else {
            $r .= ' - ' . $this->nome;
        }
        

        return $r;
    }


    /**
     * @param string $tipo
     * @return array|null
     */
    public function getEnderecoByTipo(string $tipo): ?array
    {
        $enderecos = $this->jsonData['enderecos'] ?? null;
        if ($enderecos) {
            foreach ($enderecos as $endereco) {
                if (strpos($endereco['tipo'], $tipo) !== FALSE) {
                    return $endereco;
                }
            }
        }
        return null;
    }


    /**
     * Insere somente se já não existir.
     *
     * @param array $novoEndereco
     */
    public function inserirNovoEndereco(array $novoEndereco)
    {
        // Verifica os endereços do cliente
        $enderecoJaSalvo = false;
        if (($this->jsonData['enderecos'] ?? false) && count($this->jsonData['enderecos']) > 0) {
            foreach ($this->jsonData['enderecos'] as $endereco) {
                if (
                    (($endereco['logradouro'] ?? '') === ($novoEndereco['logradouro'] ?? '')) &&
                    (($endereco['numero'] ?? '') === ($novoEndereco['numero'] ?? '')) &&
                    (($endereco['complemento'] ?? '') === ($novoEndereco['complemento'] ?? '')) &&
                    (($endereco['bairro'] ?? '') === ($novoEndereco['bairro'] ?? '')) &&
                    (($endereco['cep'] ?? '') === ($novoEndereco['cep'] ?? '')) &&
                    (($endereco['cidade'] ?? '') === ($novoEndereco['cidade'] ?? '')) &&
                    (($endereco['estado'] ?? '') === ($novoEndereco['estado'] ?? ''))) {
                    $enderecoJaSalvo = true;
                }
            }
        }
        if (!$enderecoJaSalvo) {
            if (!isset($this->jsonData['enderecos'])) {
                $this->jsonData['enderecos'] = [];
            }
            $this->jsonData['enderecos'][] = [
                'tipo' => $novoEndereco['tipo'] ?? '',
                'logradouro' => $novoEndereco['logradouro'] ?? '',
                'numero' => $novoEndereco['numero'] ?? '',
                'complemento' => $novoEndereco['complemento'] ?? '',
                'bairro' => $novoEndereco['bairro'] ?? '',
                'cep' => $novoEndereco['cep'] ?? '',
                'cidade' => $novoEndereco['cidade'] ?? '',
                'estado' => $novoEndereco['estado'] ?? '',
            ];
        }
    }

}
