"""
Delete an individual TRAPID experiment (DB and local files).
"""

# Usage example: python delete_experiment.py <exp_id> <exp_tmp_dir> <trapid_db> <db_host> <db_user> <db_pswd>

import argparse
import os
import sys

from shutil import rmtree
from traceback import print_exc

import MySQLdb as MS

import common


def parse_arguments():
    """Parse command-line arguments.

    :return: parsed arguments (Namespace object)

    """
    cmd_parser = argparse.ArgumentParser(description='Perform TRAPID experiment deletion. Experiment must be in `deleting` state.',
                                         formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    cmd_parser.add_argument('exp_id', type=int, help='TRAPID experiment ID.')
    cmd_parser.add_argument('tmp_dir', type=str,
                            help='Temporary experiment directory.')
    cmd_parser.add_argument('db_name', type=str, help='TRAPID DB name.')
    cmd_parser.add_argument('db_host', type=str, help='TRAPID DB host.')
    cmd_parser.add_argument('db_user', type=str, help='TRAPID DB username.')
    cmd_parser.add_argument('db_pswd', type=str, help='TRAPID DB password.')
    cmd_parser.add_argument('-v', '--verbose', action='store_true', default=False,
                            help='Print debug/progress information (verbose mode).')
    cmd_args = cmd_parser.parse_args()
    return cmd_args


def get_exp_info(exp_id, trapid_db_data):
    """Get experiment information as dict."""
    sys.stderr.write(
        '[Message] Retrieve experiment information for {}\n'.format(exp_id))
    query = "SELECT exp.user_id, exp.used_plaza_database, tr.`transcript_count` AS transcript_count, exp.title, exp.creation_date, exp.last_edit_date, exp.process_state FROM `experiments` exp, (SELECT COUNT(transcript_id) AS transcript_count FROM transcripts WHERE experiment_id = '{exp_id}') tr WHERE exp.`experiment_id` = '{exp_id}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(query.format(exp_id=exp_id))
    exp_data = cursor.fetchone()
    db_conn.close()
    return exp_data


def delete_exp_db(exp_id, trapid_db_data, verbose=False):
    """Delete experiment's data from TRAPID database."""
    delete_tables = [
        'transcripts_annotation',
        'transcripts_tax',
        'functional_enrichments',
        'gene_families',
        'rna_families',
        'transcripts_labels',
        'transcripts',
        'similarities',
        'rna_similarities',
        'completeness_results',
        'data_uploads',
        'experiment_jobs',
        'experiment_log',
        'experiment_stats',
        'experiments',
        'cleanup_experiments'
    ]
    sys.stderr.write('[Message] Delete experiment data from TRAPID db...\n')
    db_conn = common.db_connect(*trapid_db_data)
    for table in delete_tables:
        query = "DELETE FROM `{table}` WHERE `experiment_id`='{exp_id}';"
        if verbose:
            sys.stderr.write(query.format(table=table, exp_id=exp_id) + "\n")
        cursor = db_conn.cursor()
        cursor.execute(query.format(table=table, exp_id=exp_id))
        db_conn.commit()
    db_conn.close()


def delete_exp_tmp_data(tmp_dir):
    """Cleanup experiment's data from temporary storage."""
    sys.stderr.write(
        '[Message] Delete experiment tmp data ({}) \n'.format(tmp_dir))
    # Only a warning because 'empty' experiments don't have a tmp directory.
    if not os.path.exists(tmp_dir):
        sys.stderr.write("[Warning] Directory '%s' was not found.\n" % tmp_dir)
    else:
        rmtree(tmp_dir)


def store_deleted_exp(exp_id, exp_info, trapid_db_data, verbose=False):
    """Store experiment in `deleted_experiments`."""
    sys.stderr.write('[Message] Add epxeriment to `deleted_experiments`...\n')
    query = "INSERT INTO `deleted_experiments` (`user_id`, `experiment_id`, `used_plaza_database`, `num_transcripts`, `title`, `creation_date`, `last_edit_date`, `deletion_date`) VALUES ('{}', '{}' , '{}' , {}, '{}', '{}','{}', NOW());"
    formatted_query = query.format(
        exp_info['user_id'],
        exp_id,
        exp_info['used_plaza_database'],
        exp_info['transcript_count'],
        exp_info['title'],
        exp_info['creation_date'],
        exp_info['last_edit_date']
    )
    if verbose:
        sys.stderr.write(formatted_query + "\n")

    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(formatted_query)
    db_conn.commit()  # Not needed?
    db_conn.close()


def main():
    cmd_args = parse_arguments()
    trapid_db_data = [cmd_args.db_user, cmd_args.db_pswd,
                      cmd_args.db_host, cmd_args.db_name]
    try:
        exp_info = get_exp_info(cmd_args.exp_id, trapid_db_data)
        if exp_info.get('process_state') != 'deleting':
            sys.exit("[Error] Experiment is not in 'deleting' state, exit.")
        delete_exp_db(cmd_args.exp_id, trapid_db_data, cmd_args.verbose)
        delete_exp_tmp_data(cmd_args.tmp_dir)
        store_deleted_exp(cmd_args.exp_id, exp_info,
                          trapid_db_data, cmd_args.verbose)
    except Exception:
        print_exc()


if __name__ == '__main__':
    main()
