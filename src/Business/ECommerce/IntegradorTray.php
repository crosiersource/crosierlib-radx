<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\ECommerce;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Depto;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Produto;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\DeptoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Estoque\ProdutoEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaEntityHandler;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas\VendaItemEntityHandler;
use GuzzleHttp\Client;
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

    private ProdutoEntityHandler $produtoEntityHandler;

    private VendaEntityHandler $vendaEntityHandler;

    private VendaItemEntityHandler $vendaItemEntityHandler;

    private ?array $deptosNaTray = null;


    public function __construct(Security               $security,
                                ParameterBagInterface  $params,
                                SyslogBusiness         $syslog,
                                DeptoEntityHandler     $deptoEntityHandler,
                                ProdutoEntityHandler   $produtoEntityHandler,
                                VendaEntityHandler     $vendaEntityHandler,
                                VendaItemEntityHandler $vendaItemEntityHandler
    )
    {
        $this->security = $security;
        $this->params = $params;
        $this->syslog = $syslog->setApp('radx')->setComponent(self::class);
        $this->deptoEntityHandler = $deptoEntityHandler;
        $this->produtoEntityHandler = $produtoEntityHandler;
        $this->vendaEntityHandler = $vendaEntityHandler;
        $this->vendaItemEntityHandler = $vendaItemEntityHandler;
        $this->client = new Client();
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function autorizarApp(string $code): array
    {
        $r = $this->deptoEntityHandler->getDoctrine()->getConnection()
            ->fetchAssociative('SELECT valor FROM cfg_app_config WHERE app_uuid = :appUUID AND chave = :chave',
                [
                    'appUUID' => $_SERVER['CROSIERAPP_UUID'],
                    'chave' => 'tray.configs.json'
                ]);
        $rs = json_decode($r['valor'], true);
        $url = $this->endpoint . 'web_api/auth';
        $response = $this->client->request('POST', $url, [
            'form_params' => [
                'consumer_key' => $rs['consumer_key'],
                'consumer_secret' => $rs['consumer_secret'],
                'code' => $code,
            ]
        ]);

        $bodyContents = $response->getBody()->getContents();
        $json = json_decode($bodyContents, true);
        return $json;
    }


    public function renewAccessToken(string $refreshToken): array
    {
        try {
            $response = $this->client->request('GET', $this->getEndpoint() . 'web_api/auth?refresh_token=' . $refreshToken);
            $bodyContents = $response->getBody()->getContents();
            $json = json_decode($bodyContents, true);
            return $json;
        } catch (GuzzleException $e) {
            throw new ViewException('Erro - renewAccessToken', 0, $e);
        }
    }


    /**
     * @throws ViewException
     */
    public function integraCategoria(Depto $depto): int
    {
        $syslog_obs = 'depto = ' . $depto->nome . ' (' . $depto->getId() . ')';
        $this->syslog->debug('integraDepto - ini', $syslog_obs);
        $idDeptoTray = null;

        $url = $this->getEndpoint() . 'web_api/categories?access_token=' . $this->accessToken . '&name=' . $depto->nome;
        $response = $this->client->request('GET',
            $url);
        $bodyContents = $response->getBody()->getContents();
        $json = json_decode($bodyContents, true);
        $idDeptoTray = $json['Categories'][0]['Category']['id'] ?? null;


        if (!$idDeptoTray) {
            $this->syslog->info('integraDepto - não existe, enviando...', $syslog_obs);

            $url = $this->getEndpoint() . 'web_api/categories?access_token=' . $this->accessToken;
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
            $url = $this->getEndpoint() . 'web_api/products?access_token=' . $this->accessToken;
            $method = 'POST';
            if ($produto->jsonData['ecommerce_id'] ?? false) {
                //$arrProduto['id'] = $produto->jsonData['ecommerce_id'];
                $url = $this->getEndpoint() . 'web_api/products/' . $produto->jsonData['ecommerce_id'] . '?access_token=' . $this->accessToken;
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
            $url = $this->getEndpoint() . 'web_api/products/variants?access_token=' . $this->accessToken;
            $method = 'POST';
            if ($produto->jsonData['ecommerce_item_id'] ?? false) {
                //$arrProduto['id'] = $produto->jsonData['ecommerce_id'];
                $url = $this->getEndpoint() . 'web_api/products/variants/' . $produto->jsonData['ecommerce_item_id'] . '?access_token=' . $this->accessToken;
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

    public function obterPedido(int $numPedido): array
    {
        $url = $this->getEndpoint() . '/web_api/orders/' . $numPedido . '?refresh_token=' . $refreshToken;
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

    public function obterCliente($idClienteECommerce)
    {
        // TODO: Implement obterCliente() method.
    }

    public function reintegrarVendaParaCrosier(Venda $venda)
    {
        // TODO: Implement reintegrarVendaParaCrosier() method.
    }

    public function integrarVendaParaECommerce(Venda $venda)
    {
        // TODO: Implement integrarVendaParaECommerce() method.
    }

    public function integrarVendaParaECommerce2(int $numPedido)
    {
        try {//https://{api_address}/orders/:id/invoices
            $url = $this->getEndpoint() . 'web_api/orders/' . $numPedido . '/invoices?access_token=' . $this->accessToken;
            $arr = [
                'issue_date' => '2021-08-25',
                'number' => 3,
                'serie' => 99,
                'value' => 10,
                'key' => '41210834411048000104550990000000031135566010',
                'xml_danfe' => '<?xml version="1.0" encoding="UTF-8"?><nfeProc versao="4.00" xmlns="http://www.portalfiscal.inf.br/nfe"><NFe xmlns="http://www.portalfiscal.inf.br/nfe"><infNFe Id="NFe41210834411048000104550990000000031135566010" versao="4.00"><ide><cUF>41</cUF><cNF>13556601</cNF><natOp>VENDA</natOp><mod>55</mod><serie>99</serie><nNF>3</nNF><dhEmi>2021-08-25T11:19:24-03:00</dhEmi><dhSaiEnt>2021-08-25T11:19:29-03:00</dhSaiEnt><tpNF>1</tpNF><idDest>1</idDest><cMunFG>4119905</cMunFG><tpImp>1</tpImp><tpEmis>1</tpEmis><cDV>0</cDV><tpAmb>2</tpAmb><finNFe>1</finNFe><indFinal>1</indFinal><indPres>1</indPres><procEmi>0</procEmi><verProc>02014-6</verProc></ide><emit><CNPJ>34411048000104</CNPJ><xNome>IPE UNIFORMES LTDA</xNome><xFant>IPE UNIFORMES LTDA</xFant><enderEmit><xLgr>RUA BITTENCOURT SAMPAIO</xLgr><nro>598</nro><xBairro>NOVA RUSSIA</xBairro><cMun>4119905</cMun><xMun>PONTA GROSSA</xMun><UF>PR</UF><CEP>84053030</CEP><cPais>1058</cPais><xPais>BRASIL</xPais><fone>4230271793</fone></enderEmit><IE>9082234117</IE><CRT>1</CRT></emit><dest><CPF>40080286097</CPF><xNome>NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL</xNome><enderDest><xLgr>AV DOM PEDRO II</xLgr><nro>337</nro><xBairro>NOVA RUSSIA</xBairro><cMun>4119905</cMun><xMun>Ponta Grossa</xMun><UF>PR</UF><CEP>84053000</CEP><cPais>1058</cPais><xPais>BRASIL</xPais><fone>42999888777</fone></enderDest><indIEDest>2</indIEDest></dest><det nItem="1"><prod><cProd>1266051301</cProd><cEAN>SEM GTIN</cEAN><xProd>NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL</xProd><NCM>63052000</NCM><CFOP>5102</CFOP><uCom>UN</uCom><qCom>1</qCom><vUnCom>10</vUnCom><vProd>10.00</vProd><cEANTrib>SEM GTIN</cEANTrib><uTrib>UN</uTrib><qTrib>1</qTrib><vUnTrib>10.00</vUnTrib><indTot>1</indTot></prod><imposto><ICMS><ICMSSN102><orig>0</orig><CSOSN>103</CSOSN></ICMSSN102></ICMS><PIS><PISNT><CST>07</CST></PISNT></PIS><COFINS><COFINSNT><CST>07</CST></COFINSNT></COFINS></imposto></det><total><ICMSTot><vBC>0.00</vBC><vICMS>0.00</vICMS><vICMSDeson>0.00</vICMSDeson><vFCP>0.00</vFCP><vBCST>0.00</vBCST><vST>0.00</vST><vFCPST>0.00</vFCPST><vFCPSTRet>0.00</vFCPSTRet><vProd>10.00</vProd><vFrete>0.00</vFrete><vSeg>0.00</vSeg><vDesc>0.00</vDesc><vII>0.00</vII><vIPI>0.00</vIPI><vIPIDevol>0.00</vIPIDevol><vPIS>0.00</vPIS><vCOFINS>0.00</vCOFINS><vOutro>0.00</vOutro><vNF>10.00</vNF><vTotTrib>0.00</vTotTrib></ICMSTot></total><transp><modFrete>9</modFrete></transp><pag><detPag><tPag>01</tPag><vPag>10.00</vPag></detPag></pag><infRespTec><CNPJ>34411048000104</CNPJ><xContato>CARLOS EDUARDO PAULUK</xContato><email>carlospauluk@gmail.com</email><fone>4230271793</fone></infRespTec></infNFe><Signature xmlns="http://www.w3.org/2000/09/xmldsig#"><SignedInfo><CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/><SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/><Reference URI="#NFe41210834411048000104550990000000031135566010"><Transforms><Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/></Transforms><DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/><DigestValue>a4fy5L+vCAZAc02hHRbpDPBJRtQ=</DigestValue></Reference></SignedInfo><SignatureValue>pnsTUShOGjBBrmSt/XYZlkGUbg50mVtdrQEkJq6ZO8NWuQtuZ3TQV8uR5YxudU6w8T9kNQz1Mwv6loNgcAMU3fQ0iNmVYTGuV7LUyjSYFJqKvaREomQwUua9wUi4cg9ajriz3Kv4xpJf7rXg5idL3f79x9t95hwBN21vIazYEY8aIe/8IscPII4iJUvvLSm7e2aGe74hfBivLb98b2APG8yLdUxzIt4Pd1NL2scZ09XG9aIJIotL+Q4u3vM1XEjEkghtG7a3ifCdqVbAQ5DjvVodOWsZVdJAZaFSxt7SCfKF+fn7o389iyAUZ4amHiArLOxuHRW7wEtvW9knY92k5A==</SignatureValue><KeyInfo><X509Data><X509Certificate>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</X509Certificate></X509Data></KeyInfo></Signature></NFe><protNFe versao="4.00"><infProt><tpAmb>2</tpAmb><verAplic>PR-v4_7_35</verAplic><chNFe>41210834411048000104550990000000031135566010</chNFe><dhRecbto>2021-08-25T11:19:46-03:00</dhRecbto><nProt>141210000719025</nProt><digVal>a4fy5L+vCAZAc02hHRbpDPBJRtQ=</digVal><cStat>100</cStat><xMotivo>Autorizado o uso da NF-e</xMotivo></infProt></protNFe></nfeProc>'
            ];
            $jsonRequest = json_encode($arr, JSON_UNESCAPED_SLASHES);
            $method = 'POST';
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


    public function atualizaDadosEnvio(int $numPedido)
    {
        try {
            $url = $this->getEndpoint() . 'web_api/orders/' . $numPedido . '?access_token=' . $this->accessToken;
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
            $url = $this->getEndpoint() . 'web_api/orders/cancel/' . $numPedido . '?access_token=' . $this->accessToken;
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


}
