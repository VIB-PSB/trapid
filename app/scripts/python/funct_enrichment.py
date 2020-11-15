"""
A collection of functions used to perform subset functional enrichment analysis within TRAPID.
"""

import math
import os
import subprocess
import sys
import time

import MySQLdb as MS

import common

GO_FILTER = {"GO:0005575", "GO:0003674", "GO:0008150"}  # Default list of GO to filter (top GO terms)


def check_enricher_bin(enricher_bin, verbose=False):
    """Check if the path to @dreec enricher binary exists. If not, print an error message and exit.

    :param enricher_bin: path to enricher binary
    :param verbose: whether to be verbose (print extra information to stderr if set to True)

    """
    if not os.path.exists(enricher_bin):
        sys.stderr.write("[Error] Enricher binary ('%s') not found\n" % enricher_bin)
        sys.exit(1)
    else:
        if verbose:
            sys.stderr.write("[Message] Enricher binary to use: '%s'\n" % enricher_bin)


def del_files(files):
    """Delete the files given as parameters (typically a list of temporary files).

    :param files: a collection of files

    """
    sys.stderr.write("[Message] Removing temporary files...\n")
    for file in files:
        os.remove(file)


def delete_previous_results(trapid_db_conn, exp_id, fa_type, subset, max_pval, verbose=False):
    """Delete functional enrichment results from the `functional_enrichments` and `functional_enrichments_sankey` tables
    of TRAPID db for given subset, functional annotation type, and maximum p-value.

    :param trapid_db_conn: TRAPID db connection as returned by common.db_connect()
    :param exp_id: TRAPID experiment id
    :param fa_type: functional annotation type
    :param subset: subset name
    :param max_pval: maximum p-value
    :param verbose: whether to be verbose (print extra information to stderr if set to True)

    """
    sys.stderr.write("[Message] Delete previous enrichment results from TRAPID db...\n")
    # result_tables = ["functional_enrichments", "functional_enrichments_sankey"]  # Tables to delete records from
    result_tables = ["functional_enrichments"]  # Tables to delete records from
    sql_query = "DELETE FROM `{table}` WHERE `experiment_id`='{exp_id}' AND `label`='{subset}' AND `data_type`='{fa_type}' AND `max_p_value`={max_pval}"
    for table in result_tables:
        formatted_query = sql_query.format(table=table, exp_id=exp_id, subset=subset, fa_type=fa_type, max_pval=max_pval)
        if verbose:
            sys.stderr.write("[Message] Query to execute: %s\n" % formatted_query)
        cursor = trapid_db_conn.cursor()
        cursor.execute(formatted_query)
    trapid_db_conn.commit()


def get_go_data(ref_db_conn):
    """Get GO data (hierarchy, alternative IDs, aspects) from the used reference database.

    :param ref_db_conn: reference db connection as returned by common.db_connect()
    :return: retrieved GO data as a dictionary

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
    # TODO: check `is_obsolete` in parents as well?
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
    """Get alternative GO - canonical GO id correspondence.

    :param go_dict: GO information dictionary
    :return: alt_id:go mapping dictionary

    """
    alt_gos = {}
    for go in go_dict:
        if go_dict[go]['alt_ids']:
            for alt_go in go_dict[go]['alt_ids']:
                alt_gos[alt_go] = go
    return alt_gos


def create_enricher_input_set(trapid_db_conn, exp_id, subset, fa_type, tmp_dir, verbose=False):
    """Create input set file for enricher script. The set file is created based on transcript ids from a subset
    retrieved from the `transcripts_labels` table.


    :param trapid_db_conn: TRAPID db connection as returned by common.db_connect()
    :param exp_id: TRAPID experiment id
    :param subset: subset for which enrichment analysis is performed
    :param fa_type: functional annotation type (used for file naming)
    :param tmp_dir: TRAPID experiment temporary directory
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: path of the created input file.

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
    """Create input feature file for enricher script. Feature file created from all transcript/functional annotation
    from the `transcripts_annotation` table for all transcripts of an experiment and for a given functional annotation
    type.

    :param trapid_db_conn: TRAPID db connection as returned by common.db_connect()
    :param exp_id: TRAPID experiment id
    :param fa_type: functional annotation type (used for data etrieval and file naming)
    :param tmp_dir: TRAPID experiment temporary directory
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: path of the created input file

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
    """Clean input GO feature file: replace obsolete/alternative GO terms when possible, and remove missing
    GO terms, based on ontology data from a reference database.

    :param feature_file: feature file to clean
    :param go_data: GO data dictionary
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :param go_filter: GO terms to filter, the 3 root terms by default

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
    """Using reference database GO data and alternive GO data `alt_gos`, examine GO term set/list `go_terms` to
    flag invalid GO terms (obsolete, alternative, or not found in the data). Return the results as a dictionary of
    invalid GO sets per category.

    :param go_terms: list or set of GO terms to check
    :param go_data: GO data dictionary
    :param alt_gos: alternative GO - canonical GO id correspondence
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: invalid GO terms dictionary

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
    """Create input files for enricher script.
        * Feature file created from all transcript/functional annotation from the `transcripts_annotation` table for all
          transcripts of the experiment the selected functional annotation type
        * Set file created based on transcript ids from a subset retrieved from the `transcripts_labels` table.

    :param trapid_db_conn: TRAPID db connection as returned by common.db_connect()
    :param exp_id: TRAPID experiment id
    :param fa_type: functional annotation type
    :param subset: subset for which enrichment analysis is performed
    :param tmp_dir: TRAPID experiment temporary directory
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: paths of the created input files as tuple.

    """
    sys.stderr.write("[Message] Create enricher input files...\n")
    feature_file = create_enricher_input_feature(trapid_db_conn, exp_id, fa_type, tmp_dir, verbose)
    set_file = create_enricher_input_set(trapid_db_conn, exp_id, subset, fa_type, tmp_dir, verbose)
    return (feature_file, set_file)


def call_enricher(feature_file, set_file, max_pval, exp_id, subset, fa_type, enricher_bin, tmp_dir):
    """Call enricher script with the defined input files and selected parameters and return output file path.

    :param feature_file: input feature file
    :param set_file: input set file
    :param max_pval: maximum correct p-value
    :param exp_id: TRAPID experiment id
    :param subset: subset for which enrichment analysis is performed
    :param fa_type: functional annotation type
    :param enricher_bin: path to enricher binary
    :param tmp_dir: TRAPID experiment temporary directory
    :return: path to output file

    """
    cmd_str = "{enricher_bin} -f {max_pval} -o {out_file} {feature_file} {set_file}"
    out_file = os.path.join(tmp_dir, "%s_enrichment_%s_%s_%s.out" % (fa_type, exp_id, subset, str(max_pval)))
    # Format cmd string and run!
    formatted_cmd = cmd_str.format(enricher_bin=enricher_bin, max_pval=max_pval, out_file=out_file, feature_file=feature_file, set_file=set_file)
    sys.stderr.write("[Message] Call enricher script with command: %s.\n" % formatted_cmd)
    job = subprocess.Popen(formatted_cmd, shell=True)
    job.communicate()
    return out_file


def read_enricher_output(enricher_output):
    """Read enricher output file and return results as a dictionary.


    :param enricher_output: path of enricher output file
    :return: enrichment result dictionary

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


def run_enricher(trapid_db_data, exp_id, fa_type, subset, max_pval, go_data, enricher_bin, tmp_dir, keep_tmp=False,
                 verbose=False):
    """A wrapper function to create enricher input, run it, store output as a variable, and delete temporary files.

    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())
    :param exp_id: TRAPID experiment id
    :param fa_type: functional annotation type
    :param subset: subset for which enrichment analysis is performed
    :param max_pval: maximum p-value
    :param go_data: GO data dictionary
    :param enricher_bin: path to enricher binary
    :param tmp_dir: TRAPID experiment temporary directory
    :param keep_tmp: whtehr temporary files hsould be kept
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: enrichment results and GF data

    """
    # enricher_results = {}
    # Columns of the enricher output (only from the 3rd)
    # enricher_cols = ["p-val", "q-val", "enr_fold", "set_size", "ftr_size", "n_hits"]
    # Fetch needed functional annotation data for enrichment script and create input files
    db_conn = common.db_connect(*trapid_db_data)
    enricher_files = create_enricher_input(db_conn, exp_id, fa_type, subset, tmp_dir, verbose)
    if fa_type == 'go':
        clean_enricher_input_feature_go(enricher_files[0], go_data, verbose)
    # Perform functional enrichment
    enricher_output = call_enricher(enricher_files[0], enricher_files[1], max_pval, exp_id, subset, fa_type, enricher_bin, tmp_dir)
    enrichment_results = read_enricher_output(enricher_output)
    # Get GF data for enrichment results
    enrichment_gf_data = get_enrichment_gf_data(db_conn, exp_id, subset, enrichment_results, enricher_files[0], enricher_files[1], verbose)
    db_conn.close()
    # Delete temporary files (if the `--keep_tmp` flag wasn't provided)
    if not keep_tmp:
        to_delete = [enricher_output]
        to_delete.extend(enricher_files)
        del_files(to_delete)
    return {"results": enrichment_results, "gf_data": enrichment_gf_data}


def create_enrichment_rows(enricher_results, enrichment_gf_data, exp_id, subset, fa_type, max_pval, go_data):
    """Process raw enrichment results to create records that can be inserted into TRAPID's DB 'functional_enrichments'
    table. Also set `is_hidden` value for parental GO terms in enrichment results based on GO hierarchy, if needed.


    :param enricher_results: raw enrichment results
    :param enrichment_gf_data: enrichment GF data (used for Sankey diagrams) as reutrned by get_enrichment_gf_data()
    :param exp_id: TRAPID experiment id
    :param subset: subset for which enrichment analysis is performed
    :param fa_type: functional annotation type
    :param max_pval: maximum (corrected) p-value used for the enrichment
    :param go_data: GO data dictionary
    :return: correctly-formatted values to insert as a list of tuples

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
            sub_hits = enricher_results[fa]['n_hits']  # This is used for the enrichment Sankey diagrams
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
            values = (exp_id, subset, fa_type, max_pval, fa, is_hidden, p_val, log2_enr, sub_ratio, sub_hits, enrichment_gf_data[fa])
            enrichment_rows.append(values)
    else:
        for fa in sorted(enricher_results):
            # Compute log2 of enrichment
            log2_enr = math.log(max(enricher_results[fa]['enr_fold'], sys.float_info.min), 2)
            # Compute subset ratio
            sub_ratio = enricher_results[fa]['n_hits'] / enricher_results[fa]['set_size'] * 100
            p_val = enricher_results[fa]['q-val']  # p-val stored in DB is the corrected one
            sub_hits = enricher_results[fa]['n_hits']  # This is used for the enrichment Sankey diagrams
            is_hidden = 0
            values = (exp_id, subset, fa_type, max_pval, fa, is_hidden, p_val, log2_enr, sub_ratio, sub_hits, enrichment_gf_data[fa])
            enrichment_rows.append(values)
    return enrichment_rows


def upload_results_to_db(trapid_db_conn, enrichment_rows, verbose=False, chunk_size=500):
    """Insert formatted enrichment results into TRAPID DB.


    :param trapid_db_conn: TRAPID db connection as returned by common.db_connect()
    :param enrichment_rows: formatted enrichment result records
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :param chunk_size: number of records ot insert at once (500 by default)

    """
    sys.stderr.write("[Message] Upload enrichment results...\n")
    funct_enrichment_query = "INSERT INTO `functional_enrichments` (`id`, `experiment_id`, `label`, `data_type`, `max_p_value`, `identifier`, `is_hidden`, `p_value`, `log_enrichment`, `subset_ratio`, `subset_hits`, `subset_hits_gf_data`) VALUES (0, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
    trapid_db_conn.autocommit = False
    cursor = trapid_db_conn.cursor()
    if verbose:
        sys.stderr.write("[Message] %d rows to insert!\n" % len(enrichment_rows))
    for i in range(0, len(enrichment_rows), chunk_size):
        cursor.executemany(funct_enrichment_query, enrichment_rows[i:min(i+chunk_size, len(enrichment_rows))])
        if verbose:
            sys.stderr.write("[Message] %s: Inserted %d rows...\n" % (time.strftime('%H:%M:%S'), chunk_size))
    trapid_db_conn.commit()


def update_enrichment_state(trapid_db_conn, exp_id):
    """Update functional enrichment state in the `experiments` table of TRAPID db.

    :param trapid_db_conn: TRAPID db connection as returned by common.db_connect()
    :param exp_id: TRAPID experiment id

    """
    sql_query = "UPDATE `experiments` SET `enrichment_state`='finished' WHERE `experiment_id`='{exp_id}'"
    formatted_query = sql_query.format(exp_id=exp_id)
    cursor = trapid_db_conn.cursor()
    cursor.execute(formatted_query)
    trapid_db_conn.commit()


def send_end_email_preprocessing(trapid_db_conn, exp_id, fa_type):
    """Send an email to the owner of an experiment to warn them of job completion.

    :param trapid_db_conn: TRAPID db connection as returned by common.db_connect()
    :param exp_id: TRAPID experiment id
    :param fa_type: functional annotation type

    """
    # Functional annotation type labels used in the email
    fa_labels = {"go": "GO", "ipr": "protein domain", "ko": "KO"}
    # Get title & email address associated to the experiment
    query_str = "SELECT a.`title`, b.`email` FROM `experiments` a,`authentication` b WHERE a.`experiment_id`='{exp_id}' AND b.`user_id`=a.`user_id`;"
    page_url = '/'.join([common.TRAPID_BASE_URL, 'trapid', 'experiment', str(exp_id)])
    cursor = trapid_db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id))
    exp_data = cursor.fetchone()
    # Send email!
    if not exp_data:
        sys.stderr.write("[Error] Impossible to retrieve experiment title/email address (experiment '%d')!\n" % (exp_id))
        sys.exit(1)
    email_subject = "TRAPID experiment has finished functional enrichment preprocessing\n"
    email_content = "Dear user,\n\nThe functional enrichment preprocessing ({fa_label}) in your TRAPID experiment '{exp_title}' has finished. \n\nYou can access your experiment at this URL: {page_url}\n\nThank you for using TRAPID. \n".format(fa_label=fa_labels[fa_type], exp_title=exp_data[0], page_url=page_url)
    common.send_mail(to=[exp_data[1]], subject=email_subject, text=email_content)


def get_transcript_gfs(trapid_db_conn, exp_id, transcripts, verbose=False):
    """Get GF for a set or list of transcripts.

    :param trapid_db_conn: TRAPID db connection as returned by common.db_connect()
    :param exp_id: TRAPID experiment id
    :param transcripts: set or list of transcript ids
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: transcript:gf dictionary

    """
    trs_gf = {}
    trs_gf_query = "SELECT `transcript_id`, `gf_id` from `transcripts` WHERE `experiment_id`='{exp_id}' AND `transcript_id` IN ({trs_str})"
    if transcripts:
        # Create string for SQL `IN` clause (quoted, separated by commas)
        trs_str = ", ".join(["'%s'" % trs for trs in sorted(list(transcripts))])
        # Get associated GFs
        cursor = trapid_db_conn.cursor()
        formatted_trs_gf_query = trs_gf_query.format(exp_id=exp_id, trs_str=trs_str)
        if verbose:
            sys.stderr.write("[Message] Query to execute: %s\n" % formatted_trs_gf_query)
        cursor.execute(formatted_trs_gf_query)
        for record in common.ResultIter(db_cursor=cursor):
            if record[1]:
                trs_gf[record[0]] = record[1]
            else:
                trs_gf[record[0]] = "NULL"  # Change `NULL` to a more readable value?
    return trs_gf


def cleanup_enrichment_preprocessing(trapid_db_conn, exp_id, fa_type, verbose):
    """Perform cleanup tasks at the end of functional enrichment preprocessing:
        * Update `experiment_log`
        * Delete cluster job from `experiment_jobs`
        * Update `enrichment_state` for the experiment
        * Send a completion email to the user

    :param trapid_db_conn: TRAPID db connection as returned by common.db_connect()
    :param exp_id: TRAPID experiment id
    :param fa_type: functional annotation type
    :param verbose: whether to be verbose (print extra information to stderr if set to True)

    """
    sys.stderr.write("[Message] Cleanup at the end of enrichment preprocessing...\n")
    if verbose:
        sys.stderr.write("[Message] Update experiment log...\n")
    common.update_experiment_log(experiment_id=exp_id, action='enrichment_preprocessing', params='stop', depth=1, db_conn=trapid_db_conn)
    if verbose:
        sys.stderr.write("[Message] Delete enrichment preprocessing job from TRAPID database...\n")
    common.delete_experiment_job(experiment_id=exp_id, job_name='enrichment_preprocessing', db_conn=trapid_db_conn)
    if verbose:
        sys.stderr.write("[Message] Update enrichment state...\n")
    update_enrichment_state(trapid_db_conn=trapid_db_conn, exp_id=exp_id)
    if verbose:
        sys.stderr.write("[Message] Send an email to the experiment's user... \n")
    send_end_email_preprocessing(trapid_db_conn=trapid_db_conn, exp_id=exp_id, fa_type=fa_type)


def get_enrichment_gf_data(trapid_db_conn, exp_id, subset, enricher_results, feature_file, set_file, verbose=False):
    """Get GF data for enrichment results (i.e. GFs of subset transcripts having enriched functional annotations).
    This data is used to display the enrichment Sankey diagrams within TRAPID.

    :param trapid_db_conn: TRAPID db connection as returned by common.db_connect()
    :param exp_id: TRAPID experiment id
    :param subset: subset for which enrichment analysis is performed
    :param enricher_results: parsed enricher results
    :param feature_file:  enricher input feature file
    :param set_file: enricher input set file
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: GF data as fa:gf_string dictionary, with `gf_string` formatted as semi-colon separated `GF=n_hits` pairs.

    """
    sys.stderr.write("[Message] Generate enrichment GF data strings...\n")
    enriched_fa_gf_data = {}

    # Read feature file to get transcripts for each functional annotation
    fa_trs = {}
    with open(feature_file, 'r') as in_file:
        for line in in_file:
            fa_id, trs_id = line.strip().split('\t')
            if fa_id in enricher_results:
                if fa_id not in fa_trs:
                    fa_trs[fa_id] = set([trs_id])
                else:
                    fa_trs[fa_id].add(trs_id)
    # Get subset transcripts
    subset_trs = set()
    with open(set_file, 'r') as in_file:
        for line in in_file:
            subset_trs.add(line.strip())

    # Filter feature file data to retain only enriched functional annotations and subset transcripts
    enriched_fa_trs = {}
    for enriched_fa in enricher_results:
        enriched_fa_trs[enriched_fa] = fa_trs[enriched_fa] & subset_trs

    # Get transcripts from subset associated to enriched functional annotation and their GF data
    enriched_subset_trs = set()
    for fa_trs in enriched_fa_trs.values():
        enriched_subset_trs.update(fa_trs)
    trs_gf = get_transcript_gfs(trapid_db_conn, exp_id, enriched_subset_trs, verbose)
    gf_prefix = "%s_" % str(exp_id)
    # Create GF data strings
    for fa, trs in enriched_fa_trs.items():
        gfs = [trs_gf[t] for t in trs]
        gf_data = ";".join(["%s=%d" % (gf.replace(gf_prefix, ''), gfs.count(gf)) for gf in set(gfs)])
        enriched_fa_gf_data[fa] = gf_data

    return enriched_fa_gf_data
