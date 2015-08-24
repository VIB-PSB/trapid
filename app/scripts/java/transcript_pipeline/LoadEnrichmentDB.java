package transcript_pipeline;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;

public class LoadEnrichmentDB {

	
	public static void main(String[] args){
		LoadEnrichmentDB le	= new LoadEnrichmentDB();
		try{
			//workbench variables, necessary for storing homology/orthology information
			String trapid_server				= args[0];
			String trapid_name					= args[1];
			String trapid_login					= args[2];
			String trapid_password				= args[3];
			
			String exp_id						= args[4];
			String data_type					= args[5];
			String label						= args[6];			
			String enriched_file_path			= args[7];
			
			//create database connection
			Class.forName("com.mysql.jdbc.Driver");	
			Connection trapid_db_connection		= le.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			String sql							= "INSERT INTO `functional_enrichments`(`experiment_id`,`label`,`data_type`,`identifier`,`is_hidden`,`p_value`,`log_enrichment`,`subset_ratio`) VALUES (?,?,?,?,?,?,?,?) ;";
			PreparedStatement stmt				= trapid_db_connection.prepareStatement(sql); 			
			BufferedReader reader				= new BufferedReader(new FileReader(new File(enriched_file_path)));			
			String s							= reader.readLine();
			while(s!=null){
				String [] split					= s.split("\t");
				String identifier				= split[0].trim();
				String is_hidden				= split[1].trim();
				String pvalue					= split[2].trim();
				String logenrichment			= split[3].trim();
				String subsetratio				= split[4].trim();				
				stmt.setString(1, exp_id);
				stmt.setString(2, label);
				stmt.setString(3, data_type);
				stmt.setString(4, identifier);
				stmt.setString(5, is_hidden);
				stmt.setString(6, pvalue);
				stmt.setString(7, logenrichment);
				stmt.setString(8, subsetratio);				
				stmt.execute();				
				s								= reader.readLine();
			}			
			reader.close();
			stmt.close();
			trapid_db_connection.close();		
		}
		catch(Exception exc){
			exc.printStackTrace();
		}
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
