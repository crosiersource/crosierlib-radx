<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\ECommerce;


use CrosierSource\CrosierLibBaseBundle\Exception\ViewException;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\VendaPagto;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class IntegradorMercadoPago
 * @package CrosierSource\CrosierLibRadxBundle\Business\ECommerce
 */
class IntegradorMercadoPago
{

    private Connection $conn;

    /**
     * IntegradorMercadoPago constructor.
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return mixed
     * @throws ViewException
     */
    private function getMercadoPagoConfigs()
    {
        try {
            $cache = new FilesystemAdapter('mercadopago_configs.json', 0, $_SERVER['CROSIER_SESSIONS_FOLDER']);
            return $cache->get('mercadopago_configs', function (ItemInterface $item) {
                $rsAppConfig = $this->conn->fetchAssociative('SELECT valor FROM cfg_app_config WHERE chave = :chave', ['chave' => 'mercadopago_configs.json']);
                return json_decode($rsAppConfig['valor'], true);
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

}