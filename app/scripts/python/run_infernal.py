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


cmd_parser = argparse.ArgumentParser(
    description='''Run Infernal, keep only best non-overlapping hits, upload results to TRAPID db. ''',
    formatter_class=argparse.ArgumentDefaultsHelpFormatter)
cmd_parser.add_argument('ini_file_initial', type=str,
                        help='Initial processing configuration file (generated upon initial processing start)')


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


def create_infernal_files(config_dict):
    """Create `cm` and `clanin` files needed by Infernal for user-selected RFAM clans. """
    individual_cms = "individual_cms"  # Name of directory containing individual CMs (in config's `rfam_dir`)
    tmp_exp_dir = config_dict["experiment"]["tmp_exp_dir"]
    rfam_cm_file = "Rfam_%s.cm" % config_dict["experiment"]["exp_id"]
    rfam_clans_file = "Rfam_%s.clanin" % config_dict["experiment"]["exp_id"]
    sys.stderr.write("[Message] Create RFAM `cm` and `clanin` files for Infernal ('%s' and '%s').\n" % (rfam_cm_file, rfam_clans_file))
    clan_members = {}
    exp_cms = set()
    # Read clans to include for the experiment from config value
    exp_clans = config_dict["infernal"]["rfam_clans"].split(",")
    # Get clan membership information from `configuration` table
    # Since there are only 111 clans, we can retrieve this information for all of them
    query_str = "SELECT `key`, `value` FROM `configuration` WHERE `method`='rfam_clans' AND `attr`='families'"
    db_conn = common.db_connect(config_dict["trapid_db"]["trapid_db_username"], config_dict["trapid_db"]["trapid_db_password"],
        config_dict["trapid_db"]["trapid_db_server"], config_dict["trapid_db"]["trapid_db_name"])
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
                cm_file = os.path.join(config_dict["infernal"]["rfam_dir"], individual_cms, cm_name)
                cm_lines = []
                with open(cm_file, "r") as in_file:
                    cm_lines = [line for line in in_file]
                out_file.write(''.join(cm_lines))


def run_cmpress(config_dict):
    """Call `cmpress` (to run before Infernal). """
    cmd_str = "cmpress -F {rfam_cm_file}"
    # Get path of experiment directory and RFAM CM file to use for `cmpress` call
    tmp_exp_dir = config_dict["experiment"]["tmp_exp_dir"]
    rfam_cm_file = "Rfam_%s.cm" % config_dict["experiment"]["exp_id"]
    rfam_cm_file = os.path.join(tmp_exp_dir, rfam_cm_file)
    # Format cmd string and run!
    formatted_cmd = cmd_str.format(rfam_cm_file=os.path.join(tmp_exp_dir, rfam_cm_file))
    sys.stderr.write("[Message] Call `cmpress` with command: %s.\n" % formatted_cmd)
    job = subprocess.Popen(formatted_cmd, shell=True)
    job.communicate()


def get_cmscan_z_value(config_dict):
    """Retrieve value needed for cmscan `-Z` parameter (total length in million of nucleotides of query sequences). """
    query_str = "SELECT SUM(`len`) FROM (SELECT CHAR_LENGTH(`transcript_sequence`) AS len FROM `transcripts` WHERE experiment_id ='{exp_id}') tr;"
    db_conn = common.db_connect(config_dict["trapid_db"]["trapid_db_username"], config_dict["trapid_db"]["trapid_db_password"],
        config_dict["trapid_db"]["trapid_db_server"], config_dict["trapid_db"]["trapid_db_name"])
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=config_dict["experiment"]["exp_id"]))
    total_nts = float([record for record in cursor.fetchone()][0])
    db_conn.close()
    return (total_nts / 10e6) * 2


def run_infernal(z_value, config_dict):
    """Run infernal, return path of tabulated output file"""
    # Command-line to run
    cmd_str = "cmscan -Z {z_value} --cut_ga --rfam --nohmmonly --cpu {n_cpu} --tblout {tblout_out_file} --fmt 2 --clanin {rfam_clans_file} {rfam_cm_file} {fasta_file} > {cmscan_out_file}"
    # Get some configuration values for later use (those used more than once)
    tmp_exp_dir = config_dict["experiment"]["tmp_exp_dir"]
    exp_id = config_dict["experiment"]["exp_id"]
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


def filter_out_overlaps(tblout_file, config_dict):
    """Filter out overlapping matches in Infernal tabulated output file. Return name of filtered output"""
    tmp_exp_dir = config_dict["experiment"]["tmp_exp_dir"]
    exp_id = config_dict["experiment"]["exp_id"]
    tblout_filtered_file = os.path.join(tmp_exp_dir, "infernal_%s.filtered.tblout" % exp_id)
    to_keep = []
    with open(tblout_file, "r") as in_file:
        for line in in_file:
            if " = " not in line:
                to_keep.append(line)
    with open(tblout_filtered_file, "w") as out_file:
        out_file.write(''.join(to_keep))
    return tblout_filtered_file


def flag_rna_genes(filtered_tblout_file, config_dict):
    """Flag a set of transcripts as RNA genes in TRAPID's database"""
    sys.stderr.write('[Message] Flag RNA genes in `transcripts` table. \n')
    query_str = "UPDATE `transcripts` SET `is_rna_gene`=1 WHERE `experiment_id`='{exp_id}' and transcript_id='{transcript_id}';"
    db_conn = common.db_connect(config_dict["trapid_db"]["trapid_db_username"], config_dict["trapid_db"]["trapid_db_password"],
        config_dict["trapid_db"]["trapid_db_server"], config_dict["trapid_db"]["trapid_db_name"])
    cursor = db_conn.cursor()
    with open(filtered_tblout_file, "r") as in_file:
        for line in in_file:
            if not line.startswith("#"):
                splitted = line.strip().split()
                formatted_query = query_str.format(exp_id=config_dict["experiment"]["exp_id"], transcript_id=splitted[3])
                print formatted_query
                cursor.execute(formatted_query)
                print line.strip()
    db_conn.commit()
    db_conn.close()


# TODO: clean up transcripts table
def main(config_dict):
    """Main function: run Infernal, filter results and flag RNA genes in TRAPID db. """
    create_infernal_files(config_dict=config_dict)
    run_cmpress(config_dict=config_dict)
    total_m_nts = get_cmscan_z_value(config_dict=config_dict)
    infernal_tblout = run_infernal(z_value=total_m_nts, config_dict=config_dict)
    # Parse Infernal output to retrieve best non-ovelrapping matches
    infernal_tblout_filtered = filter_out_overlaps(tblout_file=infernal_tblout, config_dict=config_dict)
    flag_rna_genes(filtered_tblout_file=infernal_tblout_filtered, config_dict=config_dict)
    # That's it for now... More soon!


if __name__ == '__main__':
    cmd_args = cmd_parser.parse_args()
    sys.stderr.write('[Message] Starting ncRNA annotation procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))
    config = load_config(cmd_args.ini_file_initial)
    db_connection = common.db_connect(config["trapid_db"]["trapid_db_username"], config["trapid_db"]["trapid_db_password"],
        config["trapid_db"]["trapid_db_server"], config["trapid_db"]["trapid_db_name"])
    common.update_experiment_log(experiment_id=config["experiment"]["exp_id"], action='start_nc_rna_search', params='Infernal', depth=2, db_conn=db_connection)
    db_connection.close()
    main(config_dict=config)
    db_connection = common.db_connect(config["trapid_db"]["trapid_db_username"], config["trapid_db"]["trapid_db_password"],
        config["trapid_db"]["trapid_db_server"], config["trapid_db"]["trapid_db_name"])
    common.update_experiment_log(experiment_id=config["experiment"]["exp_id"], action='stop_nc_rna_search', params='Infernal', depth=2, db_conn=db_connection)
    db_connection.close()
    sys.stderr.write('[Message] Finished ncRNA annotation procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))
