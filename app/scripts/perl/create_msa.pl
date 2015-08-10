#!/usr/local/bin/perl
use strict;
use warnings;
use DBI;
use POSIX;
use Statistics::Descriptive;
use FindBin qw{$Bin};

#general settings
our $alnmethod = 'muscle';
our %blosum;
our @strip_params = qw(0.1 50 1); # Default = 0.1 50 1
our ($max_gap_portion,$percentile,$per_codon)= @strip_params;

### This perl-script contains the necessary code 
### to create a multiple sequence alignment (this can later be used to create trees as well).
### 
### Creation of the MSA itself is done by an external program.
### The correct modules need as such be loaded.
###


#=======================================================================================================
#=======================================================================================================
#read parameters
my %par;
my $perform_msa_stripping       = 0;

#PLAZA database parameters. Necessary for retrieving sequences from other species
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
$par{"experiment_id"}           = $ARGV[11];

#gene family for which to create the MSA
$par{"gf_id"}                   = $ARGV[12];


#location of BLOSSUM FILE 
$par{"blossum_file"}            = $ARGV[13];

if(scalar(@ARGV) == 15){
    #editing mode variable          
    $par{"editing_mode"}            = $ARGV[14];
    $max_gap_portion                = $par{"editing_mode"};
    $perform_msa_stripping          = 1;
}





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
my $dsn_plaza		= qq{DBI:mysql:$par{"plaza_db_name"}:$par{"plaza_db_server"}:$par{"plaza_db_port"}};
my $dbh_plaza		= DBI->connect($dsn_plaza,$par{"plaza_db_user"},$par{"plaza_db_password"},{RaiseError=>1,AutoCommit=>1});	
if($dbh_plaza->err){
	print STDOUT "ERROR: Cannot connect with PLAZA database\n";
	exit;	
}






#=======================================================================================================
#=======================================================================================================
# Second step : retrieve basic information on experiment:
#  - what type of sequences are in PLAZA database
#  - what type of gene family for the experiment (IORTHO OR HOM)
#=======================================================================================================
#=======================================================================================================
my $basic_information          = &get_basic_information($dbh_trapid,$par{"experiment_id"});
my $gf_type                    = $basic_information->{"gf_type"};
my $seq_type                   = $basic_information->{"seq_type"};





#=======================================================================================================
#=======================================================================================================
# Third step : create FASTA file containing the necessary sequences:
# - sequences from PLAZA gene family (or IORTHO) group, but filtered by species
# - transcript sequences      
# Important to note: 
# - transcript sequences need to be translated first (use ORF_SEQUENCE from table TRANSCRIPTS)
# - PLAZA sequences may have to be translated, based on settings from DATA_SOURCES table
#=======================================================================================================
#=======================================================================================================


my $stime1                   = time();
my $gf_information           = &retrieve_gf_information($dbh_trapid,$par{"experiment_id"},$par{"gf_id"});
#retrieve used species from gf_information. This is a string of TAX-ids!! (for database storage reasons).
my @used_species             = @{&get_used_species($dbh_plaza,$gf_information->{"used_species"})};

my @exclude_transcripts      = ();

if($perform_msa_stripping){ #only during tree construction --> msa strippig can transcripts be excluded.
    @exclude_transcripts     = split(",",$gf_information->{"exclude_transcripts"});
}

my %plaza_sequences;
if($gf_type eq "HOM"){     
    %plaza_sequences         = %{&retrieve_sequences_plaza_hom($dbh_plaza,$gf_information->{"plaza_gf_id"},\@used_species)};
}
elsif($gf_type eq "IORTHO"){
    %plaza_sequences         = %{&retrieve_sequences_plaza_iorth($dbh_plaza,$gf_information->{"gf_content"},\@used_species)};
}

#need to translate the sequences first to amino acid representation, if necessary
if($seq_type eq "DNA"){
    %plaza_sequences         = %{&translate_sequences(\%plaza_sequences)};
}

#retrieve trapid sequences
my %trapid_sequences         = %{&retrieve_sequences_trapid($dbh_trapid,$par{"experiment_id"},$par{"gf_id"})};
%trapid_sequences            = %{&translate_sequences(\%trapid_sequences)};


#write sequences to temporary file
my $multi_fasta_file         = $par{"temp_dir"}."multi_fasta_".$par{"experiment_id"}."_".$par{"gf_id"}.".fasta";
if(-e $multi_fasta_file){unlink($multi_fasta_file);}
print STDOUT "Creating multifasta file\n";
&create_multi_fasta($multi_fasta_file,\%trapid_sequences,\%plaza_sequences,\@exclude_transcripts);


#=======================================================================================================
#=======================================================================================================
# Close database connections before executing MSA
#=======================================================================================================
#=======================================================================================================
$dbh_plaza->disconnect();
$dbh_trapid->disconnect();



#=======================================================================================================
#=======================================================================================================
# Execute actual MSA creation
#  - execute external alignement
#  - remove temp-files
#  - perform alignment stripping.
#  - loading blossum matrix
#=======================================================================================================
#=======================================================================================================

print STDOUT "Alignment file creation\n";
print STDOUT "Creating alignment file using $alnmethod\n";
my $aligned_file             = $par{"temp_dir"}."aligned_file_".$par{"experiment_id"}."_".$par{"gf_id"}.".faln";
if(-e $aligned_file){unlink($aligned_file);}
if ($alnmethod eq 'muscle'){
   print STDOUT "muscle -in $multi_fasta_file -out $aligned_file -maxiters 3\n";
   system("muscle -in $multi_fasta_file -out $aligned_file -maxiters 3");
}  
if ($alnmethod eq 't_coffee'){
   print STDOUT "t_coffee -infile $multi_fasta_file -outfile $aligned_file -output=fasta_aln\n";
   #system("t_coffee -infile $multi_fasta_file -outfile $aligned_file -output=fasta_aln");
}


my @msa_data;
if($perform_msa_stripping){
    print STDOUT "Postprocessing : stripped alignement\n";
    #load blossum content
    print STDOUT "Creating Blosum matrix from ".$par{"blossum_file"}."\n";
    %blosum                      = &read_blosum($par{"blossum_file"});

    #strip multiple sequence alignement
    print STDOUT "Creating stripped alignment\n";
    #($msa,$msa_strip, $alnmethod, $msa_length, $msa_strip_length,join(" ",@strip_params));
    @msa_data                    = &strip_msa($aligned_file);   
}
else{
    @msa_data                    = &load_msa($aligned_file);    
}

#print STDOUT "Number of entries in msa align file :\n";
#print STDOUT scalar(@msa_data)."\n\n";
#print STDOUT "non-stripped data :\n\n";
#print STDOUT $msa_data[0]."\n\n";
#print STDOUT "stripped data:\n\n";
#print STDOUT $msa_data[1]."\n\n";


#=======================================================================================================
#=======================================================================================================
# Open TRAPID DB connection, and store the result.
#=======================================================================================================
#=======================================================================================================
$dbh_trapid		= DBI->connect($dsn_trapid,$par{"trapid_db_user"},$par{"trapid_db_password"},{RaiseError=>1,AutoCommit=>1});	
if($dbh_trapid->err){
    print STDOUT "ERROR: Cannot connect with TRAPID database\n";
    exit;	
}
&store_msa_results($dbh_trapid,$par{"experiment_id"},$par{"gf_id"},\@msa_data);


#=======================================================================================================
#=======================================================================================================
# indicate in the database that the current running job is finished and send email
#=======================================================================================================
#=======================================================================================================
&delete_current_job($dbh_trapid,$par{"experiment_id"},$par{"gf_id"});
if(!$perform_msa_stripping){
	&send_email($dbh_trapid,$par{"experiment_id"},$par{"gf_id"});
}


#Close the database connection
$dbh_trapid->disconnect();

#removing multi_fasta_file, as it is no longer necessary
print STDOUT "Removing files\n";
system("rm -f $multi_fasta_file");
system("rm -f $aligned_file");

1;










#=======================================================================================================
#=======================================================================================================
# MSA FUNCTIONS :
#   - LOADING MSA ALIGNMENT FROM FILE
#   - STRIPPING MSA ALIGNMENT
#=======================================================================================================
#=======================================================================================================

sub load_msa{
    my $fin            = $_[0];
    my (%align); 
    %align=&input2hash($fin);      
    # store raw msa
    my $msa;
    foreach my $gene_id (keys %align){
      $msa.= ">$gene_id;$align{$gene_id}";
    }
    print STDOUT $msa."\n";
    return($msa);
}



sub strip_msa{
  my $fin              = $_[0];   
  
  my ($c,$i,$last_anchor);
  my (@align,@ids);
  my (%align);
 
  %align=&input2hash($fin);    
  
  # store raw msa
  my $msa;
  my $msa_length;
  foreach my $gene_id (keys %align){
      $msa.= ">$gene_id;$align{$gene_id}";
      $msa_length=length($align{$gene_id});
  }

  #print $msa."\n";
  #die;

  #print $per_codon."\n";
  #die;


  #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  # Construct a data structure for editing the alignment
  #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  $c=0;
  foreach (keys %align){ 
      $ids[$c]=$_; # keeps track of original acc; converts to index $c
      my $n=0;
      for ($i=0;$i<length($align{$_});$i+=$per_codon){
	  # @align is double array; first index $n is position, second index $c represents AC
	  $align[$n][$c]=substr($align{$_},$i,$per_codon);
	  $n++;
      } #for ($i=0;$i<length($align{$_};$i+=$per_codon)
      $c++; 
  } #foreach (keys %align)

  
  #die;

  #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  # Strip the alignment
  #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  $last_anchor=-1;

  #print STDOUT scalar(@align)."\n";
  #die;  
  for ( $i=0;$i<scalar(@align);$i++ ){ # goes over positions      
         
      #print STDOUT @{$align[$i]}."\n";
      if (&anchor( @{$align[$i]} )){     
	  $last_anchor=$i;      
      }      
      #print "position : ".$i."\n";
      if ( &gap( @{$align[$i]} ) || ( $last_anchor == -1 ) ){       
	  #while ( !&anchor( @{$align[$last_anchor]} ) && ( $last_anchor > -1 ) ) {$last_anchor--}
	  $last_anchor++;
	  do{
	      splice(@align,$last_anchor,1);
	     # print STDERR "\t"."pos\t".$i."\t"."align_size\t".scalar(@{$align[$i]})."\n";
	  } until ( ($last_anchor == scalar(@align)) || &anchor(@{$align[$last_anchor]}) );
	  $i=$last_anchor;
      } #if (&gap(@{$align[$i]))
  } #for ($i=0;$i<scalar(@align);$i++)
  ( $last_anchor < $#align ) && splice (@align,$last_anchor+1); 
  
  

  #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  # Store the edited alignment again in the original hash structure
  #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  %align=();
  foreach (@align){
      for ($i=0;$i<scalar(@{$_});$i++){
	  $align{$ids[$i]}.=$$_[$i];
      } #for ($i=0;$i<scalar(@{$_});$i++)
  } #foreach (@align)
    
  my $msa_strip;
  my $msa_strip_length;
  foreach my $gene_id (keys %align){
      $msa_strip.= ">$gene_id;$align{$gene_id}";
      $msa_strip_length=length($align{$gene_id});
  }   

  #print STDERR "MSA\n$msa\nMSA_STRIP\n$msa_strip\n";   
  return($msa,$msa_strip, $alnmethod, $msa_length, $msa_strip_length,join(" ",@strip_params));
 }



sub input2hash ( $ ){
  my ($key);
  my (%fasta_hash);
  my $fin = $_[0];
  open (FIN, "$fin");
  while (<FIN>){
    chomp;
    if (/^>(\S+)/){
      $key=$1;
    } #if (/^>(\w)$/)
    else
     {
      $key || die "Input is not in fasta format!";
      s/\s+//g;   
      $fasta_hash{$key}.=$_;
     } #else
   } #while (<STDIN>)
  close FIN;
  return (%fasta_hash);
 }



sub read_blosum ($){
    my $blosum_file = $_[0];
    my (@residus);
    my (%matrix);
    open (IN,$blosum_file) || die "Could not open $blosum_file: $!\n";
    (undef,@residus)=split(/[\t\n]/,<IN>);
    while (<IN>){
	chomp;
	my ($this,@row)=split(/\t/);
	@{$matrix{$this}}{@residus}=@row;
    }
    close IN; 
    return (%matrix);
}


sub gap{
    my ($test,$gaps);
    $gaps=0;
    $test= '-' x $per_codon;
  #  print STDERR "Gap1\t".scalar(@_)."\t".$gaps."\t"."@_"."\n";
    foreach (@_){
	($_ eq $test) && ($gaps++);
    }
  #  print STDERR "Gap2\t".scalar(@_)."\t".$gaps."\t"."@_"."\n";
    ($gaps/scalar(@_)>$max_gap_portion) ? (return -1) : (return 0); 
}


sub anchor{
    my (@row)=@_;
    my ($return,$test,$gap);
    $return=0;
    $gap='-' x $per_codon;
    #print $gap."\n";
    unless (&gap(@row)){
	my ($i,$blosums);
	$blosums = Statistics::Descriptive::Full->new();
	if ($per_codon==3){
	    @row = map{$_=&_translate_simple($_)}(@row);
	} #if ($per_codon==3)
	if ( scalar( @row  ) > 2 ){
	    foreach $i (0..$#row-1){
		my ($j);
		($row[$i] eq $gap) && (next);
		foreach $j ($i+1..$#row){
		    ($row[$j] eq $gap) && (next);
		    #print STDERR "\t"."blossom\t".$row[$i]." ".$row[$j]."\t".$blosum{$row[$i]}{$row[$j]}."\n";
		    $blosums->add_data($blosum{$row[$i]}{$row[$j]});        	  
		} #foreach $j ($i+1..$#row)
	    } #foreach $i (0..$#row)      
	    ($blosums->percentile($percentile)>=0) && ($return=-1);
	} #if ( scalar( @row  ) > 2 )
	else {   
	    ( $blosum{$row[0]}{$row[1]} >= 0 ) && ($return=-1);
	} 
    } # unless (&gap(@row))  
    return ($return); 
 }




#=======================================================================================================
#=======================================================================================================
# UTIL FUNCTIONS :
#   - TRANSLATING SEQUENCES
#   - CREATING MULTIFASTA
#=======================================================================================================
#=======================================================================================================

sub create_multi_fasta($ $ $ $){
    my $file_path         = $_[0];
    my %trapid_sequences  = %{$_[1]};
    my %plaza_sequences   = %{$_[2]};       
    my @exclude_transcripts = @{$_[3]};

    my %excl_trans          = map {$_ => 1} @exclude_transcripts;

    open (MFA,">".$file_path);
    for my $transcript_id (keys(%trapid_sequences)){
	if(!exists($excl_trans{$transcript_id})){
	    my $prot_seq      = $trapid_sequences{$transcript_id};
	    $prot_seq         =~ s/\*//g;
	    print MFA ">$transcript_id\n$prot_seq\n";
        } 
    }
    for my $gene_id (keys(%plaza_sequences)){
	my $prot_seq     = $plaza_sequences{$gene_id};
	$prot_seq         =~ s/\*//g;
        print MFA ">$gene_id\n$prot_seq\n";
    }	
    close(MFA);
}


sub translate_sequences($){
    my %result;
    my %dna_sequences = %{$_[0]};
    foreach my $id (keys %dna_sequences){
	my $dna_seq      = $dna_sequences{$id};
	my $prot_seq     = &_translate_simple($dna_seq);
	$result{$id}     = $prot_seq;
        #print ">".$id."\n".$prot_seq."\n";
    }
    return \%result;
}


sub _translate_simple ( $ ) {   
    my $seq            = $_[0];
    my $return_on_stop = 0;
    my $transl_table   = 1;	
    my $len            = length($seq);
    my $output         = q{};
    my %protein_of = (
	"TCA"=>"S","TCC"=>"S","TCG"=>"S","TCT"=>"S","TCN"=>"S",     	# Serine
	"TTT"=>"F","TTC"=>"F","TTY"=>"F",                               # Phenylalanine
	"TTA"=>"L","TTG"=>"L","TTR"=>"L",                               # Leucine
	"TAT"=>"Y","TAC"=>"Y","TAY"=>"Y",                               # Tyrosine
	"TAA"=>"*","TAG"=>"*","TAR"=>"*",                               # Stop
	"TGT"=>"C","TGC"=>"C","TGY"=>"C",                               # Cysteine
	"TGA"=>"*",                                                     # Stop
	"TGG"=>"W",                                                     # Tryptophan
	"CTA"=>"L","CTC"=>"L","CTG"=>"L","CTT"=>"L","CTN"=>"L",         # Leucine
	"CCA"=>"P","CCC"=>"P","CCG"=>"P","CCT"=>"P","CCN"=>"P",         # Proline
	"CAT"=>"H","CAC"=>"H","CAY"=>"H",                               # Histidine
	"CAA"=>"Q","CAG"=>"Q","CAR"=>"Q",                               # Glutamine
	"CGA"=>"R","CGC"=>"R","CGG"=>"R","CGT"=>"R","CGN"=>"R",         # Arginine
	"ATA"=>"I","ATC"=>"I","ATT"=>"I","ATH"=>"I",                    # Isoleucine
	"ATG"=>"M",                                                     # Methionine
	"ACA"=>"T","ACC"=>"T","ACG"=>"T","ACT"=>"T","ACN"=>"T",         # Threonine
	"AAT"=>"N","AAC"=>"N","AAY"=>"N",                               # Asparagine
	"AAA"=>"K","AAG"=>"K","AAR"=>"K",                               # Lysine
	"AGT"=>"S","AGC"=>"S","AGY"=>"S",                               # Serine
	"AGA"=>"R","AGG"=>"R","AGR"=>"R",                               # Arginine
	"GTA"=>"V","GTC"=>"V","GTG"=>"V","GTT"=>"V","GTN"=>"V",         # Valine
	"GCA"=>"A","GCC"=>"A","GCG"=>"A","GCT"=>"A","GCN"=>"A",         # Alanine
	"GAT"=>"D","GAC"=>"D","GAY"=>"D",                               # Aspartic Acid
	"GAA"=>"E","GAG"=>"E","GAR"=>"E",                               # Glutamic Acid
	"GGA"=>"G","GGC"=>"G","GGG"=>"G","GGT"=>"G","GGN"=>"G",         # Glycine
	);
     $protein_of{'0'}{'ATG'}="M";    
    for (my $i=0; $i < ($len-2); $i+=3) {
	my $codon = substr($seq, $i, 3);
	if ($i==0 && exists $protein_of{$i}{$codon})
	 {
	 my $protein = $protein_of{$i}{$codon};
	 $output .= $protein;
	 next;
	 }	    

	if (exists $protein_of{ $codon }) { 
	    my $protein = $protein_of{ $codon };
	    $output .= $protein;
	    last if ($protein eq "*" && $return_on_stop);
	}
	else {
	    $output .= 'X'; # unknown codon
	    #	printf STDERR " no translation for: " . substr($seq,$i,3) . "\n"; 
	}                        
    }
    return ($output);
}



#=======================================================================================================
#=======================================================================================================
# DATABASE FUNCTIONS : 
#  - STORING DATA 
#=======================================================================================================
#=======================================================================================================
sub store_msa_results($ $ $ $){  #($dbh_trapid,\@msa_data)
    my $dbh_trapid            = $_[0];
    my $exp_id                = $_[1];
    my $gf_id                 = $_[2];
    my @msa_data              = @{$_[3]}; 
    #msa_data[0]   = multiple sequence alignment (raw) 
    #msa_data[1]   = stripped multiple sequence alignment              --> only present if strip align
    #msa_data[2]   = alignment method (normally muscle)                 -> only present if strip align
    #msa_data[3]   = length of multiple sequence alignment              -> only present if strip align
    #msa_data[4]   = length of stripped multiple sequence alignment     -> only present if strip align
    #msa_data[5]   = msa strip parameters                               -> only present if strip align
    
    my $query      = "";
    if(scalar(@msa_data)==1){ #not stripped alignment
	$query     = "UPDATE `gene_families` SET `msa`='".$msa_data[0]."'                                 
                                 WHERE `experiment_id`='".$exp_id."' AND `gf_id`='".$gf_id."' ";  
    }
    else{ # stripped alignment
       $query      = "UPDATE `gene_families` SET `msa`='".$msa_data[0]."',
                                 `msa_stripped`='".$msa_data[1]."', `msa_stripped_params`='".$msa_data[5]."' 
                                 WHERE `experiment_id`='".$exp_id."' AND `gf_id`='".$gf_id."' ";     
    }
    my $dbq            = $dbh_trapid->prepare($query);
    $dbq->execute();
    $dbq->finish();    
}


sub delete_current_job($ $ $){
	my $dbh_trapid		= $_[0];
	my $experiment_id	= $_[1];
	my $gf_id		= $_[2];
	my $dbq			= $dbh_trapid->prepare("DELETE FROM `experiment_jobs` WHERE `experiment_id`=? AND `comment`=?");
	my $comment		= "create_msa ".$gf_id;
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
	my $subject		= "Subject: TRAPID multiple sequence alignment finished for ".$gf_id."\n";
	my $content		= "Dear user,\nThe multiple sequence alignment (MSA) for gene family '".$gf_id."' in experiment '".$experiment_title."' has been created.\n";
	$content		= $content."You can now view the MSA (after authentication) at this URL:\n";
	$content		= $content."http://bioinformatics.psb.ugent.be/webtools/trapid/tools/create_msa/".$experiment_id."/".$gf_id." \n";
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

sub retrieve_sequences_trapid($ $ $){
    my %result;
    my $dbh_trapid             = $_[0];
    my $exp_id                 = $_[1];
    my $gf_id                  = $_[2];
    my $query                  = "SELECT `transcript_id`,`orf_sequence` FROM `transcripts` 
                                  WHERE `experiment_id`='".$exp_id."' AND `gf_id`='".$gf_id."' ";
    my $dbq                    = $dbh_trapid->prepare($query);
    $dbq->execute();
    while((my @record) = $dbq->fetchrow_array){
	my $transcript_id       = $record[0];
	my $orf_sequence        = $record[1];
	$result{$transcript_id} = $orf_sequence;
    }
    $dbq->finish();
    return \%result;
} 


sub get_used_species($ $){
    my @result = ();
    my $dbh_plaza              = $_[0];
    my $tax_id_string          = $_[1];
    my $tax_id_string_db       = "('".join("','",split(",",$tax_id_string))."')";
    my $query                  = "SELECT `species` FROM `annot_sources` WHERE `tax_id` IN ".$tax_id_string_db;
    my $dbq                    = $dbh_plaza->prepare($query);
    $dbq->execute();
    while((my @record) = $dbq->fetchrow_array){
	my $species            = $record[0];      
        push(@result,$species);
	#print $species."\n";
	#print scalar(@result)."\n";
    }
    $dbq->finish();    
    #print "@result";
    return \@result;
}


sub retrieve_sequences_plaza_hom($ $ $){
    my %result;
    my $dbh_plaza             = $_[0];
    my $plaza_gf_id           = $_[1];
    my @used_species          = @{$_[2]};
    my $used_species_string   = "('".join("','",@used_species)."')";
    my $query                 = "SELECT `annotation`.`gene_id`,`annotation`.`seq` FROM `annotation`,`gf_data` 
                                WHERE `gf_data`.`gf_id`='".$plaza_gf_id."' AND `annotation`.`gene_id`=`gf_data`.`gene_id`
                                AND `annotation`.`species` IN ".$used_species_string;
    my $dbq                    = $dbh_plaza->prepare($query);
    $dbq->execute();
    while((my @record) = $dbq->fetchrow_array){
	my $gene_id           = $record[0];
        my $sequence          = $record[1];
	$result{$gene_id}     = $sequence;
	#print ">".$gene_id."\n".$sequence."\n";
    }
    $dbq->finish();
    return \%result;
}


sub retrieve_sequences_plaza_iorth($ $ $){
    my %result;
    my $dbh_plaza              = $_[0];
    my $gf_content             = $_[1];
    my @used_species           = @{$_[2]};
    my $used_species_string    = "('".join("','",@used_species)."')";
    my $gene_string            = "('".join("','",split(" ",$gf_content))."')";
    my $query                  = "SELECT `annotation`.`gene_id`,`annotation`.`seq` FROM `annotation` 
                                 WHERE `annotation`.`gene_id` IN ".$gene_string." AND `annotation`.`species` IN ".$used_species_string;
    my $dbq                    = $dbh_plaza->prepare($query);
    $dbq->execute();
    while((my @record) = $dbq->fetchrow_array){
	my $gene_id           = $record[0];
        my $sequence          = $record[1];
	$result{$gene_id}     = $sequence;
	#print ">".$gene_id."\n".$sequence."\n";
    }
    $dbq->finish();
    return \%result;
}




#Retrieve gene family information from the trapid database
sub retrieve_gf_information($ $ $){
        my %result;
	my $dbh_trapid          = $_[0];		       
	my $experiment_id       = $_[1];
	my $trapid_gf_id        = $_[2];
	my $query               = "SELECT `plaza_gf_id`,`gf_content`,`used_species`,`exclude_transcripts` FROM `gene_families` 
                                   WHERE `experiment_id`='".$experiment_id."' AND `gf_id`='".$trapid_gf_id."' ";
	my $dbq                 = $dbh_trapid->prepare($query);
	$dbq->execute();
	while((my @record) = $dbq->fetchrow_array){
	    my $plaza_gf_id         = $record[0];
	    my $gf_content          = $record[1];
	    my $used_species        = $record[2];
	    my $exclude_trans       = $record[3];
	    $result{"plaza_gf_id"}  = $plaza_gf_id;
	    $result{"gf_content"}   = $gf_content;
	    $result{"used_species"} = $used_species;
	    $result{"exclude_transcripts"} = $exclude_trans;
	}	
	#close handlers 
	$dbq->finish();
	return \%result;
}



#Retrieve basic information on a trapid experiment
sub get_basic_information($ $){
        my %result;
	my $dbh_trapid          = $_[0];		       
	my $experiment_id       = $_[1];
	my $query               = "SELECT  `experiments`.`genefamily_type`,`data_sources`.`seq_type` 
                                   FROM `experiments`,`data_sources` 
                                   WHERE `experiments`.`experiment_id`='".$experiment_id."' AND `data_sources`.`db_name`=`experiments`.`used_plaza_database` ";
	my $dbq			= $dbh_trapid->prepare($query);
	$dbq->execute();
	while((my @record) = $dbq->fetchrow_array){
	    my $gf_type             = $record[0];
	    my $seq_type            = $record[1];
	    $result{"gf_type"}      = $gf_type;
	    $result{"seq_type"}     = $seq_type;
	}	
	#close handlers 
	$dbq->finish();
	return \%result;
}







