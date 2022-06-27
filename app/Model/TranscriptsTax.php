<?php

/* This class represents the taxonomic binning results stored in the transcripts_tax table */
class TranscriptsTax extends AppModel {

    var $name	= 'TranscriptsTax';
    var $useTable = 'transcripts_tax';


    public $virtualFields = array(
        'tax_results' => 'UNCOMPRESS(TranscriptsTax.tax_results)'
    );

    /* Get unique tax ids, associated transcripts and lineages from `transcripts_tax` table */
    function getSummaryAndLineages($exp_id) {
        $tax_array = array();  // Array that will store result sand be returned
        // $set_options = 'SET GLOBAL group_concat_max_len=20000000';
        // mysql_query($set_options);
        $query = "SELECT tt.txid, tt.transcripts, full_taxonomy.tax from (select distinct txid, group_concat(transcript_id) as transcripts 
                  from transcripts_tax where experiment_id = '".$exp_id."' group by txid) as tt 
                  left join full_taxonomy on tt.txid=full_taxonomy.txid;";
        $res = $this->query($query);
        for($i=0;$i< count($res);$i++){
            $r = $res[$i];
            $transcripts = explode(',',$r['tt']['transcripts']);
            $lineage = explode('; ', $r["full_taxonomy"]["tax"]);
            $tax_id = $r['tt']['txid'];
            $tax_array[$tax_id] = array("transcripts"=>$transcripts, "lineage"=>$lineage);
        }
        return($tax_array);
    }

}

