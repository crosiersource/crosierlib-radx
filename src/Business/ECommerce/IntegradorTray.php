<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\ECommerce;

use App\Entity\EcommIntegra\ClienteConfig;
use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibRadxBundle\Business\Fiscal\NotaFiscalBusiness;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Depto;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscal;
use CrosierSource\CrosierLibRadxBundle\Entity\Fiscal\NotaFiscalItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\DeptoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\ProdutoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaItemEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\Fiscal\NotaFiscalRepository;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Regras de negócio para a integração com a Tray.
 *
 * @author Carlos Eduardo Pauluk
 */
class IntegradorTray implements IntegradorECommerce
{

    private Client $client;

    public string $endpoint;

    public string $accessToken;

    private Security $security;

    private ParameterBagInterface $params;

    private SyslogBusiness $syslog;

    private DeptoEntityHandler $deptoEntityHandler;

    private Connection $conn;

    private ProdutoEntityHandler $produtoEntityHandler;

    private VendaEntityHandler $vendaEntityHandler;

    private VendaItemEntityHandler $vendaItemEntityHandler;

    private ?array $deptosNaTray = null;

    private NotaFiscalBusiness $notaFiscalBusiness;


    public function __construct(Security               $security,
                                ParameterBagInterface  $params,
                                SyslogBusiness         $syslog,
                                DeptoEntityHandler     $deptoEntityHandler,
                                ProdutoEntityHandler   $produtoEntityHandler,
                                VendaEntityHandler     $vendaEntityHandler,
                                VendaItemEntityHandler $vendaItemEntityHandler,
                                NotaFiscalBusiness     $notaFiscalBusiness
    )
    {
        $this->security = $security;
        $this->params = $params;
        $this->syslog = $syslog->setApp('radx')->setComponent(self::class);
        $this->deptoEntityHandler = $deptoEntityHandler;
        $this->conn = $deptoEntityHandler->getDoctrine()->getConnection();
        $this->produtoEntityHandler = $produtoEntityHandler;
        $this->vendaEntityHandler = $vendaEntityHandler;
        $this->vendaItemEntityHandler = $vendaItemEntityHandler;
        $this->notaFiscalBusiness = $notaFiscalBusiness;
        $this->client = new Client();
    }

    /**
     * @param array $store
     * @throws ViewException
     */
    public function saveStoreConfig(array $store)
    {
        if (!$store['store_id']) {
            throw new ViewException('chave "store_id" n/d no array');
        }
        $rs = $this->conn->fetchAssociative('SELECT id, valor FROM cfg_app_config WHERE chave = :chave', ['chave' => 'tray.configs.json']);
        if ($rs['valor'] ?? false) {
            $stores = json_decode($rs['valor'], true);
            $achou = false;
            foreach ($stores as $k => $v) {
                if ($v['store_id'] === $store['store_id']) {
                    $stores[$k] = array_merge($stores[$k], $store);
                    $achou = true;
                    break;
                }
            }
            if (!$achou) {
                $stores[] = $store;
            }
            $this->conn->update('cfg_app_config', ['valor' => json_encode($stores)], ['id' => $rs['id']]);
            return $store;
            if (!$store) {
                throw new ViewException('storeId n/d em cfg_app_config.tray.configs.json');
            }
        } else {
            throw new ViewException('cfg_app_config.tray.configs.json n/d');
        }
    }

    public function getStores()
    {
        $rs = $this->conn->fetchAssociative('SELECT id, valor FROM cfg_app_config WHERE chave = :chave', ['chave' => 'tray.configs.json']);
        if ($rs['valor'] ?? false) {
            return json_decode($rs['valor'], true);
        } else {
            throw new ViewException('cfg_app_config.tray.configs.json n/d');
        }
    }


    public function getStore(?string $storeId = null)
    {
        $stores = $this->getStores();
        $store = null;
        // se não passou pega o único por default
        if (!$storeId) {
            if (count($stores) > 1) {
                throw new ViewException('Diversas configs de lojas em cfg_app_config.tray.configs.json e storeId n/d');
            } else {
                $store = $stores[0];
            }
        } else {
            foreach ($stores as $k => $v) {
                if ($v['store_id'] === $storeId) {
                    $store = $v;
                    // já seta a chave cfg_app_config.id para poder salvar mais fácil 
                    if (!($store['cfg_app_config.id'] ?? false)) {
                        $store['cfg_app_config.id'] = $rs['id'];
                        $this->saveStoreConfig($store);
                    }
                }
            }
        }
        if (!$store) {
            throw new ViewException('storeId n/d em cfg_app_config.tray.configs.json');
        }
        return $store;

    }

    /**
     * @throws ViewException
     */
    public function handleAccessToken(array &$store): string
    {
        if (!($store['date_expiration_access_token'] ?? false) || DateTimeUtils::diffInMinutes(DateTimeUtils::parseDateStr($store['date_expiration_access_token']), new \DateTime()) < 60) {
            try {
                $this->syslog->info('Tray.renewAccessToken', $store['url_loja']);
                $rs = $this->renewAccessToken($store);
                $store = $this->saveStoreConfig($store);
            } catch (ViewException $e) {
                if ($e->getPrevious() instanceof ClientException && $e->getPrevious()->getResponse()->getStatusCode() === 401) {
                    $store['ativa'] = false;
                    $store = $this->saveStoreConfig($store);
                }
                throw new ViewException($e->getMessage(), 0, $e);
            }
        }
        return $store['access_token'];
    }


    public function renewAllAccessTokens(): void
    {
        $stores = $this->getStores();
        foreach ($stores as $store) {
            $this->renewAccessToken($store);
        }
    }


    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }


    public function autorizarApp(?string $storeId = null)
    {
        try {
            $store = $this->getStore($storeId);
            $url = $store['url_loja'] . 'web_api/auth';
            $response = $this->client->request('POST', $url, [
                'form_params' => [
                    'consumer_key' => $store['consumer_key'],
                    'consumer_secret' => $store['consumer_secret'],
                    'code' => $store['code'],
                ]
            ]);
            $bodyContents = $response->getBody()->getContents();
            $authInfo = json_decode($bodyContents, true);
            $store['access_token'] = $authInfo['access_token'];
            $store['refresh_token'] = $authInfo['refresh_token'];
            $store['date_expiration_access_token'] = $authInfo['date_expiration_access_token'];
            $store['date_expiration_refresh_token'] = $authInfo['date_expiration_refresh_token'];
            $store['date_activated'] = $authInfo['date_activated'];
            $this->saveStoreConfig($store);
        } catch (GuzzleException $e) {
            throw new ViewException('Erro - autorizarAppByStoreId', 0, $e);
        }
    }


    public function renewAccessToken(?array $store = null): array
    {
        try {
            $store = $store ?? $this->getStore();

            $response = $this->client->request('GET', $store['url_loja'] . 'web_api/auth?refresh_token=' . $store['refresh_token']);
            $bodyContents = $response->getBody()->getContents();
            $authInfo = json_decode($bodyContents, true);

            $store['access_token'] = $authInfo['access_token'];
            $store['refresh_token'] = $authInfo['refresh_token'];
            $store['date_expiration_access_token'] = $authInfo['date_expiration_access_token'];
            $store['date_expiration_refresh_token'] = $authInfo['date_expiration_refresh_token'];
            $store['date_activated'] = $authInfo['date_activated'];
            $store = $this->saveStoreConfig($store);
            return $store;
        } catch (GuzzleException $e) {
            if ($e->getCode() === 401) {
                throw new ViewException('Erro: 401 - Unauthorized em renewAccessToken. É necessário reativar a loja.', 0, $e);    
            }
            throw new ViewException('Erro - renewAccessTokenByStoreId', 0, $e);
        }
    }


    /**
     * @throws ViewException
     */
    public function integraCategoria(Depto $depto): int
    {
        $store = $this->getStore();
        $syslog_obs = 'depto = ' . $depto->nome . ' (' . $depto->getId() . ')';
        $this->syslog->debug('integraDepto - ini', $syslog_obs);
        $idDeptoTray = null;

        $url = $this->getEndpoint() . 'web_api/categories?access_token=' . $this->handleAccessToken($store) . '&name=' . $depto->nome;
        $response = $this->client->request('GET',
            $url);
        $bodyContents = $response->getBody()->getContents();
        $json = json_decode($bodyContents, true);
        $idDeptoTray = $json['Categories'][0]['Category']['id'] ?? null;


        if (!$idDeptoTray) {
            $this->syslog->info('integraDepto - não existe, enviando...', $syslog_obs);

            $url = $this->getEndpoint() . 'web_api/categories?access_token=' . $this->handleAccessToken($store);
            $response = $this->client->request('POST', $url, [
                'form_params' => [
                    'Category' => [
                        'name' => $depto->nome,
                    ]
                ]
            ]);
            $bodyContents = $response->getBody()->getContents();
            $json = json_decode($bodyContents, true);
            if ($json['message'] !== 'Created') {
                throw new ViewException('Erro ao criar categoria');
            }
            $idDeptoTray = $json['id'];
            $this->syslog->info('integraDepto - integrado', $syslog_obs);
        }
        if (!isset($depto->jsonData['ecommerce_id']) || $depto->jsonData['ecommerce_id'] !== $idDeptoTray) {
            $this->syslog->info('integraDepto - salvando json_data', $syslog_obs);
            $depto->jsonData['ecommerce_id'] = $idDeptoTray;
            $depto->jsonData['integrado_em'] = (new \DateTime())->format('Y-m-d H:i:s');
            $depto->jsonData['integrado_por'] = $this->security->getUser() ? $this->security->getUser()->getUsername() : 'n/d';
            $this->deptoEntityHandler->save($depto);
            $this->syslog->info('integraDepto - salvando json_data: OK', $syslog_obs);
        }

        return $idDeptoTray;
    }

    /**
     * @throws ViewException
     */
    public function integraProduto(Produto $produto): int
    {
        try {
            $syslog_obs = 'produto = ' . $produto->nome . ' (' . $produto->getId() . ')';
            $this->syslog->debug('integraProduto - ini', $syslog_obs);
            $arrProduct = [
                'Product' => [
//                    'category_id' => $produto->depto->jsonData['ecommerce_id'],
//                    'ean' => $produto->jsonData['ean'],
//                    'brand' => $produto->jsonData['marca'],
//                    'name' => $produto->nome,
//                    'title' => $produto->jsonData['titulo'],
//                    'description' => $produto->jsonData['descricao_produto'],
//                    'additional_message' => $produto->jsonData['caracteristicas'],
//                    "picture_source_1" => "https://49839.cdn.simplo7.net/static/49839/sku/panos-de-cera-pano-de-cera-kit-p-m-g-estampa-abelhas--p-1619746505558.jpg",
//                    "picture_source_2" => "https://49839.cdn.simplo7.net/static/49839/sku/panos-de-cera-pano-de-cera-kit-p-m-g-estampa-abelhas--p-1619746502208.jpg",
//                    'available' => $produto->status === 'ATIVO' ? 1 : 0,
//                    'has_variation' => 0,
//                    'hot' => 1,
//                    'price' => 10,
//                    'weight' => 20,
                    'stock' => 9,
                ],
            ];
            $jsonRequest = json_encode($arrProduct, JSON_UNESCAPED_SLASHES);
            $url = $this->getEndpoint() . 'web_api/products?access_token=' . $this->handleAccessToken($store);
            $method = 'POST';
            if ($produto->jsonData['ecommerce_id'] ?? false) {
                //$arrProduto['id'] = $produto->jsonData['ecommerce_id'];
                $url = $this->getEndpoint() . 'web_api/products/' . $produto->jsonData['ecommerce_id'] . '?access_token=' . $this->handleAccessToken($store);
                $method = 'PUT';
            }
            $response = $this->client->request($method, $url, [
                'form_params' => $arrProduct
            ]);
            $bodyContents = $response->getBody()->getContents();
            $json = json_decode($bodyContents, true);
            if (!in_array($json['message'], ['Created', 'Saved'], true)) {
                throw new ViewException('Erro ao criar produto');
            }
            $idProdutoTray = $json['id'];
            $this->syslog->info('integraProduto - integrado', $syslog_obs);
            $this->syslog->info('integraProduto - salvando json_data', $syslog_obs);
            $produto->jsonData['ecommerce_id'] = $idProdutoTray;
            $produto->jsonData['integrado_em'] = (new \DateTime())->format('Y-m-d H:i:s');
            $produto->jsonData['integrado_por'] = $this->security->getUser() ? $this->security->getUser()->getUsername() : 'n/d';
            $this->produtoEntityHandler->save($produto);
            $this->syslog->info('integraProduto - salvando json_data: OK', $syslog_obs);
            return $idProdutoTray;
        } catch (GuzzleException $e) {
            dd($e->getResponse()->getBody()->getContents());
        }
    }

    /**
     * @throws ViewException
     */
    public function integraVariacaoProduto(Produto $produto): int
    {
        try {
            $syslog_obs = 'produto = ' . $produto->nome . ' (' . $produto->getId() . ')';
            $this->syslog->debug('integraProduto - ini', $syslog_obs);
            $variacao = '102';
            $arrVariant = [
                'Variant' => [
                    'product_id' => $produto->jsonData['ecommerce_id'],
                    'ean' => $produto->jsonData['ean'] . '_' . $variacao,
                    "picture_source_1" => "https://49839.cdn.simplo7.net/static/49839/sku/160453730076346.jpg",
                    "picture_source_2" => "https://49839.cdn.simplo7.net/static/49839/sku/160453730095911.jpg",
                    'price' => 18,
                    'stock' => 999,
                    'weight' => 321,
                    'Sku' => [
                        ['type' => 'Tamanho', 'value' => 102],
                    ]
                ],
            ];
            $jsonRequest = json_encode($arrVariant, JSON_UNESCAPED_SLASHES);
            $url = $this->getEndpoint() . 'web_api/products/variants?access_token=' . $this->handleAccessToken($store);
            $method = 'POST';
            if ($produto->jsonData['ecommerce_item_id'] ?? false) {
                //$arrProduto['id'] = $produto->jsonData['ecommerce_id'];
                $url = $this->getEndpoint() . 'web_api/products/variants/' . $produto->jsonData['ecommerce_item_id'] . '?access_token=' . $this->handleAccessToken($store);
                $method = 'PUT';
            }
            $response = $this->client->request($method, $url, [
                'form_params' => $arrVariant
            ]);
            $bodyContents = $response->getBody()->getContents();
            $json = json_decode($bodyContents, true);
            if (!in_array($json['message'], ['Created', 'Saved'], true)) {
                throw new ViewException('Erro ao criar produto');
            }
            $idVariantTray = $json['id'];
            $this->syslog->info('integraProduto - integrado', $syslog_obs);
            $this->syslog->info('integraProduto - salvando json_data', $syslog_obs);
            $produto->jsonData['ecommerce_item_id'] = $idVariantTray;
            $produto->jsonData['integrado_em'] = (new \DateTime())->format('Y-m-d H:i:s');
            $produto->jsonData['integrado_por'] = $this->security->getUser() ? $this->security->getUser()->getUsername() : 'n/d';
            $this->produtoEntityHandler->save($produto);
            $this->syslog->info('integraProduto - salvando json_data: OK', $syslog_obs);
            return $idVariantTray;
        } catch (GuzzleException $e) {
            dd($e->getResponse()->getBody()->getContents());
        }
    }

    public function obterCliente($idClienteECommerce)
    {
        // TODO: Implement obterCliente() method.
    }

    public function obterPedido(string $numPedido): array
    {
        $store = $this->getStore();
        $url = $store['url_loja'] . 'web_api/orders/' . $numPedido . '/complete?access_token=' . $this->handleAccessToken($store);
        $response = $this->client->request('GET', $url);
        $bodyContents = $response->getBody()->getContents();
        $json = json_decode($bodyContents, true);
        return $json;
    }


    public function obterVendas(\DateTime $dtVenda, ?bool $resalvar = false): int
    {
        // TODO: Implement obterVendas() method.
    }

    public function obterVendasPorData(\DateTime $dtVenda)
    {
        // TODO: Implement obterVendasPorData() method.
    }


    public function integrarVendaParaCrosier(string $numPedido)
    {
        $dadosPedido = $this->obterPedido($numPedido);
    }

    public function reintegrarVendaParaCrosier(Venda $venda)
    {

    }

    public function integrarVendaParaECommerce(Venda $venda)
    {
        // TODO: Implement integrarVendaParaECommerce() method.
    }

    public function integrarDadosFiscaisNoPedido(int $numPedido)
    {
        try {
            $conn = $this->vendaEntityHandler->getDoctrine()->getConnection();
            $existe = $conn->fetchAssociative('SELECT nf.id FROM fis_nf nf WHERE nf.json_data->>"$.num_pedido_tray" = :numPedido', ['numPedido' => $numPedido]);
            if (!$existe) {
                throw new ViewException('Nota Fiscal não encontrada para este pedido');
            }

            /** @var NotaFiscalRepository $repoNotaFiscal */
            $repoNotaFiscal = $this->vendaEntityHandler->getDoctrine()->getRepository(NotaFiscal::class);
            /** @var NotaFiscal $notaFiscal */
            $notaFiscal = $repoNotaFiscal->find($existe['id']);

            $store = $this->getStore();
            $url = $store['url_loja'] . 'web_api/orders/' . $numPedido . '/invoices?access_token=' . $this->handleAccessToken($store);
            $arr = [
                'issue_date' => $notaFiscal->dtEmissao->format('Y-m-d'),
                'number' => $notaFiscal->numero,
                'serie' => $notaFiscal->serie,
                'value' => $notaFiscal->valorTotal,
                'key' => $notaFiscal->chaveAcesso,
                'xml_danfe' => $notaFiscal->getXMLDecodedAsString()
            ];
            $jsonRequest = json_encode($arr, JSON_UNESCAPED_SLASHES);
            $method = 'POST';
            $response = $this->client->request($method, $url, [
                'form_params' => $arr
            ]);
            $bodyContents = $response->getBody()->getContents();
            $json = json_decode($bodyContents, true);
            if (!in_array($json['message'], ['Created', 'Saved'], true)) {
                throw new ViewException('Erro - integrarVendaParaECommerce2');
            }
            return $json;
        } catch (GuzzleException $e) {
            dd($e->getResponse()->getBody()->getContents());
        }
    }


    public function atualizaDadosEnvio(int $numPedido)
    {
        try {
            $url = $this->getEndpoint() . 'web_api/orders/' . $numPedido . '?access_token=' . $this->handleAccessToken($store);
            $arr = [
                'Order' => [
                    'status_id' => 124141,
                    'sending_date' => '2021-08-25',
                    'sending_code' => 'PY871797797BR',
                ]
            ];
            $jsonRequest = json_encode($arr, JSON_UNESCAPED_SLASHES);
            $method = 'PUT';
            $response = $this->client->request($method, $url, [
                'form_params' => $arr
            ]);
            $bodyContents = $response->getBody()->getContents();
            $json = json_decode($bodyContents, true);
            if (!in_array($json['message'], ['Created', 'Saved'], true)) {
                throw new ViewException('Erro ao criar produto');
            }
        } catch (GuzzleException $e) {
            dd($e->getResponse()->getBody()->getContents());
        }
    }


    public function cancelarPedido(int $numPedido)
    {
        try {
            $url = $this->getEndpoint() . 'web_api/orders/cancel/' . $numPedido . '?access_token=' . $this->handleAccessToken($store);
            $method = 'PUT';
            $response = $this->client->request($method, $url);
            $bodyContents = $response->getBody()->getContents();
            $json = json_decode($bodyContents, true);
            if (!in_array($json['message'], ['Canceled'], true)) {
                throw new ViewException('Erro ao criar produto');
            }
        } catch (GuzzleException $e) {
            dd($e->getResponse()->getBody()->getContents());
        }
    }


    /**
     * @param int $numPedido
     * @return int|null
     * @throws ViewException
     * @throws \Doctrine\DBAL\Exception
     */
    public function gerarNFeParaVenda(string $numPedido): ?int
    {
        $conn = $this->vendaEntityHandler->getDoctrine()->getConnection();
        $existe = $conn->fetchAssociative('SELECT id FROM fis_nf WHERE json_data->>"$.num_pedido_tray" = :numPedido', ['numPedido' => $numPedido]);
        if ($existe) {
            return $existe['id'];
        }

        $arrPedido = $this->obterPedido($numPedido);

        $notaFiscal = new NotaFiscal();

        $notaFiscal->jsonData['num_pedido_tray'] = $numPedido;
        $notaFiscal->documentoEmitente = '34411048000104';
        $notaFiscal->tipoNotaFiscal = 'NFE';
        $notaFiscal->naturezaOperacao = 'VENDA';
        $notaFiscal->entradaSaida = 'S';
        $notaFiscal->dtEmissao = new \DateTime();
        $notaFiscal->dtSaiEnt = new \DateTime();
        $notaFiscal->transpModalidadeFrete = 'EMITENTE';
        $notaFiscal->documentoDestinatario = $arrPedido['Order']['Customer']['cpf'] ?? $arrPedido['Order']['Customer']['cnpj'];
        $notaFiscal->xNomeDestinatario = $arrPedido['Order']['Customer']['name'];
        $endereco = $arrPedido['Order']['Customer']['CustomerAddresses'][0]['CustomerAddress'];
        $notaFiscal->logradouroDestinatario = $endereco['address'];
        $notaFiscal->numeroDestinatario = $endereco['number'];
        $notaFiscal->complementoDestinatario = $endereco['complement'] ?? '';
        $notaFiscal->bairroDestinatario = $endereco['neighborhood'] ?? '';
        $notaFiscal->cidadeDestinatario = $endereco['city'];
        $notaFiscal->estadoDestinatario = $endereco['state'];
        $notaFiscal->cepDestinatario = $endereco['zip_code'];
        $notaFiscal->foneDestinatario = $arrPedido['Order']['Customer']['cellphone'] ?? $arrPedido['Order']['Customer']['phone'] ?? '';
        $notaFiscal->emailDestinatario = $arrPedido['Order']['Customer']['email'] ?? '';

        $notaFiscal->infoCompl = 'Envio: ' . $arrPedido['Order']['shipment_integrator'];
        $notaFiscal->infoCompl .= PHP_EOL . 'Pedido: ' . $numPedido;


        foreach ($arrPedido['Order']['ProductsSold'] as $rItem) {
            $item = $rItem['ProductsSold'];
            $notaFiscalItem = new NotaFiscalItem();
            $notaFiscalItem->codigo = $item['reference'];
            $notaFiscalItem->descricao = $item['original_name'];
            $notaFiscalItem->qtde = $item['quantity'];
            $notaFiscalItem->cfop = $endereco['state'] === 'PR' ? '5102' : '6102';
            $notaFiscalItem->csosn = 103;
            $notaFiscalItem->ncm = '63052000';
            $notaFiscalItem->unidade = 'UN';
            $notaFiscalItem->valorUnit = $item['price'];

            $notaFiscal->addItem($notaFiscalItem);
        }

        $this->notaFiscalBusiness->saveNotaFiscal($notaFiscal);

        return $notaFiscal->getId();
    }


}
