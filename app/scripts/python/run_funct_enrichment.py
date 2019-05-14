"""
Run a functional enrichment job: fetch functional annotation data, perform enrichment using @dreec script, load results
into TRAPID database.
"""

# Usage: python run_funct_enrichment.py <funct_enrichment.ini> <fa_type> <subset> <max_pval>

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
    cmd_parser = argparse.ArgumentParser(description='Perform a functional enrichment analysis for a given subset, functional annotation type, and p-value threshold',
        formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    cmd_parser.add_argument('ini_file_enrichment', type=str,
                            help='Functional enrichment configuration file (generated upon enrichment job submission)')
    cmd_parser.add_argument('fa_type', type=str, choices=['go', 'ipr'],
                            help='Type of functional annotation for which enrichment analysis will be performed. ')
    cmd_parser.add_argument('subset', type=str,
                            help='Transcript subset for which enrichment analysis will be performed. ')
    cmd_parser.add_argument('max_pval', type=float,
                            help='Maximum p-value threshold to consider a functional enrichment to be significant. ')
    # Optional arguments
    cmd_parser.add_argument('-k', '--keep_tmp', dest='keep_tmp', action='store_true', default=False, help='Keep temporary files')
    # Verbosity (for debugging purposes)
    cmd_parser.add_argument('-v', '--verbose', dest='verbose', action='store_true', default=False, help='Print extra debug/progress information (verbose mode). ')
    cmd_args = cmd_parser.parse_args()
    return vars(cmd_args)


def main(ini_file_enrichment, fa_type, subset, max_pval, keep_tmp, verbose=False):
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
    # Delete previous enrichment results from TRAPID DB
    db_conn = common.db_connect(*trapid_db_data)
    delete_previous_results(db_conn, exp_id, fa_type, subset, max_pval, verbose)
    db_conn.close()
    # Run enricher
    enricher_results = run_enricher(trapid_db_data, exp_id, fa_type, subset, max_pval, enricher_bin, tmp_dir, keep_tmp, verbose)
    # Create result records and upload them to TRAPID DB
    enrichment_rows = create_enrichment_rows(enricher_results, exp_id, subset, fa_type, max_pval, go_data)
    db_conn = common.db_connect(*trapid_db_data)
    upload_results_to_db(db_conn, enrichment_rows, verbose)
    db_conn.close()


if __name__ == '__main__':
    # Parse command-line arguments
    cmd_args = parse_arguments()
    main(**cmd_args)
