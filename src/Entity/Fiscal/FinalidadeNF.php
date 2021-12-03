<?php

namespace CrosierSource\CrosierLibRadxBundle\Entity\Fiscal;

/**
 * @author Carlos Eduardo Pauluk
 */
class FinalidadeNF
{

    public const NORMAL = array(
        'key' => 'NORMAL',
        'codigo' => 1,
        'label' => 'NF-e normal'
    );

    public const COMPLEMENTAR = array(
        'key' => 'COMPLEMENTAR',
        'codigo' => 2,
        'label' => 'NF-e complementar'
    );

    public const AJUSTE = array(
        'key' => 'AJUSTE',
        'codigo' => 3,
        'label' => 'NF-e de ajuste'
    );

    public const DEVOLUCAO = array(
        'key' => 'DEVOLUCAO',
        'codigo' => 4,
        'label' => 'Devolução de mercadoria'
    );


    public const ALL = array(
        FinalidadeNF::NORMAL,
        FinalidadeNF::COMPLEMENTAR,
        FinalidadeNF::AJUSTE,
        FinalidadeNF::DEVOLUCAO
    );


    /**
     * @return array
     */
    public static function getChoices(): array
    {
        $arr = array();
        foreach (self::ALL as $e) {
            $arr[$e['label']] = $e['codigo'];
        }
        return $arr;
    }


    /**
     * @param $key
     * @return array|null
     */
    public static function get($key): ?array
    {
        foreach (self::ALL as $e) {
            if ($e['key'] === $key) {
                return $e;
            }
        }
        return null;
    }


    /**
     * @param $codigo
     * @return array|null
     */
    public static function getByCodigo($codigo): ?array
    {
        foreach (FinalidadeNF::ALL as $e) {
            if ($e['codigo'] === (int)$codigo) {
                return $e;
            }
        }
        return null;
    }


}