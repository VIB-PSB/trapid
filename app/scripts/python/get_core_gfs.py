#!/usr/bin/python
"""
Retrieve core gene families for a given clade, from a PLAZA3-like database. Now also works with NCBI tax_id/clade name
as input.
"""

# Usage: ./get_core_gfs.py -u <mysql_user> -h <host_name> -sp <species_threshold> -o <output_name> <db_name> <clade_name>

# Import modules
import argparse
import MySQLdb as MS
import sys
import os
import json
import getpass
import time

### Command-line arguments

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


### Functions
def connect_to_db(db_user, db_host, db_name):
    """"Connection to the database. Return a database connection. """
    try:
        # If password is not set as environment variable, prompt the user for it
        if os.environ.get('DB_PWD') is not None:
            sys.stderr.write('[' + time.strftime(
                "%H:%M:%S") + '] Password provided as environment variable (be careful with that). \n')
            db_pwd = os.environ.get('DB_PWD')
        else:
            sys.stderr.write('[' + time.strftime(
                "%H:%M:%S") + '] Password not provided as environment variable (set $DB_PWD to avoid typing it each time). \n')
            db_pwd = getpass.getpass(prompt='Password for user ' + db_user + '@' + db_host + ':')
        db_conn = MS.connect(host=db_host, user=db_user, passwd=db_pwd, db=db_name)
        sys.stderr.write(
            '[' + time.strftime("%H:%M:%S") + '] Connected to database ' + db_name + ' (host: ' + db_host + ')\n')
    except Exception as e:
        sys.stderr.write('[' + time.strftime(
            "%H:%M:%S") + '] Error: impossible to connect to database ' + db_name + ' (host: ' + db_host + '). \n')
        sys.stderr.write(str(e) + '\n')
        sys.exit(1)
    return db_conn


# Handy iterator to fetch large amounts of data without bloating memory uselessly
def ResultIter(db_cursor, arraysize=1000):
    """An iterator that uses `fetchmany` (keep memory usage down, faster than `fetchall`)."""
    while True:
        # sys.stderr.write('['+time.strftime("%H:%M:%S")+'] Fetching '+str(arraysize)+' more...\n')
        results = db_cursor.fetchmany(arraysize)
        if not results:
            break
        for result in results:
            yield result


# Kind of stupid function that loads a species dict according to the selected database.
def load_clades(db_name):
    """Load clade-species JSON file depending on the selected database. Return clades-species dictionary. """
    if 'plaza_pico_02' in db_name:
        # sys.stderr.write('['+time.strftime("%H:%M:%S")+'] Load clades for pico-PLAZA 2\n')
        clades_species_path = os.path.join(os.path.dirname(__file__), 'data', 'plaza_02_pico_species_dict.json')
    elif 'plaza_dicots_03' in db_name:
        # sys.stderr.write('['+time.strftime("%H:%M:%S")+'] Load clades for PLAZA 3 dicots\n')
        clades_species_path = os.path.join(os.path.dirname(__file__), 'data', 'plaza_03_dicots_species_dict.json')
    elif 'plaza_monocots_03' in db_name:
        # sys.stderr.write('['+time.strftime("%H:%M:%S")+'] Load clades for PLAZA 3 monocots\n')
        clades_species_path = os.path.join(os.path.dirname(__file__), 'data', 'plaza_03_monocots_species_dict.json')
    elif 'plaza_02_5' in db_name:
        # sys.stderr.write('['+time.strftime("%H:%M:%S")+'] Load clades for PLAZA 2.5\n')
        clades_species_path = os.path.join(os.path.dirname(__file__), 'data', 'plaza_02_5_species_dict.json')
    else:
        sys.stderr.write('[' + time.strftime(
            "%H:%M:%S") + '] Error: no clades/species dictionary for the database you selected (probably because of an incorrect `db_name`). Cannot go further. \n')
        sys.exit(1)
    if not os.path.exists(clades_species_path):
        sys.stderr.write('[' + time.strftime(
            "%H:%M:%S") + '] Error: Couldn\'t find the clades/species dictionary (normally in ./data). \n')
        sys.exit(1)
    try:
        clades_species_dict = json.loads(open(clades_species_path, 'r').read())
        sys.stderr.write('[' + time.strftime("%H:%M:%S") + '] Loaded clades data (' + clades_species_path + ').\n')
    except:
        sys.stderr.write('[' + time.strftime(
            "%H:%M:%S") + '] Error: Badly formatted JSON file (' + clades_species_dict + ')... Regenerate it!\n')
        sys.exit(1)
    return clades_species_dict


def check_clade(clade_name, clade_dict):
    """Check if a clade name is valid or not. """
    if clade_name not in clade_dict.keys():
        sys.stderr.write('[' + time.strftime(
            "%H:%M:%S") + '] Error: ' + clade_name + ' is not a valid clade. Correct values: ' + ', '.join(
            clade_dict.keys()) + '\n')
        sys.exit(1)


def check_cutoff_values():
    """Check if user-specified cutoff values make sense."""
    sys.stderr.write("[" + time.strftime("%H:%M:%S") + "] Cutoff criteria are not checked yet! TODO!\n")
    return None


# Main function (SQL query, weighting and processing and creation of output core GFs table)
def get_core_gfs(db_conn, clade_name, species_list, cutoff_dict, output_file):
    """Retrieve GFs/core GFs from MySQLdatabase and create output.
    Output format: GF|#genes|#species|weight|is_core_gf|genes """
    # Output: GF|#genes|#species|weight|genes
    sys.stderr.write('[' + time.strftime("%H:%M:%S") + '] Retrieve core gene families. \n')
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
    sys.stderr.write("Query to execute: " + " ".join(get_core_gfs_query) + '\n')  # Debug (print SQL_request)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(" ".join(get_core_gfs_query))
    core_gfs_list = [record['gf_id'] for record in ResultIter(db_cursor=cursor)]
    # sys.stderr.write(', '.join(core_gfs_list)+'\n')  # Debug
    # sys.stderr.write(str(len(core_gfs_list))+'\n')  # Debug
    cursor.execute(set_options_query)
    sys.stderr.write("Query to execute: " + " ".join(get_all_gfs_query) + '\n')  # Debug (print SQL_request)
    cursor.execute(" ".join(get_all_gfs_query))
    # Create output
    column_names = ['gene_family', 'n_genes', 'n_species', 'weight', 'core_gf',
                    'gf_genes']  # [i[0] for i in cursor.description]
    if output_file is not None:
        with open(output_file, 'wb') as out_file:
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
    else:
        sys.stdout.write("\t".join(column_names) + '\n')
        for record in ResultIter(db_cursor=cursor):
            sys.stdout.write("\t".join([
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


def main(db_name, clade, username, mysql_server, output_file, min_genes, max_genes, species_perc, tax_source):
    """Script execution"""
    # Connect to reference database
    db_ref = connect_to_db(db_user=username, db_host=mysql_server, db_name=db_name)
    # Check provided cutoff values (TODO)
    check_cutoff_values()
    # Depending on `tax_source`, perform core GF retrieval.
    if tax_source == "ncbi":
        # TODO: move import somewhere else?
        from ete3 import NCBITaxa
        ncbi_taxonomy = NCBITaxa(dbfile="/www/blastdb/biocomp/moderated/trapid_02/.etetoolkit/taxa.sqlite")  # HARDCODED LOCATION OF ETE3 TAXONOMY DB FILE
        species_list =  get_species_list_ncbi(clade=clade, ncbi_taxonomy=ncbi_taxonomy, db_conn=db_ref)
        get_core_gfs(db_conn=db_ref,
                     clade_name=clade,
                     species_list=species_list,
                     cutoff_dict={"min_genes": min_genes, "max_genes": max_genes,
                                  "species_perc": species_perc},
                     output_file=output_file)
    if tax_source == "json":
        clades_species_dict = load_clades(db_name=db_name)
        check_clade(clade_name=clade, clade_dict=clades_species_dict)
        # Gather data!
        species_list = clades_species_dict[clade].split(',')
        get_core_gfs(db_conn=db_ref,
                     clade_name=clade,
                     species_list=species_list,
                     cutoff_dict={"min_genes": min_genes, "max_genes": max_genes,
                                  "species_perc": species_perc},
                     output_file=output_file)
    sys.stderr.write('[' + time.strftime("%H:%M:%S") + '] Core GFs retrieval finished!\n')


### Script execution when called from the command-line
if __name__ == '__main__':
    cmd_args = cmd_parser.parse_args()
    # sys.stderr.write(str(cmd_args)+'\n')  # Debug
    main(db_name=cmd_args.db_name, clade=cmd_args.clade, username=cmd_args.username,
         mysql_server=cmd_args.mysql_server, output_file=cmd_args.output_file, min_genes=cmd_args.min_genes,
         max_genes=cmd_args.max_genes, species_perc=cmd_args.species_perc, tax_source=cmd_args.tax_source)
