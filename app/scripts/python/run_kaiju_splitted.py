#!/usr/bin/python

'''
Run kaiju, merge results, stor results in db, generate visualizations.
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

# Constants
KAIJU_BIN_PATH = "kaiju"  # "/group/transreg/frbuc/tax_binning_benchmark/kaiju/bin/kaiju"
KAIJU_TO_KRONA_PATH = "kaiju2krona"
# KT_IMPORT_TEXT_PATH = "/www/blastdb/biocomp/moderated/trapid_02/kaiju_files/tools/krona/bin/ktImportText"
TIMESTAMP = time.strftime('%Y_%m_%d_%H%M%S')  # Get timestamp (for default output directory naming)

### Parse command-line arguments
cmd_parser = argparse.ArgumentParser(
    description='''Run spltited kaiju, merge results, put results in database and produce everything needed by visualizations. ''',
    formatter_class=argparse.ArgumentDefaultsHelpFormatter)
# Positional arguments
cmd_parser.add_argument('exp_id', type=int,
                        help='Experiment ID')
cmd_parser.add_argument('nodes_dmp_file',
                        help='Path to the `nodes.dmp` file. The same file should be used for running kaiju and merging results. ')
cmd_parser.add_argument('names_dmp_file',
                        help='Path to the `names.dmp` file. ')
cmd_parser.add_argument('splitted_db_dir',
                        help='Path to splitted db files (can be `ls`-like expression). If a directory is given, will \
                        consider all the `fmi` files in that directory. ')
cmd_parser.add_argument('input_file', type=str,
                        help='Input file (transcriptome or reads). ')
# Optional arguments
cmd_parser.add_argument('-o', '--output_dir', dest='output_dir', type=str,
                        help='Directory name for the output files of the splitted kaiju runs. ',
                        default='_'.join(["kaiju_splitted_results", TIMESTAMP]))
cmd_parser.add_argument('-s', '--output_script', dest='output_script', type=str,
                        help='Filename for the output shell script. If none provided, will output to STDOUT.',
                        default=None)
cmd_parser.add_argument('-kp', '--kaiju_parameters', dest='kaiju_parameters', type=str,
                        help='A string of extra parameters to append to the kaiju command, separated with blank spaces. ',
                        default=None, nargs='+')

# Parse arguments. Move to main?
cmd_args = cmd_parser.parse_args()
# sys.stderr.write(str(cmd_args)+'\n')  # Debug


### Functions
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
        kaiju_str = "{0} -t {1} -i {2} -f {3} -o {4} {5}".format(KAIJU_BIN_PATH, nodes_dmp_file, input_file, db_file, os.path.join(output_dir, input_basename+"_VS_"+kaiju_output+".out"), ' '.join(kaiju_parameters))
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


def db_connect(username="trapid_website", password="@Z%28ZwABf5pZ3jMUz", host="psbsql01.psb.ugent.be", db_name="db_trapid_02"):
    """Connect to database. Return a database connection. """
    try:
        db_connection = MS.connect(host=host,
            user=username,
            passwd=password,
            db=db_name)
    except:
        sys.stderr.write("[Error] Impossible to connect to the database. Check host/username/password (see error message below)\n")
        raise
    return db_connection


# TODO: insert data chunk by chunk for speed increase.
def kaiju_output_to_db(exp_id, kaiju_output_file, db_connection, chunk_size=2000):
    """Simple function to parse kaiju's result to fill the `transcripts_tax` table for the current experiment. """
    cursor = db_connection.cursor()
    sys.stderr.write("[Message] Insert kaiju results in `transcripts_tax`.\n")
    with open(kaiju_output_file, 'r') as in_file:
        for line in in_file:
            splitted = line.strip().split('\t')
            transcript_id = splitted[1]
            tax_id = 0 if splitted[0]=='U' else int(splitted[2])  # If unclassified, tax_id is set to 0.
            # A string summarizing kaiju results
            # Works because we use verbose output.
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

            sql_insert="insert into transcripts_tax(experiment_id, transcript_id, txid, tax_results) values({exp_id}, '{transcript_id}', {tax_id}, '{kaiju_str}');".format(
                exp_id=str(exp_id),
                transcript_id=transcript_id,
                tax_id=str(tax_id),
                kaiju_str=kaiju_str
            )
            cursor.execute(sql_insert)
    db_connection.commit()
    # db_connection.close()


### Script execution

if __name__ == '__main__':
    sys.stderr.write('[Message] Starting kaiju procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))
    # Update experiment log
    db_connection = db_connect()
    common.update_experiment_log(experiment_id=cmd_args.exp_id, action='start_tax_binning', params='kaiju_mem', depth=2, db_conn=db_connection)
    # Create and run shell script that will run kaiju.
    kaiju_splitted_script = create_shell_script(db_file_list=get_splitted_db_files(file_path=cmd_args.splitted_db_dir),
        output_dir=os.path.join(cmd_args.output_dir, "splitted_results"),
        output_script_name=cmd_args.output_script,
        kaiju_parameters=cmd_args.kaiju_parameters,
        nodes_dmp_file=cmd_args.nodes_dmp_file,
        input_file=cmd_args.input_file)
    # Impossible to qsub directly from the webcluster...
    # qsub_and_wait(script_name=kaiju_splitted_script, n_cores=1, mem_per_core=8)
    os.chmod(kaiju_splitted_script, 0755)
    job = subprocess.Popen(kaiju_splitted_script)
    job.communicate()
    # Merge kaiju results
    merge_kaiju_splitted_results.main(kaiju_outdir=os.path.join(cmd_args.output_dir, "splitted_results"), nodes_tax_file=cmd_args.nodes_dmp_file, output_file=os.path.abspath(os.path.join(cmd_args.output_dir, "kaiju_merged.out")))
    # Process output file to generate graphical outputs
    KAIJU_DATA_DIR = os.path.abspath(cmd_args.output_dir)
    DATA_DICT = {
        'names_dmp': cmd_args.names_dmp_file,
        'nodes_dmp': cmd_args.nodes_dmp_file,
        'kaiju_output': os.path.join(KAIJU_DATA_DIR, "kaiju_merged.out"),
        # Domain and phylum composition
        'domain_comp': os.path.join(KAIJU_DATA_DIR, 'top_tax.domain.tsv'),
        'phylum_comp': os.path.join(KAIJU_DATA_DIR, 'top_tax.phylum.tsv'),
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
            nodes_tax_file=DATA_DICT['nodes_dmp'])
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
    except Exception as e:
        print(e)
        sys.stderr.write("[Error] Unable to produce Domain/phylum summaries. \n")
    # Now, let's store results in the TRAPID database
    kaiju_output_to_db(exp_id=cmd_args.exp_id, kaiju_output_file=DATA_DICT['kaiju_output'], db_connection=db_connection)
    # Also update experiment log
    common.update_experiment_log(experiment_id=cmd_args.exp_id, action='stop_tax_binning', params='kaiju_mem', depth=2, db_conn=db_connection)
    db_connection.close()
    sys.stderr.write('[Message] Finished kaiju procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))
