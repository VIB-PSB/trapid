import json
import subprocess
import sys

KAIJU_TO_KRONA_PATH = "kaiju2krona"
KT_IMPORT_TEXT_PATH = "/www/blastdb/biocomp/moderated/trapid_02/kaiju_files/tools/krona/KronaTools/scripts/ImportText.pl"
# KT_IMPORT_TEXT_PATH = "/blastdb/webdb/moderated/trapid_02/kaiju_files/tools/krona/KronaTools/scripts/ImportText.pl"  # from midas

###
# NCBI taxonomy-related functions
###


def get_tax_parents(nodes_tax_file, tax_ids):
    """Parse `nodes_tax_file` (nodes.dmp from NCBI taxonomy) to get all parents of `tax_ids` (list of tax ids).
    Return a <tax_id>:<parent_list> dictionary.
    """
    # Read tax nodes
    tax_nodes = {}
    tax_parents = {}
    with open(nodes_tax_file, 'r') as nodes_tax:
        for line in nodes_tax:
            current_node = [field.strip() for field in line.split('|')]
            tax_nodes[current_node[0]]=current_node[1]
    # Get hierarchy to populate final dictionary
    for node in tax_ids:
        if node not in tax_nodes:
            tax_parents[node] = None
            sys.stderr.write('[Warning] Could not find %s in %s while parsing taxonomy hierarchy\n' % (node, nodes_tax_file))
        else:
            parent=tax_nodes[node]
            tax_parents[node] = []
            tax_parents[node].append(parent)
            while parent != tax_nodes[parent]:
                parent = tax_nodes[parent]
                tax_parents[node].append(parent)
    return tax_parents


def get_tax_ranks(nodes_tax_file, tax_ids):
    """Parse `nodes_tax_file` (nodes.dmp from NCBI taxonomy) to get all taxonomic ranks of `tax_ids` (list of tax ids).
    Return a <tax_id>:<taxonomic_rank> dictionary.
    """
    tax_nodes = {}
    tax_ranks = {}
    with open(nodes_tax_file, 'r') as nodes_tax:
        for line in nodes_tax:
            current_node = [field.strip() for field in line.split('|')]
            tax_nodes[current_node[0]] = current_node[2]
    for tax_id in tax_ids:
        if tax_id not in tax_nodes:
            tax_ranks[tax_id]=None
            sys.stderr.write('[Warning] Could not find %s in %s while parsing taxonomic ranks\n' % (tax_id, nodes_tax_file))
        else:
            tax_ranks[tax_id] = tax_nodes[tax_id]
    return tax_ranks


def get_tax_names(names_tax_file, tax_ids):
    """Parse `names_tax_file` (names.dmp from NCBI taxonomy) to get all common names of `tax_ids` (list of tax ids).
    Return a <tax_id>:<common_name> dictionary.
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
    """Parse `names_tax_file` and `nodes_tax_file` (names.dmp and nodes.dmp from NCBI taxonomy) to get all common names
    corresponding to all organisms of rank `tax_rank` (a taxonomic rank).
    Return a list of common names.
    """
    tax_ids = []
    tax_names = []
    with open(nodes_tax_file, 'r') as nodes_tax:
        for line in nodes_tax:
            if '\t'+tax_rank in line:
                current_node = [field.strip() for field in line.split('|')]
                tax_ids.append(current_node[0])
    with open(names_tax_file, 'r') as names_tax:
        for line in names_tax:
            current_rec = [field.strip() for field in line.split('|') if 'scientific name' in line]
            if current_rec and current_rec[0] in tax_ids:
                tax_names.append(current_rec[1])
        if not tax_names:
            sys.stderr.write('[Warning] Could not find any names corresponding to taxonomic rank \'%s\'. \n' % tax_rank)
    return sorted(tax_names)


###
# Krona
###

def kaiju_to_krona(kaiju_output_file, kaiju_tsv_output_file, krona_html_file, names_tax_file, nodes_tax_file):
    """Use Kaiju's `kaiju2krona` and `ktImportTex`(from KronaTools) to generate Krona HTML file. """
    # 1. Generate tsv output file
    kaiju2krona = subprocess.Popen([KAIJU_TO_KRONA_PATH,    # Path to kaiju2krona
                        "-i", kaiju_output_file,
                        "-t", nodes_tax_file,
                        "-n", names_tax_file,
                        "-o", kaiju_tsv_output_file,   # Path to output (tsv output)
                        "-u"  # Include unclassified sequences in output
                        ],
                        stdout=subprocess.PIPE,
                        stderr=subprocess.PIPE)
    sys.stderr.write("Generating kaiju tsv output file (`kaiju2krona`)... \n")
    kaiju2krona.communicate()
    exit_status = kaiju2krona.returncode
    sys.stderr.write("... kaiju2krona finished with code: " + str(exit_status) + '\n')
    if exit_status != 0:
        sys.exit(exit_status)
    # 2. Generate Krona HTML
    print KT_IMPORT_TEXT_PATH
    kt_import_text = subprocess.Popen([KT_IMPORT_TEXT_PATH,    # Path to ktImportText
                          kaiju_tsv_output_file,  # Path to kaiju2krona's output file
                          "-o", krona_html_file   # Path to output (Krona HTML)
                          ],
                         stdout=subprocess.PIPE,
                         stderr=subprocess.PIPE)
    sys.stderr.write("Generating Krona HTML file... \n")
    kt_import_text.communicate()
    exit_status = kt_import_text.returncode
    sys.stderr.write("... ktImportText finished with code: " + str(exit_status) + '\n')
    if exit_status != 0:
        sys.exit(exit_status)


###
# Tree view
###

def kaiju_to_treeview(kaiju_output_file, names_tax_file, nodes_tax_file, treeview_json_file):
    """Process `kaiju_output_file` (output of kaiju) to create correctly-formatted file for use with Unipept's tree
    view (`treeview_json_file`). `names_tax_file` and `nodes_tax_file` correspond to names.dmp and nodes.dmp files from
    NCBI taxonomy.
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
    kaiju_dict = {key:value for key,value in kaiju_dict.items()}
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
    all_tax_ids=list(set(all_tax_ids))
    all_names = get_tax_names(names_tax_file=names_tax_file, tax_ids=all_tax_ids)
    all_ranks = get_tax_ranks(nodes_tax_file=nodes_tax_file, tax_ids=all_tax_ids)
    # 4. Make data edible by the tree viewer
    treeview_json_data = to_treeview_dict(kaiju_data_dict=kaiju_dict, names_dict=all_names, ranks_dict=all_ranks)
    # 5. Create output file
    with open(treeview_json_file, 'w') as out_file:
        out_file.write(json.dumps(treeview_json_data, sort_keys=True))


def to_treeview_dict(kaiju_data_dict, names_dict, ranks_dict):
    """From kaiju's results dictionary (`kaiju_data_dict`) and corresponding names (`names_dict`) and taxonomic ranks
    (`ranks_dict`), format data correctly for use with Unipept's tree view. Return a correctly-formatted dict.
    """
    cache = {}
    root = None
    for k,v in kaiju_data_dict.items():
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


###
# Piechart/Barchart summaries
###

def kaiju_to_tax_piechart_data(kaiju_tsv_output_file, output_data_table, names_tax_file, nodes_tax_file,
                               rank_limit='superkingdom', top_tax=10):
    """Use `kaiju_tsv_output_file` (output of Kaiju's `kaiju2krona`) to create `output_data_table`, a TSV file
    that contains the data used for the pie/barcharts shown on the web page (i.e. overview of the domain-level
    composition of samples).
    * `names_tax_file` and `nodes_tax_file`: names.dmp and nodes.dmp files from NCBI taxonomy.
    * `rank_limit`: the maximum taxonomic rank to consider
    * `top_tax`:  show the name of this number of top taxa (the rest is collapsed as 'Other').
    """
    kaiju_data = []
    chart_data = {}
    unclass_str = 'Unclassified'
    # 1. Get all phylum names for given rank_limit.
    tax_names = get_tax_rank_names(names_tax_file=names_tax_file, nodes_tax_file=nodes_tax_file, tax_rank=rank_limit)
    tax_names.append(unclass_str)
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
        if len(chart_data)>top_tax:
            other_sum = sum([a[1] for a in sorted([a for a in chart_data.iteritems()],key=lambda tup: tup[1], reverse=True)[10:]])
            out_file.write('Other'+'\t'+str(other_sum)+'\n')


def read_piechart_data(piechart_data_table):
    """Read piechart data and return it as list of list. """
    data_list = []
    with open(piechart_data_table, 'r') as in_file:
        for line in in_file:
            data_list.append(line.split())
    return data_list
