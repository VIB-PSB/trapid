"""
A post-processing script that uploads eggnog-mapper's results to TRAPID database, for a given experiment.
This script replaces the GF assignment and functional annotation steps of the TRAPID pipeline.
"""

import argparse
import common
import MySQLdb as MS
import os
import subprocess
import sys
import time
from ConfigParser import ConfigParser

TOP_GOS = {'GO:0003674', 'GO:0008150', 'GO:0005575'}


def parse_arguments():
    """
    Parse command-line arguments and return them as dictionary
    """
    cmd_parser = argparse.ArgumentParser(
        description='''Upload eggnog-mapper's results to TRAPID database. ''',
        formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    cmd_parser.add_argument('ini_file_initial', type=str,
                            help='Initial processing configuration file (generated upon initial processing start)')
    cmd_args = cmd_parser.parse_args()
    return vars(cmd_args)


def load_config(ini_file_initial):
    """Read initial processing configuration file and check if all needed sections are there. Return it as dictionary. """
    config = ConfigParser()
    config.read(ini_file_initial)
    config_dict = {section: dict(config.items(section)) for section in config.sections()}
    config_sections = set(config_dict.keys())
    needed_sections = {"trapid_db", "reference_db", "experiment"}
    if len(needed_sections & config_sections) < len(needed_sections):
        missing_sections = needed_sections - config_sections
        sys.stderr.write("[Error] Not all required sections were found in the INI file ('%s')\n" % ini_file_initial)
        sys.stderr.write("[Error] Missing section(s): %s\n" % ", ".join(list(missing_sections)))
        sys.exit(1)
    return config_dict


def cleanup_db(trapid_db_conn, exp_id):
    """
    Clean up potential previous results from TRAPID DB for current experiment.
    """
    sys.stderr.write("[Message] Cleanup TRAPID db...\n")
    sql_queries = [
    "UPDATE `transcripts` SET `gf_id`=NULL, `orf_sequence`=NULL, `detected_frame`='0', `detected_strand`='+', `full_frame_info`=NULL, `putative_frameshift`='0', `is_frame_corrected`='0', `orf_start`=NULL,`orf_stop`=NULL, `orf_contains_start_codon`=NULL,`orf_contains_stop_codon`=NULL, `meta_annotation`='No Information',`meta_annotation_score`=NULL,`gf_id_score`=NULL WHERE `experiment_id`='{exp_id}';",
    "DELETE FROM `gene_families` WHERE `experiment_id`='{exp_id}'",
    "DELETE FROM `transcripts_annotation` WHERE `experiment_id`='{exp_id}'",
    "DELETE FROM `similarities` WHERE `experiment_id`='{exp_id}'"
    ]
    for query_str in sql_queries:
        sys.stderr.write(query_str.format(exp_id=exp_id) + "\n")
        cursor = trapid_db_conn.cursor()
        cursor.execute(query_str.format(exp_id=exp_id))
        trapid_db_conn.commit()


def parse_emapper_output(emapper_output):
    """
    Parse emapper's `.annotations` output file, and return them as dictionary.
    """
    emapper_results = {}
    with open(emapper_output) as in_file:
        for line in in_file:
            if line[0] != '#':
                splitted = line.strip().split('\t')
                emapper_results[splitted[0]] = {
                    "go_terms": set(splitted[5].split(',')),
                    "ko_terms": set(splitted[6].split(',')),
                    "ogs": splitted[9].split(','),
                    "tax_scope": splitted[8]
                }
    return emapper_results


# TODO: use prepared statements...
# TODO: avoid having empty sets from start!
def perform_gf_assignment(emapper_results, trapid_db_conn, exp_id):
    """
    Perform GF assignment (upload to TRAPID db). The assigned GF in TRAPID correspond to the OG of the seed ortholog
    sequence at the user-selected taxonomic scope.
    """
    sys.stderr.write("[Message] Perform GF assignment... \n")
    gf_transcripts = {}  # Maps GF and number of assocaited transcripts / original ID
    trapid_gf_str = "{exp_id}_{gf_id}"  # TRAPID GF ids template
    # SQL queries
    transcripts_query = "UPDATE `transcripts` SET `gf_id`= '{trapid_gf_id}' , `gf_id_score` = 1 WHERE `experiment_id`='{exp_id}' AND `transcript_id` = '{transcript_id}';";
    gf_query = "INSERT INTO `gene_families` (`experiment_id`,`gf_id`,`plaza_gf_id`,`num_transcripts`) VALUES ('{exp_id}', '{trapid_gf_id}' , '{gf_id}' , '{n_transcripts}');"

    trapid_db_conn.autocommit = False
    cursor = trapid_db_conn.cursor()
    # Update transcripts with their associated GF information
    for transcript, results in emapper_results.items():
        if not results['ogs'] == set(['']):
            # Get tax. scope and corresponding ortholog group
            tax_scope = results['tax_scope'].split('[')[0]  # Beurk
            chosen_og = [og for og in results['ogs'] if og.endswith(tax_scope)][0]
            chosen_og = chosen_og.split('@')[0]
            trapid_og = trapid_gf_str.format(exp_id=exp_id, gf_id=chosen_og)
            # Count transcript in `gf_transcripts`
            if trapid_og in gf_transcripts:
                gf_transcripts[trapid_og]["n_transcripts"] += 1
            else:
                gf_transcripts[trapid_og] = {"gf_id": chosen_og, "n_transcripts": 1}
            # Update transcript in `transcripts` table
            cursor.execute(transcripts_query.format(exp_id=exp_id, trapid_gf_id=trapid_og, transcript_id=transcript))
    trapid_db_conn.commit()
    # Populate `gene_families` table
    for gf, gf_data in gf_transcripts.items():
        cursor.execute(gf_query.format(exp_id=exp_id, trapid_gf_id=gf, gf_id=gf_data['gf_id'], n_transcripts=gf_data['n_transcripts']))
    trapid_db_conn.commit()


def get_go_data(ref_db_conn):
    """
    Get GO data (hierarchy, alternative IDs, aspects) from the used reference database. Return the retrieved data as dictionary.
    """
    sys.stderr.write("[Message] Fetch GO data from ref. DB... \n")
    go_dict = {}
    func_data_query = "SELECT `name`, `desc`, `alt_ids`, `is_obsolete`, `replacement`, `info` FROM `functional_data` WHERE `type`='go';"
    func_parents_query = "SELECT `child`, `parent` from `functional_parents` WHERE `type`='go';"
    go_dict = {}
    # 1. Read `functional_data` table
    cursor = ref_db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(func_data_query)
    for record in cursor.fetchall():
        is_obsolete = False
        replace_by = None
        alt_ids = set([])
        if record['is_obsolete'] == 1:
            is_obsolete = True
            replace_by = record['replacement']
        if record['alt_ids'] != '':
            alt_ids = set(record['alt_ids'].split(','))
        go_dict[record['name']] = {'desc': record['desc'], 'aspect': record['info'], 'parents': set([]),
                                   'is_obsolete': is_obsolete, 'alt_ids': alt_ids, 'replace_by': replace_by}
    # 2. Retrieve GO hierarchy from `functional_parents` table, then populate `go_dict` with parents
    go_hierarchy = {}
    cursor.execute(func_parents_query)
    for record in cursor.fetchall():
        child = record['child']
        parent = record['parent']
        if child not in go_hierarchy:
            go_hierarchy[child] = set([parent])
        else:
            go_hierarchy[child].add(parent)
    # Now populate `go_dict` with parents
    for go in go_dict:
        if not go_dict[go]['is_obsolete']:
            if go in go_hierarchy:
                go_dict[go]['parents'].update(go_hierarchy[go])
            else:
                sys.stderr.write("[Warning] No parents found for '%s'.\n" % go)
    return go_dict


def get_alt_gos(go_dict):
    """
    Return an alt_id:go mapping dictionary from information retrieved from `go_dict`.
    """
    alt_gos = {}
    for go in go_dict:
        if go_dict[go]['alt_ids']:
            for alt_go in go_dict[go]['alt_ids']:
                alt_gos[alt_go] = go
    return alt_gos


def get_go_parents(transcript_annotation, go_dict):
    """
    For a given transcript, retrieve GO parents from `go_dict`. Return parents as dictionary (go_aspect:go_parents)
    """
    go_parents = set()
    for go in transcript_annotation:
            go_parents.update(go_dict[go]['parents'])
    return go_parents


def perform_go_annotation(emapper_results, trapid_db_conn, go_data, exp_id, chunk_size=10000):
    """
    Populate `transcripts_annotation` table of TRAPID DB with GO annotation from emapper's results.
    """
    sys.stderr.write("[Message] Preform GO annotation...\n")
    go_annot_query = "INSERT INTO `transcripts_annotation` (`experiment_id`, `type`, `transcript_id`, `name`, `is_hidden`) VALUES (%s, 'go', %s, %s, %s)"
    go_annot_values = []

    # Create final set of GO annotations for all transcripts: replace obsolate/alt. GOs, and retrieve parental terms
    transcript_gos = {}
    alt_gos = get_alt_gos(go_data)  # Alternative GO to 'regular' GO mapping
    for transcript, results in emapper_results.items():
        transcript_gos[transcript] = set()
        for go in results['go_terms']:
            is_valid = True
            go_term = go
            # Check if GO term is alternative and replace it
            if go_term in alt_gos:
                go_term = alt_gos[go_term]
            # Check if GO term is obsolete and replace it by the term in `replace_by`.
            if go_term in go_data and go_data[go_term]['is_obsolete']:
                go_term = go_dict[go_term]['replace_by']
            if go_term not in go_data:
                is_valid = False  # i.e. we couldn't replace the GO (not alt. ID or obsolete) + not found -> invalid
            # If the GO term is valid it is added to `transcript_annotations`
            if is_valid:
                transcript_gos[transcript].add(go_term)
    # Add parental GOs and filter top GOs
    for transcript in transcript_gos:
        go_parents =  get_go_parents(transcript_gos[transcript], go_data)
        transcript_gos[transcript].update(go_parents)
        transcript_gos[transcript] = transcript_gos[transcript] - TOP_GOS
    print transcript_gos

    # Create a list of tuples with the values to insert
    for transcript, gos in transcript_gos.items():
        for go in gos:
            is_hidden = 1
            all_children = set([k for k in go_data if go in go_data[k]['parents']])
            # If term has no children in the associated transcript's GO terms, `is_hidden` equals 0
            if not all_children & gos:
                is_hidden = 0
            values = (exp_id, transcript, go, is_hidden)
            go_annot_values.append(values)

    # Populate `transcripts_annotation`
    trapid_db_conn.autocommit = False
    cursor = trapid_db_conn.cursor()
    for i in range(0, len(go_annot_values), chunk_size):
        # print go_annot_values[i:min(i+chunk_size, len(go_annot_values))]  # Debug
        cursor.executemany(go_annot_query, go_annot_values[i:min(i+chunk_size, len(go_annot_values))])
    trapid_db_conn.commit()


def perform_ko_annotation(emapper_results, trapid_db_conn, exp_id, chunk_size=10000):
    """
    Populate `transcripts_annotation` table of TRAPID DB with KO annotation from emapper's results.
    """
    sys.stderr.write("[Message] Preform KO annotation...\n")
    ko_annot_query = "INSERT INTO `transcripts_annotation` (`experiment_id`, `type`, `transcript_id`, `name`, `is_hidden`) VALUES (%s, 'ko', %s, %s, %s)"
    ko_annot_values = []

    # Create a list of tuples with the values to insert
    for transcript, results in emapper_results.items():
        if results['ko_terms']!= set(['']):
            for ko in results['ko_terms']:
                values = (exp_id, transcript, ko, 0)
                ko_annot_values.append(values)
    # Populate `transcripts_annotation`
    trapid_db_conn.autocommit = False
    cursor = trapid_db_conn.cursor()
    for i in range(0, len(ko_annot_values), chunk_size):
        cursor.executemany(ko_annot_query, ko_annot_values[i:min(i+chunk_size, len(ko_annot_values))])
    trapid_db_conn.commit()


def main(ini_file_initial):
    # Load experiment configuration
    config = load_config(ini_file_initial)
    # TRAPID db data (list containing all needed parameters for `common.db_connect()`)
    trapid_db_data = [config['trapid_db']['trapid_db_username'], config['trapid_db']['trapid_db_password'],
                      config['trapid_db']['trapid_db_server'], config['trapid_db']['trapid_db_name']]
    # Ref. db data (list containing all needed parameters for `common.db_connect()`)
    ref_db_data = [config['reference_db']['reference_db_username'], config['reference_db']['reference_db_password'],
                   config['reference_db']['reference_db_server'], config['reference_db']['reference_db_name']]
    exp_id = config['experiment']['exp_id']

    # Clean TRAPID db (in case of previous results)
    db_conn = common.db_connect(*trapid_db_data)
    cleanup_db(db_conn, exp_id)
    db_conn.close()

    # Parse Eggnog-mapper's output file
    emapper_output = os.path.join(config['experiment']['tmp_exp_dir'], "emapper_%s.emapper.annotations" % exp_id)
    if not os.path.exists(emapper_output):
        sys.stderr.write("Error: emapper output file (%s) not found!\n" % emapper_output)
    emapper_results = parse_emapper_output(emapper_output)

    # Perform GF assignment
    db_conn = common.db_connect(*trapid_db_data)
    perform_gf_assignment(emapper_results, db_conn, exp_id)
    db_conn.close()

    # Get GO data
    db_conn = common.db_connect(*ref_db_data)
    go_data = get_go_data(db_conn)
    db_conn.close()
    # Perform GO annotation
    db_conn = common.db_connect(*trapid_db_data)
    perform_go_annotation(emapper_results, db_conn, go_data, exp_id)
    db_conn.close()

    # Perform KO annotation
    db_conn = common.db_connect(*trapid_db_data)
    perform_ko_annotation(emapper_results, db_conn, exp_id)
    db_conn.close()


if __name__ == '__main__':
    cmd_args = parse_arguments()
    main(**cmd_args)
