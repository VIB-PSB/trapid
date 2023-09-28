<?php
if (isset($file_name)) {
    header("Content-disposition: attachment; filename=$file_name");
    header('Content-type: text/plain');
    if ($type == 'ipr') {
        if ($comparison == 1) {
            echo "#ProteinDomain\tDescription\tSubset1\tSubset2\tPerc1\tPerc2\tRatio1/2\n";
            foreach ($data_subset1 as $k => $v) {
                if (array_key_exists($k, $data_subset2)) {
                    echo $k . "\t" . $descriptions[$k] . "\t" . $data_subset1[$k] . "\t" . $data_subset2[$k] . "\t";
                    $ratio_subset1 = (100 * $data_subset1[$k]) / $subset1_size;
                    $ratio_subset2 = (100 * $data_subset2[$k]) / $subset2_size;
                    echo number_format($ratio_subset1, 1) . "%\t";
                    echo number_format($ratio_subset2, 1) . "%\t";
                    echo number_format($ratio_subset1 / $ratio_subset2, 2) . "\n";
                }
            }
        } elseif ($comparison == 2) {
            echo "#ProteinDomain\tDescription\tSubset1\tPerc1\n";
            foreach ($data_subset1 as $k => $v) {
                if (!array_key_exists($k, $data_subset2)) {
                    echo $k . "\t" . $descriptions[$k] . "\t" . $data_subset1[$k] . "\t";
                    $ratio_subset1 = (100 * $data_subset1[$k]) / $subset1_size;
                    echo number_format($ratio_subset1, 1) . "%\n";
                }
            }
        } elseif ($comparison == 3) {
            echo "#ProteinDomain\tDescription\tSubset2\tPerc2\n";
            foreach ($data_subset2 as $k => $v) {
                if (!array_key_exists($k, $data_subset1)) {
                    echo $k . "\t" . $descriptions[$k] . "\t" . $data_subset2[$k] . "\t";
                    $ratio_subset2 = (100 * $data_subset2[$k]) / $subset2_size;
                    echo number_format($ratio_subset2, 1) . "%\n";
                }
            }
        }
    } elseif ($type == 'go') {
        $go_type = $subtype;
        if ($comparison == 1) {
            echo "#GO\tDescription\tSubset1\tSubset2\tPerc1\tPerc2\tRatio1/2\n";
            foreach ($data_subset1 as $k => $v) {
                if (array_key_exists($k, $data_subset2) && $go_types[$k] == $go_type) {
                    echo $k . "\t" . $descriptions[$k] . "\t" . $data_subset1[$k] . "\t" . $data_subset2[$k] . "\t";
                    $ratio_subset1 = (100 * $data_subset1[$k]) / $subset1_size;
                    $ratio_subset2 = (100 * $data_subset2[$k]) / $subset2_size;
                    echo number_format($ratio_subset1, 1) . "%\t";
                    echo number_format($ratio_subset2, 1) . "%\t";
                    echo number_format($ratio_subset1 / $ratio_subset2, 2) . "\n";
                }
            }
        } elseif ($comparison == 2) {
            echo "#GO\tDescription\tSubset1\tPerc1\n";
            foreach ($data_subset1 as $k => $v) {
                if (!array_key_exists($k, $data_subset2) && $go_types[$k] == $go_type) {
                    echo $k . "\t" . $descriptions[$k] . "\t" . $data_subset1[$k] . "\t";
                    $ratio_subset1 = (100 * $data_subset1[$k]) / $subset1_size;
                    echo number_format($ratio_subset1, 1) . "%\n";
                }
            }
        } elseif ($comparison == 3) {
            echo "#GO\tDescription\tSubset2\tPerc2\n";
            foreach ($data_subset2 as $k => $v) {
                if (!array_key_exists($k, $data_subset1) && $go_types[$k] == $go_type) {
                    echo $k . "\t" . $descriptions[$k] . "\t" . $data_subset2[$k] . "\t";
                    $ratio_subset2 = (100 * $data_subset2[$k]) / $subset2_size;
                    echo number_format($ratio_subset2, 1) . "%\n";
                }
            }
        }
    }
}
?>
