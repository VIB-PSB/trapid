"""
A collection of functions used to perform subset functional enrichment analysis within TRAPID
"""

import argparse
import common
import math
import MySQLdb as MS
import os
import subprocess
import sys
import time
from ConfigParser import ConfigParser

GO_FILTER = {"GO:0005575", "GO:0003674", "GO:0008150"}  # Default list of GO to filter (top GO terms)


def check_enricher_bin(enricher_bin, verbose=False):
    """Check if the path to @dreec enricher binary exists. If not, print an error message and exit. """
    if not os.path.exists(enricher_bin):
        sys.stderr.write("[Error] Enricher binary ('%s') not found\n" % enricher_bin)
        sys.exit(1)
    else:
        if verbose:
            sys.stderr.write("[Message] Enricher binary to use: '%s'\n" % enricher_bin)


def del_files(files):
    """Delete the files given as parameters (typically a list of temporary files). """
    sys.stderr.write("[Message] Removing temporary files...\n")
    for file in files:
        os.remove(file)


def delete_previous_results(trapid_db_conn, exp_id, fa_type, subset, max_pval, verbose):
    """
    Delete functional enrichment results from the `functional_enrichments` table of TRAPID db (accessed with `trapid_db_conn`),
    for experiment `exp_id`, subset `subset`, functional annotation type `fa_type` and maximum p-value `max_pval`.
    """
    sys.stderr.write("[Message] Delete previous enrichment results from TRAPID db...\n")
    sql_query = "DELETE FROM `functional_enrichments` WHERE `experiment_id`='{exp_id}' AND `label`='{subset}' AND `data_type`='{fa_type}' AND `max_p_value`={max_pval}"
    formatted_query = sql_query.format(exp_id=exp_id, subset=subset, fa_type=fa_type, max_pval=max_pval)
    if verbose:
        sys.stderr.write("[Message] Query to execute: %s\n" % formatted_query)
    cursor = trapid_db_conn.cursor()
    cursor.execute(formatted_query)
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
        if record['alt_ids'] and record['alt_ids'] != '':
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
    # Now populate `go_dict` with parents and children
    for go in go_dict:
        if not go_dict[go]['is_obsolete']:
            if go in go_parents:
                go_dict[go]['parents'].update(go_parents[go])
            else:
                sys.stderr.write("[Warning] No parents found for '%s'.\n" % go)
            if go in go_children:
                go_dict[go]['children'].update(go_children[go])
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


def create_enricher_input_set(trapid_db_conn, exp_id, subset, fa_type, tmp_dir, verbose=False):
    """
    Create input set file for Dries' enricher. Set file created based on transcript ids from subset `subset` retrieved
    from `transcripts_labels` table. TRAPID db accessed through `trapid_db_conn`. Return path to the created input file.
    """
    sys.stderr.write("[Message] Create enricher input set file...\n")
    set_file = os.path.join(tmp_dir, "transcript_subset_{fa}_{subset}.lst".format(fa=fa_type, subset=subset))
    # Create set file. Format: either object ids (like here) or set ids in first column and object ids in second column
    cursor = trapid_db_conn.cursor()
    set_query = "SELECT `transcript_id` FROM `transcripts_labels` WHERE `experiment_id` = '{exp_id}' AND `label`='{subset}'"
    formatted_set_query = set_query.format(exp_id=exp_id, subset=subset)
    if verbose:
        sys.stderr.write("[Message] Query to execute: %s\n" % formatted_set_query)
    cursor.execute(formatted_set_query)
    with open(set_file, 'w') as out_file:
        for record in common.ResultIter(db_cursor=cursor):
            out_file.write("%s\n" % record[0])
    return set_file


def create_enricher_input_feature(trapid_db_conn, exp_id, fa_type, tmp_dir, verbose=False):
    """
    Create input feature file for Dries' enricher. Feature file created from all transcript/functional annotation from
    the `transcripts_annotation` table for all transcripts of experiment `exp_id` and for type `fa_type`. Return path to
    the created input file.
    """
    sys.stderr.write("[Message] Create enricher input feature file...\n")
    feature_file = os.path.join(tmp_dir, "all_transcripts_{fa_type}_{exp_id}.tsv".format(fa_type=fa_type, exp_id=exp_id))
    # Create feature file. Format: feature ids in 1st column (i.e. GOs, IPRs), object ids in 2nd column (i.e. transcript ids)
    feature_query = "SELECT `transcript_id`, `name` FROM `transcripts_annotation` WHERE `experiment_id`='{exp_id}' AND `type`='{fa_type}'"
    cursor = trapid_db_conn.cursor()
    formatted_feature_query = feature_query.format(exp_id=exp_id, fa_type=fa_type)
    if verbose:
        sys.stderr.write("[Message] Query to execute: %s\n" % formatted_feature_query)
    cursor.execute(formatted_feature_query)
    with open(feature_file, 'w') as out_file:
        for record in common.ResultIter(db_cursor=cursor):
            # Create string to write to `feature_file` and write it
            out_file.write("{annot}\t{tr}\n".format(annot=record[1], tr=record[0]))
    return feature_file


# Note: this fix should not be necessary if potentially invalid GO terms are taken care of during initial processing.
def clean_enricher_input_feature_go(feature_file, go_data, verbose=False, go_filter=GO_FILTER):
    """
    Clean input GO feature file `feature_file`: replace obsolete/alternative GO terms when possible, and remove missing
    GO terms, based on ontology data from `go_data`.
    """
    sys.stderr.write("[Message] Clean enricher input feature file (GO check)...\n")
    go_trs = {}
    alt_gos = get_alt_gos(go_data)  # Alt. GO term to regular GO term mapping dictionary
    # Get GO->transcripts mapping
    with open(feature_file, 'r') as in_file:
        for line in in_file:
            go_id, trs_id = line.strip().split('\t')
            if go_id not in go_trs:
                go_trs[go_id] = set([trs_id])
            else:
                go_trs[go_id].add(trs_id)
    # Handle invalid GO terms: a term is valid only if it is in `go_data`, or it is alternative/obsolete and can be replaced.
    invalid_gos = flag_invalid_gos(go_trs.keys(), go_data, alt_gos, verbose)
    # Remove unfound GO terms
    for go in invalid_gos['unfound']:
        del go_trs[go]
    # Replace alternative/obsolete GO terms and add parental terms
    for go in invalid_gos['alternative'] | invalid_gos['obsolete']:
        transcripts = go_trs[go]
        go_replacement = (alt_gos[go] if go in alt_gos else go_data[go]['replace_by'])
        del go_trs[go]
        go_trs[go_replacement] = transcripts
        # Update parental terms
        parents = go_data[go_replacement]['parents'] - go_filter
        for parent in parents:
            if parent in go_trs:
                go_trs[parent].update(transcripts)
            else:
                if verbose:
                    sys.stderr.write("[Warning] Added '%s' as parental GO term when replacing '%s' by '%s'\n" % (parent, go, go_replacement))
                go_trs[parent] = transcripts
    # Write cleaned feature file
    with open(feature_file, 'w') as out_file:
        for go, transcripts in go_trs.items():
            go_lines = "\n".join(["{go}\t{tr}".format(go=go, tr=tr) for tr in transcripts])
            out_file.write("%s\n" % go_lines)


def flag_invalid_gos(go_terms, go_data, alt_gos, verbose=False):
    """
    Using reference database GO data `go_data` and alternive GO data `alt_gos`, examine GO term set/list `go_terms` to
    flag invalid GO terms (obsolete, alternative, or not found in the data). Return the results as a dictionary of
    invalid GO sets per category.
    """
    invalid_gos = {'obsolete': set([]), 'alternative':set([]), 'unfound': set([])}
    for go in go_terms:
        go_term = go
        # Check if GO term is alternative, replace it
        if go_term in alt_gos:
            if go_term not in invalid_gos['alternative']:
                invalid_gos['alternative'].add(go_term)
                if verbose:
                    sys.stderr.write("[Warning] GO term '%s' is alternative & can be replaced by '%s' \n" % (go_term, alt_gos[go_term]))
            go_term = alt_gos[go_term]
        # Check if GO term is obsolete and replace it by the term in `replace_by`.
        if go_term in go_data and go_data[go_term]['is_obsolete']:
            if go_term not in invalid_gos['obsolete']:
                invalid_gos['obsolete'].add(go_term)
                if verbose:
                    sys.stderr.write("[Warning] GO term '%s' is obsolete & can be replaced by '%s' \n" % (go_term, go_data[go_term]['replace_by']))
            go_term = go_data[go_term]['replace_by']
        # If there is no possible replacement, the GO term is invalid and ignored
        if go_term not in go_data:
            if go_term not in invalid_gos['unfound']:
                invalid_gos['unfound'].add(go_term)
                if verbose:
                    sys.stderr.write("[Warning] GO term '%s' not found in GO data and will be ignored.\n" % go_term)
    return invalid_gos



def create_enricher_input(trapid_db_conn, exp_id, fa_type, subset, tmp_dir, verbose=False):
    """
    Create input files for Dries' enricher. Feature file created from all transcript/functional annotation from the
    `transcripts_annotation` table for all transcripts of experiment `exp_id` and for type `fa_type`. Set file created based on
    transcript ids from subset `subset` retrieved from `transcripts_labels` table. TRAPID DB accessed through `trapid_db_conn`.
    Return path to the created input files as tuple.
    """
    sys.stderr.write("[Message] Create enricher input files...\n")
    feature_file = create_enricher_input_feature(trapid_db_conn, exp_id, fa_type, tmp_dir, verbose)
    set_file = create_enricher_input_set(trapid_db_conn, exp_id, subset, fa_type, tmp_dir, verbose)
    return (feature_file, set_file)


def call_enricher(feature_file, set_file, max_pval, exp_id, subset, fa_type, enricher_bin, tmp_dir):
    """
    Call enricher script with the defined input files `feature_file` and `set_file`, using a FDR threshold of `max_pval`.
    `exp_id`, `subset`, and `fa_type` are used to name the output file. Return output file name.
    """
    cmd_str = "{enricher_bin} -f {max_pval} -o {out_file} {feature_file} {set_file}"
    out_file = os.path.join(tmp_dir, "%s_enrichment_%s_%s_%s.out" % (fa_type, exp_id, subset, str(max_pval)))
    # Format cmd string and run!
    formatted_cmd = cmd_str.format(enricher_bin=enricher_bin, max_pval=max_pval, out_file=out_file, feature_file=feature_file, set_file=set_file)
    sys.stderr.write("[Message] Call enricher script with command: %s.\n" % formatted_cmd)
    job = subprocess.Popen(formatted_cmd, shell=True)
    job.communicate()
    return out_file


def read_enricher_output(enricher_output, verbose=False):
    """
    Read enricher output file `enricher_output` and return results as a dictionary.
    """
    enricher_results = {}
    # Columns of the enricher output (only from the 3rd)
    enricher_cols = ["p-val", "q-val", "enr_fold", "set_size", "ftr_size", "n_hits"]
    # Read output file and store results as dictionary
    with open(enricher_output, 'r') as in_file:
        for line in in_file:
            if line[0] != '#':
                splitted = line.strip().split('\t')
                enricher_results[splitted[1]] = dict(zip(enricher_cols, [float(v) for v in splitted[2:]]))
    return enricher_results


def run_enricher(trapid_db_data, exp_id, fa_type, subset, max_pval, go_data, enricher_bin, tmp_dir, keep_tmp=False, verbose=False):
    """
    A wrapper function to create enricher input, run it, store output as a variable, and delete temporary files.
    """
    enricher_results = {}
    # Columns of the enricher output (only from the 3rd)
    enricher_cols = ["p-val", "q-val", "enr_fold", "set_size", "ftr_size", "n_hits"]
    # Fetch needed functional annotation data for enrichment script and create input files
    db_conn = common.db_connect(*trapid_db_data)
    enricher_files = create_enricher_input(db_conn, exp_id, fa_type, subset, tmp_dir, verbose)
    db_conn.close()
    if fa_type == 'go':
        clean_enricher_input_feature_go(enricher_files[0], go_data, verbose)
    # Perform functional enrichment
    enricher_output = call_enricher(enricher_files[0], enricher_files[1], max_pval, exp_id, subset, fa_type, enricher_bin, tmp_dir)
    enricher_results = read_enricher_output(enricher_output, verbose)
    # Delete temporary files (if the `--keep_tmp` flag wasn't provided)
    if not keep_tmp:
        to_delete = [enricher_output]
        to_delete.extend(enricher_files)
        del_files(to_delete)
    return enricher_results


def create_enrichment_rows(enricher_results, exp_id, subset, fa_type, max_pval, go_data):
    """
    Process raw enrichment results `enricher_results` to create records that can be inserted into TRAPID's DB
    'functional_enrichments' table. Also set `is_hidden` value for parental GO terms in enrichment results based on GO hierarchy
    from `go_data`, if needed. Return correctly-formatted values to insert as list of tuples.
    """
    sys.stderr.write("[Message] Process enricher output...\n")
    enrichment_rows = []
    if fa_type == 'go':
        all_gos = set(enricher_results.keys())
        for fa in sorted(enricher_results):
            # Compute log2 of enrichment
            log2_enr = math.log(max(enricher_results[fa]['enr_fold'], sys.float_info.min), 2)
            # Compute subset ratio
            sub_ratio = enricher_results[fa]['n_hits'] / enricher_results[fa]['set_size'] * 100
            p_val = enricher_results[fa]['q-val']  # p-val stored in DB is the corrected one
            is_hidden = 0
            # GO should be hidden if any of its parents or children has more significant enrichment results.
            # 'more significant' => larger log2 enrichment fold and lower p-value
            # Check parents + children to see if there are better terms
            rel_gos = (go_data[fa]['parents'] | go_data[fa]['children']) & all_gos
            if rel_gos:
                rel_p_val = [enricher_results[go]['q-val'] for go in rel_gos]
                rel_enr = [math.log(max(enricher_results[go]['enr_fold'], sys.float_info.min), 2) for go in rel_gos]
                higher_enr = any([abs(f) > abs(log2_enr) for f in rel_enr])  # `True` if any related GO term has a larger log2 fold change
                lower_p_val = any([p < p_val for p in rel_p_val])            # `True` if any related GO term has a smaller enrichment p-value
                same_enr = any([abs(f) == abs(log2_enr) for f in rel_enr])   # `True` if any related GO term has an identical log2 fold change
                same_p_val = any([p == p_val for p in rel_p_val])            # `True` if any related GO term has an identical enrichment p-value
                if higher_enr and lower_p_val:
                    is_hidden = 1  # There is a better GO term to use! Set value to 1
                # Some related GO terms can have the same enrichment and p-value:
                # if the current GO term is not the most specific one, set `is_hidden` to 1
                if same_enr and same_p_val:
                    if go_data[fa]['children'] & all_gos:
                        is_hidden = 1
            values = (exp_id, subset, fa_type, max_pval, fa, is_hidden, p_val, log2_enr, sub_ratio)
            enrichment_rows.append(values)
    else:
        for fa in sorted(enricher_results):
            # Compute log2 of enrichment
            log2_enr = math.log(max(enricher_results[fa]['enr_fold'], sys.float_info.min), 2)
            # Compute subset ratio
            sub_ratio = enricher_results[fa]['n_hits'] / enricher_results[fa]['set_size'] * 100
            p_val = enricher_results[fa]['q-val']  # p-val stored in DB is the corrected one
            is_hidden = 0
            values = (exp_id, subset, fa_type, max_pval, fa, is_hidden, p_val, log2_enr, sub_ratio)
            enrichment_rows.append(values)
    return enrichment_rows


def upload_results_to_db(trapid_db_conn, enrichment_rows, verbose=False, chunk_size=500):
    """
    Insert formatted enrichment results `enrichment_rows` into TRAPID DB (accessed with `trapid_db_conn`).
    """
    sys.stderr.write("[Message] Upload enrichment results...\n")
    funct_enrichment_query = "INSERT INTO `functional_enrichments` (`id`, `experiment_id`, `label`, `data_type`, `max_p_value`, `identifier`, `is_hidden`, `p_value`, `log_enrichment`, `subset_ratio`) VALUES (0, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
    trapid_db_conn.autocommit = False
    cursor = trapid_db_conn.cursor()
    if verbose:
        sys.stderr.write("[Message] %d rows to insert!\n" % len(enrichment_rows))
    for i in range(0, len(enrichment_rows), chunk_size):
        cursor.executemany(funct_enrichment_query, enrichment_rows[i:min(i+chunk_size, len(enrichment_rows))])
        if verbose:
            sys.stderr.write("[Message] %s: Inserted %d rows...\n" % (time.strftime('%H:%M:%S'), chunk_size))
    trapid_db_conn.commit()
