<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;

use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\DepreciacaoPreco;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class DepreciacaoPrecoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return DepreciacaoPreco::class;
    }

    /**
     * @param int $prazo
     * @return float|null
     */
    public function findDepreciacaoByPrazo(int $prazo): ?float
    {
        if ($prazo >= 136) {
            return 0.894;
        } elseif ($prazo >= 121) {
            return 0.904;
        } elseif ($prazo >= 106) {
            return 0.914;
        } elseif ($prazo >= 91) {
            return 0.924;
        } elseif ($prazo >= 76) {
            return 0.935;
        } elseif ($prazo >= 61) {
            return 0.946;
        } elseif ($prazo >= 46) {
            return 0.958;
        } elseif ($prazo >= 31) {
            return 0.969;
        } elseif ($prazo >= 16) {
            return 0.981;
        } else {
            return 1;
        }
    }
}
