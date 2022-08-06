<?php
/*
 * Model for a reference database's annotation source data.
 */
class AnnotSources extends AppModel {
    var $name = 'AnnotSources';
    var $useTable = 'annot_sources';

    function getSpeciesCommonNames() {
        $query = 'SELECT `species`,`common_name` FROM `annot_sources` ORDER BY `common_name` ASC ';
        $res = $this->query($query);
        $result = [];
        foreach ($res as $r) {
            $sp = $r['annot_sources']['species'];
            $cn = $r['annot_sources']['common_name'];
            $result[$sp] = $cn;
        }
        return $result;
    }

    function getSpeciesFromTaxIds($tax_ids) {
        $result = [];
        if (count($tax_ids) == 0) {
            return $result;
        }
        $tax_ids_string = "('" . implode("','", $tax_ids) . "')";
        $query = 'SELECT `species` FROM `annot_sources` WHERE `tax_id` IN ' . $tax_ids_string;
        $res = $this->query($query);
        foreach ($res as $r) {
            $spec = $r['annot_sources']['species'];
            $result[$spec] = $spec;
        }
        return $result;
    }

    // Eggnog specific: parse `taxonomic_levels` table to get NOG_level (i.e. tax scope) -> Name correspondence
    // TODO: move this somewhere else?
    function getEggnogTaxLevels() {
        $tax_levels = [];
        $query = 'SELECT `scope`,`name` FROM `taxonomic_levels`;';
        $res = $this->query($query);
        foreach ($res as $r) {
            $tax_levels[$r['taxonomic_levels']['scope']] = $r['taxonomic_levels']['name'];
        }
        return $tax_levels;
    }
}
