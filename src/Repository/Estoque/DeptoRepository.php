<?php

namespace CrosierSource\CrosierLibRadxBundle\Repository\Estoque;


use CrosierSource\CrosierLibBaseBundle\Repository\FilterRepository;
use CrosierSource\CrosierLibRadxBundle\Entity\Estoque\Depto;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 *
 * @author Carlos Eduardo Pauluk
 *
 */
class DeptoRepository extends FilterRepository
{

    public function getEntityClass(): string
    {
        return Depto::class;
    }

    /**
     * @return false|string
     */
    public function buildDeptosGruposSubgruposSelect2(?int $deptoIdSelected = null, ?int $grupoIdSelected = null, ?int $subgrupoIdSelected = null)
    {
        $chaveCache = ($deptoIdSelected ?? 'null') . '_' . ($grupoIdSelected ?? 'null') . '_' . ($subgrupoIdSelected ?? 'null');

        $cache = new FilesystemAdapter($_SERVER['CROSIERAPP_ID'] . '.buildDeptosGruposSubgruposSelect2', 0, $_SERVER['CROSIER_SESSIONS_FOLDER']);

        return $cache->get($chaveCache, function (ItemInterface $item) use ($deptoIdSelected, $grupoIdSelected, $subgrupoIdSelected) {

            $subgrupos = $this->getEntityManager()->getConnection()->fetchAll('SELECT * FROM est_subgrupo ORDER BY json_data->>"$.depto_codigo", json_data->>"$.grupo_codigo", codigo');

            $deptos = [];
            foreach ($subgrupos as $subgrupo) {
                if (!$subgrupo['json_data']) continue;
                $jsonData = json_decode($subgrupo['json_data'], true);
                if (!isset($deptos[$jsonData['depto_id']])) {
                    $deptos[$jsonData['depto_id']] = [
                        'id' => $jsonData['depto_id'],
                        'codigo' => $jsonData['depto_codigo'],
                        'nome' => $jsonData['depto_nome']
                    ];
                }
                if (!isset($deptos[$jsonData['depto_id']]['grupos'][$jsonData['grupo_id']])) {
                    $deptos[$jsonData['depto_id']]['grupos'][$jsonData['grupo_id']] = [
                        'id' => $jsonData['grupo_id'],
                        'codigo' => $jsonData['grupo_codigo'],
                        'nome' => $jsonData['grupo_nome']
                    ];
                }
                $deptos[$jsonData['depto_id']]['grupos'][$jsonData['grupo_id']]['subgrupos'][$subgrupo['id']] = [
                    'id' => $subgrupo['id'],
                    'codigo' => $subgrupo['codigo'],
                    'nome' => $subgrupo['nome']
                ];
            }

            $sDeptos = [];

            $sDeptos[0] = [
                'id' => '',
                'text' => 'Selecione...'
            ];
            $d = 1;
            foreach ($deptos as $depto) {
                $sDeptos[$d] = [
                    'id' => $depto['id'],
                    'text' => $depto['codigo'] . ' - ' . $depto['nome'],
                    'selected' => (int)$depto['id'] === $deptoIdSelected
                ];
                $sDeptos[$d]['grupos'][0] = [
                    'id' => '',
                    'text' => 'Selecione...'
                ];
                $g = 1;
                foreach ($depto['grupos'] as $grupo) {
                    $sDeptos[$d]['grupos'][$g] = [
                        'id' => $grupo['id'],
                        'text' => $grupo['codigo'] . ' - ' . $grupo['nome'],
                        'selected' => (int)$grupo['id'] === $grupoIdSelected
                    ];
                    $sDeptos[$d]['grupos'][$g]['subgrupos'][] = [
                        'id' => '',
                        'text' => 'Selecione...'
                    ];
                    foreach ($grupo['subgrupos'] as $subgrupo) {
                        $sDeptos[$d]['grupos'][$g]['subgrupos'][] = [
                            'id' => $subgrupo['id'],
                            'text' => $subgrupo['codigo'] . ' - ' . $subgrupo['nome'],
                            'selected' => (int)$subgrupo['id'] === $subgrupoIdSelected
                        ];
                    }
                    $g++;
                }
                $d++;
            }

            return json_encode($sDeptos);

        });


    }

}
