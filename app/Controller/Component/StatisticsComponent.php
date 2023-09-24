<?php
App::uses('Component', 'Controller');

class StatisticsComponent extends Component {
    function makeVennOverview($transcript2labels) {
        $result = [];
        foreach ($transcript2labels as $transcript => $labels) {
            $labels = array_unique($labels);
            sort($labels);
            $label_string = implode(';;;', $labels);
            if (!array_key_exists($label_string, $result)) {
                $result[$label_string] = 0;
            }
            $result[$label_string]++;
        }
        return $result;
    }

    function create_json_data_infovis($data, $data_label) {
        $values = $data['values'];
        $labels = $data['labels'];
        $result = [];
        $new_values = [];
        for ($i = 0; $i < count($values); $i++) {
            $val = [];
            $val['label'] = $labels[$i];
            $val['values'] = [$values[$i]];
            $new_values[] = $val;
        }
        // $result["color"] = array("#FF0000","#00FF00","#0000FF");

        $result['label'] = [$data_label];
        $result['values'] = $new_values;
        return $result;
    }

    function normalize_json_data($json) {
        // `sums` represents the total amount of transcripts/genes
        $sums = [];
        // First step, get the sum for each possible entry
        foreach ($json['label'] as $k => $v) {
            $sums[$k] = 0;
        }
        // Initialize sums array
        foreach ($json['values'] as $val) {
            foreach ($val['values'] as $k => $v) {
                $sums[$k] = $sums[$k] + $v;
            }
        }

        // Second step: normalize the data
        foreach ($json['values'] as $index => $val) {
            foreach ($val['values'] as $k => $v) {
                $norm = ceil((100 * $v) / $sums[$k]);
                // $norm = 100*$v/$sums[$k];
                $json['values'][$index]['values'][$k] = $norm;
            }
        }
        return $json;
    }

    function update_json_data($data_label, $data, $json, $bins, $reduce_count = true) {
        $num_bins = count($bins['labels']);
        $min_val_arr = explode(',', $bins['labels'][0]);
        $max_val_arr = explode(',', $bins['labels'][$num_bins - 1]);
        $min_val = $min_val_arr[0];
        $max_val = $max_val_arr[1];
        $interval = $min_val_arr[1] - $min_val_arr[0];

        // Format of json requires multiple sub-arrays below the "label" and "value" sections
        // Using the current index is a convenient way to directly get the new index.
        $curr_index = count($json['label']);
        $json['label'][] = $data_label;

        for ($i = 0; $i < $num_bins; $i++) {
            $json['values'][$i]['values'][$curr_index] = 0;
        }

        foreach ($data as $d) {
            $bin = floor(($d - $min_val) / $interval);
            // Can only happen when one of the values of data is larger than any in the bins
            if ($bin >= $num_bins) {
                $bin = $num_bins - 1;
            }
            // Can only happen when one of the data values is smaller than any in the bins
            if ($bin < 0) {
                $bin = 0;
            }
            $prev_value = $json['values'][$bin]['values'][$curr_index];
            $json['values'][$bin]['values'][$curr_index] = $prev_value + 1;

            if ($reduce_count) {
                // Reduce the number of counts for the default counting. This way the sum is still correct
                $prev_value_all = $json['values'][$bin]['values'][0];
                $json['values'][$bin]['values'][0] = $prev_value_all - 1;
            }
        }
        return $json;
    }

    function create_length_bins($data, $num_bins) {
        sort($data); // Sort lengths by increasing size (from small to large)
        $result = [];
        // Initialize array, makes it easier downstream
        for ($i = 0; $i < $num_bins; $i++) {
            $result[$i] = 0;
        }
        $smallest_length = $data[0];
        $largest_length = $data[count($data) - 1];
        $bin_size = ($largest_length - $smallest_length) / $num_bins;
        foreach ($data as $d) {
            $bin = floor(($d - $smallest_length) / $bin_size);
            if ($bin >= $num_bins) {
                $bin = $num_bins - 1;
            }
            $result[$bin]++;
        }
        $labels = [];
        for ($i = 0; $i < $num_bins; $i++) {
            if ($i != $num_bins - 1) {
                $labels[] =
                    '' .
                    ($smallest_length + round($i * $bin_size)) .
                    ',' .
                    ($smallest_length + round(($i + 1) * $bin_size));
            } else {
                $labels[] = '' . ($smallest_length + round($i * $bin_size)) . ',' . $largest_length;
            }
        }
        $final_result = ['values' => $result, 'labels' => $labels];
        return $final_result;
    }
}
