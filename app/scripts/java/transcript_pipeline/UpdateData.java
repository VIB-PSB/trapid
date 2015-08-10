package transcript_pipeline;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;


/**
 * This program is designed to quickly update a small portion of the trapid database.
 * 
 * Initially, only a transcript-genefamily assocation change is implemented.
 * 
 * @author mibel
 *
 */
public class UpdateData {
	
	public static final int MIN_GF_SIZE_META		= 5;
	
	public static void main(String[] args){		
		UpdateData ud				= new UpdateData();
		try{
			String change_type			= args[0];
			if(change_type.equals("GF_ASSOC_NEW")){
				ud.changeGf1(args);
			}
			else if(change_type.equals("GF_ASSOC_EXIST")){
				ud.changeGf2(args);
			}
			else{
				throw new Exception("Unknown data change");
			}
		}
		catch(Exception exc){
			exc.printStackTrace();
		}
	}
	
	/**
	 * Change the transcript-genefamily association.
	 * This method is used when the new gene family is not yet present as a trapid gene family
	 * Therefore also the PLAZA database connection is necessary to gather information for the 
	 * meta-annotation and the functional annotation.
	 * @param args //first arg is unnec
	 * @throws Exception
	 */
	private void changeGf1(String[] args) throws Exception{
		//database variables, necessary for retrieving homology/orthology information from the
		//similarity hits
		String plaza_database_server		= args[1];	//normally psbsql03.psb.ugent.be
		String plaza_database_name			= args[2];
		String plaza_database_login			= args[3];
		String plaza_database_password		= args[4];
		
		//workbench variables, necessary for storing homology/orthology information
		String trapid_server				= args[5];
		String trapid_name					= args[6];
		String trapid_login					= args[7];
		String trapid_password				= args[8];
		
		String trapid_experiment			= args[9];
		String transcript_id				= args[10];	
		String gf_id						= args[11]; //already present in database through PHP
		
		//create necessary database connections.		
		Class.forName("com.mysql.jdbc.Driver");	
		Connection plaza_db_connection		= this.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
		Connection trapid_db_connection		= this.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
		
		//remove the previous GO and InterPro annotations associated with the transcript
		this.deleteTranscriptGo(trapid_db_connection,trapid_experiment,transcript_id);
		this.deleteTranscriptInterpro(trapid_db_connection,trapid_experiment,transcript_id);
		
		
		//ok, complicated stuff (copied partially from initialprocessing code)
		String plaza_gf_id					= this.getPlazaGfId(trapid_db_connection,trapid_experiment,gf_id);
		if(plaza_gf_id==null){throw new Exception("no plaza gf id for "+gf_id);}
		Set<String> gene_ids				= this.getGfGenes(plaza_db_connection,plaza_gf_id);
		
		Map<String,String> go_terms			= this.retrieveGoFromPlazaGf(plaza_db_connection,gene_ids);
		Set<String> interpro_terms			= this.retrieveInterproFromPlazaGf(plaza_db_connection,gene_ids);
		int[] meta_annot_data				= this.computeMetaAnnotationData(plaza_db_connection,plaza_gf_id,gene_ids);
		//meta_annot_data can be null. In this case, the number of genes in the gf was too small.
		//annotate the transcript with "No Information" in this case.
				
		String orf_sequence					= this.retrieveOrfSequence(trapid_db_connection,trapid_experiment,transcript_id);
		if(orf_sequence==null){throw new Exception("ORF sequence from "+transcript_id+" is null");}
		String[] meta_annotation			= this.createMetaAnnotation(orf_sequence.length(),meta_annot_data);
		
		this.storeGoInformation(trapid_db_connection,trapid_experiment,transcript_id,go_terms);
		this.storeInterproInformation(trapid_db_connection,trapid_experiment,transcript_id,interpro_terms);
		this.storeMetaInformation(trapid_db_connection,trapid_experiment,transcript_id,meta_annotation);
		
		
		plaza_db_connection.close();
		trapid_db_connection.close();
	}
	
	
	/**
	 * Change the transcript-genefamily association
	 * This method is only used when the new gene family is already present as a trapid gene family.
	 * As such, functional annotation, and the basis for the meta-annotation can be copied from 
	 * the trapid database as is.
	 * @param args
	 * @throws Exception
	 */
	private void changeGf2(String[] args)throws Exception{
		//workbench variables, necessary for storing homology/orthology information
		String trapid_server				= args[1];
		String trapid_name					= args[2];
		String trapid_login					= args[3];
		String trapid_password				= args[4];
		
		String trapid_experiment			= args[5];
		String transcript_id				= args[6];	
		String gf_id						= args[7]; //already present in database through previous processing
		
		//create necessary database connections.
		Class.forName("com.mysql.jdbc.Driver");	
		Connection trapid_db_connection		= this.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
				
		//remove the previous GO and InterPro annotations associated with the transcript
		this.deleteTranscriptGo(trapid_db_connection,trapid_experiment,transcript_id);
		this.deleteTranscriptInterpro(trapid_db_connection,trapid_experiment,transcript_id);
		
		
		//ok, simple transfer the functional annotation from one of the existing transcripts
		String random_gf_transcript			= this.findRandomGfTranscript(trapid_db_connection,trapid_experiment,transcript_id,gf_id);
		if(random_gf_transcript==null){throw new Exception("Random transcript from gf is null");}
			
		//transfer the functional annotation
		Map<String,String> go_terms			= this.retrieveGoFromTranscript(trapid_db_connection,trapid_experiment,random_gf_transcript);
		Set<String> interpro_terms			= this.retrieveInterproFromTranscript(trapid_db_connection,trapid_experiment,random_gf_transcript);
		int[] meta_annot_data				= this.retrieveMetaAnnotData(trapid_db_connection,trapid_experiment,random_gf_transcript);
		//meta_annot_data can be null. In this case, the number of genes in the gf was too small.
		//annotate the transcript with "No Information" in this case.		
		
		String orf_sequence					= this.retrieveOrfSequence(trapid_db_connection,trapid_experiment,transcript_id);
		if(orf_sequence==null){throw new Exception("ORF sequence from "+transcript_id+" is null");}
		String[] meta_annotation			= this.createMetaAnnotation(orf_sequence.length(),meta_annot_data);
		
		this.storeGoInformation(trapid_db_connection,trapid_experiment,transcript_id,go_terms);
		this.storeInterproInformation(trapid_db_connection,trapid_experiment,transcript_id,interpro_terms);
		this.storeMetaInformation(trapid_db_connection,trapid_experiment,transcript_id,meta_annotation);
		
		trapid_db_connection.close();
	}
	
	
	private void deleteTranscriptGo(Connection trapid_connection,String trapid_experiment,String transcript_id) throws Exception{
		String sql			= "DELETE FROM `transcripts_go` WHERE `experiment_id`='"+trapid_experiment+"' AND `transcript_id`='"+transcript_id+"' ";
		Statement stmt		= trapid_connection.createStatement();
		stmt.execute(sql);
		stmt.close();
	}
	private void deleteTranscriptInterpro(Connection trapid_connection,String trapid_experiment,String transcript_id) throws Exception{
		String sql	= "DELETE FROM `transcripts_interpro` WHERE `experiment_id`='"+trapid_experiment+"' AND `transcript_id`='"+transcript_id+"' ";
		Statement stmt		= trapid_connection.createStatement();
		stmt.execute(sql);
		stmt.close();
	}
	
	
	private Set<String> getGfGenes(Connection plaza_connection,String plaza_gf_id) throws Exception{
		Set<String> result			= new HashSet<String>();
		String sql					= "SELECT `gene_id` FROM `gf_data` WHERE `gf_id`='"+plaza_gf_id+"' ";
		Statement stmt				= plaza_connection.createStatement();
		ResultSet set				= stmt.executeQuery(sql);
		while(set.next()){
			String gene_id			= set.getString(1);
			result.add(gene_id);
		}
		set.close();
		stmt.close();		
		return result;
	}
	
	private String makeInString(Set<String> data){
		String result			= "()";
		StringBuffer buff		= new StringBuffer();
		for(String d:data){
			buff.append(",'"+d+"'");
		}
		if(buff.length()!=0){
			result	= "("+buff.substring(1)+")";			
		}
		return result;
	}
	
	private Map<String,String>retrieveGoFromPlazaGf(Connection plaza_connection,Set<String> genes) throws Exception{
		Map<String,String> result							= new HashMap<String,String>();		
		InitialTranscriptsProcessing itp					= new InitialTranscriptsProcessing();
		Map<String,Map<String,Set<String>>> go_graph_data	= itp.loadGOGraph(plaza_connection);
		Map<String,Set<String>> go_child2parents			= go_graph_data.get("child2parents");
		Map<String,Set<String>> go_parent2children			= go_graph_data.get("parent2children");
		String inc_string									= this.makeInString(genes);
		Map<String,Set<String>> gene_go						= new HashMap<String,Set<String>>();
		String sql											= "SELECT `gene_id`,`go` FROM `gene_go` WHERE `gene_id` IN "+inc_string;
		Statement stmt										= plaza_connection.createStatement();
		ResultSet set										= stmt.executeQuery(sql);
		while(set.next()){
			String gene_id				= set.getString(1);
			String go					= set.getString(2);
			if(!gene_go.containsKey(gene_id)){gene_go.put(gene_id, new HashSet<String>());}
			gene_go.get(gene_id).add(go);
		}
		set.close();
		stmt.close();
		
		Map<String,Set<String>> go_genes	= new HashMap<String,Set<String>>();
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
		double gene_gf_count		= genes.size();
		for(String go_id:go_genes.keySet()){
			double gene_go_count	= go_genes.get(go_id).size();
			if(gene_go_count/gene_gf_count >= 0.5){
				selected_gos.add(go_id);				
			}
		}			
		//now, using the go_hidden data, try to use the go-graph to see which GO's should be flagged as 
		//"hidden" in the database.
		Map<String,Integer>go_hidden	= itp.hide_go_terms(selected_gos,go_parent2children);
		for(String go:go_hidden.keySet()){
			result.put(go,""+go_hidden.get(go));
		}		
		return result;
	}
	
	
	
	
	
	private Set<String>retrieveInterproFromPlazaGf(Connection plaza_connection,Set<String> genes) throws Exception{
		Set<String> result									= new HashSet<String>();
		String inc_string									= this.makeInString(genes);
		Map<String,Set<String>> gene_interpro				= new HashMap<String,Set<String>>();
		String sql											= "SELECT `gene_id`,`motif_id` FROM `protein_motifs_data` WHERE `gene_id` IN "+inc_string;
		Statement stmt										= plaza_connection.createStatement();
		ResultSet set										= stmt.executeQuery(sql);
		while(set.next()){
			String gene_id					= set.getString(1);
			String interpro					= set.getString(2);
			if(!gene_interpro.containsKey(gene_id)){gene_interpro.put(gene_id, new HashSet<String>());}
			gene_interpro.get(gene_id).add(interpro);
		}
		set.close();
		stmt.close();	
		
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
		double gene_gf_count			= genes.size();
		for(String ipr_id:interpro_genes.keySet()){
			double gene_ipr_count	= interpro_genes.get(ipr_id).size();
			if(gene_ipr_count/gene_gf_count >= 0.5){
				result.add(ipr_id);					
			}
		}
		return result;
	}
	
	
	/*
	 * Get the meta-annotation background info for a given gene family (Represented here by its set of genes)
	 */
	private int[] computeMetaAnnotationData(Connection plaza_connection,String gf_id,Set<String> genes) throws Exception{
		int[] result						= null;
		if(genes.size()<MIN_GF_SIZE_META){return result;}
		List<Integer> cds_lengths			= new ArrayList<Integer>();
		String inc_string					= this.makeInString(genes);
		Statement stmt						= plaza_connection.createStatement();
		String sql							= "SELECT `start`,`stop` FROM `annotation` WHERE `gene_id` IN "+inc_string;
		ResultSet set						= stmt.executeQuery(sql);
		while(set.next()){
			int start						= set.getInt(1);
			int stop						= set.getInt(2);
			int length						= Math.abs(stop-start);
			cds_lengths.add(length);
		}
		set.close();
		stmt.close();
		
		InitialTranscriptsProcessing itp						= new InitialTranscriptsProcessing();
		InitialTranscriptsProcessing.LengthAnalysis	la			= itp.getLengthAnalysis(gf_id,cds_lengths,true);
		
		result	= new int[3];
		result[0]		= la.std_deviation;
		result[1]		= la.average;
		result[2]		= (la.average-2*la.std_deviation);		
		return result;
	}
	
	
	private String getPlazaGfId(Connection trapid_connection,String exp_id,String trapid_gf_id) throws Exception{
		String result			= null;
		String sql				= "SELECT `plaza_gf_id` FROM `gene_families` WHERE `experiment_id`='"+exp_id+"' AND `gf_id`='"+trapid_gf_id+"' ";
		Statement stmt			= trapid_connection.createStatement();
		ResultSet set			= stmt.executeQuery(sql);
		if(set.next()){
			result				= set.getString(1);
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	
	
	private void storeGoInformation(Connection trapid_connection,String exp_id,String transcript_id,Map<String,String> go_terms) throws Exception{
		String sql					= "INSERT INTO `transcripts_go`(`experiment_id`,`transcript_id`,`go`,`is_hidden`) VALUES ('"+exp_id+"','"+transcript_id+"',?,?)";
		PreparedStatement stmt		= trapid_connection.prepareStatement(sql);
		for(String go:go_terms.keySet()){
			String is_hidden	= go_terms.get(go);
			stmt.setString(1,go);
			stmt.setString(2, is_hidden);
			stmt.execute();
		}
		stmt.close();		
	}
	
	private void storeInterproInformation(Connection trapid_connection,String exp_id,String transcript_id,Set<String>interpro_terms) throws Exception{
		String sql				= "INSERT INTO `transcripts_interpro`(`experiment_id`,`transcript_id`,`interpro`) VALUES ('"+exp_id+"','"+transcript_id+"',?) ";
		PreparedStatement stmt		= trapid_connection.prepareStatement(sql);
		for(String ipr :interpro_terms){
			stmt.setString(1,ipr);
			stmt.execute();			
		}
		stmt.close();
	}
	
	
	private void storeMetaInformation(Connection trapid_connection,String exp_id,String transcript_id,String[] meta_annotation) throws Exception{
		String sql			= "UPDATE `transcripts` SET `meta_annotation`='"+meta_annotation[0]+"',`meta_annotation_score`='"+meta_annotation[1]+"' WHERE `experiment_id`='"+exp_id+"' AND `transcript_id`='"+transcript_id+"' ";
		Statement stmt		= trapid_connection.createStatement();
		stmt.execute(sql);
		stmt.close();
	}
	
	
	private String[] createMetaAnnotation(int orf_length,int[] meta_annot_data) throws Exception{
		String meta_annot			= "No Information";
		String meta_annot_score		= "";	
		if(meta_annot_data!=null){
			int cutoff		= meta_annot_data[2];
			if(orf_length>=cutoff){meta_annot="Full Length";}
			else{meta_annot="Partial";}	
			meta_annot_score	= "std_dev="+meta_annot_data[0]+";avg="+meta_annot_data[1]+";orf_length="+orf_length+";cutoff="+meta_annot_data[2];
		}
		String [] result	= {meta_annot,meta_annot_score};
		return result;
	}
	
	
	private String retrieveOrfSequence(Connection trapid_connection,String exp_id,String transcript_id)throws Exception{
		String result				= null;
		String sql					= "SELECT `orf_sequence` FROM `transcripts` WHERE `experiment_id`='"+exp_id+"' AND `transcript_id`='"+transcript_id+"' ";
		Statement stmt				= trapid_connection.createStatement();
		ResultSet set				= stmt.executeQuery(sql);
		if(set.next()){
			result					= set.getString(1);
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	private Map<String,String> retrieveGoFromTranscript(Connection trapid_connection,String exp_id,String transcript_id)throws Exception{
		Map<String,String> result	= new HashMap<String,String>();
		String sql					= "SELECT `go`,`is_hidden` FROM `transcripts_go` WHERE `experiment_id`='"+exp_id+"' AND `transcript_id`='"+transcript_id+"' ";
		Statement stmt				= trapid_connection.createStatement();
		ResultSet set				= stmt.executeQuery(sql);
		while(set.next()){
			String go				= set.getString(1);
			String is_hidden		= set.getString(2);
			result.put(go,is_hidden);
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	
	private Set<String> retrieveInterproFromTranscript(Connection trapid_connection,String exp_id,String transcript_id)throws Exception{
		Set<String> result	= new HashSet<String>();
		String sql					= "SELECT `interpro` FROM `transcripts_interpro` WHERE `experiment_id`='"+exp_id+"' AND `transcript_id`='"+transcript_id+"' ";
		Statement stmt				= trapid_connection.createStatement();
		ResultSet set				= stmt.executeQuery(sql);
		while(set.next()){
			String interpro			= set.getString(1);
			result.add(interpro);
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	private int[] retrieveMetaAnnotData (Connection trapid_connection,String exp_id,String transcript_id)throws Exception{
		int[] result				= null;
		String sql					= "SELECT `meta_annotation_score` FROM `transcripts` WHERE `experiment_id`='"+exp_id+"' AND `transcript_id`='"+transcript_id+"' ";
		Statement stmt				= trapid_connection.createStatement();
		ResultSet set				= stmt.executeQuery(sql);
		if(set.next()){		
			String mas				= set.getString(1);
			if(mas==null || mas.equals("")){				
			}
			else{
				String[] split			= mas.split(";");
				String std_dev			= split[0].split("=")[1];
				String avg				= split[1].split("=")[1];
				String cutoff			= split[3].split("=")[1]; //split[2] is actual length of orf, but not necessary here
				result					= new int[3];
				result[0]				= Integer.parseInt(std_dev);
				result[1]				= Integer.parseInt(avg);
				result[2]				= Integer.parseInt(cutoff);
			}
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	
	
	
	
	private String findRandomGfTranscript(Connection trapid_connection,String exp_id,String transcript_id,String gf_id) throws Exception{
		String result	= null;
		String sql		= "SELECT `transcript_id` FROM `transcripts` WHERE `gf_id`='"+gf_id+"' AND `experiment_id`='"+exp_id+"' AND `transcript_id`!='"+transcript_id+"' ";
		Statement stmt	= trapid_connection.createStatement();
		ResultSet set	= stmt.executeQuery(sql);
		if(set.next()){
			result		= set.getString(1);
		}
		set.close();
		stmt.close();
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
	
	
	
}
