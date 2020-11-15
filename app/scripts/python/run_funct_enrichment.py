"""
Run a functional enrichment job: fetch functional annotation data, perform enrichment using @dreec script, load results
into TRAPID database.
"""

# Usage: python run_fe.py <fe.ini> <fa_type> <subset> <max_pval>

import argparse

import common
import funct_enrichment as fe


def parse_arguments():
    """Parse command-line arguments.

    :return: parsed arguments (Namespace object)

    """
    cmd_parser = argparse.ArgumentParser(description='Run functional enrichment analysis for a given subset, functional annotation type, and p-value threshold',
                                         formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    cmd_parser.add_argument('ini_file_enrichment', type=str,
                            help='Functional enrichment configuration file (generated upon enrichment job submission)')
    cmd_parser.add_argument('fa_type', type=str, choices=['go', 'ko', 'ipr'],
                            help='Type of functional annotation for which enrichment analysis will be performed. ')
    cmd_parser.add_argument('subset', type=str,
                            help='Transcript subset for which enrichment analysis will be performed. ')
    cmd_parser.add_argument('max_pval', type=float,
                            help='Maximum p-value threshold to consider a functional enrichment to be significant. ')
    # Optional arguments
    cmd_parser.add_argument('-k', '--keep_tmp', action='store_true', default=False, help='Keep temporary files')
    # Verbosity (for debugging purposes)
    cmd_parser.add_argument('-v', '--verbose', action='store_true', default=False,
                            help='Print extra debug/progress information (verbose mode). ')
    cmd_args = cmd_parser.parse_args()
    return cmd_args


def main():
    # Parse command-line arguments
    cmd_args = parse_arguments()
    # Read enrichment configuration file
    config = common.load_config(cmd_args.ini_file_enrichment, {'trapid_db', 'reference_db', 'experiment', 'enrichment'})
    # TRAPID db data (list containing all needed parameters for `common.db_connect()`)
    trapid_db_data = common.get_db_connection_data(config, 'trapid_db')
    # Ref. db data (list containing all needed parameters for `common.db_connect()`)
    ref_db_data = common.get_db_connection_data(config, 'reference_db')
    exp_id = config['experiment']['exp_id']
    tmp_dir = config['experiment']['tmp_exp_dir']
    enricher_bin = config['enrichment']['enricher_bin']
    # Check existence of enrichmer bin
    fe.check_enricher_bin(enricher_bin, cmd_args.verbose)
    # Get GO data from reference database if `fa_type` is GO
    go_data = {}
    if cmd_args.fa_type == 'go':
        db_conn = common.db_connect(*ref_db_data)
        go_data = fe.get_go_data(db_conn)
        db_conn.close()
    # Delete previous enrichment results from TRAPID DB
    db_conn = common.db_connect(*trapid_db_data)
    fe.delete_previous_results(db_conn, exp_id, cmd_args.fa_type, cmd_args.subset, cmd_args.max_pval, cmd_args.verbose)
    db_conn.close()
    # Run enricher
    enrichment_data = fe.run_enricher(
        trapid_db_data, exp_id, cmd_args.fa_type, cmd_args.subset, cmd_args.max_pval, go_data, enricher_bin, tmp_dir,
        cmd_args.keep_tmp, cmd_args.verbose)
    # Create result records and upload them to TRAPID DB
    enrichment_rows = fe.create_enrichment_rows(
        enrichment_data['results'], enrichment_data['gf_data'], exp_id, cmd_args.subset, cmd_args.fa_type,
        cmd_args.max_pval, go_data)
    db_conn = common.db_connect(*trapid_db_data)
    fe.upload_results_to_db(db_conn, enrichment_rows, cmd_args.verbose)
    db_conn.close()


if __name__ == '__main__':
    main()
