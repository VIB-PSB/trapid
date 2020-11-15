"""
A collection of functions related to Kaiju results visualization
"""

import json
import os
import subprocess
import sys

KAIJU_TO_KRONA_PATH = "kaiju2krona"
KAIJU_TO_TABLE_PATH = "kaiju2table"
KT_IMPORT_TEXT_PATH = "ktImportText"


def get_tax_parents(nodes_tax_file, tax_ids):
    """Parse nodes.dmp file from NCBI taxonomy to get all parents of a list of tax ids (until the root).

    :param nodes_tax_file: `nodes.dmp` file from NCBI taxonomy (taxonomic tree)
    :param tax_ids: list of tax ids
    :return: <tax_id>:<parent_list> dictionary

    """
    # Read tax nodes
    tax_nodes = {}
    tax_parents = {}
    with open(nodes_tax_file, 'r') as nodes_tax:
        for line in nodes_tax:
            current_node = [field.strip() for field in line.split('|')]
            tax_nodes[current_node[0]] = current_node[1]
    # Get hierarchy to populate final dictionary
    for node in tax_ids:
        if node not in tax_nodes:
            tax_parents[node] = None
            sys.stderr.write('[Warning] Could not find %s in %s while parsing taxonomy hierarchy\n' % (node, nodes_tax_file))
        else:
            parent = tax_nodes[node]
            tax_parents[node] = []
            tax_parents[node].append(parent)
            while parent != tax_nodes[parent]:
                parent = tax_nodes[parent]
                tax_parents[node].append(parent)
    return tax_parents


def get_tax_ranks(nodes_tax_file, tax_ids):
    """Parse nodes.dmp file from NCBI taxonomy to get all taxonomic ranks for a list of tax ids.

    :param nodes_tax_file: `nodes.dmp` file from NCBI taxonomy (taxonomic tree)
    :param tax_ids: list of tax ids
    :return: <tax_id>:<taxonomic_rank> dictionary

    """
    tax_nodes = {}
    tax_ranks = {}
    with open(nodes_tax_file, 'r') as nodes_tax:
        for line in nodes_tax:
            current_node = [field.strip() for field in line.split('|')]
            tax_nodes[current_node[0]] = current_node[2]
    for tax_id in tax_ids:
        if tax_id not in tax_nodes:
            tax_ranks[tax_id] = None
            sys.stderr.write('[Warning] Could not find %s in %s while parsing taxonomic ranks\n' % (tax_id, nodes_tax_file))
        else:
            tax_ranks[tax_id] = tax_nodes[tax_id]
    return tax_ranks


def get_tax_names(names_tax_file, tax_ids):
    """Parse names.dmp file from NCBI taxonomy to get all common names of a list of tax ids.

    :param names_tax_file: `names.dmp` file from NCBI taxonomy (taxonomic names)
    :param tax_ids: list of tax ids
    :return: <tax_id>:<common_name> dictionary

    """
    tax_all_names = {}
    tax_names = {}
    with open(names_tax_file, 'r') as names_tax:
        for line in names_tax:
            current_rec = [field.strip() for field in line.split('|') if 'scientific name' in line]
            if current_rec:
                tax_all_names[current_rec[0]] = current_rec[1]
    for tax_id in tax_ids:
        if tax_id not in tax_all_names:
            tax_names[tax_id] = None
            sys.stderr.write('[Warning] Could not find %s in %s while parsing names\n' % (tax_id, names_tax_file))
        else:
            tax_names[tax_id] = tax_all_names[tax_id]
    return tax_names


def get_tax_rank_names(names_tax_file, nodes_tax_file, tax_rank):
    """Parse names.dmp and nodes.dmp from NCBI taxonomy to get common names corresponding to all organisms of a selected
    taxonomic rank.

    :param names_tax_file: `names.dmp` file from NCBI taxonomy (taxonomic names)
    :param nodes_tax_file: `nodes.dmp` file from NCBI taxonomy (taxonomic tree)
    :param tax_rank: taxonomic rank
    :return: list of common names for the taxonomic rank

    """
    tax_ids = set()
    tax_names = set()
    with open(nodes_tax_file, 'r') as nodes_tax:
        for line in nodes_tax:
            if '\t'+tax_rank in line:
                current_node = [field.strip() for field in line.split('|')]
                tax_ids.add(current_node[0])
    with open(names_tax_file, 'r') as names_tax:
        for line in names_tax:
            current_rec = [field.strip() for field in line.split('|') if 'scientific name' in line]
            if current_rec and current_rec[0] in tax_ids:
                tax_names.add(current_rec[1])
        if not tax_names:
            sys.stderr.write('[Warning] Could not find any names corresponding to taxonomic rank \'%s\'. \n' % tax_rank)
    # return sorted(list(tax_names))
    return tax_names


def kaiju_to_krona(kaiju_output_file, kaiju_tsv_output_file, krona_html_file, names_tax_file, nodes_tax_file,
                   kaiju_to_krona_path=KAIJU_TO_KRONA_PATH, kt_import_text_path=KT_IMPORT_TEXT_PATH):
    """Generate Krona HTML file for Kaiju results.

    :param kaiju_output_file: path of Kaiju output file
    :param kaiju_tsv_output_file: path of Kaiju 'taxon path' tabulated output file (used to generated Krona HTML)
    :param krona_html_file: path of output Krona HTML file
    :param names_tax_file: `names.dmp` file from NCBI taxonomy (taxonomic names)
    :param nodes_tax_file: `nodes.dmp` file from NCBI taxonomy (taxonomic tree)
    :param kaiju_to_krona_path: `kaiju2krona` command path
    :param kt_import_text_path: `ktImportText` (from KronaTools) command path

    """
    # 1. Generate tsv output file
    # `-u`: include unclassified sequences in output
    kaiju2krona = subprocess.Popen([kaiju_to_krona_path, "-i", kaiju_output_file, "-t", nodes_tax_file,
                                    "-n", names_tax_file, "-o", kaiju_tsv_output_file, "-u"],
                                   stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    sys.stderr.write("Generating kaiju tsv output file (`kaiju2krona`)... \n")
    kaiju2krona.communicate()
    exit_status = kaiju2krona.returncode
    sys.stderr.write("... kaiju2krona finished with code: " + str(exit_status) + '\n')
    if exit_status != 0:
        sys.exit(exit_status)
    # 2. Generate Krona HTML
    kt_import_text = subprocess.Popen([kt_import_text_path, kaiju_tsv_output_file, "-o", krona_html_file],
                                      stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    sys.stderr.write("Generating Krona HTML file... \n")
    kt_import_text.communicate()
    exit_status = kt_import_text.returncode
    sys.stderr.write("... ktImportText finished with code: " + str(exit_status) + '\n')
    if exit_status != 0:
        sys.exit(exit_status)


def kaiju_to_treeview(kaiju_output_file, treeview_json_file, names_tax_file, nodes_tax_file):
    """Process Kaiju output to create correctly-formatted file for use with Unipept's tree view.

    :param kaiju_output_file: path of Kaiju output file
    :param treeview_json_file: JSON file for use with Unipept's tree view visualization
    :param names_tax_file: `names.dmp` file from NCBI taxonomy (taxonomic names)
    :param nodes_tax_file: `nodes.dmp` file from NCBI taxonomy (taxonomic tree)

    """
    kaiju_dict = {}
    # We would need to count unclassified sequences too?
    # 1. Read Kaiju output and store results in a dict
    with open(kaiju_output_file, 'r') as kaiju_out:
        for line in kaiju_out:
            if line.startswith('C'):
                record = line.strip().split('\t')
                if record[2] not in kaiju_dict.keys():
                    kaiju_dict[record[2]] = {'count': 1, 'rank': '', 'name': '', 'parents': None}
                else:
                    kaiju_dict[record[2]]['count'] += 1
    kaiju_dict = {key:value for key, value in kaiju_dict.items()}
    # 2. Get the hierarchy of all tax ids of kaiju's output
    all_parents = get_tax_parents(nodes_tax_file=nodes_tax_file, tax_ids=kaiju_dict.keys())
    for tax_id in kaiju_dict.keys():
        if all_parents[tax_id] is None:
            # Get rid of unknown/incorrect tax ids
            kaiju_dict.pop(tax_id)
        else:
            kaiju_dict[tax_id]['parents'] = all_parents[tax_id]
    # 3. For each tax id appearing in the results (+parents), get rank/name
    all_tax_ids = []
    for tax_id in kaiju_dict.keys():
        all_tax_ids.append(tax_id)
        for parent in kaiju_dict[tax_id]['parents']:
            all_tax_ids.append(parent)
    all_tax_ids = list(set(all_tax_ids))
    all_names = get_tax_names(names_tax_file=names_tax_file, tax_ids=all_tax_ids)
    all_ranks = get_tax_ranks(nodes_tax_file=nodes_tax_file, tax_ids=all_tax_ids)
    # 4. Make data edible by the tree viewer
    treeview_json_data = to_treeview_dict(kaiju_data_dict=kaiju_dict, names_dict=all_names, ranks_dict=all_ranks)
    # 5. Create output file
    with open(treeview_json_file, 'w') as out_file:
        out_file.write(json.dumps(treeview_json_data, sort_keys=True))


def to_treeview_dict(kaiju_data_dict, names_dict, ranks_dict):
    """From Kaiju results and corresponding taxonomic names and ranks, format data correctly for use with Unipept's tree
    view (taxonomic classification hierarchy dictionary).

    Note: 'cellular organisms' (131567) is filtered out from results hierarchy.

    :param kaiju_data_dict: Kaiju result dictionary (as generated in kaiju_to_treeview())
    :param names_dict: taxonomic names dictionary
    :param ranks_dict: taxonomic ranks dictionary
    :return: correctly-formatted dictionary

    """
    cache = {}
    root = None
    for k, v in kaiju_data_dict.items():
        # print 'Starting to handle an item ... ('+str(k)+', parents: '+','.join(v['parents'])+')'
        # Remove 'cellular organisms' from results hierarchy.
        if '131567' in v['parents']:
            v['parents'].remove('131567')
        if k not in cache:
            cur = {'id': int(k),
                   'children': [],
                   'name': names_dict[k],
                   'data': {
                       'rank': ranks_dict[k],
                       'count': int(v['count']),
                       'self_count': int(v['count'])
                       }
                   }
            cache[k] = cur
            for par in v['parents']:
                if par in cache:
                    cache[par]['children'].append(cur)
                    break
                else:
                    cur = {'id': par,
                           'name': names_dict[par],
                           'children': [cur],
                           'data': {
                               'rank': ranks_dict[par]
                               }
                           }
                    if par in kaiju_data_dict.keys():
                        cur['data']['self_count'] = kaiju_data_dict[par]['count']
                    else:
                        cur['data']['self_count'] = 0
                    cur['data']['count'] = cur['data']['self_count']
                    # cur['data']['count'] = int(v['count'])+cur['data']['self_count']
                    cache[par] = cur
            if v['parents']:
                root = v['parents'][-1]
        # Append count values
        for par in v['parents']:
            cache[par]['data']['count'] += int(v['count'])
            # print 'Count added to ' + str(par)
    return cache[root]


def kaiju_to_domain_summary_data(kaiju_tsv_output_file, output_data_table, names_tax_file, nodes_tax_file, top_tax=10):
    """Create a TSV file that contains the necessary data for the pie/barcharts visualizations (i.e. overview of the
    domain-level composition of samples).

    :param kaiju_tsv_output_file: Kaiju 'taxon path' output file (output of `kaiju2krona`)
    :param output_data_table: output summary TSV file
    :param names_tax_file: `names.dmp` file from NCBI taxonomy (taxonomic names)
    :param nodes_tax_file: `nodes.dmp` file from NCBI taxonomy (taxonomic tree)
    :param top_tax: number of most represented taxa to incldue in the summary (rest collapsed as 'Other')

    """
    kaiju_data = []
    chart_data = {}
    unclass_str = 'Unclassified'
    rank_limit = 'superkingdom'
    sys.stderr.write("Generating %s summary... \n" % rank_limit)
    # 1. Get all taxon names for given rank_limit.
    tax_names = get_tax_rank_names(names_tax_file=names_tax_file, nodes_tax_file=nodes_tax_file, tax_rank=rank_limit)
    tax_names.add(unclass_str)
    # 2. Read and handle kaiju2krona's output file.
    with open(kaiju_tsv_output_file, 'r') as in_file:
        for line in in_file:
            current_rec = line.strip().split('\t')
            for tax in current_rec[1::]:
                if tax in tax_names:
                    # print 'there there '+tax
                    current_elmt = [int(current_rec[0]), current_rec[current_rec.index(tax)]]
                    kaiju_data.append(current_elmt)
                    break
    # 3. Sum results that are not equivalent
    for result in kaiju_data:
        if result[1] in chart_data.keys():
            chart_data[result[1]] += int(result[0])
        else:
            chart_data[result[1]] = int(result[0])
    # 4. Sort/filter chart data and return it
    with open(output_data_table, 'w') as out_file:
        top = sorted([a for a in chart_data.iteritems()], key=lambda tup: tup[1], reverse=True)[0:top_tax]
        for e in top:
            out_file.write(e[0]+'\t'+str(e[1])+'\n')
        if len(chart_data) > top_tax:
            other_sum = sum([a[1] for a in sorted([a for a in chart_data.iteritems()], key=lambda tup: tup[1], reverse=True)[top_tax:]])
            out_file.write('Other'+'\t'+str(other_sum)+'\n')


# Note: we cannot use this function for superkingdom/domain summary, because `phylum` is the most general taxonomic rank
# accepted by `kaiju2table`.
def kaiju_to_tax_summary_data(kaiju_output_file, output_data_table, names_tax_file, nodes_tax_file,
                              rank_limit='phylum', top_tax=10, kaiju_to_table_path=KAIJU_TO_TABLE_PATH):
    """Use Kaiju's `kaiju2table` utility to create a TSV file that contains the necessary data for the summary
    pie/barcharts visualizations (i.e. overview of sample composition at a given taxonomic rank).

    :param kaiju_output_file: Kaiju output file
    :param output_data_table: output summary TSV file
    :param names_tax_file: `names.dmp` file from NCBI taxonomy (taxonomic names)
    :param nodes_tax_file: `nodes.dmp` file from NCBI taxonomy (taxonomic tree)
    :param rank_limit: maximum taxonomic rank to consider for the summary
    :param top_tax: number of top taxa to retain in the output (the rest is collapsed as 'Other')
    :param kaiju_to_table_path: `kaiju2table` command path

    """
    sys.stderr.write("Generating %s summary... \n" % rank_limit)
    chart_data = {}
    unclass_str = 'Unclassified'
    n_unclass = 0
    # 1. Run `kaiju2table`
    kaiju2table_output_file = os.path.join(os.path.dirname(output_data_table), "kaiju_summary.%s.tsv" % rank_limit)
    kaiju2table = subprocess.Popen(
        [kaiju_to_table_path,
         "-t", nodes_tax_file,
         "-n", names_tax_file,
         "-r", rank_limit,
         "-o", kaiju2table_output_file,
         kaiju_output_file],
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE)
    sys.stderr.write("Creating Kaiju summary file (`kaiju2table`)... \n")
    kaiju2table.communicate()
    exit_status = kaiju2table.returncode
    sys.stderr.write("... kaiju2table finished with code: " + str(exit_status) + '\n')
    if exit_status != 0:
        sys.exit(exit_status)

    # 2. Parse output to create top tax. file
    with open(kaiju2table_output_file, 'r') as in_file:
        next(in_file)
        for line in in_file:
            current_rec = line.strip().split('\t')
            n_sqces = int(current_rec[2])
            tax_id = current_rec[3]
            tax_name = current_rec[-1]
            if tax_id == 'NA':
                # Ignore the 'cannot be assigned to a (non-viral) [...]' record
                # Replace unclassified by the string expected by the web app
                if tax_name == 'unclassified':
                    n_unclass = n_sqces
            else:
                chart_data[tax_name] = n_sqces

    # 3. Sort/filter chart data and create output
    with open(output_data_table, 'w') as out_file:
        top = sorted([a for a in chart_data.iteritems()], key=lambda tup: tup[1], reverse=True)[0:top_tax]
        for e in top:
            out_file.write(e[0]+'\t'+str(e[1])+'\n')
        if len(chart_data) > top_tax:
            other_sum = sum([a[1] for a in sorted([a for a in chart_data.iteritems()], key=lambda tup: tup[1], reverse=True)[top_tax:]])
            out_file.write('Other'+'\t'+str(other_sum)+'\n')
        # Add Unclassified
        if n_unclass > 0:
            out_file.write(unclass_str + '\t' + str(n_unclass) + '\n')

    # 4. Delete `kaiju2table` output
    os.remove(kaiju2table_output_file)
