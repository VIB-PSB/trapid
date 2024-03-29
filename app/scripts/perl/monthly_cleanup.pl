#!/usr/local/bin/perl
use strict;
use warnings;
use DBI;

use FindBin qw{$Bin};

# This perl script is meant to do two separate things:
#
# the first one is to gather a list of experiments which have not been accessed
# in X months (X is variable, but should be something like 3 months).
# Each of these experiments will be stored in the database, where they are flagged as "to-delete", and an email will be sent to
# the owner of those experiments (1 email, possibly containing multiple experiments).
#
# The second one is to delete those experiments for which a warning email has been send Y months ago (Y is variable, but defaults to 2).
# However, these experiments should not be blindly deleted, rather, a second check should be made whether the "last-edit-date' is now updated
# so the deletion should not occur.


# Basic parameter check
if(scalar(@ARGV)<10){
	print STDERR "Error: incorrect parameters!!!\n";
	exit;
}

# Read parameters
my %par;

# TRAPID database parameters
$par{"trapid_db_server"}	= $ARGV[0];
$par{"trapid_db_name"}		= $ARGV[1];
$par{"trapid_db_port"}		= $ARGV[2];
$par{"trapid_db_user"}		= $ARGV[3];
$par{"trapid_db_password"}	= $ARGV[4];

# Temporary directory
$par{"temp_dir"}		= $ARGV[5];

# Cleanup settings
$par{"year"}			= $ARGV[6];
$par{"month"}			= $ARGV[7];
$par{"no_access"}		= $ARGV[8];
$par{"warning"}			= $ARGV[9];




my $stime1			= time();
#create database connection
my $dsn				= qq{DBI:mysql:$par{"trapid_db_name"}:$par{"trapid_db_server"}:$par{"trapid_db_port"}};
my $dbh				= DBI->connect($dsn,$par{"trapid_db_user"},$par{"trapid_db_password"},{RaiseError=>1,AutoCommit=>1});
if($dbh->err){
	print STDERR "ERROR: Cannot connect with TRAPID database\n";
	exit;
}


# First step, we select the cleanup date id of X months ago (current date - the "warning" variable).
# We use this id to select the experiments which were flagged as outdated during cleanup of X months ago.
# These experiments might be tentavily deleted, after double check on the edit date.
# Previously, this step occurred  after the retrieval of outdated experiments.
# However, this could result in users getting two emails for their outdated experiment:
#  * first when the experiment is flagged as outdated,
#  * again if it is still outdated the next month (except it would be among the experiments to delete)
my $delete_date_id		= &get_delete_date_id($dbh,$par{"year"},$par{"month"},$par{"warning"});

# if `$delete_date_id` equals `-1`: there is no associated date-id found for deletion.
# So we skip this step, and hope the experiments will be deleted in the coming months by using the "<" symbol in the next queries.
if($delete_date_id!=-1){
	# Get all experiments which are associated with a cleanup-date equal to or smaller than the "delete_date_id"
	my @to_delete_experiments 	= &check_before_delete_experiments($dbh,$delete_date_id,$par{"year"},$par{"month"},$par{"no_access"});

	# Delete all the experiments given through the function above
	&delete_experiments($dbh,$par{"temp_dir"},\@to_delete_experiments);

}


# Second step : get the correct id from cleanup_date, which shall be used subsequently to store the experiments which
# have not been access in X (no_access) months
my $cleanup_date_id		= &get_cleanup_date_id($dbh,$par{"year"},$par{"month"});
print STDOUT "cleanup_id : ".$cleanup_date_id."\n";

# Third step: gather all the experiments, for which the "last-edit-date" is longer ago than the indicated "year" and "month"  minus the "no_access"
# variable, which should be a measurement in number of months.
my @outdated_experiments	= &get_outdated_experiments($dbh,$par{"year"},$par{"month"},$par{"no_access"});
#foreach my $oe (@outdated_experiments){print STDOUT $oe."\n";}
print STDOUT "Number of experiments flagged as outdated : ".@outdated_experiments."\n";

# Fourth step, store outdated experiments in the database.
&flag_outdated_experiments($dbh,$cleanup_date_id,\@outdated_experiments);

# Fifth step, send an email to the owner of the flagged experiments, that they should either delete their experiment, or
# check their experiment to update the last edit date.
&send_email_outdated_experiments($dbh,$par{"no_access"},\@outdated_experiments);


my $stime2			= time();
print STDERR "Time used for cleanup processing: ".($stime2-$stime1)."s \n";
# Close database connection
$dbh->disconnect();


#actual deletion of the experiments
sub delete_experiments($ $ $){
	my $dbh			= $_[0];
	my $tmp_dir		= $_[1];
	my @to_delete_exps	= @{$_[2]};

	# Queries to retrieve experiment's information and create a new record in `deleted_experiments`
	my $exp_info_query	= "SELECT exp.user_id, exp.used_plaza_database, tr.`transcript_count` AS transcript_count, exp.title, exp.creation_date, exp.last_edit_date FROM `experiments` exp, (SELECT COUNT(transcript_id) AS transcript_count FROM transcripts WHERE experiment_id = ?) tr WHERE exp.`experiment_id` = ? ";
	my $deleted_exp_query = "INSERT INTO `deleted_experiments` (`user_id`, `experiment_id`, `used_plaza_database`, `num_transcripts`, `title`, `creation_date`, `last_edit_date`, `deletion_date`) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

	# Deletion queries
	my $query1	= "DELETE FROM `transcripts_annotation` WHERE `experiment_id`= ? ";
	my $query2	= "DELETE FROM `transcripts_tax` WHERE `experiment_id`= ? ";
	my $query3	= "DELETE FROM `functional_enrichments` WHERE `experiment_id`= ? ";
	my $query4	= "DELETE FROM `gene_families` WHERE `experiment_id`= ? ";
	my $query5	= "DELETE FROM `rna_families` WHERE `experiment_id`= ? ";
	my $query6	= "DELETE FROM `transcripts_labels` WHERE `experiment_id`= ? ";
	my $query7	= "DELETE FROM `transcripts` WHERE `experiment_id`= ? ";
	my $query8	= "DELETE FROM `similarities` WHERE `experiment_id`= ? ";
	my $query9	= "DELETE FROM `rna_similarities` WHERE `experiment_id`= ? ";
	my $query10	= "DELETE FROM `completeness_results` WHERE `experiment_id`= ? ";
	my $query11	= "DELETE FROM `data_uploads` WHERE `experiment_id`= ? ";
	my $query12	= "DELETE FROM `experiment_jobs` WHERE `experiment_id`= ? ";
	my $query13	= "DELETE FROM `experiment_log` WHERE `experiment_id` = ? ";
	my $query14	= "DELETE FROM `experiment_stats` WHERE `experiment_id` = ? ";
	my $query15	= "DELETE FROM `experiments` WHERE `experiment_id`= ? ";
	my $query16	= "DELETE FROM `cleanup_experiments` WHERE `experiment_id` = ? ";

	my $db_exp_info = $dbh->prepare($exp_info_query);
	my $db_deleted_exp = $dbh->prepare($deleted_exp_query);

	my $dbq1	= $dbh->prepare($query1);
	my $dbq2	= $dbh->prepare($query2);
	my $dbq3	= $dbh->prepare($query3);
	my $dbq4	= $dbh->prepare($query4);
	my $dbq5	= $dbh->prepare($query5);
	my $dbq6	= $dbh->prepare($query6);
	my $dbq7	= $dbh->prepare($query7);
	my $dbq8	= $dbh->prepare($query8);
	my $dbq9	= $dbh->prepare($query9);
	my $dbq10	= $dbh->prepare($query10);
	my $dbq11	= $dbh->prepare($query11);
	my $dbq12	= $dbh->prepare($query12);
	my $dbq13	= $dbh->prepare($query13);
	my $dbq14	= $dbh->prepare($query14);
	my $dbq15	= $dbh->prepare($query15);
	my $dbq16	= $dbh->prepare($query16);

	foreach my $tde (@to_delete_exps){
		# Retrieve experiment's data
		$db_exp_info->execute($tde, $tde);
		while((my @record) = $db_exp_info->fetchrow_array){
			my $tde_user = $record[0];
			my $tde_ref_db = $record[1];
			my $tde_num_trs = $record[2];
			my $tde_title = $record[3];
			my $tde_creation = $record[4];
			my $tde_edit = $record[5];
			# Store deleted experiment data in `deleted_experiments`
			$db_deleted_exp->execute($tde_user, $tde, $tde_ref_db, $tde_num_trs, $tde_title, $tde_creation, $tde_edit);
		}

		# Delete all the data from the database
		$dbq1->execute($tde);
		$dbq2->execute($tde);
		$dbq3->execute($tde);
		$dbq4->execute($tde);
		$dbq5->execute($tde);
		$dbq6->execute($tde);
		$dbq7->execute($tde);
		$dbq8->execute($tde);
		$dbq9->execute($tde);
		$dbq10->execute($tde);
		$dbq11->execute($tde);
		$dbq12->execute($tde);
		$dbq13->execute($tde);
		$dbq14->execute($tde);
		$dbq15->execute($tde);
		$dbq16->execute($tde);

		# Delete the data on the web-share
		system("rm -rf ".$tmp_dir."/".$tde."/");

	}
}





# most difficult subroutine
# Check for each experiment id, which is associated with the delete_date_id, whether it should still be deleted,
# by going over the edit_date of the experiment.
# If it has to be deleted, then remove the data from the necessary tables, and also the necessary temporary data.
#
sub check_before_delete_experiments($ $ $ $ $){
	my $dbh			= $_[0];
	my $delete_date_id	= $_[1];
	my $year		= $_[2];
	my $month		= $_[3];
	my $no_access		= $_[4];

	my @result		= ();

	my $query1		= "SELECT `experiment_id` FROM `cleanup_experiments` WHERE `cleanup_date_id`<=?";
	my $dbq1		= $dbh->prepare($query1);

	my $query2		= "SELECT `last_edit_date` FROM `experiments` WHERE `experiment_id` = ? ";
	my $dbq2		= $dbh->prepare($query2);


	$dbq1->execute($delete_date_id);
	while((my @record) 	= $dbq1->fetchrow_array){
		my $exp_id	= $record[0];

		#ok, check if experiment still exists, and get the associated last_edit date
		$dbq2->execute($exp_id);
		if((my @record2) = $dbq2->fetchrow_array){
			my $last_edit_date	= $record2[0];
			my @exp_ym		= split("-",((split(" ",$last_edit_date))[0]));
			my $exp_year		= $exp_ym[0];
			my $exp_month		= $exp_ym[1];
			my $exp_months		= $exp_year*12 + $exp_month;
			my $curr_months		= $year*12 + $month;
			if(($curr_months - $no_access)> $exp_months){
				push(@result,$exp_id);
			}
		}
	}
	$dbq1->finish();
	$dbq2->finish();
	return @result;
}



#Send emails for all the flagged experiments. This should give the users the opportunity to have them not deleted during
#one of the following checks.
sub send_email_outdated_experiments($ $ $){
	my $dbh			= $_[0];
	my $no_access		= $_[1];
	my @outdated_exps	= @{$_[2]};
	my $sendmail 		= "/usr/lib/sendmail.postfix -t";
	my $from			= "From: TRAPID webmaster <no-reply\@psb.vib-ugent.be>\n";
	my $reply_to 		= "Reply-to: no-reply\@psb.vib-ugent.be\n";

	my $statement		= "SELECT `experiments`.`title`,`authentication`.`email` FROM `experiments`,`authentication` WHERE `experiments`.`experiment_id`= ? AND `experiments`.`user_id`=`authentication`.`user_id`";
	my $dbq			= $dbh->prepare($statement);
	foreach my $oe (@outdated_exps){
		$dbq->execute($oe);
		my @record 	= $dbq->fetchrow_array;
		my $oe_title	= $record[0];
		my $user_email	= $record[1];


		#print STDOUT $user_email."\n";

		#if($user_email eq "mibel\@psb.ugent.be"){
		print STDOUT "email for ".$oe_title."\n";
		my $subject	= "Subject: Your TRAPID experiment will soon be deleted. \n";
		#my $content	= "Dear,\nYour TRAPID experiment titled <html><a href='http://bioinformatics.psb.ugent.be/webtools/trapid/".$oe."'>".$oe_title."</a></html> has not been accessed in ".$no_access." months.\n";
		my $content	= "Dear user,\nYour TRAPID experiment with title '".$oe_title."' and id '".$oe."' has not been accessed in ".$no_access." months.\n";
		$content	= $content."In order to save valuable disk space this experiment will be deleted next month.\n";
		$content	= $content."This can be prevented by logging into TRAPID and accessing the experiment this month, which will update the access date for this experiment.\n\n";
		$content	= $content."You can access TRAPID at http://bioinformatics.psb.ugent.be/trapid_02/ \n";
		$content	= $content."\n\nThank you for using TRAPID.\n";
		my $send_to	= "To: ".$user_email."\n";
		open(SENDMAIL, "|$sendmail") or die "Cannot open $sendmail: $!";
		print SENDMAIL $from;
		print SENDMAIL $reply_to;
		print SENDMAIL $subject;
		print SENDMAIL $send_to;
		print SENDMAIL "Content-type: text/plain\n\n";
		print SENDMAIL $content;
		close(SENDMAIL);
		sleep 1;
		#}
	}
	$dbq->finish();

}



# Flag in the database all experiments which are to be deleted during the next check.
# For now, they are just flagged and not deleted yet.
sub flag_outdated_experiments($ $ $){
	my $dbh			= $_[0];
	my $cleanup_date_id	= $_[1];
	my @outdated_exps	= @{$_[2]};
	my $statement		= "INSERT INTO `cleanup_experiments` (`cleanup_date_id`,`experiment_id`) VALUES ('".$cleanup_date_id."',?) ";
	my $dbq			= $dbh->prepare($statement);
	foreach my $oe (@outdated_exps){
		#print STDOUT $oe."\n";
		$dbq->execute($oe);
	}
	$dbq->finish();
}



#get all experiments which are older (ie not edited) than X months, with X being the "no_access_limit"
sub get_outdated_experiments($ $ $ $){
	my $dbh			= $_[0];
	my $year		= $_[1];
	my $month		= $_[2];
	my $no_access_limit	= $_[3];

	my @result		= ();
	my $query		= "SELECT `experiment_id`,`last_edit_date` FROM `experiments`";
	my $dbq			= $dbh->prepare($query);
	$dbq->execute();
	while((my @record) 	= $dbq->fetchrow_array){
		my $exp_id	= $record[0];
		my $exp_date	= $record[1];
		my @exp_ym	= split("-",((split(" ",$exp_date))[0]));
		my $exp_year	= $exp_ym[0];
		my $exp_month	= $exp_ym[1];
		my $exp_months	= $exp_year*12 + $exp_month;
		my $curr_months	= $year*12 + $month;
		if(($curr_months - $no_access_limit)> $exp_months){
			#print $exp_id."\n";
			push(@result,$exp_id);
		}
		#print STDOUT $exp_date."\t".$exp_year."\t".$exp_month."\t".$exp_months."\n";
	}
	$dbq->finish();

	return @result;
}


sub get_delete_date_id($ $ $ $){
	my $dbh			= $_[0];
	my $year		= $_[1];
	my $month		= $_[2];
	my $delete_limit	= $_[3];
	my $result		= -1;

	#first step: get the correct deletion date for month and year (current cleanup date - `$delete_limit` months)
	my $fixed_month		= $month;
	my $fixed_year		= $year;
	for(my $i=0;$i<$delete_limit;$i++){
		$fixed_month	= $fixed_month-1;
		if($fixed_month==0){
			$fixed_year	= $fixed_year-1;
			$fixed_month	= 12;
		}
	}

	#second step: get the id for this correct month/year, if existing.
	my $query		= "SELECT `id` FROM `cleanup_date` WHERE `year`='".$fixed_year."' AND `month`='".$fixed_month."' ";
	my $dbq			= $dbh->prepare($query);
	$dbq->execute();
	if((my @record) = $dbq->fetchrow_array){
		$result		= $record[0];
	}
	else{
		#not present, return -1;
		$result		= -1;
	}
	$dbq->finish();

	print STDOUT "Delete date id : ".$result."\n";
	return $result;
}



#get id for the correct cleanup date
sub get_cleanup_date_id($ $ $){
	my $dbh			= $_[0];
	my $year		= $_[1];
	my $month		= $_[2];
	my $query		= "SELECT `id` FROM `cleanup_date` WHERE `year`='".$year."' AND `month`='".$month."' ";
	my $dbq			= $dbh->prepare($query);
	$dbq->execute();
	#has been added in PHP if not existing.
	if((my @record) = $dbq->fetchrow_array){
		my $id  	= $record[0];
		$dbq->finish();
		return $id;
	}
	else{
		print STDERR "Problem with getting correct cleanup id: no id present for year ".$year." and month ".$month."\n";
		$dbq->finish();
		die;

	}
	return -1;
}
