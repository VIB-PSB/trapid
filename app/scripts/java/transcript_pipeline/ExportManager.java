package transcript_pipeline;

import java.io.BufferedWriter;
import java.io.File;
import java.io.FileWriter;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;
import java.util.SortedMap;
import java.util.TreeMap;

public class ExportManager {


	public enum EXPORT_TYPE
		{NONE,
		STRUCTURAL,
		SEQ_TRANSCRIPT,SEQ_ORF,SEQ_AA,
		TRANSCRIPT_GF,GF_TRANSCRIPT,GF_REFERENCE,
		TRANSCRIPT_GO,GO_TRANSCRIPT,
		TRANSCRIPT_INTERPRO,INTERPRO_TRANSCRIPT,
		TRANSCRIPT_LABEL
		};

	public static void main(String[] args){
		ExportManager em		= new ExportManager();

		try{

			//plaza db variables
			String plaza_server					= args[0];
			String plaza_db_name				= args[1];
			String plaza_db_login				= args[2];
			String plaza_db_password			= args[3];

			//trapid db variables
			String trapid_server				= args[4];
			String trapid_name					= args[5];
			String trapid_login					= args[6];
			String trapid_password				= args[7];

			//user experiment id
			String trapid_experiment_id			= args[8];

			//type of export
			String export_type_string			= args[9];

			//output file
			String output_file					= args[10];

			String filter						= null;
			if(args.length==12){filter			= args[11];}

			EXPORT_TYPE export_type				= EXPORT_TYPE.NONE;
			for(EXPORT_TYPE et:EXPORT_TYPE.values()){
				if(et.toString().equalsIgnoreCase(export_type_string)){export_type=et;}
			}
			if(export_type==EXPORT_TYPE.NONE){throw new Exception("Unknown export type :"+export_type_string);}

			Class.forName("com.mysql.jdbc.Driver");
			Connection trapid_db_connection		= em.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			Connection plaza_db_connection		= em.createDbConnection(plaza_server, plaza_db_name, plaza_db_login, plaza_db_password);

			switch(export_type){
			case STRUCTURAL: em.exportStructuralData(trapid_db_connection,trapid_experiment_id,output_file,filter);break;
			case SEQ_TRANSCRIPT: em.exportTranscriptSequences(trapid_db_connection,trapid_experiment_id,output_file); break;
			case SEQ_ORF: em.exportOrfSequences(trapid_db_connection,trapid_experiment_id,output_file); break;
			case SEQ_AA: em.exportAASequences(trapid_db_connection,trapid_experiment_id,output_file);break;
			case TRANSCRIPT_GF: em.exportTranscriptGf(trapid_db_connection,trapid_experiment_id,output_file);break;
			case GF_TRANSCRIPT: em.exportGfTranscript(trapid_db_connection,trapid_experiment_id,output_file);break;
			case GF_REFERENCE:em.exportGfReference(plaza_db_connection,trapid_db_connection,trapid_experiment_id,output_file);break;
			case TRANSCRIPT_GO: em.exportTranscriptGo(plaza_db_connection,trapid_db_connection,trapid_experiment_id,output_file);break;
			case GO_TRANSCRIPT: em.exportGoTranscript(plaza_db_connection,trapid_db_connection,trapid_experiment_id,output_file);break;
			case TRANSCRIPT_INTERPRO: em.exportTranscriptInterpro(plaza_db_connection,trapid_db_connection,trapid_experiment_id,output_file);break;
			case INTERPRO_TRANSCRIPT: em.exportInterproTranscript(plaza_db_connection,trapid_db_connection,trapid_experiment_id,output_file);break;
			case TRANSCRIPT_LABEL: em.exportTranscriptLabel(trapid_db_connection,trapid_experiment_id,output_file,filter); break;
			}

			trapid_db_connection.close();
			plaza_db_connection.close();
		}
		catch(Exception exc){
			exc.printStackTrace();
		}
	}


	public void exportTranscriptLabel(Connection conn,String exp_id,String output_file,String filter) throws Exception{
		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file)));
		String sql				= "SELECT `transcript_id` FROM `transcripts_labels` WHERE `experiment_id`='"+exp_id+"' AND `label`='"+filter+"' ";
		Statement stmt			= conn.createStatement();
		ResultSet set			= stmt.executeQuery(sql);
		while(set.next()){
			String transcript_id	= set.getString("transcript_id");
			writer.write(transcript_id+"\n");
		}
		stmt.close();
		writer.close();
	}

	public void exportStructuralData(Connection conn,String exp_id,String output_file,String filter) throws Exception{
		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file)));
		String[] columns		= filter.split(",");
		String query			= "SELECT `transcript_id`";
		for(String col:columns){if(!col.equals("transcript_id")){query=query+",`"+col+"`";}}
		query					= query+" FROM `transcripts` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt			= conn.createStatement();
		ResultSet set			= stmt.executeQuery(query);
		//write header
		writer.write("#transcript_id");
		for(String col:columns){if(!col.equals("transcript_id")){writer.write("\t"+col);}}
		writer.write("\n");

		while(set.next()){
			StringBuffer buff		= new StringBuffer();
			String transcript_id	= set.getString("transcript_id");
			buff.append(transcript_id);
			for(String col:columns){
				if(!col.equals("transcript_id")){
					String c		= set.getString(col);
					buff.append("\t");
					buff.append(c);
				}
			}
			buff.append("\n");
			writer.write(buff.toString());
		}
		stmt.close();
		writer.close();
	}


	public void exportTranscriptSequences(Connection conn,String exp_id,String output_file) throws Exception{
		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file)));
		String sql			= "SELECT `transcript_id`,`transcript_sequence` FROM `transcripts` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt		= conn.createStatement();
		ResultSet set		= stmt.executeQuery(sql);
		while(set.next()){
			String transcript_id		= set.getString("transcript_id");
			String transcript_sequence	= set.getString("transcript_sequence");
			writer.write(">"+transcript_id+"\n");
			writer.write(transcript_sequence+"\n");
		}
		stmt.close();
		writer.close();
	}


	public void exportOrfSequences(Connection conn,String exp_id,String output_file) throws Exception{
		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file)));
		String sql			= "SELECT `transcript_id`,`orf_sequence` FROM `transcripts` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt		= conn.createStatement();
		ResultSet set		= stmt.executeQuery(sql);
		while(set.next()){
			String transcript_id		= set.getString("transcript_id");
			String orf_sequence			= set.getString("orf_sequence");
			writer.write(">"+transcript_id+"\n");
			writer.write(orf_sequence+"\n");
		}
		stmt.close();
		writer.close();
	}

	public void exportAASequences(Connection conn,String exp_id,String output_file) throws Exception{
		Map<String,Character> map	= this.getTranslateMap();
		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file)));
		String sql			= "SELECT `transcript_id`,`orf_sequence` FROM `transcripts` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt		= conn.createStatement();
		ResultSet set		= stmt.executeQuery(sql);
		while(set.next()){
			String transcript_id		= set.getString("transcript_id");
			String orf_sequence			= set.getString("orf_sequence");
			String aa_sequence			= this.translateSequence(orf_sequence,map);
			writer.write(">"+transcript_id+"\n");
			writer.write(aa_sequence+"\n");
		}
		stmt.close();
		writer.close();
	}


	public void exportTranscriptGf(Connection conn,String exp_id,String output_file) throws Exception{
		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file)));
		String sql				= "SELECT `transcript_id`,`gf_id` FROM `transcripts` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt			= conn.createStatement();
		ResultSet set			= stmt.executeQuery(sql);
		int counter				= 0;
		writer.write("#counter\ttranscript_id\tgf_id\n");
		while(set.next()){
			counter++;
			String transcript_id		= set.getString("transcript_id");
			String gf_id				= set.getString("gf_id");
			if(gf_id==null || gf_id.equals("null")){gf_id="";}
			writer.write(counter+"\t"+transcript_id+"\t"+gf_id+"\n");
		}
		stmt.close();
		writer.close();
	}


	public void exportGfTranscript(Connection conn,String exp_id,String output_file) throws Exception{
		String sql				= "SELECT `transcript_id`,`gf_id` FROM `transcripts` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt			= conn.createStatement();
		ResultSet set			= stmt.executeQuery(sql);
		SortedMap<String,Set<String>> tmp	= new TreeMap<String,Set<String>>();
		while(set.next()){
			String transcript_id		= set.getString("transcript_id");
			String gf_id				= set.getString("gf_id");
			if(gf_id==null || gf_id.equals("null")){gf_id="";}
			if(!gf_id.equals("")){
				if(!tmp.containsKey(gf_id)){tmp.put(gf_id, new HashSet<String>());}
				tmp.get(gf_id).add(transcript_id);
			}
		}
		stmt.close();
		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file)));
		writer.write("#counter\tgf_id\ttranscript_count\ttranscripts\n");
		int counter			= 0;
		for(String gf_id:tmp.keySet()){
			counter++;
			Set<String> genes	= tmp.get(gf_id);
			String genes_string	= make_string(genes);
			writer.write(counter+"\t"+gf_id+"\t"+genes.size()+"\t"+genes_string+"\n");
		}
		writer.close();
	}



	protected void exportGfReferenceHom(Connection plaza_conn,Connection trapid_conn,String exp_id,String output_file) throws Exception{
		Map<String,Set<String>> gf_genes		= new HashMap<String,Set<String>>();
		Map<String,String> plaza_gf_trapid_gf	= new HashMap<String,String>();
		//select all the plaza gene family information
		String sql1			= "SELECT `gf_id`,`plaza_gf_id` FROM `gene_families` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt1		= trapid_conn.createStatement();
		ResultSet set1		= stmt1.executeQuery(sql1);
		while(set1.next()){
			String trap_gf_id	= set1.getString(1);
			String hom_gf_id	= set1.getString(2);
			gf_genes.put(hom_gf_id, new HashSet<String>());
			plaza_gf_trapid_gf.put(hom_gf_id, trap_gf_id);
		}
		set1.close();
		stmt1.close();

		//get the gene/gf mapping from the PLAZA database
		String sql2				= "SELECT `gf_id`,`gene_id` FROM `gf_data` WHERE `gf_id`=?";
		PreparedStatement stmt2	= plaza_conn.prepareStatement(sql2);
		for(String hom_gf_id:gf_genes.keySet()){
			stmt2.setString(1,hom_gf_id);
			ResultSet set2			= stmt2.executeQuery();
			while(set2.next()){
				String gf_id	= set2.getString(1);
				String gene_id	= set2.getString(2);
				gf_genes.get(gf_id).add(gene_id);
			}
			set2.close();
		}
		stmt2.close();

		BufferedWriter writer = new BufferedWriter(new FileWriter(new File(output_file)));
		writer.write("#counter\ttrapid_gf_id\treference_gf_id\tgene_id\n");
		int counter = 0;
		for(String gf_id:gf_genes.keySet()){
			Set<String> genes = gf_genes.get(gf_id);
			String trap_gf_id	= plaza_gf_trapid_gf.get(gf_id);
			for(String gene:genes){
				counter++;
				writer.write(counter+"\t"+trap_gf_id+"\t"+gf_id+"\t"+gene+"\n");
			}
		}
		writer.close();
	}



	protected void exportGfReferenceIortho(Connection trapid_conn,String exp_id,String output_file) throws Exception{
		Map<String,Set<String>> gf_genes	= new HashMap<String,Set<String>>();

		String sql			= "SELECT `gf_id`,`gf_content` FROM `gene_families` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt		= trapid_conn.createStatement();
		ResultSet set		= stmt.executeQuery(sql);
		while(set.next()){
			String gf_id		= set.getString(1);
			String[] gene_list	= set.getString(2).split(" ");
			Set<String> genes	= new HashSet<String>();
			for(String g:gene_list){genes.add(g);}
			gf_genes.put(gf_id,genes);
		}
		set.close();
		stmt.close();

		BufferedWriter writer = new BufferedWriter(new FileWriter(new File(output_file)));
		writer.write("#counter\ttrapid_gf_id\tgene_id\n");
		int counter = 0;
		for(String gf_id:gf_genes.keySet()){
			Set<String> genes = gf_genes.get(gf_id);
			for(String gene:genes){
				counter++;
				writer.write(counter+"\t"+gf_id+"\t"+gene+"\n");
			}
		}
		writer.close();
	}

	public void exportGfReference(Connection plaza_conn,Connection trapid_conn,String exp_id,String output_file) throws Exception{
		//2 different types of queries, depending on GF type of the experiment.
		String gf_type	= this.getExperimentGfType(trapid_conn, exp_id);
		if(gf_type.equals("HOM")){
			this.exportGfReferenceHom(plaza_conn,trapid_conn,exp_id,output_file);
		}
		else if(gf_type.equals("IORTHO")){
			this.exportGfReferenceIortho(trapid_conn,exp_id,output_file);
		}
		else{
			throw new Exception("Unknown GF type : "+gf_type);
		}
	}


	private Map<String,String> getGoDescriptions(Connection conn) throws Exception{
		Map<String,String> result		= new HashMap<String,String>();
		String sql						= "SELECT `go`,`desc` FROM `extended_go` ";
		Statement stmt					= conn.createStatement();
		ResultSet set					= stmt.executeQuery(sql);
		while(set.next()){
			String go					= set.getString(1);
			String desc					= set.getString(2);
			result.put(go,desc);
		}
		set.close();
		stmt.close();
		return result;
	}

	private Map<String,String> getProteinDomainDescriptions(Connection conn) throws Exception{
		Map<String,String> result		= new HashMap<String,String>();
		String sql						= "SELECT `motif_id`,`desc` FROM `protein_motifs` ";
		Statement stmt					= conn.createStatement();
		ResultSet set					= stmt.executeQuery(sql);
		while(set.next()){
			String motif_id				= set.getString(1);
			String desc					= set.getString(2);
			result.put(motif_id,desc);
		}
		set.close();
		stmt.close();
		return result;
	}


	public void exportTranscriptGo(Connection plaza_connection,Connection conn,String exp_id,String output_file) throws Exception{
		Map<String,String> go_descriptions	= this.getGoDescriptions(plaza_connection);
		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file)));
		String sql				= "SELECT `transcript_id`,`name`,`is_hidden` FROM `transcripts_annotation` WHERE `experiment_id`='"+exp_id+"' AND `type`='go'";
		// TRAPID v2 database does not have this structure anymore
		// String sql				= "SELECT `transcript_id`,`go`,`is_hidden` FROM `transcripts_go` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt			= conn.createStatement();
		ResultSet set			= stmt.executeQuery(sql);
		int counter				= 0;
		writer.write("#counter\ttranscript_id\tgo\tevidence_code\tis_hidden\tdescription\n");
		while(set.next()){
			counter++;
			String transcript_id		= set.getString("transcript_id");
			String go					= set.getString("go");
			String is_hidden			= set.getString("is_hidden");
			String desc					= go_descriptions.get(go);
			writer.write(counter+"\t"+transcript_id+"\t"+go+"\t"+"ISS"+"\t"+is_hidden+"\t"+desc+"\n");
		}
		stmt.close();
		writer.close();
	}

	public void exportGoTranscript(Connection plaza_connection,Connection conn,String exp_id,String output_file) throws Exception{
		Map<String,String> go_descriptions	= this.getGoDescriptions(plaza_connection);
		String sql				= "SELECT `transcript_id`,`name` FROM `transcripts_annotation` WHERE `experiment_id`='"+exp_id+"' AND `type`='go'";
		// TRAPID v2 database does not have this structure anymore
		// String sql				= "SELECT `transcript_id`,`go` FROM `transcripts_go` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt			= conn.createStatement();
		ResultSet set			= stmt.executeQuery(sql);
		SortedMap<String,Set<String>> tmp	= new TreeMap<String,Set<String>>();
		while(set.next()){
			String transcript_id		= set.getString("transcript_id");
			String go					= set.getString("go");
			if(!tmp.containsKey(go)){tmp.put(go,new HashSet<String>());}
			tmp.get(go).add(transcript_id);
		}
		stmt.close();

		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file)));
		int counter				= 0;
		writer.write("#counter\tgo\tevidence_code\tdescription\tnum_transcripts\ttranscripts\n");
		for(String go:tmp.keySet()){
			counter++;
			Set<String> genes	= tmp.get(go);
			String genes_string	= this.make_string(genes);
			String desc			= go_descriptions.get(go);
			writer.write(counter+"\t"+go+"\t"+"ISS"+"\t"+desc+"\t"+genes.size()+"\t"+genes_string+"\n");
		}
		writer.close();
	}



	public void exportTranscriptInterpro(Connection plaza_connection,Connection conn,String exp_id,String output_file) throws Exception{
		Map<String,String> interpro_descriptions	= this.getProteinDomainDescriptions(plaza_connection);
		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file)));
		String sql				= "SELECT `transcript_id`,`interpro` FROM `transcripts_annotation` WHERE `experiment_id`='"+exp_id+"' AND `type`='ipr'";
		// TRAPID v2 database does not have this structure anymore
		// String sql				= "SELECT `transcript_id`,`interpro` FROM `transcripts_interpro` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt			= conn.createStatement();
		ResultSet set			= stmt.executeQuery(sql);
		int counter				= 0;
		writer.write("#counter\ttranscript_id\tinterpro\tdescription\n");
		while(set.next()){
			counter++;
			String transcript_id		= set.getString("transcript_id");
			String interpro				= set.getString("interpro");
			String desc					= interpro_descriptions.get(interpro);
			writer.write(counter+"\t"+transcript_id+"\t"+interpro+"\t"+desc+"\n");
		}
		stmt.close();
		writer.close();
	}



	public void exportInterproTranscript(Connection plaza_connection,Connection conn,String exp_id,String output_file) throws Exception{
		Map<String,String> interpro_descriptions	= this.getProteinDomainDescriptions(plaza_connection);
		String sql				= "SELECT `transcript_id`,`interpro` FROM `transcripts_annotation` WHERE `experiment_id`='"+exp_id+"' AND `type`='ipr'";
		// TRAPID v2 database does not have this structure anymore
		// String sql				= "SELECT `transcript_id`,`interpro` FROM `transcripts_interpro` WHERE `experiment_id`='"+exp_id+"' ";
		Statement stmt			= conn.createStatement();
		ResultSet set			= stmt.executeQuery(sql);
		SortedMap<String,Set<String>> tmp	= new TreeMap<String,Set<String>>();
		while(set.next()){
			String transcript_id		= set.getString("transcript_id");
			String interpro				= set.getString("interpro");
			if(!tmp.containsKey(interpro)){tmp.put(interpro,new HashSet<String>());}
			tmp.get(interpro).add(transcript_id);
		}
		stmt.close();

		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file)));
		int counter				= 0;
		writer.write("#counter\tinterpro\tdescription\tnum_transcripts\ttranscripts\n");
		for(String interpro:tmp.keySet()){
			counter++;
			Set<String> genes	= tmp.get(interpro);
			String genes_string	= this.make_string(genes);
			String desc			= interpro_descriptions.get(interpro);
			writer.write(counter+"\t"+interpro+"\t"+desc+"\t"+genes.size()+"\t"+genes_string+"\n");
		}
		writer.close();
	}




	public String getExperimentGfType(Connection conn,String exp_id) throws Exception{
		String result		= null;
		Statement stmt		= conn.createStatement();
		String sql			= "SELECT `genefamily_type` FROM `experiments` WHERE `experiment_id`='"+exp_id+"' ";
		ResultSet set		= stmt.executeQuery(sql);
		if(set.next()){
			result			= set.getString(1);
		}
		set.close();
		stmt.close();
		return result;
	}


	private String make_string(Set<String> data) throws Exception{
		if(data.size()==0){return "";}
		StringBuffer buff		= new StringBuffer();
		for(String d:data){buff.append(" "+d);}
		String result			= buff.substring(1);
		return result;
	}

	private String translateSequence(String cds_sequence,Map<String,Character> map)throws Exception{
		String result = null;
		while(cds_sequence.length()%3!=0){
			cds_sequence+="N";
		}
		StringBuffer buffer = new StringBuffer();
		for(int i=0;i<cds_sequence.length()-2;i+=3){
			String codon = cds_sequence.substring(i,i+3);
			Character c = map.get(codon);
			if(c==null){
				c		= 'X';
			}
			buffer.append(c);
		}
		result = buffer.toString();
		return result;
	}


	private Map<String,Character> getTranslateMap(){
		Map<String,Character> map		= new HashMap<String,Character>();
		//start with an A
		map.put("AAA",'K');	map.put("AAG",'K');	map.put("AAC",'N');	map.put("AAT",'N');
		map.put("AGA",'R');	map.put("AGG",'R');	map.put("AGC",'S');	map.put("AGT",'S');
		map.put("ACA",'T');	map.put("ACG",'T');	map.put("ACC",'T');	map.put("ACT",'T');
		map.put("ATA",'I');	map.put("ATG",'M');	map.put("ATC",'I');	map.put("ATT",'I');

		//start with a G
		map.put("GAA",'E'); map.put("GAG",'E'); map.put("GAC",'D');	map.put("GAT",'D');
		map.put("GGA",'G');	map.put("GGG",'G');	map.put("GGC",'G');	map.put("GGT",'G');
		map.put("GCA",'A');	map.put("GCG",'A'); map.put("GCC",'A');	map.put("GCT",'A');
		map.put("GTA",'V'); map.put("GTG",'V');	map.put("GTC",'V');	map.put("GTT",'V');
		//special cases
		map.put("GGR",'G'); map.put("GGY",'G');
		map.put("GGR",'M'); map.put("GGK",'G');
		map.put("GGW",'G'); map.put("GGS",'G');
		map.put("GGN",'G');

		//starts with a C
		map.put("CAA",'Q'); map.put("CAG",'Q');	map.put("CAC",'H');	map.put("CAT",'H');
		map.put("CGA",'R');	map.put("CGG",'R');	map.put("CGC",'R');	map.put("CGT",'R');
		map.put("CCA",'P');	map.put("CCG",'P');	map.put("CCC",'P');	map.put("CCT",'P');
		map.put("CTA",'L');	map.put("CTG",'L');	map.put("CTC",'L');	map.put("CTT",'L');

		//starts with a T
		map.put("TAA",'*'); map.put("TAG",'*');	map.put("TAC",'Y');	map.put("TAT",'Y');
		map.put("TGA",'*');	map.put("TGG",'W');	map.put("TGC",'C');	map.put("TGT",'C');
		map.put("TCA",'S');	map.put("TCG",'S');	map.put("TCC",'S');	map.put("TCT",'S');
		map.put("TTA",'L');	map.put("TTG",'L');	map.put("TTC",'F');	map.put("TTT",'F');

		return map;
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
