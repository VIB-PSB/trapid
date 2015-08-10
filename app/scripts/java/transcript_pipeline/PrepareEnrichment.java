package transcript_pipeline;

import java.io.BufferedWriter;
import java.io.File;
import java.io.FileWriter;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.HashSet;
import java.util.Set;


/**
 * Stub class for creating the files necessary for GO enrichment
 * @author mibel
 *
 */
public class PrepareEnrichment {

	
	public static void main(String[] args){
		PrepareEnrichment	pge	= new PrepareEnrichment();
		try{
			//workbench variables, necessary for storing homology/orthology information
			String trapid_server				= args[0];
			String trapid_name					= args[1];
			String trapid_login					= args[2];
			String trapid_password				= args[3];
			
			String exp_id						= args[4];
			String data_type					= args[5];
			String all_gene_funcannot_file		= args[6];
			String subset_gene_funcannot_file	= args[7];
			String subset						= args[8];
			
			Class.forName("com.mysql.jdbc.Driver");	
			Connection trapid_db_connection		= pge.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			BufferedWriter all_writer			= new BufferedWriter(new FileWriter(new File(all_gene_funcannot_file)));
			BufferedWriter subset_writer		= new BufferedWriter(new FileWriter(new File(subset_gene_funcannot_file)));
			
			Set<String> subset_genes			= pge.getSubsetGenes(trapid_db_connection,exp_id,subset);
			String query						= null;
			String data_identifier				= null;
			if(data_type.equals("go")){				
				query							= "SELECT `transcript_id`,`go` FROM `transcripts_go` WHERE `experiment_id`='"+exp_id+"' ";
				data_identifier					= "go";
			}
			else if(data_type.equals("ipr")){			
				query							= "SELECT `transcript_id`,`interpro` FROM `transcripts_interpro` WHERE `experiment_id`='"+exp_id+"' ";
				data_identifier					= "interpro";	
			}
			else{
				throw new Exception("unknown data type : "+data_type);
			}					
			Statement stmt						= trapid_db_connection.createStatement();
			ResultSet set						= stmt.executeQuery(query);
			while(set.next()){
				String transcript_id			= set.getString("transcript_id");
				String funcannot				= set.getString(data_identifier);
				//now write to all file
				all_writer.write(transcript_id+"\t"+funcannot+"\n");
				//write to subset file if present in subset_genes
				if(subset_genes.contains(transcript_id)){
					subset_writer.write(transcript_id+"\t"+funcannot+"\n");
				}				
			}			
			set.close();
			stmt.close();
			all_writer.close();
			subset_writer.close();
			trapid_db_connection.close();
		}
		catch(Exception exc){
			exc.printStackTrace();
		}
		
	}	
	
	
	
	
	
	private Set<String> getSubsetGenes(Connection conn,String exp_id,String subset)throws Exception{
		Set<String> result		= new HashSet<String>();
		Statement stmt			= conn.createStatement();
		String query			= "SELECT `transcript_id` FROM `transcripts_labels` WHERE `experiment_id`='"+exp_id+"' AND `label`='"+subset+"' ";
		ResultSet set			= stmt.executeQuery(query);
		while(set.next()){
			result.add(set.getString("transcript_id"));
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

