#!/usr/local/bin/perl
use strict;
use warnings;
use DBI;

use FindBin qw{$Bin};

### This perl-script contains the necessary calls to sub-programs, 
### to take care of the initial processing step associated with
### transcript-data.

### Current order
### 1) similarity search (RapSearch2)
### 2) gf-assignment
### 3) orf-verificatie
### 4) transfer of functional annotation



#Basic parameter check
if(scalar(@ARGV)<15){
	print STDOUT "ERROR: parameters\n";
   	exit;
}


#=======================================================================================================
#=======================================================================================================
#read parameters
my %par;

#PLAZA database parameters
$par{"plaza_db_server"}		= $ARGV[0];
$par{"plaza_db_name"}		= $ARGV[1];
$par{"plaza_db_port"}		= $ARGV[2];
$par{"plaza_db_user"}		= $ARGV[3];
$par{"plaza_db_password"}	= $ARGV[4];

#TRAPID database parameters
$par{"trapid_db_server"}	= $ARGV[5];
$par{"trapid_db_name"}		= $ARGV[6];
$par{"trapid_db_port"}		= $ARGV[7];
$par{"trapid_db_user"}		= $ARGV[8];
$par{"trapid_db_password"}	= $ARGV[9];

#storage parameter
$par{"temp_dir"}		= $ARGV[10];

#experiment settings
$par{"experiment"}		= $ARGV[11];
$par{"blast_location"}		= $ARGV[12];
$par{"blast_directory"}		= $ARGV[13];
$par{"gf_type"}			= $ARGV[14];
$par{"num_top_hits"}            = $ARGV[15];
$par{"evalue"}                  = $ARGV[16];
$par{"func_annot"}              = $ARGV[17];

#location of executables        
$par{"base_script_location"}    = $ARGV[18];


my $DELETE_TEMP_DATA = 1;


#=======================================================================================================
#=======================================================================================================
# FIRST STEP :: perform similarity search using RAPSEARCH2
#=======================================================================================================
#=======================================================================================================

#Create multi-fasta file containing the transcripts

&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
            "start_similarity_search","RAPSearch2",2);
my $stime1		= time();	
my $multi_fasta_file 	= &create_experiment_multifasta($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
				$par{"trapid_db_user"},$par{"trapid_db_password"},$par{"temp_dir"},$par{"experiment"});										
my $similarity_output 	= &perform_similarity_search($multi_fasta_file,$par{"temp_dir"},$par{"experiment"},
						     $par{"blast_location"},$par{"blast_directory"},$par{"evalue"},
						     $par{"base_script_location"});
			      
my $stime2		= time();
print STDOUT "###Time used for Rapsearch: ".($stime2-$stime1)."s \n";
&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
            "stop_similarity_search","RAPSearch2",2);								
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
my $java_program	= "transcript_pipeline.InitialTranscriptsProcessing";
my $java_location	= $par{"base_script_location"}."java/";

my $java_command        = "java -cp .:..:".$java_location.":".$java_location."mysql.jar ".$java_program;
my @java_options        = ($par{"plaza_db_server"},$par{"plaza_db_name"},$par{"plaza_db_user"},$par{"plaza_db_password"},
			   $par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_user"},$par{"trapid_db_password"},
			   $par{"experiment"},$similarity_output,$par{"gf_type"},$par{"num_top_hits"},$par{"func_annot"});

&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
            "start_postprocessing","",2);
my $java_exec           = $java_command." ".join(" ",@java_options);
print STDOUT $java_exec."\n";
system($java_exec);
#remove final output file
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
	my $reply_to 		= "Reply-to: no-reply\@psb.vib-ugent.be\n";
	my $subject		= "Subject: TRAPID experiment has finished processing phase\n";
	my $content		= "Dear,\nYour TRAPID experiment titled '".$experiment_title."' has finished its processing phase.\n";
	$content		= $content."You can now log in into TRAPID, and begin the analysis of your transcriptome dataset.\n";
	$content		= $content."You can access TRAPID at http://bioinformatics.psb.ugent.be/webtools/trapid/ \n";
	$content		= $content."\n\nThank you for your interest in TRAPID\n";	
	my $send_to		= "To: ".$user_email."\n";
	open(SENDMAIL, "|$sendmail") or die "Cannot open $sendmail: $!";
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
	my $query		= "SELECT `transcript_id`,`transcript_sequence` FROM `transcripts` WHERE `experiment_id`='".$experiment_id."' ";
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











	

