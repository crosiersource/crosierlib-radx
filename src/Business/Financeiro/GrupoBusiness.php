<?php

namespace CrosierSource\CrosierLibRadxBundle\Business\Financeiro;


use CrosierSource\CrosierLibBaseBundle\Utils\DateTimeUtils\DateTimeUtils;
use CrosierSource\CrosierLibBaseBundle\Utils\StringUtils\StringUtils;
use CrosierSource\CrosierLibRadxBundle\Entity\Financeiro\Grupo;

/**
 * @author Carlos Eduardo Pauluk
 */
class GrupoBusiness
{

    public static function findDtVenctoByDtMoviment(Grupo $grupo, \DateTime $dtMoviment): \DateTime
    {
        if ($dtMoviment->format('d') >= $grupo->diaInicioAprox) {
            $mesAno = DateTimeUtils::incMes($dtMoviment);
        } else {
            $mesAno = $dtMoviment;
        }
        return DateTimeUtils::parseDateStr($mesAno->format('Y-m') . '-' . StringUtils::strpad($grupo->diaVencto, 2));
    }

}