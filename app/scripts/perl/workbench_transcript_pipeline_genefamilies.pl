#!/usr/local/bin/perl
use strict;
use warnings;

use FindBin qw{$Bin};
use lib "$Bin/../../modules";

### The pipeline for the gene family identification part (and subsequence parts) 
### of the transcript analysis in a PLAZA workbench environment
###
###
### This includes the following steps:
###
### + loading ORF sequences
### + using BLASTP to identify putative gene family hits
### + assign each transcript to a gene family, if possible
### + transfer functional annotation to each gene family
###
### if necessary target states are indicated, this will be extended with the following steps:
###
### + make MSA's 
### + make trees
###
### par0 = plaza workbench db to connect: db_plaza_workbench
### par1 = workbench experiment id
### par2 = plaza database to connect (e.g. db_plaza_public_02_5)
### par3 = location of blast databases
### par4 = blast database name
### par5 = output dir for blast and other results (tmp dir)
### par6 = e-value (for BLAST)
### par7 = number of blast results to interprete, per transcript
### par8 = gene family type to use (HOM or ORTH)

use PLAZA::DB;
use PLAZA::PLAZA_utils;
use Statistics::Descriptive;

#Basic parameter check
if(scalar(@ARGV)!=9){
    print STDERR "* Parameters!\n";
    exit;
}

our %blosum; # has to work over subroutines



#=======================================================================================================
#=======================================================================================================
#read parameters
my %par;

$par{'workbench_database'}  	= $ARGV[0];
$par{'workbench_exp_id'}    	= $ARGV[1]; 
$par{'plaza_database'}		= $ARGV[2];
$par{'db_dir'}	   		= $ARGV[3];	
$par{'blast_db'}   		= $ARGV[4];
$par{'output_dir'} 		= $ARGV[5];
$par{'e-value'}     		= $ARGV[6];
$par{'num_blast_results'}	= $ARGV[7];
$par{'gf_type'}			= $ARGV[8];



#my $db_dir = "/blastdb/webdb/moderated/plaza/";            #MIDAS LOCATION of blast databases
#my $db_dir = "/www/blastdb/biocomp/moderated/plaza/";      #WEBSERVER LOCATION of blast databases


#step 0: read the transcript ids and associated orf_sequences into a hash. This data can be useful further on in the processing.
print STDERR "Reading transcript ids and sequences \n";
my %transcript_sequences           = &retrieve_experiment_information($par{'workbench_database'},$par{'workbench_exp_id'});
my @transcript_ids		   = keys(%transcript_sequences);

#step 1: write ORF sequences to file, to be used as BLAST input
print STDERR "Creating BLAST file\n";
my $blast_input_file               = &create_blast_file(\%transcript_sequences,$par{'workbench_exp_id'},$par{'output_dir'});

#step 2: execute BLAST 
print STDERR "Executing similarity search\n";
my $blast_output_file              = &execute_similarity_search($blast_input_file,$par{'db_dir'},$par{'blast_db'},$par{'e-value'},$par{'num_blast_results'});

#step 3: read similarity search output, contains double hash : {transcript_id -> {gene_id -> bitscore}}
print STDERR "Parsing similarity output\n";
my %blast_results                  = &read_similarity_output($blast_output_file,\@transcript_ids,$par{'num_blast_results'});

#step 4: use the blast results to find the correct gene families.
print STDERR "Inferring gene families\n";
my %transcript2genefamilies	   = &select_gene_families(\%blast_results,$par{'gf_type'},$par{'plaza_database'});

#step 5: detecting gene family representatives from the set of transcripts (in order to save disk-space on the workbench database).
#print STDERR "Detecting gene family representatives\n";
#my %gf_representatives	 	= &select_gf_representatives(\%transcript2genefamilies);

#step 5: store the gene family information in the workbench database. 
print STDERR "Storing gene family information\n";
&store_gene_family_information(\%transcript2genefamilies,$par{'workbench_database'},$par{'workbench_exp_id'});

print STDERR "DONE\n";

exit;



#Function to retrieve all transcript-ids and associated ORF sequences from the database for a given workbench experiment.
#Result is a hash with as key the transcript-id and as value the ORF sequence.
sub retrieve_experiment_information($ $){
    my $workbench_database = $_[0];
    my $workbench_exp_id   = $_[1];
    my %transcript_data;
    my %db_config;
    $db_config{'database'} = $workbench_database;    
    my $query              = "SELECT `gene_id`,`orf_sequence` FROM `wb_experiment_transcript_genes` WHERE `experiment_id`='".$workbench_exp_id."' ";    
    my $dbh                = &connect(\%db_config);
    my $dbq                = $dbh->prepare($query);
    $dbq->execute();
    while((my @record) = $dbq->fetchrow_array){
	my $transcript_id   = $record[0];
	my $orf_sequence    = $record[1];
	my $aa_sequence     = &_translate_simple($orf_sequence); #assume normal translation table
	$transcript_data{$transcript_id} = $aa_sequence;
    }
    $dbq->finish();
    $dbh->disconnect();
    return %transcript_data;
}




#Function which creates a amino-acid multi-fasta file from the ORF sequences (not necessarily CDS sequences),
#part of the normal PLAZA workbench experiment
sub create_blast_file($ $ $){
    		
    my %transcript_data    = %{$_[0]};	
    my $workbench_exp_id   = $_[1];
    my $tmp_dir            = $_[2];   
     
    my $blast_input_file   = $tmp_dir."wb_tap_blast_input_".$workbench_exp_id.".fasta";
    open FASTA_FILE,">",$blast_input_file;    
    for my $transcript_id (keys(%transcript_data)){
    	my $aa_sequence    = $transcript_data{$transcript_id};
	print FASTA_FILE ">".$transcript_id."\n";
	print FASTA_FILE $aa_sequence."\n";	
    }	   
    close FASTA_FILE;
    return $blast_input_file;
}





#Function which executes similarity search (normally using BLAST)
#
sub execute_similarity_search($ $ $ $ $ $){
    my $input_file         = $_[0];
    my $blast_dir          = $_[1];
    my $blast_db           = $_[2];
    my $e_value            = $_[3];
    my $num_blast_results  = $_[4];
    my $output_file        = $input_file.".m8";
    #=========================================
    # execute similarity search, here we use BLASTP
    #=========================================
    my $blast_command      = "blastall -p blastp -d $blast_dir$blast_db -i $input_file -e $e_value -m 8 -o $output_file";    
    #my $blast_command      = "blastp -db $blast_dir$blast_db -query $input_file -evalue $e_value -outfmt 6 -out $output_file ";
    print STDERR " * ".(localtime)."\n";
    print STDERR "*$blast_command\n";
    system($blast_command);
    print STDERR " * ".(localtime)."\n";
    return $output_file;
}







#Function which parses the similarity search output, and returns a hash-datastructure containing the results. 
#The transcript_ids should also be provided as input, in order to correctly initiate the resulting data structure (correctly 
#indicate transcripts with no found hits).
#Also provided (last parameter) is the number of blast_results to be returned (if -1 : return all found results).
#This value is a parameter, as this value should be inherintly different for the different similarity search software types 
#(e.g. blastp vs blastx vs. usearch vs. rapsearch)
sub read_similarity_output($ $ $){
    my $to_parse_file       = $_[0];
    my @transcript_ids      = @{$_[1]};
    my $max_hits            = $_[2];
    
    #initiate the resulting datastructure with all the transcript ids
    my %blast_results;
    for my $transcript_id(@transcript_ids){
    	my %blast_hits;
	$blast_results{$transcript_id} = {%blast_hits}; 	#each transcript has several gene_ids as BLAST result, 
    }
    
    #parse blast output file
    open FIN,"<",$to_parse_file;
    while(defined(my $line = <FIN>)){
        chomp $line;
        my @array = split("\t",$line);
        my $transcript_id	= $array[0];
        my $plaza_gene_id	= $array[1];    
        my $bitscore            = $array[11];   
        my $num_gene_ids        = scalar(keys(%{$blast_results{$transcript_id}})); 
        if($max_hits==-1 || $num_gene_ids<$max_hits){
	    if(! exists $blast_results{$transcript_id}{$plaza_gene_id}){           #unique gene entries! #best hit is first!
	        $blast_results{$transcript_id}{$plaza_gene_id} = $bitscore;
	    }      
        }	
    }
    close FIN;
    return %blast_results;    
}




#Function to select the gene family for each transcript, which is done by taking the similarity-hits into acccount for each transcript.
#This step also requires database access, to locate the gene families for each similarity hit.
sub select_gene_families($ $ $){
    my %blast_results       = %{$_[0]};
    my $gf_type		    = $_[1];
    my $plaza_database      = $_[2];
    
    my %transcript_2_gf;	#temp storage for mapping between transcripts and gene families
    my $query	  	    = "SELECT a.`gf_id`,b.`num_genes` FROM `gf_data` a, `phylo_profiles` b WHERE a.`gene_id` = ? AND a.`gf_id` LIKE '".$gf_type."%' AND a.`gf_id`=b.`gf_id` ";
    
    #database initialization
    my %db_config;
    $db_config{'database'} = $plaza_database;
    my $dbh                = &connect(\%db_config);
    my $dbq                = $dbh->prepare($query);
    for my $transcript_id (keys %blast_results){
   	my @gene_ids		= keys(%{$blast_results{$transcript_id}});
   	my %gf_ids;	
   	#retrieve gene family id and gf size for each gene hit
   	for my $gene_id (@gene_ids){
	     $dbq->execute($gene_id);
	     my ($gf_id,$num_genes) = $dbq->fetchrow_array();	
	     $gf_ids{$gene_id} = [($gf_id,$num_genes)];	
        }
   	#now, we have for each transcript a set of genes, and for these genes the associated gene family and gene 
   	#family size. The best thing we can do now is try to infer the correct gene family per transcript 
   	if(scalar(@gene_ids)==0){
   	     $transcript_2_gf{$transcript_id}	= ["undefined",0,0];
   	}
   	else{
       	     my %blast_info                     = %{$blast_results{$transcript_id}};
       	     my ($gf_id,$numhits,$bitscore)	= &infer_gene_family($transcript_id,\%gf_ids,\%blast_info);   
	     #print STDOUT $transcript_id."\t".$gf_id."\t".$numhits."\t".$bitscore."\n"; 
       	     $transcript_2_gf{$transcript_id} 	= [($gf_id,$numhits,$bitscore)];
       }
   }
   $dbq->finish();
   $dbh->disconnect();
   return %transcript_2_gf;
}




#Function which determines, for a given transcript, the correct gene family by 
#taking the blast-hits into account.
#Input data is a hash with gene_ids as keys, and an array(gf_id,num_genes_gf_id) as value.
#The second value in the array (num_genes_gf_id) equates with the total number of genes in that gene family (in PLAZA).
#This value is needed in this function for correct evaluation of probabilities.
sub infer_gene_family{
    my $transcript_id  = $_[0];
    my %gf_ids         = %{$_[1]};
    my %blast_info     = %{$_[2]};	
    
    my %gf_sizes;   #just quick storage for quick lookup later on.
    for my $gene_id (keys(%gf_ids)){
	my $gf_id   = $gf_ids{$gene_id}[0];
	my $gf_size = $gf_ids{$gene_id}[1];
	$gf_sizes{$gf_id}  = $gf_size;
    }
    
    #get the support for each of the gene families, associated with the gene_ids
    #also get the total bitscore length
    my %count_support;   
    my %bitscore_support;
    for my $gene_id (keys(%blast_info)){
	my $gf_id      = $gf_ids{$gene_id}[0];
	my $bitscore   = $blast_info{$gene_id};
	if(exists($count_support{$gf_id})){
	    $count_support{$gf_id}       = $count_support{$gf_id}+1;
	    $bitscore_support{$gf_id}    = $bitscore_support{$gf_id}+$bitscore;
	}     
	else{
	    $count_support{$gf_id}       = 1;
            $bitscore_support{$gf_id}    = $bitscore;	   
	}
    }
    
    	 #   print STDOUT $transcript_id."\n";
#	    for my $local_gf_id(keys(%count_support)){
#		my $local_gf_support     = $count_support{$local_gf_id};
#		my $bitscore_gf_support  = $bitscore_support{$local_gf_id};
#		my $local_gf_size        = $gf_sizes{$local_gf_id};	
#		print STDOUT $local_gf_id."\t".$local_gf_support."\t".$bitscore_gf_support."\t".$local_gf_size."\n";
#	    }
#	    print STDOUT "-----------------------------\n";
    
    
    #first selection criterium  : number of hits.
    #second selection criterium : bitscore
    #So, for now we just take the gene family with the most hits. If there are several gene families
    #with the same top amount of hits, take the one with the longest bitscore value.
    my $current_best_gf          = "undefined";
    my $current_best_hitcount    = -1;
    my $current_best_bitscore    = -1;
    for my $local_gf_id(keys(%count_support)){
        my $local_gf_count = $count_support{$local_gf_id};
	my $local_bitscore = $bitscore_support{$local_gf_id};
	if($local_gf_count>$current_best_hitcount){
	    $current_best_gf     	= $local_gf_id;
	    $current_best_hitcount	= $local_gf_count;
	    $current_best_bitscore	= $local_bitscore;
	}
	elsif($local_gf_count == $current_best_hitcount){
	    if($local_bitscore > $current_best_bitscore){
	    	$current_best_gf	= $local_gf_id;
		$current_best_hitcount	= $local_gf_count;
		$current_best_bitscore	= $local_bitscore;
	    }	
	    else{
	    	# equal bitscore is highly unlikely, and lower bitscore is a worse gene family match.
	    }
	}
	else{
		# $local_gf_count < $current_best_hitcount
		# Don't do anything	
	}
    }           
    return ($current_best_gf,$current_best_hitcount,$current_best_bitscore);
}


#Function to retrieve the gene family representatives from a set of transcripts (with assoc gfs).
#This representative function is of no biological relevance, but is only used to reducde storage space on the database.
#sub select_gf_representatives($){
#    my %transcript_2_gf		= %{$_[0]};
#    my %representatives;
#    for my $transcript_id (keys(%transcript_2_gf)){
#    	my ($gf_id,$numhits,$bitscore)	= @{$transcript_2_gf{$transcript_id}};
#	print STDOUT $transcript_id."\t".$gf_id."\t".$numhits."\t".$bitscore."\n";
#	if($gf_id ne "undefined"){
#	    if(!exists($representatives{$gf_id})){
#           	$representatives{$gf_id} = $transcript_id;
#       	    }
#	}
#    }
#    return %representatives;
#}


#Store the retrieved gene family information in the database
sub store_gene_family_information($ $ $ $){
    my %transcript_2_gf		= %{$_[0]};  
    my $workbench_database	= $_[1];
    my $workbench_exp_id	= $_[2];
                   	    
   
    

    #database initialization
    my %db_config;
    $db_config{'database'}  = $workbench_database;
    my $dbh                 = &connect(\%db_config);

    my $query1              = "UPDATE `wb_experiment_transcript_genes` SET `plaza_gf_id`=? , `wb_gf_id`=? , `wb_gf_score`=? WHERE `experiment_id`=? AND `gene_id`=?";
    my $dbq1                = $dbh->prepare($query1);    
    for my $transcript_id(keys %transcript_2_gf){
        my ($gf_id,$numhits,$bitscore) = @{$transcript_2_gf{$transcript_id}};
	if($gf_id ne "undefined"){
	    my $wb_gf_score = "hits=".$numhits.";bitscore=".$bitscore; 
	    my $wb_gf_id    = $workbench_exp_id."_".$gf_id;
	    my $is_rep      = 0;	  
	    $dbq1->execute($gf_id,$wb_gf_id,$wb_gf_score,$workbench_exp_id,$transcript_id);
	}	
    }                        
    $dbq1->finish();


    #create unique set of associated gene families, and store those gene families in the wb_experiment_transcript_gf table,
    #if not yet present. 
    #If they are present, set the is_edited flag to 1.
    my %wb_gf_ids             = ();
    for my $transcript_id(keys %transcript_2_gf){
	my ($gf_id,$numhits,$bitscore) = @{$transcript_2_gf{$transcript_id}};
	if($gf_id ne "undefined"){
	    my $wb_gf_id          = $workbench_exp_id."_".$gf_id;
	    $wb_gf_ids{$wb_gf_id} = $wb_gf_id;
	}
    }
    
    my $query21 = "SELECT * FROM `wb_experiment_transcript_gf` WHERE `experiment_id`=? AND `wb_gf_id`=? ";
    my $query22 = "INSERT INTO `wb_experiment_transcript_gf` (`experiment_id`,`wb_gf_id`,`is_edited`) VALUES (?,?,'0') ";
    my $query23 = "UPDATE `wb_experiment_transcript_gf` SET `is_edited`='1' WHERE `experiment_id`=? AND `wb_gf_id`=? ";
    my $dbq21   = $dbh->prepare($query21);
    my $dbq22   = $dbh->prepare($query22);
    my $dbq23   = $dbh->prepare($query23);

    for my $wb_gf_id (keys %wb_gf_ids){
	$dbq21->execute($workbench_exp_id,$wb_gf_id);
	my $is_present = 0;
        if((my @record) = $dbq21->fetchrow_array){$is_present=1;}
	if($is_present){
	    $dbq23->execute($workbench_exp_id,$wb_gf_id);
	}
	else{
	    $dbq22->execute($workbench_exp_id,$wb_gf_id);
	}
    }

    $dbq21->finish();
    $dbq22->finish();
    $dbq23->finish();

    $dbh->disconnect();
}



##=======================================================================================================
##determine representatives (is_representative field in database) and mapping of gene families to genes (needed for next steps as well).
#print STDERR " * Determination of gene family representatives \n";
#my %representatives;
#my %gf_transcript_mapping;
#for my $transcript_id (keys(%transcript_2_gf)){
#   my $gf_id	= $transcript_2_gf{$transcript_id};
#   if($gf_id ne "undefined"){
#       if(!exists($representatives{$gf_id})){
#           $representatives{$gf_id} = $transcript_id;
#       }
#       if(!exists($gf_transcript_mapping{$gf_id})){
#           my @transcripts	= ();
#	   $gf_transcript_mapping{$gf_id} = [@transcripts];	
#       }
#       push(@{$gf_transcript_mapping{$gf_id}},$transcript_id);
#   }
#}
#
#
#
##=======================================================================================================
##Store the results in the workbench database
#my $dbh1;
#my %kk_workbench;
#my $dbq1;
#print STDERR " * Storing gene family information in workbench database\n";
#$dbh1      = &connect(\%kk_workbench);
#my $store_workbench_query_gf  = "UPDATE `wb_experiment_transcript_genes` SET `plaza_gf_id`= ? WHERE `gene_id`=? AND `experiment_id`='".$par{'workbench_exp_id'}."' ";
#my $store_workbench_query_rep = "UPDATE `wb_experiment_transcript_genes` SET `is_representative`='1' WHERE `gene_id`=? AND `experiment_id`='".$par{'workbench_exp_id'}."' ";
#my $swqf   = $dbh1->prepare($store_workbench_query_gf);
#my $swqr   = $dbh1->prepare($store_workbench_query_rep);
#
#for my $transcript_id(keys(%transcript_2_gf)){
#   my $gf_id = $transcript_2_gf{$transcript_id};
#   if($gf_id ne "undefined"){
#       $swqf->execute($gf_id,$transcript_id);
#       my $rep = $representatives{$gf_id};
#       if($rep eq $transcript_id){
#           $swqr->execute($transcript_id);
#       }
#   }
#}
#$swqf->finish();
#$swqr->finish();
#$dbq1->finish();
#$dbh1->disconnect();








#
##=======================================================================================================
##initiate the creation of custom msa's and gene families: step1, retrieve necessary data
##This is done by going over the data of the workbench experiment, and selecting the unique gene families and associated genes. 
##Then we get the gene family information from the PLAZA database (per gene family), and perform multiple sequence alignment, editing
##of the multiple sequence alignment and tree construction.
#
#$dbh1   = &connect(\%kk_workbench);
#$dbh2   = &connect(\%kk_database);
#my $query_gf_data_wb	= "SELECT `gene_id`,`sequence` FROM `wb_experiment_transcript_genes` WHERE `experiment_id`='".$par{'workbench_exp_id'}."' AND `plaza_gf_id` = ? ";
#my $query_gf_data_db	= "SELECT a.`gene_id`,b.`seq` FROM `gf_data` a, `annotation` b WHERE a.`gf_id`= ? AND a.`gene_id`=b.`gene_id` AND a.`outlier`='0' ";
#my $qfdw		= $dbh1->prepare($query_gf_data_wb);
#my $qfdd		= $dbh2->prepare($query_gf_data_db);
#for my $gf_id(keys(%gf_transcript_mapping)){ #go over all "new" gene families
#    #get all data from the plaza database for this gene family.
#    $qfdd->execute($gf_id);
#    my %gf_info;
#    while(my($gene_id,$seq)=$qfdd->fetchrow_array()){
#	$gf_info{$gene_id} = $seq;	
#    }   
#    $qfdw->execute($gf_id);
#    while(my($transcript_id,$seq)=$qfdw->fetchrow_array()){
#        $gf_info{$transcript_id}=$seq;
#    }    
#    #Create multiple sequence alignment
#    my $align_file = &create_msa($gf_id,\%gf_info,$par{'output_dir'},$par{'workbench_exp_id'});
#    #Strip the multiple sequence alignment
#   # my ($msa,$msa_strip,$msa_length, $msa_strip_length) = &strip_msa($gf_id,$align_file,$par{'output_dir'});    
#    #print $msa."\n".$msa_strip."\n";
#    
#    my $tree_bsbl;
#    #Create the phylogenetic tree
#    if(scalar(keys(%gf_info))<3){#tree with 2 sequences is stupid
#        #don't do anything
#    }
#    else{
#        #
#    }
#    
#}
#$qfdw->finish();
#$qfdd->finish();
#$dbh1->disconnect();
#$dbh2->disconnect();
#
#
#exit;
#
#
#
#
#
#
##SHORTCUT, NEEDS TO BE UPDATED! DEFINE A FUNCTION WHICH TAKES THIS DATA EN INDICATES WHETHER 
##A SINGLE GENE FAMILY CAN BE EXTRACTED, OR WHETHER IT IS UNCLEAR
## NOW JUST THE BEST HIT IS TAKEN INTO ACCOUNT
#sub infer_gene_family_shortcut{
#   my $params	= shift;
#   my %gf_ids	= %$params;	
#   my @gene_ids = keys(%gf_ids);
#   my $result	= $gf_ids{$gene_ids[0]}[0];
#   return $result;
#}
#
#
#
#
#
##Method from Phylogenetics.pm, slighlty adapted to make everything work.
#sub strip_msa( $ $ $ ){
#    my $gf_id		= $_[0];
#    my $fin	 	= $_[1];	#input file with multiple sequence alignment data
#    my $tmp_dir		= $_[2];
#    
#    my $blossum_file	= $tmp_dir."blosum62.txt";
#    
#  my ($c,$i,$last_anchor);
#  my (@align,@ids);
#  my (%align);
#  
#  my $per_codon	= 1;
#  
#
#  # strip
#  #($max_gap_portion,$percentile,$per_codon)= @strip_params;
#  #($per_codon) ? ($per_codon=3) : ($per_codon=1);
#  %align = &input2hash($fin);  
#  my %blosum=&read_blosum($blossum_file);
#  
#  # store raw msa
#  my $msa;
#  my $msa_length;
#  foreach my $gene_id (keys %align)
#   {
#   $msa.= ">$gene_id;$align{$gene_id}";
#   $msa_length=length($align{$gene_id});
#   }
#  #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
#  # Construct a data structure for editing the alignment
#  #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
#  $c=0;
#  foreach (keys %align)
#   {
#   #my ($n);
#   $ids[$c]=$_; # keeps track of original acc; converts to index $c
#   my $n=0;
#    for ($i=0;$i<length($align{$_});$i+=$per_codon)
#    {
#    $align[$n][$c]=substr($align{$_},$i,$per_codon); # @align is double array; first index $n is position, second index $c represents AC
#    $n++;
#    } #for ($i=0;$i<length($align{$_};$i+=$per_codon)
#   $c++; 
#  } #foreach (keys %align)
#
#  #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
#  # Strip the alignment
#  #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
#  $last_anchor=-1;
#  for ( $i=0;$i<scalar(@align);$i++ ) # goes over positions
#   {
#   #print "DEBUG Position $i\t@{$align[$i]}\n";
#    if (&anchor( @{$align[$i]} ))
#     {     
#      $last_anchor=$i;      
#      }
#    if ( &gap( @{$align[$i]} ) || ( $last_anchor == -1 ) )
#     {       
#  #    while ( !&anchor( @{$align[$last_anchor]} ) && ( $last_anchor > -1 ) ) {$last_anchor--}
#      $last_anchor++;
#      do
#       {
#        splice(@align,$last_anchor,1);
#       } until ( ($last_anchor == scalar(@align)) || &anchor(@{$align[$last_anchor]}) );
#       $i=$last_anchor;
#     } #if (&gap(@{$align[$i]))
#   } #for ($i=0;$i<scalar(@align);$i++)
#  ( $last_anchor < $#align ) && splice (@align,$last_anchor+1); 
#  
#   #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
#   # Store the edited alignment again in the original hash structure
#   #+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
#   %align=();
#   foreach (@align)
#    {
#      for ($i=0;$i<scalar(@{$_});$i++)
#      {
#       $align{$ids[$i]}.=$$_[$i];
#      } #for ($i=0;$i<scalar(@{$_});$i++)
#    } #foreach (@align)
#    
#   my $msa_strip;
#   my $msa_strip_length;
#   foreach my $gene_id (keys %align)
#    {
#    $msa_strip.= ">$gene_id;$align{$gene_id}";
#    $msa_strip_length=length($align{$gene_id});
#    }   
#
#   #print STDERR "MSA\n$msa\nMSA_STRIP\n$msa_strip\n";   
#   #return($msa,$msa_strip, $alnmethod, $msa_length, $msa_strip_length,join(" ",@strip_params));
#   return($msa,$msa_strip,$msa_length, $msa_strip_length);
#}
#
#sub read_blosum($)
# {
#  my $blosum_file= $_[0];
#  my (@residus);
#  my (%matrix);
#  open (IN,$blosum_file) || die "Could not open $blosum_file: $!\n";
#  (undef,@residus)=split(/[\t\n]/,<IN>);
#  while (<IN>)
#   {
#    chomp;
#    my ($this,@row)=split(/\t/);
#    @{$matrix{$this}}{@residus}=@row;
#   } #while (<IN>)
#  close IN; 
#  return (%matrix) 
# } #sub read_blosum
#
#sub input2hash ( $ )
# {
#  my ($key);
#  my (%fasta_hash);
#  my $fin = $_[0];
#  open (FIN, "$fin");
#  while (<FIN>)
#   {
#    chomp;
#    if (/^>(\S+)/)
#     {
#      $key=$1;
#      if (exists $fasta_hash{$key})
#       {
#       print STDERR "* Double entries in input file: $key!\n";
#       exit;
#       }
#     } #if (/^>(\w)$/)
#    else
#     {
#      $key || die "Input is not in fasta format!";
#      s/\s+//g;
#      #s/[^ARNDCQEGHILKMFPSTWYV*-]/X/gi; #SET OF BY KLPOE
#      $fasta_hash{$key}.=$_;
#     } #else
#   } #while (<STDIN>)
#  close FIN;
#  return (%fasta_hash);
# } #sub input2hash ( $ )
# 
# sub gap
# {
#  my $per_codon = 1;
#  my $max_gap_portion = 0.1;
#  my ($test,$gaps);
#  $gaps=0;
#  $test= '-' x $per_codon;
#  foreach (@_)
#   {
#    ($_ eq $test) && ($gaps++);
#   } #foreach (@_)
#  ($gaps/scalar(@_)>$max_gap_portion) ? (return -1) : (return 0); 
# } #sub gap
#
#
#sub anchor
# {
#  my $per_codon 	= 1;
#  my $max_gap_portion 	= 0.1;
#  my $percentile	= 50;
#  	
#  my (@row)=@_;
#  my ($return,$test,$gap);
#  $return=0;
#  $gap='-' x $per_codon;
#  unless (&gap(@row))
#   {
#    my ($i,$blosums);
#    $blosums=Statistics::Descriptive::Full->new();
#    if ($per_codon==3)
#     {
#      @row=map  {$_=&_translate_simple($_)}(@row);
#     } #if ($per_codon==3)
#
#    if ( scalar( @row  ) > 2 )
#     {
#      foreach $i (0..$#row-1)
#       {
#	my ($j);
#	($row[$i] eq $gap) && (next);
#	foreach $j ($i+1..$#row)
#	 {
#	  ($row[$j] eq $gap) && (next);
#          $blosums->add_data($blosum{$row[$i]}{$row[$j]});
#         #$print "DEBUG\tBLOSUM value \t$i = $row[$i] $j = $row[$j]\t$blosum{$row[$i]}{$row[$j]}\n";	  
#	 } #foreach $j ($i+1..$#row)
#       } #foreach $i (0..$#row)
#       #print "DEBUG\tperc $percentile\t".$blosums->percentile($percentile)."\n";
#      ($blosums->percentile($percentile)>=0) && ($return=-1);
#     } #if ( scalar( @row  ) > 2 )
#    else 
#     {
#     #print "DEBUG\tless then 3 aa\n";
#      ( $blosum{$row[0]}{$row[1]} >= 0 ) && ($return=-1);
#     } #else 
#   } # unless (&gap(@row))  
#  return ($return); 
# } #sub anchor
#
#
#
##Method for creating the multiple sequence alignment from a set of genes.
##Most of the genes should come from the PLAZA database, while others are ORFs from transcripts
##extracted from a workbench experiment.
#sub create_msa( $ $ $ $){
#   my $gf_id	= $_[0];
#   my %gf_info	= %{$_[1]};   
#   my $tmp_dir	= $_[2];
#   my $exp_id	= $_[3];
#   
#   print STDERR "Creating MSA for gf ".$gf_id."\n";
#   
#   my %gf_info2;	   
#   my $transl_table 	= 1;
#   my $msa_method	= "muscle";
#   
#   my $file_name 	= $tmp_dir."msa_prot_file_".$exp_id."_".$gf_id.".tfa";
#   my $file_name_align 	= $tmp_dir."msa_prot_file_".$exp_id."_".$gf_id.".faln";
#   
#   #step 1: create protein sequences from all the cds sequences
#   for my $gene_id (keys(%gf_info)){
#       my $seq	= $gf_info{$gene_id};
#       my $prot = ${&_translate_full({'sequence'=>\$seq,'return_on_stop'=>1,'transl_table'=>$transl_table})};
#       $gf_info2{$gene_id} = $prot;
#   }  
#     
#   
#   open (MFA,">".$file_name);
#   for my $gene_id (keys(%gf_info2)){
#       my $seqout	= $gf_info2{$gene_id};
#       $seqout		=~ s/\*$//g;
#       print MFA ">$gene_id\n$seqout\n";
#   }
#   close(MFA);
#   
#   #make alignment
#   if($msa_method eq "muscle"){
#       system("muscle -in ".$file_name." -out ".$file_name_align);
#   }
#   elsif($msa_method eq "t_coffee"){
#       system("t_coffee -infile ".$file_name." -outfile ".$file_name_align." -output=fasta_aln");
#   }
#   return $file_name_align;   
#}
#
#
#


































