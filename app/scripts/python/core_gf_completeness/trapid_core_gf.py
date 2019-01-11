"""
A collection of functions used to perform core GF completeness analysis from TRAPID 2.
"""

import math
import MySQLdb as MS
import pandas as pd
import sys
from common import connect_to_db, print_log_msg, ResultIter


def read_trapid_data(db_conn, experiment_id, top_hits, transcript_label=None):
    """Retrieve similarity search results of TRAPID experiment `experiment_id`, (optionally for transcripts labelled
    with `transcript_label`label), from the TRAPID database (through `db_conn`). Return data as a sorted and indexed
    pandas dataframe (keep only query, subject and e-value). `top_hits` is used to avoid retrieving more results than
    necessary. """
    print_log_msg(log_str='Retrieving similarity search data from TRAPID database.')
    sim_list= []
    if transcript_label not in [None, "None"]:  # Quickfix (ambiguity between None, and 'None' str).
        get_sim_data_query = "SELECT sim.transcript_id, UNCOMPRESS(sim.similarity_data) as `similarity_data`" \
                             "FROM similarities sim INNER JOIN transcripts_labels tl " \
                             "ON sim.transcript_id = tl.transcript_id " \
                             "WHERE sim.experiment_id = {experiment_id} " \
                             "AND tl.label = '{transcript_label}'".format(experiment_id=str(experiment_id),
                                                                          transcript_label=transcript_label)
    else:
        get_sim_data_query = "SELECT sim.transcript_id, UNCOMPRESS(sim.similarity_data) as `similarity_data` " \
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
    print_log_msg(log_str='Export core GF completeness results to TRAPID database.')
    cursor = db_conn.cursor()
    # Kind of dumb way to create the request, but it works.
    columns = ', '.join(sorted(output_dict))
    values = ', '.join(["\'{insert_value}\'".format(insert_value=output_dict[k]) for k in sorted(output_dict)])
    export_query = "INSERT INTO completeness_results ({columns}) VALUES ({values})".format(columns=columns, values=values)
    cursor.execute(export_query)
    db_conn.commit()
