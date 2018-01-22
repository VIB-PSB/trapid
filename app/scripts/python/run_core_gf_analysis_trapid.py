#!/usr/bin/python

"""
A wrapper script to perform the entirety of a core GF completeness analysis for a given TRAPID experiment.
This script is the one used within TRAPID 2.
"""

# TODO: once code is clean, move these functions somewhere else (and only keep a wrapper here).
# TODO: have precomputed core GF files and check if it corresponds to the parameters currently used.
# Note: From the `all_gfs` dictionary we can get pretty much everything we want and export it to the TRAPID database.
# Note: is it really needed to export GF weight to TRAPID datatabase? It can be found easily (n_species/n_genes)

import argparse
import core_gf_analysis
import get_core_gfs
import math
import MySQLdb as MS
import os
import pandas as pd
import sys
import time
from get_core_gfs import ResultIter, connect_to_db


TIMESTAMP = time.strftime('%Y_%m_%d_%H%M%S')  # Get timestamp (for default output directory name)
# Forced to define $DB_PWD here since it seems impossible to use environment variable on the web cluster
os.environ["DB_PWD"] = "@Z%28ZwABf5pZ3jMUz"

# Command-line arguments
cmd_parser = argparse.ArgumentParser(
    description='A wrapper script to perform core GF completeness analysis using data of a TRAPID experiment. ',
    formatter_class=argparse.ArgumentDefaultsHelpFormatter)
# Positional arguments
cmd_parser.add_argument('clade',
                        help='Retrieve core GFs for which clade?')
cmd_parser.add_argument('experiment_id',
                        help='TRAPID experiment identifier. ')
# Optional arguments (core GF retrieval)
data_retrieval_args = cmd_parser.add_argument_group('optional arguments (data retrieval: core GFs and TRAPID data)')
data_retrieval_args.add_argument('-l', '--label', type=str, dest='transcript_label',
                        help='Retrieve similarity search data only for transcripts of this label. If set to `None` (default), retrieve all transcripts of the TRAPID experiment. ',
                        default=None)
data_retrieval_args.add_argument('-sp', '--species_perc', dest='species_perc', type=float,
                        help='Cutoff value: species representation percentage. Only gene families present in at least this proportion of species of the chosen clade will be retrieved. Must be comprised between 0 and 1. ',
                        default=0.9)
data_retrieval_args.add_argument('-db', '--trapid_db', type=str, dest='trapid_db_name',
                        help='Name of TRAPID database. ', default='db_trapid_02')
data_retrieval_args.add_argument('-u', '--username', type=str, dest='username',
                        help='Username to connect to the TRAPID database. The script will prompt you for the password, unless it was provided as environment variable ($DB_PWD). ',
                        default='trapid_website')
data_retrieval_args.add_argument('-s', '--mysql_server', type=str, dest='mysql_server', help='Host name (server). ',
                        default='psbsql01.psb.ugent.be')
# Optional arguments (core GF completeness)
completeness_args = cmd_parser.add_argument_group('optional arguments (completeness analysis)')
completeness_args.add_argument('-o', '--output_dir', dest='output_dir', type=str,
                        help='Path of an output directory for the core GF analysis results. Will be created if it does not exist. ', default='core_gf_analysis_%s'%TIMESTAMP)
completeness_args.add_argument('-t', '--top_hits', dest='top_hits', type=int,
                        help='Top protein similarity search hits to consider when looking for a GF. ', default=5)

# Unused arguments (for now)
# gf_retrieval_args.add_argument('-m', '--min_genes', dest='min_genes', type=int,
#                         help='Cutoff value: only retrieve gene families having minimum this number of genes. By default (0), no filter is applied. ',
#                         default=0)
# gf_retrieval_args.add_argument('-M', '--max_genes', dest='max_genes', type=int,
#                         help='Cutoff value: only retrieve gene families having maximum this number of genes. By default (0), no filter is applied. ',
#                         default=0)
# gf_retrieval_args.add_argument('-ts', '--tax_source', dest='tax_source', choices=['ncbi', 'json'],
#                         help='''The source from which to get tax_ids/organisms belonging to your clade of interest.
#                         `ncbi`: from the NCBI taxonomy (with ete-toolkit), a tax_id or a clade name can be used as `clade`.
#                         `json`: from the species trees (of various PLAZA versions), based on the JSON files in `data`.
#                         If this option is selected only a clade name (present in the JSON files) can be used as `clade`. ''',
#                         default='ncbi')


# We need to define a few extra functions to get the current code to work with the TRAPID database.
def read_trapid_data(db_conn, experiment_id, top_hits, transcript_label=None):
    """Get similarity search results of a TRAPID experiment (and optionally only for transcripts having a label), from
    the TRAPID database, and return data as sorted and indexed pandas dataframe (keep only query, subject and e-value).
    `top_hits` is used here to avoid retrieveing more results than necessary. """
    sys.stderr.write('[' + time.strftime("%H:%M:%S") + '] Retrieving similarity search data from TRAPID database.\n')
    sim_list= []
    if transcript_label not in [None, "None"]:  # Quickfix (ambiguity between None, and 'None' str).
        get_sim_data_query = "SELECT sim.transcript_id, sim.similarity_data " \
                             "FROM similarities sim INNER JOIN transcripts_labels tl " \
                             "ON sim.transcript_id = tl.transcript_id " \
                             "WHERE sim.experiment_id = {experiment_id} " \
                             "AND tl.label = '{transcript_label}'".format(experiment_id=str(experiment_id),
                                                                          transcript_label=transcript_label)
    else:
        get_sim_data_query = "SELECT sim.transcript_id, sim.similarity_data " \
                             "FROM similarities sim " \
                             "WHERE sim.experiment_id = {experiment_id}".format(experiment_id=str(experiment_id))
    # Execute query
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(get_sim_data_query)
    # Process output to get only information we want.
    for record in ResultIter(db_cursor=cursor):
        # Get a list of similarity search results for current query, formatted like:
        # [[query, subject_1, e-value_1], [query, subject_2, e-value_2], ...]
        sim_data = [[record['transcript_id'], data.split(',')[0], float(data.split(',')[1])] for data in record['similarity_data'].split(';')[0:top_hits]]
        sim_list.extend(sim_data)
    trapid_df = pd.DataFrame(sim_list, columns=["query_gene",  "subject", "log_e_value"])
    # Convert e-values to log10 (they are stored as raw e-values in TRAPID's `similarities` table)
    trapid_df['log_e_value'] = [math.log10(e_val) if e_val > sys.float_info.min else math.log10(sys.float_info.min)
                                   for e_val in trapid_df['log_e_value'].tolist()]
    # Sort, index and return
    trapid_df = trapid_df.sort_values(by=['query_gene', 'log_e_value'], ascending=[True, True])
    trapid_df = trapid_df.set_index(['query_gene'])
    return trapid_df


def get_ref_db_name(db_conn, experiment_id):
    """Get the name of the used reference database of a TRAPID experiment. Return it as string. """
    get_ref_db_query = "SELECT used_plaza_database FROM experiments WHERE experiment_id={experiment_id}".format(experiment_id=str(experiment_id))
    # Execute query
    cursor = db_conn.cursor()
    cursor.execute(get_ref_db_query)
    ref_db_name = cursor.fetchall()[0][0]
    return ref_db_name


def get_missing_gfs_str(all_gfs_dict):
    """From a GF dictionary, return `missing_gfs_str`, a properly-formatted string with information on each missing GF.
    Format is `gf_id:n_genes:n_species:gf_weight` for each gf (separated by `;`). """
    missing_gfs = [gf for gf in all_gfs_dict if not all_gfs_dict[gf]['represented'] and all_gfs_dict[gf]['is_core_gf']]
    missing_gfs_str = ';'.join([':'.join([gf, str(len(all_gfs_dict[gf]['members'])), all_gfs_dict[gf]['n_species'], all_gfs_dict[gf]['weight']]) for gf in missing_gfs])
    return missing_gfs_str


def get_represented_gfs_str(all_gfs_dict):
    """From a GF dictionary, return `missing_gfs_str`, a properly-formatted string with information on each missing GF.
    Format is `gf_id:n_genes:n_species:gf_weight:query_1,query_2,...,query_n` for each gf (separated by `;`). """
    represented_gfs = [gf for gf in all_gfs_dict if all_gfs_dict[gf]['represented'] and all_gfs_dict[gf]['is_core_gf']]
    represented_gfs_str = ';'.join([':'.join([gf, str(len(all_gfs_dict[gf]['members'])), all_gfs_dict[gf]['n_species'], all_gfs_dict[gf]['weight'], ','.join(all_gfs_dict[gf]['query_list'])]) for gf in represented_gfs])
    return represented_gfs_str


def export_results_to_db(db_conn, output_dict):
    """Export core GF completeness analysis results to the TRAPID database. """
    sys.stderr.write('[' + time.strftime("%H:%M:%S") + '] Export core GF completeness results to TRAPID database.\n')
    cursor = db_conn.cursor()
    # Kind of dumb way to create the request, but it works.
    columns = ', '.join(sorted(output_dict))
    values = ', '.join(["\'{insert_value}\'".format(insert_value=output_dict[k]) for k in sorted(output_dict)])
    export_query = "INSERT INTO completeness_results ({columns}) VALUES ({values})".format(columns=columns, values=values)
    cursor.execute(export_query)
    db_conn.commit()


def main(output_dir, trapid_db_name, trapid_db_user, trapid_db_host, experiment_id, transcript_label, top_hits, species_perc, clade):
    """Main function (execute TRAPID's core gf completeness analysis). """
    # 1. Create output directory if does not already exist
    if not os.path.exists(output_dir):
        sys.stderr.write('[' + time.strftime("%H:%M:%S") + '] Creating output directory \'%s\'. \n' % output_dir)
        os.makedirs(output_dir)
    else:
        sys.stderr.write('[' + time.strftime("%H:%M:%S") + '] Output directory \'%s\' already exists. \n' % output_dir)

    # 2. Connect to TRAPID database, retrieve similarity search data and used reference database.
    db_trapid = connect_to_db(db_user=trapid_db_user, db_name=trapid_db_name, db_host=trapid_db_host)
    trapid_df = read_trapid_data(db_conn=db_trapid, experiment_id=experiment_id, top_hits=top_hits, transcript_label=transcript_label)
    print trapid_df
    ref_db_name = get_ref_db_name(db_conn=db_trapid, experiment_id=experiment_id)

    # 3. Retrieve core GFs from reference database and create core GFs file.
    core_gfs_file = os.path.join(output_dir, "core_gfs_{clade}_sp{species_perc}_{tax_source}.tsv".format(clade=str(clade), species_perc=species_perc, tax_source="ncbi"))
    get_core_gfs.main(db_name=ref_db_name, clade=clade, username=trapid_db_user, mysql_server=trapid_db_host,
                      output_file=core_gfs_file, min_genes=0, max_genes=0,
                      species_perc=species_perc, tax_source="ncbi")

    # 4. Perform core GF completeness analysis and format results
    all_gfs = core_gf_analysis.read_all_gfs(core_gfs_file=core_gfs_file)
    gene_gf_map = core_gf_analysis.get_gene_gf_map(all_gfs_dict=all_gfs)
    core_gf_analysis.process_blast_output(blast_df=trapid_df, n_hits=top_hits, gene_gf_map=gene_gf_map,
                         all_gfs_dict=all_gfs)
    used_method = "sp={species_perc};ts={tax_source};th={top_hits}".format(species_perc=species_perc, tax_source="ncbi", top_hits=top_hits)
    completeness_score = core_gf_analysis.get_completeness_score(all_gfs_dict=all_gfs)
    missing_gfs = get_missing_gfs_str(all_gfs_dict=all_gfs)
    represented_gfs = get_represented_gfs_str(all_gfs_dict=all_gfs)

    # 5. Put everything in a dict and export to TRAPID database
    output_dict = {
        'clade_txid': str(clade),
        'completeness_score': str(completeness_score),
        'experiment_id': str(experiment_id),
        'label': str(transcript_label),
        'missing_gfs': missing_gfs,
        'represented_gfs': represented_gfs,
        'used_method': used_method
    }
    export_results_to_db(db_conn=db_trapid, output_dict=output_dict)


# Script execution when called from the command-line
if __name__ == "__main__":
    cmd_args = cmd_parser.parse_args()
    main(output_dir=cmd_args.output_dir, trapid_db_name=cmd_args.trapid_db_name, trapid_db_user=cmd_args.username,
         trapid_db_host=cmd_args.mysql_server, experiment_id=cmd_args.experiment_id,
         transcript_label=cmd_args.transcript_label, top_hits=cmd_args.top_hits, species_perc=cmd_args.species_perc,
         clade=cmd_args.clade)
