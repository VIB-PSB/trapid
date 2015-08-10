#!/usr/local/bin/perl
use strict;
use warnings;
use DBI;
use CGI;


use FindBin qw{$Bin};

#read parameters
my %par;

if(scalar(@ARGV)!=5){
	print STDERR "Error: not enough parameters\n";
	die;
}
#TRAPID database parameters
$par{"trapid_db_server"}	= $ARGV[0];
$par{"trapid_db_name"}		= $ARGV[1];
$par{"trapid_db_port"}		= $ARGV[2];
$par{"trapid_db_user"}		= $ARGV[3];
$par{"trapid_db_password"}	= $ARGV[4];

#Create database connection
my $dsn			= qq{DBI:mysql:$par{"trapid_db_name"}:$par{"trapid_db_server"}:$par{"trapid_db_port"}};
my $dbh			= DBI->connect($dsn,$par{"trapid_db_user"},$par{"trapid_db_password"},{RaiseError=>1,AutoCommit=>1});	
if($dbh->err){die;}

#Retrieve all the experiments which have the "send email" flag, and send an email to the associated users.
my $query		= "SELECT a.`title`,b.`email`,a.`experiment_id` FROM `experiments` a,`authentication` b WHERE a.`send_email`='1' AND b.`user_id`=a.`user_id`";
my $dbq			= $dbh->prepare($query);
$dbq->execute();
my @experiment_ids	= ();
while((my @record) = $dbq->fetchrow_array){
	my $experiment_title	= $record[0];
	my $user_email		= $record[1];
	my $experiment_id	= $record[2];
	#print STDOUT $user_email."\t".$experiment_title."\n";
	&send_email($user_email,$experiment_title);
	push(@experiment_ids,$experiment_id);
}
$dbq->finish();


#ok, now change the email field in the table experiments, so the email will be send only once
foreach my $exp_id (@experiment_ids){
    my $statement = "UPDATE `experiments` SET `send_email`='0' WHERE `experiment_id`='".$exp_id."' ";
    my $dbs       = $dbh->prepare($statement);
    $dbs->execute();
    $dbs->finish();
}


#Close database connection
$dbh->disconnect();




#This method sends the actual confirmation email to the user
sub send_email($ $){
	my $user_email		= $_[0];
	my $experiment_title	= $_[1];
	#my $sendmail 		= "/usr/sbin/sendmail -t";
	#my $sendmail 		= "/etc/alternatives/mta-sendmail -t";
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

