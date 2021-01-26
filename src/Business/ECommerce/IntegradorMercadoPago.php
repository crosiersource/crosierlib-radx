<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\ECommerce;


use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\Config\AppConfigEntityHandler;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\ExceptionUtils\ExceptionUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\ProdutoPreco;
use CrosierSource\CrosierLibRadxBundle\Entity\RH\Colaborador;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaItem;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaPagto;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\CRM\ClienteEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\ProdutoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\ProdutoPrecoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaItemEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\CRM\ClienteRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Estoque\ProdutoRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\RH\ColaboradorRepository;
use CrosierSource\CrosierLibRadxBundle\Repository\Vendas\VendaRepository;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @author Carlos Eduardo Pauluk
 */
class IntegradorMercadoPago
{

    private Connection $conn;

    public ?string $mlUser = null;

    private AppConfigEntityHandler $appConfigEntityHandler;

    private Security $security;

    private ProdutoEntityHandler $produtoEntityHandler;

    private ProdutoPrecoEntityHandler $produtoPrecoEntityHandler;

    private VendaEntityHandler $vendaEntityHandler;

    private VendaItemEntityHandler $vendaItemEntityHandler;

    private ClienteEntityHandler $clienteEntityHandler;

    private ParameterBagInterface $params;

    private MessageBusInterface $bus;

    private SyslogBusiness $syslog;

    public const CANAL = 'MERCADOLIVRE';

    /**
     * IntegradorWebStorm constructor.
     * @param AppConfigEntityHandler $appConfigEntityHandler
     * @param Security $security
     * @param ProdutoEntityHandler $produtoEntityHandler
     * @param ProdutoPrecoEntityHandler $produtoPrecoEntityHandler
     * @param VendaEntityHandler $vendaEntityHandler
     * @param VendaItemEntityHandler $vendaItemEntityHandler
     * @param ClienteEntityHandler $clienteEntityHandler
     * @param ParameterBagInterface $params
     * @param MessageBusInterface $bus
     * @param SyslogBusiness $syslog
     */
    public function __construct(AppConfigEntityHandler $appConfigEntityHandler,
                                Security $security,
                                ProdutoEntityHandler $produtoEntityHandler,
                                ProdutoPrecoEntityHandler $produtoPrecoEntityHandler,
                                VendaEntityHandler $vendaEntityHandler,
                                VendaItemEntityHandler $vendaItemEntityHandler,
                                ClienteEntityHandler $clienteEntityHandler,
                                ParameterBagInterface $params,
                                MessageBusInterface $bus,
                                SyslogBusiness $syslog)
    {
        $this->appConfigEntityHandler = $appConfigEntityHandler;
        $this->security = $security;
        $this->produtoEntityHandler = $produtoEntityHandler;
        $this->produtoPrecoEntityHandler = $produtoPrecoEntityHandler;
        $this->vendaEntityHandler = $vendaEntityHandler;
        $this->vendaItemEntityHandler = $vendaItemEntityHandler;
        $this->clienteEntityHandler = $clienteEntityHandler;
        $this->params = $params;
        $this->bus = $bus;
        $this->syslog = $syslog->setApp('radx')->setComponent(self::class);
    }

    /**
     * @return mixed
     * @throws ViewException
     */
    private function getMercadoPagoConfigs()
    {
        try {
            if (!$this->mlUser) {
                throw new ViewException('mlUser n/d');
            }
            $cache = new FilesystemAdapter('mercadopago_configs.json', 0, $_SERVER['CROSIER_SESSIONS_FOLDER']);
            return $cache->get('mercadopago_configs.' . preg_replace('/[\W]/', '', $this->mlUser), function (ItemInterface $item) {
                $rsAppConfig = $this->conn->fetchAssociative('SELECT valor FROM cfg_app_config WHERE chave = :chave', ['chave' => 'mercadopago_configs.json']);
                $todos = json_decode($rsAppConfig['valor'], true);
                foreach ($todos as $config) {
                    if ($config['user'] === $this->mlUser) {
                        return $config;
                    }
                }
                throw new ViewException('mlUser n/d');
            });
        } catch (InvalidArgumentException $e) {
            throw new ViewException('Erro ao obter mercadopago_configs.json');
        }
    }

    /**
     * @param VendaPagto $pagto
     * @return mixed|null
     * @throws ViewException
     */
    public function handleTransacaoParaVendaPagto(VendaPagto $pagto)
    {
        if (($pagto->jsonData['integrador'] ?? '') !== 'Mercado Pago') {
            return null;
        }

        if (!($pagto->jsonData['codigo_transacao'] ?? false)) {
            return null;
        }

        try {
            $client = new Client();
            $response = $client->request('GET', $this->getMercadoPagoConfigs()['endpoint_api'] . '/v1/payments/' . $pagto->jsonData['codigo_transacao'],
                [
                    'headers' => [
                        'Content-Type' => 'application/json; charset=UTF-8',
                        'accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->getMercadoPagoConfigs()['token']
                    ],
                ]
            );
            $bodyContents = $response->getBody()->getContents();

            $json = json_decode($bodyContents, true);
            $pagto->jsonData['mercadopago_retorno'] = $json;
            $pagto_jsonData = json_encode($pagto->jsonData);

            $this->conn->update('ven_venda_pagto', ['json_data' => $pagto_jsonData], ['id' => $pagto->getId()]);

            return $json;
        } catch (GuzzleException $e) {
            throw new ViewException('Erro na comunicação', 0, $e);
        } catch (\Throwable $e) {
            throw new ViewException('Erro em handleTransacaoParaVendaPagto', 0, $e);
        }
    }

    public function obterVendas(\DateTime $dtVenda)
    {
        try {
            $dtVendaStr = $dtVenda->format('Y-m-d');
            $dtVendaStr_ini = '2020-01-01T00:00:00.000-03:00'; // $dtVendaStr . 'T00:00:00.000-03:00';
            $dtVendaStr_fim = '2020-12-31T23:59:59.999-03:00';
            $client = new Client();
            $response = $client->request('GET', 'https://api.mercadolibre.com/orders/search?seller=' . $this->getMercadoPagoConfigs()['userid'] . '&order.date_created.from=' . $dtVendaStr_ini . '&order.date_created.to=' . $dtVendaStr_fim,
                [
                    'headers' => [
                        'Content-Type' => 'application/json; charset=UTF-8',
                        'accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->getMercadoPagoConfigs()['token']
                    ],
                ]
            );
            $bodyContents = $response->getBody()->getContents();

            $json = json_decode($bodyContents, true);
            if ($json['results'] ?? false) {
                foreach ($json['results'] as $rVenda) {
                    try {
                        $this->integrarVendaParaCrosier($rVenda);
                    } catch (ViewException $e) {
                        $this->syslog->err('Erro ao integrarVendaParaCrosier - id (ml)' . ($rVenda['id'] ?? 'n/d'));
                    }
                }
            }

            return $json;
        } catch (\Throwable $e) {
            $msg = ExceptionUtils::treatException($e);
            throw new ViewException('Erro em obterVendas (' . $msg . ')', 0, $e);
        }
    }


    private function integrarVendaParaCrosier(array $mlOrder, ?bool $resalvar = false): void
    {
        try {
            if (($mlOrder['status'] ?? '') !== 'paid') {
                $this->syslog->info('Venda não importada (status != paid): ' . ($mlOrder['status'] ?? ''));
                return;
            }

            $conn = $this->vendaEntityHandler->getDoctrine()->getConnection();

            $itens = $mlOrder['order_items'];

            $cliente_cpfcnpj = preg_replace("/[^0-9]/", "", ($mlOrder['buyer']['billing_info']['doc_number'] ?? ''));
            $cliente_nome = trim(($mlOrder['buyer']['first_name'] ?? '') . ' ' . ($mlOrder['buyer']['last_name'] ?? ''));
            $dtPedido = DateTimeUtils::parseDateStr($mlOrder['date_created']);

            $this->syslog->info('Integrando pedido ' . $mlOrder['id'] . ' de ' .
                $dtPedido->format('d/m/Y H:i:s') . ' Cliente: ' . $cliente_nome);


            $venda = $conn->fetchAllAssociative('SELECT * FROM ven_venda WHERE json_data->>"$.canal" = :canal AND json_data->>"$.ecommerce_idPedido" = :ecommerce_idPedido',
                [
                    'canal' => self::CANAL,
                    'ecommerce_idPedido' => $mlOrder['id']
                ]);
            $venda = $venda[0] ?? null;
            if ($venda) {

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
                ['documento' => $cliente_cpfcnpj]);
            /** @var ClienteRepository $repoCliente */
            $repoCliente = $this->vendaEntityHandler->getDoctrine()->getRepository(Cliente::class);
            if ($cliente[0]['id'] ?? false) {
                $cliente = $repoCliente->find($cliente[0]['id']);
            } else {
                $cliente = null;
            }

            if (!$cliente || $resalvar) {

                $cliente = $cliente ?? new Cliente();

                $cliente->documento = $cliente_cpfcnpj;
                $cliente->nome = $mlOrder['cliente_razaosocial'];
                $cliente->jsonData['tipo_pessoa'] = strlen($cliente_cpfcnpj) === 11 ? 'PF' : 'PJ';
                $cliente->jsonData['canal'] = self::CANAL;
                $cliente->jsonData['ecommerce_id'] = $mlOrder['buyer']['id'] ?? '';

                $cliente = $this->clienteEntityHandler->save($cliente);
            }

            $venda->cliente = $cliente;

            $venda->jsonData['canal'] = self::CANAL;
            $venda->jsonData['ecommerce_idPedido'] = $mlOrder['id'];
            $venda->jsonData['ecommerce_status'] = $mlOrder['status'];
            $venda->jsonData['ecommerce_status_descricao'] = $mlOrder['status'];

            $venda->subtotal = 0.0;// a ser recalculado posteriormente
            $venda->desconto = 0.0;// a ser recalculado posteriormente
            $venda->valorTotal = 0.0;// a ser recalculado posteriormente

            $totalProdutos = 0.0;
            foreach ($itens as $item) {
                $totalProdutos = bcadd($totalProdutos, $item['full_unit_price'], 2);
            }

            // Salvo aqui para poder pegar o id
            $this->vendaEntityHandler->save($venda);

            /** @var ProdutoRepository $repoProduto */
            $repoProduto = $this->produtoEntityHandler->getDoctrine()->getRepository(Produto::class);
            $ordem = 1;
            $i = 0;
            $descontoAcum = 0.0;
            $vendaItem = null;
            $totalTaxasItens = 0;
            foreach ($itens as $item) {
                /** @var Produto $produto */
                $produto = null;
                try {
                    // verifica se já existe uma ven_venda com o json_data.ecommerce_idPedido
                    $sProduto = $conn->fetchAssociative('SELECT id FROM est_produto WHERE json_data->>"$.canal" = :canal AND json_data->>"$.ecommerce_id" = :idProduto',
                        [
                            'canal' => self::CANAL,
                            'idProduto' => $item['produto_id']
                        ]);
                    if ($sProduto['id'] ?? false) {
                        $produto = new Produto();
                        $produto->jsonData['canal'] = self::CANAL;
                        $produto->jsonData['ecommerce_id'] = $item['item']['id'];
                        $produto->nome = $item['item']['title'];
                        $produto->codigo = $item['item']['id'];

                        $this->produtoEntityHandler->save($produto, false);

                        $preco = new ProdutoPreco();
                        $preco->produto = $produto;
                        $preco->dtPrecoVenda = $dtPedido;
                        $preco->precoPrazo = $item['full_unit_price'];
                        $this->produtoPrecoEntityHandler->save($preco, false);

                        $this->produtoEntityHandler->save($produto, true);
                    } else {
                        $produto = $repoProduto->find($sProduto['id']);
                    }
                } catch (\Throwable $e) {
                    throw new ViewException('Erro ao integrar venda. Erro ao pesquisar produto (idProduto = ' . $item['produto_id'] . ')');
                }

                $vendaItem = new VendaItem();
                $venda->addItem($vendaItem);
                $vendaItem->descricao = $produto->nome;
                $vendaItem->ordem = $ordem++;
                $vendaItem->devolucao = false;

                $vendaItem->precoVenda = $item['full_unit_price'];
                $vendaItem->qtde = $item['quantity'];
                $vendaItem->subtotal = bcmul($vendaItem->precoVenda, $vendaItem->qtde, 2);
                // Para arredondar para cima

                $descontoAcum = (float)bcadd($descontoAcum, $vendaItem->desconto, 2);
                $vendaItem->produto = $produto;

                $taxaNoItem = $item['sale_fee'];
                $totalTaxasItens = bcadd($taxaNoItem, $totalTaxasItens, 2);
                $vendaItem->jsonData['ecommerce_taxa_venda'] = $taxaNoItem; // taxa cobrada na venda do item
                $vendaItem->jsonData['ecommerce_ml_listing_type_id'] = $item['listing_type_id']; // gold, etc

                $this->vendaItemEntityHandler->save($vendaItem);
                $i++;
            }

            $venda->jsonData['mlOrder'] = $mlOrder;

            $venda->jsonData['ecommerce_total_pago'] = $mlOrder['payments'][0]['total_paid_amount'];
            $venda->jsonData['ecommerce_total_taxas'] = $totalTaxasItens;
            $venda->jsonData['ecommerce_total_frete'] = $mlOrder['payments'][0]['shipping_cost'];
            $venda->jsonData['ecommerce_total_liquido'] = $venda->jsonData['ecommerce_total_pago'] - $totalTaxasItens - $mlOrder['payments'][0]['shipping_cost'];

            $venda->recalcularTotais();


            try {
                $conn->delete('ven_venda_pagto', ['venda_id' => $venda->getId()]);
            } catch (\Throwable $e) {
                $erro = 'Erro ao deletar pagtos da venda (id = "' . $venda['id'] . ')';
                $this->syslog->err($erro);
                throw new \RuntimeException($erro);
            }


            $totalPagto = bcadd($venda->valorTotal, $venda->jsonData['ecommerce_entrega_frete_calculado'] ?? 0.0, 2);

            $integrador = $pagamento['integrador'] ?? 'n/d';

            $vendaPagto = [
                'venda_id' => $venda->getId(),
                'valor_pagto' => $totalPagto,
                'json_data' => [
                    'integrador' => $integrador,
                    'codigo_transacao' => $pagamento['codigo_transacao'] ?? 'n/d',
                    'carteira_id' => $this->getMercadoPagoConfigs()['carteira_id'],
                ],
                'inserted' => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated' => (new \DateTime())->format('Y-m-d H:i:s'),
                'version' => 0,
                'user_inserted_id' => 1,
                'user_updated_id' => 1,
                'estabelecimento_id' => 1
            ];
            $descricaoPlanoPagto = null;

            $vendaPagto['json_data'] = json_encode($vendaPagto['json_data']);

            try {
                $conn->insert('ven_venda_pagto', $vendaPagto);
                $vendaPagtoId = $conn->lastInsertId();
                if ($integrador === 'Mercado Pago') {
                    $eVendaPagto = $this->vendaEntityHandler->getDoctrine()->getRepository(VendaPagto::class)->find($vendaPagtoId);
                    $this->handleTransacaoParaVendaPagto($eVendaPagto);
                }
            } catch (\Throwable $e) {
                throw new ViewException('Erro ao salvar dados do pagamento');
            }


            $venda->jsonData['infoPagtos'] = $descricaoPlanoPagto .
                ': R$ ' . number_format($venda->valorTotal, 2, ',', '.');

            $this->vendaEntityHandler->save($venda);
        } catch (\Throwable $e) {
            $this->syslog->err('Erro ao integrarVendaParaCrosier', $mlOrder['id']);
            throw new ViewException('Erro ao integrarVendaParaCrosier', 0, $e);
        }
    }

}
