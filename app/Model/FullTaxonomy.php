<?php

/*
 * Represents NCBI taxonomy as present in `full_taxonomy` table.
 */

class FullTaxonomy extends AppModel {
    var $useTable = 'full_taxonomy';

    function findClades($species_array) {
        $result = [];
        if (count($species_array) == 0) {
            return $result;
        }

        $tax_data = [];
        $species_string = "('" . implode("','", $species_array) . "')";
        $query = "SELECT * FROM `full_taxonomy` WHERE `txid` IN $species_string ORDER BY `tax` ASC";
        $res = $this->query($query);
        $clade_to_species = [];
        $parents_to_child_clade = [];

        foreach ($res as $r) {
            $tid = $r['full_taxonomy']['txid'];
            $scname = $r['full_taxonomy']['scname'];
            $tax_string = $r['full_taxonomy']['tax'];
            // Note: reverse array because clades are stored from most specific to most general.
            $tax_split = array_reverse(explode(';', $tax_string));

            for ($i = 0; $i < count($tax_split); $i++) {
                $ts = trim($tax_split[$i]);
                if (!array_key_exists($ts, $clade_to_species)) {
                    $clade_to_species[$ts] = [];
                }
                $clade_to_species[$ts][] = $tid;

                if (!array_key_exists($ts, $parents_to_child_clade)) {
                    $parents_to_child_clade[$ts] = [];
                }
                for ($j = $i + 1; $j < count($tax_split); $j++) {
                    $child_clade = trim($tax_split[$j]);
                    if (!array_key_exists($child_clade, $parents_to_child_clade[$ts])) {
                        $parents_to_child_clade[$ts][$child_clade] = $child_clade;
                    }
                }
            }
        }

        //ok, now select the unique "smallest" clades which contain more than 1 species
        foreach ($clade_to_species as $clade => $species) {
            $num_species = count($species);
            if ($num_species > 1) {
                //check child clades
                $accept = true;
                foreach ($parents_to_child_clade[$clade] as $child_clade) {
                    $num_species_child_clade = count($clade_to_species[$child_clade]);
                    if ($num_species_child_clade > 1 && $num_species_child_clade == $num_species) {
                        $accept = false;
                        break;
                    }
                }
                if ($accept) {
                    $result[$clade] = $species;
                }
            }
        }

        //ok, now create better parent-to-child clade representation.
        $final_parents_to_child_clade = [];
        foreach ($result as $clade => $species) {
            $child_clades = [];
            foreach ($parents_to_child_clade[$clade] as $child_clade) {
                if (array_key_exists($child_clade, $result)) {
                    $child_clades[] = $child_clade;
                }
            }
            $final_parents_to_child_clade[$clade] = $child_clades;
        }

        //pr($parents_to_child_clade);
        //pr($final_parents_to_child_clade);

        //query the database, and get the full string representation for this top clade
        $temp = [];
        foreach ($res as $r) {
            $txid = $r['full_taxonomy']['txid'];
            $scname = $r['full_taxonomy']['scname'];
            $tax_string = $r['full_taxonomy']['tax'];
            // $tax_split = explode(";", $tax_string);
            // In the new DB structure, the order of the full taxonomy is reversed.
            // So a quick fix to get this model working is to reverse the order of the retrieved array
            $tax_split = array_reverse(explode(';', $tax_string));
            $local_tmp = '';
            for ($i = 0; $i < count($tax_split); $i++) {
                $ts = trim($tax_split[$i]);
                if (array_key_exists($ts, $final_parents_to_child_clade)) {
                    $local_tmp = $local_tmp . '' . $ts . ';';
                }
            }
            $temp[] = $local_tmp . '' . $txid;
        }
        $full_tree = $this->explodeTree($temp, ';');

        $final_result = [
            'parent_child_clades' => $final_parents_to_child_clade,
            'clade_species_tax' => $result,
            'full_tree' => $full_tree
        ];
        return $final_result;
    }

    function explodeTree($array, $delimiter = '_') {
        if (!is_array($array)) {
            return false;
        }
        $splitRE = '/' . preg_quote($delimiter, '/') . '/';

        $returnArr = [];
        $i = 0;
        foreach ($array as $val) {
            // Get parent parts and the current leaf
            $parts = preg_split($splitRE, $val, -1, PREG_SPLIT_NO_EMPTY);
            //if($i==42){pr($val);pr($parts);}
            $leafPart = array_pop($parts);

            // Build parent structure
            // Might be slow for really deep and large structures
            $parentArr = &$returnArr;
            foreach ($parts as $part) {
                if (!isset($parentArr[$part])) {
                    $parentArr[$part] = [];
                } elseif (!is_array($parentArr[$part])) {
                    $parentArr[$part] = [];
                }
                $parentArr = &$parentArr[$part];
            }
            // Add the final part to the structure
            if (empty($parentArr[$leafPart])) {
                if (is_numeric($leafPart)) {
                    $parentArr[] = $leafPart;
                } else {
                    $parentArr[$leafPart] = [];
                }
            }
            $i++;
        }
        return $returnArr;
    }
}
