#!/usr/bin/python

'''
Run kaiju, merge results, store results in DB, generate visualizations.
'''

# Import modules
import argparse
import sys
import os
import time
import glob
import subprocess
import json
import MySQLdb as MS
import merge_kaiju_splitted_results
import kaiju_viz
import common
from ConfigParser import ConfigParser


# Constants
KAIJU_BIN_PATH = "kaiju"  # "/group/transreg/frbuc/tax_binning_benchmark/kaiju/bin/kaiju"
TIMESTAMP = time.strftime('%Y_%m_%d_%H%M%S')  # Get timestamp (for default output directory naming)


### Parse command-line arguments
cmd_parser = argparse.ArgumentParser(
    description='''Run spltited kaiju, merge results, put results in database and produce everything needed by visualizations. ''',
    formatter_class=argparse.ArgumentDefaultsHelpFormatter)
# Positional arguments
cmd_parser.add_argument('ini_file_initial', type=str,
                        help='Initial processing configuration file (generated upon initial processing start)')


### Functions
def load_config(ini_file_initial):
    """Read initial processing configuration file and check if all needed sections are there. Return it as dictionary. """
    config = ConfigParser()
    config.read(ini_file_initial)
    config_dict = {section: dict(config.items(section)) for section in config.sections()}
    config_sections = set(config_dict.keys())
    needed_sections = {"tax_binning", "trapid_db", "experiment"}
    if len(needed_sections & config_sections) < len(needed_sections):
        missing_sections = needed_sections - config_sections
        sys.stderr.write("[Error] Not all required sections were found in the INI file ('%s')\n" % ini_file_initial)
        sys.stderr.write("[Error] Missing section(s): %s\n" % ", ".join(list(missing_sections)))
        sys.exit(1)
    return config_dict


def get_splitted_db_files(file_path):
    '''Get all splitted db files from `file_path` and return them as list (`file_list`). '''
    if '*' not in file_path:
        file_list = glob.glob(os.path.join(file_path, '*.fmi'))
    else:
        file_list = glob.glob(file_path)
    sys.stderr.write('[Message] File list to consider as kaiju DBs: %s.\n' % ', '.join([os.path.basename(f) for f in file_list]))
    return file_list


# TODO: include checks for parameters, to see if everything is correct
def create_shell_script(db_file_list, output_dir, output_script_name, kaiju_parameters, nodes_dmp_file, input_file):
    '''Create output shell script, ready to qsub. If no output script name provided, will print to STDOUT. '''
    if os.path.exists(output_dir):
        sys.stderr.write('[Warning] Output directory already exists. You may overwrite previous results! \n')
    shell_header_lines = '#!/bin/bash\n\nmodule load gcc\n\nmkdir -p {0}\n\n'.format(output_dir)
    input_basename = os.path.splitext(os.path.basename(input_file))[0]
    kaiju_cmds = []
    for db_file in sorted(db_file_list):
        kaiju_output = os.path.splitext(os.path.basename(db_file))[0]
        kaiju_str = "{0} -t {1} -i {2} -f {3} -o {4} {5}".format(KAIJU_BIN_PATH, nodes_dmp_file, input_file, db_file, os.path.join(output_dir, input_basename+"_VS_"+kaiju_output+".out"), kaiju_parameters)
        kaiju_cmds.append(kaiju_str)
    if not output_script_name:
        sys.stdout.write(shell_header_lines)
        sys.stdout.write('\n'.join(kaiju_cmds)+'\n')
    else:
        with open(output_script_name, 'w') as out_file:
            out_file.write(shell_header_lines)
            out_file.write('\n'.join(kaiju_cmds)+'\n')
    return output_script_name


def qsub_and_wait(script_name, n_cores, mem_per_core, extra_parameters=''):
    '''Qsub a script and wait for it to finish '''
    qsub_cmd = ["qsub", "-wd",
        os.path.abspath(os.path.dirname(script_name)), "-sync", "y",
        # "-pe", "serial", str(n_cores),
        "-l",
        "mem_free="+str(n_cores*mem_per_core)+"G,h_vmem="+str(mem_per_core)+"G",
        os.path.abspath(script_name),
        "-N", os.path.splitext(os.path.basename(script_name))[0]]
    print ' '.join(qsub_cmd)  # Debug
    job = subprocess.Popen(qsub_cmd)
    job.communicate()
    time.sleep(3)  # To prevent the lag between job completion and appearance of output files


def clear_db_content(exp_id, db_connection):
    """Cleanup exepriment's taxonomic binning results, stored in the `transcript_tax` table. """
    sys.stderr.write("[Message] Cleanup `transcripts_tax` for experiment \'%s\'.\n" % str(exp_id))
    cursor = db_connection.cursor()
    delete_query = "DELETE FROM transcripts_tax WHERE experiment_id = \'{exp_id}\';"
    cursor.execute(delete_query.format(exp_id=str(exp_id)))
    db_connection.commit()
    cursor.close()


# TODO: insert data chunk by chunk for speed increase?
def kaiju_output_to_db(exp_id, kaiju_output_file, db_connection, chunk_size=2000):
    """Simple function to parse kaiju's result to fill the `transcripts_tax` table for the current experiment. """
    sys.stderr.write("[Message] Insert kaiju results in `transcripts_tax`.\n")
    insert_query = "INSERT INTO transcripts_tax(experiment_id, transcript_id, txid, tax_results) VALUES ({exp_id}, '{transcript_id}', {tax_id}, '{kaiju_str}');"
    cursor = db_connection.cursor()
    with open(kaiju_output_file, 'r') as in_file:
        for line in in_file:
            splitted = line.strip().split('\t')
            transcript_id = splitted[1]
            tax_id = 0 if splitted[0]=='U' else int(splitted[2])  # If unclassified, tax_id is set to 0.
            # Create a string summarizing current kaiju results (works because we use verbose output).
            # kaiju_str = '' if splitted[0]=='U' else "score={score};match_tax={taxs};match_seq_ids={seq_ids}".format(
            #     score=str(splitted[3]),
            #     taxs=splitted[4],
            #     seq_ids=splitted[5]
            # )
            # Note: we replaced the old output in DB for the timebeing (when too many matches were found, fields were absurdly long...)
            kaiju_str = '' if splitted[0]=='U' else "score={score};match_tax={taxs};match_seq_ids={seq_ids}".format(
                score=str(splitted[3]),
                taxs=str(len(splitted[4])),
                seq_ids=str(len(splitted[5]))
            )
            # Insert current results in `transcript_tax`
            insert_kaiju_result = insert_query.format(exp_id=str(exp_id), transcript_id=transcript_id,
                                                      tax_id=str(tax_id), kaiju_str=kaiju_str)
            cursor.execute(insert_kaiju_result)
    db_connection.commit()
    cursor.close()


### Script execution
if __name__ == '__main__':
    sys.stderr.write('[Message] Starting kaiju procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))
    cmd_args = cmd_parser.parse_args()
    # sys.stderr.write(str(cmd_args)+'\n')  # Debug
    # Read experiment's initial processing configuration file
    config = load_config(cmd_args.ini_file_initial)
    # A list containing all needed parameters for `common.db_connect()`
    trapid_db_data = [config['trapid_db']['trapid_db_username'], config['trapid_db']['trapid_db_password'],
                      config['trapid_db']['trapid_db_server'], config['trapid_db']['trapid_db_name']]
    exp_id = config['experiment']['exp_id']
    # Input / output
    input_file = os.path.join(config['experiment']['tmp_exp_dir'], "transcripts_%s.fasta" % exp_id)
    output_dir = os.path.join(config['experiment']['tmp_exp_dir'], "kaiju")
    output_script = os.path.join(config['experiment']['tmp_exp_dir'], "run_kaiju_splitted.sh")
    # Paths to files needed for tax. binning AND post-processing
    splitted_db_dir = config['tax_binning']['splitted_db_dir']
    kaiju_parameters = config['tax_binning']['kaiju_parameters']
    names_dmp_file = config['tax_binning']['names_dmp_file']
    nodes_dmp_file = config['tax_binning']['nodes_dmp_file']
    kt_import_text_path = config['tax_binning']['kt_import_text_path']
    # Update experiment log
    db_connection = common.db_connect(*trapid_db_data)
    common.update_experiment_log(experiment_id=exp_id, action='start_tax_binning', params='kaiju_mem', depth=2, db_conn=db_connection)
    db_connection.close()
    # Create and run shell script that will run kaiju.
    kaiju_splitted_script = create_shell_script(db_file_list=get_splitted_db_files(file_path=splitted_db_dir),
        output_dir=os.path.join(output_dir, "splitted_results"),
        output_script_name=output_script,
        kaiju_parameters=kaiju_parameters,
        nodes_dmp_file=nodes_dmp_file,
        input_file=input_file)
    # Impossible to qsub directly from the webcluster...
    # qsub_and_wait(script_name=kaiju_splitted_script, n_cores=1, mem_per_core=8)
    os.chmod(kaiju_splitted_script, 0755)
    # job = subprocess.Popen(kaiju_splitted_script)
    # job.communicate()
    # Merge kaiju results
    merge_kaiju_splitted_results.main(kaiju_outdir=os.path.join(output_dir, "splitted_results"), nodes_tax_file=nodes_dmp_file, output_file=os.path.abspath(os.path.join(output_dir, "kaiju_merged.out")))
    # Process output file to generate graphical outputs
    KAIJU_DATA_DIR = os.path.abspath(output_dir)
    DATA_DICT = {
        'names_dmp': names_dmp_file,
        'nodes_dmp': nodes_dmp_file,
        'kaiju_output': os.path.join(KAIJU_DATA_DIR, "kaiju_merged.out"),
        # Domain, phylum, order, genus compositions
        'domain_comp': os.path.join(KAIJU_DATA_DIR, 'top_tax.domain.tsv'),
        'phylum_comp': os.path.join(KAIJU_DATA_DIR, 'top_tax.phylum.tsv'),
        'order_comp': os.path.join(KAIJU_DATA_DIR, 'top_tax.order.tsv'),
        'genus_comp': os.path.join(KAIJU_DATA_DIR, 'top_tax.genus.tsv'),
        # Krona
        'kaiju_tsv_output': os.path.join(KAIJU_DATA_DIR, 'kaiju_merged.to_krona.out'),
        'krona_html_file': os.path.join(KAIJU_DATA_DIR, 'kaiju_merged.krona.html'),
        # Treeview
        'treeview_json': os.path.join(KAIJU_DATA_DIR, 'kaiju_merged.to_treeview.json')
    }
    # Generate everything!
    # 1. Krona
    try:
        kaiju_viz.kaiju_to_krona(
            kaiju_output_file=DATA_DICT['kaiju_output'],
            kaiju_tsv_output_file=DATA_DICT['kaiju_tsv_output'],
            krona_html_file=DATA_DICT['krona_html_file'],
            names_tax_file=DATA_DICT['names_dmp'],
            nodes_tax_file=DATA_DICT['nodes_dmp'],
            kt_import_text_path=kt_import_text_path)
    except Exception as e:
        print(e)
        sys.stderr.write("[Error] Unable to produce Krona output. \n")
    # 2. Treeview JSON data
    try:
        kaiju_viz.kaiju_to_treeview(kaiju_output_file=DATA_DICT['kaiju_output'],
            names_tax_file=DATA_DICT['names_dmp'],
            nodes_tax_file=DATA_DICT['nodes_dmp'],
            treeview_json_file=DATA_DICT['treeview_json'])
    except Exception as e:
        print(e)
        sys.stderr.write("[Error] Unable to produce Treeview JSON file. \n")
    # 3. Pie/barcharts summaries data
    try:
        kaiju_viz.kaiju_to_tax_piechart_data(kaiju_tsv_output_file=DATA_DICT['kaiju_tsv_output'],
            names_tax_file=DATA_DICT['names_dmp'],
            nodes_tax_file=DATA_DICT['nodes_dmp'],
            output_data_table=DATA_DICT['domain_comp'],
            rank_limit='superkingdom')
        kaiju_viz.kaiju_to_tax_piechart_data(kaiju_tsv_output_file=DATA_DICT['kaiju_tsv_output'],
            names_tax_file=DATA_DICT['names_dmp'],
            nodes_tax_file=DATA_DICT['nodes_dmp'],
            output_data_table=DATA_DICT['phylum_comp'],
            rank_limit='phylum')
        kaiju_viz.kaiju_to_tax_piechart_data(kaiju_tsv_output_file=DATA_DICT['kaiju_tsv_output'],
            names_tax_file=DATA_DICT['names_dmp'],
            nodes_tax_file=DATA_DICT['nodes_dmp'],
            output_data_table=DATA_DICT['order_comp'],
            rank_limit='order')
        kaiju_viz.kaiju_to_tax_piechart_data(kaiju_tsv_output_file=DATA_DICT['kaiju_tsv_output'],
            names_tax_file=DATA_DICT['names_dmp'],
            nodes_tax_file=DATA_DICT['nodes_dmp'],
            output_data_table=DATA_DICT['genus_comp'],
            rank_limit='genus')
    except Exception as e:
        print(e)
        sys.stderr.write("[Error] Unable to produce Domain/phylum summaries. \n")
    # Now, let's cleanup existing results and store current results in the TRAPID database
    db_connection = common.db_connect(*trapid_db_data)
    clear_db_content(exp_id=exp_id, db_connection=db_connection)
    db_connection.close()
    db_connection = common.db_connect(*trapid_db_data)
    kaiju_output_to_db(exp_id=exp_id, kaiju_output_file=DATA_DICT['kaiju_output'], db_connection=db_connection)
    db_connection.close()
    # Also update experiment log
    db_connection = common.db_connect(*trapid_db_data)
    common.update_experiment_log(experiment_id=exp_id, action='stop_tax_binning', params='kaiju_mem', depth=2, db_conn=db_connection)
    db_connection.close()
    sys.stderr.write('[Message] Finished kaiju procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))
