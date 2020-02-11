"""
Run enrichment jobs for the 'enrichment preprocessing' of TRAPID (i.e. run multiple enrichment jobs for one tpye of
functional annotation using multiple subsets/p-values thresholds).
"""

# Usage: python run_funct_enrichment_preprocess.py <funct_enrichment.ini> <fa_type> --subsets [subsets] --max_pvals [max_p_values]

import argparse
import common
import math
import MySQLdb as MS
import os
import subprocess
import sys
import time
from ConfigParser import ConfigParser
from funct_enrichment import *


def parse_arguments():
    """Parse command-line arguments and return them as a dictionary. """
    cmd_parser = argparse.ArgumentParser(description='Perform functional enrichment preprocessing for given experiment and functional annotation type',
        formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    cmd_parser.add_argument('ini_file_enrichment', type=str,
                            help='Functional enrichment configuration file (generated upon enrichment job submission)')
    cmd_parser.add_argument('fa_type', type=str, choices=['go', 'ipr'],
                            help='Type of functional annotation for which enrichment analysis will be performed. ')
    cmd_parser.add_argument('--subsets', type=str, nargs='+',
                            help='Transcript subset(s) for which enrichment analysis will be performed. ')
    cmd_parser.add_argument('--max_pvals', type=float, nargs='+',
                            help='Maximum p-value thresholds for each transcript subset')
    # Optional arguments
    cmd_parser.add_argument('-k', '--keep_tmp', dest='keep_tmp', action='store_true', default=False, help='Keep temporary files')
    # Verbosity (for debugging purposes)
    cmd_parser.add_argument('-v', '--verbose', dest='verbose', action='store_true', default=False, help='Print extra debug/progress information (verbose mode). ')
    cmd_args = cmd_parser.parse_args()
    return vars(cmd_args)


def main(ini_file_enrichment, fa_type, subsets, max_pvals, keep_tmp, verbose=False):
    # Read enrichment configuration file
    config = common.load_config(cmd_args['ini_file_enrichment'], {'trapid_db', 'reference_db', 'experiment', 'enrichment'})
    # TRAPID db data (list containing all needed parameters for `common.db_connect()`)
    trapid_db_data = [config['trapid_db']['trapid_db_username'], config['trapid_db']['trapid_db_password'],
                      config['trapid_db']['trapid_db_server'], config['trapid_db']['trapid_db_name']]
    # Ref. db data (list containing all needed parameters for `common.db_connect()`)
    ref_db_data = [config['reference_db']['reference_db_username'], config['reference_db']['reference_db_password'],
                   config['reference_db']['reference_db_server'], config['reference_db']['reference_db_name']]
    exp_id = config['experiment']['exp_id']
    tmp_dir = config['experiment']['tmp_exp_dir']
    enricher_bin = config['enrichment']['enricher_bin']
    # Check existence of enrichmer bin
    check_enricher_bin(enricher_bin, verbose)
    # Get GO data from reference database if `fa_type` is GO
    go_data = {}
    if fa_type == 'go':
        db_conn = common.db_connect(*ref_db_data)
        go_data = get_go_data(db_conn)
        db_conn.close()
    # For each subset / p-value threshold, delete previous enrichment results from TRAPID DB
    for subset in subsets:
        for pval in max_pvals:
            db_conn = common.db_connect(*trapid_db_data)
            delete_previous_results(db_conn, exp_id, fa_type, subset, pval, verbose)
            db_conn.close()
    # Run enrichment, but not using the `run_enricher()` wrapper function, since feature file (background) needs to be
    # created only once.
    # Create feature file
    db_conn = common.db_connect(*trapid_db_data)
    feature_file = create_enricher_input_feature(db_conn, exp_id, fa_type, tmp_dir, verbose)
    db_conn.close()
    if fa_type == 'go':
        clean_enricher_input_feature_go(feature_file, go_data, verbose)
    # Create all set files (one per subset)
    set_files = {}
    for subset in subsets:
        db_conn = common.db_connect(*trapid_db_data)
        set_file = create_enricher_input_set(db_conn, exp_id, subset, fa_type, tmp_dir, verbose)
        set_files[subset] = set_file
        db_conn.close()
    # Run/post-process enricher for every possible case
    out_files = []
    for subset in subsets:
        for pval in max_pvals:
                enricher_output = call_enricher(feature_file, set_files[subset], pval, exp_id, subset, fa_type, enricher_bin, tmp_dir)
                out_files.append(enricher_output)
                enricher_results = read_enricher_output(enricher_output, verbose)
                # Create result records and upload them to TRAPID DB
                enrichment_rows = create_enrichment_rows(enricher_results, exp_id, subset, fa_type, pval, go_data)
                db_conn = common.db_connect(*trapid_db_data)
                upload_results_to_db(db_conn, enrichment_rows, verbose)
                db_conn.close()

    # Delete temporary files (depending on `--keep_tmp` flag)
    if not keep_tmp:
        del_files(set_files.values())
        del_files(out_files)
        del_files([feature_file])


if __name__ == '__main__':
    # Parse command-line arguments
    cmd_args = parse_arguments()
    main(**cmd_args)
