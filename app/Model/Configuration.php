<?php


  /*
   *	CakePHP-model object for the configuration-table.
   *    Contains mostly custom queries.
   *
   */
class Configuration extends AppModel {

    var $name = 'Configuration';
    var $useTable = 'configuration';


    // Retrieve Rfam clans metadata (id + description) from the database, and return them as an array
    function getRfamClansMetadata() {
        $clans_metadata = array();
        // Retrieve data from DB (`configuration` table)
        $config_clan_ids = $this->find("all", array('conditions'=>array('method'=>'rfam_clans', 'attr'=>'clan_id')));
        $config_clan_descs = $this->find("all", array('conditions'=>array('method'=>'rfam_clans', 'attr'=>'description')));
        if($config_clan_ids && $config_clan_descs) {
            // Process retrieved configuration values and return formatted array
            foreach($config_clan_ids as $cf) {
                $clan_acc = $cf['Configuration']['key'];
                $clan_id = $cf['Configuration']['value'];
                $clans_metadata[$clan_acc]["clan_id"] = $clan_id;
            }
            foreach($config_clan_descs as $cf) {
                $clan_acc = $cf['Configuration']['key'];
                // Useless check as we know for sure every clan has both an ID and a description?
                // if(array_key_exists($clan_acc, $clans_metadata)){
                $clan_desc = $cf['Configuration']['value'];
                $clans_metadata[$clan_acc]["clan_desc"] = $clan_desc;
                // }
            }
            return $clans_metadata;
        }
        else {
            return null;
        }
    }


    // A simple wrapper around find to retreive default FRFAM clans and return them as an array.
    // In the future, we'll store more initial processing values in the `configuration` table.
    function getRfamClansDefault() {
        $default_clans = $this->find("first", array('conditions'=>array('method'=>'initial_processing_settings', 'key'=>'ncrna', 'attr'=>'default_rfam_clans'), 'fields'=>array('value')));
        $default_clans = explode(",", $default_clans['Configuration']['value']);
        return $default_clans;
    }

    // Retrieve tools' data (used in the documentation) from the database, and return it as array.
    function getDocToolsParameters() {
        $tools_parameters_data = array();
        $config_values = $this->find("all", array('conditions'=>array('method'=>'doc_tools_parameters')));
        if($config_values) {
            // Process retrieved configuration values and return formatted array
            foreach($config_values as $cf) {
                $tool_id = $cf['Configuration']['key'];
                $attr = $cf['Configuration']['attr'];
                $value = $cf['Configuration']['value'];
                $tools_parameters_data[$tool_id][$attr] = $value;
            }
        }
        return $tools_parameters_data;
    }

}
