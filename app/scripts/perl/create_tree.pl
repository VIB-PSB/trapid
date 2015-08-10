#!/usr/local/bin/perl
use strict;
use warnings;
use DBI;
use POSIX;
use Statistics::Descriptive;
use FindBin qw{$Bin};

### This perl-script contains the necessary code 
### to create a phylogenetic tree
### 
### Creation of the tree itself is done by an external program.
### The correct modules need as such be loaded.
###
our %idswitch;

our $PHYML_prot_settings 	= "-m WAG -f e -c 4 -a e"; #phyml 3.0 aa settings NO PARAMETER OPTIMIZATION DEFINED
our $FASTTREE_prot_settings 	= "-wag -gamma ";

#our $PHYML_prot_settings = "-m WAG -f e -c 4 -a e -o n"; #phyml 3.0 aa settings NO PARAMETER OPTIMIZED
#our $PHYML_prot_settings2 = "-m WAG -f e -c 4 -a e -o tl"; #phyml 3.0 aa settings TOPOLOGY + BL OPTIMIZED

#=======================================================================================================
#=======================================================================================================
#read parameters
my %par;

if(scalar(@ARGV)!=10){
    die "Parameters: trapid-db-settings tmp_dir exp_id gf_id bootstraps";
}

#TRAPID database parameters
$par{"trapid_db_server"}	= $ARGV[0];
$par{"trapid_db_name"}		= $ARGV[1];
$par{"trapid_db_port"}		= $ARGV[2];
$par{"trapid_db_user"}		= $ARGV[3];
$par{"trapid_db_password"}	= $ARGV[4];

#storage parameter
$par{"temp_dir"}		= $ARGV[5];

#experiment settings
$par{"experiment_id"}           = $ARGV[6];

#gene family for which to create the MSA
$par{"gf_id"}                   = $ARGV[7];

#bootstrap variable
$par{"bootstrap_mode"}          = $ARGV[8];

#optimization variable
#$par{"optimization_mode"}	= $ARGV[9];
$par{"tree_program"}		= $ARGV[9];



#=======================================================================================================
#=======================================================================================================
# First step : create 2 database connections. 
#  - we keep these open at first, to retrieve the necessary data. 
#  - during MSA construction they should be closed.
#  - after MSA construction the trapid connection should be opened again for storing results.
#=======================================================================================================
#=======================================================================================================
my $dsn_trapid		= qq{DBI:mysql:$par{"trapid_db_name"}:$par{"trapid_db_server"}:$par{"trapid_db_port"}};
my $dbh_trapid		= DBI->connect($dsn_trapid,$par{"trapid_db_user"},$par{"trapid_db_password"},{RaiseError=>1,AutoCommit=>1});	
if($dbh_trapid->err){
	print STDOUT "ERROR: Cannot connect with TRAPID database\n";
	exit;	
}

my $stime1                   = time();
my %gf_information           = %{&retrieve_gf_information($dbh_trapid,$par{"experiment_id"},$par{"gf_id"})};
#disconnect because phyml might take a while
$dbh_trapid->disconnect();


my $outtree                  = &create_tree($par{"experiment_id"},$par{"gf_id"},$gf_information{"stripped_msa"},$par{"bootstrap_mode"},$par{"tree_program"},$par{"temp_dir"});

$dbh_trapid		= DBI->connect($dsn_trapid,$par{"trapid_db_user"},$par{"trapid_db_password"},{RaiseError=>1,AutoCommit=>1});	
if($dbh_trapid->err){
	print STDOUT "ERROR: Cannot connect with TRAPID database\n";
	exit;	
}
&store_tree($dbh_trapid,$par{"experiment_id"},$par{"gf_id"},$outtree);

&delete_current_job($dbh_trapid,$par{"experiment_id"},$par{"gf_id"});
&send_email($dbh_trapid,$par{"experiment_id"},$par{"gf_id"});

$dbh_trapid->disconnect();


my $stime2                   = time();




#=======================================================================================================
#=======================================================================================================
# Create phylo tree
#=======================================================================================================
#=======================================================================================================



sub create_tree ($ $ $ $ $ $){
    my $exp_id              = $_[0];
    my $gf_id               = $_[1];
    my $stripped_msa        = $_[2];
    my $bootstrap_mode      = $_[3];
    my $tree_program	    = $_[4];	
    my $tmp_dir             = $_[5];
    my $outtree;
    
    print STDERR "Running algorithm $tree_program on gene family $gf_id from experiment $exp_id";
                
    #create tree by running phyml    
    if($tree_program eq "phyml"){    
	    my $phylip_file         = $tmp_dir."phylip_".$exp_id."_".$gf_id.".phy";   
	    if (! &faln2phylip2file($stripped_msa,$phylip_file) ){  #names are converted using %idswitch!
		print STDERR "* Empty strip_aln; quit!\n";
		return("0");
	    }        	    
	    $outtree = &_run_phyml($phylip_file,$bootstrap_mode)."\n";	
	    #remove temp files
    	    system("rm -f $phylip_file*");   
    }
    #create tree by running fasttree
    elsif($tree_program eq "fasttree"){
    	    my $stripped_msa_file = $tmp_dir."stripped_msa_".$exp_id."_".$gf_id.".fasta";
	    if(! &create_msa_file($stripped_msa,$stripped_msa_file)){
	    	print STDERR "* Empty strip_aln; quit!\n";
	    }
       	    $outtree = &_run_fasttree($stripped_msa_file,$bootstrap_mode)."\n";
	    #rempove tmp files
	    system("rm -f $stripped_msa_file*");
    }

    return $outtree;
}



sub _run_fasttree ( $ $){
 my $fin           = $_[0]; 
 my $bs            = $_[1];
 print STDERR "Running FastTree with input file : $fin\n";
 
 my $error_file_location = $fin."_fasttree_error.txt";
 my $fout          = $fin."_fasttree_tree.txt";
 
 my $command = "FastTree $FASTTREE_prot_settings  $fin > $fout 2> $error_file_location";
 print STDERR "Command for fasttree : ".$command."\n";
 system($command);
 my $result        = 0; 
 my $constree;
 if(! -e $fout){
     print STDERR "ERROR: Cannot open fasttree output file $fout\n";
     return $result;
 } 
 print STDERR "Parsing output tree from $fout \n";
 open (TREE,$fout);
 while (<TREE>){
  chomp;
  $constree.=$_;
 }
 close TREE;
 return $constree; 
}




sub _run_phyml ( $ $){
 #print STDERR "SUB run_phyml @_\n";
 #print STDERR "DES run_phyml :: runs phyml aa for file \$_[0] and returns Newick_bootstrap-like_tree\n";
 my $fin           = $_[0]; 
 my $bs            = $_[1];	
 print STDERR "Running Phyml with input file : $fin\n";
 my $error_file_location = $fin."_phyml_error.txt";
 my $fout          = $fin."_phyml_tree.txt";
 system("phyml -i $fin -d aa  -n 1 -b $bs $PHYML_prot_settings 2>&1 > $error_file_location"); 
 my $result        = 0; 
 my $constree;
 if(! -e $fout){
     print STDERR "ERROR: Cannot open phyml output file $fout\n";
     return $result;
 } 
 print STDERR "Parsing output tree from $fout \n";
 open (TREE,$fout);
 while (<TREE>){
  chomp;
  $constree.=$_;
 }
 close TREE;
 $constree =~ s/_//g;

 #restore original gene_id	 
 my @revert = split(/:/,$constree);
 my @revert2;
 foreach my $e (@revert)
  {
  if ($e =~ /^(.+)(X\d+X)(.+)$/)
   {
   if (exists $idswitch{$2})
    {
    $e = $1.$idswitch{$2}.$3;
    }
   }
  if ($e =~ /^(X\d+X)(.+)$/)
   {
   if (exists $idswitch{$1})
    {
    $e = $idswitch{$1}.$2;
    }
   }
  if ($e =~ /^(.+)(X\d+X)$/)
   {
   if (exists $idswitch{$2})
    {
    $e = $1.$idswitch{$2};
    }
   }
   push @revert2, $e;
  }
 return(join(':',@revert2)); 
}


#=======================================================================================================
#=======================================================================================================
# UTIL FUNCTIONS :
#   - writing stripped multiple sequence alignment in different form to file
#=======================================================================================================
#=======================================================================================================


#simply write the multiple sequence file in fasta format to the 
sub create_msa_file( $ $){
	my $in = $_[0];
	my $fout = $_[1];
	open (FOUT,">$fout");
	my $in_adapt = $in;
	$in_adapt =~ s/;/\n/g;
	$in_adapt =~ s/>/\n>/g;
	$in_adapt =~ s/\n//;
	print FOUT $in_adapt;
	close FOUT;
	return '1';
}


sub faln2phylip2file ( $ $ )
 {
 #print STDERR "SUB faln2phylip2file \$_[0] $_[1]\n";
 #print STDERR "DES faln2phylip2file :: reads aln string \$_[0] and writes phylip interleaved alignment to file $_[1]\n"; 
 my $in = $_[0];
 my $fout = $_[1];
 my($numberseq,%bib,$woord,@seq,%vollseq,$k,$blocks,$blocksint,$l,@word,$i);
 my($m,$z,$o,$j,$letters,$konijn);
 my $tekens=0;
 
# print STDERR "Cleaning /%idswitch before adding new genes!\n";
 #undef %idswitch;
 
 my @convert = split(/>/,$in);
 $numberseq = 0;
 foreach my $ac (@convert)
  {
  if (! $ac) { next };
  $numberseq++;
  my @string = split(/;/,$ac);
  my $short = "X".sprintf("%06d",$numberseq)."X";
  $idswitch{$string[0]}=$short;
  $idswitch{$short}=$string[0];  
  #print STDERR "$string[0]=$short ";
  $bib{$numberseq} = $idswitch{$string[0]}; # so bib contains as key the seq nr 1 and as value 000000001
  @{$vollseq{$idswitch{$string[0]}}} = split (//, $string[1]);  
  $tekens = scalar @{$vollseq{$idswitch{$string[0]}}};		
  }
 
if ($tekens == 0)
 {
 return(0); 
 }
 
open (FOUT, ">$fout");
$blocks = $tekens/50;
$blocksint = ceil($blocks);
$konijn=0;

if ($blocksint > 1)
	{
	print FOUT "$numberseq $tekens\n";
	foreach $l(1..$numberseq)
		{
		@word = split (//, $bib{$l});
		$letters = scalar(@word);
		foreach $i(1..10-$letters)
			{
			print FOUT " ";
			}
		print FOUT $bib{$l}." ";
		foreach $j(0..49) # prints first block of 50 residues
			{
			print FOUT "${$vollseq{$bib{$l}}}[$j]";
			}
		print FOUT "\n"
		}

	foreach $z(1..$blocksint-2)
		{
		print FOUT "\n";
		foreach $m(1..$numberseq)
			{
			foreach $k($z*50..(($z+1)*50)-1)
				{
				print FOUT "${$vollseq{$bib{$m}}}[$k]";
				}
			print FOUT "\n";
			}
		$konijn = $z;
		}
	print FOUT "\n";

	foreach $o(1..$numberseq)
		{
		foreach $b((($konijn+1)*50)..$tekens-1)
			{
			print FOUT "${$vollseq{$bib{$o}}}[$b]";
			}
		print FOUT "\n";
		}	
	}

else
	{
	print FOUT "$numberseq $tekens\n";
	foreach $l(1..$numberseq)
		{
		@word = split (//, $bib{$l});
		$letters = scalar(@word);
		foreach $i(1..10-$letters)
			{
			print FOUT " ";
			}
		print FOUT $bib{$l}." ";
		foreach $j(0..$tekens-1)
			{
			print FOUT "${$vollseq{$bib{$l}}}[$j]";
			}
		print FOUT "\n"
		}
	}
 close FOUT;
 return('1');
 }


#=======================================================================================================
#=======================================================================================================
# DATABASE FUNCTIONS : 
#  - STORING DATA 
#=======================================================================================================
#=======================================================================================================

sub store_tree ($ $ $ $){
    my $dbh_trapid          = $_[0];		       
    my $experiment_id       = $_[1];
    my $trapid_gf_id        = $_[2];
    my $tree                = $_[3];
    my $statement           = "UPDATE `gene_families` SET `tree`='".$tree."' WHERE `experiment_id`='".$experiment_id."' 
                              AND `gf_id`='".$trapid_gf_id."' ";
    my $dbq                 = $dbh_trapid->prepare($statement);
    $dbq->execute();
    $dbq->finish();
}


sub delete_current_job($ $ $){
	my $dbh_trapid		= $_[0];
	my $experiment_id	= $_[1];
	my $gf_id		= $_[2];
	my $dbq			= $dbh_trapid->prepare("DELETE FROM `experiment_jobs` WHERE `experiment_id`=? AND `comment`=?");
	my $comment		= "create_tree ".$gf_id;
	$dbq->execute($experiment_id,$comment);	
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
	my $subject		= "Subject: TRAPID phylogenetic tree finished for ".$gf_id."\n";
	my $content		= "Dear user,\nThe phylogenetic tree for gene family '".$gf_id."' in experiment '".$experiment_title."' has been created.\n";
	$content		= $content."You can now view the phylogenetic tree at this URL:\n";
	$content		= $content."http://bioinformatics.psb.ugent.be/webtools/trapid/tools/create_tree/".$experiment_id."/".$gf_id." \n";
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


#=======================================================================================================
#=======================================================================================================
# DATABASE FUNCTIONS : 
#  - RETRIEVING DATA 
#=======================================================================================================
#=======================================================================================================


#Retrieve gene family information from the trapid database
sub retrieve_gf_information($ $ $){
        my %result;
	my $dbh_trapid          = $_[0];		       
	my $experiment_id       = $_[1];
	my $trapid_gf_id        = $_[2];
	my $query               = "SELECT `plaza_gf_id`,`gf_content`,`msa_stripped` FROM `gene_families` 
                                   WHERE `experiment_id`='".$experiment_id."' AND `gf_id`='".$trapid_gf_id."' ";
	my $dbq                 = $dbh_trapid->prepare($query);
	$dbq->execute();
	while((my @record) = $dbq->fetchrow_array){
	    my $plaza_gf_id         = $record[0];
	    my $gf_content          = $record[1];
	    my $stripped_msa        = $record[2];
	    $result{"plaza_gf_id"}  = $plaza_gf_id;
	    $result{"gf_content"}   = $gf_content;	   
	    $result{"stripped_msa"} = $stripped_msa;
	}	
	#close handlers 
	$dbq->finish();
	return \%result;
}
