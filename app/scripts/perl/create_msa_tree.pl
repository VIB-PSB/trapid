#!/usr/local/bin/perl
use strict;
use warnings;
use FindBin qw{$Bin};
use Statistics::Descriptive;
use Bio::TreeIO;
use Bio::AlignIO;
use Bio::SeqIO;
use POSIX;
use Getopt::Long qw(:config );

# script which creates an MSA and a phylogenetic tree based on a FASTA file.

# global variables
# required
my $base_file_path		= "";	# Base path: used to append extra suffixes for additional files to be created.
my $fasta_file_path		= "";	# FASTA file containing protein sequences
my $msa_file_path		= ""; 	# MSA file path
my $msa_stripped_file_path	= ""; 	# Stripped MSA file path. Will have the same content as msa_file_path if msa_editing == none
my $tree_file_path		= "";	# File path for phylogenetic tree
my $msa_program			= "";	# muscle or mafft
my $tree_program		= ""; 	# fasttree, phyml or raxml
my $msa_editing			= "";	# none, column, row or column_row

#optional
my $email			= "";	# address to send email to
my $base_file_name		= "";	# base file name
my $url				= "";	# base url
my $msa_only    = ""; # Generate MSA only?

# read parameters from command line
GetOptions(
	"base-path=s"=>\$base_file_path,
	"fasta-path=s"=>\$fasta_file_path,
	"msa-path=s"=>\$msa_file_path,
	"msa-stripped-path=s"=>\$msa_stripped_file_path,
	"tree-path=s"=>\$tree_file_path,
	"msa-program=s"=>\$msa_program,
	"tree-program=s"=>\$tree_program,
	"editing-program=s"=>\$msa_editing,
	"email=s"=>\$email,
	"base-name=s"=>\$base_file_name,
	"url=s"=>\$url,
	"msa_only=s"=>\$msa_only
);

if($base_file_path eq "" || $fasta_file_path eq "" || $msa_file_path eq "" || $msa_stripped_file_path eq "" || $tree_file_path eq "" || $msa_program eq "" || $tree_program eq "" || $msa_editing eq ""){
	die "*Parameters!!! \n";
}

#keep track of executed commands
our %executed_commands;

#keep track of extra (model) information
our %extra_model_information;

#keep track of extra alignment information
our %extra_aln_information;

# Step 1: check if certain output files already exist. If they do, remove them.
&clear_existing_files();

# step 2: run the MSA program
&create_msa();

# If generating MSA only, stop execution (no MSA editing, no tree construction)
if($msa_only eq "yes") {
    print STDERR "[Message] Only generate MSA, stop phylogenetics pipeline execution. \n";
    exit 0;
}

# step 3: strip/edit the MSA
&strip_edit_msa();

# step 4: find out statistics about the original/stripped alignment
&gather_msa_stats();

# step 5: run the tree construction program
&create_tree();

# step 6: write extra data to files to be postprocessed by PHP and presented to end-user
&write_extra_data_files();

# step 7: send email if email address has been provided to this script
if($email ne ""){
	&send_email();
}

# exit the program gracefully
exit 0;


#####################################################################################################
############## MAIN FUNCTIONS #######################################################################
#####################################################################################################

# Create multiple sequence alignment
sub create_msa(){
	# depending on algorithm, follow different workflow here
	if ($msa_program eq 'muscle'){
		#simply execute muscle
		system("muscle -in $fasta_file_path -out $msa_file_path");
		$executed_commands{"msa"}{"muscle"} = "muscle -in \$FASTA_FILE_PATH -out \$MSA_FILE_PATH";
	}
	elsif ($msa_program eq 'mafft'){
		#execute mafft in auto mode
		system("mafft --maxiterate 1000 --auto $fasta_file_path > $msa_file_path 2> $msa_file_path.log");
		$executed_commands{"msa"}{"mafft"} = "mafft --maxiterate 1000 --auto \$FASTA_FILE_PATH &gt; \$MSA_FILE_PATH";
		#extract which strategy was used for MAFFT
		my $version_cmd    		= "grep -A1 '^Strategy:' $msa_file_path.log | sed -n '1!p' | cut -f2 -d' ' > $msa_file_path.version ";
		system($version_cmd);
		my $version_info		= `cat $msa_file_path.version`;
		chomp($version_info);
		system("rm $msa_file_path.log");
		#update model information about which MAFFT strategy was used
		$extra_model_information{"Selected MAFFT alignment method"}{$version_info}="http://mafft.cbrc.jp/alignment/software/manual/manual.html";
	}
	else{
		&error_state("Unknown msa program : $msa_program");
	}
	# check for required presence of created MSA file
	if (! &check_presence_file($msa_file_path)){
		&error_state(
			"MSA creation failed. $msa_file_path file not created after MSA step.",
			"MSA creation failed. MSA file not created after MSA step."
		);
	}
}


# strip/edit the multiple sequence alignment using a variety of methods
sub strip_edit_msa(){
	if ( $msa_editing eq "none" ){ # copy the original msa file to the stripped file if no editing is required
		system("cp $msa_file_path $msa_stripped_file_path");
	}
	elsif( $msa_editing eq "column" || $msa_editing eq "row" || $msa_editing eq "column_row"){
		&double_strip_msa($msa_file_path,$msa_stripped_file_path,$msa_editing);
	}
	else{
		&error_state("Unknown msa editing mode : $msa_editing");
	}
	# check whether the stripped MSA file path exists. If not, we throw an error because clearly the stripping of the MSA failed.
	if( ! &check_presence_file($msa_stripped_file_path)){
		&error_state(
			"Stripping of the MSA failed. $msa_stripped_file_path file does not exist.",
			"Stripping of the MSA failed. Stripped MSA file does not exist.",
		);
	}
}


# gather statistics such as lengths and removed genes from the original and stripped MSA
sub gather_msa_stats(){
	# get length + ids of input MSA
	my $original_io 		= Bio::SeqIO->new(-file => $msa_file_path,-format => "fasta" );
	my $original_sequence_length	= 0;
	my @original_ids;
	while(my $seqObj= $original_io->next_seq()) {
		$original_sequence_length= $seqObj->length();
		push(@original_ids,$seqObj->primary_id());
	}
	$extra_aln_information{"msa"}{"Original MSA length"}	= $original_sequence_length;
	# get length + ids of stripped MSA
	my $stripped_io 		= Bio::SeqIO->new(-file => $msa_stripped_file_path,-format => "fasta" );
	my $stripped_sequence_length	= 0;
	my @stripped_ids;
	while( my $seqObj2 = $stripped_io->next_seq()) {
		$stripped_sequence_length	= $seqObj2->length();
		push(@stripped_ids,$seqObj2->primary_id());
	}
	$extra_aln_information{"msa"}{"Edited MSA length"}	= $stripped_sequence_length;
	# find out which genes were removed during the editing/stripping procedure
	my @removed_genes;
	my %lookup 			= map { $_ => undef } @stripped_ids;
	foreach my $gene_id ( @original_ids ) {
		if(not exists $lookup{$gene_id}){
			push(@removed_genes,$gene_id);
		}
	}
	if (scalar(@removed_genes) > 0){
		my $removed_genes_string 			= join(',',@removed_genes);
		$extra_aln_information{"msa"}{"Removed genes"}	= $removed_genes_string;
	}
}

# run the tree construction method algorithm
sub create_tree(){
	if( $tree_program eq "fasttree" ){
		my $tmp_tree_file_path	= $tree_file_path.".tmp.newick";
		system("FastTree -wag -gamma $msa_stripped_file_path > $tmp_tree_file_path");
		$executed_commands{"tree"}{"fasttree"} = "FastTree -wag -gamma \$MSA_FILE_PATH &gt; \$NEWICK_FILE_PATH";
		if( ! -e $tmp_tree_file_path ){
			&error_state(
				"FastTree didn't run correctly. $tmp_tree_file_path file not created.",
				"FastTree didn't run correctly. Newick file not created.",
			);
		}
		#necessary because of the following reasons:
		#1) empty bootstraps
		#2) possible mixup by notung of the bootstraps and branch lengths
		&_reformat_tree($tmp_tree_file_path,$tree_file_path);
	}
	elsif( $tree_program eq "phyml" ){
		# input of phyml is phylip file, which places limits on length of gene identifiers.
		# therefore, we convert the MSA alignment to phylip, and return the conversion table
		my $msa_phylip_file_path	= $msa_stripped_file_path.".phy";
		my $tmp_tree_file_path		= $msa_stripped_file_path.".phy_phyml_tree";	# create in phyml command, without giving name
		my %idswitch			= &faln2phylip2file($msa_stripped_file_path,$msa_phylip_file_path);
		system("phyml -i $msa_phylip_file_path -d aa -n 1 -b 100 -m WAG -f e -c 4 -a e -o n");
		$executed_commands{"tree"}{"phyml"}	= "phyml -i \$MSA_FILE_PATH -d aa -n 1 -b 100 -m WAG -f e -c 4 -a e -o n";
		if(! -e $tmp_tree_file_path ){
			&error_state(
				"PhyML didn't run correctly. $tmp_tree_file_path file not created.",
				"PhyML didn't run correctly. Newick file not created.",
			);
		}
		# replace the gene identifiers from the output file.
		&replace_newick_tree_ids($tmp_tree_file_path,$tree_file_path,\%idswitch);
	}
	elsif( $tree_program eq "raxml" ){
		my $output_dir			= `dirname $tree_file_path`;
		my $tree_file_path_name		= `basename $tree_file_path`;
		chomp($output_dir);
		chomp($tree_file_path_name);
		my $tmp_tree_file_path		= "$output_dir/RAxML_bestTree.$tree_file_path_name";
		my $raxml_command		= "raxmlHPC-PTHREADS-SSE3 -T 1 -f a -x 12345677 -p 12345677 -N 100 -m PROTGAMMAWAG -s $msa_stripped_file_path -w $output_dir -n $tree_file_path_name -k";
		system($raxml_command);
		$executed_commands{"tree"}{"raxml"}	= "raxmlHPC-PTHREADS-SSE3 -T 1 -f a -x 12345677 -p 12345677 -N 100 -m PROTGAMMAWAG -s \$MSA_FILE_PATH -w \$OUTPUT_DIR -n \$NEWICK_FILE_NAME -k";
		if( ! -e $tmp_tree_file_path ){
			&error_state(
				"RAxML didn't run correctly. $tmp_tree_file_path file not created.",
				"RAxML didn't run correctly. Newick file not created.",
			);
		}
		system("cp $tmp_tree_file_path $tree_file_path");
	}
	elsif( $tree_program eq "iqtree" ){
		my $tmp_tree_file_path	= "$msa_stripped_file_path.iqtree.newick";
		# my $iqtree_cmd 		= "iqtree-omp -st AA -s $msa_stripped_file_path -pre $tmp_tree_file_path -nt 1 -bb 1000 -mset JTT,LG,WAG,Blosum62,VT,Dayhoff -mfreq F -mrate R > $tmp_tree_file_path.log";
		my $iqtree_cmd 		= "iqtree -st AA -s $msa_stripped_file_path -pre $tmp_tree_file_path -nt 1 -bb 1000 -mset JTT,LG,WAG,Blosum62,VT,Dayhoff -mfreq F -mrate R > $tmp_tree_file_path.log";
		system($iqtree_cmd);
		# $executed_commands{"tree"}{"iqtree"}	= "iqtree-omp -st AA -s \$MSA_FILE_PATH -pre \$NEWICK_FILE_PATH -nt 1 -bb 1000 -mset JTT,LG,WAG,Blosum62,VT,Dayhoff -mfreq F -mrate R";
		$executed_commands{"tree"}{"iqtree"}	= "iqtree -st AA -s \$MSA_FILE_PATH -pre \$NEWICK_FILE_PATH -nt 1 -bb 1000 -mset JTT,LG,WAG,Blosum62,VT,Dayhoff -mfreq F -mrate R";
		my $model_cmd    	= "grep 'Best-fit model according to BIC:' $tmp_tree_file_path.iqtree | cut -f2 -d':' | tr -d ' \t\n\r\f' > $msa_file_path.iqtree.model ";
		system($model_cmd);
		system("mv $tmp_tree_file_path.contree $tree_file_path");
		system("rm $tmp_tree_file_path.*");
		my $model_info		= `cat $msa_file_path.iqtree.model`;
		chomp($model_info);
		$extra_model_information{"Selected IQ-Tree Model"}{$model_info}	= "http://www.iqtree.org/doc/Substitution-Models#protein-models";
	}
	else{
		&error_state("Unknown tree construction program $tree_program");
	}
	# check whether the tree file was created. If not, we throw an error because clearly the tree construction failed.
	if( ! &check_presence_file($tree_file_path)){
		&error_state(
			"Tree construction failed. $tree_file_path file does not exist.",
			"Tree construction failed. Newick file does not exist.",
		);
	}
}

# write extra data files so the info can be presented to the end-user
sub write_extra_data_files(){
	&write_extra_data_file(\%executed_commands,".commands");
	&write_extra_data_file(\%extra_model_information,".model");
	&write_extra_data_file(\%extra_aln_information,".aln_param");
}



#####################################################################################################
############## UTILITY FUNCTIONS ####################################################################
#####################################################################################################

# write a single extra data file to storage
# first input is double hash
# second input is file-extension suffix for file
sub write_extra_data_file($ $){
	my %extra_data		= %{$_[0]};
	my $file_suffix		= $_[1];
	if(scalar(keys(%extra_data)) > 0){
		my $extra_data_file_path	= $base_file_path.$file_suffix;
		open(EXTRA_DATA,">",$extra_data_file_path);
		foreach my $extra_data_entry_title(keys(%extra_data)){
			my %extra_data_step	= %{$extra_data{$extra_data_entry_title}};
			foreach my $extra_data_step_title(keys(%extra_data_step)){
				my $extra_data_value	= $extra_data_step{$extra_data_step_title};
				print EXTRA_DATA $extra_data_entry_title."\t".$extra_data_step_title."\t".$extra_data_value."\n";
			}
		}
		close(EXTRA_DATA);
	}
}


# Remove existing result files.
# This is done to prevent accidental re-use of incorrect results in case
# the produced hash-prefix wasn't unique
sub clear_existing_files(){
	if( -e $msa_file_path ){system("rm -f $msa_file_path");}
	if( -e $msa_stripped_file_path ){system("rm -f $msa_stripped_file_path");}
	if( -e $tree_file_path ){system("rm -f $tree_file_path");}
}



# Check whether a file is present.
# If not, wait for a certain time, then try again.
# If not at the end of the time-out the file is still not present, return false.
# Otherwise return true.
sub check_presence_file($){
	my $file_path		= $_[0];
	my $time_out		= 1;
	my $num_tries		= 5;

	# try X times to check for file presence, sleep for Y seconds in between.
	for(my $i=0;$i<$num_tries;$i++){
		if( -e $file_path ){
			return 1;
		}
		sleep($time_out);
	}
	#final try
	if( -e $file_path){
		return 1;
	}
	return 0;
}


# called in case the program halts in the middle of processing for some reason.
sub error_state($ $){
	my $error_all		= $_[0];
	my $error_server	= $error_all;
	my $error_email		= $error_all;
	if(scalar(@_)==2){
		$error_email	= $_[1];
	}
	if($email eq ""){
		print STDERR $error_server."\n";
		exit 1;
	}
	else{
		&send_email($error_email);
		exit 1;
	}
}


# send email to user to notify him the processing of the tree is finished.
sub send_email($){
	my $error	= "";
	if(scalar(@_) == 1){
		$error	= $_[0];
	}
	my $sendmail	= "/usr/lib/sendmail.postfix -t";
	my $from        = "From: noreply\@plaza.psb.vib-ugent.be\n";
	my $reply_to 	= "Reply-to: no-reply\@plaza.vib-ugent.be\n";
	my $subject	= "Subject: Interactive Phylogenetics Module processing finished\n";
	my $send_to     = "To: ".$email."\n";
	my $content;
	if($error eq ""){
		$content	= "The processing of the PLAZA Interactive Phylogenetics Module finished. You can access the results using this URL: ".$url."/".$base_file_name."\n\nThis result will be kept for 48h.\n\nKind regards,\nThe PLAZA team\n";
	}
	else{
		$content	= "The processing of the PLAZA Interactive Phylogenetics Module failed.\nLast error-message: $error.\n\nPlease try running the the PLAZA Interactive Phylogenetics Module again with a different set of parameters.\n\nKind regards,\nThe PLAZA team\n";
	}
	open(SENDMAIL,"|$sendmail") or die "Cannot open $sendmail: $!";
	print SENDMAIL $from;
	print SENDMAIL $reply_to;
	print SENDMAIL $subject;
	print SENDMAIL $send_to;
	print SENDMAIL "Content-type: text/plain\n\n";
	print SENDMAIL $content;
	close(SENDMAIL);
}


#####################################################################################################
############## TREE AND MSA PROCESSING FUNCTIONS ####################################################
#####################################################################################################

#replace the gene identifiers in a newick output file with the correct ones (due to ALN->phylip conversion)
sub replace_newick_tree_ids($ $ $){
	my $tmp_tree_file_path		= $_[0];
	my $tree_file_path		= $_[1];
	my %idswitch			= %{$_[2]};

	open (INPUT,"<$tmp_tree_file_path");
	my $constree			= "";
	while(<INPUT>){
		chomp;
		$constree.=$_;
	}
	close(INPUT);
	$constree 			=~ s/_//g;

	#restore original gene_id
	my @revert 			= split(/:/,$constree);
	my @revert2;
	foreach my $e (@revert){
		if ($e =~ /^(.+)(X\d+X)(.+)$/){
			if (exists $idswitch{$2}){
				$e = $1.$idswitch{$2}.$3;
			}
		}
		if ($e =~ /^(X\d+X)(.+)$/){
			if (exists $idswitch{$1}){
				$e = $idswitch{$1}.$2;
			}
		}
		if ($e =~ /^(.+)(X\d+X)$/){
			if (exists $idswitch{$2}){
				$e = $1.$idswitch{$2};
			}
		}
	   	push @revert2, $e;
	}
	my $constree2	= join(':',@revert2);
	open (OUTPUT,">$tree_file_path");
	print OUTPUT $constree2."\n";
	close(OUTPUT);
}


# converts a multifasta style type of MSA to a phylip style type of MSA.
# necessary for phyml to work.
# return associated gene id conversion
sub faln2phylip2file ( $ $ ){
	my $aln_file		= $_[0];
	my $phylip_file		= $_[1];

	my %idswitch;

	my($numberseq,%bib,$woord,@seq,%vollseq,$k,$blocks,$blocksint,$l,@word,$i);
	my($m,$z,$o,$j,$letters,$konijn);
	my $tekens		= 0;

	my %msa_alignment	= &input2hash($aln_file);

	$numberseq = 0;
	foreach my $gene_id (keys %msa_alignment){
		$numberseq++;
		my $sequence		= $msa_alignment{$gene_id};
		my $short 		= "X".sprintf("%06d",$numberseq)."X";
		$idswitch{$gene_id}	= $short;
		$idswitch{$short}	= $gene_id;

  		$bib{$numberseq} 	= $idswitch{$gene_id}; # so bib contains as key the seq nr 1 and as value 000000001
		@{$vollseq{$idswitch{$gene_id}}} = split (//,$sequence);
		$tekens 		= scalar @{$vollseq{$idswitch{$gene_id}}};
	}
 	if ($tekens == 0){
		print STDERR "No sequences in MSA file before phylip conversion ".$aln_file."\n";
		exit 1;
	}

	open (FOUT, ">$phylip_file");
	$blocks 			= $tekens/50;
	$blocksint 			= ceil($blocks);
	$konijn				= 0;

	if ($blocksint > 1){
		print FOUT "$numberseq $tekens\n";
		foreach $l(1..$numberseq){
			@word 		= split (//, $bib{$l});
			$letters 	= scalar(@word);
			foreach $i(1..10-$letters){
				print FOUT " ";
			}
			print FOUT $bib{$l}." ";
			foreach $j(0..49){ # prints first block of 50 residues
				print FOUT "${$vollseq{$bib{$l}}}[$j]";
			}
			print FOUT "\n"
		}
		foreach $z(1..$blocksint-2){
			print FOUT "\n";
			foreach $m(1..$numberseq){
				foreach $k($z*50..(($z+1)*50)-1){
					print FOUT "${$vollseq{$bib{$m}}}[$k]";
				}
				print FOUT "\n";
			}
			$konijn = $z;
		}
		print FOUT "\n";
		foreach $o(1..$numberseq){
			foreach $b((($konijn+1)*50)..$tekens-1){
				print FOUT "${$vollseq{$bib{$o}}}[$b]";
			}
			print FOUT "\n";
		}
	}
	else{
		print FOUT "$numberseq $tekens\n";
		foreach $l(1..$numberseq){
			@word = split (//, $bib{$l});
			$letters = scalar(@word);
			foreach $i(1..10-$letters){
				print FOUT " ";
			}
			print FOUT $bib{$l}." ";
			foreach $j(0..$tekens-1){
				print FOUT "${$vollseq{$bib{$l}}}[$j]";
			}
			print FOUT "\n"
		}
	}
	close FOUT;
	return (%idswitch);
}









# read content of phylogenetic tree file into memory
sub read_tree_file($){
	my $input_tree_file_path	= $_[0];
	#open output file and read contents into memory
	my $constree;
	open (TREE, "<$input_tree_file_path");
	while (<TREE>){
		chomp;
		$constree.=$_;
	}
	close TREE;
	return $constree;
}

# write newick tree to file
sub write_tree_file($ $){
	my $output_tree_file_path	= $_[0];
	my $tree_content		= $_[1];
	open (TREE,">$output_tree_file_path");
	print TREE $tree_content."\n";
	close(TREE);
}


#change bootstrap values represented as fractions to integers (for use in Plaza) and add missing bootstraps (bootstrap=100 for missing)
sub _reformat_tree {
	my $input_tree_file_path	= $_[0];
	my $output_tree_file_path	= $_[1];

	my $tree_in			= &read_tree_file($input_tree_file_path);
	my $tree_reformat;

        open(my $in_fh, "<", \$tree_in);
        open(my $out_fh, ">", \$tree_reformat);

        my $treeio			 = Bio::TreeIO->new(
							-format => 'newick',
							-fh => $in_fh,
							-internal_node_id => 'bootstrap'
							);

        my $tree_out 			= Bio::TreeIO->new(
							-format => 'newick',
							-fh => $out_fh,
							-internal_node_id => 'bootstrap'
							);

	while( my $tree = $treeio->next_tree ){
		for my $node ( $tree->get_nodes ){
			if (not defined $node->bootstrap){
				my $bootstrap_new = 100;
				$node->bootstrap($bootstrap_new);
			}
			else{
				my $bootstrap = $node->bootstrap;
				my $bootstrap_new = int(($bootstrap * 100) + 0.5);
				if ($bootstrap_new > 100){
					$bootstrap_new = $bootstrap;
				}
				$node->bootstrap($bootstrap_new);
			}
		}
		$tree_out->write_tree($tree);
        }
	close($in_fh);
	close($out_fh);

	&write_tree_file($output_tree_file_path,$tree_reformat);
}





# function to strip the MSA. Either column-based, row based or both.
sub double_strip_msa ( $ $ $ ){
	#compared with strip_msa, this routine also removed partial seqs prior to removing bad positions & gaps
	my $msa_path			= $_[0];
	my $stripped_msa_path		= $_[1];
	my $strip_method		= $_[2];


	# settings for the various ways the MSA can be stripped (or not)
	# perform alignment stripping in general. If set to false, then the the msa will be equal to the stripped_msa, and the comment & strip_params will be empty
	# all other options will also have an impact on which kind of data is returned.
	my $perform_msa_stripping			= 1;
	my $perform_msa_stripping_genes			= 1;
	my $perform_msa_stripping_genes_partial		= 1;
	my $perform_msa_stripping_positions		= 1;
	my $perform_msa_stripping_positions_maxgap	= 0.1;
	my $perform_msa_stripping_positions_percentile	= 50;
	my $perform_msa_stripping_positions_percodon	= 1;
	my $perform_msa_stripping_positions_blosum	= "BLOSUM62";

	if($strip_method eq "column"){
		$perform_msa_stripping_genes		= 0;
		$perform_msa_stripping_genes_partial	= 0;
	}
	elsif($strip_method eq "row"){
		$perform_msa_stripping_positions	= 0;
	}
	else{
		#default settings.
	}

 	my %input_msa_align		= &input2hash($msa_path);  		# key is AC, value is alignment string
	my %msa_align			= %input_msa_align;		#make copy, we continue working on the msa_align from here on out.

	#input unstripped alignment
  	my $msa				= "";
  	my $msa_length			= 0;
  	foreach my $gene_id (keys %msa_align){
		$msa.= ">$gene_id;$msa_align{$gene_id}";
		$msa_length=length($msa_align{$gene_id});
	}

	# ----------------------------------------------------------------------------------------------------------
	# Remove alignment positions where only 1 gene has a non-space value.
	# This is done as first step, in order to achieve 2 things:
	# 1) If a small family is processed, the statistics (median, etc..) can be a bit tricky. However, this method is a very raw cutoff.
	# 2) It can drastically speed up the statistics for larger families, because much less positions need to be processed.
	# ----------------------------------------------------------------------------------------------------------
	if($perform_msa_stripping_positions){
		%msa_align		= &strip_msa_positions_single_presence(\%msa_align);
	}


	# ----------------------------------------------------------------------------------------------------------
	# Perform the removal of genes in this step
	# ----------------------------------------------------------------------------------------------------------
	if($perform_msa_stripping_genes){

		# -------------------------------------------------------------------------------------------------------
		# remove genes/entries from the MSA which have only a partial length (compared to the average in the MSA).
		# this is done by looking at the number of gaps per gene, and comparing this against to other number of
		# gaps over the entire family.
		# Since every sequence in the MSA is of equal length, we can get the gap percentage per gene.
		# -------------------------------------------------------------------------------------------------------
		if($perform_msa_stripping_genes_partial){
			my @partial_stripped_msa	= &strip_msa_genes_partial(\%msa_align);
			%msa_align			= %{$partial_stripped_msa[0]};
  		}
	}


	# ----------------------------------------------------------------------------------------------------------
	# Perform the removal of positions in this step, based on the maximum gap percentage, etc.
	# ----------------------------------------------------------------------------------------------------------
	if($perform_msa_stripping_positions){
		%msa_align		= &strip_msa_positions(\%msa_align,$perform_msa_stripping_positions_blosum,$perform_msa_stripping_positions_maxgap,$perform_msa_stripping_positions_percentile,$perform_msa_stripping_positions_percodon);

		if($perform_msa_stripping_genes_partial){
			my @partial_stripped_msa	= &strip_msa_genes_partial(\%msa_align);
			%msa_align			= %{$partial_stripped_msa[0]};
  		}
	}


	#now create the data structure for the output.
   	my $msa_strip				= "";
   	my $msa_strip_length			= 0;
   	foreach my $gene_id (keys %msa_align){
		$msa_strip			= $msa_strip.">$gene_id;$msa_align{$gene_id}";
		$msa_strip_length		= length($msa_align{$gene_id});
	}


	print STDOUT "Stripped \t".scalar(keys %input_msa_align)." genes (input) -> ".scalar(keys %msa_align)." genes (after removal) \t".$msa_length."AA (original length) -> ".$msa_strip_length."AA (stripped length)\n";

	&hash2output(\%msa_align,$stripped_msa_path);
}




# writes a hash (gene2sequence) to a fasta file
sub hash2output($ $){
	my %gene2sequence	= %{$_[0]};
	my $output_file_path	= $_[1];
	open (FOUT,">$output_file_path");
	foreach my $gene_id(keys %gene2sequence){
		my $sequence	= $gene2sequence{$gene_id};
		print FOUT ">".$gene_id."\n";
		print FOUT $sequence."\n";
	}
	close(FOUT);
}



# reads a multifasta or alignment file into hash structure
sub input2hash ( $ ){
	my ($key);
	my (%fasta_hash);
	my $fin = $_[0];
	open (FIN, "$fin");
	while (<FIN>){
		chomp;
		if (/^>(\S+)/){
			$key=$1;
			if (exists $fasta_hash{$key}){
				print STDERR "* Double entries in input file: $key!\n";
				exit;
			}
     		}
    		else{
			$key || die "Input is not in fasta format!";
      			s/\s+//g;
			$fasta_hash{$key}.=$_;
     		}
   	}
	close FIN;
	return (%fasta_hash);
}




# this method will strip away all positions in an MSA where those positions are a non-gap in only a single gene.
# e.g. The fifth position in the MSA below would be removed, since it is a non-gap in only a single sequence.
# AAAAA----CAA
# AAAA-AAAAAAA
# AAAA-AAAAAAA
# The stripped MSA is then returned. Logging messages are send to STDERR.
sub strip_msa_positions_single_presence( $ ){
	#input is reference to MSA hash
	my %msa_align		= %{$_[0]};

	if(scalar(keys(%msa_align))==0){
		return %msa_align;
	}

	my %seqindex; # key is position in alignment, value is #genes with non-gap residue
	foreach my $gene_id (keys %msa_align){
		my @s = split(//,$msa_align{$gene_id});
		for(my $i=0;$i<scalar(@s);$i++){
			my $c	= $s[$i];
			if($c ne '-'){
				$seqindex{$i}++;
			}
		}
	}

	my %badpositions;
	foreach my $i (keys %seqindex){
		if ($seqindex{$i} == 1){
			$badpositions{$i}++;
		}
	}

	print STDERR scalar(keys %badpositions)." positions removed during initial test (if only 1 gene has non-space value in MSA on position X)!\n";

	my %clean_align;
	foreach my $gene_id (keys %msa_align){
		my @s = split(//,$msa_align{$gene_id});
		for(my $i=0;$i<scalar(@s);$i++){
			if (! exists $badpositions{$i}){
				push @{$clean_align{$gene_id}}, $s[$i];
			}
     		}
		$msa_align{$gene_id} = join("",@{$clean_align{$gene_id}});
	}
	#output is stripped MSA hash
	return %msa_align;
}



# This method will remove genes from an MSA that are only partial genes (comparing the number of gaps in their alignment to the overal number of gaps in the entire MSA).
# Genes that are removed are named in the 'comment' variable, which is returned to the callee method together with the adapted MSA
sub strip_msa_genes_partial($){
	my %msa_align			= %{$_[0]};

	if(scalar(keys(%msa_align))==0){
		my @result	= (\%msa_align);
		return @result;
	}

	my $gapstat 			= Statistics::Descriptive::Full->new();
	my %gapcount;
	foreach my $gene_id (keys %msa_align){
		my @s 			= split(//,$msa_align{$gene_id});
		$gapcount{$gene_id} 	= 0;	#we have to initialize this value, otherwise the gapstat statement could work with unitialized values.
		foreach my $c (@s){
			if ($c eq '-'){
				$gapcount{$gene_id}++
			}
   		}
		$gapstat->add_data(sprintf("%2.2f",$gapcount{$gene_id} / length($msa_align{$gene_id})));
	}


    	# the median gap percentage times two is the maximum gap percentage we allow, before calling a gene partial
	my $pgapthreshold 		= $gapstat->median*2;
	#if median gap percentage is > 0.5, then the threshold is over 1. We then take the value corresponding to the 95th percentile.
	if ( $pgapthreshold >=1 ){
		$pgapthreshold 		= $gapstat->percentile(95);
	}
	#If median gap percentage is 0 (or extremely small), then we have a problem because ALL genes above the median would be classified
	#as being partial if not other checks are done on the value of the pgapthreshold.
	#However, if we add a rough breakdown measure (i.e. only remove genes if above threshold and threshold must be bigger than 0.5),
	#then partial genes will also NOT be removed.
	if( $pgapthreshold <0.5){
		$pgapthreshold		= 0.5;
	}

	print STDERR "Removal partial genes stats\t".scalar(keys %msa_align)."\tGAP $pgapthreshold (".$gapstat->median.")\n";

       	my $number_partial_deleted_genes	= 0;
	#remove the genes from the MSA when the gap threshold is exceeded, but only if the gap threshold is bigger than the 0.5
	foreach my $gene_id (keys %gapcount){
		if ( ($gapcount{$gene_id} / length($msa_align{$gene_id})) >= $pgapthreshold ){
			print STDERR "Remove $gene_id\t".sprintf("%2.2f",$gapcount{$gene_id} / length($msa_align{$gene_id}))."\n";
			delete $msa_align{$gene_id};
			$number_partial_deleted_genes++;
		}
	}

	if($number_partial_deleted_genes > 0){
		print STDERR "Removal of partial genes\t".scalar(keys %msa_align)." genes (new count) \n";
	}
	else{
		print STDERR "Removal of partial genes\t".scalar(keys %msa_align)." genes (new count) --> zero genes removed\n";
	}
	my @result		= (\%msa_align);
	return @result;
}



# This method will remove positions from the MSA that are not conserved enough. This is done by either exceeding the maximum percentage of gaps, or by having too different amino-acids on a given position.
# Several options influence the selection of positions to be removed (maxgap, percentile, percodon).
# Input and output consist of the MSA in its (un)stripped form.
sub strip_msa_positions($ $ $ $ $){
	my %msa_align			= %{$_[0]};
	my $blosum_matrix		= $_[1];
	my $max_gap			= $_[2];
	my $percentile			= $_[3];
	my $per_codon			= $_[4];


	if(scalar(keys(%msa_align))==0){
		return %msa_align;
	}

	# read the blosum matrix into memory, used for scoring the similarity percentages per position.
	my %blosum			= &read_blosum($blosum_matrix);

	# Construct a data structure for editing the alignment
	my @align;
	my @gene_ids			= keys(%msa_align);
	for (my $gene_index=0;$gene_index<scalar(@gene_ids);$gene_index++){
		my $gene_id		= $gene_ids[$gene_index];
		my $sequence		= $msa_align{$gene_id};
		my $n			= 0;
		for (my $i=0;$i<length($sequence);$i+=$per_codon){
			$align[$n][$gene_index]	= substr($sequence,$i,$per_codon); # @align is double array; first index $n is position, second index $gene_index represents AC
			$n++;
		}
  	}
	my $input_length_msa		= scalar(@align);

	# Strip the alignment
	my $last_anchor			= -1;
	for (my $i=0;$i<scalar(@align);$i++ ){ # iterates over positions
		my @column		= @{$align[$i]};
		if (&is_anchor(\@column,$max_gap,$percentile,$per_codon,\%blosum)){
			$last_anchor	= $i;
		}
		if (&is_gap(\@column,$max_gap,$per_codon) || ( $last_anchor == -1 )){
			$last_anchor++;
      			do{
				splice(@align,$last_anchor,1);
       			}
			until ( ($last_anchor == scalar(@align)) || &is_anchor(\@{$align[$last_anchor]},$max_gap,$percentile,$per_codon,\%blosum));
			$i	= $last_anchor;
     		}
   	}

	if($last_anchor < $#align){
		splice(@align,$last_anchor+1);
	}

	# Store the edited alignment again in the original hash structure
   	my %new_msa_align		= ();
	my $output_length_msa		= scalar(@align);
	for(my $position=0;$position<scalar(@align);$position++){
		my @position_data	= @{$align[$position]};
		for (my $gene_index=0;$gene_index<scalar(@position_data);$gene_index++){
			my $gene_id	= $gene_ids[$gene_index];
			if(not exists $new_msa_align{$gene_id}){
				$new_msa_align{$gene_id}	= "";
			}
			$new_msa_align{$gene_id} 		= $new_msa_align{$gene_id} . $position_data[$gene_index];
      		}
    	}
	%msa_align			= %new_msa_align;		# set the MSA we're working on equal to the newly stripped MSA.

	print STDERR "Positions stripped\t".$input_length_msa."(input)->".$output_length_msa."(output) :: ".($input_length_msa-$output_length_msa)." positions removed\n";

	#return the stripped alignment
	return %msa_align;
}


# test whether or not a position in an alignment is a gap or not.
# input is an array of data from a given position in the alignment (e.g. one amino-acid per gene, at location X)
# returns 1 if there are too many gaps, 0 otherwise.
# Parameter 1: reference to array with data for a given position in the alignment
# Parameter 2: the percentage in the alignment (between 0 and 1) that has to be gap, for this position to be counted as a 'gap'
# Parameter 3: indicates how 'long' the data is per position. Either 1 (for amino acids) or 3 (for DNA).
sub is_gap ( $ $ $ ) {
	my @amino_acids		= @{$_[0]};
	my $max_gap_percentage	= $_[1];
	my $per_codon		= $_[2];
	my $gaps		= 0;
	my $test		= '-' x $per_codon; #$per_codon should be 1 for protein alignments, and 3 for DNA alignments
	foreach my $position(@amino_acids){
		if($position eq $test){
			$gaps++;
		}
	}
	my $result		= 0;
	if($gaps/scalar(@amino_acids) > $max_gap_percentage){
		$result		= 1;
	}
	return $result;
}


# Determines whether or not a position in an alignment is concordant or not (50% or more same/similar amino-acid, taking the BLOSUM matrix into account)
# return 1 if the position is an anchor position (conserved position) or return 0 if the position is not conserved.
# Parameter 1: reference to the array with data for a given position in the alignment
# Parameter 2: the percentage in the alignment (between 0 and 1) that has to be gap, for this position to be counted as a 'gap'
# Parameter 3: indicates how 'long' the data is per position. Either 1 (for amino acids) or 3 (for DNA).
# Parameter 4: the percentile cutoff for a positive score in the BLOSUM matrix comparison.
sub is_anchor ( $ ){
 	my @column		= @{$_[0]};			#indicates all the data in a column position of an MSA
	my $max_gap_percentage	= $_[1];
	my $percentile		= $_[2];
	my $per_codon		= $_[3];
	my %blosum		= %{$_[4]};

	my $result		= 0;
	#if there is no data, return 0. If there is only one data point, return 1.
	my $num_data		= scalar(@column);
	if($num_data == 0){return 0;}
	if($num_data == 1){return 1;}

	my $gap			= '-' x $per_codon;	#per_codon should be 1 for protein MSA, and 3 for DNA MSA
	my $test		= 0;

	my $is_gap		= &is_gap(\@column,$max_gap_percentage,$per_codon);

	if ($is_gap != 1){	#if the position is a gap, the position cannot be an anchor.
		my $blosums	= Statistics::Descriptive::Full->new();
		if ($per_codon == 3){
			@column	= map{$_=&_translate_simple($_)}(@column);
     		}
		if ( $num_data > 2 ){
			foreach my $i (0..($#column-1)){			# $#column is the last index in column, so we go from zero to size(@column)-2
				if( $column[$i] eq $gap){next;}			# if the current amino-acid (or 3 nucleotides) is a gap, we skip it
				foreach my $j ($i+1..$#column){			# make comparison matrix. Matrix should be symmetric, so we can skip half the computations.
					if( $column[$j] eq $gap){next;}		# if the current amino-acid (or 3 nucleotides) is a gap, we skip it
					$blosums->add_data($blosum{$column[$i]}{$column[$j]});
	 			}
       			}
			if(!defined $blosums->percentile($percentile)){ #cannot compute the percentile due to not enough data points.
				$result		= 0;
			}
			elsif($blosums->percentile($percentile)>=0){# the blossum62 values range from -4 to 11 (see https://en.wikipedia.org/wiki/BLOSUM ).
				$result		= 1;
			}
		}
		elsif($num_data == 2){	#only 2 values in the data. Other possibilties have been checked above.
			if($blosum{$column[0]}{$column[1]} >= 0){
				$result		= 1;
			}
     		}
	}
  	return $result;
}



#read blosum matrix into memory
sub read_blosum($){
	my $blosum_matrix_type	= $_[0];
	my $blosum_directory	= "$Bin/../data/blosum_files/";
	my $blosum_file		= $blosum_directory.$blosum_matrix_type.".txt";
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
	return (%matrix)
}
