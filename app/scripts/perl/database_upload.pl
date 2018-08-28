#!/usr/local/bin/perl
use strict;
use warnings;
use DBI;
use Archive::Extract;
use File::Basename;

use FindBin qw{$Bin};

print STDOUT "START Database upload\n";

#basic parameter check
if(scalar(@ARGV)<8){
	print STDOUT "ERROR: parameters\n";
	exit;
}

#read parameters
my %par;

#TRAPID parameters
$par{"trapid_db_server"} 	= $ARGV[0];
$par{"trapid_db_name"}		= $ARGV[1];
$par{"trapid_db_port"}		= $ARGV[2];
$par{"trapid_db_user"}		= $ARGV[3];
$par{"trapid_db_password"}	= $ARGV[4];

#program parameters
$par{"upload_dir"}		= $ARGV[5];
$par{"experiment"}		= $ARGV[6];
$par{"base_scripts_location"}	= $ARGV[7];

#FIRST STEP: get all data in file format:
# - normal fasta files
# - unzip/untar archive files
# - download data from URL's
&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
            "transcript_database_upload","file_check",1);

#Get list from database
my @data_content = &read_database_uploads($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"});


my $MAX_FILE_SIZE_WGET = 400000000;


for(my $i=0;$i<scalar(@data_content);$i++){
        print STDOUT "\tDatabase upload:\n";
        print STDOUT "\t\tType: $data_content[$i][0]\n";
	print STDOUT "\t\tPath: $data_content[$i][1]\n";
	my $file_location = "";
	if($data_content[$i][0] eq "url"){
		#here we force the download of the file, through a wget operation
		my $url = $data_content[$i][1];
		#check whether URL is valid
		my $url_file_exists = `sh -c "wget --spider $url 2>&1 " | grep '404 Not Found' `;
		if ($url_file_exists ne ""){
		        print STDOUT "\t\tURL doesn't exist\n";
			&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    			$par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
           			"file_does_not_exist",$url,2);
			&update_data_uploads_status($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    			$par{"trapid_db_user"},$par{"trapid_db_password"},
				$data_content[$i][3],"error","File does not exist.");
		}
		else{
		        print STDOUT "\t\tURL exists \n";
			#check file size
		        #my $url_file_size = `sh -c "wget --spider $url 2>&1 | grep 'Length' " | awk '{print $2}' `;
		        my $url_file_size_string = `sh -c "wget --spider $url 2>&1 | grep 'Length' "  `;
			print STDOUT "\t\tURL size string : $url_file_size_string";
			my @url_file_size_array  = split(" ",$url_file_size_string);
			my $url_file_size        = $url_file_size_array[1];
			print STDOUT "\t\tURL size : $url_file_size\n";
			if ($url_file_size > $MAX_FILE_SIZE_WGET){
			        print STDOUT "\t\tURL size too large (max: $MAX_FILE_SIZE_WGET)\n";
				&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    				$par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
	           			"file_error_too_large",$url,2);
				&update_data_uploads_status($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    				$par{"trapid_db_user"},$par{"trapid_db_password"},
					$data_content[$i][3],"error","File size too big (".$url_file_size."). Max :".$MAX_FILE_SIZE_WGET);
			}
			else{
				#file size is ok, download file to correct location, and set path
				#get file name from url as well
				my $file_name  = substr $url, rindex($url,'/')+1;
				$file_location = $par{"upload_dir"}.$file_name;
				print STDOUT "\t\tURL size OK. \n";
				print STDOUT "\t\t\tDownload location : $file_location\n";
				if (-e $file_location){
				    print STDOUT "\t\t\tPrevious file instance existed: deleting\n";
				    #remove previous instance
				    `rm -f $file_location`;
				}
				print STDOUT "\t\t\tWget command : wget -O $file_location $url 2>&1 \n";
				my $t = `wget -O $file_location $url 2>&1 `;
			}
		}
 	}
	elsif($data_content[$i][0] eq "file"){
		$file_location = $par{"upload_dir"}.$data_content[$i][1];
	}

	# Trying to fix the 'infinite upload status' issue (Gitlab issue #1)
	# Wait before upload
	sleep 3;

	#next step, check the file extension. If zip or gz, try to extract the content in the upload folder
	my ($name,$path,$suffix) = fileparse($file_location,qr"\..[^.]*$");
	#archive: unzip
	if($suffix eq ".zip" || $suffix eq ".gz"){
		my $ae = Archive::Extract->new(archive=>$file_location);
		if($ae->is_tgz || $ae->is_gz || $ae->is_zip){
			my $target_unzip_dir = $par{"upload_dir"}.$name."/";
			`mkdir $target_unzip_dir `;
			`chmod 0777 $target_unzip_dir `;
			my $ok = $ae->extract(to=>$target_unzip_dir);
			$file_location = $target_unzip_dir;
		}
		# Trying to fix the 'infinite upload status' issue (Gitlab issue #1)
		# Wait after uncompressing file
		sleep 2;
	}


	#ok, now check the file location,if it is a file, read contents and upload to data.
	#if it is a directory, go over the files in it, and read their content.
	if ($file_location ne ""){
		if (-f $file_location){
			&upload_file_content($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
				    	$par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
				    	$file_location,$data_content[$i][2],$data_content[$i][3]);
		}
		else{
			opendir(D,$file_location);
			while(my $f = readdir(D)){
				if ($f ne "." && $f ne ".."){
					&upload_file_content($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
				    		$par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
					    	$file_location."/".$f,$data_content[$i][2],$data_content[$i][3]);
				}
			}
			closedir(D);
		}
	}
}

#finally, update log again and set the experiment status to  "upload"

&update_log($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
            "transcript_database_upload","complete",1);

#remove all data_upload entries from the experiment which are not in error phase
#&remove_file_entries($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
#	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"});

&update_status($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
	    "upload");

&delete_current_job($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"},
	    "database_upload");


&send_email($par{"trapid_db_server"},$par{"trapid_db_name"},$par{"trapid_db_port"},
	    $par{"trapid_db_user"},$par{"trapid_db_password"},$par{"experiment"});

#remove contents of the upload directory.
`rm -rf $par{"upload_dir"}/* `;

print STDOUT "STOP Database Upload\n";

exit;


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
  my $query  = "SELECT a.`title`,b.`email`,count(c.`transcript_id`) FROM `experiments` a,`authentication` b, `transcripts` c WHERE a.`experiment_id`=? AND b.`user_id`=a.`user_id` AND c.`experiment_id`=a.`experiment_id` GROUP BY a.`experiment_id`";
  my $dbq			= $dbh->prepare($query);
  $dbq->execute($experiment_id);
  while((my @record) = $dbq->fetchrow_array){
	  my $experiment_title  	= $record[0];
	  my $user_email        	= $record[1];
	  my $transcript_count	= $record[2];

  	my $sendmail 		= "/usr/lib/sendmail.postfix -t";
	  my $from			= "From: TRAPID webmaster <no-reply\@psb.vib-ugent.be>\n";
	  my $reply_to 		= "Reply-to: no-reply\@psb.vib-ugent.be\n";
	  my $subject		= "Subject: TRAPID experiment has finished upload phase\n";
	  my $content		= "Dear user,\nYour TRAPID experiment titled '".$experiment_title."' has finished its upload phase.\n";
	  $content		= $content."A total of ".$transcript_count." transcripts has been uploaded in your TRAPID experiment.\n";
	  $content		= $content."You can now log in into TRAPID, and begin the analysis of your transcriptome dataset.\n";
	  # $content		= $content."You can access TRAPID at http://bioinformatics.psb.ugent.be/webtools/trapid/ \n";
	  $content		= $content."You can access TRAPID at http://bioinformatics.psb.ugent.be/testix/trapid_dev/ \n";
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
	  print STDOUT "Confirmation email send to : $user_email\n";
  }
  # Close handlers and return name of multi-fasta file
  $dbq->finish();
  $dbh->disconnect();
  return;
}


sub remove_file_entries($ $ $ $ $ $){
	my $trapid_db_server	= $_[0];
	my $trapid_db_name	= $_[1];
	my $trapid_db_port	= $_[2];
	my $trapid_db_user	= $_[3];
	my $trapid_db_password	= $_[4];
	my $experiment_id	= $_[5];

	my $dsn			= qq{DBI:mysql:$trapid_db_name:$trapid_db_server:$trapid_db_port};
	my $dbh			= DBI->connect($dsn,$trapid_db_user,$trapid_db_password,{RaiseError=>1,AutoCommit=>1});
	if($dbh->err){print STDERR "Cannot create database connection during database uploads retrieval";exit;}
	my $dbq			= $dbh->prepare("DELETE FROM `data_uploads` WHERE `experiment_id`=? AND `status`!='error' ");
	$dbq->execute($experiment_id);
	$dbq->finish();
	$dbh->disconnect();
}


sub update_status($ $ $ $ $ $ $){
	my $trapid_db_server	= $_[0];
	my $trapid_db_name	= $_[1];
	my $trapid_db_port	= $_[2];
	my $trapid_db_user	= $_[3];
	my $trapid_db_password	= $_[4];
	my $experiment_id	= $_[5];
	my $new_status		= $_[6];

	my $dsn			= qq{DBI:mysql:$trapid_db_name:$trapid_db_server:$trapid_db_port};
	my $dbh			= DBI->connect($dsn,$trapid_db_user,$trapid_db_password,{RaiseError=>1,AutoCommit=>1});
	if($dbh->err){print STDERR "Cannot create database connection during database uploads retrieval";exit;}
	my $dbq			= $dbh->prepare("UPDATE `experiments` SET `process_state`=? WHERE `experiment_id`=?");
	$dbq->execute($new_status,$experiment_id);
	$dbq->finish();
	$dbh->disconnect();
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


sub upload_file_content($ $ $ $ $ $ $ $){
	my $trapid_db_server	= $_[0];
	my $trapid_db_name	= $_[1];
	my $trapid_db_port	= $_[2];
	my $trapid_db_user	= $_[3];
	my $trapid_db_password	= $_[4];
	my $experiment_id	= $_[5];
	my $file_path		= $_[6];
	my $label		= $_[7];
	my $du_id		= $_[8];

	#print $file_path."\n";
	#return;
	print STDOUT "Dos2unixing file $file_path\n";
	#`dos2unix $file_path`;

	#print "performing execution for file : ".$file_path."\n";
	my $dsn			= qq{DBI:mysql:$trapid_db_name:$trapid_db_server:$trapid_db_port};
	my $dbh			= DBI->connect($dsn,$trapid_db_user,$trapid_db_password,{RaiseError=>1,AutoCommit=>1});
	if($dbh->err){print STDERR "Cannot create database connection during database uploads retrieval";exit;}
	my $dbq			= $dbh->prepare("INSERT INTO `transcripts` (`experiment_id`,`transcript_id`,`transcript_sequence`) VALUES (?,?,COMPRESS(?)) ");
	my @transcript_ids	= ();
	my $current_transcript	= "";
	my $current_sequence	= "";
	print STDOUT "\t\tFull-path: $file_path\n";
	print STDOUT "\t\tReading file and uploading data:\n";
	open FILE,"<",$file_path;
	while (my $line = <FILE>){
		chomp($line);
		#indication of new transcript
		if(substr($line,0,1) eq ">"){
			my $transcript_id	= substr($line,1);
			$transcript_id	= (split(" ",$transcript_id))[0];
			$transcript_id	= (split("\\|",$transcript_id))[0];
			if($current_transcript ne ""){
				#load data into database
				$dbq->execute($experiment_id,$current_transcript,$current_sequence);
				print STDOUT "\t\t\t".$current_transcript."\n";
			}
			$current_transcript = $transcript_id;
			$current_sequence = "";
			push @transcript_ids,$transcript_id;
		}
		else{
			$current_sequence	= $current_sequence."".uc($line);
		}
	}
	close FILE;

	#also add the last transcript
	$dbq->execute($experiment_id,$current_transcript,$current_sequence);
	$dbq->finish();

	if(defined($label)){ #undef == NULL values from database
		my $dbq2 = $dbh->prepare("INSERT INTO `transcripts_labels` (`experiment_id`,`transcript_id`,`label`) VALUES (?,?,?) ");
		for my $transcript_id(@transcript_ids){
			$dbq2->execute($experiment_id,$transcript_id,$label);
		}
		$dbq2->finish();
	}

	$dbh->disconnect();

	&update_log($trapid_db_server,$trapid_db_name,$trapid_db_port,
	    $trapid_db_user,$trapid_db_password,$experiment_id,
            "file_upload_done",basename($file_path),2);

}


sub read_database_uploads($ $ $ $ $ $){
	my $trapid_db_server	= $_[0];
	my $trapid_db_name	= $_[1];
	my $trapid_db_port	= $_[2];
	my $trapid_db_user	= $_[3];
	my $trapid_db_password	= $_[4];
	my $experiment_id	= $_[5];

	my $dsn			= qq{DBI:mysql:$trapid_db_name:$trapid_db_server:$trapid_db_port};
	my $dbh			= DBI->connect($dsn,$trapid_db_user,$trapid_db_password,{RaiseError=>1,AutoCommit=>1});
	if($dbh->err){print STDERR "Cannot create database connection during database uploads retrieval";exit;}
	my $query 		= "SELECT `name`,`label`,`type`,`id` FROM `data_uploads` WHERE `experiment_id`='".$experiment_id."' AND `status`!='error' ";
	my $dbq			= $dbh->prepare($query);
	$dbq->execute();
	my @result		= ();
	my $counter		= 0;
	while((my @record) = $dbq->fetchrow_array){
		my $du_name		= $record[0];
		my $du_label		= $record[1];
		my $du_type	 	= $record[2];
		my $du_id		= $record[3];
		push @{$result[$counter]},$du_type;
		push @{$result[$counter]},$du_name;
		push @{$result[$counter]},$du_label;
		push @{$result[$counter]},$du_id;
		$counter++;
	}
	$dbq->finish();
	$dbh->disconnect();
	return @result;
}

sub update_data_uploads_status($ $ $ $ $ $ $ $){
	my $trapid_db_server	= $_[0];
	my $trapid_db_name	= $_[1];
	my $trapid_db_port	= $_[2];
	my $trapid_db_user	= $_[3];
	my $trapid_db_password	= $_[4];
	my $id			= $_[5];
	my $new_status		= $_[6];
	my $new_comment		= $_[7];
	#print STDOUT $id."\n";
	#print STDOUT $new_status."\n";
	#print STDOUT $new_comment."\n";
	my $dsn			= qq{DBI:mysql:$trapid_db_name:$trapid_db_server:$trapid_db_port};
	my $dbh			= DBI->connect($dsn,$trapid_db_user,$trapid_db_password,{RaiseError=>1,AutoCommit=>1});
	if($dbh->err){print STDERR "Cannot create database connection during database uploads retrieval";exit;}
	my $query		= "UPDATE `data_uploads` SET `status`='".$new_status."',`comment`='".$new_comment."' WHERE `id`='".$id."' ";
	my $dbq			= $dbh->prepare($query);
	$dbq->execute();
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
	print STDOUT $query;
	my $dbq			= $dbh->prepare($query);
	$dbq->execute();
	$dbq->finish();
	$dbh->disconnect();
	return;
}
