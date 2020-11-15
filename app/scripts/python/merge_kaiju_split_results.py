"""
Merge Kaiju output files. Read all the result and get the best classification for each input sequence. If there is
conflict (i.e. two matches are equally good but in different splits), assign the LCA of conflicting tax ids to this
sequence. The script should be used after Kaiju was run with a split index.
"""

# Usage: python merge_split_kaiju_results.py <nodes.dmp> <split_results_dir> -o <merged_output_file>

import argparse
import glob
import os
import sys
import random
import time


def parse_arguments():
    """Parse command-line arguments.

    :return: parsed arguments (Namespace object).

    """
    cmd_parser = argparse.ArgumentParser(
        description='Merge Kaiju results. Use this script after Kaiju was run with a split index. ',
        formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    # Positional arguments
    cmd_parser.add_argument('nodes_dmp_file',
                            help='Path to nodes.dmp file. The same file should be used for running kaiju and merging results.')
    cmd_parser.add_argument('kaiju_outdir',
                            help='Path to split kaiju results files (can be a `ls`-like expression). If a directory is \
                            provided, will consider all the `.out` files in that directory. ')
    # Optional arguments
    cmd_parser.add_argument('-o', metavar='--output_file', dest='output_file', type=str,
                            help='Output file. If none provided, will output to STDOUT', default=None)
    # cmd_parser.add_argument('-m', '--kaiju_mode', type=str,
    #                         help='What mode was kaiju run with? Can be \'mem\' or \'greedy\'. \
    #                         THIS SCRIPT ONLY WORKS WITH MEM MODE FOR NOW!', default='mem')
    # sys.stderr.write(str(cmd_args)+'\n')  # Debug
    cmd_args = cmd_parser.parse_args()
    return cmd_args


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


# TODO: cleaner implementation LCA function.
def get_lca(tax_ids, tax_parents):
    """Return lowest common ancestor (LCA) of a set of tax ids. The tax ids need to be present in `tax_parents`.

    This function is a simplistic implementation of LCA that uses the list of parents produced with `get_tax_parents`.
    Here, getting the LCA of a set of tax ids is equivalent to reading the parent list of each tax id backwards (i.e.
    from root) until they differ. The last element to be identical represents the LCA.

    :param tax_ids: set of tax ids for which to compute the LCA
    :param tax_parents: <tax_id>:<parent_list> dictionary as returned by `get_tax_parents()`
    :return: the LCA of input tax ids

    """
    # We ignore tax ids not found in our results: this should be no issue with NCBI nr as all the data (taxonomy files
    # for Kaiju / TRAPID db, NCBI nr sequence) is retrieved approximately at the same time.
    # If only one tax_id provided, just return it as LCA.
    # This should not happen but I am not merging the kaiju results ''correctly'' (i.e. considering there is a conflict
    # when there is not and that we have the same match in the same tax_id, just with two different seqs. To correct)
    if len(tax_ids) == 1:
        return next(iter(tax_ids))
    # parent_subset = {tax_id: tax_parents[tax_id][::-1] for tax_id in tax_ids if tax_parents[tax_id] is not None}
    parent_subset = {tax_id: tax_parents[tax_id][::-1]+[tax_id] for tax_id in tax_ids if tax_parents[tax_id] is not None}
    # print len(parent_subset.keys())
    parent_list = [a for a in parent_subset.values()]
    lowest_common_ancestor = ''
    # print parent_subset
    for i in range(0, min([len(a) for a in parent_list])):
        ancestor_tax_ids = []
        for parent in parent_list:
            ancestor_tax_ids.append(parent[i])
        if len(set(ancestor_tax_ids)) == 1:
            # print "Still CA "+lowest_common_ancestor
            lowest_common_ancestor = ancestor_tax_ids[0]
        else:
        #     print "Not common anymore "+lowest_common_ancestor
            break  # No need to continue the search further
    return lowest_common_ancestor


def create_transcript_dict(kaiju_outdir):
    """Read a set of (split) Kaiju results from the same directory. Create and return a dictionnary that summarizes
    the results. The returned dictionary still needs to be processed to solve conflicts (need to get LCA).
    This method was only carefully tested with MEM mode (with verbose output files) but should work in greedy mode
    (using score threshold with `-s`): instead of the match length, the match score is compared in case of sequences
    classified in different splits.

    :param kaiju_outdir: Kaiju split output directory (or glob pattern). If a directory is provided, all `*.out` files
    from the directory are considered to be output files.
    :return: Kaiju result as transcript_id:results dictionary

    """
    transcript_dict = {}
    sys.stderr.write('[%s] Retrieving results from kaiju output files...\n' % time.strftime("%H:%M:%S"))
    if os.path.isdir(kaiju_outdir):
        file_list = glob.glob(os.path.join(kaiju_outdir, '*.out'))
    else:
        file_list = glob.glob(kaiju_outdir)
    sys.stderr.write('[%s] File list to consider: %s\n'
                     % (time.strftime("%H:%M:%S"), ', '.join([os.path.basename(f) for f in file_list])))
    for kaiju_output_file in file_list:
        sys.stderr.write('[%s] Reading results in %s\n' % (time.strftime("%H:%M:%S"), os.path.basename(kaiju_output_file)))
        with open(kaiju_output_file, 'r') as kaiju_output:
            for line in kaiju_output:
                current_data = line.strip().split('\t')
                # print current_data
                is_classified = True if current_data[0] == 'C' else False
                transcript_id = current_data[1]
                # if transcript_id=='TRINITY_DN15314_c0_g1_i1':
                #     sys.stderr.write('---'.join(current_data)+'\n')
                if is_classified:
                    selected_taxon = current_data[2]
                    match_len = current_data[3]
                    match_tax_ids = set(current_data[4][:-1].split(','))
                    match_sqce_ids = set(current_data[5][:-1].split(','))
                    match_sqces = set(current_data[6][:-1].split(','))
                    if transcript_id not in transcript_dict:
                        transcript_dict[transcript_id] = {'classified': is_classified,
                                                          'match_len': str(match_len),
                                                          'match_tax_ids': match_tax_ids,
                                                          'match_sqce_ids': match_sqce_ids,
                                                          'match_sqces': match_sqces,
                                                          'selected_taxon': selected_taxon}
                    else:
                        # It is already there... If same length, add extra information, replace selected taxon by `None`
                        # (i.e. we need to find LCA).
                        if int(match_len) == int(transcript_dict[transcript_id]['match_len']):
                            # print transcript_id
                            transcript_dict[transcript_id]['selected_taxon'] = None
                            transcript_dict[transcript_id]['match_tax_ids'] = transcript_dict[transcript_id]['match_tax_ids']|match_tax_ids
                            transcript_dict[transcript_id]['match_sqce_ids'] = transcript_dict[transcript_id]['match_sqce_ids']|match_sqce_ids
                            transcript_dict[transcript_id]['match_sqces'] = transcript_dict[transcript_id]['match_sqces']|match_sqces
                        # If superior length, overwrite previous data
                        if int(match_len) > int(transcript_dict[transcript_id]['match_len']):
                            transcript_dict[transcript_id] = {'classified': is_classified,
                                                              'match_len': str(match_len),
                                                              'match_tax_ids': match_tax_ids,
                                                              'match_sqce_ids': match_sqce_ids,
                                                              'match_sqces': match_sqces,
                                                              'selected_taxon': selected_taxon}
                else:
                    if transcript_id not in transcript_dict:
                        transcript_dict[transcript_id] = {'classified': is_classified,
                                                          'match_len': str(0),
                                                          'match_tax_ids': set(),
                                                          'match_sqce_ids': set(),
                                                          'match_sqces': set(),
                                                          'selected_taxon': '0'}
    return transcript_dict


def solve_match_conflicts(transcript_dict, nodes_tax_file):
    """Process a Kaiju result dictionary and return an updated dictionnary with conflict resolved and updated tax ids.

    :param transcript_dict: input Kaiju result dictionary
    :param nodes_tax_file: `nodes.dmp` file from NCBI taxonomy (taxonomic tree)
    :return: updated Kaiju result dictionary with conflicting matched resolved and updated tax ids.

    """
    sys.stderr.write('[%s] Solving conflicting matches (replacement by LCA). \n' % time.strftime("%H:%M:%S"))
    updated_transcript_dict = transcript_dict
    # Try to get 'ambiguous' taxonomic results (i.e. we need to get the LCA).
    try:
        conflicting_tax_ids = set.union(*[(transcript_dict[t]['match_tax_ids']) for t in transcript_dict if not transcript_dict[t]['selected_taxon']])
        # print conflicting_tax_ids
    except:
        sys.stderr.write('[%s] No conflicting matches found! \n' % time.strftime("%H:%M:%S"))
        conflicting_tax_ids = None
    # If there were no 'ambiguities' to solve, just return `transcript_dict`
    if not conflicting_tax_ids:
        return transcript_dict
    # Get parents for all retrieved tax_ids.
    conflicting_tax_parents = get_tax_parents(nodes_tax_file=nodes_tax_file, tax_ids=conflicting_tax_ids)
    not_found = set([parent for parent in conflicting_tax_parents.keys() if conflicting_tax_parents[parent] is None])
    # Now for each transcript for which we have a conflict, get LCA and assign it.
    for transcript_id in transcript_dict:
        if not transcript_dict[transcript_id]['selected_taxon']:
            tax_ids = transcript_dict[transcript_id]['match_tax_ids'] - not_found
            if len(tax_ids) > 0:
                best_taxon = get_lca(tax_ids=tax_ids, tax_parents=conflicting_tax_parents)
            # There are only problematic tax ids in that case... what to do?
            else:
                sys.stderr.write("[Warning] problematic case: conflict and none of the tax_ids are in the up-to-date taxonomy. \n")
                sys.stderr.write("Corresponding tax ids: %s \n"% ', '.join(transcript_dict[transcript_id]['match_tax_ids']))
                best_taxon = random.choice(list(transcript_dict[transcript_id]['match_tax_ids']))
            updated_transcript_dict[transcript_id]['selected_taxon'] = best_taxon
    return updated_transcript_dict


def produce_output(transcript_dict, output_file):
    """Produce merged output file. If no output file is provided, will output to STDOUT.

    :param transcript_dict: Kaiju result dictionary
    :param output_file: path of merged Kaiju output file

    """
    sys.stderr.write('[%s] Producing merged output. \n' % time.strftime("%H:%M:%S"))
    output_lines = []
    for transcript_id, record in sorted(transcript_dict.items()):
        if not record['classified']:
            output_lines.append('\t'.join([
                'U',
                transcript_id,
                record['selected_taxon']
            ]))
        else:
            output_lines.append('\t'.join([
                'C',
                transcript_id,
                record['selected_taxon'],
                record['match_len'],
                ','.join(sorted(list(record['match_tax_ids']), key=int))+',',
                ','.join(sorted(list(record['match_sqce_ids'])))+',',
                ','.join(sorted(list(record['match_sqces'])))+','
            ]))
    if not output_file:
        sys.stdout.write('\n'.join(output_lines)+'\n')
    else:
        with open(output_file, 'w') as out_file:
            out_file.write('\n'.join(output_lines)+'\n')


def merge_results(kaiju_outdir, nodes_tax_file, output_file):
    """Main function: read all split Kaiju output files, merge results, resolve conflicting matches, and create merged
    Kaiju output file.

    :param kaiju_outdir: path of split kaiju results files (directory or glob pattern)
    :param nodes_tax_file: `nodes.dmp` file from NCBI taxonomy (taxonomic tree)
    :param output_file: path of merged Kaiju output file

    """
    transcript_dict = create_transcript_dict(kaiju_outdir=kaiju_outdir)
    final_transcript_dict = solve_match_conflicts(transcript_dict=transcript_dict, nodes_tax_file=nodes_tax_file)
    produce_output(transcript_dict=final_transcript_dict, output_file=output_file)
    sys.stderr.write('[%s] Finished!\n' % time.strftime("%H:%M:%S"))


def main():
    """Script execution"""
    cmd_args = parse_arguments()
    merge_results(cmd_args.kaiju_outdir, cmd_args.nodes_dmp_file, cmd_args.output_file)


# Script execution when the script is called from the command-line
if __name__ == '__main__':
    main()
