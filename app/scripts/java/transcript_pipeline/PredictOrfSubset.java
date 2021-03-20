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
import java.util.Arrays;
import java.util.Collections;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Hashtable;
import java.util.List;
import java.util.Map;
import java.util.Properties;
import java.util.Set;
import java.util.SortedMap;
import java.util.TreeMap;
import org.ini4j.Ini;
import org.json.*;



/**
  * This program runs ORF prediction for selected translation table and transcript subset.
  * It is basically a stripped-down version of the TRAPID initial processing pipeline, that only runs the ORF finding
  * procedure.
 */

public class PredictOrfSubset {

    public static void main(String[] args){

        PredictOrfSubset pos    = new PredictOrfSubset();

        // Parse command-line arguments
        // TRAPID DB information
         String trapid_server = args[0];
         String trapid_name = args[1];
         String trapid_login = args[2];
         String trapid_password = args[3];
        // Experiment id
         String trapid_experiment_id = args[4];
        // Transcript subset name
        String trapid_subset_name = args[5];
        // Location of json file with translation tables data
         String transl_tables_file = args[6];
        // Selected translation table
         String transl_table    = args[7];


        Connection plaza_db_connection        = null;
        Connection trapid_db_connection        = null;

        try{

            /*
             * Executing the homology assignment
             * ==============================================================
             */

            Class.forName("com.mysql.jdbc.Driver");

            // Shouldn't we perform frame detection again when retranslating sequences? Update when needed!

            // Get longest ORFs in preferred frame.
            trapid_db_connection        = pos.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
            long t61    = System.currentTimeMillis();
            pos.performInitialORFPrediction(trapid_db_connection,trapid_experiment_id, trapid_subset_name, transl_tables_file, transl_table);
            long t62    = System.currentTimeMillis();
            timing("ORF prediction", t61, t62);
            // pos.update_log(trapid_db_connection,trapid_experiment_id,"orf_prediction","","3");
            // plaza_db_connection.close();
            trapid_db_connection.close();
        }
        catch(Exception exc){
            exc.printStackTrace();
            System.exit(1);
        }
        System.err.println("[Message] Subset sequences retranslated!");
    }



    /*
     * Update logging system
     */
    public void update_log(Connection trapid_connection,String exp_id,String action,String param,String depth) throws Exception{
        String sql    = "INSERT INTO `experiment_log`(`experiment_id`,`date`,`action`,`parameters`,`depth`) VALUES ('"+exp_id+"',NOW(),'"+action+"','"+param+"','"+depth+"')";
        Statement stmt    = trapid_connection.createStatement();
        stmt.execute(sql);
        stmt.close();
    }



    public static void timing(String msg,long t1,long t2){
        timing(msg,t1,t2,1);
    }
    public static void timing(String msg,long t1,long t2,int lvl){
        long t    = (t2-t1);
        for(int i=1;i<lvl;i++){System.out.print("\t");}
        System.out.println(msg+"\t"+t+"ms");
    }
    public static void timingNano(String msg,long t,int lvl){
        long t_final    = t/1000000;
        for(int i=1;i<lvl;i++){System.out.print("\t");}
        System.out.println(msg+"\t"+t_final+"ms");
    }


    /**
     * Funtion to perform intitial ORF prediction based on detected frame
     *
     *
     */
    public void performInitialORFPrediction(Connection db_connection,String experiment_id, String trapid_subset_name, String transl_tables_file, String transl_table) throws Exception{
        ORFFinder orf_finder = new ORFFinder(transl_tables_file, transl_table);

        Hashtable<String, String> geneSequences = new Hashtable<String, String>();
        Hashtable<String, Character> geneStrand = new Hashtable<String, Character>();
        Hashtable<String, Integer> geneFrame = new Hashtable<String, Integer>();

        // First get transcripts for selected experiment/subset
        // Not even needed to check the validity of the subset?
        // Also retrieve `is_rna_gene` and `full_frame_info` to:
        //   1. avoid inferring ORF sequences for transcripts flagged as RNA transcripts and with no similarity search hit
        //   2. reset frame for transcripts with no similarity search hit, but not flagged as RNA transcripts (frame needs to be inferred again).

        String query_transcripts    = "SELECT `transcript_id`, UNCOMPRESS(`transcript_sequence`), `detected_strand`, `detected_frame`, `is_rna_gene`, `full_frame_info` FROM `transcripts` WHERE `experiment_id` = \'" + experiment_id + "\' AND `transcript_id` IN (SELECT `transcript_id` FROM `transcripts_labels` WHERE `experiment_id` = \'" + experiment_id + "\' AND `label` = \'" + trapid_subset_name + "\')";
        Statement stmt                = db_connection.createStatement();
        ResultSet set                = stmt.executeQuery(query_transcripts);
        while(set.next()){
            // Skip RNA transcripts with no similarity search hit (full_frame_info is NULL)
            // Another way to deal with this would be to not query these transcripts in the first place.
            if(set.getInt(5) == 1 && set.getObject(6) == null) {
                // System.err.println(set.getString(1));
                continue;
            }
            geneSequences.put(set.getString(1), set.getString(2));
            geneStrand.put(set.getString(1), set.getString(3).charAt(0));
            geneFrame.put(set.getString(1), set.getInt(4));
            // If there is no DIAMOND hit, `full_frame_info` is NULL: set frame to 0.
            if(set.getObject(6) == null) {
                geneFrame.put(set.getString(1), 0);
                // System.err.println(set.getString(1));
            }
        }

        if(geneSequences.size() == 0) {
            System.err.println("[Warning] No sequences could retrieved for experiment " + experiment_id + " and subset " + trapid_subset_name);
        }

        set.close();
        stmt.close();
        String update_transcripts                     = "UPDATE `transcripts` SET `orf_sequence`= COMPRESS(?) , `orf_start` = ? , `orf_stop` = ?, `orf_contains_start_codon` = ?, `orf_contains_stop_codon` = ?, `transl_table` = ?  WHERE `experiment_id`='"+experiment_id+"' AND `transcript_id` = ? ";
        PreparedStatement stmt_update_transcripts    = db_connection.prepareStatement(update_transcripts);
        String update_frame_info                    = "UPDATE `transcripts` SET `detected_frame`=? , `detected_strand` = ? WHERE `experiment_id`='"+experiment_id+"' AND `transcript_id` = ? ";
        PreparedStatement stmt_update_frame            = db_connection.prepareStatement(update_frame_info);
        for (String gene: geneSequences.keySet()){
            String sequence         = geneSequences.get(gene);
            char strand             = geneStrand.get(gene);
            int frame                 = geneFrame.get(gene);
            Map<String,String> res    = orf_finder.findLongestORF(sequence, strand, frame);
            //default update of ORF information
            stmt_update_transcripts.setString(1,res.get("ORF"));
            stmt_update_transcripts.setString(2,res.get("start"));
            stmt_update_transcripts.setString(3,res.get("stop"));
            stmt_update_transcripts.setString(4,Boolean.parseBoolean(res.get("hasStartCodon"))?"1":"0");
            stmt_update_transcripts.setString(5,Boolean.parseBoolean(res.get("hasStopCodon"))?"1":"0");
            stmt_update_transcripts.setString(6, transl_table);
            stmt_update_transcripts.setString(7, gene);
            stmt_update_transcripts.execute();
            //if new frame information is present in result, do second update as well.
            if(res.containsKey("newFrame")){
                System.err.println("NEW FRAME AND/OR STRAND. ");
                stmt_update_frame.setString(1,res.get("newFrame"));
                stmt_update_frame.setString(2,res.get("newStrand"));
                stmt_update_frame.setString(3,gene);
                stmt_update_frame.execute();
            }
        }
        stmt_update_transcripts.close();
        stmt_update_frame.close();
        // System.out.println(update_transcripts.toString());
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
        // String url        = "jdbc:mysql://"+server+"/"+database;
        String url        = "jdbc:mysql://"+server+"/"+database+"?rewriteBatchedStatements=true";
        Connection conn    = DriverManager.getConnection(url,login,password);
        return conn;
    }


    public class ORFFinder{

        private Hashtable<String,Character> codonLookUp;
        private Hashtable<String, Character> alternateCodonLookUp;
        public ORFFinder(String transl_tables_file, String transl_table) {
            // Initiate lookup tables
            codonLookUp = new Hashtable<String,Character>();
            alternateCodonLookUp = new Hashtable<String, Character>();

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

            try {
                String transl_tables_str = "";
                BufferedReader buf = new BufferedReader(new FileReader(transl_tables_file));
                String lineJustFetched = null;
                String[] wordsArray;
                while (true) {
                    lineJustFetched = buf.readLine();
                    if (lineJustFetched == null) {
                        break;
                    } else {
                        transl_tables_str = transl_tables_str + lineJustFetched;
                    }
                }
                buf.close();
                JSONObject all_transl_tables = new JSONObject(transl_tables_str);
                JSONObject transl_table_data = all_transl_tables.getJSONObject(transl_table).getJSONObject("table");
                // Store translation table data as regular hashmap
                for(String codon:transl_table_data.keySet()){
                    alternateCodonLookUp.put(codon, transl_table_data.getString(codon).charAt(0));
                }
                codonLookUp = alternateCodonLookUp;
                System.err.println("[Message] Translation table " + transl_table + " loaded from file " + transl_tables_file);
            } catch (Exception e) {
                System.err.println("[Message] No translation table file provided or invalid translation table index, will use the hardcoded (standard) one. Stack trace: ");
                e.printStackTrace();
            }
            System.err.println(Arrays.asList(codonLookUp)); // Debug: print hashmap
        }


        private String reverseComplement(String input){
            StringBuffer buffer = new StringBuffer(input.toUpperCase()).reverse();
            char[] new_sequence    = new char[buffer.length()];
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
                String forward_sequence    = sequence;
                String reverse_sequence    = this.reverseComplement(sequence);
                //iterate over the 3 frames and the 2 strands, and compute all the results. Then compare them.
                Map<String,Map<String,String>> cache    = new HashMap<String,Map<String,String>>();
                for(int i=1;i<=3;i++){    //forward_strand
                    cache.put("+"+i,this.findLongestORF(forward_sequence, i));
                }
                for(int i=1;i<=3;i++){ //reverse strand
                    cache.put("-"+i,this.findLongestORF(reverse_sequence, i));
                }
                //find best result
                String best_result            = null;
                int longest_orf_length        = -1;
                for(String possible_strand_frame:cache.keySet()){
                    if(cache.get(possible_strand_frame).get("ORF").length()>longest_orf_length){
                        best_result            = possible_strand_frame;
                        longest_orf_length    = cache.get(possible_strand_frame).get("ORF").length();
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
            Map<String,String> result        = new HashMap<String,String>();
            //final result
            String longestAA                = "";
            String longestORF                = "";
            int longestStart                = 0;
            int longestStop                    = 0;
            boolean longestHasStartCodon    = false;
            boolean longestHasStopCodon     = false;
            //create current sequence (placeholder for sequence found)
            StringBuffer currentAA             = new StringBuffer();
            StringBuffer currentORF         = new StringBuffer();
            int currentStart                 = 0;
            int currentStop                 = 0;
            boolean currentHasStartCodon     = false;
            boolean currentHasStopCodon     = false;

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
                        longestAA                 = currentAA.toString();
                        longestORF                 = currentORF.toString();
                        longestStart             = currentStart;
                        longestStop             = currentStop;
                        longestHasStartCodon     = currentHasStartCodon;
                        longestHasStopCodon     = currentHasStopCodon;
                    }
                    currentAA                 = new StringBuffer();
                    currentORF                 = new StringBuffer();;
                    currentStart             = 0;
                    currentStop             = 0;
                    currentHasStartCodon     = false;
                    currentHasStopCodon     = false;
                }
            }
            //final one,
            if (currentAA.length() > longestAA.length()){
                longestAA                     = currentAA.toString();
                longestORF                     = currentORF.toString();
                longestStart                 = currentStart;
                longestStop                 = currentStart + longestORF.length();
                longestHasStartCodon         = currentHasStartCodon;
                longestHasStopCodon         = currentHasStopCodon;
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
