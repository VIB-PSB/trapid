"""
Create default transcript subsets after initial processing.
"""

# Usage: python create_default_subsets.py exp_initial_processing_settings.ini


import common
import sys

SUBSET_NAMES = {"rf_gf": "RNA_ambiguous"}


def delete_subset(exp_id, subset_name, trapid_db_data):
    """
    Delete subset `subset_name` of experiment `exp_id` from the `transcripts_labels` table of TRAPID database
    (accessed using data in `trapid_db_data`).
    """
    sys.stderr.write("[Message] Delete transcript subset '%s' for experiment '%s'.\n" % (subset_name, exp_id))
    query_str = "DELETE FROM `transcripts_labels` WHERE `experiment_id`='{exp_id}' AND `label`='{subset_name}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id, subset_name=subset_name))
    db_conn.commit()
    db_conn.close()


def create_subset(exp_id, subset_name, transcripts, trapid_db_data):
    """
    Create subset `subset_name` for a set of transcripts `transcripts` from experiment `exp_id`. TRAPID database is
    accessed using data in `trapid_db_data`.
    """
    sys.stderr.write("[Message] Create transcript subset '%s' for experiment '%s'.\n" % (subset_name, exp_id))
    query_str = "INSERT INTO `transcripts_labels` (`experiment_id`,`transcript_id`,`label`) VALUES ('{exp_id}', '{transcript_id}', '{subset_name}');"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    for transcript in transcripts:
        formatted_query = query_str.format(exp_id=exp_id, transcript_id=transcript, subset_name=subset_name)
        cursor.execute(formatted_query)
    db_conn.commit()
    db_conn.close()


def get_ambiguous_rna_transcripts(exp_id, trapid_db_data):
    """
    Retrieve 'ambiguous' RNA transcripts, i.e. transcript that are part of both a gene family and a RNA family, for
    experiment `exp_id`, and return them as set of transcript identifiers. TRAPID database is accessed using data in
    `trapid_db_data`.
    """
    sys.stderr.write("[Message] Retrieve ambiguous transcripts for experiment '%s'.\n" % exp_id)
    query_str = "SELECT `transcript_id` FROM `transcripts` WHERE `experiment_id`='{exp_id}' AND `gf_id` IS NOT NULL AND `rf_ids` IS NOT NULL;"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id))
    transcripts = set([record[0] for record in cursor.fetchall()])
    db_conn.commit()
    db_conn.close()
    return transcripts


def create_ambiguous_rna_transcripts_subset(exp_id, trapid_db_data):
    """
    Create subset for ambiguous RNA transcripts of experiment `exp_id`. TRAPID DB accesssed using `trapid_db_data`.
    """
    delete_subset(exp_id, SUBSET_NAMES['rf_gf'], trapid_db_data)
    ambiguous_transcripts = get_ambiguous_rna_transcripts(exp_id, trapid_db_data)
    sys.stderr.write("[Message] %d ambiguous RNA transcripts were retrieved! \n" % len(ambiguous_transcripts))
    if ambiguous_transcripts:
        create_subset(exp_id, SUBSET_NAMES['rf_gf'], ambiguous_transcripts, trapid_db_data)


def main(config_dict):
    exp_id = config_dict["experiment"]["exp_id"]
    # List containing all needed parameters for `common.db_connect()`
    trapid_db_data = [config['trapid_db']['trapid_db_username'], config['trapid_db']['trapid_db_password'],
                      config['trapid_db']['trapid_db_server'], config['trapid_db']['trapid_db_name']]
    create_ambiguous_rna_transcripts_subset(exp_id, trapid_db_data)


if __name__ == '__main__':
    if len(sys.argv) < 2:
        sys.stderr.write("Error: please provide correct parameters.\n")
        sys.stderr.write("Usage: python create_default_subsets.py exp_initial_processing_settings.ini\n")
        sys.exit(1)
    config = common.load_config(sys.argv[1], {"trapid_db", "experiment"})
    main(config)
