"""
A wrapper to run Infernal versus a selection of RFAM models (ncRNA annotation during TRAPID initial processing)
"""

# Usage: python run_infernal.py exp_initial_processing_settings.ini


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
    """Parse command-line arguments and return them as dictionary"""
    cmd_parser = argparse.ArgumentParser(
        description='''Run Infernal, keep only best non-overlapping hits, upload results to TRAPID db. ''',
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
    needed_sections = {"infernal", "trapid_db", "experiment"}
    if len(needed_sections & config_sections) < len(needed_sections):
        missing_sections = needed_sections - config_sections
        sys.stderr.write("[Error] Not all required sections were found in the INI file ('%s')\n" % ini_file_initial)
        sys.stderr.write("[Error] Missing section(s): %s\n" % ", ".join(list(missing_sections)))
        sys.exit(1)
    return config_dict


def create_infernal_files(exp_id, tmp_exp_dir, rfam_dir, exp_clans, trapid_db_data):
    """Create `cm` and `clanin` files needed by Infernal for user-selected RFAM clans. """
    individual_cms = "individual_cms"  # Name of directory containing individual CMs (in `rfam_dir`)
    rfam_cm_file = "Rfam_%s.cm" % exp_id
    rfam_clans_file = "Rfam_%s.clanin" % exp_id
    sys.stderr.write("[Message] Create RFAM `cm` and `clanin` files for Infernal ('%s' and '%s').\n" % (rfam_cm_file, rfam_clans_file))
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
            out_file.write(clanin_str.format(clan=clan,members="\t".join(clan_members[clan])))
            # Also retrieve RFAM models (update `exp_cms`, later used to retrieve individual models)
            exp_cms.update(clan_members[clan])
    # Create `cm` file
    with open(os.path.join(tmp_exp_dir, rfam_cm_file), "w") as out_file:
        for model in sorted(list(exp_cms)):
            for model_type in ["infernal", "hmmer"]:
                cm_name = "{cm_id}_{cm_type}.cm".format(cm_id=model, cm_type=model_type)
                cm_file = os.path.join(rfam_dir, individual_cms, cm_name)
                cm_lines = []
                with open(cm_file, "r") as in_file:
                    cm_lines = [line for line in in_file]
                out_file.write(''.join(cm_lines))


def run_cmpress(exp_id, tmp_exp_dir):
    """Call `cmpress` (to run before Infernal). """
    cmd_str = "cmpress -F {rfam_cm_file}"
    # Get path of experiment directory and RFAM CM file to use for `cmpress` call
    rfam_cm_file = "Rfam_%s.cm" % exp_id
    rfam_cm_file = os.path.join(tmp_exp_dir, rfam_cm_file)
    # Format cmd string and run!
    formatted_cmd = cmd_str.format(rfam_cm_file=os.path.join(tmp_exp_dir, rfam_cm_file))
    sys.stderr.write("[Message] Call `cmpress` with command: %s.\n" % formatted_cmd)
    job = subprocess.Popen(formatted_cmd, shell=True)
    job.communicate()


def get_cmscan_z_value(exp_id, trapid_db_data):
    """Retrieve value needed for cmscan `-Z` parameter (total length in million of nucleotides of query sequences). """
    query_str = "SELECT SUM(`len`) FROM (SELECT CHAR_LENGTH(UNCOMPRESS(`transcript_sequence`)) AS len FROM `transcripts` WHERE experiment_id ='{exp_id}') tr;"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id))
    total_nts = float([record for record in cursor.fetchone()][0])
    db_conn.close()
    print total_nts
    return (total_nts / 10e6) * 2


def run_infernal(exp_id, tmp_exp_dir, z_value):
    """Run infernal, return path of tabulated output file"""
    # Command-line to run
    cmd_str = "cmscan -Z {z_value} --cut_ga --rfam --nohmmonly --cpu {n_cpu} --tblout {tblout_out_file} --fmt 2 --clanin {rfam_clans_file} {rfam_cm_file} {fasta_file} > {cmscan_out_file}"
    # Define path/name of files to use for Infernal
    fasta_file = os.path.join(tmp_exp_dir, "transcripts_%s.fasta" % exp_id)
    cmscan_out_file = os.path.join(tmp_exp_dir, "infernal_%s.cmscan" % exp_id)
    tblout_out_file = os.path.join(tmp_exp_dir, "infernal_%s.tblout" % exp_id)
    rfam_clans_file = os.path.join(tmp_exp_dir, "Rfam_%s.clanin" % exp_id)
    rfam_cm_file = os.path.join(tmp_exp_dir, "Rfam_%s.cm" % exp_id)
    # Format cmd string and run!
    formatted_cmd = cmd_str.format(z_value=str(z_value), n_cpu="2", tblout_out_file=tblout_out_file,
        rfam_clans_file=rfam_clans_file, rfam_cm_file=rfam_cm_file, fasta_file=fasta_file, cmscan_out_file=cmscan_out_file)
    sys.stderr.write("[Message] Call Infernal with command: %s.\n" % formatted_cmd)
    job = subprocess.Popen(formatted_cmd, shell=True)
    job.communicate()
    return tblout_out_file


def filter_out_overlaps(exp_id, tmp_exp_dir, tblout_file):
    """Filter out overlapping matches in Infernal tabulated output file. Return name of filtered output"""
    tblout_filtered_file = os.path.join(tmp_exp_dir, "infernal_%s.filtered.tblout" % exp_id)
    to_keep = []
    with open(tblout_file, "r") as in_file:
        for line in in_file:
            if " = " not in line:
                to_keep.append(line)
    with open(tblout_filtered_file, "w") as out_file:
        out_file.write(''.join(to_keep))
    return tblout_filtered_file


#TODO: use the same attributes as in the output file for more consistency?
def parse_infernal_tblout_rec(rec_str):
    """Parse 1 record (line) from filtered Infernal tabulated output. Return a dictionary. """
    splitted = rec_str.split()
    rec_dict = {
        "cm_id": splitted[1], "cm_acc": splitted[2], "query": splitted[3], "clan": splitted[5],
        "mdl_type": splitted[6], "mdl_from": splitted[7], "mdl_to": splitted[8],
        "seq_from": splitted[9], "seq_to": splitted[10], "strand": splitted[11],
        "trunc": splitted[12], # "pass": splitted[13],  # Probably not useful to keep this value
        "gc": splitted[14], "bias": splitted[15], "score": splitted[16], "e_value": splitted[17],
        "inc": splitted[18]  # ,
        # "olp": splitted[19]  # No need to keep that one because we already removed overlapping hits?
        # The rest of the columns have to see with overlapping hits too...
    }
    return rec_dict


def infernal_tblout_to_list(tblout_file):
    """Parse Infernal tabulated output (filtered) and return results as dictionary"""
    infernal_res = []
    with open(tblout_file, "r") as in_file:
        for line in in_file:
            if not line.startswith("#"):
                infernal_res.append(parse_infernal_tblout_rec(rec_str=line.strip()))
    return infernal_res


def flag_rna_genes(exp_id, trapid_db_data, infernal_results):
    """Flag a set of transcripts as RNA genes in TRAPID's database"""
    sys.stderr.write('[Message] Flag RNA genes in `transcripts` table. \n')
    query_str = "UPDATE `transcripts` SET `is_rna_gene`=1, `rf_ids`='{rf_ids}' WHERE `experiment_id`='{exp_id}' and transcript_id='{transcript_id}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    # Get RFAM families associated to each transcript
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
    """Cleanup a table from the db for an experiment"""
    query_str = "DELETE FROM `{table_name}` WHERE `experiment_id`='{exp_id}'"
    # Cleanup previous Infernal results for the experiment
    sys.stderr.write('[Message] Cleanup previous data from `{table_name}`. \n'.format(table_name=table_name))
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(table_name=table_name, exp_id=exp_id))
    db_conn.commit()
    db_conn.close()


def store_rna_similarities(exp_id, trapid_db_data, infernal_results):
    """Store Infernal tabulated output data in the `rna_similarities` table of TRAPID's db. """
    # First cleanup the table
    cleanup_table(exp_id=exp_id, table_name="rna_similarities", trapid_db_data=trapid_db_data)
    sys.stderr.write('[Message] Store Infernal results in `rna_similarities`. \n')
    query_str = "INSERT INTO `rna_similarities` (`experiment_id`,`transcript_id`,`similarity_data`) VALUES ('{exp_id}','{transcript_id}', COMPRESS(\"{infernal_data}\"))";
    # Get and format similarity data
    fields_to_keep = ["cm_acc", "cm_id", "clan", "e_value", "score", "bias", "mdl_from", "mdl_to", "trunc", "seq_from", "seq_to"]
    rna_sim_data = {}
    for rec in infernal_results:
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



def store_rna_families(exp_id, trapid_db_data, infernal_results):
    """Populate `rna_families` for the experiment from Infernal results"""
    # First cleanup the table
    cleanup_table(exp_id=exp_id, table_name="rna_families", trapid_db_data=trapid_db_data)
    sys.stderr.write('[Message] Store Infernal results in `rna_families`. \n')
    query_str = "INSERT INTO `rna_families` (`experiment_id`,`rf_id`,`rfam_rf_id`,`rfam_clan_id`, `num_transcripts`) VALUES ('{e}','{f}','{rf}','{c}','{n}')";
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
    """Retrieve RFAM GO annotation stored the `configuration` table of TRAPID's database. Return it as cm:gos dict. """
    sys.stderr.write("[Message] Retrieve RFAM GO annotation from `configuration`.\n")
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
    """
    Get GO data (hierarchy, alternative IDs, aspects) from the used reference database. Return the retrieved data as dictionary.
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


def perform_go_annotation(infernal_results, trapid_db_data, go_data, rfam_go, exp_id, tmp_exp_dir, chunk_size=10000):
    """
    Populate `transcripts_annotation` table of TRAPID DB with GO annotation from RFAM.
    """
    sys.stderr.write("[Message] Perform GO annotation.\n")
    go_annot_query = "INSERT INTO `transcripts_annotation` (`experiment_id`, `type`, `transcript_id`, `name`, `is_hidden`) VALUES (%s, 'go', %s, %s, %s)"
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
                sys.sterr.write("[Warning] Invalid GO term: %s. \n" % go_term)
            # If the GO term is valid it is added to `transcript_annotations`
            if is_valid:
                transcript_gos[transcript].add(go_term)
    # Add parental GOs and filter top GOs
    for transcript in transcript_gos:
        go_parents =  get_go_parents(transcript_gos[transcript], go_data)
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


# TODO: clean up transcripts table
# TODO: more results filtering...
def main(config_dict):
    """Main function: run Infernal, filter results and flag RNA genes in TRAPID db. """
    exp_id = config_dict["experiment"]["exp_id"]
    tmp_exp_dir = config_dict["experiment"]["tmp_exp_dir"]
    rfam_dir = config_dict["infernal"]["rfam_dir"]
    exp_clans = config_dict["infernal"]["rfam_clans"].split(",")
    # Lists containing all needed parameters for `common.db_connect()` (TRAPID + reference DB)
    trapid_db_data = [config['trapid_db']['trapid_db_username'], config['trapid_db']['trapid_db_password'],
                      config['trapid_db']['trapid_db_server'], config['trapid_db']['trapid_db_name']]
    reference_db_data = [config['reference_db']['reference_db_username'], config['reference_db']['reference_db_password'],
                         config['reference_db']['reference_db_server'], config['reference_db']['reference_db_name']]
    db_connection = common.db_connect(*trapid_db_data)
    common.update_experiment_log(experiment_id=exp_id, action='start_nc_rna_search', params='Infernal', depth=2, db_conn=db_connection)
    db_connection.close()
    create_infernal_files(exp_id=exp_id, tmp_exp_dir=tmp_exp_dir, rfam_dir=rfam_dir, exp_clans=exp_clans, trapid_db_data=trapid_db_data)
    run_cmpress(exp_id=exp_id, tmp_exp_dir=tmp_exp_dir)
    total_m_nts = get_cmscan_z_value(exp_id=exp_id, trapid_db_data=trapid_db_data)
    infernal_tblout = run_infernal(exp_id=exp_id, tmp_exp_dir=tmp_exp_dir, z_value=total_m_nts)
    # Filter Infernal tabulated output (keep best non-ovelrapping matches)
    infernal_tblout_filtered = filter_out_overlaps(exp_id=exp_id, tmp_exp_dir=tmp_exp_dir, tblout_file=infernal_tblout)
    # Get filtered results as list of dict
    infernal_results = infernal_tblout_to_list(tblout_file=infernal_tblout_filtered)
    # Flag potential rna genes (`is_rna_gene` value set to 1 in `transcripts` table)
    flag_rna_genes(exp_id=exp_id, trapid_db_data=trapid_db_data, infernal_results=infernal_results)
    # Store filtered results in `rna_similarities` ...
    store_rna_similarities(exp_id=exp_id, trapid_db_data=trapid_db_data, infernal_results=infernal_results)
    # ... and `rna_families`
    store_rna_families(exp_id=exp_id, trapid_db_data=trapid_db_data, infernal_results=infernal_results)
    # Annotate transcripts using GO terms from RFAM
    rfam_go = retrieve_rfam_go_data(trapid_db_data=trapid_db_data)
    go_data = get_go_data(reference_db_data=reference_db_data)
    perform_go_annotation(infernal_results=infernal_results, trapid_db_data=trapid_db_data, go_data=go_data,
                          rfam_go=rfam_go, exp_id=exp_id, tmp_exp_dir=tmp_exp_dir)
    # That's it for now... More soon!

    db_connection = common.db_connect(*trapid_db_data)
    common.update_experiment_log(experiment_id=exp_id, action='stop_nc_rna_search', params='Infernal', depth=2, db_conn=db_connection)
    db_connection.close()


if __name__ == '__main__':
    cmd_args = parse_arguments()
    sys.stderr.write('[Message] Starting ncRNA annotation procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))
    # Read experiment's initial processing configuration file
    config = load_config(cmd_args['ini_file_initial'])
    # Run Infernal, parse and export results to DB
    main(config_dict=config)
    sys.stderr.write('[Message] Finished ncRNA annotation procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))
