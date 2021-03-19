"""
A script to perform the ncRNA annotation step of TRAPID's initial processing (wrapper to run Infernal against a
selection of Rfam models).
"""

# Usage: python run_infernal.py exp_initial_processing_settings.ini

import argparse
import os
import subprocess
import sys
import time

from traceback import print_exc

import MySQLdb as MS

import common

TOP_GOS = {'GO:0003674', 'GO:0008150', 'GO:0005575'}


def parse_arguments():
    """Parse command-line arguments.

    :return: parsed arguments (Namespace object)

    """
    cmd_parser = argparse.ArgumentParser(
        description='Run Infernal, keep only best non-overlapping hits, upload results to TRAPID db.',
        formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    cmd_parser.add_argument('ini_file_initial', type=str,
                            help='Initial processing configuration file (generated upon initial processing start)')
    cmd_args = cmd_parser.parse_args()
    return cmd_args


def run_cmfetch(exp_id, tmp_exp_dir, rfam_dir):
    """Call `cmfetch` to retrieve the experiment's CMs and create `cm` file (needed to run Infernal).

    :param exp_id: TRAPID experiment id
    :param tmp_exp_dir: experiment's directory
    :param rfam_dir: directory with Rfam data

    """
    cmd_str = "cmfetch -f {all_rfam_cm_file} {exp_cms_file} > {rfam_cm_file}"
    # Path of general Rfam CM file
    all_rfam_cm_file = os.path.join(rfam_dir, "Rfam.cm")
    # Get path of experiment directory and Rfam CM file to use for `cmfetch` call
    exp_cms_file = os.path.join(tmp_exp_dir, "rfam_cms_%s.lst" % exp_id)
    rfam_cm_file = os.path.join(tmp_exp_dir, "Rfam_%s.cm" % exp_id)
    # Format cmd string and run!
    formatted_cmd = cmd_str.format(all_rfam_cm_file=all_rfam_cm_file, exp_cms_file=exp_cms_file, rfam_cm_file=rfam_cm_file)
    sys.stderr.write("[Message] Call `cmfetch` with command: '%s'.\n" % formatted_cmd)
    job = subprocess.Popen(formatted_cmd, shell=True)
    job.communicate()


def create_infernal_files(exp_id, tmp_exp_dir, rfam_dir, exp_clans, trapid_db_data):
    """Create `cm` and `clanin` files needed by Infernal for user-selected Rfam clans.

    :param exp_id: TRAPID experiment id
    :param tmp_exp_dir: experiment's directory
    :param rfam_dir: directory with Rfam data
    :param exp_clans: user-selected Rfam clans
    :param trapid_db_data: TRAPID db connection data

    """
    # individual_cms = "individual_cms"  # Name of directory containing individual CMs (in `rfam_dir`)
    rfam_clans_file = "Rfam_%s.clanin" % exp_id
    rfam_cm_file = os.path.join(tmp_exp_dir, "Rfam_%s.cm" % exp_id)
    exp_cms_file = "rfam_cms_%s.lst" % exp_id
    sys.stderr.write("[Message] Create Rfam `cm` and `clanin` files for Infernal ('%s' and '%s').\n" % (rfam_cm_file, rfam_clans_file))
    clan_members = {}
    exp_cms = set()
    # Get clan membership information from `configuration` table
    # Since there are only 111 clans, we can retrieve this information for all of them
    query_str = "SELECT `key`, `value` FROM `configuration` WHERE `method`='rfam_clans' AND `attr`='families'"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(query_str)
    for record in cursor.fetchall():
        clan_members[record['key']] = record['value'].split(',')
    db_conn.close()
    # Create `clanin` file
    with open(os.path.join(tmp_exp_dir, rfam_clans_file), "w") as out_file:
        for clan in exp_clans:
            clanin_str = "{clan}\t{members}\n"
            out_file.write(clanin_str.format(clan=clan, members="\t".join(clan_members[clan])))
            # Also retrieve Rfam models (update `exp_cms`, later used to retrieve individual models)
            exp_cms.update(clan_members[clan])
    # Create file listing the experiment's CM (input file for `cmfetch`)
    with open(os.path.join(tmp_exp_dir, exp_cms_file), "w") as out_file:
        out_file.write('\n'.join(sorted(list(exp_cms))) + '\n')
    # Create `cm` file using Infernal's `cmfetch` command
    run_cmfetch(exp_id, tmp_exp_dir, rfam_dir)
    # with open(os.path.join(tmp_exp_dir, rfam_cm_file), "w") as out_file:
    #     for model in sorted(list(exp_cms)):
    #         for model_type in ["infernal", "hmmer"]:
    #             cm_name = "{cm_id}_{cm_type}.cm".format(cm_id=model, cm_type=model_type)
    #             cm_file = os.path.join(rfam_dir, individual_cms, cm_name)
    #             cm_lines = []
    #             with open(cm_file, "r") as in_file:
    #                 cm_lines = [line for line in in_file]
    #             out_file.write(''.join(cm_lines))


def get_infernal_z_value(exp_id, trapid_db_data):
    """Retrieve value needed for cmscan/cmsearch `-Z` parameter (search space size in megabase, here the total length of
    query sequences in Mb multiplied by 2).

    :param exp_id: TRAPID experiment id
    :param trapid_db_data: TRAPID db connection data
    :return: value for `-Z` parameter

    """
    query_str = "SELECT SUM(`len`) FROM (SELECT CHAR_LENGTH(UNCOMPRESS(`transcript_sequence`)) AS len FROM `transcripts` WHERE experiment_id ='{exp_id}') tr;"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id))
    total_nts = float([record for record in cursor.fetchone()][0])
    db_conn.close()
    return (total_nts / 10e6) * 2


def run_infernal(exp_id, tmp_exp_dir, z_value):
    """Run infernal (cmsearch), return path of tabulated output file.

    :param exp_id: TRAPID experiment id
    :param tmp_exp_dir: TRAPID experiment directory
    :param z_value: value for `-Z`
    :return: tabulated output file path

    """
    # Command-line to run
    # cmd_str = "cmscan -Z {z_value} --cut_ga --rfam --noali --nohmmonly --cpu {n_cpu} --tblout {tblout_out_file} --fmt 2 --clanin {rfam_clans_file} {rfam_cm_file} {fasta_file} > {cmscan_out_file}"
    cmd_str = "cmsearch --cut_ga --rfam --noali --nohmmonly --cpu {n_cpu} -Z {z_value}  --clanin {rfam_clans_file} --tblout {tblout_out_file} -o {cmsearch_out_file} {rfam_cm_file} {fasta_file}"
    # Define path/name of files to use for Infernal
    fasta_file = os.path.join(tmp_exp_dir, "transcripts_%s.fasta" % exp_id)
    # cmscan_out_file = os.path.join(tmp_exp_dir, "infernal_%s.cmscan" % exp_id)
    cmsearch_out_file = os.path.join(tmp_exp_dir, "infernal_%s.cmsearch" % exp_id)
    tblout_out_file = os.path.join(tmp_exp_dir, "infernal_%s.tblout" % exp_id)
    rfam_clans_file = os.path.join(tmp_exp_dir, "Rfam_%s.clanin" % exp_id)
    rfam_cm_file = os.path.join(tmp_exp_dir, "Rfam_%s.cm" % exp_id)
    # Format cmd string and run!
    formatted_cmd = cmd_str.format(
        z_value=str(z_value), n_cpu="2", tblout_out_file=tblout_out_file, rfam_clans_file=rfam_clans_file,
        rfam_cm_file=rfam_cm_file, fasta_file=fasta_file, cmsearch_out_file=cmsearch_out_file)
    sys.stderr.write("[Message] Call Infernal (cmsearch) with command: '%s'.\n" % formatted_cmd)
    job = subprocess.Popen(formatted_cmd, shell=True)
    job.communicate()
    if job.returncode != 0:
        raise subprocess.CalledProcessError(job.returncode, formatted_cmd)
    return tblout_out_file


def filter_out_overlaps(exp_id, tmp_exp_dir, tblout_file):
    """Filter out overlapping matches in Infernal tabulated output file. Return name of filtered output.

    Currently unused: this function works to process output files enerated by `cmscan`, and TRAPID now uses `cmsearch`.

    :param exp_id: TRAPID experiment id
    :param tmp_exp_dir: experiment directory
    :param tblout_file: `cmscan` tabluted output file (unfiltered)
    :return: filtered output file path

    """
    tblout_filtered_file = os.path.join(tmp_exp_dir, "infernal_%s.filtered.tblout" % exp_id)
    to_keep = []
    with open(tblout_file, "r") as in_file:
        for line in in_file:
            if " = " not in line:
                to_keep.append(line)
    with open(tblout_filtered_file, "w") as out_file:
        out_file.write(''.join(to_keep))
    return tblout_filtered_file


def keep_best_results(exp_id, tmp_exp_dir, tblout_file):
    """Filter out Infernal tabulated output file to keep only the best hit per query sequence. Return name of filtered
    output.

    :param exp_id: TRAPID experiment id
    :param tmp_exp_dir: experiment directory
    :param tblout_file: (unfiltered) tabulated output file path
    :return: filtered tabulated output file path

    """
    tblout_filtered_file = os.path.join(tmp_exp_dir, "infernal_%s.filtered.tblout" % exp_id)
    to_keep = {}
    with open(tblout_file, "r") as in_file:
        for line in in_file:
            if not line.startswith("#"):
                splitted = line.split()
                query = splitted[0]
                score = float(splitted[14])
                # If there already is a result for this query, replace it if the current one has a better score
                if query in to_keep:
                    if score > to_keep[query]['score']:
                        to_keep[query]['score'] = score
                        to_keep[query]['line'] = line
                # If there are no result for this query yet, add the current result
                else:
                    to_keep[query] = {'score': score, 'line': line}
    # Write filtered output to a file and return its path
    with open(tblout_filtered_file, "w") as out_file:
        out_file.write(''.join([to_keep[query]['line'] for query in to_keep]))
    return tblout_filtered_file


#TODO: use the same attributes as in the output file for more consistency?
def parse_infernal_tblout_rec(rec_str, cm_clans):
    """Parse one record (line) from filtered Infernal tabulated output. Return a dictionary.

    :param rec_str: record (line) of a tabulated output file
    :param cm_clans: Rfam clan data
    :return: dictionary for the parsed record

    """
    splitted = rec_str.split()
    # When we were using `cmscan`
    # rec_dict = {
    #     "cm_id": splitted[1], "cm_acc": splitted[2], "query": splitted[3], "clan": splitted[5],
    #     "mdl_type": splitted[6], "mdl_from": splitted[7], "mdl_to": splitted[8],
    #     "seq_from": splitted[9], "seq_to": splitted[10], "strand": splitted[11],
    #     "trunc": splitted[12], # "pass": splitted[13],  # Probably not useful to keep this value
    #     "gc": splitted[14], "bias": splitted[15], "score": splitted[16], "e_value": splitted[17],
    #     "inc": splitted[18]  # ,
    #     # "olp": splitted[19]  # No need to keep that one because we already removed overlapping hits?
    #     # The rest of the columns have to see with overlapping hits too...
    # }
    rec_dict = {
        "query": splitted[0], "cm_id": splitted[2], "cm_acc": splitted[3],
        "mdl_type": splitted[4], "mdl_from": splitted[5], "mdl_to": splitted[6],
        "seq_from": splitted[7], "seq_to": splitted[8], "strand": splitted[9],
        "trunc": splitted[10], # "pass": splitted[13],  # Probably not useful to keep this value
        "gc": splitted[12], "bias": splitted[13], "score": splitted[14], "e_value": splitted[15],
        "inc": splitted[16],
        "clan": cm_clans[splitted[2]]
    }
    return rec_dict


def infernal_tblout_to_list(tblout_file, cm_clans):
    """Parse Infernal tabulated output (filtered), add clan information, and return results as a list of dictionary

    :param tblout_file: tabulated output file
    :param cm_clans: Rfam clan data
    :return: parsed results as list of dictionary

    """
    infernal_res = []
    with open(tblout_file, "r") as in_file:
        for line in in_file:
            if not line.startswith("#"):
                infernal_res.append(parse_infernal_tblout_rec(rec_str=line.strip(), cm_clans=cm_clans))
    return infernal_res


def clear_transcripts_table(exp_id, trapid_db_data):
    """Clear content from the `transcripts` table of the TRAPID database, which is necessary prior to updating the table
    with new Infernal results.

    :param exp_id: TRAPID experiment id
    :param trapid_db_data: TRAPID db connection data

    """
    sys.stderr.write('[Message] Clear content in `transcripts` table. \n')
    clear_query_str = "UPDATE `transcripts` SET `is_rna_gene`=0, `rf_ids`=NULL WHERE `experiment_id`='{exp_id}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    formatted_query = clear_query_str.format(exp_id=exp_id)
    cursor.execute(formatted_query)
    db_conn.commit()
    db_conn.close()


def flag_rna_genes(exp_id, infernal_results, trapid_db_data):
    """Flag a set of transcripts as RNA genes in TRAPID's database.

    :param exp_id: TRAPID experiment id
    :param infernal_results: parsed (filtered) infernal results
    :param trapid_db_data: TRAPID db connection data

    """
    # Before updating `transcripts` with the current Infernal results, clear previous content
    clear_transcripts_table(exp_id, trapid_db_data)
    sys.stderr.write('[Message] Flag RNA genes in `transcripts` table. \n')
    query_str = "UPDATE `transcripts` SET `is_rna_gene`=1, `rf_ids`='{rf_ids}' WHERE `experiment_id`='{exp_id}' and transcript_id='{transcript_id}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    # Get Rfam families associated to each transcript
    transcript_families = {}
    for rec in infernal_results:
        exp_cm_acc = "%s_%s" % (exp_id, rec["cm_acc"])
        if rec["query"] not in transcript_families:
            transcript_families[rec["query"]] = set([exp_cm_acc])
        else:
            transcript_families[rec["query"]].add(exp_cm_acc)
    for transcript_id in sorted(transcript_families):
        rf_ids = ",".join(sorted(list(transcript_families[transcript_id])))
        formatted_query = query_str.format(exp_id=exp_id, rf_ids=rf_ids, transcript_id=transcript_id)
        cursor.execute(formatted_query)
    db_conn.commit()
    db_conn.close()


def cleanup_table(exp_id, table_name, trapid_db_data):
    """Cleanup a table from the db for an experiment.

    :param exp_id: TRAPID experiment id
    :param table_name: the table to cleanup
    :param trapid_db_data: TRAPID db connection data

    """
    query_str = "DELETE FROM `{table_name}` WHERE `experiment_id`='{exp_id}'"
    # Cleanup previous Infernal results for the experiment
    sys.stderr.write('[Message] Cleanup previous data from `{table_name}`. \n'.format(table_name=table_name))
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(table_name=table_name, exp_id=exp_id))
    db_conn.commit()
    db_conn.close()


def store_rna_similarities(exp_id, infernal_results, trapid_db_data):
    """Store Infernal tabulated output data in the `rna_similarities` table of TRAPID's db.

    :param exp_id: TRAPID experiment id
    :param infernal_results: parsed infernal results to store
    :param trapid_db_data: TRAPID db connection data

    """
    # First cleanup the table
    cleanup_table(exp_id=exp_id, table_name="rna_similarities", trapid_db_data=trapid_db_data)
    sorted_infernal_results = sorted(infernal_results, key=lambda k: float(k['score']), reverse=True)
    sys.stderr.write('[Message] Store Infernal results in `rna_similarities`. \n')
    query_str = "INSERT INTO `rna_similarities` (`experiment_id`,`transcript_id`,`similarity_data`) VALUES ('{exp_id}','{transcript_id}', COMPRESS(\"{infernal_data}\"))"
    # Get and format similarity data
    fields_to_keep = ["cm_acc", "cm_id", "clan", "e_value", "score", "bias", "mdl_from", "mdl_to", "trunc", "seq_from", "seq_to"]
    rna_sim_data = {}
    for rec in sorted_infernal_results:
        if rec["query"] not in rna_sim_data:
            sim_str = ",".join([rec[f] for f in fields_to_keep])
            rna_sim_data[rec["query"]] = [sim_str]
        else:
            sim_str = ",".join([rec[f] for f in fields_to_keep])
            rna_sim_data[rec["query"]].append(sim_str)
    for transcript_id in rna_sim_data:
        rna_sim_data[transcript_id] = ";".join(rna_sim_data[transcript_id])
    # Store Infernal results in `rna_similarities`
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    for transcript_id in sorted(rna_sim_data.keys()):
        cursor.execute(query_str.format(exp_id=exp_id, transcript_id=transcript_id, infernal_data=rna_sim_data[transcript_id]))
    db_conn.commit()
    db_conn.close()



def store_rna_families(exp_id, infernal_results, trapid_db_data):
    """Populate `rna_families` for the experiment from Infernal results.

    :param exp_id: TRAPID experiment id
    :param infernal_results: parsed infernal results
    :param trapid_db_data: TRAPID db connection data

    """
    # First cleanup the table
    cleanup_table(exp_id=exp_id, table_name="rna_families", trapid_db_data=trapid_db_data)
    sys.stderr.write('[Message] Store Infernal results in `rna_families`. \n')
    query_str = "INSERT INTO `rna_families` (`experiment_id`,`rf_id`,`rfam_rf_id`,`rfam_clan_id`, `num_transcripts`) VALUES ('{e}','{f}','{rf}','{c}','{n}')"
    # Get and format data from Infernal results
    rna_fam_data = {}
    for rec in infernal_results:
        if rec["cm_acc"] not in rna_fam_data:
            rna_fam_data[rec["cm_acc"]] = {"clan": rec["clan"], "n_transcripts": 1}
        else:
            rna_fam_data[rec["cm_acc"]]["n_transcripts"] += 1
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    for rf_id in sorted(rna_fam_data.keys()):
        exp_rf_id = "%s_%s" % (exp_id, rf_id)
        cursor.execute(query_str.format(e=exp_id, f=exp_rf_id, rf=rf_id, c=rna_fam_data[rf_id]["clan"], n=rna_fam_data[rf_id]["n_transcripts"]))
    db_conn.commit()
    db_conn.close()


def retrieve_rfam_go_data(trapid_db_data):
    """Retrieve Rfam GO annotation stored the `configuration` table of TRAPID's database. Return it as cm:gos dictionary.

    :param trapid_db_data: TRAPID db connection data
    :return: Rfam RNA family GO annotations as cm:gos dictionary.

    """
    sys.stderr.write("[Message] Retrieve Rfam GO annotation from `configuration`.\n")
    rfam_go = {}
    query_str = "SELECT `key`, `value` FROM `configuration` WHERE `method`='rfam_annotation' AND `attr`='go'"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(query_str)
    for record in cursor.fetchall():
        rfam_go[record['key']] = record['value'].split(',')
    db_conn.close()
    return rfam_go


def get_go_data(reference_db_data):
    """Get GO data (hierarchy, alternative IDs, obsolete status, aspects) from the used reference database. Return the
    retrieved data as a dictionary.

    :param reference_db_data: reference db connection data
    :return: parsed GO data and hierarchy as dictionary

    """
    sys.stderr.write("[Message] Fetch GO data from ref. DB (%s)... \n" % reference_db_data[-1])
    go_dict = {}
    func_data_query = "SELECT `name`, `desc`, `alt_ids`, `is_obsolete`, `replacement`, `info` FROM `functional_data` WHERE `type`='go';"
    func_parents_query = "SELECT `child`, `parent` from `functional_parents` WHERE `type`='go';"
    go_dict = {}
    # 1. Read `functional_data` table
    ref_db_conn = common.db_connect(*reference_db_data)
    cursor = ref_db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(func_data_query)
    for record in cursor.fetchall():
        is_obsolete = False
        replace_by = None
        alt_ids = set([])
        if record['is_obsolete'] == 1:
            is_obsolete = True
            replace_by = record['replacement']
        # if record['alt_ids'] != '':
        if record['alt_ids']:
            alt_ids = set(record['alt_ids'].split(','))
        go_dict[record['name']] = {'desc': record['desc'], 'aspect': record['info'], 'parents': set([]), 'children': set([]),
                                   'is_obsolete': is_obsolete, 'alt_ids': alt_ids, 'replace_by': replace_by}
    # 2. Retrieve GO hierarchy from `functional_parents` table, then populate `go_dict` with parents
    go_parents = {}
    go_children = {}
    cursor.execute(func_parents_query)
    for record in cursor.fetchall():
        child = record['child']
        parent = record['parent']
        if child not in go_parents:
            go_parents[child] = set([parent])
        else:
            go_parents[child].add(parent)
        if parent not in go_children:
            go_children[parent] = set([child])
        else:
            go_children[parent].add(child)
    ref_db_conn.close()
    # Now populate `go_dict` with parents/children and return it
    for go in go_dict:
        if not go_dict[go]['is_obsolete']:
            if go in go_parents:
                go_dict[go]['parents'].update(go_parents[go])
            else:
                sys.stderr.write("[Warning] No parents found for '%s'.\n" % go)
            if go in go_children:
                go_dict[go]['children'].update(go_children[go])
            # else:
            #     sys.stderr.write("[Warning] No children found for '%s'.\n" % go)
    return go_dict


def get_alt_gos(go_dict):
    """Return an alt_id:go mapping dictionary from information retrieved from `go_dict`.

    :param go_dict: GO data
    :return: alternative GO data (correspondence between alternative id and regular id)

    """
    alt_gos = {}
    for go in go_dict:
        if go_dict[go]['alt_ids']:
            for alt_go in go_dict[go]['alt_ids']:
                alt_gos[alt_go] = go
    return alt_gos


def get_go_parents(transcript_annotation, go_dict):
    """For a given transcript, retrieve GO parents from `go_dict`. Return parents as dictionary (go_aspect:go_parents)

    :param transcript_annotation: a set of GO terms (GO annotation of a transcript)
    :param go_dict: GO data
    :return: set of parental GO terms for the transcript
    """
    go_parents = set()
    for go in transcript_annotation:
        go_parents.update(go_dict[go]['parents'])
    return go_parents


# The original purpose of this function was to populate `transcripts_annotation` with GO annotation from Rfam.
# def perform_go_annotation(exp_id, infernal_results, rfam_go, go_data, tmp_exp_dir, trapid_db_data, chunk_size=10000):
def perform_go_annotation(exp_id, infernal_results, rfam_go, go_data, tmp_exp_dir):
    """Assign GO terms from Rfam to transcripts matched to RNA models. Currently, the transcript:GO mapping is stored in
    a table, and uploaded to TRAPID's DB during a later step of the initial processing.

    :param exp_id: TRAPID experiment id
    :param infernal_results: parsed infernal results
    :param rfam_go: Rfam GO annotation data
    :param go_data: GO data
    :param tmp_exp_dir: experiment directory

    """
    sys.stderr.write("[Message] Perform GO annotation.\n")
    # go_annot_query = "INSERT INTO `transcripts_annotation` (`experiment_id`, `type`, `transcript_id`, `name`, `is_hidden`) VALUES (%s, 'go', %s, %s, %s)"
    go_annot_values = []  # A list to store values to insert
    # Create final set of GO annotations for all transcripts: replace obsolete/alt. GOs, and retrieve parental terms
    # We ignore GO terms that do not exist in `go_data`
    transcript_gos = {}
    alt_gos = get_alt_gos(go_data)  # Alternative GO to 'regular' GO mapping
    for result_rec in infernal_results:
        transcript = result_rec['query']
        cm_acc = result_rec['cm_acc']
        if transcript not in transcript_gos:
            transcript_gos[transcript] = set()
        for go in rfam_go[cm_acc]:
            is_valid = True
            go_term = go
            # Check if GO term is alternative and replace it
            if go_term in alt_gos:
                go_term = alt_gos[go_term]
            # Check if GO term is obsolete and replace it by the term in `replace_by`.
            if go_term in go_data and go_data[go_term]['is_obsolete']:
                go_term = go_data[go_term]['replace_by']
            if go_term not in go_data:
                is_valid = False  # i.e. we couldn't replace the GO (not alt. ID or obsolete) + not found -> invalid
                sys.stderr.write("[Warning] Invalid GO term: %s. \n" % go_term)
            # If the GO term is valid it is added to `transcript_annotations`
            if is_valid:
                transcript_gos[transcript].add(go_term)
    # Add parental GOs and filter top GOs
    for transcript in transcript_gos:
        go_parents = get_go_parents(transcript_gos[transcript], go_data)
        transcript_gos[transcript].update(go_parents)
        transcript_gos[transcript] = transcript_gos[transcript] - TOP_GOS

    # Create a list of tuples with the values to insert
    for transcript, gos in transcript_gos.items():
        for go in gos:
            is_hidden = 1
            # If term has no children in the associated transcript's GO terms, `is_hidden` equals 0
            if not go_data[go]['children'] & gos:
                is_hidden = 0
            values = (exp_id, transcript, go, is_hidden)
            go_annot_values.append(values)

    # Populate `transcripts_annotation`
    # NOTE: the `transcripts_annotation` data is actually cleaned up in a subsequent processing step, so all results
    # inserted now would be deleted later. Before a proper way to deal with that issue is found, a working solution is
    # to write GO annotations to a file, that will be read afterwards to populate the table.
    # trapid_db_conn = common.db_connect(*trapid_db_data)
    # trapid_db_conn.autocommit = False
    # cursor = trapid_db_conn.cursor()
    # sys.stderr.write("[Message] %d rows to insert!\n" % len(go_annot_values))
    # for i in range(0, len(go_annot_values), chunk_size):
    #     cursor.executemany(go_annot_query, go_annot_values[i:min(i+chunk_size, len(go_annot_values))])
    #     sys.stderr.write("[Message] %s: Inserted %d rows...\n" % (time.strftime('%H:%M:%S'), chunk_size))
    # trapid_db_conn.commit()
    # trapid_db_conn.close()
    rfam_go_file = "rfam_go_data.tsv"
    with open(os.path.join(tmp_exp_dir, rfam_go_file), "w") as out_file:
        out_file.write("\n".join(["\t".join([str(val) for val in rec]) for rec in go_annot_values]))
        out_file.write("\n")


def get_exp_cm_clans(exp_id, tmp_exp_dir):
    """Read experiment's Rfam clan data from `clanin` file. Return as dictionary (cm_id:clan_acc).

    :param exp_id: TRAPID experiment id
    :param tmp_exp_dir: experiment directory
    :return: Rfam cm:clan_acc dictionary

    """
    cm_clans = {}
    rfam_clans_file = os.path.join(tmp_exp_dir, "Rfam_%s.clanin" % exp_id)
    with open(rfam_clans_file, 'r') as in_file:
        for line in in_file:
            splitted = line.strip().split('\t')
            clan_acc = splitted[0]
            for cm_id in splitted[1:]:
                cm_clans[cm_id] = clan_acc
    return cm_clans


# def run_cmpress(exp_id, tmp_exp_dir):
#     """Call `cmpress` (to run before Infernal).
#
#     :param exp_id: TRAPID experiment id
#     :param tmp_exp_dir: experiment directory
#
#     """
#     cmd_str = "cmpress -F {rfam_cm_file}"
#     # Get path of experiment directory and Rfam CM file to use for `cmpress` call
#     rfam_cm_file = os.path.join(tmp_exp_dir, "Rfam_%s.cm" % exp_id)
#     # Format cmd string and run!
#     formatted_cmd = cmd_str.format(rfam_cm_file=rfam_cm_file)
#     sys.stderr.write("[Message] Call `cmpress` with command: '%s'.\n" % formatted_cmd)
#     job = subprocess.Popen(formatted_cmd, shell=True)
#     job.communicate()


def main():
    """
    Main function: run Infernal, filter results and flag RNA genes in TRAPID db.
    """
    cmd_args = parse_arguments()
    # Read experiment's initial processing configuration file
    config = common.load_config(cmd_args.ini_file_initial, {"infernal", "trapid_db", "experiment"})
    # The web application sets the Rfam clan string to 'None' in case the user chose no clans
    # If this is the case, exit the script with an information message
    if config["infernal"]["rfam_clans"] == "None":
        sys.stderr.write("[Message] No Rfam clans selected: skip ncRNA annotation step.\n")
        sys.exit()
    try:
        # Run Infernal, parse and export results to DB
        sys.stderr.write('[Message] Starting ncRNA annotation procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))
        exp_id = config["experiment"]["exp_id"]
        tmp_exp_dir = config["experiment"]["tmp_exp_dir"]
        rfam_dir = config["infernal"]["rfam_dir"]
        exp_clans = config["infernal"]["rfam_clans"].split(",")
        # Lists containing all needed parameters for `common.db_connect()` (TRAPID + reference DB)
        trapid_db_data = common.get_db_connection_data(config, 'trapid_db')
        reference_db_data = common.get_db_connection_data(config, 'reference_db')
        db_connection = common.db_connect(*trapid_db_data)
        common.update_experiment_log(exp_id, 'start_nc_rna_search', 'Infernal', 2, db_connection)
        db_connection.close()
        create_infernal_files(exp_id, tmp_exp_dir, rfam_dir, exp_clans, trapid_db_data)
        # run_cmpress(exp_id=exp_id, tmp_exp_dir=tmp_exp_dir)
        total_m_nts = get_infernal_z_value(exp_id, trapid_db_data)
        infernal_tblout = run_infernal(exp_id, tmp_exp_dir, total_m_nts)
        # Filter Infernal tabulated output (keep best non-ovelrapping matches)
        # infernal_tblout_filtered = filter_out_overlaps(exp_id=exp_id, tmp_exp_dir=tmp_exp_dir, tblout_file=infernal_tblout)
        infernal_tblout_filtered = keep_best_results(exp_id, tmp_exp_dir, infernal_tblout)
        # Get filtered results as list of dict and add clan information
        # Read Rfam clan information from `clanin` file. Would it make more sense to retrieve it when creating it?
        cm_clans = get_exp_cm_clans(exp_id, tmp_exp_dir)
        filtered_infernal_results = infernal_tblout_to_list(infernal_tblout_filtered, cm_clans)
        infernal_results = infernal_tblout_to_list(infernal_tblout, cm_clans)
        # Flag potential rna genes (set `is_rna_gene` value to 1 and `rf_ids` in `transcripts` table)
        flag_rna_genes(exp_id, filtered_infernal_results, trapid_db_data)
        # Store filtered results in `rna_similarities` ...
        store_rna_similarities(exp_id, infernal_results, trapid_db_data)
        # ... and `rna_families`
        store_rna_families(exp_id, filtered_infernal_results, trapid_db_data)
        # Annotate transcripts using GO terms from Rfam
        rfam_go = retrieve_rfam_go_data(trapid_db_data)
        go_data = get_go_data(reference_db_data)
        # perform_go_annotation(exp_id, infernal_results, rfam_go, go_data, tmp_exp_dir)
        perform_go_annotation(exp_id, filtered_infernal_results, rfam_go, go_data, tmp_exp_dir)
        # That's it for now
        db_connection = common.db_connect(*trapid_db_data)
        common.update_experiment_log(exp_id, 'stop_nc_rna_search', 'Infernal', 2, db_connection)
        db_connection.close()
        sys.stderr.write('[Message] Finished ncRNA annotation procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))
    # If any exception was raised, update the experiment's log, set status to 'error', and exit
    except Exception:
        print_exc()
        common.stop_initial_processing_error(exp_id, trapid_db_data)


if __name__ == '__main__':
    main()
