<?php

namespace tomzx\TomRochette;

use Grav\Common\GravTrait;
use Grav\Common\Taxonomy;

class TaxonomyList
{
    use GravTrait;

    /**
     * Get taxonomy list.
     *
     * @return array
     */
    public function get($filters = [], $operator = 'and')
    {
        return $this->build($filters, $operator);
    }

    /**
     * @internal
     */
    protected function build($filters = [], $operator = 'and')
    {
        /** @var Taxonomy $taxonomy_map */
        $taxonomyMap = self::getGrav()['taxonomy'];

        if ($filters) {
            $taxonomyPages = $taxonomyMap->findTaxonomy($filters, $operator);
            $taxonomy = new Taxonomy(self::getGrav());
            foreach ($taxonomyPages as $page) {
                $taxonomy->addTaxonomy($page);
            }
        } else {
            $taxonomy = $taxonomyMap;
        }

        $taxonomylist = $taxonomy->taxonomy();
        $cache = self::getGrav()['cache'];
        $hash = hash('md5', serialize($taxonomylist));

        if ($taxonomy = $cache->fetch($hash)) {
            return $taxonomy;
        }

        $newlist = [];
        foreach ($taxonomylist as $x => $y) {
            $partial = [];
            foreach ($taxonomylist[$x] as $key => $value) {
                $taxonomylist[$x][strval($key)] = count($value);
                $partial[strval($key)] = count($value);
            }
            arsort($partial);
            $newlist[$x] = $partial;
        }
        $cache->save($hash, $newlist);
        return $newlist;
    }
}
