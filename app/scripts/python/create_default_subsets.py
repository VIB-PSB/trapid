"""
Create default transcript subsets at the end of initial processing.
"""

# Usage: python create_default_subsets.py exp_initial_processing_settings.ini

import sys

import common


SUBSET_NAMES = {"rf_gf": "RNA_ambiguous", "gf_only": "Protein_coding", "rf_only": "RNA"}


def delete_subset(exp_id, subset_name, trapid_db_data):
    """For a TRAPID experiment, delete a transcript subset from the `transcripts_labels` table of TRAPID database.

    :param exp_id: TRAPID experiment id
    :param subset_name: name of the subset to delete
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())

    """
    sys.stderr.write("[Message] Delete transcript subset '%s' for experiment '%s'.\n" % (subset_name, exp_id))
    query_str = "DELETE FROM `transcripts_labels` WHERE `experiment_id`='{exp_id}' AND `label`='{subset_name}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id, subset_name=subset_name))
    db_conn.commit()
    db_conn.close()


def create_subset(exp_id, subset_name, transcripts, trapid_db_data):
    """Create a subset containing a given set of transcripts.

    :param exp_id: TRAPID experiment id
    :param subset_name: name of the subset to create
    :param transcripts: set of transcript identifiers in the subset
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())

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
    """Retrieve 'ambiguous' RNA transcripts, i.e. transcripts that are part of both a gene family and a RNA family, and
    return them as set of transcript identifiers.

    :param exp_id: TRAPID experiment id
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())
    :return: 'ambiguous' RNA transcripts (set of transcript identifiers)

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


def get_rna_transcripts(exp_id, trapid_db_data):
    """Retrieve RNA transcripts, i.e. transcripts that are part of a RNA family but no gene family, and return them as
    a set of transcript identifiers.

    :param exp_id: TRAPID experiment id
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())
    :return: RNA transcripts (set of transcript identifiers)

    """
    sys.stderr.write("[Message] Retrieve RNA transcripts for experiment '%s'.\n" % exp_id)
    query_str = "SELECT `transcript_id` FROM `transcripts` WHERE `experiment_id`='{exp_id}' AND `gf_id` IS NULL AND `rf_ids` IS NOT NULL;"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id))
    transcripts = set([record[0] for record in cursor.fetchall()])
    db_conn.commit()
    db_conn.close()
    return transcripts


def get_protein_coding_transcripts(exp_id, trapid_db_data):
    """Retrieve protein-coding transcripts, i.e. transcripts that are part of a gene family but no RNA family, and
    return them as set of transcript identifiers.

    :param exp_id: TRAPID experiment id
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())
    :return: protein-coding transcripts (set of transcript identifiers)

    """
    sys.stderr.write("[Message] Retrieve protein-coding transcripts for experiment '%s'.\n" % exp_id)
    query_str = "SELECT `transcript_id` FROM `transcripts` WHERE `experiment_id`='{exp_id}' AND `gf_id` IS NOT NULL AND `rf_ids` IS NULL;"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id))
    transcripts = set([record[0] for record in cursor.fetchall()])
    db_conn.commit()
    db_conn.close()
    return transcripts


def create_ambiguous_rna_transcripts_subset(exp_id, trapid_db_data):
    """Create subset for ambiguous RNA transcripts.

    :param exp_id: TRAPID experiment id
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())

    """
    delete_subset(exp_id, SUBSET_NAMES['rf_gf'], trapid_db_data)
    ambiguous_transcripts = get_ambiguous_rna_transcripts(exp_id, trapid_db_data)
    sys.stderr.write("[Message] %d ambiguous RNA transcripts were retrieved! \n" % len(ambiguous_transcripts))
    if ambiguous_transcripts:
        create_subset(exp_id, SUBSET_NAMES['rf_gf'], ambiguous_transcripts, trapid_db_data)


def create_rna_transcripts_subset(exp_id, trapid_db_data):
    """Create subset for RNA transcripts.

    :param exp_id: TRAPID experiment id
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())

    """
    delete_subset(exp_id, SUBSET_NAMES['rf_only'], trapid_db_data)
    rna_transcripts = get_rna_transcripts(exp_id, trapid_db_data)
    sys.stderr.write("[Message] %d RNA transcripts were retrieved! \n" % len(rna_transcripts))
    if rna_transcripts:
        create_subset(exp_id, SUBSET_NAMES['rf_only'], rna_transcripts, trapid_db_data)


def create_protein_coding_transcripts_subset(exp_id, trapid_db_data):
    """Create subset for protein-coding transcripts.

    :param exp_id: TRAPID experiment id
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())

    """
    delete_subset(exp_id, SUBSET_NAMES['gf_only'], trapid_db_data)
    pc_transcripts = get_protein_coding_transcripts(exp_id, trapid_db_data)
    sys.stderr.write("[Message] %d protein-coding transcripts were retrieved! \n" % len(pc_transcripts))
    if pc_transcripts:
        create_subset(exp_id, SUBSET_NAMES['gf_only'], pc_transcripts, trapid_db_data)


def main():
    if len(sys.argv) < 2:
        sys.stderr.write("Error: please provide correct parameters.\n")
        sys.stderr.write("Usage: python create_default_subsets.py exp_initial_processing_settings.ini\n")
        sys.exit(1)
    config = common.load_config(sys.argv[1], {"trapid_db", "experiment"})

    exp_id = config["experiment"]["exp_id"]
    # List containing all needed parameters for `common.db_connect()`
    trapid_db_data = common.get_db_connection_data(config, 'trapid_db')
    # Create default subsets
    create_ambiguous_rna_transcripts_subset(exp_id, trapid_db_data)
    create_rna_transcripts_subset(exp_id, trapid_db_data)
    create_protein_coding_transcripts_subset(exp_id, trapid_db_data)


if __name__ == '__main__':
    main()
