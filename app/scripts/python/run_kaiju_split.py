"""
A script to perform the taxonomic classification step of TRAPID's initial processing, that does the following:
 * Run Kaiju using a split index
 * Merge results from all splits, replacing matching taxa by their LCA if necessary
 * Store the results in TRAPID's DB
 * Generate required files for visualization within TRAPID.
"""

# Usage: python run_kaiju_split.py <initial_processing.ini>

import argparse
import glob
import os
import subprocess
import sys
import time

import common
import kaiju_viz
import merge_kaiju_split_results

KAIJU_BIN_PATH = "kaiju"
TIMESTAMP = time.strftime('%Y_%m_%d_%H%M%S')  # Get timestamp (for default output directory naming)


def parse_arguments():
    """Parse command-line arguments.

    :return: parsed arguments (Namespace object).

    """
    cmd_parser = argparse.ArgumentParser(
        description='Run split kaiju, merge/store results in database, & produce everything needed for visualizations.',
        formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    cmd_parser.add_argument('ini_file_initial', type=str,
                            help='Initial processing configuration file (generated upon initial processing start)')
    cmd_args = cmd_parser.parse_args()
    return cmd_args


def get_split_db_files(file_path):
    """Get all split db files from `file_path` and return them as list (`file_list`).

    :param file_path: file path or glob pattern of Kaiju split db files. `*.fmi` is appended if it is a directory.
    :return: list of Kaiju split db files

    """
    if os.path.isdir(file_path):
        file_list = glob.glob(os.path.join(file_path, '*.fmi'))
    else:
        file_list = glob.glob(file_path)
    sys.stderr.write('[Message] File list to consider as Kaiju DBs: %s.\n' % ', '.join([os.path.basename(f) for f in file_list]))
    return file_list


# TODO: include checks for parameters, to see if everything is correct?
def create_shell_script(db_file_list, output_dir, output_script_name, kaiju_parameters, nodes_dmp_file, input_file):
    """Create output shell script, ready to run or qsub. If no output script name is provided, will print to STDOUT.

    :param db_file_list: list of split Kaiju db files
    :param output_dir: output directory for Kaiju output files for individual splits
    :param output_script_name: path/name of the shell script to run Kaiju
    :param kaiju_parameters: a string of parameters to append to Kaiju's command-line
    :param nodes_dmp_file: path of `nodes.dmp` NCBI taxonomy file
    :param input_file: path of input FASTA file
    :return: path/name of the shell script to run Kaiju

    """
    if os.path.exists(output_dir):
        sys.stderr.write('[Warning] Output directory already exists. You may overwrite previous results! \n')
    # shell_header_lines = '#!/bin/bash\n\nmodule load gcc\n\nmkdir -p {0}\n\n'.format(output_dir)
    shell_header_lines = '#!/bin/bash\n\nmkdir -p {0}\n\n'.format(output_dir)
    input_basename = os.path.splitext(os.path.basename(input_file))[0]
    kaiju_cmds = []
    kaiju_base_str = "{0} -t {1} -i {2} -f {3} -o {4} {5}"
    for db_file in sorted(db_file_list):
        kaiju_output_db = os.path.splitext(os.path.basename(db_file))[0]  # Part of output name based on DB file
        kaiju_output = os.path.join(output_dir, "%s_VS_%s.out" % (input_basename, kaiju_output_db))
        kaiju_cmds.append(
            kaiju_base_str.format(KAIJU_BIN_PATH, nodes_dmp_file, input_file, db_file, kaiju_output, kaiju_parameters)
        )
    if not output_script_name:
        sys.stdout.write(shell_header_lines)
        sys.stdout.write('\n'.join(kaiju_cmds)+'\n')
    else:
        with open(output_script_name, 'w') as out_file:
            out_file.write(shell_header_lines)
            out_file.write('\n'.join(kaiju_cmds)+'\n')
    return output_script_name


def clear_db_content(exp_id, db_connection):
    """Cleanup exepriment's taxonomic classification results stored in the `transcripts_tax` table.

    :param exp_id: TRAPID experiment id
    :param db_connection: TRAPID db connection as returned by common.db_connect()

    """
    sys.stderr.write("[Message] Cleanup `transcripts_tax` for experiment \'%s\'.\n" % str(exp_id))
    cursor = db_connection.cursor()
    delete_query = "DELETE FROM transcripts_tax WHERE experiment_id = \'{exp_id}\';"
    cursor.execute(delete_query.format(exp_id=str(exp_id)))
    db_connection.commit()
    cursor.close()


# TODO: insert data chunk by chunk for speed increase?
def kaiju_output_to_db(exp_id, kaiju_output_file, db_connection, chunk_size=2000):
    """Simple function to parse Kaiju results and fill the `transcripts_tax` table for the current experiment.

    :param exp_id: TRAPID experiment id
    :param kaiju_output_file: path of (merged) Kaiju output file
    :param db_connection: TRAPID db connection as returned by common.db_connect()
    :param chunk_size: number of records to insert at once (executemany) -- unused as of now

    """
    sys.stderr.write("[Message] Insert Kaiju results in `transcripts_tax`.\n")
    insert_query = "INSERT INTO transcripts_tax(experiment_id, transcript_id, txid, tax_results) VALUES ({exp_id}, '{transcript_id}', {tax_id}, COMPRESS('{kaiju_str}'));"
    cursor = db_connection.cursor()
    with open(kaiju_output_file, 'r') as in_file:
        for line in in_file:
            fields = line.strip().split('\t')
            transcript_id = fields[1]
            tax_id = int(fields[2])  # If unclassified, tax_id is set to 0.
            # Note: we replaced the old output in DB , since storing the raw Kaiju output was resulting in absurdly
            # long fields when too many matches (or very long matching sequences) were found.
            # Create a string summarizing current Kaiju results (works because we use verbose output).
            # kaiju_str = '' if fields[0]=='U' else "score={score};match_tax={taxs};match_seq_ids={seq_ids}".format(
            #     score=str(fields[3]),
            #     taxs=fields[4],
            #     seq_ids=fields[5]
            # )
            kaiju_str = '' if fields[0] == 'U' else "score={score};match_tax={taxs};match_seq_ids={seq_ids}".format(
                score=str(fields[3]),
                taxs=str(len([elmt for elmt in fields[4].split(",") if elmt])),
                seq_ids=str(len([elmt for elmt in fields[5].split(",") if elmt]))
            )
            # Insert current results in `transcript_tax`
            insert_kaiju_result = insert_query.format(exp_id=str(exp_id), transcript_id=transcript_id,
                                                      tax_id=str(tax_id), kaiju_str=kaiju_str)
            cursor.execute(insert_kaiju_result)
    db_connection.commit()
    cursor.close()


# This function is not used since this script does not submit the Kaiju job to the cluster
# def qsub_and_wait(script_name, n_cores, mem_per_core, extra_parameters=''):
#     '''Qsub a script and wait for it to finish '''
#     qsub_cmd = ["qsub", "-wd",
#         os.path.abspath(os.path.dirname(script_name)), "-sync", "y",
#         # "-pe", "serial", str(n_cores),
#         "-l",
#         "mem_free="+str(n_cores*mem_per_core)+"G,h_vmem="+str(mem_per_core)+"G",
#         os.path.abspath(script_name),
#         "-N", os.path.splitext(os.path.basename(script_name))[0]]
#     # print ' '.join(qsub_cmd)  # Debug
#     job = subprocess.Popen(qsub_cmd)
#     job.communicate()
#     time.sleep(3)  # To prevent the lag between job completion and appearance of output files


def main():
    sys.stderr.write('[Message] Starting Kaiju procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))
    cmd_args = parse_arguments()
    # sys.stderr.write(str(cmd_args)+'\n')  # Debug
    # Read experiment's initial processing configuration file
    config = common.load_config(cmd_args.ini_file_initial, {"tax_binning", "trapid_db", "experiment"})
    # A list containing all needed parameters for `common.db_connect()`
    trapid_db_data = common.get_db_connection_data(config, 'trapid_db')
    exp_id = config['experiment']['exp_id']
    # Input / output
    input_file = os.path.join(config['experiment']['tmp_exp_dir'], "transcripts_%s.fasta" % exp_id)
    output_dir = os.path.join(config['experiment']['tmp_exp_dir'], "kaiju")
    output_script = os.path.join(config['experiment']['tmp_exp_dir'], "run_kaiju_split.sh")
    # Paths to files needed for taxonomic classification and post-processing
    split_db_dir = config['tax_binning']['splitted_db_dir']
    kaiju_parameters = config['tax_binning']['kaiju_parameters']
    names_dmp_file = config['tax_binning']['names_dmp_file']
    nodes_dmp_file = config['tax_binning']['nodes_dmp_file']
    # Update experiment log
    db_connection = common.db_connect(*trapid_db_data)
    common.update_experiment_log(exp_id, 'start_tax_classification', 'kaiju_mem', 2, db_connection)
    db_connection.close()
    # Create and run shell script that will run Kaiju.
    kaiju_split_script = create_shell_script(
        get_split_db_files(split_db_dir), os.path.join(output_dir, "split_results"), output_script,
        kaiju_parameters, nodes_dmp_file, input_file)
    # Impossible to qsub directly from the webcluster... So run Kaiju shell script from here
    # qsub_and_wait(script_name=kaiju_split_script, n_cores=1, mem_per_core=8)
    os.chmod(kaiju_split_script, 0o755)
    job = subprocess.Popen(kaiju_split_script)
    job.communicate()
    # Merge Kaiju results
    merge_kaiju_split_results.merge_results(os.path.join(output_dir, "split_results"), nodes_dmp_file,
                                            os.path.abspath(os.path.join(output_dir, "kaiju_merged.out")))
    # Process output file to generate graphical outputs
    kaiju_data_dir = os.path.abspath(output_dir)
    data_dict = {
        'names_dmp': names_dmp_file,
        'nodes_dmp': nodes_dmp_file,
        'kaiju_output': os.path.join(kaiju_data_dir, "kaiju_merged.out"),
        # Domain, phylum, order, genus composition files
        'domain_comp': os.path.join(kaiju_data_dir, 'top_tax.domain.tsv'),
        'phylum_comp': os.path.join(kaiju_data_dir, 'top_tax.phylum.tsv'),
        'order_comp': os.path.join(kaiju_data_dir, 'top_tax.order.tsv'),
        'genus_comp': os.path.join(kaiju_data_dir, 'top_tax.genus.tsv'),
        # Krona HTML
        'kaiju_tsv_output': os.path.join(kaiju_data_dir, 'kaiju_merged.to_krona.out'),
        'krona_html_file': os.path.join(kaiju_data_dir, 'kaiju_merged.krona.html'),
        # Treeview JSON
        'treeview_json': os.path.join(kaiju_data_dir, 'kaiju_merged.to_treeview.json')
    }
    # Generate all visualization output files
    # 1. Krona
    try:
        kaiju_viz.kaiju_to_krona(data_dict['kaiju_output'], data_dict['kaiju_tsv_output'], data_dict['krona_html_file'],
                                 data_dict['names_dmp'], data_dict['nodes_dmp'])
    except Exception as e:
        print(e)
        sys.stderr.write("[Error] Unable to produce Krona output. \n")
    # 2. Treeview JSON data
    try:
        kaiju_viz.kaiju_to_treeview(data_dict['kaiju_output'], data_dict['treeview_json'],
                                    data_dict['names_dmp'], data_dict['nodes_dmp'])
    except Exception as e:
        print(e)
        sys.stderr.write("[Error] Unable to produce Treeview JSON file. \n")
    # 3. Pie/barcharts summaries data
    try:
        # At superkingdom/domain level
        kaiju_viz.kaiju_to_domain_summary_data(data_dict['kaiju_tsv_output'], data_dict['domain_comp'],
                                               data_dict['names_dmp'], data_dict['nodes_dmp'])
        # At selected taxonomic ranks (iterating over a rank->rank composition file dictionary)
        tax_ranks = {'phylum': 'phylum_comp', 'order': 'order_comp', 'genus': 'genus_comp'}
        for rank, rank_comp in tax_ranks.items():
            kaiju_viz.kaiju_to_tax_summary_data(data_dict['kaiju_output'], data_dict[rank_comp],
                                                data_dict['names_dmp'], data_dict['nodes_dmp'], rank_limit=rank)
        # kaiju_viz.kaiju_to_tax_summary_data(
        #     kaiju_output_file=data_dict['kaiju_output'],
        #     names_tax_file=data_dict['names_dmp'],
        #     nodes_tax_file=data_dict['nodes_dmp'],
        #     output_data_table=data_dict['order_comp'],
        #     rank_limit='order')
        # kaiju_viz.kaiju_to_tax_summary_data(
        #     kaiju_output_file=data_dict['kaiju_output'],
        #     names_tax_file=data_dict['names_dmp'],
        #     nodes_tax_file=data_dict['nodes_dmp'],
        #     output_data_table=data_dict['genus_comp'],
        #     rank_limit='genus')
    except Exception as e:
        print(e)
        sys.stderr.write("[Error] Unable to produce domain/taxonomic rank summaries. \n")
    # Now, let's cleanup existing results and store current results in the TRAPID database
    db_connection = common.db_connect(*trapid_db_data)
    clear_db_content(exp_id, db_connection)
    db_connection.close()
    db_connection = common.db_connect(*trapid_db_data)
    kaiju_output_to_db(exp_id, data_dict['kaiju_output'], db_connection)
    db_connection.close()
    # Also update experiment log
    db_connection = common.db_connect(*trapid_db_data)
    common.update_experiment_log(exp_id, 'stop_tax_classification', 'kaiju_mem', 2, db_connection)
    db_connection.close()
    sys.stderr.write('[Message] Finished Kaiju procedure: %s\n'  % time.strftime('%Y/%m/%d %H:%M:%S'))


if __name__ == '__main__':
    main()
