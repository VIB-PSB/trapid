#!/usr/local/bin/perl
use strict;
use warnings;
use DBI;

use FindBin qw{$Bin};

### This perl-script is used for dealing with the preprocessing of enrichments within the TRAPID framework.
### There are currently 2 runtimes: 
### 1) cleanup after preprocessing (send email, add logs, remove info from exp_jobs,update enrichment_state in experiments)
### 2) removal of previous enrichment results
###

# Parameter check
if(scalar(@ARGV)<1){
    print STDERR "ERROR: parameters! No runtime defined!\n";
    exit 1;
}

# based on first parameter, define which runtime to follow.
if($ARGV[0] eq "cleanup"){
    my %par;
    #check num parameters
    if(scalar(@ARGV)<7){
	 print STDERR "ERROR: parameters! not enough parms for cleanup \n";
	 exit 1;
    }
    #TRAPID database parameters
    $par{"trapid_db_server"}	= $ARGV[1];
    $par{"trapid_db_name"}	= $ARGV[2];
    $par{"trapid_db_port"}	= $ARGV[3];
    $par{"trapid_db_user"}	= $ARGV[4];
    $par{"trapid_db_password"}	= $ARGV[5];

    #experiment identifier
    $par{"experiment_id"}       = $ARGV[6];

    #create database connection
    my $dsn = qq{DBI:mysql:$par{"trapid_db_name"}:$par{"trapid_db_server"}:$par{"trapid_db_port"}};
    my $dbh = DBI->connect($dsn,$par{"trapid_db_user"},$par{"trapid_db_password"},{RaiseError=>1,AutoCommit=>1});
    if($dbh->err){print STDERR "Cannot create database connection during cleanup of enrichment procedure.";exit;}


    #1) add logs
    my $dbq_logs = $dbh->prepare("INSERT INTO `experiment_log`(`experiment_id`,`date`,`action`,`parameters`,`depth`) VALUES ('".$par{"experiment_id"}."',NOW(),'enrichment_preprocessing','stop','1');");
    $dbq_logs->execute();
    $dbq_logs->finish();
       
    #2) send email   
    my $dbq_email      = $dbh->prepare("SELECT a.`title`,b.`email` FROM `experiments` a,`authentication` b WHERE a.`experiment_id`='".$par{"experiment_id"}."' AND b.`user_id`=a.`user_id` " );
    $dbq_email->execute();
    while((my @record) = $dbq_email->fetchrow_array){
	my $experiment_title  = $record[0];
	my $user_email        = $record[1];
	
	my $sendmail 		= "/usr/lib/sendmail.postfix -t";
    my $from			= "From: TRAPID webmaster <no-reply\@psb.vib-ugent.be>\n";
    my $reply_to 		= "Reply-to: no-reply\@psb.vib-ugent.be\n";
	my $subject		= "Subject: TRAPID experiment has finished processing phase\n";
	my $content		= "Dear,\nYour TRAPID experiment titled '".$experiment_title."' has finished its enrichment preprocessing phase.\n";
	$content		= $content."You can now log in into TRAPID, and begin the analysis of the enriched labels within your dataset.\n";
	$content		= $content."You can access TRAPID at https://bioinformatics.psb.ugent.be/webtools/trapid_dev/ \n";
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
    $dbq_email->finish();

    #3) delete jobinfo from db
    my $dbq_jobinfo = $dbh->prepare("DELETE FROM `experiment_jobs` WHERE `experiment_id`='".$par{"experiment_id"}."' AND `comment`='enrichment_preprocessing' ");
    $dbq_jobinfo->execute();
    $dbq_jobinfo->finish();
    
    #4) set the state of enrichment in the experiments table    
    my $dbq_state = $dbh->prepare("UPDATE `experiments` SET `enrichment_state`='finished' WHERE `experiment_id`='".$par{"experiment_id"}."' ");
    $dbq_state->execute();
    $dbq_state->finish();

    $dbh->disconnect();
}
elsif($ARGV[0] eq "delete_previous_results"){
    my %par;
    #check num parameters
    if(scalar(@ARGV)<9){
	 print STDERR "ERROR: parameters! not enough parms for cleanup \n";
	 exit 1;
    }
    #TRAPID database parameters
    $par{"trapid_db_server"}	= $ARGV[1];
    $par{"trapid_db_name"}	= $ARGV[2];
    $par{"trapid_db_port"}	= $ARGV[3];
    $par{"trapid_db_user"}	= $ARGV[4];
    $par{"trapid_db_password"}	= $ARGV[5];

    #experiment identifier
    $par{"experiment_id"}       = $ARGV[6];

    #extra data
    $par{"data_type"}           = $ARGV[7];
    $par{"label"}               = $ARGV[8];
    $par{"max_pvalue"}          = $ARGV[9];

    #now delete all the entries in the database which correspond with the passed parameters
    my $dsn = qq{DBI:mysql:$par{"trapid_db_name"}:$par{"trapid_db_server"}:$par{"trapid_db_port"}};
    my $dbh = DBI->connect($dsn,$par{"trapid_db_user"},$par{"trapid_db_password"},{RaiseError=>1,AutoCommit=>1});
    if($dbh->err){print STDERR "Cannot create database connection during database delete enrichment procedure.";exit;}	
    my $dbq			= $dbh->prepare("DELETE FROM `functional_enrichments` WHERE `experiment_id`=? AND `label`=? AND `data_type`=? AND `max_p_value`=? ");
    $dbq->execute($par{"experiment_id"},$par{"label"},$par{"data_type"},$par{"max_pvalue"});
    $dbq->finish();
    $dbh->disconnect();
}
else{
    print STDERR "ERROR: unknown runtime : ".$ARGV[0]."\n";
}
