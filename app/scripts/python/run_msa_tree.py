"""
A wrapper script used to create GF MSA & phylogenetic trees in TRAPID. It performs the following:
 * Create a protein multifasta file for the selected set of input sequences (GF + species / sequence selection)
 * Call a modified version of @mibel's tree creation pipeline (MSA, MSA editing, tree creation)
 * Upload the output to the database

The purpose of such a script is twofold: consistence with the rest of the PLAZA framework, and easy to maintain if the
phylogenetics pipeline gets updated.
"""

# Usage example: python run_msa_tree.py <exp_id> <trapid_gf_id> <exp_dir> <base_dir> <trapid_db> <db_host> <db_user> <db_pswd>

import argparse
import common
import glob
import json
import MySQLdb as MS
import os
import subprocess
import sys


def parse_arguments():
    """Parse command-line arguments and return them as a dictionary"""
    cmd_parser = argparse.ArgumentParser(description='Create a GF MSA/tree for a TRAPID experiment. ',
        formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    cmd_parser.add_argument('exp_id', type=int, help='TRAPID experiment ID. ')
    cmd_parser.add_argument('gf_id', type=str, help='The gene family which a MSA / tree is generated for. ')
    cmd_parser.add_argument('tmp_dir', type=str, help='Temporary experiment directory. ')
    cmd_parser.add_argument('base_dir', type=str, help='Base TRAPID `scripts` directory. ')
    # TRAPID DB details
    cmd_parser.add_argument('db_name', type=str, help='TRAPID DB name. ')
    cmd_parser.add_argument('db_host', type=str, help='TRAPID DB host. ')
    cmd_parser.add_argument('db_user', type=str, help='TRAPID DB username. ')
    cmd_parser.add_argument('db_pswd', type=str, help='TRAPID DB password. ')
    # MSA/tree creation parameters
    cmd_parser.add_argument('-mp', '--msa_program', type=str, choices={'mafft', 'muscle'},
                            default="muscle", help='The program to use to generate the MSA')
    cmd_parser.add_argument('-me', '--msa_editing', type=str, choices={'none', 'column', 'row', 'column_row'},
                            default="column", help='MSA editing')
    cmd_parser.add_argument('-tp', '--tree_program', type=str, choices={'fasttree', 'iqtree', 'phyml', 'raxml'},
                            default="fasttree", help='The program to use to build the phylogenetic tree')
    cmd_parser.add_argument('-mo', '--msa_only', action='store_true', default=False,
                            help='Stop after generating the MSA (i.e. do not edit MSA, do not create tree). ')
    # Optional arguments
    cmd_parser.add_argument('-rh', '--ref_db_host', type=str, default=None,
                            help='Reference database host. If no value is provided, we assume the host is the same as `db_host`. ')
    cmd_parser.add_argument('-ru', '--ref_db_user', type=str, default=None,
                            help='Username to connect to the reference database. Assumed to be the same as `db_user` if no value is provided. ')
    cmd_parser.add_argument('-rp', '--ref_db_pswd', type=str, default=None,
                            help='Password to connect to the reference database. Assumed to be the same as `db_pswd` if no value is provided. ')
    # Temporary files / verbosity (for debugging purposes)
    cmd_parser.add_argument('-k', '--keep_tmp', action='store_true', default=False, help='Keep temporary files')
    cmd_parser.add_argument('-v', '--verbose', action='store_true', default=False, help='Print debug/progress information (verbose mode). ')
    cmd_args = cmd_parser.parse_args()
    return vars(cmd_args)


def get_exp_data(exp_id, trapid_db_data, verbose=False):
    """Get reference database name and gene family type used for experiment `exp_id` from TRAPID DB. """
    if verbose:
        sys.stderr.write("[Message] Retrieve data for experiment '%d' from TRAPID DB. \n" % exp_id)
    query_str = "SELECT `used_plaza_database`, `genefamily_type` FROM `experiments` WHERE `experiment_id`='{exp_id}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(query_str.format(exp_id=exp_id))
    exp_data = cursor.fetchone()
    db_conn.close()
    if not exp_data:
        sys.stderr.write("[Error] Impossible to retrieve experiment data (experiment '%d')!\n" % (exp_id))
        sys.exit(1)
    return exp_data


def get_ref_db_sqce_type(trapid_db_data, ref_db_name, verbose=False):
    """Get sequence type (`DNA` or `AA`) of reference database `ref_db_name` from TRAPID DB. """
    if verbose:
        sys.stderr.write("[Message] Retrieve sequence type for reference database '%s' from TRAPID DB. \n" % ref_db_name)
    query_str = "SELECT `seq_type` FROM `data_sources` WHERE `db_name`='{ref_db_name}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(ref_db_name=ref_db_name))
    sqce_type = cursor.fetchone()[0]
    db_conn.close()
    if not sqce_type:
        sys.stderr.write("[Error] Impossible to retrieve sequence type for reference database '%s'!\n" % (ref_db_name))
        sys.exit(1)
    if sqce_type not in ['DNA', 'AA']:
        sys.stderr.write("[Error] Invalid sequence type for reference DB '%s'!\n" % ref_db_name)
    return sqce_type


def get_gf_data(exp_id, gf_id, trapid_db_data, verbose=False):
    """
    Retrieve data for gene family `gf_id` of experiment `exp_id` from TRAPID database (accessed using information in `trapid_db_data`).
    Return it as a dictionary (field:value).
    """
    if verbose:
        sys.stderr.write("[Message] Retrieve data for GF '%s' (experiment '%d') from TRAPID DB. \n" % (gf_id, exp_id))
    query_str = "SELECT * FROM `gene_families` WHERE `experiment_id`='{exp_id}' AND `gf_id`='{gf_id}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(query_str.format(exp_id=exp_id, gf_id=gf_id))
    gf_data = cursor.fetchone()
    db_conn.close()
    if not gf_data:
        sys.stderr.write("[Error] No GF data could be retrieved (GF '%s', experiment '%d')!\n" % (gf_id, exp_id))
        sys.exit(1)
    return gf_data


def load_transl_tables(transl_tables_path, verbose=False):
    """Load translation tables from `transl_tables_path` (path to TRAPID translation tables JSON file). """
    if verbose:
        sys.stderr.write("[Message] Read translation tables data from '%s'...\n" % transl_tables_path)
    if not os.path.exists(transl_tables_path):
        sys.stderr.write("[Error] Couldn't find the translation tables (%s)!\n " % transl_tables_path)
        sys.exit(1)
    try:
        transl_tables=json.loads(open(transl_tables_path, 'r').read())
    except:
        sys.stderr.write("[Error] Incorrectly formatted translation tables JSON file (%s)!\n" % transl_tables_path)
        sys.exit(1)
    return transl_tables


# TODO: fix this function! Start codons are ignored as of now...
def translate_dna_to_aa(dna_string, transl_tables, transl_table_idx=1):
    """Translate a DNA string `dna_string` into AA, using translation table `transl_table_idx` from `transl_tables`. """
    # Check translation table index
    if str(transl_table_idx) not in transl_tables.keys():
        sys.stderr.write('[Warning] the translation table index `%d` couldn\'t be found, use default (1) instead.\n' % transl_table_idx)
        transl_table_idx = 1
    used_transl_table = transl_tables[str(transl_table_idx)]
    dna_to_translate = dna_string
    dna_translated = []
    # Check the length of the sequence. Add some `N` if not a multiple of 3.
    while len(dna_to_translate) % 3 != 0:
         dna_to_translate+='N'
    # Translate DNA to AA using selected translation table
    for i in range(0, len(dna_to_translate), 3):
        codon = dna_to_translate[i:i+3]
        # The start codons are actually also in the table data, we should not ignore them!
        # First check for four-folod degenerate
        if codon[0:2] in used_transl_table["table"]:
            dna_translated.append(used_transl_table["table"][codon[0:2]])
        # Check for normal codon
        elif codon in used_transl_table["table"]:
            dna_translated.append(used_transl_table["table"][codon])
        else:
            dna_translated.append("X")
    return ''.join(dna_translated)


def get_gf_sqces(gf_data, ref_db_data, gf_type, ref_sqce_type, transl_tables, verbose=False):
    """
    Retrieve GF reference/external protein sequences for MSA/tree creation & return them as a sqce_id:sqce dictionary.
    `gf_data`: all GF data retrieved from the TRAPID database (output of `get_gf_data()`).
    `ref_db_data`: reference DB data (list with parameters needed for `common.db_connect()`)
    `gf_type`: GF type of the current TRAPID exepriment
    `ref_sqce_type`: sequence type of reference database sequences (DNA or AA)
    `transl_tables`: translation tables dictionary
    """
    if gf_type not in ['HOM', 'IORTHO']:
        sys.stderr.write("[Error] Invalid GF type ('%s'), cannot retrieve GF sequences!\n" % gf_type)
    # Get used species (spacies name) based on `gf_data` tax ids
    species_query_str = "SELECT `species` FROM `annot_sources` WHERE `tax_id` IN ({tax_id_str});"
    tax_id_str = "'%s'" % "','".join(gf_data['used_species'].split(','))
    used_species = set([])
    used_genes = set([])
    used_sqces = {}
    db_conn = common.db_connect(*ref_db_data)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(species_query_str.format(tax_id_str=tax_id_str))
    for rec in cursor.fetchall():
        used_species.add(rec['species'])  # To replace (update)?
    # Would it be better to fetch filtered data directly?
    gf_data_query_str = "SELECT gene_id, species FROM `gf_data` WHERE `gf_id` = '{gf_id}';"
    cursor.execute(gf_data_query_str.format(gf_id=gf_data['plaza_gf_id']))
    for record in common.ResultIter(cursor):
        if record['species'] in used_species:
            used_genes.add(record['gene_id'])
    # If we are dealing with an IORTHO GF, further filtering is required (only keep sequences that are in `gf_content`)
    if gf_type == "IORTHO" and gf_data['gf_content']:
        to_remove = set(gf_data['gf_content'].split(','))
        used_genes = used_genes - to_remove
    # Retrieve and translate sequences for genes in `used_genes`
    sqce_query_str = "SELECT `gene_id`, `seq`, `transl_table` FROM annotation  WHERE `gene_id` IN ({gene_id_str});"
    gene_id_str =  "'%s'" % "','".join(sorted(list(used_genes)))
    # print sqce_query_str.format(gene_id_str=gene_id_str)
    cursor.execute(sqce_query_str.format(gene_id_str=gene_id_str))
    # Store & return translated sequences
    if ref_sqce_type == 'DNA':
        for record in common.ResultIter(cursor):
            used_sqces[record['gene_id']] = translate_dna_to_aa(dna_string=record['seq'], transl_tables=transl_tables, transl_table_idx=record['transl_table'])
    else:
        for record in common.ResultIter(cursor):
            used_sqces[record['gene_id']] = record['seq']
    db_conn.close()
    return used_sqces


def get_transcript_sqces(gf_data, trapid_db_data, transl_tables, verbose=False):
    """
    Retrieve GF transcript sequences for MSA/tree creation, translate them & return them as a sqce_id:sqce dictionary.
    `gf_data`: all GF data retrieved from the TRAPID database (output of `get_gf_data()`).
    `trapid_db_data`: TRAPID DB data (list with parameters needed for `common.db_connect()`)
    `transl_tables`: translation tables dictionary
    """
    trs_sqces = {}
    query_str = "SELECT `transcript_id`, UNCOMPRESS(`orf_sequence`) as `orf_sequence`, `transl_table` FROM `transcripts` WHERE `experiment_id`='{exp_id}' AND `gf_id`='{gf_id}';"
    excluded_trs = set([])
    if gf_data['exclude_transcripts']:
        excluded_trs = set(gf_data['exclude_transcripts'].split(','))
    if verbose:
        sys.stderr.write("[Message] These transcripts will be excluded: %s.\n" % ", ".join(excluded_trs))
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(query_str.format(exp_id=gf_data['experiment_id'], gf_id=gf_data['gf_id']))
    for record in common.ResultIter(cursor):
        if record['transcript_id'] not in excluded_trs:
            trs_sqces[record['transcript_id']] = translate_dna_to_aa(dna_string=record['orf_sequence'], transl_tables=transl_tables, transl_table_idx=record['transl_table'])
    return trs_sqces


def create_multifasta_file(gf_sqces, trs_sqces, tmp_dir, exp_id, gf_id):
    """Create a FASTA file with sequences from `trs_sqces` and `gf_sqces` sqce_id:sqce dictionaries. """
    # Output file name
    multifasta_file = os.path.join(tmp_dir, '_'.join(['sqces', str(exp_id), gf_id]) + '.faa')
    with open(multifasta_file, 'w') as out_file:
        for sqce_id, sqce in sorted(gf_sqces.items()):
            out_file.write(">%s\n" % sqce_id)
            out_file.write(sqce + '\n')
        for trs_id, sqce in sorted(trs_sqces.items()):
            out_file.write(">%s\n" % trs_id)
            out_file.write(sqce + '\n')
    return multifasta_file


def run_phylogenetics_pipeline(exp_id, gf_id, tmp_dir, base_dir, multifasta_file, msa_program, msa_editing, tree_program, msa_only, verbose=False):
    """Run PLAZA phylogenetics pipeline & return output files as dictionary. """
    # Path of PLAZA phylogenetics pipeline perl script
    script_path = os.path.join(base_dir, 'perl', 'create_msa_tree.pl')
    # Output files
    base_path = os.path.join(tmp_dir, "msa_tree_%s" % gf_id)
    msa_out_file = base_path + ".aln"
    msa_stripped_out_file = base_path + ".stripped.aln"
    tree_out_file = base_path + ".newick"
    cmd_str = "perl -w {} --base-path {} --fasta-path {} --msa-path {} --msa-stripped-path {} --tree-path {} --msa-program {} --tree-program {} --editing-program {}"
    # Format cmd and run! No need to check MSA/tree parameters because we already checked with argparse
    formatted_cmd = cmd_str.format(script_path, base_path, multifasta_file, msa_out_file, msa_stripped_out_file, tree_out_file, msa_program, tree_program, msa_editing)
    if msa_only:
        formatted_cmd = formatted_cmd + " --msa_only yes"
    if verbose:
        sys.stderr.write("[Message] Call PLAZA phylogenetics pipeline with command: %s\n" % formatted_cmd)
    job = subprocess.Popen(formatted_cmd, shell=True)
    job.communicate()
    return {"base_path": base_path, "msa_out": msa_out_file, "msa_stripped_out": msa_stripped_out_file, "tree_out": tree_out_file}


def store_msa_db(msa_file, msa_program, exp_id, gf_id, trapid_db_data, verbose=False):
    """Read faln MSA file `msa_file` for GF `gf_id` of experiment `exp_id` and upload its content to TRAPID database. """
    query_str = "UPDATE `gene_families` SET `msa`='{msa_data}', `msa_params`='{msa_params}'  WHERE `experiment_id`='{exp_id}' AND `gf_id`='{gf_id}';"
    if verbose:
        sys.stderr.write("[Message] Store MSA data ('%s') for GF '%s' of experiment '%d' to TRAPID database.\n" % (msa_file, gf_id, exp_id))
    msa_data = []
    with open(msa_file, 'r') as in_file:
        for line in in_file:
            if line.startswith(">"):
                msa_data.append(line.strip()+";")
            else:
                msa_data[-1] += line.strip()
    msa_data_str = ''.join(msa_data)
    if msa_data_str:
        db_conn = common.db_connect(*trapid_db_data)
        cursor = db_conn.cursor()
        cursor.execute(query_str.format(msa_data=msa_data_str, msa_params=msa_program, exp_id=exp_id, gf_id=gf_id))
        db_conn.commit()
        db_conn.close()


def store_msa_stripped_db(msa_file, exp_id, gf_id, msa_editing_str, trapid_db_data, verbose=False):
    """Read faln stripped MSA file `msa_file` for GF `gf_id` of experiment `exp_id` and upload its content to TRAPID database. """
    query_str = "UPDATE `gene_families` SET `msa_stripped`='{msa_data}', `msa_stripped_params`='{msa_editing_str}' WHERE `experiment_id`='{exp_id}' AND `gf_id`='{gf_id}';"
    if verbose:
        sys.stderr.write("[Message] Store stripped MSA data ('%s') for GF '%s' of experiment '%d' to TRAPID database.\n" % (msa_file, gf_id, exp_id))
    msa_data = []
    with open(msa_file, 'r') as in_file:
        for line in in_file:
            if line.startswith(">"):
                msa_data.append(line.strip()+";")
            else:
                msa_data[-1] += line.strip()
    msa_data_str = ''.join(msa_data)
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(msa_data=msa_data_str, msa_editing_str=msa_editing_str, exp_id=exp_id, gf_id=gf_id))
    db_conn.commit()
    db_conn.close()


def store_tree_db(tree_file, tree_program, exp_id, gf_id, trapid_db_data, verbose=False):
    """Read newick tree file `tree_file` for GF `gf_id` of experiment `exp_id` and upload its content to TRAPID database. """
    query_str = "UPDATE `gene_families` SET `tree`='{tree_data}', `tree_params`='{tree_params}'  WHERE `experiment_id`='{exp_id}' AND `gf_id`='{gf_id}';"
    if verbose:
        sys.stderr.write("[Message] Store newick tree data ('%s') for GF '%s' of experiment '%d' to TRAPID database.\n" % (tree_file, gf_id, exp_id))
    with open(tree_file, 'r') as in_file:
        tree_data = in_file.read().replace('\n', '')
        db_conn = common.db_connect(*trapid_db_data)
        cursor = db_conn.cursor()
        cursor.execute(query_str.format(tree_data=tree_data, tree_params=tree_program, exp_id=exp_id, gf_id=gf_id))
        db_conn.commit()
        db_conn.close()


def delete_db_job(exp_id, gf_id, trapid_db_data, msa_only, verbose=False):
    """Delete MSA/tree creation job (GF `gf_id` from experiment `exp_id`) from TRAPID db (accessed using `trapid_db_data`). """
    if verbose:
        sys.stderr.write("[Message] Delete MSA/tree creation job from TRAPID database...\n")
    query_str = "DELETE FROM `experiment_jobs` WHERE `experiment_id`='{exp_id}' AND `comment`='{comment_str}'"
    job_name = "create_tree"
    if msa_only:
        job_name = "create_msa"
    comment_str = "{} {}".format(job_name, gf_id)
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id, comment_str=comment_str))
    db_conn.commit()
    db_conn.close()


def send_end_email(exp_id, gf_id, trapid_db_data, msa_only, verbose=False):
    """Send an email to the owner of experiment `exp_id` to warn them of job completion.  """
    # Get title & email address associated to the experiment
    query_str = "SELECT a.`title`, b.`email` FROM `experiments` a,`authentication` b WHERE a.`experiment_id`='{exp_id}' AND b.`user_id`=a.`user_id`;"
    page_url = '/'.join([common.TRAPID_BASE_URL, 'tools', 'create_tree', str(exp_id), gf_id])
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id))
    exp_data = cursor.fetchone()
    db_conn.close()
    # Send email!
    if not exp_data:
        sys.stderr.write("[Error] Impossible to retrieve experiment title/email address (experiment '%d')!\n" % (exp_id))
        sys.exit(1)
    email_subject = "TRAPID phylogenetic tree finished (%s)\n" % gf_id
    if msa_only:
        email_subject = "TRAPID MSA finished (%s)\n" % gf_id
    email_content = "Dear user,\n\nThe phylogenetic tree for gene family '{gf_id}' in experiment '{exp_title}' has been created. \n\nYou can now view it at this URL: {page_url}\n\nThank for using TRAPID. \n".format(gf_id=gf_id, page_url=page_url, exp_title=exp_data[0])
    common.send_mail(to=[exp_data[1]], subject=email_subject, text=email_content)


def main(exp_id, gf_id, db_name, db_host, db_user, db_pswd, tmp_dir, base_dir, ref_db_host, ref_db_pswd, ref_db_user, msa_program, tree_program, msa_editing, msa_only, keep_tmp, verbose):
    # List containing all needed parameters for `common.db_connect()` (TRAPID DB)
    trapid_db_data = [db_user, db_pswd, db_host, db_name]
    # Get ref. DB name + GF type for the exeperiment
    exp_data = get_exp_data(exp_id=exp_id, trapid_db_data=trapid_db_data, verbose=verbose)
    ref_db_name = exp_data['used_plaza_database']
    gf_type = exp_data['genefamily_type']
    # List containing all needed parameters for `common.db_connect()` (reference DB)
    reference_db_data = [ref_db_user, ref_db_pswd, ref_db_host, ref_db_name]
    reference_sqce_type = get_ref_db_sqce_type(trapid_db_data=trapid_db_data, ref_db_name=ref_db_name, verbose=verbose)
    # Retrieve GF data
    gf_data = get_gf_data(exp_id=exp_id, gf_id=gf_id, trapid_db_data=trapid_db_data, verbose=verbose)
    # Get GF sequences
    transl_tables = load_transl_tables(transl_tables_path=os.path.join(base_dir, 'cfg', 'all_translation_tables.json'), verbose=verbose)
    gf_sqces = get_gf_sqces(gf_data=gf_data, ref_db_data=reference_db_data, gf_type=gf_type, ref_sqce_type=reference_sqce_type, transl_tables=transl_tables, verbose=verbose)
    # Get transcript sequences
    trs_sqces = get_transcript_sqces(gf_data=gf_data, trapid_db_data=trapid_db_data, transl_tables=transl_tables, verbose=verbose)
    # Create input multifasta file
    multifasta_file = create_multifasta_file(gf_sqces=gf_sqces, trs_sqces=trs_sqces, tmp_dir=tmp_dir, exp_id=exp_id, gf_id=gf_id)
    # Call PLAZA phylogenetics pipeline
    phylo_pipeline_output = run_phylogenetics_pipeline(exp_id=exp_id, gf_id=gf_id, tmp_dir=tmp_dir, base_dir=base_dir, multifasta_file=multifasta_file, msa_program=msa_program, msa_editing=msa_editing, tree_program=tree_program, msa_only=msa_only, verbose=verbose)
    # Store results to TRAPID database
    try:
        store_msa_db(msa_file=phylo_pipeline_output['msa_out'], msa_program=msa_program, exp_id=exp_id, gf_id=gf_id, trapid_db_data=trapid_db_data, verbose=verbose)
    except:
        sys.stderr.write("[Error] Problem storing MSA data from '%s': %s\n" % (phylo_pipeline_output['msa_out'], sys.exc_info()[0]))
    if not msa_only:
        try:
            store_msa_stripped_db(msa_file=phylo_pipeline_output['msa_stripped_out'], exp_id=exp_id, gf_id=gf_id, msa_editing_str=msa_editing, trapid_db_data=trapid_db_data, verbose=verbose)
        except:
            sys.stderr.write("[Error] Problem storing stripped MSA data from '%s': %s\n" % (phylo_pipeline_output['msa_stripped_out'], sys.exc_info()[0]))
        try:
            store_tree_db(tree_file=phylo_pipeline_output['tree_out'], tree_program=tree_program, exp_id=exp_id, gf_id=gf_id, trapid_db_data=trapid_db_data, verbose=verbose)
        except:
            sys.stderr.write("[Error] Problem storing tree data from '%s': %s\n" % (phylo_pipeline_output['tree_out'], sys.exc_info()[0]))
    # Clean-up: remove temporary files, send email and delete job from DB
    if not keep_tmp:
        sys.stderr.write("[Message] Remove tmp files...\n")
        for tmp_file in glob.glob(phylo_pipeline_output['base_path'] + "*"):
            os.remove(tmp_file)
        os.remove(multifasta_file)
    delete_db_job(exp_id=exp_id, gf_id=gf_id, trapid_db_data=trapid_db_data, msa_only=msa_only, verbose=verbose)
    send_end_email(exp_id=exp_id, gf_id=gf_id, trapid_db_data=trapid_db_data, msa_only=msa_only, verbose=verbose)


if __name__ == '__main__':
    cmd_args = parse_arguments()
    # Update reference DB data if needed (i.e. replace 'None' values)
    if not cmd_args['ref_db_host']:
        cmd_args['ref_db_host'] = cmd_args['db_host']
    if not cmd_args['ref_db_user']:
        cmd_args['ref_db_user'] = cmd_args['db_user']
    if not cmd_args['ref_db_pswd']:
        cmd_args['ref_db_pswd'] = cmd_args['db_pswd']
    main(**cmd_args)
