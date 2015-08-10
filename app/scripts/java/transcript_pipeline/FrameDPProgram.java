package transcript_pipeline;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.Collection;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.SortedMap;
import java.util.TreeMap;

/**
 * This class is a utility class, used for creating a training data set for framedp.
 * Selecting random sequences is not a good idea, therefore the sequences are selected based on
 * the length-distribution of the transcripts.
 * 
 * Following steps are performed:
 * a) check whether experiment is in a 'finished' state. If previous step (initial processing) failed,
 * then this step is not necessary
 * b) perform the selection of transcripts and writing of multi-fasta file
 * c) update the framedp status of the experiment to 'training', so the next step in the pipeline is simply 
 * starting the framedp program 
 * 
 */
public class FrameDPProgram {

	
	public static void main(String[] args){
		FrameDPProgram ft		= new FrameDPProgram();
		
		String command			= args[0];
		try{
			if(command.equals("create_training_file")){
				ft.createTrainingFile(args);
			}
			else if(command.equals("check_training_output")){
				ft.checkTrainingOutput(args);
			}
			else if(command.equals("check_evaluation_output")){
				System.out.println("Evaluating FrameDP output: start");
				long t1			= System.currentTimeMillis();				
				ft.checkEvaluationOutput(args);				
				long t2			= System.currentTimeMillis();
				System.out.println("Evaluating FrameDP output: stop (Time used : "+((t2-t1)/1000)+"s)");
			}
			else{
				System.err.println("Unknown target command for FrameDPTraining.java");
				System.exit(1);
			}
		}
		catch(Exception exc){
			exc.printStackTrace();					
		}		
	}
	
	
	
	private void writeTrapidLog(Connection conn,String experiment_id,String action,String parameters,String depth) throws Exception{
		String sql	= "INSERT INTO `experiment_log` (`experiment_id`,`date`,`action`,`parameters`,`depth`) VALUES ('"+experiment_id+"',NOW(),?,?,?)";		
		PreparedStatement stmt	= conn.prepareStatement(sql);
		stmt.setString(1,action);
		stmt.setString(2,parameters);
		stmt.setString(3,depth);
		stmt.execute();
		stmt.close();
	}
	
	private void checkEvaluationOutput(String[] args) throws Exception{
		//trapid database variables
		String trapid_server				= args[1];
		String trapid_name					= args[2];
		String trapid_login					= args[3];
		String trapid_password				= args[4];			
		//experiment identifier
		String experiment_id				= args[5];	
		//location of training dir 
		String training_dir					= args[6];
		//file containing the names of the transcripts which were selected by the user.
		//should be a subset of the transcripts in the multifasta file
		String selected_transcripts_file	= args[7];			
		//multi fasta file containing ids of transcripts that were evaluated
		String multifastafile				= args[8];
		
		Connection trapid_db_connection		= null;
		try{
			Class.forName("com.mysql.jdbc.Driver");	
			trapid_db_connection			= this.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			
			this.writeTrapidLog(trapid_db_connection, experiment_id, "postprocessing","start", "2");
			
			Set<String> transcripts			= this.readSelectedTranscripts(selected_transcripts_file);
			//find the associated gene family(ies) for these transcripts,
			//and remove MSA/tree information.
			Set<String> gene_families		= this.findAssociatedGf(trapid_db_connection,experiment_id,transcripts);
			this.clearMsaTree(trapid_db_connection,experiment_id,gene_families);
			
			//retrieve transcript identifiers
			Set<String> all_transcripts			= this.retrieveTranscriptsFromFasta(multifastafile); 
			System.out.println("\t#Evaluated Transcripts : "+transcripts.size());
			System.out.println("\t#Fasta-transcripts : "+all_transcripts.size());
			if(transcripts.size()==0){
				//this.writeTrapidLog(trapid_db_connection, experiment_id, "Error", "No transcripts present in text file","2");
				throw new Exception("No transcripts present in text file : "+selected_transcripts_file);
			}
			if(all_transcripts.size()==0){
				//this.writeTrapidLog(trapid_db_connection, experiment_id, "Error", "No transcripts present in fasta file","2");
				throw new Exception("No transcripts present in multi-fasta file : "+multifastafile);
			}
			if(!all_transcripts.containsAll(transcripts)){
				//this.writeTrapidLog(trapid_db_connection, experiment_id, "Error", "Transcripts text file not subset of multi-fasta","2");
				throw new Exception("Transcripts text file not subset of multi-fasta");
			}
			
			
			//go over the training dir, and try to find the directories which correspond with these transcripts.
			Map<String,String> transcript2dir	= this.locateEvaluationDirectories(training_dir,transcripts);			
			if(transcript2dir.size()!=transcripts.size()){
				throw new Exception("Number of transcript directories ("+transcript2dir.size()+") not equal to number of transcripts ("+transcripts.size()+")");
			}
					
			Map<String,GffInfo> gff_infos				= new HashMap<String,GffInfo>();		
			int counter	= 0;
			for(String transcript:transcripts){
				String transcriptdirpath		= transcript2dir.get(transcript);
				//System.out.println(transcript+"\t\t"+transcriptdirpath);
				if(!transcriptdirpath.endsWith("/")){transcriptdirpath+="/";}				
				String gff_file_path			= transcriptdirpath+transcript+".gff3";				
				File gff_file					= new File(gff_file_path);				
				String gff_file_path2			= transcriptdirpath+transcript.replace('-','_')+".gff3";				
				File gff_file2					= new File(gff_file_path2);				
				if(!gff_file.exists() && !gff_file2.exists()){
					System.err.println("Gff file does not exist : "+gff_file_path+" or "+gff_file_path2);
				}
				GffInfo gff_info				= null;
				if(gff_file.exists()){gff_info	= this.parseGffFile(gff_file,transcript);}
				else if(gff_file2.exists()){gff_info = this.parseGffFile(gff_file2,transcript);}
				else{gff_info					= this.getDefaultData(transcript);}
				
								
				if(gff_info.transcript_sequence_corrected==null || (gff_info.start_orf==0 && gff_info.stop_orf==0)){
					System.err.println("Not enough info could be extracted from the GFF file for transcript "+transcript);	
					gff_info.only_checked	= true;
				}
				else{
					counter++;
				}								
				gff_infos.put(transcript, gff_info);									
			}		
			System.out.println("\t#Transcripts with GFF output : "+counter);
			this.storeUpdatedFrameData(trapid_db_connection,experiment_id,gff_infos);			
			this.writeTrapidLog(trapid_db_connection, experiment_id, "postprocessing","stop", "2");			
			trapid_db_connection.close();
		}
		catch(Exception exc){
			this.writeTrapidLog(trapid_db_connection, experiment_id, "Error", exc.toString(),"2");
			throw exc;
		}
	}
	
	
	public String extractOrf(String transcript,String transcript_sequence,int start_orf,int stop_orf,int frame,String strand)throws Exception{		
		int beginIndex		= start_orf;
		if(strand.equals("-")){
			if(frame==1){
				if(start_orf==1){ beginIndex = start_orf-1; }
				else{beginIndex	= start_orf-1;}
			}
			else if (frame==2){
				if(start_orf==1){beginIndex		= start_orf;}
				else{beginIndex = start_orf-1;}				
			}
			else if(frame==3){				
				if(start_orf==1){beginIndex		= start_orf+1;}
				else{beginIndex		= start_orf-1;}
			}
		}
		else{
			if(frame==1){								
				if(start_orf==1){beginIndex	= start_orf-1;}
				else{beginIndex	= start_orf-1;}
			}
			else if (frame==2){
				if(start_orf==1){beginIndex	= start_orf;}
				else{beginIndex	= start_orf-1;}
			}
			else if(frame==3){	
				if(start_orf==1){beginIndex	= start_orf+1;}
				else{beginIndex = start_orf-1;}
			}
		}
		
		
		/*if(strand.equals("-")){
			if(frame==3){
				beginIndex			= beginIndex+(frame)-4; 
			}	
			else{
				beginIndex			= beginIndex+(frame-3);//adapt for frame
			}
		}
		else if(strand.equals("+")){
			if(frame==3){
				beginIndex			= beginIndex+(frame-4);
			}
			else{
				beginIndex			= beginIndex+(frame-3);//adapt for frame
			}
		}*/
		if(beginIndex<0){beginIndex=0;}
		
		//System.out.println("DEBUG beginIndex "+transcript+"\t"+beginIndex);
		
		String orf_sequence	= transcript_sequence.substring(beginIndex,stop_orf);
		
		return orf_sequence;	
	}
	
	public String reverseComplement(String input){
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
	
	public GffInfo getDefaultData(String transcript) throws Exception{
		GffInfo gff_info		= new GffInfo();	
		gff_info.no_gff			= true;
		return gff_info;
	}
	
	private void log(String transcript,String message){
		if(transcript.equals("contig14563")){System.out.println("LOG : "+message);}
	}
	
	public GffInfo parseGffFile(File gff_file,String transcript)throws Exception{
		GffInfo gff_info		= new GffInfo();		
		BufferedReader reader	= new BufferedReader(new FileReader(gff_file));
		String s				= reader.readLine();
		while(s!=null){
			if(!s.startsWith("#")){
				String[] split	= s.split("\t");
				if(split.length>=7 && (split[0].equals(transcript) || split[0].replace('_','-').equals(transcript)) &&split[1].equals("FrameD")){
					if(split[2].equals("CDS")){					
						int start_orf		= Integer.parseInt(split[3]);
						int stop_orf		= Integer.parseInt(split[4]);						
						int current_orf_length	= Math.abs(gff_info.stop_orf-gff_info.start_orf);						
						int new_orf_length		= Math.abs(stop_orf-start_orf);
						log(transcript,"Current-orf-length : "+current_orf_length);
						log(transcript,"Possible-new-orf-length : "+new_orf_length);
						if(new_orf_length>current_orf_length){	
							log(transcript,"New-orf-length : "+new_orf_length);
							gff_info.start_orf	= start_orf;
							gff_info.stop_orf	= stop_orf;
							gff_info.strand		= split[6];
							String comment		= split[8];
							int frame			= this.getFrameFromComment(comment);
							if(frame==-1){throw new Exception("Unknown frame in comment for transcript "+transcript+" : "+comment);}
							gff_info.frame		= frame;
						}
					}
				}
			}
			s					= reader.readLine();
		}
		reader.close();

		String transcript_seq				= this.getTranscriptSequenceFromGff(gff_file);
		boolean hasBeenReversed				= this.hasBeenReversed(gff_file);
		log(transcript,"is_reversed : "+hasBeenReversed);
		
		/*
		if(gff_info.strand.equals("+")){
			String new_orf_sequence			= this.extractOrf(transcript,transcript_seq, gff_info.start_orf, gff_info.stop_orf, gff_info.frame,gff_info.strand);		
		}
		else if(gff_info.strand.equals("-")){			
		}
		else{
			throw new Exception("Unknown strand : "+gff_info.strand);
		}*/
		
		
		String transcript_seq_corrected		= null;	
		if(hasBeenReversed){
			//transcript_seq_corrected 		= this.reverseComplement(transcript_seq);
			transcript_seq_corrected		= transcript_seq;	
			gff_info.strand					= this.reverseStrand(gff_info.strand);
		}				
		else{
			transcript_seq_corrected		= transcript_seq;					
		}
			
		
		String new_orf_sequence				= this.extractOrf(transcript,transcript_seq, gff_info.start_orf, gff_info.stop_orf, gff_info.frame,gff_info.strand);
		log(transcript,new_orf_sequence);
		
		gff_info.transcript_sequence_corrected	= transcript_seq_corrected;		
		gff_info.new_orf_sequence				= new_orf_sequence;
		return gff_info;
	}
	
	private String reverseStrand(String strand){
		if(strand.equals("-")){return "+";}
		else if(strand.equals("+")){return "-";}
		else{return "+";}
	}
	
	private int getFrameFromComment(String comment){
		String[] split	= comment.split(";");
		String com		= split[0].trim();
		if(!com.startsWith("ID=")){return -1;}
		String [] split2	= com.split(":");
		try{
			int result		= Integer.parseInt(split2[split2.length-1]);
			return result;
		}
		catch(Exception e){return -1;}
	}
	
	private class GffInfo{
		public String transcript_sequence_corrected	= null;
		public String new_orf_sequence				= null;
		public int start_orf						= 0;
		public int stop_orf							= 0;		
		public String strand						= "+";
		public int frame							= 0;
		public boolean only_checked					= false;
		public boolean no_gff						= false;
	}
	
	
	
	private void storeUpdatedFrameData(Connection conn,String exp_id,			
			Map<String,GffInfo>gff_infos) throws Exception{
		String sql1						= "UPDATE `transcripts` SET " +
				"`transcript_sequence_corrected`=?," +
				" `is_frame_corrected`='1'," +
				" `is_framedp_run`='1', "+
				" `orf_sequence`=?," +
				" `orf_start` = ? ," +
				" `orf_stop` = ?," +
				" `orf_contains_start_codon` = ?," +
				" `orf_contains_stop_codon` = ?," +
				" `detected_frame`=?,"+
				" `detected_strand` = ? "+
 				" WHERE `experiment_id`='"+exp_id+"' AND `transcript_id`=?";
		PreparedStatement stmt1			= conn.prepareStatement(sql1);
		
		
		String sql2	= "UPDATE `transcripts` SET " +
			" `is_framedp_run`='1' WHERE `experiment_id`='"+exp_id+"' AND `transcript_id`=?";
		PreparedStatement stmt2			= conn.prepareStatement(sql2);
			
		
		for(String transcript:gff_infos.keySet()){
			GffInfo gff_info				= gff_infos.get(transcript);
			if(gff_info.no_gff || gff_info.only_checked){
				stmt2.setString(1,transcript);
				stmt2.execute();
				System.out.println("###"+transcript+" -- defaulting ");
			}
			else{
				String corrected_transcript_seq	= gff_info.transcript_sequence_corrected;
				String corrected_orf_seq		= gff_info.new_orf_sequence;
				stmt1.setString(1,corrected_transcript_seq);
				stmt1.setString(2,corrected_orf_seq);			
				stmt1.setString(3,""+gff_info.start_orf);
				stmt1.setString(4,""+gff_info.stop_orf);
				int has_start_codon	= 0; if(corrected_orf_seq.startsWith("ATG")){has_start_codon=1;}
				int has_stop_codon	= 0; if(corrected_orf_seq.endsWith("TAA")||corrected_orf_seq.endsWith("TGA")||corrected_orf_seq.endsWith("TAG")){has_stop_codon	= 1;}
				stmt1.setString(5,""+has_start_codon);
				stmt1.setString(6,""+has_stop_codon);
				stmt1.setString(7,""+gff_info.frame);
				stmt1.setString(8,""+gff_info.strand);
				stmt1.setString(9,transcript);
				stmt1.execute();			
				System.out.println("###"+transcript+"\t"+gff_info.frame+"\t"+gff_info.strand);
			}
		}		
		stmt1.close();
		stmt2.close();
	}
	
	
	
	private boolean hasBeenReversed(File gff_file) throws Exception{
		boolean	result			= false;
		BufferedReader reader	= new BufferedReader(new FileReader(gff_file));
		String s				= reader.readLine();
		while(s!=null){
			if(s.startsWith(">")){
				if(s.contains("[corrected by FrameD]") && s.contains("reverse complemented by FrameDP")){
					result		= true;
				}
			}
			s					= reader.readLine();
		}		
		reader.close();
		return result;
	}
	
	private String getTranscriptSequenceFromGff(File gff_file) throws Exception{
		String result			= null;
		BufferedReader reader	= new BufferedReader(new FileReader(gff_file));
		String s				= reader.readLine();
		StringBuffer buff		= new StringBuffer();
		boolean reading_seq		= false;
		while(s!=null){
			if(s.startsWith(">")){
				if(s.contains("[corrected by FrameD]")){reading_seq		= true;}
				else{reading_seq=false;}
			}
			else{
				if(reading_seq){
					buff.append(s.trim());
				}
			}
			s					= reader.readLine();
		}		
		reader.close();
		if(buff.length()!=0){
			result			= buff.toString().toUpperCase();
		}		
		return result;
	}
	
	
	
	
	private Map<String,String> locateEvaluationDirectories(String training_dir_path,Set<String> transcripts) throws Exception{
		if(!training_dir_path.endsWith("/")){
			training_dir_path	= training_dir_path+"/";
		}
		Map<String,String> result		= new HashMap<String,String>();
		File training_dir				= new File(training_dir_path);
		if(!(training_dir.exists()&&training_dir.isDirectory())){throw new Exception("Training directory does not exist : "+training_dir_path);}
		for(int i=0;i<=9;i++){
			String subdir_path			= training_dir_path+"00"+i;
			File subdir					= new File(subdir_path);
			if(subdir.exists() && subdir.isDirectory()){
				File[] transcriptdirs	= subdir.listFiles();									
				for(File transcriptdir:transcriptdirs){
					String transcriptdirname	= transcriptdir.getName().trim();					
					if(transcripts.contains(transcriptdirname)){
						result.put(transcriptdirname,transcriptdir.getAbsolutePath());
					}
					//also check for the ridiculous dash to underscore conversion
					String transcriptdirname2	= transcriptdir.getName().trim().replace('_','-');
					if(transcripts.contains(transcriptdirname2)){
						result.put(transcriptdirname2, transcriptdir.getAbsolutePath());
					}
					
				}
			}
		}
		return result;
	}
	
	
	public void clearMsaTree(Connection conn,String experiment_id,Set<String> gf_ids) throws Exception{
		String sql				= "UPDATE `gene_families` SET `used_species`=NULL , `exclude_transcripts`=NULL , `msa`=NULL , `msa_stripped`=NULL , `msa_stripped_params`=NULL , `tree`=NULL , `xml_tree`=NULL WHERE `experiment_id` = ? AND `gf_id`= ? ";
		PreparedStatement stmt	= conn.prepareStatement(sql);
		for(String gf_id : gf_ids){
			System.out.println("DEBUG : Clearing output for gene family : "+gf_id);
			stmt.setString(1,experiment_id);
			stmt.setString(2,gf_id);		
			stmt.executeUpdate();
		}		
		stmt.close();
	}
	
	
	public Set<String> findAssociatedGf(Connection conn,String experiment_id,Set<String> transcript_ids) throws Exception{
		Set<String> result			= new HashSet<String>();
		String sql					= "SELECT `gf_id` FROM `transcripts` WHERE `experiment_id`=? AND `transcript_id` = ? ";
		PreparedStatement stmt		= conn.prepareStatement(sql);
		for(String transcript_id : transcript_ids){
			stmt.setString(1,experiment_id);
			stmt.setString(2,transcript_id);
			ResultSet set = stmt.executeQuery();
			while(set.next()){
				String gf_id	= set.getString(1);
				if(gf_id!=null && !gf_id.equals("NULL")){
					result.add(gf_id);
					//System.out.println("DEBUG : detected gene family "+gf_id);
				}
			}
			set.close();
		}
		stmt.close();
		return result;
	}
	
	
	public Set<String> readSelectedTranscripts(String text_file) throws Exception{
		Set<String> result				= new HashSet<String>();
		File file						= new File(text_file);	
		if(!file.exists()){
			throw new Exception("File with selected transcripts does not exist : "+ text_file);
		}
		BufferedReader reader			= new BufferedReader(new FileReader(file));
		String s						= reader.readLine();
		while(s!=null){
			String transcript			= s.trim();
			if(!transcript.equals("")){
				result.add(transcript);
			}
			s							= reader.readLine();
		}
		reader.close();
		return result;
	}
	
	public Set<String> retrieveTranscriptsFromFasta(String fastafile) throws Exception{
		Set<String> result				= new HashSet<String>();
		File file						= new File(fastafile);
		if(!file.exists()){
			throw new Exception("Multi fasta file does not exist : "+fastafile);
		}
		BufferedReader reader			= new BufferedReader(new FileReader(file));
		String s						= reader.readLine();
		while(s!=null){
			if(s.startsWith(">")){
				String transcript		= s.substring(1).trim();
				result.add(transcript);
			}
			s							= reader.readLine();
		}
		reader.close();
		return result;
	}
	
	
	
	private void checkTrainingOutput(String[] args) throws Exception{
		//trapid database variables
		String trapid_server				= args[1];
		String trapid_name					= args[2];
		String trapid_login					= args[3];
		String trapid_password				= args[4];			
		//experiment identifier
		String experiment_id				= args[5];	
		//location of training dir 
		String training_dir					= args[6];
		
		Connection trapid_db_connection		= null;
		try{
			Class.forName("com.mysql.jdbc.Driver");	
			trapid_db_connection					= this.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			this.checkExperimentProcessState(trapid_db_connection,experiment_id,"finished");
			this.checkFramedpState(trapid_db_connection,experiment_id,"training");
			//check content of training directory
			boolean is_ok 							= this.checkContentTrainingDir(training_dir);
			if(!is_ok){
				this.setExperimentFrameDpStatus(trapid_db_connection,experiment_id,"error");
			}
			else{
				this.setExperimentFrameDpStatus(trapid_db_connection,experiment_id,"finished");				
			}
			trapid_db_connection.close();
		}
		catch(Exception exc){
			try{
				this.setExperimentFrameDpStatus(trapid_db_connection,experiment_id,"error");
				trapid_db_connection.close();				
			}
			catch(Exception e){}
			throw exc;
		}		
	}
	
	
	private void createTrainingFile(String[] args) throws Exception{		
		//trapid database variables
		String trapid_server				= args[1];
		String trapid_name					= args[2];
		String trapid_login					= args[3];
		String trapid_password				= args[4];			
		//experiment identifier
		String experiment_id				= args[5];			
		//number of transcripts	
		int num_transcripts					= Integer.parseInt(args[6]);			
		//output directory	
		String output_file					= args[7];
				
		Connection trapid_db_connection		= null;
		try{
			Class.forName("com.mysql.jdbc.Driver");	
			trapid_db_connection					= this.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			this.checkExperimentProcessState(trapid_db_connection,experiment_id,"finished");			
			SortedMap<String,Integer> orf_lengths	= this.getOrfLengths(trapid_db_connection,experiment_id);
			Map<Integer,List<String>> bins			= this.createBins(orf_lengths, num_transcripts);
			SortedMap<Integer,String> selected_transcripts		= this.selectTranscripts(bins);
			//for(int bin_index:selected_transcripts.keySet()){String sel_tran=selected_transcripts.get(bin_index);int transcript_length=orf_lengths.get(sel_tran);System.out.println(bin_index+"\t"+sel_tran+"\t"+transcript_length);}
			Map<String,String> sequences			= this.getSequences(trapid_db_connection,experiment_id,selected_transcripts.values());			
			this.writeOutputFile(output_file,sequences);			
			this.setExperimentFrameDpStatus(trapid_db_connection,experiment_id,"training");
			trapid_db_connection.close();
		}
		catch(Exception exc){
			try{
				this.setExperimentFrameDpStatus(trapid_db_connection,experiment_id,"error");
				trapid_db_connection.close();				
			}
			catch(Exception e){}
			throw exc;
		}
	}
	

	
	private boolean checkContentTrainingDir(String training_dir) throws Exception{
		File dir			= new File(training_dir);
		if(!(dir.exists() && dir.isDirectory())){return false;}
		String[] files		= dir.list();
		boolean contains_par	= false;
		boolean contains_info	= false;
		boolean contains_mat	= false;
		for(String f:files){
			if(f.endsWith("par")){contains_par=true;}
			if(f.endsWith("info")){contains_info=true;}
			if(f.contains("mat")){contains_mat=true;}
		}
		if(contains_par && contains_info && contains_mat){return true;}		
		return false;
	}
	
	
	private void checkFramedpState(Connection conn,String experiment_id,String required_state)throws Exception{
		String sql			= "SELECT `framedp_state` FROM `experiments` WHERE `experiment_id`='"+experiment_id+"' ";
		Statement stmt		= conn.createStatement();
		ResultSet set		= stmt.executeQuery(sql);
		if(!set.next()){throw new Exception("Experiment '"+experiment_id+"' does not exist");}
		String state		= set.getString(1);
		if(!state.equals(required_state)){throw new Exception("Attempting to check framedp output for experiment '"+experiment_id+"' not in training phase");}		
		set.close();
		stmt.close();
	}
	
	private void checkExperimentProcessState(Connection conn,String experiment_id,String required_state)throws Exception{
		String sql			= "SELECT `process_state` FROM `experiments` WHERE `experiment_id`='"+experiment_id+"' ";
		Statement stmt		= conn.createStatement();
		ResultSet set		= stmt.executeQuery(sql);
		if(!set.next()){throw new Exception("Experiment '"+experiment_id+"' does not exist");}
		String state		= set.getString(1);
		if(!state.equals(required_state)){throw new Exception("Attempting to create extract frameDP training from non-finished experiment '"+experiment_id+"' ");}		
		set.close();
		stmt.close();
	}
	
	
	
	private void writeOutputFile(String output_file,Map<String,String> sequences) throws Exception{
		BufferedWriter writer		= new BufferedWriter(new FileWriter(new File(output_file)));
		for(String transcript_id : sequences.keySet()){
			String seq				= sequences.get(transcript_id);
			writer.write(">"+transcript_id+"\n");
			writer.write(seq+"\n");
		}
		writer.close();		
	}
	
	
	
	private Map<String,String> getSequences(Connection conn,String exp_id,Collection<String> transcripts) throws Exception{
		Map<String,String> result		= new HashMap<String,String>();
		String sql						= "SELECT `transcript_sequence` FROM `transcripts` WHERE `experiment_id`='"+exp_id+"' AND `transcript_id`= ? ";
		PreparedStatement stmt			= conn.prepareStatement(sql);
		for(String transcript:transcripts){
			stmt.setString(1,transcript);
			ResultSet set				= stmt.executeQuery();
			set.next();
			String transcript_sequence	= set.getString(1);
			result.put(transcript,transcript_sequence);			
			set.close();
		}		
		stmt.close();
		return result;
	}
	
	
	private SortedMap<Integer,String> selectTranscripts(Map<Integer,List<String>> bins){
		SortedMap<Integer,String> result			= new TreeMap<Integer,String>();
		for(int bin_index:bins.keySet()){
			List<String> list		= bins.get(bin_index);
			if(list.size()>0){
				result.put(bin_index,list.get(0));
			}
		}		
		return result;
	}
	
	
	private int[] getExtremes(Map<String,Integer> orf_lengths){
		int shortest_length		= Integer.MAX_VALUE;
		int longest_length		= 0;
		for(Map.Entry<String,Integer> e:orf_lengths.entrySet()){
			int l				= e.getValue();
			if(l<shortest_length){shortest_length=l;}
			if(l>longest_length){longest_length=l;}
		}
		int[] result			= {shortest_length,longest_length};
		return result;
	}
	
	private Map<Integer,List<String>> createBins(Map<String,Integer> orf_lengths,int num_transcripts) throws Exception{
	
		//find shortest and longest values.
		int[] min_max			= getExtremes(orf_lengths);
		double min				= min_max[0];
		double max				= min_max[1];
		double num_trans		= num_transcripts;
		//now, create the bins and initialize it
		Map<Integer,List<String>> bins	= new HashMap<Integer,List<String>>();
		for(int i=0;i<num_transcripts;i++){
			bins.put(i,new ArrayList<String>());	//list for non-random access
		}
		double bin_size			= (max-min)/num_trans;
		for(Map.Entry<String,Integer> e:orf_lengths.entrySet()){
			int val				= e.getValue();			
			int bin				= (int)(Math.floor((val-min)/bin_size));
			if(bin>=num_transcripts){bin=num_transcripts-1;}
			bins.get(bin).add(e.getKey());
		}
		return bins;
	}
	
	
	private SortedMap<String,Integer> getOrfLengths(Connection conn,String experiment_id) throws Exception{
		SortedMap<String,Integer> result	= new TreeMap<String,Integer>();
		String sql					= "SELECT `transcript_id`,`orf_sequence` FROM `transcripts` WHERE `experiment_id`='"+experiment_id+"' ";
		Statement stmt				= conn.createStatement();
		ResultSet set				= stmt.executeQuery(sql);
		while(set.next()){
			String transcript_id	= set.getString(1);
			int orf_seq_length		= set.getString(2).length();
			result.put(transcript_id,orf_seq_length);
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	private void setExperimentFrameDpStatus(Connection trapid_connection,String experiment_id,String state) throws Exception{
		String sql		= "UPDATE `experiments` SET `framedp_state`='"+state+"' WHERE `experiment_id`='"+experiment_id+"' ";
		Statement stmt	= trapid_connection.createStatement();
		stmt.execute(sql);
		stmt.close();
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
