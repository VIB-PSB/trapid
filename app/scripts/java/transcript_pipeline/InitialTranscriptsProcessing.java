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

    // Minimum GF size required to perfrom meta-annotation
    public static final int META_MIN_GF_SIZE = 5;
    // GF outlier percentage to remove (meta-annotation)
    public static final double META_PERC_REMOVE = 0.05;

    public enum GF_TYPE {
        NONE,
        HOM,
        IORTHO
    }

    public enum FUNC_ANNOT {
        NONE,
        GF,
        BESTHIT,
        GF_BESTHIT
    }

    public enum SEQ_TYPE {
        TR,
        CDS
    }

    public static void main(String[] args) {
        int NUM_BLAST_HITS_CACHE = 50; //SEPRO: note that for the frameshift detection this should be sufficiently large !!

        InitialTranscriptsProcessing itp = new InitialTranscriptsProcessing();
        /*
         * Parse experiment initial processing configuration (INI file) and retrieve the necessary variables
         * =========================================================
         */

        Ini expConfig = new Ini();
        try {
            expConfig = itp.readExpConfig(args[0]);
        } catch (java.io.IOException e) {
            e.printStackTrace();
            System.exit(1);
        }

        //IMPORTANT!!!
        //DATABASE VARIABLES HERE ARE NOT STORED IN JAVA CODE - DUE TO SECURITY CONSTRAINTS -
        //BUT PASSED THROUGH PHP AND PERL!!

        //database variables, necessary for retrieving homology/orthology information from the
        //similarity hits
        String plaza_database_server = expConfig.get("reference_db", "reference_db_server"); // normally psbsql01.psb.ugent.be
        String plaza_database_name = expConfig.get("reference_db", "reference_db_name");
        String plaza_database_login = expConfig.get("reference_db", "reference_db_username");
        String plaza_database_password = expConfig.get("reference_db", "reference_db_password");

        // TRAPID DB varianles, necessary for storing homology/orthology information
        String trapid_server = expConfig.get("trapid_db", "trapid_db_server");
        String trapid_name = expConfig.get("trapid_db", "trapid_db_name");
        String trapid_login = expConfig.get("trapid_db", "trapid_db_username");
        String trapid_password = expConfig.get("trapid_db", "trapid_db_password");

        // user experiment id
        String trapid_experiment_id = expConfig.get("experiment", "exp_id");

        //location of the output file of the similarity search
        //This similarity output file is normally coming from Rapsearch2 (but any m8 formatted
        //file is actually OK).
        String similarity_search_file = args[1];

        //type of gene family assignment to be performed. Defined in enum GF_TYPE
        //dependend on this type of gf assignment, the correct function(s) should be called,
        //also filling in extra columns in table gene_families
        String gf_type_string = expConfig.get("initial_processing", "gf_type");
        GF_TYPE gf_type = GF_TYPE.NONE;
        for (GF_TYPE g : GF_TYPE.values()) {
            if (g.toString().equalsIgnoreCase(gf_type_string)) {
                gf_type = g;
            }
        }
        if (gf_type == GF_TYPE.NONE) {
            System.err.println("Incorrect parameter for the gene family type!");
            System.exit(1);
        }

        // Experiment tmp directory, used to get path to RFAM GO annotation tsv file
        String tmp_exp_dir = expConfig.get("experiment", "tmp_exp_dir");

        // Translation tables
        String transl_table = expConfig.get("initial_processing", "transl_table");
        String transl_tables_file = expConfig.get("initial_processing", "transl_tables_file");

        //number of top-hits from similarity search to take into account to decide on the
        //homology assignment. See methods and results from paper for explanation.
        //normally this number is 1 when the reference databases are either clade or species,
        //but 5 when the reference database is gene-family-representatives
        int num_top_hits = Integer.parseInt(expConfig.get("initial_processing", "num_top_hits"));

        //functional annotation transfer mode. Defined in type FUNC_ANNOT
        //depended on this type, the functional annotation will be transferred through the gene family
        //or through the best hit
        String func_annot_string = expConfig.get("initial_processing", "func_annot");
        FUNC_ANNOT func_annot = FUNC_ANNOT.NONE;
        for (FUNC_ANNOT fa : FUNC_ANNOT.values()) {
            if (fa.toString().equalsIgnoreCase(func_annot_string)) {
                func_annot = fa;
            }
        }
        if (func_annot == FUNC_ANNOT.NONE) {
            System.err.println("Incorrect parameter for the functional annotation mode!");
            System.exit(1);
        }

        // Taxonomic scope (only used when processing transcripts with EggNOG. Should be `None` otherwise
        String chosen_scope = expConfig.get("initial_processing", "tax_scope"); // "auto";
        // The minimum representation frequency of GF members to transfer functional annotation (e.g. if a GO term is
        // represented in at least this fraction of members of a gene family, assign it to the transcript).
        // Note: this variable is now retrieved from the experiment's initial processing configuration file
        double gf_min_rep = Double.parseDouble(expConfig.get("initial_processing", "gf_min_rep"));

        // Check if input sequences are (untranslated) CDSes. If they are, ORF prefiction is skipped, and everything
        // is translated in `+1` frame.
        // We keep track of that with `seq_type`.
        SEQ_TYPE seq_type = SEQ_TYPE.TR;
        boolean use_cds = Boolean.valueOf(expConfig.get("initial_processing", "use_cds"));
        if (use_cds) {
            seq_type = SEQ_TYPE.CDS;
        }
        Connection plaza_db_connection = null;
        Connection trapid_db_connection = null;

        try {
            /*
             * Executing the homology assignment
             * ==============================================================
             */

            Class.forName("com.mysql.jdbc.Driver");

            // If processing data using Eggnog as ref. db, several steps were already run beforehand: cleaning the DB, GF assignment, GO/KO annotation
            // In the future the DB name shouldn't be hardcoded...
            /* UNCLEAN AND WORK IN PROGRESS! */
            // For testing purposes just copy-paste things in this big `if`. In the future have two clearly and properly separated pipelines.
            if (plaza_database_name.equals("db_trapid_ref_eggnog_test_02")) {
                System.out.println("EGGNOG PROCESSING!");
                // No need to run step 1!

                // Step 2: parse the similarity search output file, and store some information in the TRAPID db:
                // transcript_id -> ({hit_gene,bitscore,query_start,query_stop,perc_identity,aln_length,log_e_val});
                long t21 = System.currentTimeMillis();
                Map<String, List<String[]>> simsearch_data = itp.parseSimilarityOutputFile(
                    similarity_search_file,
                    NUM_BLAST_HITS_CACHE
                );
                long t22 = System.currentTimeMillis();
                timing("Parsing similarity search file", t21, t22);

                // Step 2b: store the similarity search information in the database.
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t2b1 = System.currentTimeMillis();
                itp.storeSimilarityData(trapid_db_connection, trapid_experiment_id, simsearch_data);
                long t2b2 = System.currentTimeMillis();
                timing("Storing similarities information", t2b1, t2b2);
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 2c: determine for the similarity search what the best hitting species are
                // species -> hitcount
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                Map<String, Integer> species_hit_count = itp.getSpeciesHitCount(plaza_db_connection, simsearch_data);
                itp.storeBestSpeciesHitData(trapid_db_connection, trapid_experiment_id, species_hit_count);
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 3: create the transcript to gene family mapping, with extra info kept for later
                // This is the actual homology assignment done for the transcripts.
                // TODO: read assigned GFs directly from TRAPID database (since GF assignment was already performed in the python script)
                // plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t31 = System.currentTimeMillis();
                Map<String, GeneFamilyAssignment> transcript2gf = null;
                Map<String, List<String>> gf2transcripts = null; // Reverse mapping

                // TODO: check taxonomic scope (must be 'auto' or anything in `LEVEL_CONTENT`)
                // Initialize OG-specific maps.
                // Map<String,List<String>> transcript2ogs	= null;
                // Map<String, List<String>> ogs2transcripts = null;
                // We retrieve all the corresponding OGs at multiple levels
                // transcript2ogs = itp.inferTranscriptOGsEggnog(plaza_db_connection, simsearch_data, chosen_scope);
                // Choose the largest acceptable OG and assign it as GF.
                // transcript2gf = itp.inferTranscriptGenefamiliesEggnog(plaza_db_connection, transcript2ogs);
                transcript2gf = itp.fetchTranscriptGenefamiliesEggnog(trapid_db_connection, trapid_experiment_id);
                // plaza_db_connection.close();
                trapid_db_connection.close();

                // Make reverse mapping of: OGs/transcript, GF/transcript
                // plaza_db_connection			= itp.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                System.out.println("Reverse mapping");
                // This mapping is needed for functional annotation with OGs
                // ogs2transcripts = itp.reverseMappingOgsEggnog(transcript2ogs);
                // This mapping is used in other steps as well (i.e. meta-annotation)
                gf2transcripts = itp.reverseMapping(transcript2gf);
                long t32 = System.currentTimeMillis();
                timing("GF inferring", t31, t32);
                // plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 5: perform putative frameshift detection and store best frame.
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t51 = System.currentTimeMillis();
                itp.performPutativeFrameDetection(trapid_db_connection, trapid_experiment_id, simsearch_data);
                long t52 = System.currentTimeMillis();
                timing("PutativeFrameDetection", t51, t52);
                itp.update_log(trapid_db_connection, trapid_experiment_id, "frameshift_detection", "", "3");
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 6: get longest ORFs in preferred frame.
                // TODO: update this procedure to work with multiple translation tables
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t61 = System.currentTimeMillis();
                if (seq_type.toString().equalsIgnoreCase("cds")) {
                    itp.translateCDSsequences(
                        trapid_db_connection,
                        trapid_experiment_id,
                        transl_tables_file,
                        transl_table
                    );
                } else {
                    itp.performInitialORFPrediction(
                        trapid_db_connection,
                        trapid_experiment_id,
                        transl_tables_file,
                        transl_table
                    );
                }
                long t62 = System.currentTimeMillis();
                timing("ORF prediction", t61, t62);
                itp.update_log(trapid_db_connection, trapid_experiment_id, "orf_prediction", "", "3");
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 7: perform check on lengths of transcripts, compared to gene family CDS length, store the results.
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t71 = System.currentTimeMillis();
                itp.performMetaAnnotationPrediction(
                    plaza_db_connection,
                    trapid_db_connection,
                    plaza_database_name,
                    trapid_experiment_id,
                    transcript2gf,
                    gf2transcripts,
                    gf_type
                );
                long t72 = System.currentTimeMillis();
                timing("Meta annotation", t71, t72);
                itp.update_log(trapid_db_connection, trapid_experiment_id, "meta_annotation", "", "3");
            } else {
                // Step 1: creating 2 different database connections. One to the reference database (plaza database),
                // and another one to the TRAPID database.
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t11 = System.currentTimeMillis();
                //primary step: remove all gene family information and functional information in the TRAPID database with regards
                //to the current experiment.
                itp.clearContent(trapid_db_connection, trapid_experiment_id);
                long t12 = System.currentTimeMillis();
                timing("Clearing databases", t11, t12);
                itp.update_log(trapid_db_connection, trapid_experiment_id, "data_normalization", "", "3");
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 2: parse the similarity search output file, and store some information in the TRAPID db:
                // transcript_id -> ({hit_gene,bitscore,query_start,query_stop,perc_identity,aln_length,log_e_val});
                long t21 = System.currentTimeMillis();
                Map<String, List<String[]>> simsearch_data = itp.parseSimilarityOutputFile(
                    similarity_search_file,
                    NUM_BLAST_HITS_CACHE
                );
                long t22 = System.currentTimeMillis();
                timing("Parsing similarity search file", t21, t22);

                // Step 2b: store the similarity search information in the database.
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t2b1 = System.currentTimeMillis();
                itp.storeSimilarityData(trapid_db_connection, trapid_experiment_id, simsearch_data);
                long t2b2 = System.currentTimeMillis();
                timing("Storing similarities information", t2b1, t2b2);
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 2c: determine for the similarity search what the best hitting species are
                // species -> hitcount
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                Map<String, Integer> species_hit_count = itp.getSpeciesHitCount(plaza_db_connection, simsearch_data);
                itp.storeBestSpeciesHitData(trapid_db_connection, trapid_experiment_id, species_hit_count);
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 3: create the transcript to gene family mapping, with extra info kept for later
                // This is the actual homology assignment done for the transcripts.
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t31 = System.currentTimeMillis();
                Map<String, GeneFamilyAssignment> transcript2gf = null;
                Map<String, List<String>> gf2transcripts = null; // Reverse mapping

                if (gf_type == GF_TYPE.HOM) {
                    String gf_prefix = itp.getGfPrefix(trapid_db_connection, plaza_database_name);
                    transcript2gf =
                        itp.inferTranscriptGenefamiliesHom(
                            plaza_db_connection,
                            simsearch_data,
                            num_top_hits,
                            gf_prefix
                        );
                } else if (gf_type == GF_TYPE.IORTHO) {
                    //no num_top_hits, as this should be one!
                    transcript2gf =
                        itp.inferTranscriptGenefamiliesIntegrativeOrthology(plaza_db_connection, simsearch_data);
                }
                plaza_db_connection.close();
                trapid_db_connection.close();

                //store the results of the gene family mapping in the database
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                itp.storeGeneFamilyAssignments(trapid_db_connection, trapid_experiment_id, transcript2gf, gf_type);
                itp.update_log(trapid_db_connection, trapid_experiment_id, "infer_genefamilies", "", "3");
                plaza_db_connection.close();
                trapid_db_connection.close();

                //make reverse mapping of gene families to transcripts, in order to reduce computing time
                //storage shouldn't be too much problem
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                gf2transcripts = itp.reverseMapping(transcript2gf);
                long t32 = System.currentTimeMillis();
                timing("GF inferring", t31, t32);
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 4: perform transfer of functional annotation from the gene families to the transcripts.
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t41 = System.currentTimeMillis();
                System.out.println("Performing GO functional transfer: " + func_annot);
                Map<String, Set<String>> transcript_go = null;
                switch (func_annot) {
                    case GF:
                        transcript_go =
                            itp.assignGoTranscripts_GF(
                                plaza_db_connection,
                                transcript2gf,
                                gf2transcripts,
                                gf_type,
                                gf_min_rep
                            );
                        break;
                    case BESTHIT:
                        transcript_go = itp.assignGoTranscripts_BESTHIT(plaza_db_connection, simsearch_data);
                        break;
                    case GF_BESTHIT:
                        transcript_go =
                            itp.assignGoTranscripts_GF_BESTHIT(
                                plaza_db_connection,
                                transcript2gf,
                                gf2transcripts,
                                gf_type,
                                simsearch_data,
                                gf_min_rep
                            );
                        break;
                    default:
                        System.err.println("Illegal func annot indicator : " + func_annot);
                        System.exit(1);
                }
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 4.a.: also add GO terms derived from Infernal/RFAM to `transcript_go`
                // Get name of the file containing the RFAM GO data
                String rfam_go_file = tmp_exp_dir + "rfam_go_data.tsv";
                // Update `transcript_go`
                transcript_go = itp.addTranscriptRfamGoData(transcript_go, rfam_go_file);

                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                Map<String, Map<String, Integer>> transcript_go_hidden = itp.hideGoTerms(
                    plaza_db_connection,
                    transcript_go
                );
                itp.storeGoTranscripts(trapid_db_connection, trapid_experiment_id, transcript_go_hidden);
                plaza_db_connection.close();
                trapid_db_connection.close();

                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                System.out.println("Performing InterPro functional transfer : " + func_annot);
                Map<String, Set<String>> transcript_interpro = null;
                switch (func_annot) {
                    case GF:
                        transcript_interpro =
                            itp.assignProteindomainTranscripts_GF(
                                plaza_db_connection,
                                transcript2gf,
                                gf2transcripts,
                                gf_type,
                                gf_min_rep
                            );
                        break;
                    case BESTHIT:
                        transcript_interpro =
                            itp.assignProteindomainTranscripts_BESTHIT(plaza_db_connection, simsearch_data);
                        break;
                    case GF_BESTHIT:
                        transcript_interpro =
                            itp.assignProteindomainTranscripts_GF_BESTHIT(
                                plaza_db_connection,
                                transcript2gf,
                                gf2transcripts,
                                gf_type,
                                simsearch_data,
                                gf_min_rep
                            );
                        break;
                    default:
                        System.err.println("Illegal func annot indicator : " + func_annot);
                        System.exit(1);
                }
                plaza_db_connection.close();
                trapid_db_connection.close();

                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                itp.storeInterproTranscripts(trapid_db_connection, trapid_experiment_id, transcript_interpro);
                long t42 = System.currentTimeMillis();
                timing("Functional transfer", t41, t42);
                itp.update_log(trapid_db_connection, trapid_experiment_id, "infer_functional_annotation", "", "3");
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 5: perform putative frameshift detection and store best frame.
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t51 = System.currentTimeMillis();
                itp.performPutativeFrameDetection(trapid_db_connection, trapid_experiment_id, simsearch_data);
                long t52 = System.currentTimeMillis();
                timing("PutativeFrameDetection", t51, t52);
                itp.update_log(trapid_db_connection, trapid_experiment_id, "frameshift_detection", "", "3");
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 6: get longest ORFs in preferred frame.
                // TODO: update this procedure to work with multiple translation tables
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t61 = System.currentTimeMillis();
                if (seq_type.toString().equalsIgnoreCase("cds")) {
                    itp.translateCDSsequences(
                        trapid_db_connection,
                        trapid_experiment_id,
                        transl_tables_file,
                        transl_table
                    );
                } else {
                    itp.performInitialORFPrediction(
                        trapid_db_connection,
                        trapid_experiment_id,
                        transl_tables_file,
                        transl_table
                    );
                }
                long t62 = System.currentTimeMillis();
                timing("ORF prediction", t61, t62);
                itp.update_log(trapid_db_connection, trapid_experiment_id, "orf_prediction", "", "3");
                plaza_db_connection.close();
                trapid_db_connection.close();

                // Step 7: perform check on lengths of transcripts, compared to gene family CDS length, store the results.
                plaza_db_connection =
                    itp.createDbConnection(
                        plaza_database_server,
                        plaza_database_name,
                        plaza_database_login,
                        plaza_database_password
                    );
                trapid_db_connection =
                    itp.createDbConnection(trapid_server, trapid_name, trapid_login, trapid_password);
                long t71 = System.currentTimeMillis();
                itp.performMetaAnnotationPrediction(
                    plaza_db_connection,
                    trapid_db_connection,
                    plaza_database_name,
                    trapid_experiment_id,
                    transcript2gf,
                    gf2transcripts,
                    gf_type
                );
                long t72 = System.currentTimeMillis();
                timing("Meta annotation", t71, t72);
                itp.update_log(trapid_db_connection, trapid_experiment_id, "meta_annotation", "", "3");
            } // End PLAZA processing

            // Step 8: set status of experiment to finished
            itp.setExperimentStatus(trapid_db_connection, trapid_experiment_id, "finished");

            // Final step: close the database connections, to both the trapid database and the PLAZA database
            plaza_db_connection.close();
            trapid_db_connection.close();
        } catch (Exception exc) {
            try {
                itp.setExperimentStatus(trapid_db_connection, trapid_experiment_id, "error");
            } catch (Exception exc2) {}
            exc.printStackTrace();
            System.exit(1);
        }
    }

    /*
     * Update logging system
     */
    public void update_log(Connection trapid_connection, String exp_id, String action, String param, String depth)
        throws Exception {
        String sql =
            "INSERT INTO `experiment_log`(`experiment_id`,`date`,`action`,`parameters`,`depth`) VALUES ('" +
            exp_id +
            "',NOW(),'" +
            action +
            "','" +
            param +
            "','" +
            depth +
            "')";
        Statement stmt = trapid_connection.createStatement();
        stmt.execute(sql);
        stmt.close();
    }

    public static void timing(String msg, long t1, long t2) {
        timing(msg, t1, t2, 1);
    }

    public static void timing(String msg, long t1, long t2, int lvl) {
        long t = (t2 - t1);
        for (int i = 1; i < lvl; i++) {
            System.out.print("\t");
        }
        System.out.println(msg + "\t" + t + "ms");
    }

    public static void timingNano(String msg, long t, int lvl) {
        long t_final = t / 1000000;
        for (int i = 1; i < lvl; i++) {
            System.out.print("\t");
        }
        System.out.println(msg + "\t" + t_final + "ms");
    }

    public void printRapsearchData(Map<String, List<String[]>> data) {
        for (String transcript_id : data.keySet()) {
            System.out.println(transcript_id + "\t" + data.get(transcript_id).size());
        }
    }

    public void printGfMapping(Map<String, GeneFamilyAssignment> data, GF_TYPE gf_type) {
        if (gf_type == GF_TYPE.HOM) {
            for (String transcript_id : data.keySet()) {
                //if(transcript_id.equals("AT4G21750") || transcript_id.equals("AT4G04890"))
                System.out.println(
                    transcript_id + "\t" + data.get(transcript_id).gf_id + "\t" + data.get(transcript_id).gf_size
                );
            }
        } else if (gf_type == GF_TYPE.IORTHO) {
            for (String transcript_id : data.keySet()) {
                //if(transcript_id.equals("AT4G21750") || transcript_id.equals("AT4G04890"))
                System.out.println(
                    transcript_id + "\t" + data.get(transcript_id).gf_id + "\t" + data.get(transcript_id).gf_size
                );
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
    public void setExperimentStatus(Connection db_connection, String trapid_experiment, String status)
        throws Exception {
        String sql =
            "UPDATE `experiments` SET `process_state` = '" +
            status +
            "' WHERE `experiment_id`='" +
            trapid_experiment +
            "' ";
        Statement stmt = db_connection.createStatement();
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
    public void clearContent(Connection db_connection, String trapid_experiment) throws Exception {
        String sql1 =
            "UPDATE `transcripts` SET `gf_id`= NULL,`orf_sequence`=NULL,`detected_frame`='0'," +
            " `detected_strand`='+',`full_frame_info`=NULL,`putative_frameshift`='0', " +
            " `is_frame_corrected`='0', `orf_start`=NULL,`orf_stop`=NULL," +
            " `orf_contains_start_codon`=NULL,`orf_contains_stop_codon`=NULL, `transl_table`=1, " +
            " `meta_annotation`='No Information',`meta_annotation_score`=NULL,`gf_id_score`=NULL WHERE `experiment_id`='" +
            trapid_experiment +
            "' ";
        String sql2 = "DELETE FROM `gene_families` WHERE `experiment_id`='" + trapid_experiment + "' ";
        // TRAPID db structure update for version 2: all the annotations are in one table, `transcripts_annotation`
        String sql3 = "DELETE FROM `transcripts_annotation` WHERE `experiment_id`='" + trapid_experiment + "' ";
        String sql4 = "DELETE FROM `similarities` WHERE `experiment_id`='" + trapid_experiment + "' ";
        String sql5 = "DELETE FROM `experiment_stats` WHERE `experiment_id`='" + trapid_experiment + "' ";
        String sql6 = "DELETE FROM `completeness_results` WHERE `experiment_id`='" + trapid_experiment + "' ";
        String[] sql_queries = { sql1, sql2, sql3, sql4, sql5, sql6 };
        for (String sql : sql_queries) {
            Statement stmt = db_connection.createStatement();
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
    public void storeSimilarityData(
        Connection trapid_connection,
        String trapid_experiment,
        Map<String, List<String[]>> simsearch_data
    ) throws Exception {
        String insert_query =
            "INSERT INTO `similarities` (`experiment_id`,`transcript_id`,`similarity_data`) VALUES ('" +
            trapid_experiment +
            "',?, COMPRESS(?))";
        PreparedStatement stmt = trapid_connection.prepareStatement(insert_query);

        for (String transcript_id : simsearch_data.keySet()) {
            StringBuffer buff = new StringBuffer();
            //transcript_id -> ({hit_gene,bitscore,query_start,query_stop,perc_identity,aln_length,log_e_val})
            for (String[] simsearch_info : simsearch_data.get(transcript_id)) {
                // No need for the transformation anymore since DIAMOND returns raw e-values
                // double e_val	= Math.pow(10,Double.parseDouble(simsearch_info[6]));
                double e_val = Double.parseDouble(simsearch_info[6]);
                String t =
                    simsearch_info[0] +
                    "," +
                    e_val +
                    "," +
                    simsearch_info[1] +
                    "," +
                    simsearch_info[5] +
                    "," +
                    simsearch_info[4];
                buff.append(t + ";");
            }
            String sim_string = buff.toString();
            sim_string = sim_string.substring(0, sim_string.length() - 1); //remove trailing ';'
            stmt.setString(1, transcript_id);
            stmt.setString(2, sim_string);
            stmt.execute();
        }
        stmt.close();
    }

    public void storeBestSpeciesHitData(
        Connection trapid_connection,
        String trapid_experiment,
        Map<String, Integer> species_hit_counts
    ) throws Exception {
        SortedMap<String, Integer> tmp = new TreeMap<String, Integer>();
        tmp.putAll(species_hit_counts);
        StringBuffer res = new StringBuffer();
        for (String species : tmp.keySet()) {
            int hit_count = tmp.get(species);
            res.append(";" + species + "=" + hit_count);
        }
        String t = "";
        if (res.length() > 0) {
            t = res.substring(1);
        }
        String update_query =
            "UPDATE `experiments` SET `hit_results` = '" + t + "' WHERE `experiment_id`='" + trapid_experiment + "' ";
        Statement stmt = trapid_connection.createStatement();
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
    public void performPutativeFrameDetection(
        Connection db_connection,
        String experiment_id,
        Map<String, List<String[]>> simsearch_data
    ) throws Exception {
        Map<String, Integer> lengths = new HashMap<String, Integer>();
        //boolean frameshift = false; //flag to check if a frameshift is expected

        String query_transcript_lengths =
            "SELECT `transcript_id`, LENGTH(UNCOMPRESS(`transcript_sequence`))  FROM `transcripts` WHERE `experiment_id` = ?";
        PreparedStatement stmt_transcript_lengths = db_connection.prepareStatement(query_transcript_lengths);
        stmt_transcript_lengths.setString(1, experiment_id);
        ResultSet set = stmt_transcript_lengths.executeQuery();
        while (set.next()) {
            lengths.put(set.getString(1), set.getInt(2));
        }
        set.close();
        stmt_transcript_lengths.close();

        String update_query =
            "UPDATE `transcripts` SET `detected_frame`= ? , `detected_strand` = ? , `full_frame_info` = ?, `putative_frameshift` = ?  WHERE `experiment_id`='" +
            experiment_id +
            "' AND `transcript_id` = ? ";
        PreparedStatement stmt_update_frames = db_connection.prepareStatement(update_query);

        for (String gene : simsearch_data.keySet()) {
            String topHit = simsearch_data.get(gene).get(0)[0];
            Map<String, Integer> count = new HashMap<String, Integer>();
            for (int i = 0; i < simsearch_data.get(gene).size(); i++) { //includes top hit :-)
                String currentHit = simsearch_data.get(gene).get(i)[0];
                //same hit as best hit. As such, we expect them to be in the same reading frame
                //if not, this putatively indicates a frameshift, which will be indicated in the database as such
                if (topHit.equals(currentHit)) {
                    int start = Integer.parseInt(simsearch_data.get(gene).get(i)[2]);
                    int stop = Integer.parseInt(simsearch_data.get(gene).get(i)[3]);
                    int frame = 0;
                    char strand = ' ';
                    if (start < stop) {
                        //frame = Math.abs(start % 3); //+1;
                        frame = Math.abs((start - 1) % 3) + 1;
                        strand = '+';
                    } else {
                        int currentLength = lengths.get(gene);
                        int newStart = (currentLength + 1) - start;
                        //frame = Math.abs(newStart % 3);//+1;
                        frame = Math.abs((newStart - 1) % 3) + 1;
                        strand = '-';
                    }
                    if (count.containsKey("" + strand + frame)) {
                        int currentCount = count.get("" + strand + frame);
                        currentCount++;
                        count.put("" + strand + frame, currentCount);
                    } else {
                        count.put("" + strand + frame, 1);
                    }
                }
            }

            //FIND BEST FRAME in case of only one hit it will be the first, which should be fine as well (as they are ordered like the rapsearch output)
            String bestFrame = "";
            int bestCount = -1;
            for (String frame : count.keySet()) {
                if (count.get(frame) > bestCount) {
                    bestFrame = frame;
                    bestCount = count.get(frame);
                }
            }

            String bestStrand = bestFrame.substring(0, 1);
            String bestFrameNumber = bestFrame.substring(1, 2);
            int frameshifted = 0;
            if (count.keySet().size() > 1) {
                frameshifted = 1;
            }
            String full_frame_info = "hit=\"" + topHit + "\"";
            if (frameshifted > 0) {
                String temp = "";
                for (String frame : count.keySet()) {
                    if (!frame.equals(bestFrame)) {
                        if (temp.equals("")) {
                            temp = frame;
                        } else {
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

            try {
                stmt_update_frames.execute();
            } catch (Exception exc) {
                System.err.println("Problem updating transcript with correct frame information.");
                System.err.println("Transcript : " + gene);
                System.err.println("Raw frame : " + bestFrame);
                System.err.println("Best Frame : " + bestFrameNumber);
                System.err.println("Best Strand : " + bestStrand);
                System.err.println("Full frame info : " + full_frame_info);
                System.err.println("Putative frameshift : " + frameshifted);
                throw exc;
            }
        }
        stmt_update_frames.close();
    }

    /**
     * Funtion to perform intitial ORF prediction based on detected frame
     *
     *
     */
    public void performInitialORFPrediction(
        Connection db_connection,
        String experiment_id,
        String transl_tables_file,
        String transl_table_idx
    ) throws Exception {
        ORFFinder orf_finder = new ORFFinder(transl_tables_file, transl_table_idx);

        //First get all genes + transcripts in the experiment
        Hashtable<String, String> geneSequences = new Hashtable<String, String>();
        Hashtable<String, Character> geneStrand = new Hashtable<String, Character>();
        Hashtable<String, Integer> geneFrame = new Hashtable<String, Integer>();

        // Do not infer ORF sequences for transcripts flagged as RNA transcripts and with no similarity search hit
        // (i.e. `is_rna_gene` is 1 `full_frame_info` is null).
        String query_transcripts =
            "SELECT `transcript_id`, UNCOMPRESS(`transcript_sequence`), `detected_strand`, `detected_frame`  FROM `transcripts` WHERE `experiment_id` = " +
            experiment_id +
            " AND NOT (`is_rna_gene` = 1 AND `full_frame_info` IS NULL)";
        Statement stmt = db_connection.createStatement();
        ResultSet set = stmt.executeQuery(query_transcripts);
        while (set.next()) {
            geneSequences.put(set.getString(1), set.getString(2));
            geneStrand.put(set.getString(1), set.getString(3).charAt(0));
            geneFrame.put(set.getString(1), set.getInt(4));
        }
        set.close();
        stmt.close();
        String update_transcripts =
            "UPDATE `transcripts` SET `orf_sequence`= COMPRESS(?) , `orf_start` = ? , `orf_stop` = ?, `orf_contains_start_codon` = ?, `orf_contains_stop_codon` = ?, `transl_table` = " +
            transl_table_idx +
            "  WHERE `experiment_id`='" +
            experiment_id +
            "' AND `transcript_id` = ? ";
        PreparedStatement stmt_update_transcripts = db_connection.prepareStatement(update_transcripts);
        String update_frame_info =
            "UPDATE `transcripts` SET `detected_frame`=? , `detected_strand` = ? WHERE `experiment_id`='" +
            experiment_id +
            "' AND `transcript_id` = ? ";
        PreparedStatement stmt_update_frame = db_connection.prepareStatement(update_frame_info);
        for (String gene : geneSequences.keySet()) {
            String sequence = geneSequences.get(gene);
            char strand = geneStrand.get(gene);
            int frame = geneFrame.get(gene);
            Map<String, String> res = orf_finder.findLongestORF(sequence, strand, frame);
            //default update of ORF information
            stmt_update_transcripts.setString(1, res.get("ORF"));
            stmt_update_transcripts.setString(2, res.get("start"));
            stmt_update_transcripts.setString(3, res.get("stop"));
            stmt_update_transcripts.setString(4, Boolean.parseBoolean(res.get("hasStartCodon")) ? "1" : "0");
            stmt_update_transcripts.setString(5, Boolean.parseBoolean(res.get("hasStopCodon")) ? "1" : "0");
            stmt_update_transcripts.setString(6, gene);
            stmt_update_transcripts.execute();
            //if new frame information is present in result, do second update as well.
            if (res.containsKey("newFrame")) {
                stmt_update_frame.setString(1, res.get("newFrame"));
                stmt_update_frame.setString(2, res.get("newStrand"));
                stmt_update_frame.setString(3, gene);
                stmt_update_frame.execute();
            }
        }
        stmt_update_transcripts.close();
        stmt_update_frame.close();
        System.out.println(update_transcripts.toString());
    }

    /**
     * Function based on `performInitialORFPrediction()` that translates all sequence in '+1' frame.
     */
    public void translateCDSsequences(
        Connection db_connection,
        String experiment_id,
        String transl_tables_file,
        String transl_table_idx
    ) throws Exception {
        ORFFinder orf_finder = new ORFFinder(transl_tables_file, transl_table_idx);

        //First get all genes + transcripts in the experiment
        Hashtable<String, String> geneSequences = new Hashtable<String, String>();

        // Do not translate transcripts flagged as RNA transcripts and with no similarity search hit
        // (i.e. `is_rna_gene` is 1 `full_frame_info` is null).
        String query_transcripts =
            "SELECT `transcript_id`, UNCOMPRESS(`transcript_sequence`), `detected_strand`, `detected_frame`  FROM `transcripts` WHERE `experiment_id` = " +
            experiment_id +
            " AND NOT (`is_rna_gene` = 1 AND `full_frame_info` IS NULL)";
        Statement stmt = db_connection.createStatement();
        ResultSet set = stmt.executeQuery(query_transcripts);
        while (set.next()) {
            geneSequences.put(set.getString(1), set.getString(2));
        }
        set.close();
        stmt.close();
        String update_transcripts =
            "UPDATE `transcripts` SET `orf_sequence`= COMPRESS(?) , `orf_start` = ? , `orf_stop` = ?, `orf_contains_start_codon` = ?, `orf_contains_stop_codon` = ?, `transl_table` = " +
            transl_table_idx +
            "  WHERE `experiment_id`='" +
            experiment_id +
            "' AND `transcript_id` = ? ";
        PreparedStatement stmt_update_transcripts = db_connection.prepareStatement(update_transcripts);
        String update_frame_info =
            "UPDATE `transcripts` SET `detected_frame`=? , `detected_strand` = ? WHERE `experiment_id`='" +
            experiment_id +
            "' AND `transcript_id` = ? ";
        PreparedStatement stmt_update_frame = db_connection.prepareStatement(update_frame_info);
        for (String gene : geneSequences.keySet()) {
            String sequence = geneSequences.get(gene);
            char strand = '+';
            int frame = 1;
            Map<String, String> res = orf_finder.findLongestORF(sequence, strand, frame);
            //default update of ORF information
            stmt_update_transcripts.setString(1, res.get("ORF"));
            stmt_update_transcripts.setString(2, res.get("start"));
            stmt_update_transcripts.setString(3, res.get("stop"));
            stmt_update_transcripts.setString(4, Boolean.parseBoolean(res.get("hasStartCodon")) ? "1" : "0");
            stmt_update_transcripts.setString(5, Boolean.parseBoolean(res.get("hasStopCodon")) ? "1" : "0");
            stmt_update_transcripts.setString(6, gene);
            stmt_update_transcripts.execute();
            // We ignore frame/strand detected before (using homology information)
            // Shouldn't we just not retrieve it at all?
            stmt_update_frame.setString(1, String.valueOf(frame));
            stmt_update_frame.setString(2, Character.toString(strand));
            stmt_update_frame.setString(3, gene);
            stmt_update_frame.execute();
        }
        stmt_update_transcripts.close();
        stmt_update_frame.close();
        System.out.println(update_transcripts.toString());
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
    public void performMetaAnnotationPrediction(
        Connection plaza_connection,
        Connection trapid_connection,
        String plaza_database_name,
        String trapid_exp_id,
        Map<String, GeneFamilyAssignment> transcript2gf,
        Map<String, List<String>> gf2transcripts,
        GF_TYPE gf_type
    ) throws Exception {
        System.out.println("Performing meta annotation analysis");
        // Caching, because otherwise it takes far too long.
        long tt11 = System.currentTimeMillis();
        Map<String, Integer> transcript_orf_lengths = this.getTranscriptOrfLengths(trapid_connection, trapid_exp_id);
        Map<String, boolean[]> transcript_startstop = this.getTranscriptStartStop(trapid_connection, trapid_exp_id);
        long tt12 = System.currentTimeMillis();
        timing("Caching transcript lengths and start/stop information", tt11, tt12, 2);

        long tt21 = System.currentTimeMillis();
        Map<String, Integer> gene_cds_lengths = this.getGeneCdsLengths(plaza_connection);
        long tt22 = System.currentTimeMillis();
        timing("Caching cds lengths", tt21, tt22, 2);

        long tt31 = System.currentTimeMillis();
        Map<String, Set<String>> hom_family_content = null;
        if (gf_type == GF_TYPE.HOM) {
            if (plaza_database_name.contains("plaza")) {
                String gf_prefix = this.getGfPrefix(trapid_connection, plaza_database_name);
                hom_family_content = this.getFamilyContent(plaza_connection, gf_prefix);
            } else {
                // Remove `None` for later
                if (gf2transcripts.keySet().contains("None")) {
                    gf2transcripts.remove("None");
                }
                System.out.println("Fetch EggNOG family content");
                hom_family_content = getFamilyContentEggnog(plaza_connection, gf2transcripts.keySet());
            }
        }
        long tt32 = System.currentTimeMillis();
        timing("Caching gene family content", tt31, tt32, 2);

        Map<String, String[]> transcript2meta = new HashMap<String, String[]>();

        long tt41 = System.currentTimeMillis();
        //now, perform this meta annotation prediction for each transcript, per gene family.
        //this is why we iterate over the gene families.
        for (String gf_id : gf2transcripts.keySet()) {
            //we take the first transcript as reference
            GeneFamilyAssignment gas = transcript2gf.get(gf2transcripts.get(gf_id).get(0));
            Set<String> genes = null;
            //dependent on type of gene family, get genes from DB or from storage
            if (gf_type == GF_TYPE.HOM) {
                genes = hom_family_content.get(gf_id);
            } else if (gf_type == GF_TYPE.IORTHO) {
                genes = gas.gf_content;
            }

            if (genes.size() < META_MIN_GF_SIZE) {
                for (String transcript_id : gf2transcripts.keySet()) {
                    String[] meta = { "No Information", "" };
                    transcript2meta.put(transcript_id, meta);
                }
            } else {
                //ok, now retrieve the length of the CDS sequences for all genes in this gene family.
                List<Integer> cds_lengths = new ArrayList<Integer>();
                for (String gene_id : genes) {
                    if (gene_cds_lengths.containsKey(gene_id)) {
                        cds_lengths.add(gene_cds_lengths.get(gene_id));
                    }
                }

                //now, get statistical analysis of the gene family content
                LengthAnalysis la = this.getLengthAnalysis(gf_id, cds_lengths, true);

                //retrieve orf lengths of the transcripts
                Map<String, Integer> transcript2orflength = new HashMap<String, Integer>();
                for (String transcript_id : gf2transcripts.get(gf_id)) {
                    int length = 0;
                    if (transcript_orf_lengths.containsKey(transcript_id)) {
                        length = transcript_orf_lengths.get(transcript_id);
                    }
                    transcript2orflength.put(transcript_id, length);
                }

                //now, compare each transcript ORF length to the gene family length analysis
                for (String transcript_id : transcript2orflength.keySet()) {
                    boolean[] codon_info = transcript_startstop.get(transcript_id);
                    int orf_length = transcript2orflength.get(transcript_id);
                    String[] meta_annot = this.getMetaAnnot(orf_length, la.average, la.std_deviation, codon_info);
                    transcript2meta.put(transcript_id, meta_annot);
                }
                transcript2orflength.clear();
                cds_lengths.clear();
            }
        }
        long tt42 = System.currentTimeMillis();
        timing("Computing meta annotations", tt41, tt42, 2);

        long tt51 = System.currentTimeMillis();
        String insert_meta_annotation =
            "UPDATE `transcripts` SET `meta_annotation`=?, `meta_annotation_score`=? WHERE `experiment_id`='" +
            trapid_exp_id +
            "' AND `transcript_id` = ? ";
        PreparedStatement stmt_meta_annot = trapid_connection.prepareStatement(insert_meta_annotation);
        for (String transcript_id : transcript2meta.keySet()) {
            String[] meta_annot = transcript2meta.get(transcript_id);
            stmt_meta_annot.setString(1, meta_annot[0]);
            stmt_meta_annot.setString(2, meta_annot[1]);
            stmt_meta_annot.setString(3, transcript_id);
            stmt_meta_annot.execute();
        }
        stmt_meta_annot.close();
        long tt52 = System.currentTimeMillis();
        timing("Storing meta annotations in database", tt51, tt52, 2);

        long tt61 = System.currentTimeMillis();
        transcript2meta.clear();
        transcript_orf_lengths.clear();
        gene_cds_lengths.clear();
        transcript_startstop.clear();
        if (hom_family_content != null) {
            hom_family_content.clear();
        }
        long tt62 = System.currentTimeMillis();
        timing("Clearing temp storage", tt61, tt62, 2);
    }

    private String[] getMetaAnnot(int orf_length, int avg, int std_dev, boolean[] start_stop_codon) {
        String meta_annot = "No Information";
        String meta_annot_score = "";

        if (orf_length >= (avg - 2 * std_dev)) {
            meta_annot_score =
                "std_dev=" + std_dev + ";avg=" + avg + ";orf_length=" + orf_length + ";cutoff=" + (avg - 2 * std_dev);
            if (start_stop_codon[0] && start_stop_codon[1]) {
                meta_annot = "Full Length";
            } else {
                meta_annot = "Quasi Full Length";
            }
        } else {
            meta_annot = "Partial";
            meta_annot_score =
                "std_dev=" + std_dev + ";avg=" + avg + ";orf_length=" + orf_length + ";cutoff=" + (avg - 2 * std_dev);
        }
        String[] result = { meta_annot, meta_annot_score };
        return result;
    }

    /**
     * Computes some statistical information on the lengths of a set of sequences.
     * @param data Lengths of a set of sequences. List, because the sequences can be of equal length
     * @param remove_outliers If true, remove outliers (outside [avg-3*dev,avg+3*dev]) and compute results
     * @return Object containing results of statistical analysis.
     */
    public LengthAnalysis getLengthAnalysis(String gf_id, List<Integer> data, boolean remove_outliers) {
        //int min					= Integer.MAX_VALUE;
        //int max					= Integer.MIN_VALUE;
        long average_sum = 0;
        for (int d : data) {
            //if(d>max){max = d;}
            //if(d<min){min = d;}
            average_sum += d;
        }

        int average = (int) (average_sum / data.size());
        long std_dev_sum = 0;
        for (int d : data) {
            long temp = d - average;
            std_dev_sum += (temp * temp);
        }
        int std_dev = (int) (Math.sqrt(std_dev_sum / data.size()));

        if (remove_outliers) {
            //clearly some original genes may have been over-under predicted as well.
            //therefore we remove all those
            Collections.sort(data);

            List<Integer> new_data = new ArrayList<Integer>();
            int to_remove = (int) (Math.ceil(META_PERC_REMOVE * (double) (data.size())));
            for (int i = to_remove; i < (data.size() - to_remove); i++) {
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
        } else {
            /*
			if(std_dev==0){
				System.out.println("No outlier removal : "+gf_id+"\t"+data.toString());
			}*/
            LengthAnalysis la = new LengthAnalysis(average, std_dev);
            return la;
        }
    }

    public class LengthAnalysis {

        //	public int min				= 0;
        //	public int max				= 0;
        public int average = 0;
        public int std_deviation = 0;

        public LengthAnalysis(int avg, int std_dev) {
            this.average = avg;
            this.std_deviation = std_dev;
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
    /* Assigning GOs and InterPros to Transcripts 	*/
    /*--------------------------------------------------------------------------------------------*/
    /*--------------------------------------------------------------------------------------------*/

    /**
     * Assign GO terms to each transcript, based on the best similarity hit. Return hashmap with this assignment.
     * @param plaza_connection Connection to PLAZA database
     * @param simsearch_data Similarity search results
     * @return Mapping from transcript to GO terms
     * @throws Exception
     */
    private Map<String, Set<String>> assignGoTranscripts_BESTHIT(
        Connection plaza_connection,
        Map<String, List<String[]>> simsearch_data
    ) throws Exception {
        Map<String, Set<String>> transcript_go = new HashMap<String, Set<String>>();

        long t11 = System.currentTimeMillis();
        //load the GO parents table into memory to prevent unnecessary queries
        Map<String, Map<String, Set<String>>> go_graph_data = this.loadGOGraph(plaza_connection);
        Map<String, Set<String>> go_child2parents = go_graph_data.get("child2parents");
        Map<String, Set<String>> go_parent2children = go_graph_data.get("parent2children");
        long t12 = System.currentTimeMillis();
        timing("Loading GO graph", t11, t12, 2);

        long t21 = System.currentTimeMillis();
        Map<String, Set<String>> gene_go = this.loadGoData(plaza_connection);
        long t22 = System.currentTimeMillis();
        timing("Caching GO data", t21, t22, 2);

        long t41 = System.currentTimeMillis();

        for (String transcript_id : simsearch_data.keySet()) {
            if (simsearch_data.get(transcript_id).size() != 0) {
                String best_hit = simsearch_data.get(transcript_id).get(0)[0];
                //ok, now use the best hit to transfer the functional annotation
                if (gene_go.containsKey(best_hit)) {
                    Set<String> go_terms = new HashSet<String>();
                    //add go terms
                    for (String go : gene_go.get(best_hit)) {
                        go_terms.add(go);
                        if (go_child2parents.containsKey(go)) {
                            for (String go_parent : go_child2parents.get(go)) {
                                go_terms.add(go_parent);
                            }
                        }
                    }
                    //remove the 3 top GO terms (Biological Process, Cellular Component, Molecular Function).
                    if (go_terms.contains("GO:0003674")) {
                        go_terms.remove("GO:0003674");
                    }
                    if (go_terms.contains("GO:0008150")) {
                        go_terms.remove("GO:0008150");
                    }
                    if (go_terms.contains("GO:0005575")) {
                        go_terms.remove("GO:0005575");
                    }

                    if (go_terms.size() > 0) {
                        transcript_go.put(transcript_id, go_terms);
                    }
                }
            }
        }
        long t42 = System.currentTimeMillis();
        timing("Inferring functional annotation per transcript", t41, t42, 2);

        //clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        go_child2parents.clear();
        go_parent2children.clear();
        go_graph_data.clear();
        gene_go.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing local cache data structures", t61, t62, 2);

        return transcript_go;
    }

    /**
     * Assign GO terms to each transcript, based on the associated gene family (and the 50% rule).
     * @param plaza_connection Connection to PLAZA database
     * @param transcript2gf Mapping from transcripts to gene families
     * @param gf2transcripts Mapping from gene families to transcripts
     * @param gf_type Type of gene family to be used.
     * @param min_freq Minimum representation of protein domain in GF required to transfer it to transcripts
     * @return Mapping from transcript to GO terms
     * @throws Exception
     */
    private Map<String, Set<String>> assignGoTranscripts_GF(
        Connection plaza_connection,
        Map<String, GeneFamilyAssignment> transcript2gf,
        Map<String, List<String>> gf2transcripts,
        GF_TYPE gf_type,
        double min_freq
    ) throws Exception {
        Map<String, Set<String>> transcript_go = new HashMap<String, Set<String>>();

        long t11 = System.currentTimeMillis();
        //load the GO parents table into memory to prevent unnecessary queries
        Map<String, Map<String, Set<String>>> go_graph_data = this.loadGOGraph(plaza_connection);
        Map<String, Set<String>> go_child2parents = go_graph_data.get("child2parents");
        Map<String, Set<String>> go_parent2children = go_graph_data.get("parent2children");
        long t12 = System.currentTimeMillis();
        timing("Loading GO graph", t11, t12, 2);

        long t21 = System.currentTimeMillis();
        Map<String, Set<String>> gene_go = this.loadGoData(plaza_connection);
        long t22 = System.currentTimeMillis();
        timing("Caching GO data", t21, t22, 2);

        //necessary queries
        String query_hom_genes = "SELECT `gene_id` FROM `gf_data` WHERE `gf_id` = ? ";
        PreparedStatement stmt_hom_genes = plaza_connection.prepareStatement(query_hom_genes);

        long t41 = System.currentTimeMillis();
        for (String gf_id : gf2transcripts.keySet()) {
            //we take the first transcript as reference
            GeneFamilyAssignment gas = transcript2gf.get(gf2transcripts.get(gf_id).get(0));

            //PART 0: RETRIEVE PLAZA GENE CONTENT FOR THE GENE FAMILY
            //---------------------------------------------------------
            Set<String> genes = null;
            //dependent on type of gene family, get genes from DB or from storage
            if (gf_type == GF_TYPE.HOM) {
                genes = new HashSet<String>();
                stmt_hom_genes.setString(1, gf_id);
                ResultSet set = stmt_hom_genes.executeQuery();
                while (set.next()) {
                    genes.add(set.getString(1));
                }
                set.close();
            } else if (gf_type == GF_TYPE.IORTHO) {
                genes = gas.gf_content;
            }

            //PART 1: GO ANNOTATION
            //-------------------------------------------
            //mapping of GO terms to genes! Better than genes to GO, because we actually need
            //the GO terms. The genes are just for counting.
            Map<String, Set<String>> go_genes = new HashMap<String, Set<String>>();
            //okay, now retrieve the GO data for these genes.
            for (String gene_id : genes) {
                if (gene_go.containsKey(gene_id)) {
                    Set<String> assoc_go = gene_go.get(gene_id);
                    for (String go : assoc_go) {
                        if (!go_genes.containsKey(go)) {
                            go_genes.put(go, new HashSet<String>());
                        }
                        go_genes.get(go).add(gene_id);
                        //add parental gos as well
                        if (go_child2parents.containsKey(go)) {
                            for (String go_parent : go_child2parents.get(go)) {
                                if (!go_genes.containsKey(go_parent)) {
                                    go_genes.put(go_parent, new HashSet<String>());
                                }
                                go_genes.get(go_parent).add(gene_id);
                            }
                        }
                    }
                }
            }
            //remove the 3 top GO terms (Biological Process, Cellular Component, Molecular Function).
            if (go_genes.containsKey("GO:0003674")) {
                go_genes.remove("GO:0003674");
            }
            if (go_genes.containsKey("GO:0008150")) {
                go_genes.remove("GO:0008150");
            }
            if (go_genes.containsKey("GO:0005575")) {
                go_genes.remove("GO:0005575");
            }

            //now, iterate over all the GO identifiers, and select those who are present in at least
            //50% of the genes associated with this gene family
            Set<String> selected_gos = new HashSet<String>();
            double gene_gf_count = gas.gf_size;
            for (String go_id : go_genes.keySet()) {
                double gene_go_count = go_genes.get(go_id).size();
                if (gene_go_count / gene_gf_count >= min_freq) {
                    selected_gos.add(go_id);
                }
            }

            //now add these selected gos to each of the transcript family members of the gene family.
            for (String transcript_id : gf2transcripts.get(gf_id)) {
                transcript_go.put(transcript_id, new HashSet<String>(selected_gos));
            }

            //clear the temporary storage for this gene family.
            go_genes.clear();
        }
        long t42 = System.currentTimeMillis();
        timing("Inferring functional annotation per gene family", t41, t42, 2);

        //clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        go_child2parents.clear();
        go_parent2children.clear();
        go_graph_data.clear();
        gene_go.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing local cache data structures", t61, t62, 2);

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
     * @param min_freq Minimum representation of protein domain in GF required to transfer it to transcripts
     * @return Mapping from transcript to GO terms
     * @throws Exception
     */
    private Map<String, Set<String>> assignGoTranscripts_GF_BESTHIT(
        Connection plaza_connection,
        Map<String, GeneFamilyAssignment> transcript2gf,
        Map<String, List<String>> gf2transcripts,
        GF_TYPE gf_type,
        Map<String, List<String[]>> simsearch_data,
        double min_freq
    ) throws Exception {
        Map<String, Set<String>> transcript_go = new HashMap<String, Set<String>>();

        Map<String, Set<String>> transcript_go_besthit =
            this.assignGoTranscripts_BESTHIT(plaza_connection, simsearch_data);
        Map<String, Set<String>> transcript_go_gf =
            this.assignGoTranscripts_GF(plaza_connection, transcript2gf, gf2transcripts, gf_type, min_freq);

        transcript_go.putAll(transcript_go_besthit);
        for (String transcript : transcript_go_gf.keySet()) {
            if (!transcript_go.containsKey(transcript)) {
                transcript_go.put(transcript, transcript_go_gf.get(transcript));
            } else {
                transcript_go.get(transcript).addAll(transcript_go_gf.get(transcript));
            }
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
    private Map<String, Map<String, Integer>> hideGoTerms(
        Connection plaza_connection,
        Map<String, Set<String>> transcript_go
    ) throws Exception {
        Map<String, Map<String, Integer>> result = new HashMap<String, Map<String, Integer>>();
        Map<String, Map<String, Set<String>>> go_graph_data = this.loadGOGraph(plaza_connection);
        Map<String, Set<String>> go_parent2children = go_graph_data.get("parent2children");
        for (String transcript : transcript_go.keySet()) {
            Set<String> go_terms = transcript_go.get(transcript);
            Map<String, Integer> go_hidden = this.hide_go_terms(go_terms, go_parent2children);
            result.put(transcript, go_hidden);
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
    private Map<String, Set<String>> assignProteindomainTranscripts_BESTHIT(
        Connection plaza_connection,
        Map<String, List<String[]>> simsearch_data
    ) throws Exception {
        Map<String, Set<String>> transcript_interpro = new HashMap<String, Set<String>>();

        long t31 = System.currentTimeMillis();
        Map<String, Set<String>> gene_interpro = this.loadInterproData(plaza_connection);
        long t32 = System.currentTimeMillis();
        timing("Caching Interpro data", t31, t32, 2);

        long t41 = System.currentTimeMillis();
        for (String transcript_id : simsearch_data.keySet()) {
            if (simsearch_data.get(transcript_id).size() != 0) {
                String best_hit = simsearch_data.get(transcript_id).get(0)[0];
                //ok, now use the best hit to transfer the functional annotation

                if (gene_interpro.containsKey(best_hit)) {
                    Set<String> interpros = new HashSet<String>();
                    //add interpros
                    for (String interpro : gene_interpro.get(best_hit)) {
                        interpros.add(interpro);
                    }
                    if (interpros.size() > 0) {
                        transcript_interpro.put(transcript_id, interpros);
                    }
                }
            }
        }

        long t42 = System.currentTimeMillis();
        timing("Inferring functional annotation per transcript", t41, t42, 2);

        //clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        gene_interpro.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing local cache data structures", t61, t62, 2);

        return transcript_interpro;
    }

    /**
     * Assign protein domains to each transcript, based on the associated gene family (and the 50% rule).
     * @param plaza_connection Connection to PLAZA database
     * @param transcript2gf Mapping from transcripts to gene families
     * @param gf2transcripts Mapping from gene families to transcripts
     * @param gf_type Type of gene family to be used.
     * @param min_freq Minimum representation of protein domain in GF required to transfer it to transcripts
     * @return Mapping from transcript to protein domains
     * @throws Exception
     */
    private Map<String, Set<String>> assignProteindomainTranscripts_GF(
        Connection plaza_connection,
        Map<String, GeneFamilyAssignment> transcript2gf,
        Map<String, List<String>> gf2transcripts,
        GF_TYPE gf_type,
        double min_freq
    ) throws Exception {
        Map<String, Set<String>> transcript_interpro = new HashMap<String, Set<String>>();

        long t31 = System.currentTimeMillis();
        Map<String, Set<String>> gene_interpro = this.loadInterproData(plaza_connection);
        long t32 = System.currentTimeMillis();
        timing("Caching Interpro data", t31, t32, 2);

        //necessary queries
        String query_hom_genes = "SELECT `gene_id` FROM `gf_data` WHERE `gf_id` = ? ";
        PreparedStatement stmt_hom_genes = plaza_connection.prepareStatement(query_hom_genes);

        long t41 = System.currentTimeMillis();
        for (String gf_id : gf2transcripts.keySet()) {
            //we take the first transcript as reference
            GeneFamilyAssignment gas = transcript2gf.get(gf2transcripts.get(gf_id).get(0));

            //PART 0: RETRIEVE PLAZA GENE CONTENT FOR THE GENE FAMILY
            //---------------------------------------------------------
            Set<String> genes = null;
            //dependent on type of gene family, get genes from DB or from storage
            if (gf_type == GF_TYPE.HOM) {
                genes = new HashSet<String>();
                stmt_hom_genes.setString(1, gf_id);
                ResultSet set = stmt_hom_genes.executeQuery();
                while (set.next()) {
                    genes.add(set.getString(1));
                }
                set.close();
            } else if (gf_type == GF_TYPE.IORTHO) {
                genes = gas.gf_content;
            }

            //PART 2 : INTERPRO ANNOTATION
            //-------------------------------------------------------------
            //mapping of interpro domains to genes. The genes are just for counting
            //okay, now retrieve the GO data for these genes.
            Map<String, Set<String>> interpro_genes = new HashMap<String, Set<String>>();
            for (String gene_id : genes) {
                if (gene_interpro.containsKey(gene_id)) {
                    Set<String> assoc_interpro = gene_interpro.get(gene_id);
                    for (String ipr : assoc_interpro) {
                        if (!interpro_genes.containsKey(ipr)) {
                            interpro_genes.put(ipr, new HashSet<String>());
                        }
                        interpro_genes.get(ipr).add(gene_id);
                    }
                }
            }

            //now, iterate over all the Interpro identifiers, and select those who are present in at least
            //50% of the genes associated with this gene family
            double gene_gf_count = gas.gf_size;
            Set<String> selected_interpros = new HashSet<String>();
            for (String ipr_id : interpro_genes.keySet()) {
                double gene_ipr_count = interpro_genes.get(ipr_id).size();
                if (gene_ipr_count / gene_gf_count >= min_freq) {
                    selected_interpros.add(ipr_id);
                }
            }

            //now add these selected gos to each of the transcript family members of the gene family.
            for (String transcript_id : gf2transcripts.get(gf_id)) {
                transcript_interpro.put(transcript_id, new HashSet<String>(selected_interpros));
            }

            interpro_genes.clear();
        }
        long t42 = System.currentTimeMillis();
        timing("Inferring functional annotation per gene family", t41, t42, 2);

        //clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        gene_interpro.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing local cache data structures", t61, t62, 2);

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
     * @param min_freq Minimum representation of protein domain in GF required to transfer it to transcripts
     * @return Mapping from transcript to protein domains
     * @throws Exception
     */
    private Map<String, Set<String>> assignProteindomainTranscripts_GF_BESTHIT(
        Connection plaza_connection,
        Map<String, GeneFamilyAssignment> transcript2gf,
        Map<String, List<String>> gf2transcripts,
        GF_TYPE gf_type,
        Map<String, List<String[]>> simsearch_data,
        double min_freq
    ) throws Exception {
        Map<String, Set<String>> transcript_interpro = new HashMap<String, Set<String>>();

        Map<String, Set<String>> transcript_interpro_besthit =
            this.assignProteindomainTranscripts_BESTHIT(plaza_connection, simsearch_data);
        Map<String, Set<String>> transcript_interpro_gf =
            this.assignProteindomainTranscripts_GF(plaza_connection, transcript2gf, gf2transcripts, gf_type, min_freq);

        transcript_interpro.putAll(transcript_interpro_besthit);
        for (String transcript : transcript_interpro_gf.keySet()) {
            if (!transcript_interpro.containsKey(transcript)) {
                transcript_interpro.put(transcript, transcript_interpro_gf.get(transcript));
            } else {
                transcript_interpro.get(transcript).addAll(transcript_interpro_gf.get(transcript));
            }
        }
        return transcript_interpro;
    }

    /*--------------------------------------------------------------------------------------------*/
    /*--------------------------------------------------------------------------------------------*/
    /* Storing GO's and InterPros to Transcripts 	*/
    /*--------------------------------------------------------------------------------------------*/
    /*--------------------------------------------------------------------------------------------*/

    /**
     * Store all the transcript - GO associations, that were detected
     * @param trapid_connection Connection to trapid database
     * @param trapid_exp_id Trapid experiment id
     * @param transcript_interpro Mapping from transcripts to protein domains
     * @throws Exception
     */
    private void storeGoTranscripts(
        Connection trapid_connection,
        String trapid_exp_id,
        Map<String, Map<String, Integer>> transcript_go_hidden
    ) throws Exception {
        long t51 = System.currentTimeMillis();
        String insert_go_annot =
            "INSERT INTO `transcripts_annotation` (`experiment_id`, `type`, `transcript_id`, `name`, `is_hidden`) VALUES ('" +
            trapid_exp_id +
            "', 'go', ?, ?, ?) ";
        // TRAPID db strcture updated for version 2
        // String insert_go_annot				= "INSERT INTO `transcripts_go` (`experiment_id`,`transcript_id`,`go`,`is_hidden`) VALUES ('"+trapid_exp_id+"',?,?,?) ";
        PreparedStatement ins_go_annot = trapid_connection.prepareStatement(insert_go_annot);
        boolean prev_commit_state = trapid_connection.getAutoCommit();
        trapid_connection.setAutoCommit(false);
        Set<String> all_gos = new HashSet<String>();

        for (String transcript_id : transcript_go_hidden.keySet()) {
            for (String go_id : transcript_go_hidden.get(transcript_id).keySet()) {
                all_gos.add(go_id);
                String hidden_status = "" + transcript_go_hidden.get(transcript_id).get(go_id);
                ins_go_annot.setString(1, transcript_id);
                ins_go_annot.setString(2, go_id);
                ins_go_annot.setString(3, hidden_status);
                ins_go_annot.addBatch();
            }
            ins_go_annot.executeBatch();
            trapid_connection.commit();
            ins_go_annot.clearBatch();
        }

        // Store GO annotation stats in `experiment_stats`
        int n_go = all_gos.size();
        int n_trs = transcript_go_hidden.size();
        String query_trs_go_exp_stats =
            "INSERT INTO `experiment_stats` (`experiment_id`, `stat_type`, `stat_value`) VALUES('" +
            trapid_exp_id +
            "', 'trs_go', '" +
            String.valueOf(n_trs) +
            "')";
        String query_n_go_exp_stats =
            "INSERT INTO `experiment_stats` (`experiment_id`, `stat_type`, `stat_value`) VALUES('" +
            trapid_exp_id +
            "', 'n_go', '" +
            String.valueOf(n_go) +
            "')";
        Statement stmt_exp_stats = trapid_connection.createStatement();
        stmt_exp_stats.execute(query_trs_go_exp_stats);
        stmt_exp_stats.execute(query_n_go_exp_stats);
        trapid_connection.commit();

        trapid_connection.setAutoCommit(prev_commit_state);
        long t52 = System.currentTimeMillis();
        timing("Storing GO functional annotation in database per transcript", t51, t52, 2);
        //close all statements
        ins_go_annot.close();
        //clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        transcript_go_hidden.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing GO local cache data structures", t61, t62, 2);
    }

    /**
     * Store all the transcript - interpro associations, that were detected
     * @param trapid_connection Connection to trapid database
     * @param trapid_exp_id Trapid experiment id
     * @param transcript_interpro Mapping from transcripts to protein domains
     * @throws Exception
     */
    private void storeInterproTranscripts(
        Connection trapid_connection,
        String trapid_exp_id,
        Map<String, Set<String>> transcript_interpro
    ) throws Exception {
        long t51 = System.currentTimeMillis();
        String insert_ipr_annot =
            "INSERT INTO `transcripts_annotation` (`experiment_id`, `type`, `transcript_id`, `name`, `is_hidden`) VALUES ('" +
            trapid_exp_id +
            "', 'ipr', ?, ?, '0') ";
        // TRAPID db structure changed for version 2...
        // String insert_ipr_annot				= "INSERT INTO `transcripts_interpro` (`experiment_id`,`transcript_id`,`interpro`) VALUES ('"+trapid_exp_id+"', ? , ? )  ";
        PreparedStatement ins_ipr_annot = trapid_connection.prepareStatement(insert_ipr_annot);
        boolean prev_commit_state = trapid_connection.getAutoCommit();
        trapid_connection.setAutoCommit(false);
        Set<String> all_ipr = new HashSet<String>();

        for (String transcript_id : transcript_interpro.keySet()) {
            for (String ipr_id : transcript_interpro.get(transcript_id)) {
                all_ipr.add(ipr_id);
                ins_ipr_annot.setString(1, transcript_id);
                ins_ipr_annot.setString(2, ipr_id);
                ins_ipr_annot.addBatch();
            }
            ins_ipr_annot.executeBatch();
            trapid_connection.commit();
            ins_ipr_annot.clearBatch();
        }

        // Store ipr annotation stats in `experiment_stats`
        int n_ipr = all_ipr.size();
        int n_trs = transcript_interpro.size();
        String query_trs_ipr_exp_stats =
            "INSERT INTO `experiment_stats` (`experiment_id`, `stat_type`, `stat_value`) VALUES('" +
            trapid_exp_id +
            "', 'trs_ipr', '" +
            String.valueOf(n_trs) +
            "')";
        String query_n_ipr_exp_stats =
            "INSERT INTO `experiment_stats` (`experiment_id`, `stat_type`, `stat_value`) VALUES('" +
            trapid_exp_id +
            "', 'n_ipr', '" +
            String.valueOf(n_ipr) +
            "')";
        Statement stmt_exp_stats = trapid_connection.createStatement();
        stmt_exp_stats.execute(query_trs_ipr_exp_stats);
        stmt_exp_stats.execute(query_n_ipr_exp_stats);
        trapid_connection.commit();

        trapid_connection.setAutoCommit(prev_commit_state);
        long t52 = System.currentTimeMillis();
        timing("Storing InterPro functional annotation in database per transcript", t51, t52, 2);
        //close all statements
        ins_ipr_annot.close();
        //clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        transcript_interpro.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing InterPro local cache data structures", t61, t62, 2);
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
    public Map<String, Integer> hide_go_terms(Set<String> selected_gos, Map<String, Set<String>> go_parent2children)
        throws Exception {
        Map<String, Integer> result = new HashMap<String, Integer>();
        for (String go_id : selected_gos) {
            int is_hidden = 0;
            if (go_parent2children.containsKey(go_id)) {
                boolean has_present_child = false;
                for (String child_go : go_parent2children.get(go_id)) {
                    if (selected_gos.contains(child_go)) {
                        has_present_child = true;
                        break;
                    }
                }
                if (has_present_child) {
                    is_hidden = 1;
                }
            }
            result.put(go_id, is_hidden);
        }
        return result;
    }

    /*
     * Class representing a gene family to which a transcript is assigned
     */
    public class GeneFamilyAssignment {

        public GeneFamilyAssignment(String gf_id, String gf_assignment_score) {
            super();
            this.gf_id = gf_id;
            this.gf_assignment_score = gf_assignment_score;
            this.gf_content = new HashSet<String>();
        }

        public String getGfContent() {
            StringBuffer buffer = new StringBuffer();
            if (this.gf_content.size() == 0) {
                return buffer.toString();
            }
            for (String gfc : this.gf_content) {
                buffer.append(" " + gfc);
            }
            String res = buffer.toString().substring(1);
            return res;
        }

        public String gf_id = null;
        public String gf_assignment_score = null;
        public Set<String> gf_content = null;
        public int gf_size = 0;

        public int associated_genes = 0;
    }

    /**
     * This function stores the mapping from each transcript to a gene family into the database.
     * @param trapid_exp_id TRAPID experiment identifier
     * @param gf_map Mapping of each transcript to a gene family.
     * @throws Exception In case of
     */
    private void storeGeneFamilyAssignments(
        Connection trapid_db_connection,
        String trapid_exp_id,
        Map<String, GeneFamilyAssignment> transcript2gf,
        GF_TYPE gf_type
    ) throws Exception {
        //step1: update the transcripts table (columns gf_id and gf_id_score).
        String sql1 =
            "UPDATE `transcripts` SET `gf_id`= ? , `gf_id_score` = ? WHERE `experiment_id`='" +
            trapid_exp_id +
            "' AND `transcript_id` = ? ";
        //step2: insert the gene families into the database. Take care of the gene family type
        String sql2a =
            "INSERT INTO `gene_families` (`experiment_id`,`gf_id`,`plaza_gf_id`,`num_transcripts`) VALUES ('" +
            trapid_exp_id +
            "', ? , ? , ? )";
        String sql2b =
            "INSERT INTO `gene_families` (`experiment_id`,`gf_id`,`gf_content`,`num_transcripts`) VALUES ('" +
            trapid_exp_id +
            "', ? , ? , ? )";
        PreparedStatement stmt1 = trapid_db_connection.prepareStatement(sql1);
        PreparedStatement stmt2 = null;
        if (gf_type == GF_TYPE.HOM) {
            stmt2 = trapid_db_connection.prepareStatement(sql2a);
        } else if (gf_type == GF_TYPE.IORTHO) {
            stmt2 = trapid_db_connection.prepareStatement(sql2b);
        }

        //for secondary database insertions
        Map<String, GeneFamilyAssignment> gf_information = new HashMap<String, GeneFamilyAssignment>();

        //first: update the transcripts with their associated gene family
        for (String transcript_id : transcript2gf.keySet()) {
            GeneFamilyAssignment gfa = transcript2gf.get(transcript_id);
            String trapid_gf_id = trapid_exp_id + "_" + gfa.gf_id;
            stmt1.setString(1, trapid_gf_id);
            stmt1.setString(2, gfa.gf_assignment_score);
            stmt1.setString(3, transcript_id);
            stmt1.execute();

            //now, update the gf_information for the second batch of updates
            if (!gf_information.containsKey(trapid_gf_id)) {
                gfa.associated_genes = 1;
                gf_information.put(trapid_gf_id, gfa);
            } else {
                gf_information.get(trapid_gf_id).associated_genes++;
            }
        }

        //now, insert the gene families into the database.
        for (String trapid_gf_id : gf_information.keySet()) {
            GeneFamilyAssignment gas = gf_information.get(trapid_gf_id);
            if (gf_type == GF_TYPE.HOM) {
                stmt2.setString(1, trapid_gf_id);
                stmt2.setString(2, gas.gf_id);
                stmt2.setString(3, "" + gas.associated_genes);
            } else if (gf_type == GF_TYPE.IORTHO) {
                stmt2.setString(1, trapid_gf_id);
                stmt2.setString(2, gas.getGfContent());
                stmt2.setString(3, "" + gas.associated_genes);
            }
            stmt2.execute();
        }

        stmt1.close();
        stmt2.close();
        gf_information.clear();
    }

    private String getGfPrefix(Connection trapid_db_connection, String plaza_db_name) throws Exception {
        String result = "";
        Statement stmt = trapid_db_connection.createStatement();
        String sql = "SELECT `gf_prefix` FROM `data_sources` WHERE `db_name` = '" + plaza_db_name + "' ";
        ResultSet set = stmt.executeQuery(sql);
        if (set.next()) {
            result = set.getString("gf_prefix");
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
    private Map<String, GeneFamilyAssignment> inferTranscriptGenefamiliesHom(
        Connection plaza_db_connection,
        Map<String, List<String[]>> data,
        int num_hits,
        String gf_prefix
    ) throws Exception {
        Map<String, GeneFamilyAssignment> result = new HashMap<String, GeneFamilyAssignment>();

        //storing homology information from PLAZA database in memory. Way faster than
        //subsequent queries
        Map<String, String> gene2gf = this.loadGeneFamilies(plaza_db_connection, gf_prefix);
        Map<String, Integer> gf_sizes = this.determinePLAZAGfSizes(gene2gf);

        //split up in 2 different modes: easy mode where num_top_hits is equal to 1, the more
        //computationally difficuly mode where num_top_hits is larger than 1.

        if (num_hits == 1) { //EASY MODE, ONLY TAKE TOP HIT
            for (String transcript_id : data.keySet()) {
                //top_hit : (gene_id,bitscore,query_start)
                String[] top_hit = data.get(transcript_id).get(0); //there is always at least 1 hit
                String gf_id = gene2gf.get(top_hit[0]); //each gene is present in a gene family (might be singleton)
                String gf_score = "1";
                GeneFamilyAssignment gfa = new GeneFamilyAssignment(gf_id, gf_score);
                gfa.gf_size = gf_sizes.get(gf_id);
                result.put(transcript_id, gfa);
            }
        } else { //DIFFICULT MODE, TAKE 'NUM_TOP_HITS' HITS
            NumberFormat nf = NumberFormat.getInstance();
            nf.setMaximumFractionDigits(2);
            for (String transcript_id : data.keySet()) {
                Map<String, Integer> gf_counter = new HashMap<String, Integer>();
                int max_gf_counter = 0;
                String current_best_gf = null;
                List<String[]> search_hits = data.get(transcript_id);
                //store data
                for (int i = 0; i < search_hits.size() && i < num_hits; i++) {
                    String gf_id = gene2gf.get(search_hits.get(i)[0]);
                    if (!gf_counter.containsKey(gf_id)) {
                        gf_counter.put(gf_id, 0);
                    }
                    int new_count = gf_counter.get(gf_id) + 1;
                    gf_counter.put(gf_id, new_count);
                    if (new_count > max_gf_counter) {
                        current_best_gf = gf_id;
                    }
                }
                //now, retrieve correct gene family
                double gf_score_d = ((double) gf_counter.get(current_best_gf)) / ((double) (num_hits));
                String gf_score = nf.format(gf_score_d);
                GeneFamilyAssignment gfa = new GeneFamilyAssignment(current_best_gf, gf_score);
                gfa.gf_size = gf_sizes.get(current_best_gf);
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
    private Map<String, GeneFamilyAssignment> inferTranscriptGenefamiliesIntegrativeOrthology(
        Connection plaza_db_connection,
        Map<String, List<String[]>> data
    ) throws Exception {
        Map<String, GeneFamilyAssignment> result = new HashMap<String, GeneFamilyAssignment>();

        //create an SQL-query which performs the necessary database queries for each hit_gene.
        //necessary prepared statement per hit_gene. However, we can try to optimize so the same gene cannot
        //be queried twice, as are the in-paralogs of the hit-gene.
        PreparedStatement stmt = plaza_db_connection.prepareStatement(
            "SELECT * FROM `orthologs` WHERE `gene_id` = ? AND `type`!='anchor_point' "
        );

        Map<String, GeneFamilyAssignment> cache = new HashMap<String, GeneFamilyAssignment>();

        int counter = 1;

        for (String transcript_id : data.keySet()) {
            String hit_gene = data.get(transcript_id).get(0)[0];

            if (!cache.containsKey(hit_gene)) {
                //basic set containing the content of the ortho-group
                Set<String> valid_ortho_group_content = new HashSet<String>();
                List<String> new_in_paralogs = new ArrayList<String>();
                List<String> explored_in_paralogs = new ArrayList<String>();
                new_in_paralogs.add(hit_gene);
                valid_ortho_group_content.add(hit_gene);

                //now, we just keep on adding data while there are new in-paralogs.
                while (new_in_paralogs.size() > 0) {
                    Map<String, Set<String>> ortho_info = new HashMap<String, Set<String>>();
                    Set<String> possible_in_paralogs = new HashSet<String>();
                    String query_gene = new_in_paralogs.get(0);
                    stmt.setString(1, query_gene);
                    ResultSet set = stmt.executeQuery();
                    while (set.next()) {
                        String hit_species = set.getString("species");
                        String type = set.getString("type");
                        String[] global_content = set.getString("gene_content").split(";");
                        for (String species_content : global_content) {
                            String[] spec_content = species_content.split(":");
                            String spec = spec_content[0];
                            String[] genes = spec_content[1].split(",");
                            for (String g : genes) {
                                if (!ortho_info.containsKey(g)) {
                                    ortho_info.put(g, new HashSet<String>());
                                }
                                ortho_info.get(g).add(type);
                                if (
                                    spec.equals(hit_species) &&
                                    !new_in_paralogs.contains(g) &&
                                    !explored_in_paralogs.contains(g)
                                ) {
                                    possible_in_paralogs.add(g);
                                }
                            }
                        }
                    }
                    set.close();

                    Set<String> acceptable_evidence_genes = this.filterOrthologs(ortho_info);
                    for (String pip : possible_in_paralogs) {
                        if (acceptable_evidence_genes.contains(pip)) {
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
                String gf_id = "iOrtho_" + counter++;
                String gf_assign_score = "1.0";
                GeneFamilyAssignment gas = new GeneFamilyAssignment(gf_id, gf_assign_score);
                gas.gf_content.addAll(valid_ortho_group_content);
                gas.gf_size = gas.gf_content.size();
                result.put(transcript_id, gas);

                //put link to GeneFamilyAssignment into cache for all explored paralogs
                for (String eip : explored_in_paralogs) {
                    cache.put(eip, gas);
                }
            } else {
                result.put(transcript_id, cache.get(hit_gene));
            }
        }

        stmt.close();
        return result;
    }

    private Set<String> filterOrthologs(Map<String, Set<String>> data) {
        Set<String> result = new HashSet<String>();
        for (String query_gene : data.keySet()) {
            //if(query_gene.equals("MD09G020410")){System.out.println(data.get(query_gene).size());}
            if (data.get(query_gene).size() >= 2) {
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
    // Modify this method for eggnog?  Caching the whole table may be a bit heavy.
    private Map<String, Integer> getSpeciesHitCount(
        Connection plaza_db_connection,
        Map<String, List<String[]>> simsearch_data
    ) throws Exception {
        Map<String, Integer> result = new HashMap<String, Integer>();
        //gather a default mapping of genes to species for all content in plaza database
        Map<String, String> gene2species = new HashMap<String, String>(14875530);
        String query = "SELECT `gene_id`,`species` FROM `annotation` ";
        Statement stmt = plaza_db_connection.createStatement();
        stmt.setFetchSize(2000);
        ResultSet set = stmt.executeQuery(query);
        int counter = 0;
        while (set.next()) {
            if ((counter % 1000000) == 0) {
                System.out.println(counter + " rows...");
            }
            counter += 1;
            String gene_id = set.getString(1);
            String species = set.getString(2);
            gene2species.put(gene_id, species);
        }
        set.close();
        stmt.close();
        //now gather data
        for (String transcript : simsearch_data.keySet()) {
            if (simsearch_data.get(transcript) != null && simsearch_data.get(transcript).size() != 0) {
                String[] d = simsearch_data.get(transcript).get(0);
                String hit_gene = d[0];
                if (gene2species.containsKey(hit_gene)) {
                    String species = gene2species.get(hit_gene);
                    if (!result.containsKey(species)) {
                        result.put(species, 0);
                    }
                    result.put(species, (result.get(species) + 1));
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
    private Map<String, List<String[]>> parseSimilarityOutputFile(String file_name, int num_top_hits) throws Exception {
        Map<String, List<String[]>> result = new HashMap<String, List<String[]>>();
        BufferedReader reader = new BufferedReader(new FileReader(new File(file_name)));
        String s = reader.readLine();
        while (s != null) {
            if (!s.startsWith("#")) {
                String[] split = s.split("\t");
                if (split.length == 12) {
                    String transcript_id = split[0].trim();
                    String hit_gene = split[1].trim();
                    String perc_identity = split[2].trim();
                    String aln_length = split[3].trim();
                    String query_start = split[6].trim();
                    String query_stop = split[7].trim();
                    String log_e_val = split[10].trim();
                    String bitscore = split[11].trim();
                    if (!result.containsKey(transcript_id)) {
                        result.put(transcript_id, new ArrayList<String[]>());
                    }
                    if (result.get(transcript_id).size() < num_top_hits) {
                        String[] tmp = {
                            hit_gene,
                            bitscore,
                            query_start,
                            query_stop,
                            perc_identity,
                            aln_length,
                            log_e_val
                        };
                        result.get(transcript_id).add(tmp);
                    }
                }
            }
            s = reader.readLine();
        }
        reader.close();
        return result;
    }

    /**
     * Reverse mapping from transcripts to gene families, to gene families to transcripts
     * @param data
     * @return
     */
    public Map<String, List<String>> reverseMapping(Map<String, GeneFamilyAssignment> data) {
        Map<String, List<String>> result = new HashMap<String, List<String>>();
        for (String k : data.keySet()) {
            String v = data.get(k).gf_id;
            if (!result.containsKey(v)) {
                result.put(v, new ArrayList<String>());
            }
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
    private Map<String, Integer> determinePLAZAGfSizes(Map<String, String> gene2gf) {
        Map<String, Integer> result = new HashMap<String, Integer>();
        for (String gene_id : gene2gf.keySet()) {
            String gf_id = gene2gf.get(gene_id);
            if (!result.containsKey(gf_id)) {
                result.put(gf_id, 0);
            }
            result.put(gf_id, result.get(gf_id) + 1);
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
    private Map<String, String> loadGeneFamilies(Connection conn, String gf_type) throws Exception {
        Map<String, String> result = new HashMap<String, String>();
        String query = "SELECT `gene_id`,`gf_id` FROM `gf_data` ";
        if (!gf_type.trim().equals("")) {
            query += " WHERE `gf_id` LIKE '" + gf_type + "%' ";
        }
        Statement stmt = conn.createStatement();
        ResultSet set = stmt.executeQuery(query);
        while (set.next()) {
            String gene_id = set.getString(1);
            String gf_id = set.getString(2);
            result.put(gene_id, gf_id);
        }
        set.close();
        stmt.close();
        return result;
    }

    private Map<String, Set<String>> loadGoData(Connection conn) throws Exception {
        Map<String, Set<String>> gene_go = new HashMap<String, Set<String>>();
        String query_go_annot = "SELECT `gene_id`,`go` FROM `gene_go` ";
        Statement stmt_go_annot = conn.createStatement();
        ResultSet set_go_annot = stmt_go_annot.executeQuery(query_go_annot);
        while (set_go_annot.next()) {
            String gene_id = set_go_annot.getString(1);
            String go = set_go_annot.getString(2);
            if (!gene_go.containsKey(gene_id)) {
                gene_go.put(gene_id, new HashSet<String>());
            }
            gene_go.get(gene_id).add(go);
        }
        set_go_annot.close();
        stmt_go_annot.close();
        return gene_go;
    }

    private Map<String, Set<String>> loadInterproData(Connection conn) throws Exception {
        Map<String, Set<String>> gene_interpro = new HashMap<String, Set<String>>();
        // String query_interpro_annot						= "SELECT `gene_id`,`motif_id` FROM `protein_motifs_data` ";
        // Reference DBs structure changed in version 2
        // String query_interpro_annot						= "SELECT `gene_id`,`motif_id` FROM `gene_protein_motif` ";
        // Quick fix to not take SignalP annotations as they are too long for the limit of 10 characters (12 in some cases)
        // Ask Michiel what to do with them (keep or not?)
        String query_interpro_annot =
            "SELECT `gene_id`,`motif_id` FROM `gene_protein_motif` where `motif_id` not like 'SignalP-%'";
        Statement stmt_interpro_annot = conn.createStatement();
        ResultSet set_intepro_annot = stmt_interpro_annot.executeQuery(query_interpro_annot);
        while (set_intepro_annot.next()) {
            String gene_id = set_intepro_annot.getString(1);
            String motif_id = set_intepro_annot.getString(2);
            if (!gene_interpro.containsKey(gene_id)) {
                gene_interpro.put(gene_id, new HashSet<String>());
            }
            gene_interpro.get(gene_id).add(motif_id);
        }
        return gene_interpro;
    }

    private Map<String, Set<String>> getFamilyContent(Connection plaza_conn, String gf_type) throws Exception {
        Map<String, Set<String>> result = new HashMap<String, Set<String>>();
        String query = "SELECT `gf_id`,`gene_id` FROM `gf_data` WHERE `gf_id` LIKE '" + gf_type + "%'";
        Statement stmt = plaza_conn.createStatement();
        ResultSet set = stmt.executeQuery(query);
        while (set.next()) {
            String gf_id = set.getString(1);
            String gene_id = set.getString(2);
            if (!result.containsKey(gf_id)) {
                result.put(gf_id, new HashSet<String>());
            }
            result.get(gf_id).add(gene_id);
        }
        set.close();
        stmt.close();
        return result;
    }

    // Scaling issues when working with big databases...
    private Map<String, Integer> getGeneCdsLengths(Connection plaza_conn) throws Exception {
        Map<String, Integer> result = new HashMap<String, Integer>();
        //String query				= "SELECT `gene_id`,LENGTH(`seq`) as length FROM `annotation` WHERE `type`='coding' ";
        //String query				= "SELECT `gene_id`,LENGTH(`seq`) as length FROM `annotation` ";
        // This query does not scale well... Use it only if we are dealing with a PLAZA reference database.
        String db_url = plaza_conn.getMetaData().getURL();
        String db_name = db_url.substring(db_url.lastIndexOf("/") + 1);
        String query = "SELECT `gene_id`,CHAR_LENGTH(`seq`) FROM `annotation` WHERE `type`='coding' ";
        if (!db_name.contains("plaza")) {
            // EggNOG ref. db contains protein sequences: Multiply sequence length by 3!
            query = "SELECT `gene_id`, `seq_length`*3 FROM `annotation` USE INDEX(PRIMARY) WHERE `type`='coding' ";
        }
        Statement stmt = plaza_conn.createStatement();
        stmt.setFetchSize(1000);
        ResultSet set = stmt.executeQuery(query);
        while (set.next()) {
            String gene_id = set.getString(1);
            int length = set.getInt(2);
            result.put(gene_id, length);
        }
        set.close();
        stmt.close();
        return result;
    }

    private Map<String, boolean[]> getTranscriptStartStop(Connection trapid_conn, String trapid_exp_id)
        throws Exception {
        Map<String, boolean[]> result = new HashMap<String, boolean[]>();
        String query =
            "SELECT `transcript_id`,`orf_contains_start_codon`,`orf_contains_stop_codon` FROM `transcripts` WHERE `experiment_id`='" +
            trapid_exp_id +
            "' ";
        Statement stmt = trapid_conn.createStatement();
        ResultSet set = stmt.executeQuery(query);
        while (set.next()) {
            String transcript_id = set.getString(1);
            boolean has_start = set.getBoolean(2);
            boolean has_stop = set.getBoolean(3);
            boolean[] k = { has_start, has_stop };
            result.put(transcript_id, k);
        }
        set.close();
        stmt.close();
        return result;
    }

    private Map<String, Integer> getTranscriptOrfLengths(Connection trapid_conn, String trapid_exp_id)
        throws Exception {
        Map<String, Integer> result = new HashMap<String, Integer>();
        String query =
            "SELECT `transcript_id`, CHAR_LENGTH(UNCOMPRESS(`orf_sequence`)) as length FROM `transcripts` WHERE `experiment_id`='" +
            trapid_exp_id +
            "' ";
        Statement stmt = trapid_conn.createStatement();
        ResultSet set = stmt.executeQuery(query);
        while (set.next()) {
            String transcript_id = set.getString(1);
            int length = set.getInt(2);
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
    public Map<String, Map<String, Set<String>>> loadGOGraph(Connection conn) throws Exception {
        Map<String, Map<String, Set<String>>> result = new HashMap<String, Map<String, Set<String>>>();

        Map<String, Set<String>> go_child2parents = new HashMap<String, Set<String>>(); //mapping from child go to parent go
        Map<String, Set<String>> go_parent2children = new HashMap<String, Set<String>>(); //mapping of

        // String query					= "SELECT `child_go`,`parent_go` FROM `go_parents` ";
        // Reference DBs structure changed in version 2
        String query = "SELECT `child`, `parent` FROM `functional_parents` WHERE `type`=\"go\"";
        Statement stmt = conn.createStatement();
        ResultSet set = stmt.executeQuery(query);
        while (set.next()) {
            String child_go = set.getString(1);
            String parent_go = set.getString(2);
            if (!go_child2parents.containsKey(child_go)) {
                go_child2parents.put(child_go, new HashSet<String>());
            }
            go_child2parents.get(child_go).add(parent_go);
            if (!go_parent2children.containsKey(parent_go)) {
                go_parent2children.put(parent_go, new HashSet<String>());
            }
            go_parent2children.get(parent_go).add(child_go);
        }
        set.close();
        stmt.close();

        result.put("child2parents", go_child2parents);
        result.put("parent2children", go_parent2children);

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
    private Connection createDbConnection(String server, String database, String login, String password)
        throws Exception {
        // String url		= "jdbc:mysql://"+server+"/"+database;
        String url = "jdbc:mysql://" + server + "/" + database + "?rewriteBatchedStatements=true";
        Connection conn = DriverManager.getConnection(url, login, password);
        return conn;
    }

    public class ORFFinder {

        private Hashtable<String, Character> codonLookUp;
        private Hashtable<String, Character> alternateCodonLookUp;

        public ORFFinder(String transl_tables_file, String transl_table) {
            // Initiate lookup tables
            codonLookUp = new Hashtable<String, Character>();
            alternateCodonLookUp = new Hashtable<String, Character>();

            // Default translation table -- overridden if valid translation table JSON file / translation table index
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
                // Read JSON data as string
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
                // Parse translation table data JSON string
                JSONObject all_transl_tables = new JSONObject(transl_tables_str);
                JSONObject transl_table_data = all_transl_tables.getJSONObject(transl_table).getJSONObject("table");
                // Store translation table data as regular hashmap (useless?)
                for (String codon : transl_table_data.keySet()) {
                    alternateCodonLookUp.put(codon, transl_table_data.getString(codon).charAt(0));
                }
                codonLookUp = alternateCodonLookUp;
                System.err.println(
                    "[Message] Translation table " + transl_table + " loaded from file " + transl_tables_file
                );
            } catch (Exception e) {
                System.err.println(
                    "[Message] No translation table file provided or invalid translation table index, will use the harcoded (standard) one. Stack trace: "
                );
                e.printStackTrace();
            }
            System.err.println(Arrays.asList(codonLookUp)); // Debug: print hashmap
        }

        private String reverseComplement(String input) {
            StringBuffer buffer = new StringBuffer(input.toUpperCase()).reverse();
            char[] new_sequence = new char[buffer.length()];
            for (int i = 0; i < buffer.length(); i++) {
                char temp = buffer.charAt(i);
                switch (temp) {
                    case 'G':
                        new_sequence[i] = 'C';
                        break;
                    case 'C':
                        new_sequence[i] = 'G';
                        break;
                    case 'A':
                        new_sequence[i] = 'T';
                        break;
                    case 'T':
                        new_sequence[i] = 'A';
                        break;
                    case 'M':
                        new_sequence[i] = 'K';
                        break;
                    case 'K':
                        new_sequence[i] = 'M';
                        break;
                    case 'R':
                        new_sequence[i] = 'Y';
                        break;
                    case 'Y':
                        new_sequence[i] = 'R';
                        break;
                    case 'H':
                        new_sequence[i] = 'D';
                        break;
                    case 'D':
                        new_sequence[i] = 'H';
                        break;
                    case 'B':
                        new_sequence[i] = 'V';
                        break;
                    case 'V':
                        new_sequence[i] = 'B';
                        break;
                    default:
                        new_sequence[i] = 'N';
                        break;
                }
            }
            return new String(new_sequence);
        }

        private char translateCodon(String Codon) {
            if (Codon.length() == 3) {
                if (codonLookUp.containsKey(Codon)) {
                    return codonLookUp.get(Codon);
                } else {
                    //check if it's a fourfold degenerate
                    String sub = Codon.substring(0, 2);
                    if (codonLookUp.containsKey(sub)) {
                        return codonLookUp.get(sub);
                    } else {
                        return 'X';
                    } //unknown or undefined codon
                }
            } else {
                return 'X';
            } //wrong length
        }

        //frame can be 0,1,2,3 (0 if no BLAST hits found).
        public Map<String, String> findLongestORF(String sequence, char strand, int frame) {
            //create sequence to work with, reverse complement if necessary
            if (frame == 0) {
                String forward_sequence = sequence;
                String reverse_sequence = this.reverseComplement(sequence);
                //iterate over the 3 frames and the 2 strands, and compute all the results. Then compare them.
                Map<String, Map<String, String>> cache = new HashMap<String, Map<String, String>>();
                for (int i = 1; i <= 3; i++) { //forward_strand
                    cache.put("+" + i, this.findLongestORF(forward_sequence, i));
                }
                for (int i = 1; i <= 3; i++) { //reverse strand
                    cache.put("-" + i, this.findLongestORF(reverse_sequence, i));
                }
                //find best result
                String best_result = null;
                int longest_orf_length = -1;
                for (String possible_strand_frame : cache.keySet()) {
                    if (cache.get(possible_strand_frame).get("ORF").length() > longest_orf_length) {
                        best_result = possible_strand_frame;
                        longest_orf_length = cache.get(possible_strand_frame).get("ORF").length();
                    }
                }
                //add extra data, which should be added to the database
                cache.get(best_result).put("newStrand", best_result.substring(0, 1));
                cache.get(best_result).put("newFrame", best_result.substring(1, 2));
                return cache.get(best_result);
            } else {
                String finalSequence = "";
                if (strand == '-') {
                    finalSequence = this.reverseComplement(sequence);
                } else {
                    finalSequence = sequence;
                }
                return this.findLongestORF(finalSequence, frame);
            }
        }

        //frame can be 1,2,3 (not 0 -> refer to above method).
        private Map<String, String> findLongestORF(String sequence, int frame) {
            Map<String, String> result = new HashMap<String, String>();
            //final result
            String longestAA = "";
            String longestORF = "";
            int longestStart = 0;
            int longestStop = 0;
            boolean longestHasStartCodon = false;
            boolean longestHasStopCodon = false;
            //create current sequence (placeholder for sequence found)
            StringBuffer currentAA = new StringBuffer();
            StringBuffer currentORF = new StringBuffer();
            int currentStart = 0;
            int currentStop = 0;
            boolean currentHasStartCodon = false;
            boolean currentHasStopCodon = false;

            //go over sequence in selected frame and store everything in the current sequence,
            //at the end or at a stop evaluate and store as longest
            boolean reading = true; //only read start or after encountering a valid start codon
            for (int i = frame - 1; i < sequence.length() - 2; i += 3) {
                String codon = sequence.substring(i, i + 3);
                char AminoAcid = this.translateCodon(codon);
                if (AminoAcid == 'M' && reading == false) {
                    reading = true;
                    currentStart = i;
                    currentHasStartCodon = true;
                }
                if (reading) {
                    currentAA.append(AminoAcid);
                    currentORF.append(codon);
                }
                if (AminoAcid == '*' && reading == true) {
                    reading = false;
                    currentHasStopCodon = true;
                    currentStop = i + 2;
                    if (currentAA.length() > longestAA.length()) {
                        longestAA = currentAA.toString();
                        longestORF = currentORF.toString();
                        longestStart = currentStart;
                        longestStop = currentStop;
                        longestHasStartCodon = currentHasStartCodon;
                        longestHasStopCodon = currentHasStopCodon;
                    }
                    currentAA = new StringBuffer();
                    currentORF = new StringBuffer();
                    currentStart = 0;
                    currentStop = 0;
                    currentHasStartCodon = false;
                    currentHasStopCodon = false;
                }
            }
            //final one,
            if (currentAA.length() > longestAA.length()) {
                longestAA = currentAA.toString();
                longestORF = currentORF.toString();
                longestStart = currentStart;
                longestStop = currentStart + longestORF.length();
                longestHasStartCodon = currentHasStartCodon;
                longestHasStopCodon = currentHasStopCodon;
            }
            if (longestORF.startsWith("ATG")) {
                longestHasStartCodon = true;
            }
            result.put("AA", longestAA);
            result.put("ORF", longestORF);
            result.put("start", "" + longestStart);
            result.put("stop", "" + longestStop);
            result.put("hasStartCodon", "" + longestHasStartCodon);
            result.put("hasStopCodon", "" + longestHasStopCodon);
            return result;
        }
    }

    /* EggNOG-related code */

    /**
     * Perform the actual transcript to GF (i.e. the 'best OG') asssignment, based on the
     * lists of OGs we got for each query.
     * Result consists of a mapping of the transcript, to the gene family.
     * @param plaza_db_connection Connection to the PLAZA database
     * @param data transcript to OGs mapping
     * @throws Exception Databse problems
     */
    private Map<String, GeneFamilyAssignment> inferTranscriptGenefamiliesEggnog(
        Connection plaza_db_connection,
        Map<String, List<String>> data
    ) throws Exception {
        Map<String, GeneFamilyAssignment> result = new HashMap<String, GeneFamilyAssignment>();

        //create an SQL-query which performs the necessary database queries for each hit_gene.
        //necessary prepared statement per hit_gene. However, we can try to optimize so the same gene cannot
        //be queried twice, as are the in-paralogs of the hit-gene.
        PreparedStatement stmt = plaza_db_connection.prepareStatement(
            "SELECT gf_id,method FROM `gene_families` WHERE `gf_id` = ? ;"
        );

        for (String transcript_id : data.keySet()) {
            if (data.get(transcript_id).get(0).equals("None")) {
                // TODO: assign 'None' GF.
                GeneFamilyAssignment gfa = new GeneFamilyAssignment("None", "1");
                gfa.gf_size = 1;
                result.put(transcript_id, gfa);
            } else {
                String max_level = data.get(transcript_id).get(1);
                // 1. Get OGs as list
                List<String> chosen_ogs = Arrays.asList(data.get(transcript_id).get(0).split("\\s*,\\s*"));
                // 2. Check how many OGs there are at the maximum level
                List<String> candidate_ogs = new ArrayList<String>(); // List of OGs (one or more) that will be evaluated to be assigned as 'GF'
                for (String og : chosen_ogs) {
                    if (og.contains(max_level)) {
                        candidate_ogs.add(og);
                    }
                }
                String gf_id = "";
                String gf_score = "1";
                int gf_size = 0;
                // 3. If only one, retrieve GF info and assign it. If more than one, assign the transcript to the biggest one
                // This choice will not matter for the annotation, but this OG is what will be displayed on the website (and
                // thus has an influence for all downstream analyses -- MSAs, phylogenetic trees, etc.).
                for (String candidate_og : candidate_ogs) {
                    String og_name = candidate_og.split("@")[0];
                    stmt.setString(1, og_name);
                    ResultSet set = stmt.executeQuery();
                    while (set.next()) {
                        String current_gf_id = set.getString("gf_id"); //  + "@" + max_level;
                        // Get GF size from parsed information in `method` field.
                        // Format: `eggNOG 4.5|species:n,genes:n`
                        int current_gf_size = Integer.parseInt(set.getString("method").split(",")[1].split(":")[1]);
                        if (current_gf_size > gf_size) {
                            gf_id = current_gf_id;
                            gf_size = current_gf_size;
                        }
                    }
                    set.close();
                }
                GeneFamilyAssignment gfa = new GeneFamilyAssignment(gf_id, gf_score);
                gfa.gf_size = gf_size;
                result.put(transcript_id, gfa);
            }
        }
        stmt.close();
        return result;
    }

    /**
     * Retrieve transcript's gene family data from TRAPID's database. Use for processing eggNOG experiments, since
     * this information should already be stored (see `process_emapper.py` script).
     * Result consists of a mapping of the transcript, to the gene family.
     * @param trapid_db_connection Connection to the TRAPID database
     * @param trapid_exp_id a TRAPID experiment identifier
     * @throws Exception Databse problems
     */
    private Map<String, GeneFamilyAssignment> fetchTranscriptGenefamiliesEggnog(
        Connection trapid_db_connection,
        String trapid_exp_id
    ) throws Exception {
        Map<String, GeneFamilyAssignment> result = new HashMap<String, GeneFamilyAssignment>();

        // Create an SQL query to get GF information
        PreparedStatement stmt = trapid_db_connection.prepareStatement(
            "SELECT transcript_id, gf_id FROM `transcripts` WHERE `experiment_id` = ? ;"
        );
        stmt.setString(1, trapid_exp_id);
        ResultSet set = stmt.executeQuery();
        while (set.next()) {
            String trapid_gf_id = set.getString("gf_id");
            String transcript_id = set.getString("transcript_id");
            if (trapid_gf_id != null) {
                String ref_gf_id = trapid_gf_id.split("_")[1]; // Get rid of experiment id prefix
                GeneFamilyAssignment gfa = new GeneFamilyAssignment(ref_gf_id, "1");
                // We set a dummy `gf_size` value, since this variable is not used for any other post-processing step for
                // eggNOG experiments
                gfa.gf_size = 0;
                result.put(transcript_id, gfa);
            }
        }
        set.close();
        stmt.close();
        return result;
    }

    /**
     * Reverse mapping from transcripts to OGs to OGs (comma-separated string) to transcripts (list of transcripts)
     * @param data transcripts - OGs map
     * @return reversed mapping
     */
    public Map<String, List<String>> reverseMappingOgsEggnog(Map<String, List<String>> data) {
        Map<String, List<String>> result = new HashMap<String, List<String>>();
        for (String k : data.keySet()) {
            String v = data.get(k).get(0); // Get OGs string
            if (!result.containsKey(v)) {
                result.put(v, new ArrayList<String>());
            }
            result.get(v).add(k);
        }
        return result;
    }

    /**
     * Assign GO terms to each transcript, based on the best similarity hit. Return hashmap with this assignment.
     * @param plaza_connection Connection to PLAZA database
     * @param simsearch_data Similarity search results
     * @return Mapping from transcript to GO terms
     * @throws Exception
     * We had to create a different method than the previously-used one as Eggnog's `gene_go` table is too big, in its
     * current state (it probably contains a lot of redundancies so maybe in the future this will become useless).
     */
    // TODO: handle `alt_ids`!
    private Map<String, Set<String>> assignGoTranscriptsEggnog_BESTHIT(
        Connection plaza_connection,
        Map<String, List<String[]>> simsearch_data
    ) throws Exception {
        Map<String, Set<String>> transcript_go = new HashMap<String, Set<String>>();
        PreparedStatement stmt = plaza_connection.prepareStatement("SELECT `go` FROM `gene_go` WHERE `gene_id` = ? ;");
        long t11 = System.currentTimeMillis();
        // Load the GO parents table into memory to prevent unnecessary queries
        Map<String, Map<String, Set<String>>> go_graph_data = this.loadGOGraph(plaza_connection);
        Map<String, Set<String>> go_child2parents = go_graph_data.get("child2parents");
        Map<String, Set<String>> go_parent2children = go_graph_data.get("parent2children");
        long t12 = System.currentTimeMillis();
        timing("Loading GO graph", t11, t12, 2);

        // We do not cache GO data (table is ~20 GB)
        // long t21	= System.currentTimeMillis();
        // Map<String,Set<String>> gene_go = this.loadGoData(plaza_connection);
        // long t22	= System.currentTimeMillis();
        // timing("Caching GO data",t21,t22,2);

        long t41 = System.currentTimeMillis();

        for (String transcript_id : simsearch_data.keySet()) {
            if (simsearch_data.get(transcript_id).size() != 0) {
                Set<String> go_terms = new HashSet<String>();
                String best_hit = simsearch_data.get(transcript_id).get(0)[0];
                // Use the best hit to transfer the functional annotation
                // 1. Get GO terms of best hit
                stmt.setString(1, best_hit);
                ResultSet set = stmt.executeQuery();
                while (set.next()) {
                    // Get current GO term
                    String current_go = set.getString("go");
                    go_terms.add(current_go);
                    // Also add parents
                    if (go_child2parents.containsKey(current_go)) {
                        for (String go_parent : go_child2parents.get(current_go)) {
                            go_terms.add(go_parent);
                        }
                    }
                }
                set.close();
                // Remove the 3 top GO terms (Biological Process, Cellular Component, Molecular Function).
                if (go_terms.contains("GO:0003674")) {
                    go_terms.remove("GO:0003674");
                }
                if (go_terms.contains("GO:0008150")) {
                    go_terms.remove("GO:0008150");
                }
                if (go_terms.contains("GO:0005575")) {
                    go_terms.remove("GO:0005575");
                }
                if (go_terms.size() > 0) {
                    transcript_go.put(transcript_id, go_terms);
                } else {
                    System.err.println("[Warning] No GO terms found (BEST_HIT) for transcript " + transcript_id);
                }
            }
        }
        long t42 = System.currentTimeMillis();
        timing("Inferring functional annotation per transcript", t41, t42, 2);
        // Clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        go_child2parents.clear();
        go_parent2children.clear();
        go_graph_data.clear();
        // gene_go.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing local cache data structures", t61, t62, 2);
        stmt.close();
        return transcript_go;
    }

    /**
     * Assign GO terms to each transcript, based on the associated ortholog groups (+ min. frequency rule).
     * @param plaza_connection Connection to PLAZA database
     * @param transcript2ogs Mapping from transcripts to OGs
     * @param ogs2transcripts Mapping from OGs to transcripts
     * @param min_freq Minimum frequency of GO term in OG to consider it
     * @return Mapping from transcript to GO terms
     * @throws Exception
     */
    // NOTE: this method is not optimized at all. Ideas to optimize: find a way to cahce data,
    // rework og-transcript map, or create og_go table in the db (would limit the number of queries)
    // Problem if using `IN` clauses: some OGs have A LOT of members (for example: COG4886 with 26124 members)
    private Map<String, Set<String>> assignGoTranscriptsEggnog_GF(
        Connection plaza_connection,
        Map<String, List<String>> transcript2ogs,
        Map<String, List<String>> ogs2transcripts,
        double min_freq
    ) throws Exception {
        Map<String, Set<String>> transcript_go = new HashMap<String, Set<String>>();
        //        Map<String,Set<String>> gene_go_cache = new HashMap<String,Set<String>>();
        Map<String, Set<String>> og_go_cache = new HashMap<String, Set<String>>();
        Map<String, Set<String>> og_gene_cache = new HashMap<String, Set<String>>();
        long t11 = System.currentTimeMillis();

        // Load the GO parents table into memory to prevent unnecessary queries
        Map<String, Map<String, Set<String>>> go_graph_data = this.loadGOGraph(plaza_connection);
        Map<String, Set<String>> go_child2parents = go_graph_data.get("child2parents");
        Map<String, Set<String>> go_parent2children = go_graph_data.get("parent2children");
        long t12 = System.currentTimeMillis();
        timing("Loading GO graph", t11, t12, 2);

        // We cannot cache `gene_go` data as it is too heavy?
        // long t21	= System.currentTimeMillis();
        // Map<String,Set<String>> gene_go = this.loadGoData(plaza_connection);
        // long t22	= System.currentTimeMillis();
        // timing("Caching GO data",t21,t22,2);

        // Necessary queries
        // Go OG members
        String query_og_genes = "SELECT `gene_id` FROM `gf_data` WHERE `gf_id` = ? ;";
        // Tying to use `IN` clause instead
        // String query_go_terms = "SELECT `go` FROM `gene_go` WHERE `gene_id` = ? ;";
        String query_go_terms = "SELECT `gene_id`,`go` FROM `gene_go` WHERE `gene_id` IN ";
        PreparedStatement stmt_og_genes = plaza_connection.prepareStatement(query_og_genes);
        // PreparedStatement stmt_go_terms	= plaza_connection.prepareStatement(query_go_terms);
        // Higher `setFetchSize()` should help.
        stmt_og_genes.setFetchSize(4000);
        // stmt_go_terms.setFetchSize(500);
        long t41 = System.currentTimeMillis();
        // Remove `None` for later. No need to get annotation.
        if (ogs2transcripts.keySet().contains("None")) {
            ogs2transcripts.remove("None");
        }
        int remaining_og_strings = ogs2transcripts.keySet().size();
        for (String og_string : ogs2transcripts.keySet()) {
            //            System.out.println(og_string);
            long t411 = System.currentTimeMillis();
            // Set of all selected GOs for this set of OGs
            Set<String> selected_gos = new HashSet<String>();
            // For each selected OG, get: members, and their associated GO terms
            String[] ogs = og_string.split(",");
            for (String og : ogs) {
                Set<String> genes = null;
                genes = new HashSet<String>();
                String og_id = og.split("@")[0];
                // 1. Get members of current OG
                if (!og_gene_cache.keySet().contains(og_id)) {
                    stmt_og_genes.setString(1, og_id);
                    ResultSet set = stmt_og_genes.executeQuery();
                    while (set.next()) {
                        genes.add(set.getString(1));
                    }
                    //				System.out.println(og + "\t" + genes.toString());
                    og_gene_cache.put(og_id, genes);
                    set.close();
                } else {
                    System.err.println("[Message] Already in cache! " + og);
                    genes = og_gene_cache.get(og_id);
                }

                // 2. Retrieve GO terms associated to these genes
                Map<String, Set<String>> go_genes = new HashMap<String, Set<String>>();
                // If there are too many genes (> 6000), get the data in multiple chunks.
                int chunk_size = 1000;
                if (genes.size() > 2000) {
                    // We need a list?
                    System.out.println(og_id + ": it's too big! ");
                    List<String> gene_list = new ArrayList<String>(genes);
                    for (int i = 0; i < genes.size(); i += chunk_size) {
                        String in_clause = "(";
                        int limit = Math.min(genes.size(), i + chunk_size);
                        for (int j = i; j < limit; j++) {
                            if (!(j == limit - 1)) {
                                in_clause += "'" + gene_list.get(j) + "', ";
                            } else {
                                in_clause += "'" + gene_list.get(j) + "')";
                            }
                        }
                        Statement stmt_go_terms = plaza_connection.createStatement();
                        stmt_go_terms.setFetchSize(2000);
                        //                            System.out.println(query_go_terms + in_clause);
                        ResultSet set_go = stmt_go_terms.executeQuery(query_go_terms + in_clause);
                        while (set_go.next()) {
                            String gene = set_go.getString(1);
                            String go_term = set_go.getString(2);
                            if (!(go_genes.containsKey(go_term))) {
                                go_genes.put(go_term, new HashSet<String>());
                            }
                            go_genes.get(go_term).add(gene);
                            // Also add parental terms
                            if (go_child2parents.containsKey(go_term)) {
                                for (String go_parent : go_child2parents.get(go_term)) {
                                    if (!go_genes.containsKey(go_parent)) {
                                        go_genes.put(go_parent, new HashSet<String>());
                                    }
                                    go_genes.get(go_parent).add(gene);
                                }
                            }
                        }
                        set_go.close();
                    }
                } else {
                    String in_clause = "(";
                    int remaining_genes = genes.size();
                    for (String gene_id : genes) {
                        remaining_genes--;
                        if (!(remaining_genes == 0)) {
                            in_clause += "'" + gene_id + "', ";
                        } else {
                            in_clause += "'" + gene_id + "')";
                        }
                    }
                    Statement stmt_go_terms = plaza_connection.createStatement();
                    stmt_go_terms.setFetchSize(1000);
                    //                    System.out.println(og_id);
                    //                    System.out.println(genes.size());
                    ResultSet set_go = stmt_go_terms.executeQuery(query_go_terms + in_clause);
                    while (set_go.next()) {
                        String gene = set_go.getString(1);
                        String go_term = set_go.getString(2);
                        if (!(go_genes.containsKey(go_term))) {
                            go_genes.put(go_term, new HashSet<String>());
                        }
                        go_genes.get(go_term).add(gene);
                        // Also add parental terms
                        if (go_child2parents.containsKey(go_term)) {
                            for (String go_parent : go_child2parents.get(go_term)) {
                                if (!go_genes.containsKey(go_parent)) {
                                    go_genes.put(go_parent, new HashSet<String>());
                                }
                                go_genes.get(go_parent).add(gene);
                            }
                        }
                    }
                    set_go.close();
                }
                // 4. Remove the 3 top GO terms (BP/CC/MF).
                if (go_genes.containsKey("GO:0003674")) {
                    go_genes.remove("GO:0003674");
                }
                if (go_genes.containsKey("GO:0008150")) {
                    go_genes.remove("GO:0008150");
                }
                if (go_genes.containsKey("GO:0005575")) {
                    go_genes.remove("GO:0005575");
                }
                // 5. Now, iterate over all the GO identifiers, and select those who are present in at least
                // `min_freq` of the genes associated with this gene family
                double gene_gf_count = (double) genes.size(); // Minimum count considered to select a GO term
                for (String go_id : go_genes.keySet()) {
                    double gene_go_count = go_genes.get(go_id).size();
                    if (gene_go_count / gene_gf_count >= min_freq) {
                        selected_gos.add(go_id);
                    }
                }
                // Clear the temporary storage for this gene family.
                go_genes.clear();
            } // End 'for each OG of OG string'
            // 6. Finally, add GOs to transcripts that have this string
            for (String transcript_id : ogs2transcripts.get(og_string)) {
                transcript_go.put(transcript_id, new HashSet<String>(selected_gos));
            }
            // Debug timing
            long t412 = System.currentTimeMillis();
            remaining_og_strings--;
            timing("Dealt with OG string '" + og_string + "'. Still remaining: " + remaining_og_strings, t411, t412, 3);
        } // End 'for each OG string'

        long t42 = System.currentTimeMillis();
        timing("Inferring functional annotation per gene family", t41, t42, 2);
        // Clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        go_child2parents.clear();
        go_parent2children.clear();
        go_graph_data.clear();
        // gene_go_cache.clear();
        og_gene_cache.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing local cache data structures", t61, t62, 2);
        return transcript_go;
    }

    /**
     * Assign GO terms to each transcript, based on the associated ortholog groups (+ min. frequency rule), using the
     * `gf_functional_data` table (taht contains precomputed redundant go+frequency for each gene family).
     * @param plaza_connection Connection to PLAZA database
     * @param transcript2ogs Mapping from transcripts to OGs
     * @param ogs2transcripts Mapping from OGs to transcripts
     * @param min_freq    Minimum frequency of GO term in OG to consider it
     * @return Mapping from transcript to GO terms
     * @throws Exception
     */
    private Map<String, Set<String>> assignGoTranscriptsEggnog_GF_precomputed(
        Connection plaza_connection,
        Map<String, List<String>> transcript2ogs,
        Map<String, List<String>> ogs2transcripts,
        double min_freq
    ) throws Exception {
        Map<String, Set<String>> transcript_go = new HashMap<String, Set<String>>();
        Map<String, Set<String>> og_go_cache = new HashMap<String, Set<String>>();
        long t11 = System.currentTimeMillis();

        // Load the GO parents table into memory to prevent unnecessary queries
        Map<String, Map<String, Set<String>>> go_graph_data = this.loadGOGraph(plaza_connection);
        Map<String, Set<String>> go_child2parents = go_graph_data.get("child2parents");
        Map<String, Set<String>> go_parent2children = go_graph_data.get("parent2children");
        long t12 = System.currentTimeMillis();
        timing("Loading GO graph", t11, t12, 2);

        // We cannot cache `gene_go` data as it is too heavy?
        // long t21	= System.currentTimeMillis();
        // Map<String,Set<String>> gene_go = this.loadGoData(plaza_connection);
        // long t22	= System.currentTimeMillis();
        // timing("Caching GO data",t21,t22,2);

        // Necessary queries
        String query_go_terms =
            "SELECT `name` FROM `gf_functional_data` WHERE `gf_id` = ? and `type`='go' and freq >=" + min_freq + ";";
        PreparedStatement stmt_go_terms = plaza_connection.prepareStatement(query_go_terms);
        // Higher `setFetchSize()` should help.
        stmt_go_terms.setFetchSize(1000);
        long t41 = System.currentTimeMillis();
        // Remove `None` for later. No need to get annotation.
        if (ogs2transcripts.keySet().contains("None")) {
            ogs2transcripts.remove("None");
        }
        int remaining_og_strings = ogs2transcripts.keySet().size();
        for (String og_string : ogs2transcripts.keySet()) {
            long t411 = System.currentTimeMillis();
            // Set of all selected GOs for this set of OGs
            Set<String> selected_gos = new HashSet<String>();
            // For each selected OG, get: members, and their associated GO terms
            String[] ogs = og_string.split(",");
            for (String og : ogs) {
                String og_id = og.split("@")[0];
                // 1. Retrieve GO terms associated to genes of current OG (precomputed in `gf_functional_data` table
                Set<String> og_go = new HashSet<String>();
                // If there are too many genes (> 6000), get the data in multiple chunks.
                if (!og_go_cache.keySet().contains(og_id)) {
                    stmt_go_terms.setString(1, og_id);
                    ResultSet set = stmt_go_terms.executeQuery();
                    while (set.next()) {
                        og_go.add(set.getString(1));
                    }
                    System.out.println(og + "\t" + og_go.toString());
                    og_go_cache.put(og_id, og_go);
                    set.close();
                } else {
                    System.err.println("[Message] Already in cache! " + og);
                    og_go = og_go_cache.get(og_id);
                }
                // 5. Now, iterate over all the GO identifiers, and select those who are present in at least
                // `min_freq` of the genes associated with this gene family
                for (String go_id : og_go) {
                    if (!selected_gos.contains(go_id)) {
                        selected_gos.add(go_id);
                    }
                }
                // Clear the temporary storage for this gene family.
                og_go.clear();
            } // End 'for each OG of OG string'
            // 2. Add GOs to transcripts that have this OG string
            for (String transcript_id : ogs2transcripts.get(og_string)) {
                transcript_go.put(transcript_id, new HashSet<String>(selected_gos));
            }
            // Debug timing
            long t412 = System.currentTimeMillis();
            remaining_og_strings--;
            timing("Dealt with OG string '" + og_string + "'. Still remaining: " + remaining_og_strings, t411, t412, 3);
        } // End 'for each OG string'

        long t42 = System.currentTimeMillis();
        timing("Inferring functional annotation per gene family", t41, t42, 2);
        // Clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        go_child2parents.clear();
        go_parent2children.clear();
        go_graph_data.clear();
        // gene_go_cache.clear();
        og_go_cache.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing local cache data structures", t61, t62, 2);
        return transcript_go;
    }

    /**
     * Assign GO terms to each transcript, based on both Eggnog OGs and the best similarity hit. Call the
     * two Eggnog-specific functional annotation methods defined above.
     * @param plaza_connection Connection to PLAZA database
     * @param transcript2ogs Mapping from transcripts to gene families
     * @param ogs2transcripts Mapping from gene families to transcripts
     * @param simsearch_data Similarity search results
     * @param min_freq Minimum frequency of GO term required in GF/OG to transfer it to transcripts
     * @return Mapping from transcript to GO terms
     * @throws Exception
     */
    private Map<String, Set<String>> assignGoTranscriptsEggnog_GF_BESTHIT(
        Connection plaza_connection,
        Map<String, List<String>> transcript2ogs,
        Map<String, List<String>> ogs2transcripts,
        Map<String, List<String[]>> simsearch_data,
        double min_freq
    ) throws Exception {
        Map<String, Set<String>> transcript_go = new HashMap<String, Set<String>>();
        // 1. Get best hit GO annotation and GF/OGs GO annotation
        Map<String, Set<String>> transcript_go_besthit =
            this.assignGoTranscriptsEggnog_BESTHIT(plaza_connection, simsearch_data);
        Map<String, Set<String>> transcript_go_gf =
            this.assignGoTranscriptsEggnog_GF_precomputed(plaza_connection, transcript2ogs, ogs2transcripts, min_freq);
        // 2. Populate and return `transcript_go`
        transcript_go.putAll(transcript_go_besthit);
        for (String transcript : transcript_go_gf.keySet()) {
            if (!transcript_go.containsKey(transcript)) {
                transcript_go.put(transcript, transcript_go_gf.get(transcript));
            } else {
                transcript_go.get(transcript).addAll(transcript_go_gf.get(transcript));
            }
        }
        return transcript_go;
    }

    ////////
    // KO annotation
    ////////

    /**
     * Assign KO terms to each transcript, based on the best similarity hit. Return hashmap with this assignment.
     * @param plaza_connection Connection to PLAZA database
     * @param simsearch_data Similarity search results
     * @return Mapping from transcript to KO terms
     * @throws Exception
     */
    private Map<String, Set<String>> assignKoTranscripts_BESTHIT(
        Connection plaza_connection,
        Map<String, List<String[]>> simsearch_data
    ) throws Exception {
        Map<String, Set<String>> transcript_ko = new HashMap<String, Set<String>>();
        PreparedStatement stmt = plaza_connection.prepareStatement("SELECT `ko` FROM `gene_ko` WHERE `gene_id` = ? ;");

        // We could cache the KO data (for EggNOG db, table is ~350 MB).
        // Do it if it takes too long to do it naively, with no caching
        // long t21	= System.currentTimeMillis();
        // Map<String,Set<String>> gene_go = this.loadKoData(plaza_connection);
        // long t22	= System.currentTimeMillis();
        // timing("Caching KO data",t21,t22,2);

        long t41 = System.currentTimeMillis();

        for (String transcript_id : simsearch_data.keySet()) {
            if (simsearch_data.get(transcript_id).size() != 0) {
                Set<String> ko_terms = new HashSet<String>();
                String best_hit = simsearch_data.get(transcript_id).get(0)[0];
                // Use the best hit to transfer the functional annotation
                // 1. Get KO terms of best hit
                stmt.setString(1, best_hit);
                ResultSet set = stmt.executeQuery();
                while (set.next()) {
                    // Get current KO term
                    String current_ko = set.getString("ko");
                    ko_terms.add(current_ko);
                }
                set.close();
                if (ko_terms.size() > 0) {
                    transcript_ko.put(transcript_id, ko_terms);
                }
                // else {
                //     System.err.println("[Warning] No KO terms found (BEST_HIT) for transcript " + transcript_id);
                // }
            }
        }
        long t42 = System.currentTimeMillis();
        timing("Inferring functional annotation per transcript", t41, t42, 2);
        // Clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        // ko_terms.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing local cache data structures", t61, t62, 2);
        stmt.close();
        System.out.println(transcript_ko);
        return transcript_ko;
    }

    /**
     * Assign KO terms to each transcript, based on the associated ortholog groups (+ min. frequency rule), using the
     * `gf_functional_data` table (taht contains precomputed ko/frequency for each gene family).
     * @param plaza_connection Connection to PLAZA database
     * @param transcript2ogs Mapping from transcripts to OGs
     * @param ogs2transcripts Mapping from OGs to transcripts
     * @param min_freq Minimum frequency of KO term in OG to consider it
     * @return Mapping from transcript to GO terms
     * @throws Exception
     */
    private Map<String, Set<String>> assignKoTranscripts_GF_precomputed(
        Connection plaza_connection,
        Map<String, List<String>> transcript2ogs,
        Map<String, List<String>> ogs2transcripts,
        double min_freq
    ) throws Exception {
        Map<String, Set<String>> transcript_ko = new HashMap<String, Set<String>>();
        Map<String, Set<String>> og_ko_cache = new HashMap<String, Set<String>>();
        long t11 = System.currentTimeMillis();

        // Necessary queries
        String query_ko_terms =
            "SELECT `name` FROM `gf_functional_data` WHERE `gf_id` = ? and `type`='ko' and freq >=" + min_freq + ";";
        PreparedStatement stmt_ko_terms = plaza_connection.prepareStatement(query_ko_terms);
        // Higher `setFetchSize()` should help.
        stmt_ko_terms.setFetchSize(1000);
        long t41 = System.currentTimeMillis();
        // Remove `None` for later. No need to get annotation.
        if (ogs2transcripts.keySet().contains("None")) {
            ogs2transcripts.remove("None");
        }
        int remaining_og_strings = ogs2transcripts.keySet().size();
        for (String og_string : ogs2transcripts.keySet()) {
            long t411 = System.currentTimeMillis();
            // Set of all selected KOs for this set of OGs
            Set<String> selected_kos = new HashSet<String>();
            // For each selected OG, get: members, and their associated KOs
            String[] ogs = og_string.split(",");
            for (String og : ogs) {
                String og_id = og.split("@")[0];
                // 1. Retrieve KOs associated to genes of current OG (precomputed in `gf_functional_data` table
                Set<String> og_ko = new HashSet<String>();
                if (!og_ko_cache.keySet().contains(og_id)) {
                    stmt_ko_terms.setString(1, og_id);
                    ResultSet set = stmt_ko_terms.executeQuery();
                    while (set.next()) {
                        og_ko.add(set.getString(1));
                    }
                    og_ko_cache.put(og_id, og_ko);
                    set.close();
                } else {
                    System.err.println("[Message] Already in cache! " + og);
                    og_ko = og_ko_cache.get(og_id);
                }
                // 5. Now, iterate over all the KO identifiers, and select those who are present in at least
                // `min_freq` of the genes associated with this gene family
                for (String ko_id : og_ko) {
                    if (!selected_kos.contains(ko_id)) {
                        selected_kos.add(ko_id);
                    }
                }
                // Clear the temporary storage for this gene family.
                og_ko.clear();
            } // End 'for each OG of OG string'
            // 2. Add KOs to transcripts that have this set of OGs
            for (String transcript_id : ogs2transcripts.get(og_string)) {
                transcript_ko.put(transcript_id, new HashSet<String>(selected_kos));
            }
            // Debug timing
            long t412 = System.currentTimeMillis();
            remaining_og_strings--;
            timing("Dealt with OG string '" + og_string + "'. Still remaining: " + remaining_og_strings, t411, t412, 3); // Debug
        } // End 'for each OG string'

        long t42 = System.currentTimeMillis();
        timing("Inferring functional annotation per gene family", t41, t42, 2);
        // Clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        og_ko_cache.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing local cache data structures", t61, t62, 2);
        return transcript_ko;
    }

    /**
     * Assign KO terms to each transcript, based on both Eggnog OGs and the best similarity hit. Call the
     * two Eggnog-specific functional annotation methods defined above.
     * @param plaza_connection Connection to PLAZA database
     * @param transcript2ogs Mapping from transcripts to gene families
     * @param ogs2transcripts Mapping from gene families to transcripts
     * @param simsearch_data Similarity search results
     * @param min_freq minimum frequency rquired in GF/OG to transfer KO terms
     * @return Mapping from transcript to GO terms
     * @throws Exception
     */
    private Map<String, Set<String>> assignKoTranscripts_GF_BESTHIT(
        Connection plaza_connection,
        Map<String, List<String>> transcript2ogs,
        Map<String, List<String>> ogs2transcripts,
        Map<String, List<String[]>> simsearch_data,
        double min_freq
    ) throws Exception {
        Map<String, Set<String>> transcript_ko = new HashMap<String, Set<String>>();
        // 1. Get best hit GO annotation and GF/OGs GO annotation
        Map<String, Set<String>> transcript_ko_besthit =
            this.assignKoTranscripts_BESTHIT(plaza_connection, simsearch_data);
        Map<String, Set<String>> transcript_ko_gf =
            this.assignKoTranscripts_GF_precomputed(plaza_connection, transcript2ogs, ogs2transcripts, min_freq);
        // 2. Populate and return `transcript_ko`
        transcript_ko.putAll(transcript_ko_besthit);
        for (String transcript : transcript_ko_gf.keySet()) {
            if (!transcript_ko.containsKey(transcript)) {
                transcript_ko.put(transcript, transcript_ko_gf.get(transcript));
            } else {
                transcript_ko.get(transcript).addAll(transcript_ko_gf.get(transcript));
            }
        }
        return transcript_ko;
    }

    /**
     * Store all the transcript - KO associations, that were detected
     * @param trapid_connection Connection to trapid database
     * @param trapid_exp_id Trapid experiment id
     * @param transcript_ko Mapping from transcripts to KO terms
     * @throws Exception
     */
    private void storeKoTranscripts(
        Connection trapid_connection,
        String trapid_exp_id,
        Map<String, Set<String>> transcript_ko
    ) throws Exception {
        long t51 = System.currentTimeMillis();
        // TRAPID db structure changed for version 2...
        String insert_ko_annot =
            "INSERT INTO `transcripts_annotation` (`experiment_id`, `type`, `transcript_id`, `name`, `is_hidden`) VALUES ('" +
            trapid_exp_id +
            "', 'ko', ?, ?, '0') ";
        PreparedStatement ins_ko_annot = trapid_connection.prepareStatement(insert_ko_annot);
        boolean prev_commit_state = trapid_connection.getAutoCommit();
        trapid_connection.setAutoCommit(false);

        for (String transcript_id : transcript_ko.keySet()) {
            for (String ko_id : transcript_ko.get(transcript_id)) {
                ins_ko_annot.setString(1, transcript_id);
                ins_ko_annot.setString(2, ko_id);
                ins_ko_annot.addBatch();
            }
            ins_ko_annot.executeBatch();
            trapid_connection.commit();
            ins_ko_annot.clearBatch();
        }

        trapid_connection.setAutoCommit(prev_commit_state);
        long t52 = System.currentTimeMillis();
        timing("Storing KO functional annotation in database per transcript", t51, t52, 2);
        //close all statements
        ins_ko_annot.close();
        //clear unnecessary data structures
        long t61 = System.currentTimeMillis();
        transcript_ko.clear();
        System.gc();
        long t62 = System.currentTimeMillis();
        timing("Clearing KO local cache data structures", t61, t62, 2);
    }

    // Get GF content for a set of GFs (doesm't seem like a good idea to cache the whole table when dealing with EggNog).
    private Map<String, Set<String>> getFamilyContentEggnog(Connection plaza_conn, Set<String> og_set)
        throws Exception {
        Map<String, Set<String>> result = new HashMap<String, Set<String>>();
        String query = "SELECT `gene_id` FROM `gf_data` WHERE `gf_id` = ? ";
        PreparedStatement stmt = plaza_conn.prepareStatement(query);
        stmt.setFetchSize(1000);
        for (String og : og_set) {
            String og_name = og.split("@")[0];
            stmt.setString(1, og_name);
            ResultSet set = stmt.executeQuery();
            while (set.next()) {
                String gene_id = set.getString(1);
                if (!result.containsKey(og)) {
                    result.put(og, new HashSet<String>());
                }
                result.get(og).add(gene_id);
            }
            set.close();
        }
        stmt.close();
        return result;
    }

    /**
     * Update `transcript_go` with data read from an RFAM GO annotation file, produced by the `run_infernal.py` script
     * (GO terms transferred transitively to transcripts having Infernal hits). This set of GO contains parental terms
     * so there is no need to retrieve them again!
     * @param transcript_go transcript->GO mapping before hiding GO terms
     * @param rfam_go_file RFAM GO annotation file
     * @return Mapping from transcript to GO terms, including transcripts and GO terms from Infernal/RFAM
     * @throws Exception
     */
    private Map<String, Set<String>> addTranscriptRfamGoData(
        Map<String, Set<String>> transcript_go,
        String rfam_go_file
    ) throws Exception {
        long t11 = System.currentTimeMillis();
        File rfam_go = new File(rfam_go_file);
        // If file exists, retrieve the new GO terms!
        if (rfam_go.isFile()) {
            try {
                BufferedReader br = new BufferedReader(new FileReader(rfam_go));
                for (String line; (line = br.readLine()) != null;) {
                    String[] splitted_line = line.split("\t");
                    String transcript_id = splitted_line[1].toString();
                    String go_term = splitted_line[2].toString();
                    if (transcript_go.keySet().contains(transcript_id)) {
                        Set<String> go_terms = transcript_go.get(transcript_id);
                        go_terms.add(go_term);
                        transcript_go.put(transcript_id, go_terms);
                    } else {
                        Set<String> go_terms = new HashSet<String>();
                        go_terms.add(go_term);
                        transcript_go.put(transcript_id, go_terms);
                    }
                }
            } catch (Exception exc) {
                System.err.println("Problem reading content of RFAM GO file.");
            }
        } else {
            System.err.println("[Warning] RFAM GO annotation file \'" + rfam_go_file + "\' not found!");
            return (transcript_go);
        }
        long t12 = System.currentTimeMillis();
        timing("Adding RFAM GO annotations", t11, t12, 2);
        return (transcript_go);
    }

    // Parse experiment's initial processing configuration file.
    public Ini readExpConfig(String expConfigFilepath) throws java.io.IOException {
        Ini expConfig = new Ini(new File(expConfigFilepath));
        return (expConfig);
    }
}
