package transcript_pipeline;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.Statement;
import java.text.NumberFormat;
import java.util.ArrayList;
import java.util.Collections;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Hashtable;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.SortedMap;
import java.util.TreeMap;


/**
 * This program will be used to parse an m8 output file of a similarity search (either BLAST or Rapsearch),
 * and use this information to assign transripts to gene families.
 * After the homology assignment is done, functional annotation transfer is possibly done
 * 
 * This program is part of the transriptome pipeline
 *  
 * @author Michiel Van Bel
 * @version 1.0
 */
public class InitialTranscriptsProcessing {

	public static final int META_MIN_GF_SIZE		= 5;
	public static final double META_PERC_REMOVE		= 0.05;
	
	public enum GF_TYPE {NONE,HOM,IORTHO};
	public enum FUNC_ANNOT {NONE,GF,BESTHIT,GF_BESTHIT};
	
	public static void main(String[] args){
		
		int NUM_BLAST_HITS_CACHE			= 50;	//SEPRO: note that for the frameshift detection this should be sufficiently large !!
		
		InitialTranscriptsProcessing itp	= new InitialTranscriptsProcessing();
		/*
		 * Retrieving the necessary variables from the command line
		 * =========================================================
		 */
		
		//IMPORTANT!!!
		//DATABASE VARIABLES HERE ARE NOT STORED IN JAVA CODE - DUE TO SECURITY CONSTRAINTS - 
		//BUT PASSED THROUGH PHP AND PERL!!
		
		//database variables, necessary for retrieving homology/orthology information from the
		//similarity hits
		String plaza_database_server		= args[0];	//normally psbsql03.psb.ugent.be
		String plaza_database_name			= args[1];
		String plaza_database_login			= args[2];
		String plaza_database_password		= args[3];
		
		//workbench variables, necessary for storing homology/orthology information
		String trapid_server				= args[4];
		String trapid_name					= args[5];
		String trapid_login					= args[6];
		String trapid_password				= args[7];
		
		//user experiment id
		String trapid_experiment_id			= args[8];
		
		//location of the output file of the similarity search
		//This similarity output file is normally coming from Rapsearch2 (but any m8 formatted
		//file is actually OK).
		String similarity_search_file		= args[9];
		
		//type of gene family assignment to be performed. Defined in enum GF_TYPE
		//dependend on this type of gf assignment, the correct function(s) should be called,
		//also filling in extra columns in table gene_families
		String gf_type_string				= args[10];
		GF_TYPE gf_type						= GF_TYPE.NONE;
		for(GF_TYPE g : GF_TYPE.values()){if(g.toString().equalsIgnoreCase(gf_type_string)){gf_type	= g;}}
		if(gf_type==GF_TYPE.NONE){
			System.err.println("Incorrect parameter for the gene family type!");
			System.exit(1);
		}								
		
		//number of top-hits from similarity search to take into account to decide on the 
		//homology assignment. See methods and results from paper for explanation.
		//normally this number is 1 when the reference databases are either clade or species, 
		//but 5 when the reference database is gene-family-representatives
		int num_top_hits					= Integer.parseInt(args[11]);
				
		//functional annotation transfer mode. Defined in type FUNC_ANNOT
		//depended on this type, the functional annotation will be transferred through the gene family
		//or through the best hit
		String func_annot_string			= args[12];
		FUNC_ANNOT func_annot				= FUNC_ANNOT.NONE;
		for(FUNC_ANNOT fa:FUNC_ANNOT.values()){if(fa.toString().equalsIgnoreCase(func_annot_string)){func_annot=fa;}}
		if(func_annot==FUNC_ANNOT.NONE){
			System.err.println("Incorrect parameter for the functional annotation mode!");
			System.exit(1);
		}
				
		Connection plaza_db_connection		= null;
		Connection trapid_db_connection		= null;
		
		try{		
		
			/*
			 * Executing the homology assignment
			 * ==============================================================
			 */

			Class.forName("com.mysql.jdbc.Driver");	
			
			//step 1: creating 2 different database connections. 1 to the normal 
			//plaza database, 1 to the trapid database
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);											
			long t11	= System.currentTimeMillis();
			//primary step: remove all gene family information and functional information in the TRAPID database with regards
			//to the current experiment.
			itp.clearContent(trapid_db_connection,trapid_experiment_id);
			long t12	= System.currentTimeMillis();
			timing("clearing databases",t11,t12);
			itp.update_log(trapid_db_connection,trapid_experiment_id,"data_normalization","","3");
			plaza_db_connection.close();
			trapid_db_connection.close();
			
			
			
			//step 2: parse the similarity search output file, and store some information:
			//transcript_id -> ({hit_gene,bitscore,query_start,query_stop,perc_identity,aln_length,log_e_val});						
			long t21	= System.currentTimeMillis();
			Map<String,List<String[]>> simsearch_data		= itp.parseSimilarityOutputFile(similarity_search_file,NUM_BLAST_HITS_CACHE);
			long t22	= System.currentTimeMillis();
			timing("parsing rapsearch file",t21,t22);			
			
			
			//step 2b. Store the similarity search information in the database.
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			long t2b1	= System.currentTimeMillis();			
			itp.storeSimilarityData(trapid_db_connection,trapid_experiment_id,simsearch_data);			
			long t2b2	= System.currentTimeMillis();
			timing("Storing similarities information",t2b1,t2b2);
			plaza_db_connection.close();
			trapid_db_connection.close();
			
			//step 2c. Determine for the similarity search what the best hitting species are
			//species -> hitcount
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			Map<String,Integer> species_hit_count			= itp.getSpeciesHitCount(plaza_db_connection,simsearch_data);
			itp.storeBestSpeciesHitData(trapid_db_connection,trapid_experiment_id,species_hit_count);
			plaza_db_connection.close();
			trapid_db_connection.close();
			
			
			
			//step 3: create the transcript to gene family mapping, with extra info kept for later
			//this is the actual homology assignment done for the transcripts.
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			long t31	= System.currentTimeMillis();
			Map<String,GeneFamilyAssignment> transcript2gf	= null;
			if(gf_type==GF_TYPE.HOM){	
				String gf_prefix	= itp.getGfPrefix(trapid_db_connection,plaza_database_name);
				//System.out.println("GF_PREFIX : "+gf_prefix);
				transcript2gf 		= itp.inferTranscriptGenefamiliesHom(plaza_db_connection,simsearch_data,num_top_hits,gf_prefix);
			}
			else if(gf_type==GF_TYPE.IORTHO){
				//no num_top_hits, as this should be one!				
				transcript2gf	= itp.inferTranscriptGenefamiliesIntegrativeOrthology(plaza_db_connection, simsearch_data);
			}	
			plaza_db_connection.close();
			trapid_db_connection.close();
			
			//store the results of the gene family mapping in the database
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			itp.storeGeneFamilyAssignments(trapid_db_connection,trapid_experiment_id,transcript2gf,gf_type);
			itp.update_log(trapid_db_connection,trapid_experiment_id,"infer_genefamilies","","3");
			plaza_db_connection.close();
			trapid_db_connection.close();
			
			//make reverse mapping of gene families to transcripts, in order to reduce computing time
			//storage shouldn't be too much problem
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			Map<String,List<String>> gf2transcripts	= itp.reverseMapping(transcript2gf);
			long t32	= System.currentTimeMillis();
			timing("GF inferring",t31,t32);
			plaza_db_connection.close();
			trapid_db_connection.close();
				
								
			//step 4: perform transfer of functional annotation from the gene families to the transcripts.
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			long t41	= System.currentTimeMillis();			
			System.out.println("Performing GO functional transfer : "+func_annot);		
			Map<String,Set<String>> transcript_go			= null;
			switch(func_annot){
				case GF: transcript_go 			= itp.assignGoTranscripts_GF(plaza_db_connection, transcript2gf, gf2transcripts, gf_type);break;
				case BESTHIT: transcript_go 	= itp.assignGoTranscripts_BESTHIT(plaza_db_connection, simsearch_data); break;
				case GF_BESTHIT: transcript_go 	= itp.assignGoTranscripts_GF_BESTHIT(plaza_db_connection, transcript2gf, gf2transcripts, gf_type, simsearch_data);break;
				default: System.err.println("Illegal func annot indicator : "+func_annot); System.exit(1);
			}
			plaza_db_connection.close();
			trapid_db_connection.close();
			
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			Map<String,Map<String,Integer>>	transcript_go_hidden	= itp.hideGoTerms(plaza_db_connection, transcript_go);
			itp.storeGoTranscripts(trapid_db_connection, trapid_experiment_id,transcript_go_hidden);
			plaza_db_connection.close();
			trapid_db_connection.close();
			
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			System.out.println("Performing InterPro functional transfer : "+func_annot);	
			Map<String,Set<String>> transcript_interpro		= null;
			switch(func_annot){
				case GF: transcript_interpro		= itp.assignProteindomainTranscripts_GF(plaza_db_connection, transcript2gf, gf2transcripts, gf_type);break;
				case BESTHIT:transcript_interpro	= itp.assignProteindomainTranscripts_BESTHIT(plaza_db_connection, simsearch_data);break;
				case GF_BESTHIT:transcript_interpro	= itp.assignProteindomainTranscripts_GF_BESTHIT(plaza_db_connection, transcript2gf, gf2transcripts, gf_type, simsearch_data);break;
				default:System.err.println("Illegal func annot indicator : "+func_annot);System.exit(1);
			}
			plaza_db_connection.close();
			trapid_db_connection.close();
			
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			itp.storeInterproTranscripts(trapid_db_connection, trapid_experiment_id,transcript_interpro);			
			long t42	= System.currentTimeMillis();
			timing("Functional transfer",t41,t42);
			itp.update_log(trapid_db_connection,trapid_experiment_id,"infer_functional_annotation","","3");
			plaza_db_connection.close();
			trapid_db_connection.close();
									
			
			
			//step 5: perform putative frameshift detection and store best frame.
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			long t51	= System.currentTimeMillis();
			itp.performPutativeFrameDetection(trapid_db_connection,trapid_experiment_id,simsearch_data);
			long t52	= System.currentTimeMillis();
			timing("PutativeFrameDetection",t51,t52);
			itp.update_log(trapid_db_connection,trapid_experiment_id,"frameshift_detection","","3");
			plaza_db_connection.close();
			trapid_db_connection.close();
			
						
			//step 6: get longest ORFs in preferred frame.
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			long t61	= System.currentTimeMillis();
			itp.performInitialORFPrediction(trapid_db_connection,trapid_experiment_id);
			long t62	= System.currentTimeMillis();
			timing("ORF prediction",t61,t62);
			itp.update_log(trapid_db_connection,trapid_experiment_id,"orf_prediction","","3");
			plaza_db_connection.close();
			trapid_db_connection.close();
			
			
			//step 7: perform check on lengths of transcripts, compared to gene family CDS length
			//store the results.
			plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			trapid_db_connection		= itp.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			long t71	= System.currentTimeMillis();
			itp.performMetaAnnotationPrediction(plaza_db_connection,trapid_db_connection,plaza_database_name,trapid_experiment_id,transcript2gf,gf2transcripts,gf_type);			
			long t72	= System.currentTimeMillis();
			timing("Meta annotation",t71,t72);
			itp.update_log(trapid_db_connection,trapid_experiment_id,"meta_annotation","","3");
			
			
			//step8: set status of experiment to finished
			itp.setExperimentStatus(trapid_db_connection,trapid_experiment_id,"finished");
			
			//final step: close the database connections, to both the trapid database and the PLAZA database
			plaza_db_connection.close();
			trapid_db_connection.close();			
		}
		catch(Exception exc){
			try{
				itp.setExperimentStatus(trapid_db_connection,trapid_experiment_id,"error");
			}
			catch(Exception exc2){}
			exc.printStackTrace();
			System.exit(1);
		}
	}
	
	
	
	/*
	 * Update logging system
	 */
	public void update_log(Connection trapid_connection,String exp_id,String action,String param,String depth) throws Exception{
		String sql	= "INSERT INTO `experiment_log`(`experiment_id`,`date`,`action`,`parameters`,`depth`) VALUES ('"+exp_id+"',NOW(),'"+action+"','"+param+"','"+depth+"')";
		Statement stmt	= trapid_connection.createStatement();
		stmt.execute(sql);
		stmt.close();
	}
	
	
	
	public static void timing(String msg,long t1,long t2){
		timing(msg,t1,t2,1);
	}
	public static void timing(String msg,long t1,long t2,int lvl){
		long t	= (t2-t1);
		for(int i=1;i<lvl;i++){System.out.print("\t");}
		System.out.println(msg+"\t"+t+"ms");
	}
	public static void timingNano(String msg,long t,int lvl){
		long t_final	= t/1000000;
		for(int i=1;i<lvl;i++){System.out.print("\t");}
		System.out.println(msg+"\t"+t_final+"ms");
	}
	
	
	public void printRapsearchData(Map<String,List<String[]>> data){
		for(String transcript_id:data.keySet()){
			System.out.println(transcript_id+"\t"+data.get(transcript_id).size());
		}
	}
	
	public void printGfMapping(Map<String,GeneFamilyAssignment> data,GF_TYPE gf_type){
		if(gf_type==GF_TYPE.HOM){
			for(String transcript_id:data.keySet()){
				//if(transcript_id.equals("AT4G21750") || transcript_id.equals("AT4G04890"))
				System.out.println(transcript_id+"\t"+data.get(transcript_id).gf_id+"\t"+data.get(transcript_id).gf_size);
			}
		}
		else if(gf_type==GF_TYPE.IORTHO){
			for(String transcript_id:data.keySet()){
				//if(transcript_id.equals("AT4G21750") || transcript_id.equals("AT4G04890"))
				System.out.println(transcript_id+"\t"+data.get(transcript_id).gf_id+"\t"+data.get(transcript_id).gf_size);			
			}
		}
	}
	
	
	/**
	 * Update the status of an experiment to a new one.
	 * @param db_connection Connection to trapid database
	 * @param trapid_experiment Trapid experiment identifier
	 * @param status New status for trapid experiment. Enum in database, so only limited number of possibilities
	 * @throws Exception Database error. 
	 */
	public void setExperimentStatus(Connection db_connection,String trapid_experiment,String status) throws Exception{
		String sql	= "UPDATE `experiments` SET `process_state` = '"+status+"' WHERE `experiment_id`='"+trapid_experiment+"' ";
		Statement stmt	= db_connection.createStatement();
		stmt.execute(sql);
		stmt.close();
	}
	
	
	
	/**
	 * Remove values from the database that should be filled in by this program.
	 * This is necessary in order to reduce the risk of keeping results from previous runs.
	 * @param db_connection Connection to TRAPID database
	 * @param trapid_experiment Trapid experiment id
	 * @throws Exception Database failure.
	 */
	public void clearContent(Connection db_connection,String trapid_experiment) throws Exception{
		String sql1	= "UPDATE `transcripts` SET `gf_id`= NULL,`orf_sequence`=NULL,`detected_frame`='0'," +
				" `detected_strand`='+',`full_frame_info`=NULL,`putative_frameshift`='0', " +
				" `is_frame_corrected`='0', `orf_start`=NULL,`orf_stop`=NULL," +
				" `orf_contains_start_codon`=NULL,`orf_contains_stop_codon`=NULL," +
				" `meta_annotation`='No Information',`meta_annotation_score`=NULL,`gf_id_score`=NULL WHERE `experiment_id`='"+trapid_experiment+"' ";
		String sql2	= "DELETE FROM `gene_families` WHERE `experiment_id`='"+trapid_experiment+"' ";
		String sql3	= "DELETE FROM `transcripts_go` WHERE `experiment_id`='"+trapid_experiment+"' ";
		String sql4	= "DELETE FROM `transcripts_interpro` WHERE `experiment_id`='"+trapid_experiment+"' ";
		String sql5 = "DELETE FROM `similarities` WHERE `experiment_id`='"+trapid_experiment+"' ";
		String[] sql_queries	= {sql1,sql2,sql3,sql4,sql5};
		for(String sql:sql_queries){
			Statement stmt	= db_connection.createStatement();
			stmt.execute(sql);
			stmt.close();
		}
	}
	
	
	

	/**
	 * Store similarity search information in the database.
	 * @param trapid_connection	Database connection to trapid	
	 * @param trapid_experiment Trapid experiment
	 * @param simsearch_data Similarity search data. Key item is query gene, then a list of hits
	 * @throws Exception Database error
	 */
	public void storeSimilarityData(Connection trapid_connection,String trapid_experiment,Map<String,List<String[]>> simsearch_data) throws Exception{		
		String insert_query			= "INSERT INTO `similarities` (`experiment_id`,`transcript_id`,`similarity_data`) VALUES ('"+trapid_experiment+"',?,?)";
		PreparedStatement stmt		= trapid_connection.prepareStatement(insert_query);
		
		for(String transcript_id : simsearch_data.keySet()){
			StringBuffer buff		= new StringBuffer();
			//transcript_id -> ({hit_gene,bitscore,query_start,query_stop,perc_identity,aln_length,log_e_val})
			for(String[] simsearch_info : simsearch_data.get(transcript_id)){
				double e_val	= Math.pow(10,Double.parseDouble(simsearch_info[6]));
				String t = simsearch_info[0]+","+e_val+","+simsearch_info[1]+","+simsearch_info[5]+","+simsearch_info[4];
				buff.append(t+";");
			}
			String sim_string		= buff.toString();
			sim_string				= sim_string.substring(0,sim_string.length()-1);	//remove trailing ';'
			stmt.setString(1, transcript_id);
			stmt.setString(2, sim_string);
			stmt.execute();
		}	
		stmt.close();		
	}
	
	
	
	public void storeBestSpeciesHitData(Connection trapid_connection,String trapid_experiment,Map<String,Integer> species_hit_counts) throws Exception{
		SortedMap<String,Integer> tmp	= new TreeMap<String,Integer>();
		tmp.putAll(species_hit_counts);		
		StringBuffer res				= new StringBuffer();
		for(String species:tmp.keySet()){
			int hit_count				= tmp.get(species);
			res.append(";"+species+"="+hit_count);
		}
		String t						= "";
		if(res.length()>0){
			t							= res.substring(1);
		}				
		String update_query				= "UPDATE `experiments` SET `hit_results` = '"+t+"' WHERE `experiment_id`='"+trapid_experiment+"' ";
		Statement stmt					= trapid_connection.createStatement();
		stmt.execute(update_query);
		stmt.close();
		tmp.clear();
	}
	
	
	
	/**
	 * 
	 *    implemented by Sebastian
	 * -> putative frame detection
	 * -> putative frameshift detection
	 * -> putative ORF sequence detection 
	 * 
	 * 
	 * @param db_connection
	 * @param experiment_id
	 * @param simsearch_data
	 * @throws Exception
	 */
	
	
	/*
	 * SELECT `detected_frame` , `detected_strand` , count( `transcript_id` ) AS count FROM `transcripts` WHERE `experiment_id` =13 GROUP BY `detected_frame` , `detected_strand`
	 */
	public void performPutativeFrameDetection(Connection db_connection,String experiment_id,Map<String,List<String[]>> simsearch_data) throws Exception{
		
		Map<String, Integer> lengths = new HashMap<String, Integer>();
		//boolean frameshift = false; //flag to check if a frameshift is expected
		
		String query_transcript_lengths				= "SELECT `transcript_id`, LENGTH(`transcript_sequence`)  FROM `transcripts` WHERE `experiment_id` = ?";
		PreparedStatement stmt_transcript_lengths	= db_connection.prepareStatement(query_transcript_lengths);
		stmt_transcript_lengths.setString(1,experiment_id);
		ResultSet set	= stmt_transcript_lengths.executeQuery();
		while(set.next()){lengths.put(set.getString(1), set.getInt(2));}
		set.close();
		stmt_transcript_lengths.close();
				
		String update_query = "UPDATE `transcripts` SET `detected_frame`= ? , `detected_strand` = ? , `full_frame_info` = ?, `putative_frameshift` = ?  WHERE `experiment_id`='"+experiment_id+"' AND `transcript_id` = ? ";
		PreparedStatement stmt_update_frames	= db_connection.prepareStatement(update_query);
				
		for (String gene: simsearch_data.keySet() ){			
			String topHit = simsearch_data.get(gene).get(0)[0];										
			Map<String, Integer> count = new HashMap<String, Integer>();
			for (int i = 0; i < simsearch_data.get(gene).size(); i++){ //includes top hit :-)
				String currentHit = simsearch_data.get(gene).get(i)[0];
				//same hit as best hit. As such, we expect them to be in the same reading frame
				//if not, this putatively indicates a frameshift, which will be indicated in the database as such
				if (topHit.equals(currentHit)){	
					int start = Integer.parseInt(simsearch_data.get(gene).get(i)[2]);
					int stop = Integer.parseInt(simsearch_data.get(gene).get(i)[3]);					
					int frame = 0;
					char strand = ' ';					
					if (start < stop){
						//frame = Math.abs(start % 3); //+1;
						frame	= Math.abs((start-1)%3)+1;
						strand = '+';
					} 
					else {
						int currentLength = lengths.get(gene);
						int newStart = (currentLength + 1) - start;
						//frame = Math.abs(newStart % 3);//+1;
						frame	= Math.abs((newStart-1)%3)+1;
						strand = '-';
					/*	if(gene.equals("contig05154")){
							System.out.println(currentHit+"\t"+currentLength);
							System.out.println(currentHit+"\t"+newStart);
							System.out.println(currentHit+"\t"+start+"\t"+stop);
						}
						*/
					}				
					if (count.containsKey("" + strand + frame)){
						int currentCount = count.get("" + strand + frame);
						currentCount++;
						count.put("" + strand + frame, currentCount);
					} 
					else {
						count.put("" + strand + frame, 1);
					}
				}
			}
			
		/*	if(gene.equals("contig05154")){
				for(String fi:count.keySet()){
					System.out.println(fi+"\t"+count.get(fi));
				}
			}*/
			
			//System.out.println(count.size());System.exit(1);
			
			//FIND BEST FRAME in case of only one hit it will be the first, which should be fine as well (as they are ordered like the rapsearch output)
			String bestFrame = "";
			int bestCount = -1;
			for (String frame : count.keySet()){
				if (count.get(frame) > bestCount){
					bestFrame = frame;
					bestCount =count.get(frame); 
				}	
			}
			//System.exit(1);
			
			
			
			String bestStrand = bestFrame.substring(0,1);
			String bestFrameNumber = bestFrame.substring(1,2);
			int frameshifted = 0;
			if (count.keySet().size() > 1) {frameshifted = 1;}
			String full_frame_info = "hit=\"" + topHit + "\"";
			if( frameshifted > 0){
				String temp = "";
				for (String frame : count.keySet()){
					if (!frame.equals(bestFrame)){
						if (temp.equals("")){
							temp = frame;
						} 
						else {
							temp = temp + ";" + frame;
						}
					}
				}
				full_frame_info = full_frame_info + ";alternative_frames=\"" + temp + "\"";
			}
			
			stmt_update_frames.setString(1, bestFrameNumber);
			stmt_update_frames.setString(2, bestStrand);
			stmt_update_frames.setString(3, full_frame_info);
			stmt_update_frames.setString(4, "" + frameshifted);
			stmt_update_frames.setString(5, gene);
			
			//System.out.println(stmt_update_frames.toString());
			try{
				stmt_update_frames.execute();
			}
			catch(Exception exc){
				System.err.println("Problem updating transcript with correct frame information.");
				System.err.println("Transcript : "+gene);
				System.err.println("Raw frame : "+bestFrame);
				System.err.println("Best Frame : "+bestFrameNumber);
				System.err.println("Best Strand : "+bestStrand);
				System.err.println("Full frame info : "+full_frame_info);
				System.err.println("Putative frameshift : "+frameshifted);				
				throw exc;
			}
			
			//System.out.println(gene + "\t" + full_frame_info);
		}
		stmt_update_frames.close();
		
	}
	
	
	
	
	
	/**
	 * Funtion to perform intitial ORF prediction based on detected frame
	 * 
	 *
	 */
	public void performInitialORFPrediction(Connection db_connection,String experiment_id) throws Exception{
		ORFFinder orf_finder = new ORFFinder();
		
		//First get all genes + transcripts in the experiment
		Hashtable<String, String> geneSequences = new Hashtable<String, String>();
		Hashtable<String, Character> geneStrand = new Hashtable<String, Character>();
		Hashtable<String, Integer> geneFrame = new Hashtable<String, Integer>();
		
		String query_transcripts	= "SELECT `transcript_id`, `transcript_sequence`, `detected_strand`, `detected_frame`  FROM `transcripts` WHERE `experiment_id` = " + experiment_id;
		Statement stmt				= db_connection.createStatement();
		ResultSet set				= stmt.executeQuery(query_transcripts);
		while(set.next()){
			geneSequences.put(set.getString(1), set.getString(2));
			geneStrand.put(set.getString(1), set.getString(3).charAt(0));
			geneFrame.put(set.getString(1), set.getInt(4));
		}
		set.close();
		stmt.close();		
		String update_transcripts 					= "UPDATE `transcripts` SET `orf_sequence`= ? , `orf_start` = ? , `orf_stop` = ?, `orf_contains_start_codon` = ?, `orf_contains_stop_codon` = ?  WHERE `experiment_id`='"+experiment_id+"' AND `transcript_id` = ? ";
		PreparedStatement stmt_update_transcripts	= db_connection.prepareStatement(update_transcripts);		
		String update_frame_info					= "UPDATE `transcripts` SET `detected_frame`=? , `detected_strand` = ? WHERE `experiment_id`='"+experiment_id+"' AND `transcript_id` = ? ";
		PreparedStatement stmt_update_frame			= db_connection.prepareStatement(update_frame_info);		
		for (String gene: geneSequences.keySet()){
			String sequence 		= geneSequences.get(gene);
			char strand 			= geneStrand.get(gene);
			int frame 				= geneFrame.get(gene);
			Map<String,String> res	= orf_finder.findLongestORF(sequence, strand, frame);
			//default update of ORF information
			stmt_update_transcripts.setString(1,res.get("ORF"));
			stmt_update_transcripts.setString(2,res.get("start"));
			stmt_update_transcripts.setString(3,res.get("stop"));			
			stmt_update_transcripts.setString(4,Boolean.parseBoolean(res.get("hasStartCodon"))?"1":"0");
			stmt_update_transcripts.setString(5,Boolean.parseBoolean(res.get("hasStopCodon"))?"1":"0");
			stmt_update_transcripts.setString(6, gene);
			stmt_update_transcripts.execute();
			//if new frame information is present in result, do second update as well.
			if(res.containsKey("newFrame")){
				stmt_update_frame.setString(1,res.get("newFrame"));
				stmt_update_frame.setString(2,res.get("newStrand"));
				stmt_update_frame.setString(3,gene);
				stmt_update_frame.execute();
			}
		}	
		stmt_update_transcripts.close();
		stmt_update_frame.close();
	}
		
	
	
	/**
	 * Performs the meta-annotation of transcripts. This is done in order to get a clear idea on
	 * how long the transcripts are compared to their background frequency.
	 * 
	 * @param plaza_connection Connection to the PLAZA database
	 * @param trapid_connection Connection to the trapid database
	 * @param trapid_exp_id Trapid experiment identifier
	 * @param transcript2gf Mapping of transcripts to gene families
	 * @param gf2transcript Mapping of gene family identifiers to transcripts
	 * @param gf_type Type of gene family (HOM or iOrtho).
	 * @throws Exception Database failures
	 */
	public void performMetaAnnotationPrediction(Connection plaza_connection,Connection trapid_connection,
			String plaza_database_name,
			String trapid_exp_id,Map<String,GeneFamilyAssignment> transcript2gf,Map<String,List<String>>gf2transcripts,
			GF_TYPE gf_type) throws Exception{
		
		System.out.println("Performing meta annotation analysis");		
		
		//caching, because otherwise it takes far too long.
		long tt11									= System.currentTimeMillis();
		Map<String,Integer>	transcript_orf_lengths	= this.getTranscriptOrfLengths(trapid_connection,trapid_exp_id);
		Map<String,boolean[]> transcript_startstop	= this.getTranscriptStartStop(trapid_connection,trapid_exp_id);
		long tt12									= System.currentTimeMillis();
		timing("Caching transcript lengths and start/stop information",tt11,tt12,2);
		
		
		long tt21									= System.currentTimeMillis();
		Map<String,Integer> gene_cds_lengths		= this.getGeneCdsLengths(plaza_connection);
		long tt22									= System.currentTimeMillis();
		timing("Caching cds lengths",tt21,tt22,2);
		
		long tt31									= System.currentTimeMillis();
		Map<String,Set<String>> hom_family_content	= null;
		if(gf_type==GF_TYPE.HOM){
			String gf_prefix						= this.getGfPrefix(trapid_connection,plaza_database_name);
			hom_family_content						= this.getFamilyContent(plaza_connection,gf_prefix);
		}
		long tt32									= System.currentTimeMillis();
		timing("Caching gene family content",tt31,tt32,2);
						
		Map<String,String[]> transcript2meta	= new HashMap<String,String[]>();
		
		long tt41									= System.currentTimeMillis();
		//now, perform this meta annotation prediction for each transcript, per gene family.
		//this is why we iterate over the gene families.		
		for(String gf_id : gf2transcripts.keySet()){
			//we take the first transcript as reference
			GeneFamilyAssignment gas	= transcript2gf.get(gf2transcripts.get(gf_id).get(0));
			Set<String> genes			= null;
			//dependent on type of gene family, get genes from DB or from storage
			if(gf_type==GF_TYPE.HOM){
				genes					= hom_family_content.get(gf_id);
			}
			else if(gf_type==GF_TYPE.IORTHO){
				genes = gas.gf_content;
			}
								
			if(genes.size()<META_MIN_GF_SIZE){
				for(String transcript_id : gf2transcripts.keySet()){
					String[] meta	= {"No Information",""};
					transcript2meta.put(transcript_id, meta);						
				}				
			}
			else{
			
				//ok, now retrieve the length of the CDS sequences for all genes in this gene family.
				List<Integer> cds_lengths	= new ArrayList<Integer>();
				for(String gene_id : genes){
					if(gene_cds_lengths.containsKey(gene_id)){
						cds_lengths.add(gene_cds_lengths.get(gene_id));
					}				
				}

				//now, get statistical analysis of the gene family content
				LengthAnalysis	la		= this.getLengthAnalysis(gf_id,cds_lengths,true);

				//retrieve orf lengths of the transcripts
				Map<String,Integer> transcript2orflength	= new HashMap<String,Integer>();
				for(String transcript_id : gf2transcripts.get(gf_id)){
					int length			= 0;
					if(transcript_orf_lengths.containsKey(transcript_id)){
						length			= transcript_orf_lengths.get(transcript_id);
					}				
					transcript2orflength.put(transcript_id, length);
				}


				//now, compare each transcript ORF length to the gene family length analysis
				for(String transcript_id : transcript2orflength.keySet()){
					boolean[] codon_info		= transcript_startstop.get(transcript_id);
					int orf_length				= transcript2orflength.get(transcript_id);
					String[] meta_annot			= this.getMetaAnnot(orf_length,la.average,la.std_deviation,codon_info);
					transcript2meta.put(transcript_id, meta_annot);					
				}
				transcript2orflength.clear();
				cds_lengths.clear();
			}
		}	
		long tt42									= System.currentTimeMillis();
		timing("Computing meta annotations",tt41,tt42,2);
		
		
		
		long tt51									= System.currentTimeMillis();		
		String insert_meta_annotation		= "UPDATE `transcripts` SET `meta_annotation`=?, `meta_annotation_score`=? WHERE `experiment_id`='"+trapid_exp_id+"' AND `transcript_id` = ? ";
		PreparedStatement stmt_meta_annot	= trapid_connection.prepareStatement(insert_meta_annotation);
		for(String transcript_id:transcript2meta.keySet()){
			String[] meta_annot	= transcript2meta.get(transcript_id);
			stmt_meta_annot.setString(1,meta_annot[0]);
			stmt_meta_annot.setString(2,meta_annot[1]);
			stmt_meta_annot.setString(3,transcript_id);
			stmt_meta_annot.execute();			
		}
		stmt_meta_annot.close();
		long tt52							= System.currentTimeMillis();
		timing("Storing meta annotations in database",tt51,tt52,2);
		
		long tt61									= System.currentTimeMillis();
		transcript2meta.clear();
		transcript_orf_lengths.clear();
		gene_cds_lengths.clear();
		transcript_startstop.clear();
		if(hom_family_content!=null){
			hom_family_content.clear();
		}
		long tt62							= System.currentTimeMillis();
		timing("Clearing temp storage",tt61,tt62,2);
	}
	
	
	private String[] getMetaAnnot(int orf_length,int avg,int std_dev,boolean[] start_stop_codon){
		String meta_annot			= "No Information";
		String meta_annot_score		= "";
		
		if(orf_length>=(avg-2*std_dev)){
			meta_annot_score		= "std_dev="+std_dev+";avg="+avg+";orf_length="+orf_length+";cutoff="+(avg-2*std_dev);
			if(start_stop_codon[0] && start_stop_codon[1]){
				meta_annot				= "Full Length";
			}
			else{
				meta_annot				= "Quasi Full Length";	
			}
		}
		else{
			meta_annot				= "Partial";
			meta_annot_score		= "std_dev="+std_dev+";avg="+avg+";orf_length="+orf_length+";cutoff="+(avg-2*std_dev);
		}			
		String[] result				= {meta_annot,meta_annot_score};
		return result;
	}
	
	
	
	
	/**
	 * Computes some statistical information on the lengths of a set of sequences.
	 * @param data Lengths of a set of sequences. List, because the sequences can be of equal length
	 * @param remove_outliers If true, remove outliers (outside [avg-3*dev,avg+3*dev]) and compute results  
	 * @return Object containing results of statistical analysis.
	 */
	public LengthAnalysis getLengthAnalysis(String gf_id,List<Integer> data,boolean remove_outliers){		
		//int min					= Integer.MAX_VALUE;
		//int max					= Integer.MIN_VALUE;
		long average_sum			= 0;	
		for(int d: data){
			//if(d>max){max = d;}
			//if(d<min){min = d;}
			average_sum += d;
		}
		
		int average					= (int)(average_sum/data.size());	
		long std_dev_sum		= 0;
		for(int d:data){
			long temp			= d-average;
			std_dev_sum	+= (temp*temp);	
		}	
		int std_dev					= (int)(Math.sqrt(std_dev_sum/data.size()));
				
		if(remove_outliers){
			//clearly some original genes may have been over-under predicted as well. 
			//therefore we remove all those
			Collections.sort(data);
						
			List<Integer> new_data		= new ArrayList<Integer>();
			int to_remove 			= (int)(Math.ceil(META_PERC_REMOVE * (double)(data.size())));
			for(int i=to_remove;i<(data.size()-to_remove);i++){
				new_data.add(data.get(i));
			}
			return this.getLengthAnalysis(gf_id, new_data, false);			
			/*
			int min_outlier				= average-3*std_dev;
			int max_outlier				= average+3*std_dev;
			List<Integer> new_data		= new ArrayList<Integer>();
			for(int d:data){
				if(d>min_outlier && d<max_outlier){
					new_data.add(d);
				}
			}
			if(new_data.size()>=MIN_GF_SIZE_META){				
				return this.getLengthAnalysis(gf_id,new_data, false);
			}
			else{
				LengthAnalysis la		= new LengthAnalysis(average,std_dev);
				return la;
			}
			*/
		}
		else{	
			/*
			if(std_dev==0){
				System.out.println("No outlier removal : "+gf_id+"\t"+data.toString());
			}*/
			LengthAnalysis la		= new LengthAnalysis(average,std_dev);
			return la;
		}
	}
	
	
	
	
	public class LengthAnalysis{
	//	public int min				= 0;
	//	public int max				= 0;
		public int average			= 0;
		public int std_deviation	= 0;
		public LengthAnalysis(int avg,int std_dev){		
			this.average	= avg;
			this.std_deviation	= std_dev;
		}
		/*public LengthAnalysis(int min,int max,int avg,int std_dev){
			this.min	= min;
			this.max	= max;
			this.average	= avg;
			this.std_deviation	= std_dev;
		}*/
	}
	
	
	
	
	/*--------------------------------------------------------------------------------------------*/
	/*--------------------------------------------------------------------------------------------*/
	/* Assigning GO's and InterPros to Transcripts 	*/
	/*--------------------------------------------------------------------------------------------*/
	/*--------------------------------------------------------------------------------------------*/
	
	/**
	 * Assign GO terms to each transcript, based on the best similarity hit. Return hashmap with this assignment.
	 * @param plaza_connection Connection to PLAZA database
	 * @param simsearch_data Similarity search results
	 * @return Mapping from transcript to GO terms
	 * @throws Exception 
	 */
	private Map<String,Set<String>> assignGoTranscripts_BESTHIT(Connection plaza_connection,
			Map<String,List<String[]>> simsearch_data) throws Exception{
		
		Map<String,Set<String>> transcript_go	= new HashMap<String,Set<String>>();
		
		long t11	= System.currentTimeMillis();
		//load the GO parents table into memory to prevent unnecessary queries
		Map<String,Map<String,Set<String>>> go_graph_data	= this.loadGOGraph(plaza_connection);
		Map<String,Set<String>> go_child2parents			= go_graph_data.get("child2parents");
		Map<String,Set<String>> go_parent2children			= go_graph_data.get("parent2children");
		long t12	= System.currentTimeMillis();
		timing("Loading GO graph",t11,t12,2);
		
		long t21	= System.currentTimeMillis();
		Map<String,Set<String>> gene_go						= this.loadGoData(plaza_connection);
		long t22	= System.currentTimeMillis();
		timing("Caching GO data",t21,t22,2);
												
		long t41	= System.currentTimeMillis();
			
		for(String transcript_id:simsearch_data.keySet()){
			if(simsearch_data.get(transcript_id).size()!=0){
				String best_hit		= simsearch_data.get(transcript_id).get(0)[0];
				//ok, now use the best hit to transfer the functional annotation			
				if(gene_go.containsKey(best_hit)){		
					Set<String> go_terms					= new HashSet<String>();
					//add go terms
					for(String go:gene_go.get(best_hit)){
						go_terms.add(go);
						if(go_child2parents.containsKey(go)){
							for(String go_parent : go_child2parents.get(go)){
								go_terms.add(go_parent);
							}
						}
					}
					//remove the 3 top GO terms (Biological Process, Cellular Component, Molecular Function).
					if(go_terms.contains("GO:0003674")){go_terms.remove("GO:0003674");}
					if(go_terms.contains("GO:0008150")){go_terms.remove("GO:0008150");}
					if(go_terms.contains("GO:0005575")){go_terms.remove("GO:0005575");}	
				
					if(go_terms.size()>0){
						transcript_go.put(transcript_id,go_terms);
					}
				}			
			}			
		}
		long t42	= System.currentTimeMillis();
		timing("Inferring functional annotation per transcript",t41,t42,2);
				
		//clear unnecessary data structures
		long t61	= System.currentTimeMillis();	
		go_child2parents.clear();
		go_parent2children.clear();
		go_graph_data.clear();
		gene_go.clear();
		System.gc();
		long t62	= System.currentTimeMillis();
		timing("Clearing local cache data structures",t61,t62,2);	

		return transcript_go;
	}
	
	/**
	 * Assign GO terms to each transcript, based on the associated gene family (and the 50% rule).
	 * @param plaza_connection Connection to PLAZA database
	 * @param transcript2gf Mapping from transcripts to gene families
	 * @param gf2transcripts Mapping from gene families to transcripts
	 * @param gf_type Type of gene family to be used.
	 * @return Mapping from transcript to GO terms
	 * @throws Exception 
	 */
	private Map<String,Set<String>> assignGoTranscripts_GF(Connection plaza_connection,
			Map<String,GeneFamilyAssignment> transcript2gf,Map<String,List<String>>gf2transcripts,
			GF_TYPE gf_type) throws Exception{
		
		
		Map<String,Set<String>> transcript_go	= new HashMap<String,Set<String>>();
				
		long t11	= System.currentTimeMillis();
		//load the GO parents table into memory to prevent unnecessary queries
		Map<String,Map<String,Set<String>>> go_graph_data	= this.loadGOGraph(plaza_connection);
		Map<String,Set<String>> go_child2parents			= go_graph_data.get("child2parents");
		Map<String,Set<String>> go_parent2children			= go_graph_data.get("parent2children");
		long t12	= System.currentTimeMillis();
		timing("Loading GO graph",t11,t12,2);
		
		long t21	= System.currentTimeMillis();
		Map<String,Set<String>> gene_go						= this.loadGoData(plaza_connection);
		long t22	= System.currentTimeMillis();
		timing("Caching GO data",t21,t22,2);
		
		//necessary queries
		String query_hom_genes				= "SELECT `gene_id` FROM `gf_data` WHERE `gf_id` = ? ";
		PreparedStatement stmt_hom_genes	= plaza_connection.prepareStatement(query_hom_genes);
			
		long t41	= System.currentTimeMillis();
		for(String gf_id : gf2transcripts.keySet()){
			//we take the first transcript as reference
			GeneFamilyAssignment gas	= transcript2gf.get(gf2transcripts.get(gf_id).get(0));
							
			//PART 0: RETRIEVE PLAZA GENE CONTENT FOR THE GENE FAMILY
			//---------------------------------------------------------
			Set<String> genes			= null;
			//dependent on type of gene family, get genes from DB or from storage
			if(gf_type==GF_TYPE.HOM){
				genes			= new HashSet<String>();
				stmt_hom_genes.setString(1,gf_id);
				ResultSet set	= stmt_hom_genes.executeQuery();
				while(set.next()){genes.add(set.getString(1));}
				set.close();
			}
			else if(gf_type==GF_TYPE.IORTHO){
				genes = gas.gf_content;
			}

			//PART 1: GO ANNOTATION
			//-------------------------------------------
			//mapping of GO terms to genes! Better than genes to GO, because we actually need
			//the GO terms. The genes are just for counting.
			Map<String,Set<String>> go_genes	= new HashMap<String,Set<String>>();
			//okay, now retrieve the GO data for these genes.
			for(String gene_id : genes){
				if(gene_go.containsKey(gene_id)){
					Set<String> assoc_go			= gene_go.get(gene_id);			
					for(String go:assoc_go){
						if(!go_genes.containsKey(go)){go_genes.put(go,new HashSet<String>());}
						go_genes.get(go).add(gene_id);
						//add parental gos as well
						if(go_child2parents.containsKey(go)){
							for(String go_parent : go_child2parents.get(go)){
								if(!go_genes.containsKey(go_parent)){go_genes.put(go_parent, new HashSet<String>());}
								go_genes.get(go_parent).add(gene_id);
							}
						}
					}		
				}
			}			
			//remove the 3 top GO terms (Biological Process, Cellular Component, Molecular Function).
			if(go_genes.containsKey("GO:0003674")){go_genes.remove("GO:0003674");}
			if(go_genes.containsKey("GO:0008150")){go_genes.remove("GO:0008150");}
			if(go_genes.containsKey("GO:0005575")){go_genes.remove("GO:0005575");}	
			
			//now, iterate over all the GO identifiers, and select those who are present in at least
			//50% of the genes associated with this gene family
			Set<String> selected_gos	= new HashSet<String>();
			double gene_gf_count		= gas.gf_size;
			for(String go_id:go_genes.keySet()){
				double gene_go_count	= go_genes.get(go_id).size();
				if(gene_go_count/gene_gf_count >= 0.5){
					selected_gos.add(go_id);				
				}
			}			
			
			//now add these selected gos to each of the transcript family members of the gene family.
			for(String transcript_id: gf2transcripts.get(gf_id)){
				transcript_go.put(transcript_id,new HashSet<String>(selected_gos));
			}
			
			//clear the temporary storage for this gene family.
			go_genes.clear();								
		}
		long t42	= System.currentTimeMillis();
		timing("Inferring functional annotation per gene family",t41,t42,2);
		
		
		//clear unnecessary data structures
		long t61	= System.currentTimeMillis();	
		go_child2parents.clear();
		go_parent2children.clear();
		go_graph_data.clear();
		gene_go.clear();
		System.gc();
		long t62	= System.currentTimeMillis();
		timing("Clearing local cache data structures",t61,t62,2);
		
		return transcript_go;
	}	
	
	
	
	
	/**
	 * Assign GO terms to each transcript, based on both the gene family and the best similarity hit (take all
	 * data if possible). Just a conglemeration of 2 other methods, with a merging.
	 * @param plaza_connection Connection to PLAZA database
	 * @param transcript2gf Mapping from transcripts to gene families
	 * @param gf2transcripts Mapping from gene families to transcripts
	 * @param gf_type Type of gene family to be used.
	 * @param simsearch_data Similarity search results
	 * @return Mapping from transcript to GO terms
	 * @throws Exception
	 */
	private Map<String,Set<String>> assignGoTranscripts_GF_BESTHIT(Connection plaza_connection,
			Map<String,GeneFamilyAssignment> transcript2gf,Map<String,List<String>>gf2transcripts,
			GF_TYPE gf_type,Map<String,List<String[]>> simsearch_data
	) throws Exception{
		Map<String,Set<String>> transcript_go	= new HashMap<String,Set<String>>();
		
		Map<String,Set<String>> transcript_go_besthit	= this.assignGoTranscripts_BESTHIT(plaza_connection, simsearch_data);
		Map<String,Set<String>> transcript_go_gf		= this.assignGoTranscripts_GF(plaza_connection, transcript2gf, gf2transcripts, gf_type);
		
		transcript_go.putAll(transcript_go_besthit);
		for(String transcript:transcript_go_gf.keySet()){
			if(!transcript_go.containsKey(transcript)){transcript_go.put(transcript, transcript_go_gf.get(transcript));}
			else{transcript_go.get(transcript).addAll(transcript_go_gf.get(transcript));}
		}		
		return transcript_go;
	}	  
	
	/**
	 * Hide GO terms for which certain parental/child terms are better suited.
	 * @param plaza_connection PLAZA database connection
	 * @param transcript_go Mapping from transcripts to GO terms
	 * @return Mapping from transcripts to GO terms to hide value
	 * @throws Exception
	 */
	private Map<String,Map<String,Integer>> hideGoTerms(Connection plaza_connection,Map<String,Set<String>> transcript_go)throws Exception{
		Map<String,Map<String,Integer>> result				= new HashMap<String,Map<String,Integer>>();
		Map<String,Map<String,Set<String>>> go_graph_data	= this.loadGOGraph(plaza_connection);
		Map<String,Set<String>> go_parent2children			= go_graph_data.get("parent2children");
		for(String transcript:transcript_go.keySet()){
			Set<String> go_terms							= transcript_go.get(transcript);
			Map<String,Integer> go_hidden					= this.hide_go_terms(go_terms, go_parent2children);
			result.put(transcript,go_hidden);
		}
		go_parent2children.clear();
		go_graph_data.clear();
		System.gc();		
		return result;
	}
	
	
	/**
	 * Assign protein domains to each transcript, based on the best similarity hit. Return hashmap with this assignment.
	 * @param plaza_connection Connection to PLAZA database
	 * @param simsearch_data Similarity search results
	 * @return Mapping from transcript to protein domains
	 * @throws Exception 
	 */
	private Map<String,Set<String>> assignProteindomainTranscripts_BESTHIT(Connection plaza_connection,
			Map<String,List<String[]>> simsearch_data) throws Exception{
		
		Map<String,Set<String>> transcript_interpro	= new HashMap<String,Set<String>>();
				
		long t31	= System.currentTimeMillis();
		Map<String,Set<String>> gene_interpro				= this.loadInterproData(plaza_connection);
		long t32	= System.currentTimeMillis();
		timing("Caching Interpro data",t31,t32,2);
				
		long t41	= System.currentTimeMillis();		
		for(String transcript_id:simsearch_data.keySet()){
			if(simsearch_data.get(transcript_id).size()!=0){
				String best_hit		= simsearch_data.get(transcript_id).get(0)[0];
				//ok, now use the best hit to transfer the functional annotation			
				
				if(gene_interpro.containsKey(best_hit)){
					Set<String> interpros			= new HashSet<String>();
					//add interpros
					for(String interpro:gene_interpro.get(best_hit)){
						interpros.add(interpro);
					}
					if(interpros.size()>0){
						transcript_interpro.put(transcript_id, interpros);
					}
				}
			}			
		}

		long t42	= System.currentTimeMillis();
		timing("Inferring functional annotation per transcript",t41,t42,2);
				
		//clear unnecessary data structures
		long t61	= System.currentTimeMillis();	
		gene_interpro.clear();
		System.gc();
		long t62	= System.currentTimeMillis();
		timing("Clearing local cache data structures",t61,t62,2);	
				
		return transcript_interpro;
	}
	
	/**
	 * Assign protein domains to each transcript, based on the associated gene family (and the 50% rule).
	 * @param plaza_connection Connection to PLAZA database
	 * @param transcript2gf Mapping from transcripts to gene families
	 * @param gf2transcripts Mapping from gene families to transcripts
	 * @param gf_type Type of gene family to be used.
	 * @return Mapping from transcript to protein domains
	 * @throws Exception 
	 */
	private Map<String,Set<String>> assignProteindomainTranscripts_GF(Connection plaza_connection,
			Map<String,GeneFamilyAssignment> transcript2gf,Map<String,List<String>>gf2transcripts,
			GF_TYPE gf_type) throws Exception{
		
		Map<String,Set<String>> transcript_interpro	= new HashMap<String,Set<String>>();
					
		long t31	= System.currentTimeMillis();
		Map<String,Set<String>> gene_interpro				= this.loadInterproData(plaza_connection);
		long t32	= System.currentTimeMillis();
		timing("Caching Interpro data",t31,t32,2);
								
		//necessary queries
		String query_hom_genes				= "SELECT `gene_id` FROM `gf_data` WHERE `gf_id` = ? ";
		PreparedStatement stmt_hom_genes	= plaza_connection.prepareStatement(query_hom_genes);
			
		long t41	= System.currentTimeMillis();
		for(String gf_id : gf2transcripts.keySet()){
			//we take the first transcript as reference
			GeneFamilyAssignment gas	= transcript2gf.get(gf2transcripts.get(gf_id).get(0));
							
			//PART 0: RETRIEVE PLAZA GENE CONTENT FOR THE GENE FAMILY
			//---------------------------------------------------------
			Set<String> genes			= null;
			//dependent on type of gene family, get genes from DB or from storage
			if(gf_type==GF_TYPE.HOM){
				genes			= new HashSet<String>();
				stmt_hom_genes.setString(1,gf_id);
				ResultSet set	= stmt_hom_genes.executeQuery();
				while(set.next()){genes.add(set.getString(1));}
				set.close();
			}
			else if(gf_type==GF_TYPE.IORTHO){
				genes = gas.gf_content;
			}

			//PART 2 : INTERPRO ANNOTATION
			//-------------------------------------------------------------
			//mapping of interpro domains to genes. The genes are just for counting
			//okay, now retrieve the GO data for these genes.
			Map<String,Set<String>> interpro_genes	= new HashMap<String,Set<String>>();
			for(String gene_id : genes){
				if(gene_interpro.containsKey(gene_id)){
					Set<String> assoc_interpro			= gene_interpro.get(gene_id);
					for(String ipr:assoc_interpro){
						if(!interpro_genes.containsKey(ipr)){interpro_genes.put(ipr,new HashSet<String>());}
						interpro_genes.get(ipr).add(gene_id);
					}
				}				
			}
						
			//now, iterate over all the Interpro identifiers, and select those who are present in at least
			//50% of the genes associated with this gene family
			double gene_gf_count		= gas.gf_size;
			Set<String> selected_interpros	= new HashSet<String>();
			for(String ipr_id:interpro_genes.keySet()){
				double gene_ipr_count	= interpro_genes.get(ipr_id).size();
				if(gene_ipr_count/gene_gf_count >= 0.5){
					selected_interpros.add(ipr_id);					
				}
			}
			
			//now add these selected gos to each of the transcript family members of the gene family.
			for(String transcript_id: gf2transcripts.get(gf_id)){
				transcript_interpro.put(transcript_id,new HashSet<String>(selected_interpros));
			}
			
			interpro_genes.clear();					
		}
		long t42	= System.currentTimeMillis();
		timing("Inferring functional annotation per gene family",t41,t42,2);
				
		
		//clear unnecessary data structures
		long t61	= System.currentTimeMillis();
		gene_interpro.clear();		
		System.gc();
		long t62	= System.currentTimeMillis();
		timing("Clearing local cache data structures",t61,t62,2);	

		return transcript_interpro;
	}	
	
	/**
	 * Assign protein domains to each transcript, based on both the gene family and the best similarity hit (take all
	 * data if possible). Just a conglemeration of 2 other methods, with a merging.
	 * @param plaza_connection Connection to PLAZA database
	 * @param transcript2gf Mapping from transcripts to gene families
	 * @param gf2transcripts Mapping from gene families to transcripts
	 * @param gf_type Type of gene family to be used.
	 * @param simsearch_data Similarity search results
	 * @return Mapping from transcript to protein domains
	 * @throws Exception
	 */
	private Map<String,Set<String>> assignProteindomainTranscripts_GF_BESTHIT(Connection plaza_connection,
			Map<String,GeneFamilyAssignment> transcript2gf,Map<String,List<String>>gf2transcripts,
			GF_TYPE gf_type,Map<String,List<String[]>> simsearch_data
	) throws Exception{
		Map<String,Set<String>> transcript_interpro	= new HashMap<String,Set<String>>();
		
		Map<String,Set<String>> transcript_interpro_besthit	= this.assignProteindomainTranscripts_BESTHIT(plaza_connection, simsearch_data);
		Map<String,Set<String>> transcript_interpro_gf		= this.assignProteindomainTranscripts_GF(plaza_connection, transcript2gf, gf2transcripts, gf_type);
		
		transcript_interpro.putAll(transcript_interpro_besthit);
		for(String transcript:transcript_interpro_gf.keySet()){
			if(!transcript_interpro.containsKey(transcript)){transcript_interpro.put(transcript, transcript_interpro_gf.get(transcript));}
			else{transcript_interpro.get(transcript).addAll(transcript_interpro_gf.get(transcript));}
		}		
		return transcript_interpro;
	}	  
	
	
	
	
	/*--------------------------------------------------------------------------------------------*/
	/*--------------------------------------------------------------------------------------------*/
	/* Storing GO's and InterPros to Transcripts 	*/
	/*--------------------------------------------------------------------------------------------*/
	/*--------------------------------------------------------------------------------------------*/

	/**
	 * Store all the transcript - interpro associations, that were detected
	 * @param trapid_connection Connection to trapid database 
	 * @param trapid_exp_id Trapid experiment id
	 * @param transcript_interpro Mapping from transcripts to protein domains
	 * @throws Exception 
	 */
	private void storeGoTranscripts(Connection trapid_connection,String trapid_exp_id,Map<String,Map<String,Integer>>transcript_go_hidden) throws Exception{
		long t51							= System.currentTimeMillis();	
		String insert_go_annot				= "INSERT INTO `transcripts_go` (`experiment_id`,`transcript_id`,`go`,`is_hidden`) VALUES ('"+trapid_exp_id+"',?,?,?) ";
		PreparedStatement ins_go_annot		= trapid_connection.prepareStatement(insert_go_annot);
		boolean prev_commit_state			= trapid_connection.getAutoCommit();
		trapid_connection.setAutoCommit(false);
		
		for(String transcript_id:transcript_go_hidden.keySet()){							
			for(String go_id:transcript_go_hidden.get(transcript_id).keySet()){
				String hidden_status	= ""+transcript_go_hidden.get(transcript_id).get(go_id);
				ins_go_annot.setString(1,transcript_id);
				ins_go_annot.setString(2,go_id);
				ins_go_annot.setString(3,hidden_status);
				ins_go_annot.addBatch();		
			}
			ins_go_annot.executeBatch();
			trapid_connection.commit();
			ins_go_annot.clearBatch();
		}

		trapid_connection.setAutoCommit(prev_commit_state);
		long t52	= System.currentTimeMillis();
		timing("Storing GO functional annotation in database per transcript",t51,t52,2);			
		//close all statements	
		ins_go_annot.close();		
		//clear unnecessary data structures
		long t61	= System.currentTimeMillis();	
		transcript_go_hidden.clear();
		System.gc();
		long t62	= System.currentTimeMillis();
		timing("Clearing GO local cache data structures",t61,t62,2);	
	}
	
	
	/**
	 * Store all the transcript - interpro associations, that were detected
	 * @param trapid_connection Connection to trapid database 
	 * @param trapid_exp_id Trapid experiment id
	 * @param transcript_interpro Mapping from transcripts to protein domains
	 * @throws Exception 
	 */
	private void storeInterproTranscripts(Connection trapid_connection,String trapid_exp_id,Map<String,Set<String>>transcript_interpro) throws Exception{
		long t51							= System.currentTimeMillis();			
		String insert_ipr_annot				= "INSERT INTO `transcripts_interpro` (`experiment_id`,`transcript_id`,`interpro`) VALUES ('"+trapid_exp_id+"', ? , ? )  ";
		PreparedStatement ins_ipr_annot		= trapid_connection.prepareStatement(insert_ipr_annot);			
		boolean prev_commit_state			= trapid_connection.getAutoCommit();
		trapid_connection.setAutoCommit(false);
		
		for(String transcript_id:transcript_interpro.keySet()){			
			for(String ipr_id:transcript_interpro.get(transcript_id)){
				ins_ipr_annot.setString(1,transcript_id);
				ins_ipr_annot.setString(2,ipr_id);
				ins_ipr_annot.addBatch();
			}
			ins_ipr_annot.executeBatch();		
			trapid_connection.commit();
			ins_ipr_annot.clearBatch();
		}

		trapid_connection.setAutoCommit(prev_commit_state);
		long t52	= System.currentTimeMillis();
		timing("Storing InterPro functional annotation in database per transcript",t51,t52,2);			
		//close all statements	
		ins_ipr_annot.close();		
		//clear unnecessary data structures
		long t61	= System.currentTimeMillis();	
		transcript_interpro.clear();
		System.gc();
		long t62	= System.currentTimeMillis();
		timing("Clearing InterPro local cache data structures",t61,t62,2);	
	}
	
	
	
	/*--------------------------------------------------------------------------------------------*/
	/*--------------------------------------------------------------------------------------------*/
	/*--------------------------------------------------------------------------------------------*/
	/*--------------------------------------------------------------------------------------------*/
	
	

	
	/**
	 * Method (derived from GO enrichment) to reduce the number of GO terms visible to the user.
	 * Usefull, because otherwise entire GO graphs are displayed to the user, which isn't informative anymore.
	 * Basically, hide parental GO terms, but still include the child GO terms.
	 * 
	 * @param selected_gos Set of GO terms that are associated with a given gene family (and as such with the transcripts)
	 * @param go_parent2children Mapping of parental GO terms to their child GO terms
	 * @return Set of GO terms to an indicator whether or not they should be hidden in the database.
	 * @throws Exception Database failure.
	 */
	public Map<String,Integer> hide_go_terms(Set<String> selected_gos,Map<String,Set<String>> go_parent2children) throws Exception{
		Map<String,Integer> result		= new HashMap<String,Integer>();
		for(String go_id:selected_gos){
			int is_hidden				= 0;
			if(go_parent2children.containsKey(go_id)){
				boolean has_present_child	= false;
				for(String child_go : go_parent2children.get(go_id)){
					if(selected_gos.contains(child_go)){
						has_present_child	= true;
						break;
					}
				}				
				if(has_present_child){
					is_hidden			= 1;
				}
			}
			result.put(go_id, is_hidden);
		}		
		return result;
	}
	
	
	
	
	
	/*
	 * Class representing a gene family to which a transcript is assigned
	 */
	public class GeneFamilyAssignment{	
		
		public GeneFamilyAssignment(String gf_id,String gf_assignment_score){
			super();
			this.gf_id	= gf_id;
			this.gf_assignment_score =gf_assignment_score;
			this.gf_content	= new HashSet<String>();
		}
		
		public String getGfContent(){
			StringBuffer buffer	= new StringBuffer();
			if(this.gf_content.size()==0){return buffer.toString();}
			for(String gfc: this.gf_content){
				buffer.append(" "+gfc);
			}			
			String res	= buffer.toString().substring(1);
			return res;
		}
		
		public String gf_id					= null;
		public String gf_assignment_score	= null;
		public Set<String> gf_content		= null;
		public int gf_size					= 0;
		
		public int associated_genes			= 0;
	}
	
	
	
	
	
	/**
	 * This function stores the mapping from each transcript to a gene family into the database.
	 * @param trapid_exp_id TRAPID experiment identifier
	 * @param gf_map Mapping of each transcript to a gene family.
	 * @throws Exception In case of 
	 */
	private void storeGeneFamilyAssignments(Connection trapid_db_connection,String trapid_exp_id,Map<String,GeneFamilyAssignment> transcript2gf,GF_TYPE gf_type)throws Exception{
		//step1: update the transcripts table (columns gf_id and gf_id_score).
		String sql1		= "UPDATE `transcripts` SET `gf_id`= ? , `gf_id_score` = ? WHERE `experiment_id`='"+trapid_exp_id+"' AND `transcript_id` = ? ";
		//step2: insert the gene families into the database. Take care of the gene family type
		String sql2a	= "INSERT INTO `gene_families` (`experiment_id`,`gf_id`,`plaza_gf_id`,`num_transcripts`) VALUES ('"+trapid_exp_id+"', ? , ? , ? )";
		String sql2b	= "INSERT INTO `gene_families` (`experiment_id`,`gf_id`,`gf_content`,`num_transcripts`) VALUES ('"+trapid_exp_id+"', ? , ? , ? )";
		PreparedStatement stmt1	= trapid_db_connection.prepareStatement(sql1);
		PreparedStatement stmt2	= null; 
		if(gf_type==GF_TYPE.HOM){stmt2=trapid_db_connection.prepareStatement(sql2a);}
		else if(gf_type==GF_TYPE.IORTHO){stmt2=trapid_db_connection.prepareStatement(sql2b);}

		//for secondary database insertions
		Map<String,GeneFamilyAssignment> gf_information	= new HashMap<String,GeneFamilyAssignment>();
		
		//first: update the transcripts with their associated gene family
		for(String transcript_id :transcript2gf.keySet()){
			GeneFamilyAssignment gfa	= transcript2gf.get(transcript_id);
			String trapid_gf_id			= trapid_exp_id+"_"+gfa.gf_id;
			stmt1.setString(1,trapid_gf_id);
			stmt1.setString(2,gfa.gf_assignment_score);
			stmt1.setString(3,transcript_id);
			stmt1.execute();
			
			//now, update the gf_information for the second batch of updates
			if(!gf_information.containsKey(trapid_gf_id)){
				gfa.associated_genes	= 1;
				gf_information.put(trapid_gf_id, gfa);
			}
			else{
				gf_information.get(trapid_gf_id).associated_genes++; 
			}			
		}
		
		//now, insert the gene families into the database.
		for(String trapid_gf_id : gf_information.keySet()){
			GeneFamilyAssignment gas	= gf_information.get(trapid_gf_id);			
			if(gf_type==GF_TYPE.HOM){				
				stmt2.setString(1,trapid_gf_id);
				stmt2.setString(2,gas.gf_id);
				stmt2.setString(3,""+gas.associated_genes);
			}
			else if(gf_type==GF_TYPE.IORTHO){
				stmt2.setString(1,trapid_gf_id);
				stmt2.setString(2,gas.getGfContent());
				stmt2.setString(3,""+gas.associated_genes);
			}
			stmt2.execute();
		}
		
		stmt1.close();
		stmt2.close();	
		gf_information.clear();
	}
	
	
	
	
	private String getGfPrefix(Connection trapid_db_connection,String plaza_db_name) throws Exception{
		String result		= "";
		Statement stmt		= trapid_db_connection.createStatement();
		String sql			= "SELECT `gf_prefix` FROM `data_sources` WHERE `db_name` = '"+plaza_db_name+"' ";
		ResultSet set		= stmt.executeQuery(sql);
		if(set.next()){
			result			= set.getString("gf_prefix");
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	/**
	 * Perform the actual transcript to homology assignment (TribeMCL clusters), based on the similarity data output.
	 * Result consists of a mapping of the transcript, to the gene family.
	 * @param plaza_db_connection Connection to PLAZA database
	 * @param data Data from similarity search: 
	 * @param num_hits Number of hits to take into account for HOM assignment per transcript
	 * @param gf_prefix Prefix on which to select the gene families.
	 * @return Mapping of transcripts to gene families
	 * @throws Exception Database problems
	 */
	private Map<String,GeneFamilyAssignment> inferTranscriptGenefamiliesHom(Connection plaza_db_connection,Map<String,List<String[]>> data, int num_hits,String gf_prefix) throws Exception{
		Map<String,GeneFamilyAssignment> result		= new HashMap<String,GeneFamilyAssignment>();
		
		//storing homology information from PLAZA database in memory. Way faster than 
		//subsequent queries
		Map<String,String> gene2gf					= this.loadGeneFamilies(plaza_db_connection,gf_prefix);
		Map<String,Integer> gf_sizes				= this.determinePLAZAGfSizes(gene2gf);
		
		//split up in 2 different modes: easy mode where num_top_hits is equal to 1, the more 		
		//computationally difficuly mode where num_top_hits is larger than 1.
		
		if(num_hits==1){ //EASY MODE, ONLY TAKE TOP HIT
			for(String transcript_id: data.keySet()){
				//top_hit : (gene_id,bitscore,query_start) 
				String[] top_hit	= data.get(transcript_id).get(0);	//there is always at least 1 hit
				String gf_id		= gene2gf.get(top_hit[0]);	//each gene is present in a gene family (might be singleton)
				String gf_score		= "1";
				GeneFamilyAssignment gfa	= new GeneFamilyAssignment(gf_id,gf_score);
				gfa.gf_size					= gf_sizes.get(gf_id);
				result.put(transcript_id, gfa);
			}			
		}
		else{//DIFFICULT MODE, TAKE 'NUM_TOP_HITS' HITS			
			NumberFormat nf	= NumberFormat.getInstance();
			nf.setMaximumFractionDigits(2);
			for(String transcript_id:data.keySet()){
				Map<String,Integer> gf_counter	= new HashMap<String,Integer>();							
				int max_gf_counter				= 0;
				String current_best_gf			= null;
				List<String[]> search_hits	= data.get(transcript_id);
				//store data 
				for(int i=0;i<search_hits.size()&&i<num_hits;i++){					
					String gf_id				= gene2gf.get(search_hits.get(i)[0]);
					if(!gf_counter.containsKey(gf_id)){gf_counter.put(gf_id, 0);}
					int new_count				= gf_counter.get(gf_id)+1;
					gf_counter.put(gf_id,new_count);
					if(new_count>max_gf_counter){
						current_best_gf			= gf_id;
					}					
				}
				//now, retrieve correct gene family 
				double gf_score_d				= ((double)gf_counter.get(current_best_gf))/((double)(num_hits));
				String gf_score					= nf.format(gf_score_d);
				GeneFamilyAssignment gfa		= new GeneFamilyAssignment(current_best_gf,gf_score);
				gfa.gf_size						= gf_sizes.get(current_best_gf);
				result.put(transcript_id, gfa);
			}			
		}
		
		return result;
	}
	

	

	
	
	
	/**
	 * Perform the actual transcript to IntegrativeOrthology group assignment, based on the 
	 * similarity data output from RapSearch2.
	 * Result consists of a mapping of the transcript, to the gene family.
	 * @param plaza_db_connection Connection to the PLAZA database
	 * @param data Data from similarity search
	 * @param num_hits Number of hits to take into account per transcripts
	 * @param ref_species Reference species 
	 * @return Mapping of transcripts to gene famileis
	 * @throws Exception Databse problems
	 */
	private Map<String,GeneFamilyAssignment> inferTranscriptGenefamiliesIntegrativeOrthology(Connection plaza_db_connection,Map<String,List<String[]>> data) throws Exception{
		Map<String,GeneFamilyAssignment> result		= new HashMap<String,GeneFamilyAssignment>();
		
		//create an SQL-query which performs the necessary database queries for each hit_gene.
		//necessary prepared statement per hit_gene. However, we can try to optimize so the same gene cannot
		//be queried twice, as are the in-paralogs of the hit-gene.
		PreparedStatement stmt	= plaza_db_connection.prepareStatement("SELECT * FROM `orthologs` WHERE `gene_id` = ? AND `type`!='anchor_point' ");
			
		Map<String,GeneFamilyAssignment> cache	= new HashMap<String,GeneFamilyAssignment>();
		
		int counter					= 1;
		
		for(String transcript_id : data.keySet()){
			String hit_gene	= data.get(transcript_id).get(0)[0];
			
			if(!cache.containsKey(hit_gene)){				
								
				//basic set containing the content of the ortho-group
				Set<String> valid_ortho_group_content	= new HashSet<String>();
				List<String> new_in_paralogs			= new ArrayList<String>();
				List<String> explored_in_paralogs		= new ArrayList<String>();
				new_in_paralogs.add(hit_gene);
				valid_ortho_group_content.add(hit_gene);
				
					
				//now, we just keep on adding data while there are new in-paralogs. 
				while(new_in_paralogs.size()>0){
					Map<String,Set<String>> ortho_info	= new HashMap<String,Set<String>>();
					Set<String> possible_in_paralogs	= new HashSet<String>();
					String query_gene					= new_in_paralogs.get(0);
					stmt.setString(1,query_gene);
					ResultSet set	= stmt.executeQuery();					
					while(set.next()){
						String hit_species		= set.getString("species");	
						String type				= set.getString("type");
						String[] global_content	= set.getString("gene_content").split(";");
						for(String species_content:global_content){
							String[] spec_content	= species_content.split(":");
							String spec				= spec_content[0];
							String[] genes			= spec_content[1].split(",");
							for(String g:genes){
								if(!ortho_info.containsKey(g)){ortho_info.put(g,new HashSet<String>());}
								ortho_info.get(g).add(type);
								if(spec.equals(hit_species) && !new_in_paralogs.contains(g) && !explored_in_paralogs.contains(g)){
									possible_in_paralogs.add(g);
								}
							}
						}				
					}					
					set.close();
					
					Set<String> acceptable_evidence_genes	= this.filterOrthologs(ortho_info);
					for(String pip:possible_in_paralogs){
						if(acceptable_evidence_genes.contains(pip)){
							new_in_paralogs.add(pip);
						}
					}					
					valid_ortho_group_content.addAll(acceptable_evidence_genes);
					new_in_paralogs.remove(query_gene);
					explored_in_paralogs.add(query_gene);
					ortho_info.clear();
					possible_in_paralogs.clear();
				}
				
				//now, the valid_ortho_group_content variable contains all the genes which should be present 
				//in the ortho group. Now we just have to create a GeneFamilyAssignment variable.
				//Each gene of the target_species will also be stored in the cache, in order to reduce
				//the computational required time..
				
				//create unique id
				String gf_id				= "iOrtho_"+counter++;				
				String gf_assign_score		= "1.0";
				GeneFamilyAssignment gas	= new GeneFamilyAssignment(gf_id,gf_assign_score);
				gas.gf_content.addAll(valid_ortho_group_content);
				gas.gf_size = gas.gf_content.size();
				result.put(transcript_id, gas);
				
				//put link to GeneFamilyAssignment into cache for all explored paralogs
				for(String eip:explored_in_paralogs){
					cache.put(eip,gas);
				}				
			}
			else{
				result.put(transcript_id,cache.get(hit_gene));
			}
		}			
		
		stmt.close();
		return result;
	}
	
	
	
	
	private Set<String> filterOrthologs(Map<String,Set<String>> data){
		Set<String> result	= new HashSet<String>();		
		for(String query_gene:data.keySet()){
			//if(query_gene.equals("MD09G020410")){System.out.println(data.get(query_gene).size());}
			if(data.get(query_gene).size()>=2){
				result.add(query_gene);
			}
			
		}
		return result;
	}
	
	
	
	
	
	/**
	 * Determine for each best similarity hit the species. 
	 * @param plaza_db_connection
	 * @param simsearch_data
	 * @return
	 * @throws Exception
	 */
	private Map<String,Integer> getSpeciesHitCount(Connection plaza_db_connection,Map<String,List<String[]>> simsearch_data)throws Exception{
		Map<String,Integer> result			= new HashMap<String,Integer>();
		//gather a default mapping of genes to species for all content in plaza database
		Map<String,String> gene2species		= new HashMap<String,String>();
		String query						= "SELECT `gene_id`,`species` FROM `annotation` ";
		Statement stmt						= plaza_db_connection.createStatement();
		ResultSet set						= stmt.executeQuery(query);
		while(set.next()){
			String gene_id					= set.getString(1);
			String species					= set.getString(2);
			gene2species.put(gene_id, species);			
		}
		set.close();
		stmt.close();
		//now gather data
		for(String transcript: simsearch_data.keySet()){
			if(simsearch_data.get(transcript)!=null && simsearch_data.get(transcript).size()!=0){
				String[] d		= simsearch_data.get(transcript).get(0);
				String hit_gene	= d[0];
				if(gene2species.containsKey(hit_gene)){
					String species	= gene2species.get(hit_gene);
					if(!result.containsKey(species)){result.put(species, 0);}
					result.put(species,(result.get(species)+1));
				}
			}
		}				
		gene2species.clear();
		return result;
	}
	
	
	
	/**
	 * Parse the m8 output file of the similarity search.
	 * Result contains a mapping from the transcriptid to the top X hits, with associated information, stored as 
	 * array. 
	 * array[0]	= gene_id (from PLAZA)
	 * array[1]	= bitscore	
	 * array[2]	= query_start_position
	 * 
	 * @param file_name
	 * @param num_top_hits
	 * @return
	 * @throws Exception
	 */
	private Map<String,List<String[]>> parseSimilarityOutputFile(String file_name,int num_top_hits) throws Exception{
		Map<String,List<String[]>> result	= new HashMap<String,List<String[]>>();
		BufferedReader reader				= new BufferedReader(new FileReader(new File(file_name)));
		String s							= reader.readLine();
		while(s!=null){
			if(!s.startsWith("#")){
				String[] split			= s.split("\t");
				if(split.length==12){
					String transcript_id	= split[0].trim();
					String hit_gene			= split[1].trim();
					String perc_identity	= split[2].trim();
					String aln_length		= split[3].trim();
					String query_start		= split[6].trim(); 
					String query_stop		= split[7].trim();
					String log_e_val		= split[10].trim();
					String bitscore			= split[11].trim();
					if(!result.containsKey(transcript_id)){result.put(transcript_id, new ArrayList<String[]>());}
					if(result.get(transcript_id).size()<num_top_hits){
						String[] tmp		= {hit_gene,bitscore,query_start,query_stop,perc_identity,aln_length,log_e_val};
						result.get(transcript_id).add(tmp);
					}
				}				
			}
			s							= reader.readLine();	
		}
		reader.close();
		return result;
	}
	
	
	/**
	 * Reverse mapping from transcripts to gene families, to gene families to transcripts
	 * @param data 
	 * @return
	 */
	public Map<String,List<String>> reverseMapping(Map<String,GeneFamilyAssignment> data){
		Map<String,List<String>> result	= new HashMap<String,List<String>>();
		for(String k:data.keySet()){
			String v	= data.get(k).gf_id;
			if(!result.containsKey(v)){result.put(v,new ArrayList<String>());}
			result.get(v).add(k);
		}		
		return result;
	}
	

	/**
	 * Determining the Gene family sizes based on the mapping of genes to gene families.
	 * Necessary for debugging and statistics
	 * @param gene2gf Data from genes to gene families
	 * @return Mapping of gene families to gene family sizes	
	 */
	private Map<String,Integer> determinePLAZAGfSizes(Map<String,String> gene2gf){
		Map<String,Integer> result	= new HashMap<String,Integer>();
		for(String gene_id:gene2gf.keySet()){
			String gf_id			= gene2gf.get(gene_id);
			if(!result.containsKey(gf_id)){result.put(gf_id,0);}
			result.put(gf_id, result.get(gf_id)+1);
		}		
		return result;
	}

	/**
	 * Preload gene - genefamily associations into memory
	 * @param conn Database connection to PLAZA database
	 * @param gf_type Type of gene family
	 * @return Mapping from genes tot gene families
	 * @throws Exception Database error
	 */
	private Map<String,String> loadGeneFamilies(Connection conn,String gf_type) throws Exception{
		Map<String,String> result	= new HashMap<String,String>();
		String query				= "SELECT `gene_id`,`gf_id` FROM `gf_data` ";
		if(!gf_type.trim().equals("")){query+=" WHERE `gf_id` LIKE '"+gf_type+"%' ";}
		Statement stmt				= conn.createStatement();
		ResultSet set				= stmt.executeQuery(query);
		while(set.next()){
			String gene_id			= set.getString(1);
			String gf_id			= set.getString(2);
			result.put(gene_id,gf_id);
		}
		set.close();
		stmt.close();
		return result;
	}
	

	private Map<String,Set<String>> loadGoData(Connection conn) throws Exception{
		Map<String,Set<String>> gene_go					= new HashMap<String,Set<String>>();	
		String query_go_annot							= "SELECT `gene_id`,`go` FROM `gene_go` ";		
		Statement stmt_go_annot							= conn.createStatement();		
		ResultSet set_go_annot							= stmt_go_annot.executeQuery(query_go_annot);
		while(set_go_annot.next()){
			String gene_id	= set_go_annot.getString(1);
			String go		= set_go_annot.getString(2);
			if(!gene_go.containsKey(gene_id)){gene_go.put(gene_id, new HashSet<String>());}
			gene_go.get(gene_id).add(go);
		}
		set_go_annot.close();
		stmt_go_annot.close();
		return gene_go;
	}
	
	private Map<String,Set<String>> loadInterproData(Connection conn) throws Exception{
		Map<String,Set<String>> gene_interpro			= new HashMap<String,Set<String>>();
		String query_interpro_annot						= "SELECT `gene_id`,`motif_id` FROM `protein_motifs_data` ";
		Statement stmt_interpro_annot					= conn.createStatement();
		ResultSet set_intepro_annot						= stmt_interpro_annot.executeQuery(query_interpro_annot);
		while(set_intepro_annot.next()){
			String gene_id		= set_intepro_annot.getString(1);
			String motif_id		= set_intepro_annot.getString(2);
			if(!gene_interpro.containsKey(gene_id)){gene_interpro.put(gene_id,new HashSet<String>());}
			gene_interpro.get(gene_id).add(motif_id);
		}		
		return gene_interpro;
	}
	
	
	private Map<String,Set<String>> getFamilyContent(Connection plaza_conn,String gf_type) throws Exception{
		Map<String,Set<String>> result		= new HashMap<String,Set<String>>();
		String query						= "SELECT `gf_id`,`gene_id` FROM `gf_data` WHERE `gf_id` LIKE '"+gf_type+"%'";		
		Statement stmt						= plaza_conn.createStatement();
		ResultSet set						= stmt.executeQuery(query);
		while(set.next()){
			String gf_id					= set.getString(1);
			String gene_id					= set.getString(2);
			if(!result.containsKey(gf_id)){result.put(gf_id, new HashSet<String>());}
			result.get(gf_id).add(gene_id);
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	private Map<String,Integer> getGeneCdsLengths(Connection plaza_conn) throws Exception{
		Map<String,Integer> result	= new HashMap<String,Integer>();
		//String query				= "SELECT `gene_id`,LENGTH(`seq`) as length FROM `annotation` WHERE `type`='coding' ";
		//String query				= "SELECT `gene_id`,LENGTH(`seq`) as length FROM `annotation` ";
		String query				= "SELECT `gene_id`,CHAR_LENGTH(`seq`) FROM `annotation` WHERE `type`='coding' ";
		Statement stmt				= plaza_conn.createStatement();
		ResultSet set				= stmt.executeQuery(query);
		while(set.next()){
			String gene_id			= set.getString(1);
			int length				= set.getInt(2);
			result.put(gene_id, length);
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	private Map<String,boolean[]>  getTranscriptStartStop(Connection trapid_conn,String trapid_exp_id)throws Exception{
		Map<String,boolean[]> result		= new HashMap<String,boolean[]>();
		String query						= "SELECT `transcript_id`,`orf_contains_start_codon`,`orf_contains_stop_codon` FROM `transcripts` WHERE `experiment_id`='"+trapid_exp_id+"' ";
		Statement stmt						= trapid_conn.createStatement();
		ResultSet set						= stmt.executeQuery(query);
		while(set.next()){
			String transcript_id			= set.getString(1);
			boolean has_start				= set.getBoolean(2);
			boolean has_stop				= set.getBoolean(3);
			boolean[] k						= {has_start,has_stop};
			result.put(transcript_id, k);
		}
		set.close();
		stmt.close();
		return result;
	}
	
	private Map<String,Integer> getTranscriptOrfLengths(Connection trapid_conn,String trapid_exp_id)throws Exception{
		Map<String,Integer> result	= new HashMap<String,Integer>();
		String query				= "SELECT `transcript_id`,CHAR_LENGTH(`orf_sequence`) as length FROM `transcripts` WHERE `experiment_id`='"+trapid_exp_id+"' ";
		Statement stmt				= trapid_conn.createStatement();
		ResultSet set				= stmt.executeQuery(query);
		while(set.next()){
			String transcript_id	= set.getString(1);
			int length				= set.getInt(2);
			result.put(transcript_id, length);
		}
		set.close();
		stmt.close();		
		return result;
	}
	
	
	
	/**
	 * Preload GO graph data (typically 65MB in database table with indices, so memory shouldn't be a problem).
	 * This is done in order to speed up processing dramatically, by preventing a large number of SQL queries.
	 * @param conn Database connection to PLAZA database
	 * @return Mapping of GO terms to their parent GO terms and mapping of GO terms to their children GO terms
	 * @throws Exception Database failure
	 */
	public Map<String,Map<String,Set<String>>> loadGOGraph(Connection conn) throws Exception{
		
		Map<String,Map<String,Set<String>>> result	= new HashMap<String,Map<String,Set<String>>>();
		
		Map<String,Set<String>> go_child2parents	= new HashMap<String,Set<String>>(); 	//mapping from child go to parent go
		Map<String,Set<String>> go_parent2children	= new HashMap<String,Set<String>>();	//mapping of 
		
		
		String query					= "SELECT `child_go`,`parent_go` FROM `go_parents` ";
		Statement stmt					= conn.createStatement();
		ResultSet set					= stmt.executeQuery(query);
		while(set.next()){
			String child_go				= set.getString(1);
			String parent_go			= set.getString(2);
			if(!go_child2parents.containsKey(child_go)){go_child2parents.put(child_go, new HashSet<String>());}
			go_child2parents.get(child_go).add(parent_go);			
			if(!go_parent2children.containsKey(parent_go)){go_parent2children.put(parent_go, new HashSet<String>());}
			go_parent2children.get(parent_go).add(child_go);
		}		
		set.close();
		stmt.close();
		
		result.put("child2parents", go_child2parents);
		result.put("parent2children",go_parent2children);
		
		return result;
	}
	
	
	
	/**
	 * Create a database connection, based on given parameters
	 * @param server
	 * @param database
	 * @param login
	 * @param password
	 * @return
	 * @throws Exception
	 */
	private Connection createDbConnection(String server,String database,String login,String password) throws Exception{
		String url		= "jdbc:mysql://"+server+"/"+database;
		Connection conn	= DriverManager.getConnection(url,login,password);
		return conn;
	}
	
	
	public class ORFFinder{
			
		private Hashtable<String,Character> codonLookUp;
		public ORFFinder() {
			//initiate lookup table
			codonLookUp = new Hashtable<String,Character>();
			
			//Four fold degenerate codons , note these have to be checked correctly !!
			codonLookUp.put("TC", 'S'); // Serine
			codonLookUp.put("CT", 'L'); // Leucine
			codonLookUp.put("CC", 'P'); // Proline
			codonLookUp.put("CG", 'R'); // Arginine
			codonLookUp.put("AC", 'T'); // Threonine
			codonLookUp.put("GT", 'V'); // Valine
			codonLookUp.put("GC", 'A'); // Alanine
			codonLookUp.put("GG", 'G'); // Glycine
			//Stop codons
			codonLookUp.put("TAA", '*'); // Stop
			codonLookUp.put("TAG", '*'); // Stop
			codonLookUp.put("TAR", '*'); // Stop
			codonLookUp.put("TGA", '*'); // Stop
			//Phenylalanine
			codonLookUp.put("TTT", 'F'); // Phenylalanine
			codonLookUp.put("TTC", 'F'); // Phenylalanine
			codonLookUp.put("TTY", 'F'); // Phenylalanine
			//Leucine
			codonLookUp.put("TTA", 'L'); // Leucine
			codonLookUp.put("TTG", 'L'); // Leucine
			codonLookUp.put("TTR", 'L'); // Leucine
			//Tyrosine
			codonLookUp.put("TAT", 'Y'); // Tyrosine
			codonLookUp.put("TAC", 'Y'); // Tyrosine
			codonLookUp.put("TAY", 'Y'); // Tyrosine
			//Cysteine
			codonLookUp.put("TGT", 'C'); // Cysteine
			codonLookUp.put("TGC", 'C'); // Cysteine
			codonLookUp.put("TGY", 'C'); // Cysteine
			//Tryptophan
			codonLookUp.put("TGG", 'W'); // Tryptophan
			//Histidine
			codonLookUp.put("CAT", 'H'); // Histidine
			codonLookUp.put("CAC", 'H'); // Histidine
			codonLookUp.put("CAY", 'H'); // Histidine
			//Glutamine
			codonLookUp.put("CAA", 'Q'); // Glutamine
			codonLookUp.put("CAG", 'Q'); // Glutamine
			codonLookUp.put("CAR", 'Q'); // Glutamine
			//Isoleucine
			codonLookUp.put("ATT", 'I'); // Isoleucine
			codonLookUp.put("ATC", 'I'); // Isoleucine
			codonLookUp.put("ATA", 'I'); // Isoleucine
			codonLookUp.put("ATH", 'I'); // Isoleucine
			codonLookUp.put("ATY", 'I'); // Isoleucine
			codonLookUp.put("ATW", 'I'); // Isoleucine
			codonLookUp.put("ATM", 'I'); // Isoleucine
			//Methionine
			codonLookUp.put("ATG", 'M'); // Methionine
			//Asparagine
			codonLookUp.put("AAT", 'N'); // Asparagine
			codonLookUp.put("AAC", 'N'); // Asparagine
			codonLookUp.put("AAY", 'N'); // Asparagine
			//Lysine
			codonLookUp.put("AAA", 'K'); // Lysine
			codonLookUp.put("AAG", 'K'); // Lysine
			codonLookUp.put("AAR", 'K'); // Lysine
			//Serine
			codonLookUp.put("AGT", 'S'); // Serine
			codonLookUp.put("AGC", 'S'); // Serine
			codonLookUp.put("AGY", 'S'); // Serine
			//Arginine
			codonLookUp.put("AGA", 'R'); // Arginine
			codonLookUp.put("AGG", 'R'); // Arginine
			codonLookUp.put("AGR", 'R'); // Arginine
			//Aspartic Acid
			codonLookUp.put("GAT", 'D'); // Aspartic Acid
			codonLookUp.put("GAC", 'D'); // Aspartic Acid
			codonLookUp.put("GAY", 'D'); // Aspartic Acid
			//Glutamic Acid
			codonLookUp.put("GAA", 'E'); // Glutamic Acid
			codonLookUp.put("GAG", 'E'); // Glutamic Acid
			codonLookUp.put("GAR", 'E'); // Glutamic Acid
		}
		
		private String reverseComplement(String input){
			StringBuffer buffer = new StringBuffer(input.toUpperCase()).reverse();			
			char[] new_sequence	= new char[buffer.length()];
			for (int i = 0; i < buffer.length(); i++){
				char temp = buffer.charAt(i);
				switch(temp){
				case 'G': new_sequence[i]='C';break;
				case 'C': new_sequence[i]='G';break;
				case 'A': new_sequence[i]='T';break;
				case 'T': new_sequence[i]='A';break;
				case 'M': new_sequence[i]='K';break;
				case 'K': new_sequence[i]='M';break;
				case 'R': new_sequence[i]='Y';break;
				case 'Y': new_sequence[i]='R';break;
				case 'H': new_sequence[i]='D';break;
				case 'D': new_sequence[i]='H';break;
				case 'B': new_sequence[i]='V';break;
				case 'V': new_sequence[i]='B';break;
				default: new_sequence[i]='N'; break;
				}	
			}
			return new String(new_sequence);
		}
		
		
		private char translateCodon(String Codon){
			if (Codon.length() == 3){
				if (codonLookUp.containsKey(Codon)){
					return codonLookUp.get(Codon);
				} 
				else{
					//check if it's a fourfold degenerate
					String sub = Codon.substring(0,2);
					if (codonLookUp.containsKey(sub)){return codonLookUp.get(sub);}
					else{return 'X';} //unknown or undefined codon
				}
			} 
			else {return 'X';} //wrong length
		}
		
		
		
		//frame can be 0,1,2,3 (0 if no BLAST hits found).
		public Map<String,String> findLongestORF(String sequence,char strand, int frame){			
			//create sequence to work with, reverse complement if necessary						
			if(frame==0){
				String forward_sequence	= sequence;
				String reverse_sequence	= this.reverseComplement(sequence);				
				//iterate over the 3 frames and the 2 strands, and compute all the results. Then compare them.
				Map<String,Map<String,String>> cache	= new HashMap<String,Map<String,String>>();
				for(int i=1;i<=3;i++){	//forward_strand
					cache.put("+"+i,this.findLongestORF(forward_sequence, i));
				}
				for(int i=1;i<=3;i++){ //reverse strand
					cache.put("-"+i,this.findLongestORF(reverse_sequence, i));
				}
				//find best result
				String best_result			= null;
				int longest_orf_length		= -1;
				for(String possible_strand_frame:cache.keySet()){					
					if(cache.get(possible_strand_frame).get("ORF").length()>longest_orf_length){
						best_result			= possible_strand_frame;
						longest_orf_length	= cache.get(possible_strand_frame).get("ORF").length();
					}
				}
				//add extra data, which should be added to the database
				cache.get(best_result).put("newStrand",best_result.substring(0,1));
				cache.get(best_result).put("newFrame",best_result.substring(1,2));
				return cache.get(best_result);
			}
			else{
				String finalSequence = "";
				if (strand == '-'){finalSequence = this.reverseComplement(sequence);} 
				else {finalSequence = sequence;}
				return this.findLongestORF(finalSequence, frame);
			}
		}
		
		
		
		//frame can be 1,2,3 (not 0 -> refer to above method).
		private Map<String,String> findLongestORF(String sequence,int frame){
			Map<String,String> result		= new HashMap<String,String>();
			//final result
			String longestAA				= "";
			String longestORF				= "";
			int longestStart				= 0;
			int longestStop					= 0;
			boolean longestHasStartCodon	= false;
			boolean longestHasStopCodon 	= false;
			//create current sequence (placeholder for sequence found)
			StringBuffer currentAA 			= new StringBuffer();
			StringBuffer currentORF 		= new StringBuffer();
			int currentStart 				= 0;
			int currentStop 				= 0;
			boolean currentHasStartCodon 	= false;
			boolean currentHasStopCodon 	= false;
			
			//go over sequence in selected frame and store everything in the current sequence, 
			//at the end or at a stop evaluate and store as longest			
			boolean reading = true; //only read start or after encountering a valid start codon			
			for (int i = frame-1; i< sequence.length() - 2; i+=3){
				String codon = sequence.substring(i, i+3);
				char AminoAcid = this.translateCodon(codon);				
				if (AminoAcid == 'M' && reading == false){
					reading = true;
					currentStart = i;
					currentHasStartCodon = true;
				}
				if (reading){
					currentAA.append(AminoAcid);
					currentORF.append(codon);
				}				
				if (AminoAcid == '*' && reading == true){
					reading = false;
					currentHasStopCodon = true;
					currentStop = i+2;					
					if (currentAA.length() > longestAA.length()){
						longestAA 				= currentAA.toString();
						longestORF 				= currentORF.toString();
						longestStart 			= currentStart;
						longestStop 			= currentStop;
						longestHasStartCodon 	= currentHasStartCodon;
						longestHasStopCodon 	= currentHasStopCodon;
					}					
					currentAA 				= new StringBuffer();
					currentORF 				= new StringBuffer();;
					currentStart 			= 0;
					currentStop 			= 0;
					currentHasStartCodon 	= false;
					currentHasStopCodon 	= false;
				}
			}
			//final one, 
			if (currentAA.length() > longestAA.length()){
				longestAA 					= currentAA.toString();
				longestORF 					= currentORF.toString();
				longestStart 				= currentStart;
				longestStop 				= currentStart + longestORF.length();
				longestHasStartCodon 		= currentHasStartCodon;
				longestHasStopCodon 		= currentHasStopCodon;
			}			
			if(longestORF.startsWith("ATG")){longestHasStartCodon=true;}			
			result.put("AA",longestAA);
			result.put("ORF",longestORF);
			result.put("start",""+longestStart);
			result.put("stop",""+longestStop);
			result.put("hasStartCodon",""+longestHasStartCodon);
			result.put("hasStopCodon",""+longestHasStopCodon);
			return result;
		}		
	}

}
