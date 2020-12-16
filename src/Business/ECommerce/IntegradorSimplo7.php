<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\ECommerce;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\Entity\Security\User;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\Config\AppConfigEntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\ImageUtils\ImageUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\NumberUtils\DecimalUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\WebUtils\WebUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Depto;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Grupo;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Subgrupo;
use CrosierSource\CrosierLibRadxBundle\Entity\RH\Colaborador;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\PlanoPagto;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaPagto;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\CRM\ClienteEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\DeptoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\GrupoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\ProdutoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\SubgrupoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaItemEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\CRM\ClienteRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\RH\ColaboradorRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Vendas\PlanoPagtoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Vendas\VendaRepository;
use Doctrine\DBAL\ConnectionException;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Cache\ItemInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * Regras de negócio para a integração com a Simplo7.
 *
 * @author Carlos Eduardo Pauluk
 */
class IntegradorSimplo7 implements IntegradorECommerce
{
    private AppConfigEntityHandler $appConfigEntityHandler;

    private ?string $chave = null;

    private ?string $endpoint = null;

    private ?int $caixaSiteCarteiraId = null;

    private Security $security;

    private ProdutoEntityHandler $produtoEntityHandler;

    private VendaEntityHandler $vendaEntityHandler;

    private VendaItemEntityHandler $vendaItemEntityHandler;

    private ClienteEntityHandler $clienteEntityHandler;

    private UploaderHelper $uploaderHelper;

    private ParameterBagInterface $params;

    private MessageBusInterface $bus;

    private SyslogBusiness $syslog;

    private IntegradorMercadoPago $integradorMercadoPago;

    private ?int $delayEntreIntegracoesDeProduto = null;

    private ?bool $permiteIntegrarProdutosSemImagem = null;

    private ?array $marcasNaSimplo7 = null;

    /**
     * IntegradorWebStorm constructor.
     * @param AppConfigEntityHandler $appConfigEntityHandler
     * @param Security $security
     * @param DeptoEntityHandler $deptoEntityHandler
     * @param GrupoEntityHandler $grupoEntityHandler
     * @param SubgrupoEntityHandler $subgrupoEntityHandler
     * @param ProdutoEntityHandler $produtoEntityHandler
     * @param VendaEntityHandler $vendaEntityHandler
     * @param VendaItemEntityHandler $vendaItemEntityHandler
     * @param ClienteEntityHandler $clienteEntityHandler
     * @param UploaderHelper $uploaderHelper
     * @param ParameterBagInterface $params
     * @param MessageBusInterface $bus
     * @param SyslogBusiness $syslog
     * @param IntegradorMercadoPago $integradorMercadoPago
     */
    public function __construct(AppConfigEntityHandler $appConfigEntityHandler,
                                Security $security,
                                DeptoEntityHandler $deptoEntityHandler,
                                GrupoEntityHandler $grupoEntityHandler,
                                SubgrupoEntityHandler $subgrupoEntityHandler,
                                ProdutoEntityHandler $produtoEntityHandler,
                                VendaEntityHandler $vendaEntityHandler,
                                VendaItemEntityHandler $vendaItemEntityHandler,
                                ClienteEntityHandler $clienteEntityHandler,
                                UploaderHelper $uploaderHelper,
                                ParameterBagInterface $params,
                                MessageBusInterface $bus,
                                SyslogBusiness $syslog,
                                IntegradorMercadoPago $integradorMercadoPago)
    {
        $this->appConfigEntityHandler = $appConfigEntityHandler;
        $this->security = $security;
        $this->produtoEntityHandler = $produtoEntityHandler;
        $this->vendaEntityHandler = $vendaEntityHandler;
        $this->vendaItemEntityHandler = $vendaItemEntityHandler;
        $this->clienteEntityHandler = $clienteEntityHandler;
        $this->uploaderHelper = $uploaderHelper;
        $this->params = $params;
        $this->bus = $bus;
        $this->syslog = $syslog->setApp('radx')->setComponent(self::class);
        $this->integradorMercadoPago = $integradorMercadoPago;
    }


    /**
     * @return string
     */
    public function getChave(): string
    {
        if (!$this->chave) {
            try {
                $repoAppConfig = $this->appConfigEntityHandler->getDoctrine()->getRepository(AppConfig::class);
                $rs = $repoAppConfig->findOneByFiltersSimpl([['chave', 'EQ', 'ecomm_info_integra_SIMPLO7_chave'], ['appUUID', 'EQ', $_SERVER['CROSIERAPPRADX_UUID']]]);
                $this->chave = $rs->getValor();
            } catch (\Throwable $e) {
                throw new \RuntimeException('Erro ao instanciar IntegradorSimplo7 (chave ecomm_info_integra_SIMPLO7_chave ?)');
            }
        }
        return $this->chave;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        if (!$this->endpoint) {
            try {
                $repoAppConfig = $this->appConfigEntityHandler->getDoctrine()->getRepository(AppConfig::class);
                $rs = $repoAppConfig->findOneByFiltersSimpl([['chave', 'EQ', 'ecomm_info_integra_SIMPLO7_endpoint'], ['appUUID', 'EQ', $_SERVER['CROSIERAPPRADX_UUID']]]);
                $this->endpoint = $rs->getValor();
            } catch (\Throwable $e) {
                throw new \RuntimeException('Erro ao pesquisar appConfig ecomm_info_integra_SIMPLO7_endpoint');
            }
        }
        return $this->endpoint;
    }

    /**
     * @return string
     */
    public function getCaixaSiteCarteiraId(): string
    {
        if (!$this->caixaSiteCarteiraId) {
            try {
                $repoAppConfig = $this->appConfigEntityHandler->getDoctrine()->getRepository(AppConfig::class);
                $rs = $repoAppConfig->findOneByFiltersSimpl([['chave', 'EQ', 'ecomm_info_caixa_site_carteira_id'], ['appUUID', 'EQ', $_SERVER['CROSIERAPPRADX_UUID']]]);
                $this->caixaSiteCarteiraId = (int)$rs->getValor();
            } catch (\Throwable $e) {
                throw new \RuntimeException('Erro ao pesquisar appConfig ecomm_info_caixa_site_carteira_id');
            }
        }
        return $this->caixaSiteCarteiraId;
    }

    /**
     * Obtém as marcas cadastradas na Simplo7
     * @return array
     * @throws ViewException
     */
    public function selectMarcasNaSimplo7(): array
    {
        if (!$this->marcasNaSimplo7) {
            $this->syslog->debug('selectMarcasNaSimplo7');
            $client = $this->getNusoapClientExportacaoInstance();

            $xml = '<![CDATA[<?xml version="1.0" encoding="iso-8859-1"?>
            <ws_integracao xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <chave>' . $this->getChave() . '</chave>
                    <acao>select</acao>
                    <modulo>registros</modulo>    
                    <filtro>
                           <departamento></departamento>
                           <tipoAtributo></tipoAtributo>
                           <atributo></atributo>
                           <tipoCaracteristica></tipoCaracteristica>
                           <caracteristica></caracteristica>
                           <marca>1</marca>
                    </filtro>
                    </ws_integracao>]]>';

            $arResultado = $client->call('registrosSelect', [
                'xml' => utf8_decode($xml)
            ]);

            if ($client->faultcode) {
                $err = 'selectMarcasNaSimplo7 - faultcode: ' . (string)$client->faultcode;
                $this->syslog->err($err);
                throw new ViewException($err);
            }
            // else
            if ($client->getError()) {
                $err = 'selectMarcasNaSimplo7 - error: ' . $client->getError();
                $this->syslog->err($err);
                throw new ViewException($err);
            }

            $xmlResult = simplexml_load_string(utf8_encode($arResultado));

            if ($xmlResult->erros ?? false) {
                $err = $xmlResult->erros->erro->__toString();
                $this->syslog->err('selectMarcasNaSimplo7 - erros: ' . $xmlResult->erros->erro->__toString());
                throw new \RuntimeException($err);
            }

            $this->marcasNaSimplo7 = [];
            foreach ($xmlResult->registros->marcas->marca as $marca) {
                $this->marcasNaSimplo7[(int)$marca->idMarca->__toString()] = [
                    'nome' => $marca->nome->__toString(),
                ];
            }
            $this->syslog->debug('selectMarcasNaSimplo7 - OK: ' . count($this->marcasNaSimplo7) . ' marca(s)');
        }

        return $this->marcasNaSimplo7;
    }


    /**
     * @param string $marca
     * @return int
     */
    private function integraMarca(string $marca): int
    {
        return 0;
    }

    /**
     *
     */
    public function integrarDeptosGruposSubgrupos()
    {

    }

    /**
     * @param Depto $depto
     * @return int
     * @throws ViewException
     */
    public function integraDepto(Depto $depto): int
    {
        return 0;
    }

    /**
     * @param Grupo $grupo
     * @return int
     * @throws ViewException
     */
    public function integraGrupo(Grupo $grupo): int
    {
        return 0;
    }

    /**
     * @param Subgrupo $subgrupo
     * @return int
     * @throws ViewException
     */
    public function integraSubgrupo(Subgrupo $subgrupo): int
    {
        return 0;
    }

    /**
     * Integra um Depto, Grupo ou Subgrupo.
     *
     * @param string $descricao
     * @param int $nivel
     * @param int|null $idNivelPai1
     * @param int|null $idNivelPai2
     * @param int|null $ecommerce_id
     */
    public function integraDeptoGrupoSubgrupo(string $descricao, int $nivel, ?int $idNivelPai1 = null, ?int $idNivelPai2 = null, ?int $ecommerce_id = null)
    {

    }


    /**
     * @param Produto $produto
     * @param bool $integrarImagens
     * @param bool|null $respeitarDelay
     * @return void
     * @throws ViewException
     */
    public function integraProduto(Produto $produto, ?bool $integrarImagens = true, ?bool $respeitarDelay = false): void
    {
        $syslog_obs = 'produto = ' . $produto->getId() . '; integrarImagens = ' . $integrarImagens;

        if (!$this->isPermiteIntegrarProdutosSemImagens() && $produto->imagens->count() < 1) {
            $this->syslog->info('integraProduto - Não é permitido integrar produto sem imagens', $syslog_obs);
            throw new ViewException('Não é permitido integrar produto sem imagens');
        }
        if ($respeitarDelay) {
            if ($this->getDelayEntreIntegracoesDeProduto()) {
                $this->syslog->info('integraProduto - delay de ' . $this->getDelayEntreIntegracoesDeProduto(), $syslog_obs);
                sleep($this->getDelayEntreIntegracoesDeProduto());
            } else {
                $this->syslog->info('integraProduto - sem delay entre integrações');
            }
        }

        $start = microtime(true);

        $this->syslog->info('integraProduto - ini', $syslog_obs);

        $preco = $produto->jsonData['preco_site'] ?? $produto->jsonData['preco_tabela'] ?? 0.0;
        if ($preco <= 0) {
            $err = 'Não é possível integrar produto com preço <= 0';
            $this->syslog->err($err, $syslog_obs);
            throw new \RuntimeException($err);
        }

        try {
            $conn = $this->produtoEntityHandler->getDoctrine()->getConnection();
            $rs = $conn->fetchAssociative('SELECT valor FROM cfg_app_config WHERE chave = :chave AND app_uuid = :appUUID',
                [
                    'chave' => 'est_produto_json_metadata',
                    'appUUID' => $_SERVER['CROSIERAPPRADX_UUID']
                ]);
            $jsonCampos = json_decode($rs['valor'], true)['campos'];
        } catch (\Throwable $e) {
            $err = 'Erro ao pesquisar est_produto_json_metadata';
            $this->syslog->err($err, $syslog_obs);
            throw new \RuntimeException($err);
        }

        // Verifica se o depto, grupo e subgrupo já estão integrados
        $idDepto = $produto->depto->jsonData['ecommerce_id'] ?? $this->integraDepto($produto->depto);
        $idGrupo = $produto->grupo->jsonData['ecommerce_id'] ?? $this->integraGrupo($produto->grupo);
        $idSubgrupo = $produto->subgrupo->jsonData['ecommerce_id'] ?? $this->integraSubgrupo($produto->subgrupo);

        $idMarca = null;
        if ($produto->jsonData['marca'] ?? false) {
            $idMarca = $this->integraMarca($produto->jsonData['marca']);
        }

        $dimensoes = [];
        if (isset($produto->jsonData['dimensoes'])) {
            $dimensoes = explode('|', $produto->jsonData['dimensoes']);
        }
        $altura = $dimensoes[0] ?? '';
        $largura = $dimensoes[1] ?? '';
        $comprimento = $dimensoes[2] ?? '';

        $produtoEcommerceId = null;
        $produtoItemVendaId = null;
        if (isset($produto->jsonData['ecommerce_id']) && $produto->jsonData['ecommerce_id'] > 0) {
            $produtoEcommerceId = $produto->jsonData['ecommerce_id'];
            $produtoItemVendaId = $produto->jsonData['ecommerce_item_venda_id'] ?? null;
        }


        if (!$integrarImagens && !$produtoEcommerceId) {
            $err = 'Produto ainda não integrado. É necessário integrar as imagens!';
            $this->syslog->err($err, $syslog_obs);
            throw new ViewException($err);
        }

        $xml = '<![CDATA[<?xml version="1.0" encoding="iso-8859-1"?>
            <ws_integracao xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
               <chave>' . $this->getChave() . '</chave>
               <acao>' . ($produtoEcommerceId ? 'update' : 'insert') . '</acao>
               <modulo>produto</modulo>
               <produto pk="idProduto">' .
            ($produtoEcommerceId ? '<idProduto>' . $produtoEcommerceId . '</idProduto>' : '');
        $xml .= $idMarca ? '<idMarca>' . $idMarca . '</idMarca>' : '';
        $xml .=
            '<departamento pk="idDepartamento"><idDepartamento>' . $idDepto . '</idDepartamento></departamento>' .
            '<departamento pk="idDepartamento"><idDepartamento>' . $idGrupo . '</idDepartamento></departamento>' .
            '<departamento pk="idDepartamento"><idDepartamento>' . $idSubgrupo . '</idDepartamento></departamento>' .
            // '<situacao>0</situacao>' .
            '<prazoXD>0</prazoXD>' .
            '<conjunto />' .
            '<nome>' . $produto->jsonData['titulo'] . '</nome>' .
            '<referencia>' . strtoupper($produto->jsonData['referencia'] ?? '') . '</referencia>';

        $descricao_produto = '';
        if ($produto->jsonData['descricao_produto'] ?? false) {
            $descricao_produto = htmlspecialchars($produto->jsonData['descricao_produto']);
        }
        $xml .= '<descricao>' . $descricao_produto . '</descricao>';

        $caracteristicas = '';
        if ($produto->jsonData['caracteristicas'] ?? false) {
            $caracteristicas = htmlspecialchars($produto->jsonData['caracteristicas']);
        }
        $xml .= '<descricao-descricao-caracteristicas>' . $caracteristicas . '</descricao-descricao-caracteristicas>';

        $itens_inclusos = '';
        if ($produto->jsonData['itens_inclusos'] ?? false) {
            $itens_inclusos = htmlspecialchars($produto->jsonData['itens_inclusos']);
        }
        $xml .= '<descricao-itens-inclusos>' . $itens_inclusos . '</descricao-itens-inclusos>';

        $compativel_com = '';
        if ($produto->jsonData['compativel_com'] ?? false) {
            $compativel_com = htmlspecialchars($produto->jsonData['compativel_com']);
        }
        $xml .= '<descricao-compativel-com>' . $compativel_com . '</descricao-compativel-com>';

        $especif_tec = '';
        if ($produto->jsonData['especif_tec'] ?? false) {
            $especif_tec = htmlspecialchars($produto->jsonData['especif_tec']);
        }
        $xml .= '<descricao-especificacoes-tecnicas>' . $especif_tec . '</descricao-especificacoes-tecnicas>';


        foreach ($produto->jsonData as $campo => $valor) {
            if (isset($jsonCampos[$campo]['info_integr_ecommerce']['tipo_campo_ecommerce']) && $jsonCampos[$campo]['info_integr_ecommerce']['tipo_campo_ecommerce'] === 'caracteristica') {

                if ($jsonCampos[$campo]['info_integr_ecommerce']['ecommerce_id'] ?: null) {
                    $ecommerceId_tipoCaracteristica = (int)$jsonCampos[$campo]['info_integr_ecommerce']['ecommerce_id'];
                } else {
                    $ecommerceId_tipoCaracteristica = (int)$this->integraTipoCaracteristica($campo, $jsonCampos[$campo]['label']);
                }

                if ($jsonCampos[$campo]['tipo'] === 'tags') {
                    $valoresTags = explode(',', $valor);
                    foreach ($valoresTags as $valorTag) {
                        $ecommerceId_caracteristica = $this->integraCaracteristica($ecommerceId_tipoCaracteristica, $valorTag);
                        $xml .= '<caracteristicaProduto><idCaracteristica>' . $ecommerceId_caracteristica . '</idCaracteristica></caracteristicaProduto>';
                    }
                } else {
                    $ecommerceId_caracteristica = $this->integraCaracteristica($ecommerceId_tipoCaracteristica, $valor);
                    $xml .= '<caracteristicaProduto><idCaracteristica>' . $ecommerceId_caracteristica . '</idCaracteristica></caracteristicaProduto>';
                }
            }
        }

        $ecommerceId_caracteristica_unidade = $produto->unidadePadrao->jsonData['webstorm_info']['caracteristica_id'] ?? null;
        if (!$ecommerceId_caracteristica_unidade) {
            throw new ViewException('Erro ao integrar unidade do produto');
        }
        $xml .= '<caracteristicaProduto><idCaracteristica>' . $ecommerceId_caracteristica_unidade . '</idCaracteristica></caracteristicaProduto>';

        if ($integrarImagens) {
            foreach ($produto->imagens as $imagem) {
                $url = $_SERVER['CROSIERAPP_URL'] . $this->uploaderHelper->asset($imagem, 'imageFile');
                // verifica se existe a imagem "_1080.ext"
                $pathinfo = pathinfo($url);
                $parsedUrl = parse_url($url);
                $url1080 = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_1080.' . $pathinfo['extension'];
                try {
                    if (!WebUtils::urlNot404($url1080)) {
                        $imgDims = getimagesize($url);
                        if ($imgDims[0] > 1500 || $imgDims[1] > 1500) {
                            $imgUtils = new ImageUtils();
                            $imgUtils->load($url);
                            if ($imgDims[0] >= $imgDims[1]) {
                                // largura maior que altura
                                $imgUtils->resizeToWidth(1080);
                            } else {
                                $imgUtils->resizeToHeight(1080);
                            }
                            // '%kernel.project_dir%/public/images/produtos'
                            $file1080 = $this->params->get('kernel.project_dir') . '/public' .
                                str_replace($pathinfo['basename'], '', $parsedUrl['path']) .
                                $pathinfo['filename'] . '_1080.' . $pathinfo['extension'];
                            $imgUtils->save($file1080);
                        } else {
                            $url1080 = $url;
                        }
                    }
                } catch (\Exception $e) {
                    $err = 'Erro ao processar imagens: ' . $e->getMessage();
                    $this->syslog->err($err);
                    throw new \RuntimeException($err);
                }

                $xml .= '<imagens>
				<url>' . $url1080 . '</url>
				<prioridade>' . ($imagem->getOrdem() - 1) . '</prioridade>
			</imagens>';
            }
        }

        $referenciasExtras = '';
        if ($produto->jsonData['referencias_extras'] ?? false) {
            $referenciasExtras = htmlspecialchars($produto->jsonData['referencias_extras']);
        }
        $xml .= '<referenciasExtras>' . $referenciasExtras . '</referenciasExtras>';


        $xml .=
            '<itensVenda>
				<idItemVenda>' . $produtoItemVendaId . '</idItemVenda>
				<codigo>' . $produto->getId() . '</codigo>
				<preco>' . $preco . '</preco>
				<estoque>' . ($produto->jsonData['qtde_estoque_total'] ?? 0) . '</estoque>
				<estoqueMin>0</estoqueMin>
				<situacao>1</situacao>
				<peso>' . ($produto->jsonData['peso'] ?? '') . '</peso>
				<ean>' . htmlspecialchars(isset($produto->jsonData['ean']) ? $produto->jsonData['ean'] : '') . '</ean>
				<altura>' . $altura . '</altura>
				<largura>' . $largura . '</largura>
				<comprimento>' . $comprimento . '</comprimento>
            </itensVenda></produto>' .
            '</ws_integracao>]]>';


        $this->syslog->debug('integraProduto - XML REQUEST - ' . $syslog_obs, $xml);

        $client = $this->getNusoapClientImportacaoInstance();

        $arResultado = $client->call('produto' . ($produtoEcommerceId ? 'Update' : 'Add'), [
            'xml' => utf8_decode($xml)
        ]);

        if ($client->faultcode) {
            $this->syslog->err('integraProduto - faultcode: ' . (string)$client->faultcode, $syslog_obs);
            throw new \RuntimeException($client->faultcode);
        }
        // else
        if ($client->getError()) {
            $this->syslog->err('integraProduto - faultcode: ' . $client->getError(), $syslog_obs);
            throw new \RuntimeException($client->getError());
        }

        $arResultado = utf8_encode($arResultado);
        $arResultado = str_replace('&nbsp;', ' ', $arResultado);

        $this->syslog->debug('integraProduto - XML RESPONSE - ' . $syslog_obs, $xml);

        $xmlResult = simplexml_load_string($arResultado);

        if ($xmlResult->erros->erro ?? false) {
            $this->syslog->err('integraProduto - erros: ' . $xmlResult->erros->erro->__toString(), $syslog_obs);
            throw new \RuntimeException($xmlResult->erros->erro->__toString());
        }

        // está fazendo UPDATE
        if ($produtoEcommerceId) {
            $produto->jsonData['ecommerce_id'] = (int)$xmlResult->produtos->produto->idProduto->__toString();
            $produto->jsonData['ecommerce_item_venda_id'] = (int)$xmlResult->produtos->produto->itensVenda->itemVenda->resultado->idItemVenda->__toString();
        } else {
            $produto->jsonData['ecommerce_id'] = (int)$xmlResult->produto->produto->idProduto->__toString();
            $produto->jsonData['ecommerce_item_venda_id'] = (int)$xmlResult->produto->produto->itensVenda->itemVenda->idItemVenda->__toString();
        }


        $produto->jsonData['ecommerce_dt_integr'] = (new \DateTime())->modify('+1 minutes')->format('Y-m-d H:i:s');
        $produto->jsonData['ecommerce_dt_marcado_integr'] = null;
        $produto->jsonData['ecommerce_desatualizado'] = 0;

        /** @var User $user */
        $user = $this->security->getUser();
        $produto->jsonData['ecommerce_integr_por'] = $user ? $user->getNome() : 'n/d';


        $this->syslog->info('integraProduto - save', $syslog_obs);
        $this->produtoEntityHandler->save($produto);

        $tt = (int)(microtime(true) - $start);
        $this->syslog->info('integraProduto - OK (em ' . $tt . ' segundos)', $syslog_obs);
    }

    /**
     * Faz a integração de vários produtos em uma única chamada.
     *
     * @param array $produtosIds
     * @return void
     * @throws ViewException
     */
    public function atualizaEstoqueEPrecos(array $produtosIds): void
    {
        return;
    }

    /**
     * Envia para a fila de integração os produtos que foram alterados mas que ainda não foram reintegrados no ecommerce.
     * @return int
     */
    public function reenviarParaIntegracaoProdutosAlterados(): int
    {
        return 0;
    }

    /**
     * Envia produtos para a fila (queue) que executará as integrações com o webstorm.
     *
     * @param int|null $limit
     * @return int
     */
    public function reenviarTodosOsProdutosParaIntegracao(?int $limit = null): int
    {

    }

    /**
     * @param \DateTime $dtVenda
     * @param bool|null $resalvar
     * @return int
     * @throws ViewException
     */
    public function obterVendas(\DateTime $dtVenda, ?bool $resalvar = false): int
    {
        $conn = $this->vendaEntityHandler->getDoctrine()->getConnection();
        $pedidos = $this->obterVendasPorData($dtVenda);
        if ($pedidos ?? false) {
            foreach ($pedidos as $pedido) {
                $conn->beginTransaction();
                try {
                    $this->integrarVendaParaCrosier($pedido, $resalvar);
                    $conn->commit();
                } catch (\Throwable $e) {
                    try {
                        $conn->rollBack();
                    } catch (ConnectionException $e) {
                        throw new \RuntimeException('rollback err', 0, $e);
                    }
                    break;
                }
            }
        }
        return count($pedidos);
    }

    /**
     * @param \DateTime $dtVenda
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function obterVendasPorData(\DateTime $dtVenda)
    {
        $dtIni = (clone $dtVenda)->setTime(0, 0);
        $dtIniS = $dtIni->format('Y-m-d');

        $client = new Client();

        $jsons = [];
        $page = 1;
        do {
            $response = $client->request('GET', $this->getEndpoint() . '/ws/wspedidos.json?data_inicio=' . $dtIniS . '&page=' . $page++,
                [
                    'headers' => [
                        'Content-Type' => 'application/json; charset=UTF-8',
                        'appKey' => $this->getChave(),
                    ]
                ]
            );

            $bodyContents = $response->getBody()->getContents();
            $json = json_decode($bodyContents, true);

            $results = $json['result'] ?? [];
            foreach ($results as $result) {
                $response = $client->request('GET', $this->getEndpoint() . '/ws/wspedidos/' . $result['Wspedido']['id'] . '.json',
                    [
                        'headers' => [
                            'Content-Type' => 'application/json; charset=UTF-8',
                            'appKey' => $this->getChave(),
                        ]
                    ]
                );

                $bodyContents = $response->getBody()->getContents();
                $uJson = json_decode($bodyContents, true);

                $jsons[] = $uJson['result'];
            }
            $hasNextPage = $json['pagination']['has_next_page'];
        } while ($hasNextPage);

        return $jsons;
    }


    /**
     * @param int $idClienteECommerce
     */
    public function obterCliente($cpfCnpj)
    {


    }

    private function integrarVendaParaCrosier(array $pedido, ?bool $resalvar = false): void
    {
        try {
            $conn = $this->vendaEntityHandler->getDoctrine()->getConnection();

            $itens = $pedido['Item'];
            $pagamento = $pedido['Pagamento'] ?? null;
            $status_id = $pedido['Status']['id'];
            $status_nome = $pedido['Status']['nome'];
            $pedido = $pedido['Wspedido'];
            $pedido['cliente_cpfcnpj'] = preg_replace("/[^0-9]/", "", $pedido['cliente_cpfcnpj']);
            $dtPedido = DateTimeUtils::parseDateStr($pedido['data_pedido']);

            $this->syslog->info('Integrando pedido ' . $pedido['id'] . ' de ' .
                $dtPedido->format('d/m/Y H:i:s') . ' Cliente: ' . $pedido['cliente_razaosocial']);


            $venda = $conn->fetchAllAssociative('SELECT * FROM ven_venda WHERE json_data->>"$.ecommerce_idPedido" = :ecommerce_idPedido',
                ['ecommerce_idPedido' => $pedido['id']]);
            $venda = $venda[0] ?? null;
            if ($venda) {
                // se já existe, só confere o status
                // O único status que pode ser alterado no sentido Simplo7 -> Crosier é quando está em 'Aguardando Pagamento'
                $vendaJsonData = json_decode($venda['json_data'], true);
                if (($vendaJsonData['ecommerce_status_descricao'] === 'Criado') &&
                    (($vendaJsonData['ecommerce_status'] ?? null) != $pedido['pedidostatus_id'])) {

                    $vendaJsonData['ecommerce_status'] = $status_id;
                    $vendaJsonData['ecommerce_status_descricao'] = $status_nome;
                    $venda_['json_data'] = json_encode($vendaJsonData);
                    try {
                        $conn->update('ven_venda', $venda_, ['id' => $venda['id']]);
                    } catch (\Exception $e) {
                        throw new ViewException('Erro ao alterar status da venda. (ecommerce_idPedido = ' . $pedido['id'] . ')');
                    }
                }

                // Se não estiver pedindo para resalvar as informações novamente (o que irá sobreescrever quaisquer alterações), já retorna...
                if (!$resalvar) {
                    return;
                }

                try {
                    $conn->delete('ven_venda_item', ['venda_id' => $venda['id']]);
                } catch (\Throwable $e) {
                    $erro = 'Erro ao deletar itens da venda (id = "' . $venda['id'] . ')';
                    $this->syslog->err($erro);
                    throw new \RuntimeException($erro);
                }
                /** @var VendaRepository $repoVenda */
                $repoVenda = $this->vendaEntityHandler->getDoctrine()->getRepository(Venda::class);
                $venda = $repoVenda->find($venda['id']);

            } else {
                $venda = new Venda();
            }

            $venda->dtVenda = $dtPedido;

            /** @var ColaboradorRepository $repoColaborador */
            $repoColaborador = $this->vendaEntityHandler->getDoctrine()->getRepository(Colaborador::class);
            $vendedorNaoIdentificado = $repoColaborador->findOneBy(['cpf' => '99999999999']);
            $venda->vendedor = $vendedorNaoIdentificado;

            $venda->status = 'PV ABERTO';

            $cliente = $conn->fetchAllAssociative('SELECT id FROM crm_cliente WHERE documento = :documento',
                ['documento' => $pedido['cliente_cpfcnpj']]);
            /** @var ClienteRepository $repoCliente */
            $repoCliente = $this->vendaEntityHandler->getDoctrine()->getRepository(Cliente::class);
            if ($cliente[0]['id'] ?? false) {
                $cliente = $repoCliente->find($cliente[0]['id']);
            } else {
                $cliente = null;
            }

            if (!$cliente || $resalvar) {

                $cliente = $cliente ?? new Cliente();

                $cliente->documento = $pedido['cliente_cpfcnpj'];
                $cliente->nome = $pedido['cliente_razaosocial'];
                $cliente->jsonData['tipo_pessoa'] = strlen($pedido['cliente_cpfcnpj']) === 11 ? 'PF' : 'PJ';
                $cliente->jsonData['rg'] = '';
                $cliente->jsonData['dtNascimento'] = $pedido['cliente_data_nascimento'];
                $cliente->jsonData['sexo'] = '';
                $cliente->jsonData['nome_fantasia'] = '';
                $cliente->jsonData['inscricao_estadual'] = $pedido['cliente_ie'];

                $cliente->jsonData['fone1'] = $pedido['cliente_telefone'];
                $cliente->jsonData['fone2'] = $pedido['cliente_celular'];

                $cliente->jsonData['email'] = $pedido['cliente_email'];
                $cliente->jsonData['canal'] = 'ECOMMERCE';
                $cliente->jsonData['ecommerce_id'] = '';

                $cliente = $this->clienteEntityHandler->save($cliente);
            }

            // Verifica os endereços do cliente
            $enderecoJaSalvo = false;
            if (($cliente->jsonData['enderecos'] ?? false) && count($cliente->jsonData['enderecos']) > 0) {
                foreach ($cliente->jsonData['enderecos'] as $endereco) {
                    if ((($endereco['tipo'] ?? '') === 'ENTREGA,FATURAMENTO') &&
                        (($endereco['logradouro'] ?? '') === $pedido['entrega_logradouro']) &&
                        (($endereco['numero'] ?? '') === $pedido['entrega_numero']) &&
                        (($endereco['complemento'] ?? '') === $pedido['entrega_informacoes_adicionais']) &&
                        (($endereco['bairro'] ?? '') === $pedido['entrega_bairro']) &&
                        (($endereco['cep'] ?? '') === $pedido['entrega_cep']) &&
                        (($endereco['cidade'] ?? '') === $pedido['entrega_cidade']) &&
                        (($endereco['estado'] ?? '') === $pedido['entrega_estado'])) {
                        $enderecoJaSalvo = true;
                    }
                }
            }
            if (!$enderecoJaSalvo) {
                $cliente->jsonData['enderecos'][] = [
                    'tipo' => 'ENTREGA,FATURAMENTO',
                    'logradouro' => $pedido['entrega_logradouro'],
                    'numero' => $pedido['entrega_numero'],
                    'complemento' => $pedido['entrega_informacoes_adicionais'],
                    'bairro' => $pedido['entrega_bairro'],
                    'cep' => $pedido['entrega_cep'],
                    'cidade' => $pedido['entrega_cidade'],
                    'estado' => $pedido['entrega_estado'],
                ];
                $cliente = $this->clienteEntityHandler->save($cliente);
            }

            $venda->cliente = $cliente;

            $venda->jsonData['canal'] = 'ECOMMERCE';
            $venda->jsonData['ecommerce_idPedido'] = $pedido['id'];
            $venda->jsonData['ecommerce_status'] = $status_id;
            $venda->jsonData['ecommerce_status_descricao'] = $status_nome;

            $obs = [];
            $venda->jsonData['ecommerce_entrega_retirarNaLoja'] = '';
            $venda->jsonData['ecommerce_entrega_logradouro'] = $pedido['entrega_logradouro'];
            $venda->jsonData['ecommerce_entrega_numero'] = $pedido['entrega_numero'];
            $venda->jsonData['ecommerce_entrega_complemento'] = $pedido['entrega_informacoes_adicionais'];
            $venda->jsonData['ecommerce_entrega_bairro'] = $pedido['entrega_bairro'];
            $venda->jsonData['ecommerce_entrega_cidade'] = $pedido['entrega_cidade'];
            $venda->jsonData['ecommerce_entrega_uf'] = $pedido['entrega_estado'];
            $venda->jsonData['ecommerce_entrega_cep'] = $pedido['entrega_cep'];
            $venda->jsonData['ecommerce_entrega_telefone'] = $pedido['entrega_telefone'];
            $venda->jsonData['ecommerce_entrega_frete_calculado'] = $pedido['total_frete'];
            $venda->jsonData['ecommerce_entrega_frete_real'] = 0.00;
            $venda->jsonData['ecommerce_status'] = $pedido['pedidostatus_id'];
            $venda->jsonData['ecommerce_status_descricao'] = $arrStatus[$pedido['pedidostatus_id']]['nome'] ?? 'STATUS N/D';


            $obs[] = 'IP: ';
            $obs[] = 'Pagamento: ' . $pedido['pagamento_forma'];
            $obs[] = 'Envio: ' . $pedido['envio_servico'];
            if ($pedido['envio_codigo_objeto'] ?? false) {
                $obs[] = 'Rastreio: ' . $pedido['envio_codigo_objeto'];
            }

            $venda->jsonData['obs'] = implode(PHP_EOL, $obs);

            $venda->subtotal = 0.0;// a ser recalculado posteriormente
            $venda->desconto = 0.0;// a ser recalculado posteriormente
            $venda->valorTotal = 0.0;// a ser recalculado posteriormente


            $descontoTotal = $pedido['desconto_avista'];
            $totalProdutos = 0.0;
            foreach ($itens as $item) {
                $totalProdutos = bcadd($totalProdutos, $item['valor_total'], 2);
            }
            $pDesconto = bcdiv($descontoTotal, $totalProdutos, 8);

            // Salvo aqui para poder pegar o id
            $this->vendaEntityHandler->save($venda);

            /** @var ProdutoRepository $repoProduto */
            $repoProduto = $this->produtoEntityHandler->getDoctrine()->getRepository(Produto::class);
            $ordem = 1;
            $i = 0;
            $descontoAcum = 0.0;
            $vendaItem = null;
            foreach ($itens as $item) {
                /** @var Produto $produto */
                $produto = null;
                try {
                    // verifica se já existe uma ven_venda com o json_data.ecommerce_idPedido
                    $sProduto = $conn->fetchAssociative('SELECT id FROM est_produto WHERE json_data->>"$.ecommerce_id" = :idProduto', ['idProduto' => $item['produto_id']]);
                    if (!isset($sProduto['id'])) {
                        throw new \RuntimeException();
                    }
                    $produto = $repoProduto->find($sProduto['id']);
                } catch (\Throwable $e) {
                    throw new ViewException('Erro ao integrar venda. Erro ao pesquisar produto (idProduto = ' . $item['produto_id'] . ')');
                }

                $vendaItem = new VendaItem();
                $venda->addItem($vendaItem);
                $vendaItem->descricao = $produto->nome;
                $vendaItem->ordem = $ordem++;
                $vendaItem->devolucao = false;

                $vendaItem->unidade = $produto->unidadePadrao;

                $vendaItem->precoVenda = $item['valor_unitario'];
                $vendaItem->qtde = $item['quantidade'];
                $vendaItem->subtotal = bcmul($vendaItem->precoVenda, $vendaItem->qtde, 2);
                // Para arredondar para cima
                $vendaItem->desconto = DecimalUtils::round(bcmul($pDesconto, $vendaItem->subtotal, 3));
                $descontoAcum = (float)bcadd($descontoAcum, $vendaItem->desconto, 2);
                $vendaItem->produto = $produto;

                $vendaItem->jsonData['ecommerce_idItemVenda'] = $item['id'];
                $vendaItem->jsonData['ecommerce_codigo'] = $item['sku'];

                $this->vendaItemEntityHandler->save($vendaItem);
                $i++;
            }
            if ($descontoTotal !== $descontoAcum) {
                $diff = $descontoTotal - $descontoAcum;
                $vendaItem->desconto = bcadd($vendaItem->desconto, $diff, 2);
                $this->vendaItemEntityHandler->save($vendaItem);
            }

            $venda->recalcularTotais();


            try {
                $conn->delete('ven_venda_pagto', ['venda_id' => $venda->getId()]);
            } catch (\Throwable $e) {
                $erro = 'Erro ao deletar pagtos da venda (id = "' . $venda['id'] . ')';
                $this->syslog->err($erro);
                throw new \RuntimeException($erro);
            }


            /** @var PlanoPagtoRepository $repoPlanoPagto */
            $repoPlanoPagto = $this->vendaEntityHandler->getDoctrine()->getRepository(PlanoPagto::class);
            $arrayByCodigo = $repoPlanoPagto->arrayByCodigo();

            $totalPagto = bcadd($venda->valorTotal, $venda->jsonData['ecommerce_entrega_frete_calculado'] ?? 0.0, 2);

            $tipoFormaPagamento = $pedido['pagamento_forma'];

            $integrador = $pagamento['integrador'] ?? 'n/d';

            $vendaPagto = [
                'venda_id' => $venda->getId(),
                'valor_pagto' => $totalPagto,
                'json_data' => [
                    'nomeFormaPagamento' => $tipoFormaPagamento ?? 'n/d',
                    'integrador' => $integrador,
                    'codigo_transacao' => $pagamento['codigo_transacao'] ?? 'n/d',
                    'carteira_id' => $this->getCaixaSiteCarteiraId(),
                ],
                'inserted' => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated' => (new \DateTime())->format('Y-m-d H:i:s'),
                'version' => 0,
                'user_inserted_id' => 1,
                'user_updated_id' => 1,
                'estabelecimento_id' => 1
            ];
            $descricaoPlanoPagto = null;
            switch ($tipoFormaPagamento) {
                case 'Depósito Bancário':
                    $vendaPagto['plano_pagto_id'] = $arrayByCodigo['020']['id'];
                    $descricaoPlanoPagto = $arrayByCodigo['020']['descricao'];
                    break;
                case 'Boleto':
                    $vendaPagto['plano_pagto_id'] = $arrayByCodigo['030']['id'];
                    $descricaoPlanoPagto = $arrayByCodigo['030']['descricao'];
                    break;
                default:
                    // por padrão, cai para Cartão de Crédito
                    $vendaPagto['plano_pagto_id'] = $arrayByCodigo['010']['id'];
                    $descricaoPlanoPagto = $arrayByCodigo['010']['descricao'];
                    break;
            }

            $vendaPagto['json_data'] = json_encode($vendaPagto['json_data']);

            try {
                $conn->insert('ven_venda_pagto', $vendaPagto);
                $vendaPagtoId = $conn->lastInsertId();
                if ($integrador === 'Mercado Pago') {
                    $eVendaPagto = $this->vendaEntityHandler->getDoctrine()->getRepository(VendaPagto::class)->find($vendaPagtoId);
                    $this->integradorMercadoPago->handleTransacaoParaVendaPagto($eVendaPagto);
                }
            } catch (\Throwable $e) {
                throw new ViewException('Erro ao salvar dados do pagamento');
            }


            $venda->jsonData['infoPagtos'] = $descricaoPlanoPagto .
                ': R$ ' . number_format($venda->valorTotal, 2, ',', '.');

            $this->vendaEntityHandler->save($venda);
        } catch (\Throwable $e) {
            $this->syslog->err('Erro ao integrarVendaParaCrosier', $pedido['id']);
            throw new ViewException('Erro ao integrarVendaParaCrosier', 0, $e);
        }
    }


    /**
     * @param Venda $venda
     * @return void
     * @throws ViewException
     */
    public function reintegrarVendaParaCrosier(Venda $venda): void
    {
        if (!($venda->jsonData['ecommerce_idPedido'] ?? false)) {
            throw new ViewException('Venda sem ecommerce_idPedido');
        }

        $client = new Client();

        $response = $client->request('GET', $this->getEndpoint() . '/ws/wspedidos/' . $venda->jsonData['ecommerce_idPedido'] . '.json',
            [
                'headers' => [
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'appKey' => $this->getChave(),
                ]
            ]
        );

        $bodyContents = $response->getBody()->getContents();
        $json = json_decode($bodyContents, true);
        $arrPedido = $json['result'] ?? null;

        $this->integrarVendaParaCrosier($arrPedido, true);
    }


    /**
     * @param Venda $venda
     */
    public function integrarVendaParaECommerce(Venda $venda)
    {

    }


    public function obterProdutos()
    {
        $client = new Client();

        $response = $client->request('GET', $this->getEndpoint() . '/ws/wsprodutos.json',
            [
                'headers' => [
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'appKey' => $this->getChave(),
                ]
            ]
        );

        $bodyContents = $response->getBody()->getContents();
        $json = json_decode($bodyContents, true);

        $depto1 = $this->deptoEntityHandler->getDoctrine()->getRepository(Depto::class)->find(1);
        $grupo1 = $this->deptoEntityHandler->getDoctrine()->getRepository(Grupo::class)->find(1);
        $subgrupo1 = $this->deptoEntityHandler->getDoctrine()->getRepository(Subgrupo::class)->find(1);

        /** @var ProdutoRepository $repoProduto */
        $repoProduto = $this->produtoEntityHandler->getDoctrine()->getRepository(Produto::class);

        foreach ($json['result'] as $r) {
            $wsProduto = $r['Wsproduto'];
            $produto = $repoProduto->findOneByFiltersSimpl([['codigo', 'LIKE_END', $wsProduto['sku']]]);
            if (!$produto) {
                $produto = new Produto();
                $produto->codigo = $wsProduto['sku'];
                $produto->depto = $depto1;
                $produto->grupo = $grupo1;
                $produto->subgrupo = $subgrupo1;
            }
            $produto->nome = mb_strtoupper($wsProduto['nome']);

            $produto->jsonData['titulo'] = $wsProduto['nome'];
            $produto->jsonData['caracteristicas'] = $wsProduto['descricao_resumida'] ?? '';
            $produto->jsonData['especif_tec'] = $wsProduto['descricao'] ?? '';
            $produto->jsonData['peso'] = $r['WsprodutoEstoque'][0]['peso_liquido'] ?? '';
            $produto->jsonData['dimensoes'] =
                ($r['WsprodutoEstoque'][0]['altura'] ?? '') . '|' .
                ($r['WsprodutoEstoque'][0]['largura'] ?? '') . '|' .
                ($r['WsprodutoEstoque'][0]['comprimento'] ?? '');
            $produto->jsonData['ecommerce_id'] = $wsProduto['id'];
            $this->produtoEntityHandler->save($produto);
        }

    }


    /**
     * @return mixed
     * @throws ViewException
     */
    public function obterStatusPedidos()
    {
        try {
            $cache = new FilesystemAdapter($_SERVER['CROSIERAPP_ID'] . '.ecommerce.status', 0, $_SERVER['CROSIER_SESSIONS_FOLDER']);
            return $cache->get('obterStatusPedidos', function (ItemInterface $item) {

                $status = [];
                $rsStatus = json_decode((new Client())->request('GET', $this->getEndpoint() . '/ws/wsstatuspedidos.json',
                        [
                            'headers' => [
                                'Content-Type' => 'application/json; charset=UTF-8',
                                'appKey' => $this->getChave(),
                            ]
                        ]
                    )->getBody()->getContents(), true)['result'] ?? [];

                foreach ($rsStatus as $s) {
                    $status[$s['Wsstatuspedido']['id']] = $s['Wsstatuspedido'];
                }

                return $status;

            });
        } catch (\Psr\Cache\InvalidArgumentException | \Throwable $e) {
            $msg = 'Erro ao obter array com status de pedidos';
            $this->syslog->err($msg);
            throw new ViewException($msg);
        }

    }

}
