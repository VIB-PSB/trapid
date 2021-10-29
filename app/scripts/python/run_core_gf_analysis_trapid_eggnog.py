#!/usr/bin/python

"""
A wrapper script to perform the entirety of a core GF completeness analysis for a given TRAPID experiment, when working
with EggNOG as reference database. This script is the one used within TRAPID 2.
"""

# TODO: have precomputed core GF files and check if it corresponds to the parameters currently used.
# Note: From the `all_gfs` dictionary we can get pretty much everything we want and export it to the TRAPID database.
# Note: is it really needed to export GF weight to TRAPID database? It can be found easily (n_species/n_genes)


import argparse
import os
import time

from core_gf_completeness import core_gf_analysis
from core_gf_completeness import get_core_gfs_eggnog
from core_gf_completeness.common import connect_to_db, print_log_msg
from core_gf_completeness.trapid_core_gf import *


def parse_arguments():
    """Parse command-line arguments.

    :return: parsed arguments (Namespace object)

    """
    timestamp = time.strftime('%Y_%m_%d_%H%M%S')  # Get timestamp (for default output directory name)
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
    data_retrieval_args.add_argument(
        '-l', '--label', type=str,
        help=('Retrieve similarity search data only for transcripts of this label. '
              'If set to `None` (default), retrieve all transcripts of the TRAPID experiment. '),
        default=None)
    data_retrieval_args.add_argument(
        '-sp', '--species_perc', type=float,
        help=('Cutoff value: species representation percentage. Only gene families present in at least this proportion '
              'of species of the chosen clade will be retrieved. Must be comprised between 0 and 1. '),
        default=0.9)
    data_retrieval_args.add_argument('-db', '--trapid_db', type=str,
                                     help='Name of TRAPID database. ', default='db_trapid_dev')
    data_retrieval_args.add_argument(
        '-u', '--username', type=str,
        help=('Username to connect to the TRAPID database. The script will prompt you for the password, unless it was '
              'provided as environment variable ($DB_PWD). '),
        default='trapid_website')
    data_retrieval_args.add_argument('-s', '--mysql_server', type=str, help='Host name (server). ',
                                     default='psbsql01.psb.ugent.be')
    # Optional arguments (core GF completeness)
    completeness_args = cmd_parser.add_argument_group('optional arguments (completeness analysis)')
    cmd_parser.add_argument(
        '--naive', dest='naive_scoring',
        help=('Score GFs without taking their weight into account (scoring exlcusively based on log10 e-values of '
              'top hits). Not providing this flag may give more importance to GFs having a large weight. '),
        action='store_true', default=False)
    completeness_args.add_argument(
        '-o', '--output_dir', type=str,
        help='Path of an output directory for the core GF analysis results. Will be created if it does not exist. ',
        default='core_gf_analysis_%s' % timestamp)
    completeness_args.add_argument(
        '-t', '--top_hits', type=int, help='Top protein similarity search hits to consider when looking for a GF. ',
        default=1)
    # Unused arguments
    # gf_retrieval_args.add_argument('-m', '--min_genes', type=int,
    #                         help='Cutoff value: only retrieve gene families having minimum this number of genes. By default (0), no filter is applied. ',
    #                         default=0)
    # gf_retrieval_args.add_argument('-M', '--max_genes', type=int,
    #                         help='Cutoff value: only retrieve gene families having maximum this number of genes. By default (0), no filter is applied. ',
    #                         default=0)
    # gf_retrieval_args.add_argument('-ts', '--tax_source', choices=['ncbi', 'json'],
    #                         help='''The source from which to get tax_ids/organisms belonging to your clade of interest.
    #                         `ncbi`: from the NCBI taxonomy (with ete-toolkit), a tax_id or a clade name can be used as `clade`.
    #                         `json`: from the species trees (of various PLAZA versions), based on the JSON files in `data`.
    #                         If this option is selected only a clade name (present in the JSON files) can be used as `clade`. ''',
    #                         default='ncbi')
    cmd_args = cmd_parser.parse_args()
    return cmd_args


def main(output_dir, trapid_db_name, trapid_db_user, trapid_db_host, experiment_id, transcript_label, top_hits,
         species_perc, clade, naive_scoring):
    """Main function (execute TRAPID's core gf completeness analysis). """
    # 1. Create output directory if does not already exist
    if not os.path.exists(output_dir):
        print_log_msg(log_str='Creating output directory \'%s\'.' % output_dir)
        os.makedirs(output_dir)
    else:
        print_log_msg(log_str='Output directory \'%s\' already exists.' % output_dir)

    # 2. Connect to TRAPID database, retrieve similarity search data and used reference database.
    db_trapid = connect_to_db(db_user=trapid_db_user, db_name=trapid_db_name, db_host=trapid_db_host)
    trapid_df = read_trapid_data(db_conn=db_trapid, experiment_id=experiment_id, top_hits=top_hits,
                                 transcript_label=transcript_label)
    ref_db_name = get_ref_db_name(db_conn=db_trapid, experiment_id=experiment_id)

    # 3. Retrieve core GFs from reference database and create core GFs file.
    core_gfs_file = os.path.join(output_dir, "core_gfs_{clade}_sp{species_perc}_{tax_source}.tsv".format(
        clade=str(clade), species_perc=species_perc, tax_source="ncbi"))
    get_core_gfs_eggnog.main(db_name=ref_db_name, clade=clade, username=trapid_db_user, mysql_server=trapid_db_host,
                             output_file=core_gfs_file, min_genes=0, max_genes=0, species_perc=species_perc)
                             #, tax_source="ncbi")

    # 4. Perform core GF completeness analysis and format results
    all_gfs = core_gf_analysis.read_all_gfs(core_gfs_file=core_gfs_file, gf_len=False)
    gene_gf_map = core_gf_analysis.get_gene_gf_map(all_gfs_dict=all_gfs)
    core_gf_analysis.process_blast_output(
        blast_df=trapid_df, n_hits=top_hits, gene_gf_map=gene_gf_map, output_dir=output_dir, all_gfs_dict=all_gfs,
        naive_scoring=naive_scoring, gf_len=False, sqce_lengths=dict(), min_len=0.0)
    used_method = "sp={species_perc};ts={tax_source};th={top_hits}".format(
        species_perc=species_perc, tax_source="ncbi", top_hits=top_hits)
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


if __name__ == "__main__":
    cmd_args = parse_arguments()
    main(output_dir=cmd_args.output_dir, trapid_db_name=cmd_args.trapid_db, trapid_db_user=cmd_args.username,
         trapid_db_host=cmd_args.mysql_server, experiment_id=cmd_args.experiment_id,
         transcript_label=cmd_args.label, top_hits=cmd_args.top_hits, species_perc=cmd_args.species_perc,
         clade=cmd_args.clade, naive_scoring=cmd_args.naive_scoring)
