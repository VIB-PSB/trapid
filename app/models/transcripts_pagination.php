<?php
  /*
   * This model represents info on the transcripts
   */
class TranscriptsPagination extends AppModel{

  var $name	= 'TranscriptsPagination';
  var $useTable = 'transcripts';




  function getPossibleParameters(){
	//transcripts:			a
    	//transcripts_labels:		b,b1,b2,b3,...
    	//transcripts_go:		c,c1,c2,c3,...
    	//transcripts_interpro: 	d,d1,d2,d3,...
	 $possible_parameters	=
	      array("min_transcript_length"=>array("query"=>"CHAR_LENGTH(`transcript_sequence`)>=",
						   "table"=>"`transcripts`",
						   "tabid"=>"a",
						   "desc"=>"Minimum transcript length"),
		    "max_transcript_length"=>array("query"=>"CHAR_LENGTH(`transcript_sequence`)<=",
						   "table"=>"`transcripts`",
						   "tabid"=>"a",
						   "desc"=>"Maximum transcript length"),
		    "min_orf_length"=>array("query"=>"CHAR_LENGTH(`orf_sequence`)>=",
					    "table"=>"`transcripts`",
					    "tabid"=>"a",
					    "desc"=>"Minimum ORF length"),
		    "max_orf_length"=>array("query"=>"CHAR_LENGTH(`orf_sequence`)<=",
					    "table"=>"`transcripts`",
					    "tabid"=>"a",
					    "desc"=>"Maximum ORF length"),
		    "meta_annotation"=>array("query"=>"`meta_annotation`=",
					     "table"=>"`transcripts`",
					     "tabid"=>"a",
					     "desc"=>"Meta annotation"),
		    "gf_id"=>array("query"=>"`gf_id`=",
				   "table"=>"`transcripts`",
				   "tabid"=>"a",
				   "desc"=>"Gene family"),
		    "label"=>array("query"=>"`label`=",
				   "table"=>"`transcripts_labels`",
				   "tabid"=>"b",
				   "desc"=>"Subset"),
		    "go"=>array("query"=>"`go`=",
				"table"=>"`transcripts_go`",
				"tabid"=>"c",
				"desc"=>"GO label"),
		    "interpro"=>array("query"=>"`interpro`=",
				      "table"=>"`transcripts_interpro`",
				      "tabid"=>"d",
				      "desc"=>"InterPro domain"),
		    );
	 return $possible_parameters;
  }


  function getParsedParameters($parameters){
    	$result			= array();
	$possible_parameters	= $this->getPossibleParameters();
	$num_parameters	= count($parameters);
	for($i=1;$i<$num_parameters;$i+=2){
      		$key	= mysql_real_escape_string($parameters[$i]);
     		$value	= mysql_real_escape_string(urldecode($parameters[$i+1]));
		if(array_key_exists($key,$possible_parameters)){
		  $desc		= $possible_parameters[$key]["desc"];
		  if(!array_key_exists($desc,$result)){$result[$desc] = array();}
		  $result[$desc][] = $value;
		}
	}
	return $result;
  }


  function createQuery($parameters,$count=FALSE){

    $possible_parameters	= $this->getPossibleParameters();
    $params		= array();
    $num_parameters	= count($parameters);
    $exp_id		= mysql_real_escape_string($parameters[0]);
    //parse parameters
    for($i=1;$i<$num_parameters;$i+=2){
      $key	= mysql_real_escape_string($parameters[$i]);
      $value	= mysql_real_escape_string(urldecode($parameters[$i+1]));
      if(!array_key_exists($key,$possible_parameters)){/*pr($key);*/return FALSE;}
      $params[]	= array("key"=>$key,"value"=>$value);	//not single hash, as multiple keys should be able to be used at the same time.
    }

    //select the necessary tables for joining.
    $used_tables	= array("`transcripts` a");		//default inclusion
    $used_joins		= array();
    $used_exp_sel	= array("a.`experiment_id`='".$exp_id."'");
    $used_queries	= array();
    $used_increases	= array("a"=>0,"b"=>0,"c"=>0,"d"=>0);
    foreach($params as $param){
      $par		= $possible_parameters[$param['key']];
      $tabid		= $par["tabid"];
      //establish unique table identifier per table (except transcripts)
      $table_id		= $tabid;
      if($tabid!="a"){
	$used_increases[$tabid]++;
	$table_id   	= $tabid."".$used_increases[$tabid];
      }
      //table definitions
      $used_tables[] 	= $par['table']." ".$table_id;
      //joins between transcript table and other tables
      if($tabid!="a"){	//don't need a join when restriction is put on table transcripts
	$used_joins[]	= $table_id.".`transcript_id`=a.`transcript_id`";
      }
      //every table should be restricted by experiment id as well
      $used_exp_sel[]	= $table_id.".`experiment_id`='".$exp_id."'";
      //query itself, table identifier should be entered before the first "`" character (which should indicate a column)
      $query_insert_loc	= strpos($par['query'],"`");
      $used_query	= substr($par['query'],0,$query_insert_loc)."".$table_id.".".substr($par['query'],$query_insert_loc)."'".$param['value']."'";
      $used_queries[]	= $used_query;
    }
    $used_tables	= implode(",",array_unique($used_tables));
    $used_joins		= implode(" AND ",array_unique($used_joins));
    $used_exp_sel	= implode(" AND ",array_unique($used_exp_sel));
    $used_queries	= implode(" AND ",array_unique($used_queries));

    $query		= "SELECT ";
    if($count){$query	= $query. "COUNT(a.`transcript_id`) as count ";}
    else{$query		= $query." a.`transcript_id` ";}
    $query		= $query." FROM ".$used_tables." WHERE ".$used_exp_sel." AND ".$used_queries;
    if(strlen($used_joins)>0){$query = $query." AND ".$used_joins;}
    return $query;
  }





  function paginate($conditions,$fields,$order,$limit,$page=1,$recursive=null,$extra=array()){
    $custom_query		= $this->createQuery($conditions,FALSE);
    //pr("custom_query:".$custom_query);
    if($custom_query===FALSE){return null;}
    $limit_start = ($page-1)*$limit;
    $limit_end = $limit;
    if($limit_start<0){$limit_start = 0;}
    $use_limit = true;

    if($order){
      $custom_query		= $custom_query." ORDER BY ";
      foreach($order as $k=>$v){
	$custom_query		= $custom_query." a.`".$k."` ".$v." ";
      }
    }
    if($use_limit){
	$custom_query		= $custom_query." LIMIT ".$limit_start.",".$limit_end;
    }
    $res			= $this->query($custom_query);
    $result			= array();
    foreach($res as $r){
      $result[]		= $r['a']['transcript_id'];
    }
    return $result;
  }

  function paginateCount($conditions=null,$recursive=0,$extra=array()){
    $custom_query	= $this->createQuery($conditions,TRUE);
    if($custom_query===FALSE){return 0;}
    $res		= $this->query($custom_query);
    $result		= $res[0][0]['count'];
    return $result;
  }


/* Trying to get jQuery dataTables working with a simple query on the 'transcripts' table. */
// See dataTables documentation: https://datatables.net/development/server-side/php_cake
// TODO: Update to new datatables syntax
// TODO: Check user before doing anything.
//    public function GetData() {
//
//        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
//         * Easy set variables
//        */
//
//        /* Array of database columns which should be read and sent back to DataTables. Use a space where
//         * you want to insert a non-database field (for example a counter or static image)
//        */
//        $aColumns = array('transcript_id');
//
//        /* Indexed column (used for fast and accurate table cardinality) */
//        $sIndexColumn = "id";
//
//        /* DB table to use */
//        $sTable = "transcripts";
//
//        // App::uses('ConnectionManager', 'Model');
//        $dataSource = ConnectionManager::getDataSource('default');
//
//        /* Database connection information */
//        $gaSql['user']       = $dataSource->config['login'];
//        $gaSql['password']   = $dataSource->config['password'];
//        $gaSql['db']         = $dataSource->config['database'];
//        $gaSql['server']     = $dataSource->config['host'];
//
//
//        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
//         * If you just want to use the basic configuration for DataTables with PHP server-side, there is
//        * no need to edit below this line
//        */
//
//        /*
//         * Local functions
//        */
//        function fatal_error ( $sErrorMessage = '' )
//        {
//            header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
//            die( $sErrorMessage );
//        }
//
//
//        /*
//         * MySQL connection
//        */
//        if ( ! $gaSql['link'] = mysql_pconnect( $gaSql['server'], $gaSql['user'], $gaSql['password']  ) )
//        {
//            fatal_error( 'Could not open connection to server' );
//        }
//
//        if ( ! mysql_select_db( $gaSql['db'], $gaSql['link'] ) )
//        {
//            fatal_error( 'Could not select database ' );
//        }
//
//
//        /*
//         * Paging
//        */
//        $sLimit = "";
//        if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
//        {
//            $sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
//                    intval( $_GET['iDisplayLength'] );
//        }
//
//
//        /*
//         * Ordering
//        */
//        $sOrder = "";
//        if ( isset( $_GET['iSortCol_0'] ) )
//        {
//            $sOrder = "ORDER BY  ";
//            for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
//            {
//                if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
//                {
//                    $sOrder .= "`".$aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."` ".
//                        ($_GET['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
//                }
//            }
//
//            $sOrder = substr_replace( $sOrder, "", -2 );
//            if ( $sOrder == "ORDER BY" )
//            {
//                $sOrder = "";
//            }
//        }
//
//
//        /*
//         * Filtering
//        * NOTE this does not match the built-in DataTables filtering which does it
//        * word by word on any field. It's possible to do here, but concerned about efficiency
//        * on very large tables, and MySQL's regex functionality is very limited
//        */
//        $sWhere = "";
//        if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
//        {
//            $sWhere = "WHERE (";
//            for ( $i=0 ; $i<count($aColumns) ; $i++ )
//            {
//                $sWhere .= "`".$aColumns[$i]."` LIKE '%".mysql_real_escape_string( $_GET['sSearch'] )."%' OR ";
//            }
//            $sWhere = substr_replace( $sWhere, "", -3 );
//            $sWhere .= ')';
//        }
//
//        /* Individual column filtering */
//        for ( $i=0 ; $i<count($aColumns) ; $i++ )
//        {
//            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
//            {
//                if ( $sWhere == "" )
//                {
//                    $sWhere = "WHERE ";
//                }
//                else
//                {
//                    $sWhere .= " AND ";
//                }
//                $sWhere .= "`".$aColumns[$i]."` LIKE '%".mysql_real_escape_string($_GET['sSearch_'.$i])."%' ";
//            }
//        }
//
//
//        /*
//         * SQL queries
//        * Get data to display
//        */
//        $sQuery = "
//    SELECT SQL_CALC_FOUND_ROWS `".str_replace(" , ", " ", implode("`, `", $aColumns))."`
//            FROM   $sTable
//            $sWhere
//            $sOrder
//            $sLimit
//            ";
//        $rResult = mysql_query( $sQuery, $gaSql['link'] ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
//
//        /* Data set length after filtering */
//        $sQuery = "
//    SELECT FOUND_ROWS()
//";
//        $rResultFilterTotal = mysql_query( $sQuery, $gaSql['link'] ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
//        $aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);
//        $iFilteredTotal = $aResultFilterTotal[0];
//
//        /* Total data set length */
//        $sQuery = "
//    SELECT COUNT(`".$sIndexColumn."`)
//            FROM   $sTable
//            ";
//        $rResultTotal = mysql_query( $sQuery, $gaSql['link'] ) or fatal_error( 'MySQL Error: ' . mysql_errno() );
//        $aResultTotal = mysql_fetch_array($rResultTotal);
//        $iTotal = $aResultTotal[0];
//
//
//        /*
//         * Output
//        */
//        $output = array(
//                "sEcho" => intval($_GET['sEcho']),
//                "iTotalRecords" => $iTotal,
//                "iTotalDisplayRecords" => $iFilteredTotal,
//                "aaData" => array()
//        );
//
//        while ( $aRow = mysql_fetch_array( $rResult ) )
//        {
//            $row = array();
//            for ( $i=0 ; $i<count($aColumns) ; $i++ )
//            {
//                if ( $aColumns[$i] == "version" )
//                {
//                    /* Special output formatting for 'version' column */
//                    $row[] = ($aRow[ $aColumns[$i] ]=="0") ? '-' : $aRow[ $aColumns[$i] ];
//                }
//                else if ( $aColumns[$i] != ' ' )
//                {
//                    /* General output */
//                    // $row[] = "<strong>" . $aRow[ $aColumns[$i] ] . "</strong>"; // Formatting example
//                    $row[] = $aRow[ $aColumns[$i] ]  ;
//                }
//            }
//            $output['aaData'][] = $row;
//        }
//        // sleep(2); // Simulate long processing time?
//
//        //
//        $output['aaData'] = array_slice($output['aaData'], 0, 3);
//        return $output;
//    }

}


?>
