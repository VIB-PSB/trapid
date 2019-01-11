#!/usr/bin/python
"""
Retrieve core gene families for a given clade, from EggNOG TRAPID's reference database. Simply a quick protoype now.
"""

# Usage: ./get_core_gfs_eggnog.py -u <mysql_user> -h <host_name> -sp <species_threshold> -o <output_name> <db_name> <nog_name>

# Import modules
import argparse
import MySQLdb as MS
import sys
import os
import json
import getpass
import time
from ete3 import NCBITaxa
from common import *

### Command-line arguments

cmd_parser = argparse.ArgumentParser(
    description='A script to get core gene families for a given clade, from a  EggNOG TRAPID\'s reference database. ',
    formatter_class=argparse.ArgumentDefaultsHelpFormatter)
# Positional arguments
cmd_parser.add_argument('db_name',
                        help='Name of  EggNOG TRAPID\'s ref DB which you want to work with. ')
cmd_parser.add_argument('clade',
                        help='Retrieve core GFs for which clade? Must be either a clade name/tax_id corresponding to a NOG level, or a NOG level itself. ')
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

# Just retrieve taxonomy from the DB itself?
# cmd_parser.add_argument('--tax_source', dest='tax_source', choices=['ncbi', 'json'],
#                         help='''The source from which to get tax_ids/organisms belonging to your clade of interest.
#                         `ncbi`: from the NCBI taxonomy (with ete-toolkit), a tax_id or a clade name can be used as `clade`.
#                         `json`: from the species trees (of various PLAZA versions), based on the JSON files in `data`.
#                         If this option is selected only a clade name (present in the JSON files) can be used as `clade`. ''',
#                         default='ncbi')


### Functions
def check_cutoff_values():
    """Check if user-specified cutoff values make sense. """
    print_log_msg(log_str="Cutoff criteria are not checked yet! TODO!")


def get_target_nog(db_conn, query_str):
    """Get NOG we want to use for core GFs retrieval (taxonomic level), based on a query string (`query_str`), that
    can be a tax id, the NOG name itself or the scientific name of the clade. """
    # Retrieve all NOG levels data
    get_nog_levels_query = "SELECT * FROM `taxonomic_levels`;"
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(get_nog_levels_query)
    all_nog_levels = {nog['scope']: {
        "tax_id": str(nog["tax_id"]),
        "species": set(nog["species"].split(",")),
        "name": nog["name"],
        "sp_core_count": nog["sp_core_count"],
        "sp_periphery_count": nog["sp_periphery_count"]
        }
        for nog in ResultIter(db_cursor=cursor)
    }
    # Retrieve NOG corresponding to query clade
    chosen_nog = None
    if query_str.isdigit():
        if query_str in [all_nog_levels[nog]["tax_id"] for nog in all_nog_levels]:
            chosen_nog = [nog for nog in all_nog_levels if all_nog_levels[nog]["tax_id"] == query_str][0]
    elif query_str.lower() in [nog.lower() for nog in all_nog_levels]:
        chosen_nog = query_str
    else:
        # Get corresponding tax_id from NCBI taxonomy
        ncbi_taxonomy = NCBITaxa(dbfile="/www/blastdb/biocomp/moderated/trapid_02/.etetoolkit/taxa.sqlite")  # HARDCODED LOCATION OF ETE3 TAXONOMY DB FILE
        # tax_id = '0'
        try:
            tax_id = ncbi_taxonomy.get_name_translator([query_str]).values()[0][0]  # What if two tax_ids correspond to it?
            tax_id = str(tax_id)
        except:
            print_log_msg(log_str='Error: invalide clade name: \'%s\'.' % query_str, color='red')
            raise
        # Try getting corresponding NOG
        if tax_id in [all_nog_levels[nog]["tax_id"] for nog in all_nog_levels]:
            chosen_nog = [nog for nog in all_nog_levels if all_nog_levels[nog]["tax_id"] == tax_id][0]
    if not chosen_nog:
        print_log_msg(log_str='Error: impossible to find suitable NOG for clade \'%s\'.' % query_str, color='red')
        sys.exit(1)
    return {chosen_nog: all_nog_levels[chosen_nog]}


# Main function (SQL query, weighting and processing and creation of output core GFs table)
def get_core_gfs_nog(db_conn, cutoff_dict, output_file, target_nog):
    """Retrieve GFs/core GFs from MySQLdatabase and create output.
    Output format: GF|#genes|#species|weight|is_core_gf|genes """
    # Output: GF|#genes|#species|weight|genes
    print_log_msg(log_str='Retrieve core gene families.')
    nog_str = target_nog.keys()[0]
    # No adherent species for max species count!
    max_species = int(target_nog[nog_str]["sp_core_count"]) + int(target_nog[nog_str]["sp_periphery_count"])
    # Create SQL queries
    # Since we have the number of species represented, and we are we can get core GFs from this query only?
    get_all_gfs_query = "SELECT gf_id, method FROM gene_families WHERE scope=\'{scope}\' ORDER BY gf_id asc;"
    get_gf_genes_query = "SELECT gf_id, gene_id FROM gf_data WHERE scope=\'{scope}\';"
    # Add the other cutoff criteria if they are != 0. Outdated code!!
    # BETWEEN `a` AND `b` may be a better syntax... (does not work if a>b, with a&b numerical values)
    # if cutoff_dict["min_genes"] != 0:
    #     get_core_gfs_query.append("AND COUNT(gene_id) >= " + str(cutoff_dict["min_genes"]))
    # if cutoff_dict["max_genes"] != 0:
    #     get_core_gfs_query.append("AND COUNT(gene_id) <= " + str(cutoff_dict["max_genes"]))
    # get_all_gfs_query.append("ORDER BY n_genes DESC")
    # sys.stderr.write("Query to execute: " + " ".join(get_core_gfs_query) + '\n')  # Debug (print SQL request)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    print_log_msg(log_str="Query to execute: " + get_gf_genes_query.format(scope=target_nog.keys()[0]))  # Debug (print SQL request)
    cursor.execute(get_gf_genes_query.format(scope=target_nog.keys()[0]))
    gf_genes = {}
    for record in ResultIter(db_cursor=cursor):
        if record["gf_id"] not in gf_genes:
            gf_genes[record["gf_id"]] = set([record["gene_id"]])
        else:
            gf_genes[record["gf_id"]].add(record["gene_id"])
    # Reformat retrieved data (create GF members strings)
    for gf_id in gf_genes:
        gf_genes[gf_id] = "|".join(sorted(list(gf_genes[gf_id])))
    print_log_msg(log_str="Query to execute: " + get_all_gfs_query.format(scope=target_nog.keys()[0]))  # Debug (print SQL request)
    cursor.execute(get_all_gfs_query.format(scope=target_nog.keys()[0]))
    gf_list = []
    for record in ResultIter(db_cursor=cursor):
        gf_id = record["gf_id"]
        n_species = int(record['method'].split('|')[1].split(',')[0].split(':')[1])
        n_genes = int(record['method'].split('|')[1].split(',')[1].split(':')[1])
        weight = "{:.4f}".format(float(n_species) / n_genes)
        core_gf = n_species > max_species * cutoff_dict['species_perc']
        gf_list.append([gf_id, n_genes, n_species, weight, core_gf, gf_genes[gf_id]])
    # print max(n_species)
    # print len(target_nog[target_nog.keys()[0]]['species'])
    # Create output
    column_names = ['gene_family', 'n_genes', 'n_species', 'weight', 'core_gf',
                    'gf_genes']  # [i[0] for i in cursor.description]
    with file_or_stdout(output_file) as out_file:
        out_file.write("\t".join(column_names) + '\n')
        for gf in gf_list:
            out_file.write("\t".join([str(elmt) for elmt in gf]) + '\n')
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


def main(db_name, clade, username, mysql_server, output_file, min_genes, max_genes, species_perc):  # , tax_source):
    """Script execution"""
    # Connect to reference database
    db_ref = connect_to_db(db_user=username, db_host=mysql_server, db_name=db_name)
    # Check provided cutoff values (TODO)
    check_cutoff_values()
    target_nog = get_target_nog(db_conn=db_ref, query_str=clade)
    get_core_gfs_nog(db_conn=db_ref,
                 cutoff_dict={"min_genes": min_genes, "max_genes": max_genes,
                              "species_perc": species_perc},
                 output_file=output_file,
                 target_nog=target_nog)
    print_log_msg(log_str='Core GFs retrieval finished!', color='green')


### Script execution when called from the command-line
if __name__ == '__main__':
    cmd_args = cmd_parser.parse_args()
    # sys.stderr.write(str(cmd_args)+'\n')  # Debug
    main(db_name=cmd_args.db_name, clade=cmd_args.clade, username=cmd_args.username,
         mysql_server=cmd_args.mysql_server, output_file=cmd_args.output_file, min_genes=cmd_args.min_genes,
         max_genes=cmd_args.max_genes, species_perc=cmd_args.species_perc)  # , tax_source=cmd_args.tax_source)
