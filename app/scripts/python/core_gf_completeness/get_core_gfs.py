#!/usr/bin/python
"""
Retrieve core gene families for a given clade, from a PLAZA3-like database. Now also works with NCBI tax_id/clade name
as input.
"""

# Usage: ./get_core_gfs.py -u <mysql_user> -h <host_name> -sp <species_threshold> -o <output_name> <db_name> <clade_name>

import argparse
import json
import os

from numpy import average, median, std

from common import *


def parse_arguments():
    """Parse command-line arguments"""
    cmd_parser = argparse.ArgumentParser(
        description='A script to get core gene families for a given clade, from a TRAPID PLAZA3-like reference database. ',
        formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    # Positional arguments
    cmd_parser.add_argument('db_name',
                            help='Name of TRAPID ref DB with which you want to work (PLAZA-like only at the moment). ')
    cmd_parser.add_argument('clade',
                            help='Retrieve core GFs for which clade?')
    # Optional arguments
    cmd_parser.add_argument('-u', '--username', type=str, dest='username',
                            help='Username to connect to the database server. The script will prompt you for the password, unless you provided it as environment variable ($DB_PWD). ',
                            default='trapid_website')
    cmd_parser.add_argument('-s', '--mysql_server', type=str, dest='mysql_server', help='Host name (server). ',
                            default='psbsql01.psb.ugent.be')
    cmd_parser.add_argument('-o', '--output_file', dest='output_file', type=str,
                            help='Output file. If none provided, will output to STDOUT. ', default=None)
    cmd_parser.add_argument('-m', '--min_genes', dest='min_genes', type=int,
                            help='Cutoff value: only retrieve gene families having minimum this number of genes. By default (0), no filter is applied. ',
                            default=0)
    cmd_parser.add_argument('-M', '--max_genes', dest='max_genes', type=int,
                            help='Cutoff value: only retrieve gene families having maximum this number of genes. By default (0), no filter is applied. ',
                            default=0)
    cmd_parser.add_argument('-sp', '--species_perc', dest='species_perc', type=float,
                            help='Cutoff value: species representation percentage. Only gene families present in at least this proportion of species of the chosen clade will be retrieved. Must be comprised between 0 and 1. ',
                            default=0.8)
    cmd_parser.add_argument('--tax_source', dest='tax_source', choices=['ncbi', 'json'],
                            help='''The source from which to get tax_ids/organisms belonging to your clade of interest.
                            `ncbi`: from the NCBI taxonomy (with ete-toolkit), a tax_id or a clade name can be used as `clade`.
                            `json`: from the species trees (of various PLAZA versions), based on the JSON files in `data`.
                            If this option is selected only a clade name (present in the JSON files) can be used as `clade`. ''',
                            default='ncbi')
    cmd_parser.add_argument('--gf_len', dest='gf_len',
                            help='''When using this flag, produced core GF files will also contain avg/median/stdev gf member lengths.
                            It will take significantly longer to produce core GF files using this option. ''',
                            action='store_true', default=False)
    cmd_args = cmd_parser.parse_args()
    return cmd_args


### Functions

# Kind of stupid function that loads a species dict according to the selected database.
def load_clades(db_name):
    """Load clade-species JSON file depending on the selected database. Return clades-species dictionary. """
    if 'plaza_pico_02' in db_name:
        clades_species_path = os.path.join(os.path.dirname(__file__), '..', 'data', 'plaza_02_pico_species_dict.json')
    elif 'plaza_02_5' in db_name:
        clades_species_path = os.path.join(os.path.dirname(__file__), '..', 'data', 'plaza_02_5_species_dict.json')
    elif 'plaza_dicots_03' in db_name:
        clades_species_path = os.path.join(os.path.dirname(__file__), '..', 'data', 'plaza_03_dicots_species_dict.json')
    elif 'plaza_monocots_03' in db_name:
        clades_species_path = os.path.join(os.path.dirname(__file__), '..', 'data', 'plaza_03_monocots_species_dict.json')
    elif 'plaza_dicots_04' in db_name:
        clades_species_path = os.path.join(os.path.dirname(__file__), '..', 'data', 'plaza_04_dicots_species_dict.json')
    elif 'plaza_monocots_04' in db_name:
        clades_species_path = os.path.join(os.path.dirname(__file__), '..', 'data', 'plaza_04_monocots_species_dict.json')
    else:
        print_log_msg(log_str="Error: no clades/species dictionary for the database you selected (probably because of an incorrect `db_name`). Cannot go further", color="red")
        sys.exit(1)
    if not os.path.exists(clades_species_path):
        print_log_msg(log_str="Error: Couldn\'t find the clades/species dictionary (normally in ./data).", color="red")
        sys.exit(1)
    try:
        clades_species_dict = json.loads(open(clades_species_path, 'r').read())
        print_log_msg(log_str='Loaded clades data (' + clades_species_path + ').')
    except:
        print_log_msg(log_str='Error: Badly formatted JSON file (' + clades_species_dict + ')... Regenerate it!', color="red")
        sys.exit(1)
    return clades_species_dict


def check_clade(clade_name, clade_dict):
    """Check if a clade name is valid or not. """
    if clade_name not in clade_dict.keys():
        print_log_msg(log_str='Error: ' + clade_name + ' is not a valid clade. Correct values: ' + ', '.join(
            clade_dict.keys()
        ), color="red")
        sys.exit(1)


def check_cutoff_values():
    """Check if user-specified cutoff values make sense."""
    print_log_msg(log_str="Cutoff criteria are not checked yet! TODO!", color="orange")


def retrieve_all_gfs(db_conn):
    """Retrieve all GFs (+ members information) from a PLAZA3-like database. Return them as dictionary. """
    print_log_msg(log_str="Retrieve data for all GFs. ")
    all_gfs = {}
    get_all_gfs_query = "SELECT gf_id, gene_id, species FROM gf_data WHERE gf_id like 'HOM%';"
    print_log_msg(log_str="Query to execute: %s" % get_all_gfs_query)  # Debug (print SQL_request)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(get_all_gfs_query)
    # Iterate over query results to populate `all_gfs` dict
    for record in ResultIter(db_cursor=cursor):
        if record['gf_id'] not in all_gfs:
            all_gfs[record['gf_id']] = {'members': {record['gene_id']}, 'species': {record['species']},
                                        'is_core_gf': False, 'gf_weight': 0, 'len_avg': 0, 'len_med':0, 'len_stdev': 0}
        else:
            all_gfs[record['gf_id']]['members'].add(record['gene_id'])
            all_gfs[record['gf_id']]['species'].add(record['species'])
    cursor.close()
    # Compute GF weights and update `gf_weight` attribute
    for gf in all_gfs:
        gf_weight = float(len(all_gfs[gf]['species']))/float(len(all_gfs[gf]['members']))
        all_gfs[gf]['gf_weight'] = gf_weight
    return all_gfs


# TODO: handle all other cutoff values to define core GFs
def flag_core_gfs(all_gfs_dict, species_list, cutoff_dict):
    """Flag core GFs from all GFs present in `all_gfs_dict`. Return an updated dictionary. """
    print_log_msg(log_str="Flag core gene families. ")
    core_gfs = set([])
    # Here we chose to copy the dictionary instead of updating the one passed as parameter. Unelegant?
    updated_gfs_dict = {k: v.copy() for k,v in all_gfs_dict.items()}
    for gf, gf_data in all_gfs_dict.items():
        species_overlap = gf_data['species'] & set(species_list)
        if float(len(species_overlap))/float(len(species_list)) >= cutoff_dict['species_perc']:
            core_gfs.add(gf)
    for gf in core_gfs:
        updated_gfs_dict[gf]['is_core_gf'] = True
    return updated_gfs_dict


# Note: it may be faster to just fetch all the sequences and compute their lengths instead of running one query per GF!
def retrieve_gf_len_data(all_gfs_dict, db_conn, dna_sqces=True):
    """Retrieve length information for each GF to compute average protein length & std dev. Return an updated dictionary.
    If working with a database that contains protein sequences, `dna_sqces` needs to be set to `False`. """
    print_log_msg(log_str="Retrieve GF member sequence information. ")
    get_member_sqces_query = "SELECT gene_id, seq FROM annotation where gene_id IN ({members}) and `type`='coding';"
    updated_gfs_dict = {k: v.copy() for k, v in all_gfs_dict.items()}
    for gf in sorted(list(all_gfs_dict)):  # Sorted -> biggest GFs first
        members_len = []
        cursor = db_conn.cursor(MS.cursors.DictCursor)
        members_str = ",".join(["\'%s\'" % m for m in sorted(list(all_gfs_dict[gf]['members']))])
        cursor.execute(get_member_sqces_query.format(members=members_str))
        for record in ResultIter(db_cursor=cursor):
            if dna_sqces:
                # DNA -> divide by 3 to get protein length
                sqce_len = float(len(record['seq']) - len(record['seq']) % 3) / 3
            else:
                # Proteins -> no need to divide by 3
                sqce_len = float(len(record['seq']))
            members_len.append(sqce_len)
        cursor.close()
        updated_gfs_dict[gf]['len_avg'] = average(members_len)
        updated_gfs_dict[gf]['len_med'] = median(members_len)
        updated_gfs_dict[gf]['len_stdev'] = std(members_len)
    return updated_gfs_dict


def output_gf_data(all_gfs_dict, output_file):
    """Output data from `all_gfs_dict` to `output_file`. """
    column_names = ['gene_family', 'n_genes', 'n_species', 'weight', 'core_gf', 'gf_genes']
    with file_or_stdout(output_file) as out_file:
        out_file.write("{colnames}\n".format(colnames="\t".join(column_names)))
        for gf in sorted(all_gfs_dict):
            out_file.write("\t".join([
                gf,
                str(len(all_gfs_dict[gf]['members'])),
                str(len(all_gfs_dict[gf]['species'])),
                "{:.4f}".format(all_gfs_dict[gf]['gf_weight']),
                str(all_gfs_dict[gf]['is_core_gf']),
                "|".join(sorted(list(all_gfs_dict[gf]['members'])))
            ]) + '\n')


def output_gf_data_gf_len(all_gfs_dict, output_file):
    """Output data from `all_gfs_dict` to `output_file` when `gf_len` flag was provided. """
    column_names = ['gene_family', 'n_genes', 'n_species', 'weight', 'core_gf', 'gf_genes', 'len_avg', 'len_med', 'len_stdev']
    with file_or_stdout(output_file) as out_file:
        out_file.write("{colnames}\n".format(colnames="\t".join(column_names)))
        for gf in sorted(all_gfs_dict):
            out_file.write("\t".join([
                gf,
                str(len(all_gfs_dict[gf]['members'])),
                str(len(all_gfs_dict[gf]['species'])),
                "{:.4f}".format(all_gfs_dict[gf]['gf_weight']),
                str(all_gfs_dict[gf]['is_core_gf']),
                "|".join(sorted(list(all_gfs_dict[gf]['members']))),
                "{:.4f}".format(all_gfs_dict[gf]['len_avg']),
                "{:.4f}".format(all_gfs_dict[gf]['len_med']),
                "{:.4f}".format(all_gfs_dict[gf]['len_stdev'])
            ]) + '\n')


# If `experimental` is set to True, the core GF file will also
def get_core_gfs(db_conn, species_list, cutoff_dict, output_file, gf_len):
    """Retrieve GFs/core GFs and create core GF file to use for the completeness analysis (`output_file`) """
    gfs = retrieve_all_gfs(db_conn=db_conn)
    gfs = flag_core_gfs(all_gfs_dict=gfs, species_list=species_list, cutoff_dict=cutoff_dict)
    if gf_len:
        gfs = retrieve_gf_len_data(all_gfs_dict=gfs, db_conn=db_conn)
        output_gf_data_gf_len(all_gfs_dict=gfs, output_file=output_file)
    else:
        output_gf_data(all_gfs_dict=gfs, output_file=output_file)


# Main function (SQL query, weighting and processing and creation of output core GFs table)
def get_core_gfs_legacy(db_conn, clade_name, species_list, cutoff_dict, output_file):
    """Retrieve GFs/core GFs from MySQLdatabase and create output.
    Output format: GF|#genes|#species|weight|is_core_gf|genes """
    # Output: GF|#genes|#species|weight|genes
    print_log_msg(log_str='Retrieve core gene families.')
    # Create SQL query
    # Need to increase as some results can be truncated (long strings containing all gene names of a family)
    # If some warnings are printed, increase it further!
    set_options_query = "SET group_concat_max_len=400000;"
    get_all_gfs_query = ["SELECT",
                         "gf_id AS gene_family,",
                         "COUNT(gene_id) AS n_genes,",
                         "COUNT(DISTINCT species) AS n_species,",
                         "GROUP_CONCAT(DISTINCT gene_id) AS gf_genes",
                         "FROM gf_data",
                         "WHERE", "gf_id like 'HOM%'",
                         "GROUP BY gene_family"
                         ]
    get_core_gfs_query = ["SELECT gf_id FROM gf_data WHERE",
                          "species in", "(" + "\'{0}\'".format("\', \'".join(species_list)) + ")",
                          "AND gf_id like 'HOM%'",
                          "GROUP BY gf_id",
                          "HAVING COUNT(DISTINCT species) >=", str(cutoff_dict["species_perc"] * len(species_list)),
                          ]
    # Add the other cutoff criteria if they are != 0
    # BETWEEN `a` AND `b` may be a better syntax... (does not work if a>b, with a&b numerical values)
    if cutoff_dict["min_genes"] != 0:
        get_core_gfs_query.append("AND COUNT(gene_id) >= " + str(cutoff_dict["min_genes"]))
    if cutoff_dict["max_genes"] != 0:
        get_core_gfs_query.append("AND COUNT(gene_id) <= " + str(cutoff_dict["max_genes"]))
    get_all_gfs_query.append("ORDER BY n_genes DESC")
    print_log_msg(log_str="Query to execute: " + " ".join(get_core_gfs_query))  # Debug (print SQL_request)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(" ".join(get_core_gfs_query))
    core_gfs_list = [record['gf_id'] for record in ResultIter(db_cursor=cursor)]
    # sys.stderr.write(', '.join(core_gfs_list)+'\n')  # Debug
    # sys.stderr.write(str(len(core_gfs_list))+'\n')  # Debug
    cursor.execute(set_options_query)
    print_log_msg(log_str="Query to execute: " + " ".join(get_all_gfs_query))  # Debug (print SQL_request)
    cursor.execute(" ".join(get_all_gfs_query))
    # Create output
    column_names = ['gene_family', 'n_genes', 'n_species', 'weight', 'core_gf',
                    'gf_genes']  # [i[0] for i in cursor.description]
    with file_or_stdout(output_file) as out_file:
        out_file.write("\t".join(column_names) + '\n')
        for record in ResultIter(db_cursor=cursor):
            out_file.write("\t".join([
                record['gene_family'],
                str(record['n_genes']),
                str(record['n_species']),
                "{:.4f}".format(float(record['n_species']) / record['n_genes']),
                str(record['gene_family'] in core_gfs_list),
                record['gf_genes'].replace(',', '|')
            ]) + '\n')
    cursor.close()


def get_species_list_ncbi(clade, ncbi_taxonomy, db_conn):
    """Get a list of PLAZA short names belonging to an input clade name or tax_id in the NCBI taxonomy.
    Return `species_list` (list of PLAZA short names)
    `clade`: tax_id or name of clade of interest
    `ncbi_taxonomy`: NCBITaxa from ete3
    `db_conn`: connection to the PLAZA3-like reference database.
    """
    # 1. Get tax_ids that are part of the input tax_id, in the NCBI taxonomy
    tax_ids_list = ncbi_taxonomy.get_descendant_taxa(clade, intermediate_nodes=True)
    # if not tax_ids_list:
    #     sys.stderr.write('[{time}] Warning: unable to retrieve any descendant tax_ids from NCBI taxonomy for {input_tax}. \n'+
    #                      'Check on the NCBI taxonomy (https://www.ncbi.nlm.nih.gov/taxonomy)?\n').format(time= time.strftime("%H:%M:%S"), input_tax=clade)
    # Quick change: also add input tax_id to the list (maybe we deal with a species that has no descendant taxa).
    # Not needed?
    # if clade.isdigit():
    #     tax_ids_list.append(clade)
    # else:
    #     tax_ids_list.extend(ncbi_taxonomy.get_name_translator([clade])[clade])
    # tax_ids_list = set(tax_ids_list)
    # print tax_ids_list  # Debug
    # 2. Get tax_ids and names of species present in the reference database
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    get_names_tax_ids_query = "SELECT tax_id, species FROM annot_sources"
    cursor.execute(get_names_tax_ids_query)
    tax_id_name_dict = {int(record["tax_id"]): record["species"] for record in ResultIter(db_cursor=cursor)}
    # print tax_id_name_dict  # Debug
    # 3. Get intersection of list of tax_id we are interested in and what's available in the reference database
    found_tax_ids = set(tax_ids_list) & set(tax_id_name_dict.keys())
    # print found_tax_ids  # Debug
    # 4. Get and return short names corresponding to this list
    species_list = [tax_id_name_dict[tax_id] for tax_id in found_tax_ids]
    return species_list


def main(db_name, clade, username, mysql_server, output_file, min_genes, max_genes, species_perc, tax_source, gf_len):
    """Script execution"""
    # Connect to reference database
    db_ref = connect_to_db(db_user=username, db_host=mysql_server, db_name=db_name)
    # Check provided cutoff values (TODO)
    check_cutoff_values()
    cutoff_dict = {"min_genes": min_genes, "max_genes": max_genes, "species_perc": species_perc}
    # Depending on `tax_source`, perform core GF retrieval.
    if tax_source == "ncbi":
        # Importing `ete3` slows down core GF retrieval (even when it's not used) so the import statement was moved here
        from ete3 import NCBITaxa
        ete_ncbi_dbfile = None
        if os.environ.get("ETE_NCBI_DBFILE"):
            ete_ncbi_dbfile = os.environ.get("ETE_NCBI_DBFILE")
            print_log_msg(log_str="Load ETE NCBI taxonomy data from: %s" % ete_ncbi_dbfile, color="cyan")
        ncbi_taxonomy = NCBITaxa(dbfile=ete_ncbi_dbfile)
        species_list =  get_species_list_ncbi(clade=clade, ncbi_taxonomy=ncbi_taxonomy, db_conn=db_ref)
        if not species_list:
            print_log_msg(log_str="No species found for this clade, exit!", color="red")
            sys.exit(1)
        get_core_gfs(db_conn=db_ref, species_list=species_list, cutoff_dict=cutoff_dict, output_file=output_file, gf_len=gf_len)
        # get_core_gfs_legacy(db_conn=db_ref,
        #              clade_name=clade,
        #              species_list=species_list,
        #              cutoff_dict=cutoff_dict,
        #              output_file=output_file)
    if tax_source == "json":
        clades_species_dict = load_clades(db_name=db_name)
        check_clade(clade_name=clade, clade_dict=clades_species_dict)
        # Gather data!
        species_list = clades_species_dict[clade].split(',')
        get_core_gfs(db_conn=db_ref, species_list=species_list, cutoff_dict=cutoff_dict, output_file=output_file, gf_len=gf_len)
        # get_core_gfs_legacy(db_conn=db_ref,
        #              clade_name=clade,
        #              species_list=species_list,
        #              cutoff_dict=cutoff_dict,
        #              output_file=output_file)
    db_ref.close()
    print_log_msg(log_str="Core GFs retrieval finished!", color="green")


### Script execution when called from the command-line
if __name__ == '__main__':
    cmd_args = parse_arguments()
    # sys.stderr.write(str(cmd_args)+'\n')  # Debug
    main(**vars(cmd_args))
