#!/usr/local/bin/perl
use strict;
use warnings;
use DBI;
use Config::IniFiles;

use FindBin qw{$Bin};

### This perl-script contains the necessary calls to sub-programs,
### to take care of the initial processing step associated with
### transcript-data.

### Current order
### 1) similarity search (DIAMOND)
### 2) gf-assignment
### 3) ORF verification
### 4) transfer of functional annotation

### Update: run taxonomic binning step before similarity search (after creation of the multi-fasta file) - for now a simple kaiju mem search vs NCBI NR


#Basic parameter check
if(scalar(@ARGV) < 1){
	print STDOUT "ERROR: incorrect parameters\n";
	print STDOUT "Usage: perl /path/to/initial_processing.pl /path/to/initial_processing_settings.ini\n";
   	exit;
}


#=======================================================================================================
#=======================================================================================================
#read parameters
my %par;

my $initial_processing_ini_file = $ARGV[0];
my $initial_processing_ini_data = Config::IniFiles->new(-file => $initial_processing_ini_file);

# PLAZA/reference database parameters
$par{"plaza_db_server"}		= $initial_processing_ini_data->val("reference_db", "reference_db_server");
$par{"plaza_db_name"}		= $initial_processing_ini_data->val("reference_db", "reference_db_name");
$par{"plaza_db_port"}		= $initial_processing_ini_data->val("reference_db", "reference_db_port");
$par{"plaza_db_user"}		= $initial_processing_ini_data->val("reference_db", "reference_db_username");
$par{"plaza_db_password"}	= $initial_processing_ini_data->val("reference_db", "reference_db_password");

# TRAPID database parameters
$par{"trapid_db_server"}	= $initial_processing_ini_data->val("trapid_db", "trapid_db_server");
$par{"trapid_db_name"}		= $initial_processing_ini_data->val("trapid_db", "trapid_db_name");
$par{"trapid_db_port"}		= $initial_processing_ini_data->val("trapid_db", "trapid_db_port");
$par{"trapid_db_user"}		= $initial_processing_ini_data->val("trapid_db", "trapid_db_username");
$par{"trapid_db_password"}	= $initial_processing_ini_data->val("trapid_db", "trapid_db_password");

# Experiment settings
$par{"experiment"}		= $initial_processing_ini_data->val("experiment", "exp_id");
$par{"temp_dir"}		= $initial_processing_ini_data->val("experiment", "tmp_exp_dir");

# Similarity search settings
$par{"blast_location"}		= $initial_processing_ini_data->val("sim_search", "blast_db_dir");
$par{"blast_directory"}		= $initial_processing_ini_data->val("sim_search", "blast_db") . ".dmnd";
$par{"evalue"}                  = $initial_processing_ini_data->val("sim_search", "e_value");

# Other initial processing settings
$par{"gf_type"}			= $initial_processing_ini_data->val("initial_processing", "gf_type");
$par{"num_top_hits"}            = $initial_processing_ini_data->val("initial_processing", "num_top_hits");
$par{"func_annot"}              = $initial_processing_ini_data->val("initial_processing", "func_annot");
# Location of executables
$par{"base_script_location"}    = $initial_processing_ini_data->val("initial_processing", "base_script_dir");

# Tax. binning user choice ('true' = perform it, 'false' = don't perform it)
$par{"tax_binning"}    = $initial_processing_ini_data->val("tax_binning", "perform_tax_binning");
# Taxonomic scope (should be 'None', unless we work with EggNOG data)
$par{"tax_scope"} = $initial_processing_ini_data->val("initial_processing", "tax_scope");


my $DELETE_TEMP_DATA = 1;


#=======================================================================================================
#=======================================================================================================
# FIRST STEP :: perform similarity search using DIAMOND
#=======================================================================================================
#=======================================================================================================

# Create multi-fasta file containing the transcripts
my $multi_fasta_file 	= &create_experiment_multifasta($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
				$par{"trapid_db_user"},$par{"trapid_db_password"},$par{"temp_dir"},$par{"experiment"});


###
# Extra step: before the similarity search, run kaiju! First trial: unparalellized, quick, dirty
###

if($par{"tax_binning"} eq "false") {
    print STDERR "[Message] Taxonomic classification will not be performed\n";
}

else {
    print STDERR "[Message] Perform taxonomic classification\n";
    my $kaiju_program	= "run_kaiju_split.py";
    my $python_location	= $par{"base_script_location"} . "python/";
    my $kaiju_command   = "python " . $python_location . $kaiju_program;
    my @kaiju_options   = ($initial_processing_ini_file);

    my $kaiju_exec           = $kaiju_command . " " . join(" ", @kaiju_options);
    print STDOUT $kaiju_exec."\n";
    system($kaiju_exec);
}

# Update experiment log (start similarity search)
&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	$par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
	"start_similarity_search","DIAMOND",2);
my $stime1		= time();
# Perform similarity search
my $similarity_output 	= &perform_similarity_search($multi_fasta_file,$par{"temp_dir"},$par{"experiment"},
						     $par{"blast_location"},$par{"blast_directory"},$par{"evalue"},
						     $par{"base_script_location"});

my $stime2		= time();
print STDOUT "###Time used for DIAMOND: ".($stime2-$stime1)."s \n";
&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
            "stop_similarity_search","DIAMOND",2);


###
# Extra step (test): run Infernal to detect ncRNAs (only certain clans)
###
# TODO: do not hardcode Rfam filepaths
my $itime1 = time();
# Run Infernal
my $infernal_output = &perform_infernal_cmsearch($initial_processing_ini_file);
my $itime2 = time();
print STDOUT "###Time used for Infernal: ".($itime2-$itime1)."s \n";


#clear



#=======================================================================================================
#=======================================================================================================
# SECOND STEP :: parse the similarity search output:
#	- gene family assignment
#	- putative ORF finding
#	- functional annotation transfer
#=======================================================================================================
#=======================================================================================================


my $jtime1		= time();
&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	$par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
	"start_postprocessing","",2);

# If transcripts are being processed using EggNOG data, we run Eggnog-mapper (modified version that ignores the
#  similarity search step), and perform post-processing using modified scripts.
if($par{plaza_db_name} eq "db_trapid_ref_eggnog_04_5") {
	# Call modified version of EggNOG-mapper
	my $outfile_prefix = $par{"temp_dir"} . "emapper_" . $par{"experiment"};
	my $emapper = "emapper_trapid.py";
	my $emapper_location = $par{"blast_location"} . "eggnog_mapper/eggnog-mapper/";
	my $emapper_cmd = "python " . $emapper_location . $emapper;
	my @emapper_options = ("--tax_scope " . $par{"tax_scope"}, "-i " . $multi_fasta_file, "--output " . $outfile_prefix,
		"-m diamond", "--data_dir " . $par{"blast_location"} . "eggnog_mapper/eggnog_db/", "--cpu 2",
		"--dmnd_outfile " . $similarity_output, "--translate");  # TODO: add e-value?
	my $emapper_exec = $emapper_cmd . " " . join(" ", @emapper_options);
	print STDERR "[Message] Call EggNOG-mapper with command: " . $emapper_exec . "\n";
	system($emapper_exec);
	&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
		$par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
		"eggnog_mapper","",3);

	# Call post-processing script
	my $emapper_postprocess_script = $par{"base_script_location"} . "python/process_emapper.py";
	my $emapper_postprocess_cmd	= "python " . $emapper_postprocess_script . " " . $initial_processing_ini_file;
	print STDERR "[Message] Call EggNOG-mapper post-processing script with command: " . $emapper_postprocess_cmd . "\n";
	system($emapper_postprocess_cmd);
}


# Run TRAPID's Java post processing code
my $java_program	= "transcript_pipeline.InitialTranscriptsProcessing";
my $java_location	= $par{"base_script_location"}."java/";

my $java_command        = "java -cp .:..:".$java_location.":".$java_location."lib/* ".$java_program;
my @java_options        = ($initial_processing_ini_file, $similarity_output);

my $java_exec           = $java_command." ".join(" ",@java_options);
print STDOUT $java_exec."\n";
system($java_exec);


# Create default transcript subsets
print STDERR "[Message] Create default transcript subsets\n";
my $create_subsets_script = "create_default_subsets.py";
my $python_location	= $par{"base_script_location"} . "python/";
my $create_subsets_command = "python " . $python_location . $create_subsets_script;
my $create_subsets_exec = $create_subsets_command . " " . $initial_processing_ini_file;
print STDOUT $create_subsets_exec."\n";
system($create_subsets_exec);


#remove final output file
# TODO: remove more files!
if($DELETE_TEMP_DATA){
    system("rm -f ".$multi_fasta_file);
    system("rm -f ".$similarity_output);
}

my $jtime2		= time();
print STDOUT "###Time used for Initial Processing : ".($jtime2-$jtime1)."s \n";
&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
            "stop_postprocessing","",2);

&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
            "initial_processing","stop",1);


#delete job from database
&delete_current_job($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
	    "initial_processing");

#send email to user, to indicate that the job is finished.
&send_email($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"});



sub send_email($ $ $ $ $ $){
    my $trapid_db_server	= $_[0];
    my $trapid_db_name	        = $_[1];
    my $trapid_db_port	        = $_[2];
    my $trapid_db_user	        = $_[3];
    my $trapid_db_password	= $_[4];
    my $experiment_id	        = $_[5];

    my $dsn			= qq{DBI:mysql:$trapid_db_name:$trapid_db_server:$trapid_db_port};
    my $dbh			= DBI->connect($dsn,$trapid_db_user,$trapid_db_password,{RaiseError=>1,AutoCommit=>1});
    if($dbh->err){
		print STDOUT "ERROR: Cannot connect with TRAPID database\n";
		exit;
    }
    my $query  = "SELECT a.`title`,b.`email` FROM `experiments` a,`authentication` b WHERE a.`experiment_id`='".$experiment_id."' AND b.`user_id`=a.`user_id` ";
    my $dbq			= $dbh->prepare($query);
    $dbq->execute();
    while((my @record) = $dbq->fetchrow_array){
	my $experiment_title  = $record[0];
	my $user_email        = $record[1];

	my $sendmail 		= "/usr/lib/sendmail.postfix -t";
	my $from			= "From: TRAPID webmaster <no-reply\@psb.vib-ugent.be>\n";
	my $reply_to 		= "Reply-to: no-reply\@psb.vib-ugent.be\n";
	my $subject		= "Subject: TRAPID experiment has finished processing phase\n";
	my $content		= "Dear,\nYour TRAPID experiment titled '".$experiment_title."' has finished its processing phase.\n";
	$content		= $content."You can now log in into TRAPID, and begin the analysis of your transcriptome dataset.\n";
	# $content		= $content."You can access TRAPID at http://bioinformatics.psb.ugent.be/webtools/trapid/ \n";
	$content		= $content."You can access TRAPID at http://bioinformatics.psb.ugent.be/trapid_02/ \n";
	$content		= $content."\n\nThank you for your interest in TRAPID\n";
	my $send_to		= "To: ".$user_email."\n";
	open(SENDMAIL, "|$sendmail") or die "Cannot open $sendmail: $!";
	print SENDMAIL $from;
	print SENDMAIL $reply_to;
	print SENDMAIL $subject;
	print SENDMAIL $send_to;
	print SENDMAIL "Content-type: text/plain\n\n";
	print SENDMAIL $content;
	close(SENDMAIL);

    }
    #close handlers and return name of multi-fasta file
    $dbq->finish();
    $dbh->disconnect();
    return;
}


sub delete_current_job($ $ $ $ $ $ $){
	my $trapid_db_server	= $_[0];
	my $trapid_db_name	= $_[1];
	my $trapid_db_port	= $_[2];
	my $trapid_db_user	= $_[3];
	my $trapid_db_password	= $_[4];
	my $experiment_id	= $_[5];
	my $comment		= $_[6];
	my $dsn			= qq{DBI:mysql:$trapid_db_name:$trapid_db_server:$trapid_db_port};
	my $dbh			= DBI->connect($dsn,$trapid_db_user,$trapid_db_password,{RaiseError=>1,AutoCommit=>1});
	if($dbh->err){print STDERR "Cannot create database connection during database uploads retrieval";exit;}
	my $dbq			= $dbh->prepare("DELETE FROM `experiment_jobs` WHERE `experiment_id`=? AND `comment`=?");
	$dbq->execute($experiment_id,$comment);
	$dbq->finish();
	$dbh->disconnect();
}


sub update_log($ $ $ $ $ $ $ $ $){
    my $trapid_db_server	= $_[0];
	my $trapid_db_name	= $_[1];
	my $trapid_db_port	= $_[2];
	my $trapid_db_user	= $_[3];
	my $trapid_db_password	= $_[4];
	my $experiment_id	= $_[5];
	my $action              = $_[6];
	my $param               = $_[7];
	my $depth               = $_[8];

	my $dsn			= qq{DBI:mysql:$trapid_db_name:$trapid_db_server:$trapid_db_port};
	my $dbh			= DBI->connect($dsn,$trapid_db_user,$trapid_db_password,{RaiseError=>1,AutoCommit=>1});
	if($dbh->err){return;}
	my $query               = "INSERT INTO `experiment_log`(`experiment_id`,`date`,`action`,`parameters`,`depth`) VALUES ('".$experiment_id."',NOW(),'".$action."','".$param."','".$depth."')";
	my $dbq			= $dbh->prepare($query);
	$dbq->execute();
	$dbq->finish();
	$dbh->disconnect();
	return;
}



#=======================================================================================================
#=======================================================================================================
# METHODS FOR PROCESSING THE DATA
#=======================================================================================================
#=======================================================================================================

#Function to retrieve the necessary data from the TRAPID database from a single experiment, and create
#a multi-fasta file containing the transcript-ids and sequences.
sub create_experiment_multifasta($ $ $ $ $ $ $){
	my $trapid_db_server	= $_[0];
	my $trapid_db_name	= $_[1];
	my $trapid_db_port	= $_[2];
	my $trapid_db_user	= $_[3];
	my $trapid_db_password	= $_[4];
	my $temp_dir		= $_[5];
	my $experiment_id	= $_[6];
	#create database connection
	my $dsn			= qq{DBI:mysql:$trapid_db_name:$trapid_db_server:$trapid_db_port};
	my $dbh			= DBI->connect($dsn,$trapid_db_user,$trapid_db_password,{RaiseError=>1,AutoCommit=>1});
	if($dbh->err){
		print STDOUT "ERROR: Cannot connect with TRAPID database\n";
		exit;
	}
	#retrieve data and store write  to multi-fasta file
	my $fasta_file		= $temp_dir."transcripts_".$experiment_id.".fasta";
	open FASTA_FILE,">",$fasta_file;
	my $query		= "SELECT `transcript_id`, UNCOMPRESS(`transcript_sequence`) FROM `transcripts` WHERE `experiment_id`='".$experiment_id."' ";
	my $dbq			= $dbh->prepare($query);
	$dbq->execute();
	while((my @record) = $dbq->fetchrow_array){
		my $transcript_id	= $record[0];
		my $transcript_sequence	= $record[1];
		print FASTA_FILE ">".$transcript_id."\n";
		print FASTA_FILE $transcript_sequence."\n";
	}
	#close handlers and return name of multi-fasta file
	$dbq->finish();
	close FASTA_FILE;
	$dbh->disconnect();
	return $fasta_file;
}


#Function which performs the similarity search by calling the correct external program
sub perform_similarity_search($ $ $ $ $){
	my $multi_fasta_file	= $_[0];
	my $tmp_dir		= $_[1];
	my $experiment		= $_[2];
	my $blast_location	= $_[3];
	my $blast_directory	= $_[4];
	my $blast_evalue        = $_[5];
	my $base_script_location = $_[6];

	my $output_file		= $tmp_dir."diamond_output_".$experiment;
	my $return_file		= $output_file.".m8";
	my $align_file		= $output_file.".aln";

	my $DIAMOND_EXECUTABLE	= "diamond blastx";
	my $DIAMOND_EVALUE		= $blast_evalue;

	my $blast_dir          = $blast_location.$blast_directory;
	print STDOUT "Used DIAMOND database : ".$blast_dir."\n";

	# my $exec_command	= $DIAMOND_EXECUTABLE." --query ".$multi_fasta_file." --db ".$blast_dir." --evalue 1e".$DIAMOND_EVALUE." --out ".$output_file.".m8 -p 2 -k 100 --more-sensitive --log";
	my $exec_command	= $DIAMOND_EXECUTABLE." --query ".$multi_fasta_file." --db ".$blast_dir." --evalue 1e".$DIAMOND_EVALUE." --out ".$output_file.".m8 -p 2 --more-sensitive -k 100 --log";
	print STDOUT $exec_command."\n";

	#perform similarity search
	system($exec_command);
#	sleep (int(rand(600)) + 420);

	#remove alignment file and input multi-fasta file
	system("rm -f ".$align_file);

	return $return_file;
}


# Legacy function for similarity search (with RapSearch)
# Function which performs the similarity search by calling the correct external program
sub perform_similarity_search_rapsearch($ $ $ $ $){
    my $multi_fasta_file	= $_[0];
    my $tmp_dir		= $_[1];
    my $experiment		= $_[2];
    my $blast_location	= $_[3];
    my $blast_directory	= $_[4];
    my $blast_evalue        = $_[5];
    my $base_script_location = $_[6];

    my $output_file		= $tmp_dir."rapsearch_output_".$experiment;
    my $return_file		= $output_file.".m8";
    my $align_file		= $output_file.".aln";

    my $RAPSEARCH_EXECUTABLE	= $base_script_location."bin/rapsearch";
    my $RAPSEARCH_EVALUE		= $blast_evalue;

    my $blast_dir          = $blast_location.$blast_directory;
    print STDOUT "Used RAPSEARCH database : ".$blast_dir."\n";

    my $exec_command	= $RAPSEARCH_EXECUTABLE." -q ".$multi_fasta_file." -d ".$blast_dir." -z 1 -b 0 -e ".$RAPSEARCH_EVALUE." -o ".$output_file;
    print STDOUT $exec_command."\n";

    #perform similarity search
    system($exec_command);

    #remove alignment file and input multi-fasta file
    system("rm -f ".$align_file);

    return $return_file;
}


# Run cmsearch after DIAMOND (2 threads, limited selection of user-selected RNA models)
sub perform_infernal_cmsearch($) {
    my $initial_processing_ini_file = $_[0];
	my $infernal_wrapper_script = $par{"base_script_location"} . "python/run_infernal.py";
    # Call Infernal wrapper script
    my $exec_command	= "python " . $infernal_wrapper_script . " " . $initial_processing_ini_file;
    print STDOUT $exec_command."\n";
    system($exec_command);
}
