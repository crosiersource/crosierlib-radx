<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

/**
 * Class TipoNotaFiscal
 * @package CrosierSource\CrosierLibRadxBundle\Entity
 * @author Carlos Eduardo Pauluk
 */
class TipoNotaFiscal
{

    const NFE = array(
        'codigo' => 55,
        'label' => 'Nota Fiscal'
    );

    const NFCE = array(
        'codigo' => 65,
        'label' => 'Nota Fiscal Consumidor'
    );

    const ALL = array(
        'NFE' => TipoNotaFiscal::NFE,
        'NFCE' => TipoNotaFiscal::NFCE
    );


    public static function getChoices(): array
    {
        $arr = array();
        foreach (TipoNotaFiscal::ALL as $status) {
            $arr[$status['label']] = $status['codigo'];
        }
        return $arr;
    }

    public static function get($key): array
    {
        $all = TipoNotaFiscal::ALL;
        return $all[$key];
    }

    public static function getByCodigo($codigo): ?array
    {
        foreach (self::ALL as $tipo) {
            if ($tipo['codigo'] === (int)$codigo) {
                return $tipo;
            }
        }
        return null;
    }

}