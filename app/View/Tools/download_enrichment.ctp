<?php
if (isset($file_name)) {
    header("Content-disposition: attachment; filename=$file_name");
    header('Content-type: text/plain');
    if (isset($error)) {
        echo $error . "\n";
    } else {
        if ($type == 'go') {
            echo "#Aspect\tGO\tEnrichment_log2\tq-value\tsubset_ratio\tdescription\n";
            foreach ($go_types as $go_type => $go_ids) {
                foreach ($go_ids as $go_id) {
                    $res = $result[$go_id];
                    $desc = $go_descriptions[$go_id][0];
                    echo implode("\t", [
                        $go_type,
                        $go_id,
                        $res['enrichment'],
                        $res['p-value'],
                        $res['subset_ratio'],
                        $desc
                    ]) . "\n";
                }
            }
        } elseif ($type == 'ipr') {
            echo "#Type\tProteinDomain\tEnrichment_log2\tq-value\tsubset_ratio\tdescription\n";
            foreach ($result as $res) {
                $desc = $ipr_descriptions[$res['ipr']][0];
                $ipr_type = $ipr_types[$res['ipr']][0];
                echo implode("\t", [
                    $ipr_type,
                    $res['ipr'],
                    $res['enrichment'],
                    $res['p-value'],
                    $res['subset_ratio'],
                    $desc
                ]) . "\n";
            }
        } elseif ($type == 'ko') {
            echo "#KO\tEnrichment_log2\tq-value\tsubset_ratio\tdescription\n";
            foreach ($result as $res) {
                $desc = $ko_descriptions[$res['ko']][0];
                echo implode("\t", [$res['ko'], $res['enrichment'], $res['p-value'], $res['subset_ratio'], $desc]) .
                    "\n";
            }
        }
    }
} else {
    if (isset($error)) {
        echo $error . "\n";
    }
}
?>
