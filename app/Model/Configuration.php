<?php

/*
 * CakePHP model object for TRAPID's configuration.
 */

class Configuration extends AppModel {
    var $useTable = 'configuration';

    // Retrieve Rfam clans metadata (id + description) from the database, and return them as an array
    function getRfamClansMetadata() {
        $clans_metadata = [];
        // Retrieve data from DB (`configuration` table)
        $config_clan_ids = $this->find('all', ['conditions' => ['method' => 'rfam_clans', 'attr' => 'clan_id']]);
        $config_clan_descs = $this->find('all', ['conditions' => ['method' => 'rfam_clans', 'attr' => 'description']]);
        if ($config_clan_ids && $config_clan_descs) {
            // Process retrieved configuration values and return formatted array
            foreach ($config_clan_ids as $cf) {
                $clan_acc = $cf['Configuration']['key'];
                $clan_id = $cf['Configuration']['value'];
                $clans_metadata[$clan_acc]['clan_id'] = $clan_id;
            }
            foreach ($config_clan_descs as $cf) {
                $clan_acc = $cf['Configuration']['key'];
                // Useless check as we know for sure every clan has both an ID and a description?
                // if(array_key_exists($clan_acc, $clans_metadata)){
                $clan_desc = $cf['Configuration']['value'];
                $clans_metadata[$clan_acc]['clan_desc'] = $clan_desc;
                // }
            }
            return $clans_metadata;
        } else {
            return null;
        }
    }

    // Retrieve default Rfam clans and return them as an array.
    function getRfamClansDefault() {
        $default_clans = $this->find('first', [
            'conditions' => [
                'method' => 'initial_processing_settings',
                'key' => 'ncrna',
                'attr' => 'default_rfam_clans'
            ],
            'fields' => ['value']
        ]);
        $default_clans = explode(',', $default_clans['Configuration']['value']);
        return $default_clans;
    }

    // Retrieve third-party tool data from the database, and return it as array. This data is used to populate the
    // 'tools and parameters' documentation page.
    function getDocToolsParameters() {
        $tools_parameters_data = [];
        $config_values = $this->find('all', ['conditions' => ['method' => 'doc_tools_parameters']]);
        if ($config_values) {
            // Process retrieved configuration values and return formatted array
            foreach ($config_values as $cf) {
                $tool_id = $cf['Configuration']['key'];
                $attr = $cf['Configuration']['attr'];
                $value = $cf['Configuration']['value'];
                $tools_parameters_data[$tool_id][$attr] = $value;
            }
        }
        return $tools_parameters_data;
    }
}
