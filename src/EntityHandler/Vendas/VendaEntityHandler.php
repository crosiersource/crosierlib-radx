<?php

namespace CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas;

use CrosierSource\CrosierLibBaseBundle\Business\Config\SyslogBusiness;
use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use CrosierSource\CrosierLibBaseBundle\EntityHandler\EntityHandler;
use CrosierSource\CrosierLibBaseBundle\Repository\Config\AppConfigRepository;
use CrosierSource\CrosierLibRadxBundle\Business\Vendas\VendaBusiness;
use CrosierSource\CrosierLibRadxBundle\Entity\CRM\Cliente;
use CrosierSource\CrosierLibRadxBundle\Entity\Vendas\Venda;
use CrosierSource\CrosierLibRadxBundle\EntityHandler\CRM\ClienteEntityHandler;
use CrosierSource\CrosierLibRadxBundle\Repository\CRM\ClienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class VendaEntityHandler
 * @package CrosierSource\CrosierLibRadxBundle\EntityHandler\Vendas
 *
 * @author Carlos Eduardo Pauluk
 */
class VendaEntityHandler extends EntityHandler
{

    private VendaBusiness $vendaBusiness;

    private ClienteEntityHandler $clienteEntityHandler;

    /**
     * VendaItemEntityHandler constructor.
     * @param EntityManagerInterface $doctrine
     * @param Security $security
     * @param ParameterBagInterface $parameterBag
     * @param SyslogBusiness $syslog
     * @param VendaBusiness $vendaBusiness
     * @param ClienteEntityHandler $clienteEntityHandler
     */
    public function __construct(EntityManagerInterface $doctrine,
                                Security $security,
                                ParameterBagInterface $parameterBag,
                                SyslogBusiness $syslog,
                                VendaBusiness $vendaBusiness,
                                ClienteEntityHandler $clienteEntityHandler)
    {
        parent::__construct($doctrine, $security, $parameterBag, $syslog->setApp('radx')->setComponent(self::class));
        $this->vendaBusiness = $vendaBusiness;
        $this->clienteEntityHandler = $clienteEntityHandler;
    }

    public function beforeSave(/** @var Venda $venda */ $venda)
    {
        if ($venda->jsonData['ecommerce_status'] ?? false) {
            /** @var AppConfigRepository $repoAppConfig */
            $repoAppConfig = $this->getDoctrine()->getRepository(AppConfig::class);
            $jsonMetadata = json_decode($repoAppConfig->findByChave('ven_venda_json_metadata'), true);
            if (!($jsonMetadata['campos']['ecommerce_status']['sugestoes'][$venda->jsonData['ecommerce_status']] ?? false)) {
                throw new \RuntimeException('ecommerce_status N/D');
            }
            $venda->jsonData['ecommerce_status_descricao'] = $jsonMetadata['campos']['ecommerce_status']['sugestoes'][$venda->jsonData['ecommerce_status']];
        }

        if (!$venda->cliente) {
            /** @var ClienteRepository $repoCliente */
            $repoCliente = $this->getDoctrine()->getRepository(Cliente::class);

            if ($venda->jsonData['cliente_documento'] ?? false) {
                $documento = preg_replace("/[^G^0-9]/", "", strtoupper($venda->jsonData['cliente_documento']));
                /** @var ClienteRepository $repoCliente */
                $repoCliente = $this->getDoctrine()->getRepository(Cliente::class);
                /** @var Cliente $cliente */
                $cliente = $repoCliente->findOneBy(['documento' => $documento]);
                if ($cliente) {
                    $venda->cliente = $cliente;
                }
            }

            if (!$venda->cliente) {
                if ($venda->jsonData['cliente_nome'] ?? false) {
                    $cliente = new Cliente();
                    $cliente->nome = $venda->jsonData['cliente_nome'];
                    $cliente->jsonData['tipo_pessoa'] = 'PF';
                    $documento = null;
                    if ($venda->jsonData['cliente_documento'] ?? false) {
                        $documento = preg_replace("/[^G^0-9]/", "", strtoupper($venda->jsonData['cliente_documento']));
                    }
                    $cliente->documento = $documento ?? $repoCliente->findProxGDocumento();
                    $cliente->jsonData['email'] = $venda->jsonData['cliente_email'] ?? '';
                    $cliente->jsonData['fone1'] = $venda->jsonData['cliente_fone'] ?? '';
                    $this->clienteEntityHandler->save($cliente);
                    $venda->cliente = $cliente;
                    $venda->jsonData['cliente_fone'] = $venda->cliente->jsonData['fone1'];
                    $venda->jsonData['cliente_email'] = $venda->cliente->jsonData['email'];
                } else {
                    /** @var Cliente $consumidorNaoIdentificado */
                    $consumidorNaoIdentificado = $repoCliente->findOneBy(['documento' => '99999999999']);
                    $venda->cliente = $consumidorNaoIdentificado;
                }
            }
        }

        $alterouCliente = false;
        if ($venda->jsonData['cliente_fone'] ?? false) {
            if (!($venda->cliente->jsonData['fone1'] ?? false) || ($venda->cliente->jsonData['fone1'] !== $venda->jsonData['cliente_fone'])) {
                $venda->cliente->jsonData['fone1'] = $venda->jsonData['cliente_fone'];
                $alterouCliente = true;
            }
        }
        if ($venda->jsonData['cliente_email'] ?? false) {
            if (!($venda->cliente->jsonData['email'] ?? false) || ($venda->cliente->jsonData['email'] !== $venda->jsonData['cliente_email'])) {
                $venda->cliente->jsonData['email'] = $venda->jsonData['cliente_email'];
                $alterouCliente = true;
            }
        }
        if ($alterouCliente) {
            $this->clienteEntityHandler->save($venda->cliente);
        }

        $venda->jsonData['cliente_documento'] = $venda->cliente->documento;
        $venda->jsonData['cliente_nome'] = $venda->cliente->nome;

        $venda->subtotal = $venda->subtotal ?? 0.0;
        $venda->desconto = $venda->desconto ?? 0.0;
        $venda->valorTotal = $venda->valorTotal ?? 0.0;
    }

    public function getEntityClass(): string
    {
        return Venda::class;
    }
}