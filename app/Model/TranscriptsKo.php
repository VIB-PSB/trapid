<?php

/* This model represents KO information associated to the transcripts. */

class TranscriptsKo extends AppModel {

    var $name	= 'TranscriptsKo';
    var $useTable = 'transcripts_annotation';


    function getStats($exp_id){
        $query	= "SELECT COUNT(DISTINCT(`name`)) as count1, COUNT(DISTINCT(`transcript_id`)) as count2 FROM `transcripts_annotation` WHERE `experiment_id`='".$exp_id."' AND `type`='ko' ";
        $res	= $this->query($query);
        $result	= array("num_ko"=>0,"num_transcript_ko"=>0);
        if($res){
            $result["num_ko"]			= $res[0][0]['count1'];
            $result["num_transcript_ko"]	= $res[0][0]['count2'];
        }
        return $result;
    }


}