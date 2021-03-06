<?php


namespace CrosierSource\CrosierLibRadxBundle\Business\ECommerce;


use CrosierSource\CrosierLibBaseBundle\Entity\Config\AppConfig;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Carlos Eduardo Pauluk
 */
class IntegradorECommerceFactory
{

    private IntegradorWebStorm $integradorWebStorm;

    private EntityManagerInterface $doctrine;

    /**
     * IntegradorECommerceFactory constructor.
     * @param IntegradorWebStorm $integradorWebStorm
     * @param EntityManagerInterface $doctrine
     */
    public function __construct(IntegradorWebStorm $integradorWebStorm, EntityManagerInterface $doctrine)
    {
        $this->integradorWebStorm = $integradorWebStorm;
        $this->doctrine = $doctrine;
    }


    public function getIntegrador(): IntegradorWebStorm
    {
        $repoAppConfig = $this->doctrine->getRepository(AppConfig::class);
        $integrador = $repoAppConfig->findByChave('ecomm_info_integra');

        switch ($integrador) {
            case 'WEBSTORM':
                return $this->integradorWebStorm;
                break;
            default:
                throw new \RuntimeException('integrador n/d');
        }
    }


}
