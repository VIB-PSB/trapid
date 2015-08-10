#!/usr/local/bin/perl
use strict;
use warnings;
use DBI;
use FindBin qw{$Bin};

#=======================================================================================================
#=======================================================================================================
#read parameters
my %par;

if(scalar(@ARGV)!=7){
    die "Parameters: trapid-db-settings tmp_dir exp_id gf_id bootstraps";
}

#TRAPID database parameters
$par{"trapid_db_server"}	= $ARGV[0];
$par{"trapid_db_name"}		= $ARGV[1];
$par{"trapid_db_port"}		= $ARGV[2];
$par{"trapid_db_user"}		= $ARGV[3];
$par{"trapid_db_password"}	= $ARGV[4];

#experiment settings
$par{"experiment_id"}           = $ARGV[5];

#gene family for which to create the MSA
$par{"gf_id"}                   = $ARGV[6];

my $dsn_trapid		= qq{DBI:mysql:$par{"trapid_db_name"}:$par{"trapid_db_server"}:$par{"trapid_db_port"}};
my $dbh_trapid		= DBI->connect($dsn_trapid,$par{"trapid_db_user"},$par{"trapid_db_password"},{RaiseError=>1,AutoCommit=>1});	
if($dbh_trapid->err){
	print STDOUT "ERROR: Cannot connect with TRAPID database\n";
	exit;	
}


&delete_current_job($dbh_trapid,$par{"experiment_id"},$par{"gf_id"});
&write_log($dbh_trapid,$par{"experiment_id"},"framedp_stop",$par{"gf_id"},"1");
&send_email($dbh_trapid,$par{"experiment_id"},$par{"gf_id"});

$dbh_trapid->disconnect();



sub delete_current_job($ $ $){
	my $dbh_trapid		= $_[0];
	my $experiment_id	= $_[1];
	my $gf_id		= $_[2];
	my $dbq			= $dbh_trapid->prepare("DELETE FROM `experiment_jobs` WHERE `experiment_id`=? AND `comment`=?");
	my $comment		= "run_framedp ".$gf_id;
	$dbq->execute($experiment_id,$comment);	
	$dbq->finish();
}

sub write_log($ $ $ $){
	my $dbh_trapid		= $_[0];
	my $experiment_id	= $_[1];
	my $action		= $_[2];
	my $parameters		= $_[3];
	my $depth		= $_[4];
	my $dbq 		= $dbh_trapid->prepare("INSERT INTO `experiment_log`(`experiment_id`,`date`,`action`,`parameters`,`depth`) VALUES (?,NOW(),?,?,?)");
	$dbq->execute($experiment_id,$action,$parameters,$depth);
	$dbq->finish();
}


sub send_email($ $ $){
    my $dbh_trapid		= $_[0];    
    my $experiment_id	        = $_[1];
    my $gf_id			= $_[2];
    
    my $query  			= "SELECT a.`title`,b.`email` FROM `experiments` a,`authentication` b WHERE a.`experiment_id`=? AND b.`user_id`=a.`user_id` ";
    my $dbq			= $dbh_trapid->prepare($query);
    $dbq->execute($experiment_id);
    while((my @record) = $dbq->fetchrow_array){
	my $experiment_title  	= $record[0];
	my $user_email        	= $record[1];
	
	my $sendmail 		= "/usr/lib/sendmail.postfix -t";
	my $reply_to 		= "Reply-to: no-reply\@psb.vib-ugent.be\n";
	my $subject		= "Subject: TRAPID FrameDP finished for ".$gf_id."\n";
	my $content		= "Dear user,\nThe frameshift correction using FrameDP for gene family '".$gf_id."' in experiment '".$experiment_title."' has finished.\n";
	$content		= $content."You can now view the result at this URL:\n";
	$content		= $content."http://bioinformatics.psb.ugent.be/webtools/trapid/tools/framedp/".$experiment_id."/".$gf_id." \n";
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
    return;
}
