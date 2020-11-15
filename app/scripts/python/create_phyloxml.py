"""
Create a PhyloXML tree from a newick tree, correctly formatted for use with PhyD3. This is done to color
the genes of species differently and add extra information to the tree (subset/meta-annotation).
"""

# Usage example: python create_phyloxml.py -m -s -v <exp_id> <gf_id> <trapid_db> <db_host> <db_user> <db_pswd> <tmp_dir>

import argparse
import os
import random
import sys

from cStringIO import StringIO

import MySQLdb as MS

from Bio import Phylo
from lxml import etree

import common


def parse_arguments():
    """Parse command-line arguments.

    :return: parsed arguments (Namespace object)

    """
    cmd_parser = argparse.ArgumentParser(description='Create a PhyloXML tree from a newick tree. ',
                                         formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    cmd_parser.add_argument('exp_id', type=int,
                            help='TRAPID experiment ID. ')
    cmd_parser.add_argument('gf_id', type=str,
                            help='The gene family which a PhyloXML tree is generated for. ')
    cmd_parser.add_argument('db_name', type=str,
                            help='TRAPID DB name. ')
    cmd_parser.add_argument('db_host', type=str,
                            help='TRAPID DB host. ')
    cmd_parser.add_argument('db_user', type=str,
                            help='TRAPID DB username. ')
    cmd_parser.add_argument('db_pswd', type=str,
                            help='TRAPID DB password. ')
    cmd_parser.add_argument('tmp_dir', type=str,
                            help='Temporary experiment directory. ')
    # Optional arguments
    cmd_parser.add_argument('-rh', '--ref_db_host', type=str, default=None,
                            help='Reference database host. If no value is provided, we assume the host is that same as `db_host`. ')
    cmd_parser.add_argument('-ru', '--ref_db_user', type=str, default=None,
                            help='Username to connect to the reference database. Assumed to be the same as `db_user` if no value is provided. ')
    cmd_parser.add_argument('-rp', '--ref_db_pswd', type=str, default=None,
                            help='Password to connect to the reference database. Assumed to be the same as `db_pswd` if no value is provided. ')
    # Extra information to include in the PhyloXML tree
    cmd_parser.add_argument('-s', '--include_subsets', action='store_true', default=False,
                            help='Include subset information in the generated PhyloXML tree. ')
    cmd_parser.add_argument('-m', '--include_meta_annotation', action='store_true', default=False,
                            help='Include meta-annotation in the generated PhyloXML tree. ')
    # Verbosity (for debugging purposes)
    cmd_parser.add_argument('-v', '--verbose', action='store_true', default=False,
                            help='Print debug/progress information (verbose mode). ')
    cmd_args = cmd_parser.parse_args()
    return cmd_args


def get_ref_db(exp_id, trapid_db_data, verbose=False):
    """Get reference database name used for a TRAPID experiment from TRAPID DB.

    :param exp_id: TRAPID experiment id
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: the experiment's reference database name

    """
    if verbose:
        sys.stderr.write("[Message] Retrieve reference database name of experiment '%d' from TRAPID DB. \n" % exp_id)
    query_str = "SELECT `used_plaza_database` FROM `experiments` WHERE `experiment_id`='{exp_id}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id))
    ref_db_name = cursor.fetchone()[0]
    db_conn.close()
    return ref_db_name


def get_gf_data(exp_id, gf_id, trapid_db_data, verbose=False):
    """Retrieve data for a gene family from the TRAPID database.

    :param exp_id: TRAPID experiment id
    :param gf_id: gene family for which to retrieve data
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: gene family data as a field:value dictionary

    """
    if verbose:
        sys.stderr.write("[Message] Retrieve data for GF '%s' (experiment '%d') from TRAPID DB. \n" % (gf_id, exp_id))
    query_str = "SELECT * FROM `gene_families` WHERE `experiment_id`='{exp_id}' AND `gf_id`='{gf_id}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(query_str.format(exp_id=exp_id, gf_id=gf_id))
    gf_data = cursor.fetchone()
    db_conn.close()
    if not gf_data:
        sys.stderr.write("[Error] No GF data could be retrieved (GF '%s', experiment '%d')!\n" % (gf_id, exp_id))
        sys.exit(1)
    return gf_data


def get_meta_annotation(exp_id, gf_id, trapid_db_data, verbose=False):
    """Retrieve meta-annotation for transcripts assigned to a gene family from the TRAPID database.

    :param exp_id: TRAPID experiment id
    :param gf_id: gene family containing transcripts for which to retrieve meta-annotation
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: GF transcript meta-annotation as a dictionary (transcript_id:meta_annotation)

    """
    if verbose:
        sys.stderr.write("[Message] Retrieve meta-annotation for transcripts of GF '%s' (experiment '%d'). \n" % (gf_id, exp_id))
    tr_meta_annotation = {}
    query_str = "SELECT `transcript_id`, `meta_annotation` FROM `transcripts` WHERE `experiment_id`='{exp_id}' AND `gf_id`='{gf_id}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(exp_id=exp_id, gf_id=gf_id))
    # OK to use `fetchall()` since we are getting data for a single GF only...
    for rec in cursor.fetchall():
        tr_meta_annotation[rec[0]] = rec[1]
    db_conn.close()
    if verbose:
        sys.stderr.write("[Message] Retrieved meta-annotation: %s\n" % str(tr_meta_annotation))
    return tr_meta_annotation


def get_subsets(exp_id, gf_id, trapid_db_data, verbose=False):
    """Retrieve subset information for transcripts assigned to a gene family from the TRAPID database.

    :param exp_id: TRAPID experiment id
    :param gf_id: gene family containing transcripts for which to retrieve subset information
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: transcript subset information as a dictionary (transcript_id:subsets)

    """
    if verbose:
        sys.stderr.write("[Message] Retrieve subsets for transcripts of GF '%s' (experiment '%d'). \n" % (gf_id, exp_id))
    tr_subsets = {}
    transcripts = set()
    tr_query_str = "SELECT `transcript_id` FROM `transcripts` WHERE `experiment_id`='{exp_id}' AND `gf_id`='{gf_id}';"
    subset_query_str = "SELECT `transcript_id`,`label` FROM `transcripts_labels` WHERE `experiment_id`='{exp_id}' AND `transcript_id` IN ({transcripts});"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    # Get transcript ids
    cursor.execute(tr_query_str.format(exp_id=exp_id, gf_id=gf_id))
    # OK to use `fetchall()` since we are getting data for a single GF only...
    for rec in cursor.fetchall():
        transcripts.add(rec[0])
    # Create a string for `IN` clause of SQL query
    transcripts_str = "'%s'" % "', '".join(sorted(list(transcripts)))
    # Get subset information for transcripts
    cursor.execute(subset_query_str.format(exp_id=exp_id, transcripts=transcripts_str))
    for rec in cursor.fetchall():
        if rec[0] in tr_subsets:
            tr_subsets[rec[0]].append(rec[1])
        else:
            tr_subsets[rec[0]] = [rec[1]]
    db_conn.close()
    if verbose:
        sys.stderr.write("[Message] Retrieved subset information: %s\n" % str(tr_subsets))
    return tr_subsets


def get_tax_data(ref_db_data, verbose=False):
    """Get species, common name and tax ids from the `annot_sources` table of a reference database.

    :param ref_db_data: reference database connection data (parameters for common.db_connect())
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: return fetched `annot_sources` data as a dictionary (species:{tax_id, common_name})

    """
    if verbose:
        sys.stderr.write("[Message] Retrieve taxonomy data from reference DB '%s'. \n" % ref_db_data[-1])
    tax_data = {}
    query_str = "SELECT `species`, `tax_id`, `common_name` FROM `annot_sources`;"
    db_conn = common.db_connect(*ref_db_data)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(query_str)
    for rec in cursor.fetchall():
        tax_data[rec['species']] = {'tax_id': rec['tax_id'], 'common_name': rec['common_name']}
    db_conn.close()
    return tax_data


def get_gene_species(gf_id, ref_db_data, verbose=False):
    """Retrieve the species of the members of a gene family from a reference database.

    :param gf_id: gene family for which to retrieve reference data
    :param ref_db_data: reference database connection data (parameters for common.db_connect())
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: gene family species data as a dictionary (gene:species).

    """
    ref_gf_id = gf_id.split('_')[1]
    if verbose:
        sys.stderr.write("[Message] Retrieve species for members of GF '%s' ('%s' reference DB). \n" % (ref_gf_id, ref_db_data[-1]))
    gene_species = {}
    query_str = "SELECT `gene_id`, `species` FROM `gf_data` WHERE `gf_id`='{ref_gf_id}';"
    db_conn = common.db_connect(*ref_db_data)
    cursor = db_conn.cursor(MS.cursors.DictCursor)
    cursor.execute(query_str.format(ref_gf_id=ref_gf_id))
    for rec in cursor.fetchall():
        gene_species[rec['gene_id']] = rec['species']
    db_conn.close()
    return gene_species


def get_species_color(max_range=15):
    """Pick a random color selected from a list, modify it, and return it. This function is used to assign distinctive
    colors to species represented in the tree.
    In the future it may be better to give similar colors to closely-related species.

    :param max_range: maximum allowed range for modifying RGB values (between 0 and 255)
    :return: species color in hexadecimal format

    """
    colors = [
        "0xF8EFBA", "0x9AECDB", "0xF3C300", "0x25CCF7", "0xFC427B", "0xF38400", "0xA1CAF1", "0xBE0032",
        "0x2ecc71", "0xaaaaaa", "0x1abc9c", "0xff5e57", "0x596275", "0x008856", "0xE68FAC", "0x0067A5",
        "0xF99379", "0x604E97", "0xF6A600", "0xB3446C", "0xDCD300", "0x882D17", "0x8DB600", "0x654522",
        "0xE25822", "0x2B3D26", "0x778beb", "0xe66767", "0xC2B280", "0xffcccc"]
    base_color = random.choice(colors)
    # Slightly modify the selected color (i.e. add or subtract red, green, blue)
    red = int(base_color[2:4], 16)
    green = int(base_color[4:6], 16)
    blue = int(base_color[6:], 16)
    if random.random() < 0.5:
        red = min(red + random.randint(0, max_range), 255)
    else:
        red = max(red - random.randint(0, max_range), 0)
    if random.random() < 0.5:
        green = min(green + random.randint(0, max_range), 255)
    else:
        green = max(green - random.randint(0, max_range), 0)
    if random.random() < 0.5:
        blue = min(blue + random.randint(0, max_range), 255)
    else:
        blue = max(blue - random.randint(0, max_range), 0)
    # Convert RGB values back to hexadecimal
    red = hex(red).replace("0x", "")
    green = hex(green).replace("0x", "")
    blue = hex(blue).replace("0x", "")
    final_color = "0x%s%s%s" % (red, green, blue)
    return final_color


def nw_to_phyloxml(nw_str, verbose=False):
    """Convert a tree from newick to PhyloXML format and return it as string.

    :param nw_str: newick tree string to convert to PhyloXML
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: PhyloXML tree as string

    """
    if verbose:
        sys.stderr.write("[Message] Convert Newick tree to PhyloXML. \n")
    # Read newick tree
    nw_tree = Phylo.read(StringIO(nw_str), "newick", rooted=False)
    phyloxml_handle = StringIO()
    Phylo.write(nw_tree, phyloxml_handle, 'phyloxml')
    # phyloxml_tree.close()
    phyloxml_tree = phyloxml_handle.getvalue()
    phyloxml_handle.close()
    return phyloxml_tree


def add_taxonomy(phyloxml_tree, gene_species, tax_data, verbose=False):
    """Add taxonomy information to a PhyloXML tree. In the PhyloXML tree, species code is added to 'clade' nodes, and
    taxonomy data/color is stored in the 'taxonomies' node (separate from 'phylogeny').

    :param phyloxml_tree: PhyloXML tree string
    :param gene_species: reference gene->species correspondence as retrieved from get_gene_species()
    :param tax_data: reference taxonomy data as retrieved from get_tax_data()
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: PhyloXML tree updated with taxonomy information, as string

    """
    if verbose:
        sys.stderr.write("[Message] Update PhyloXML tree with taxonomy information. \n")
    ncbi_tax_url = "https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id={tax_id}"
    updated_tree_xml = etree.XML(phyloxml_tree)
    # Add species code to parent 'clade' nodes of 'name' that are in `gene_species`
    for elmt in updated_tree_xml[0][0].iter():
        if etree.QName(elmt).localname == "name":
            if elmt.text in gene_species:
                tax_elmt = etree.Element("taxonomy")
                etree.SubElement(tax_elmt, "code").text = gene_species[elmt.text]
                parent = elmt.getparent()
                parent.append(tax_elmt)
    # Add 'taxonomies' to PhyloXML tree
    species = sorted(list(set(gene_species.values())))
    if verbose:
        sys.stderr.write("[Message] Species list: %s.\n" % ', '.join(species))
    taxonomies = etree.Element("taxonomies")
    for sp in species:
        tax_data_elmt = etree.Element("taxonomy", code=sp)
        etree.SubElement(tax_data_elmt, "color").text = get_species_color()
        etree.SubElement(tax_data_elmt, "name").text = tax_data[sp]["common_name"]
        etree.SubElement(tax_data_elmt, "url").text = ncbi_tax_url.format(tax_id=tax_data[sp]["tax_id"])
        taxonomies.append(tax_data_elmt)
    updated_tree_xml.append(taxonomies)
    updated_tree = etree.tostring(updated_tree_xml)
    return updated_tree


def add_subsets(phyloxml_tree, tr_subsets, max_subsets=7, verbose=False):
    """Add subset information to a PhyloXML tree. Subset information is encoded as 'graph' appended to the XML outside
    of the phylogeny, and values (0/1) are assigned to transcripts using an 'id' node added to clades.

    :param phyloxml_tree: PhyloXML tree string
    :param tr_subsets: transcript subset information
    :param max_subsets: maximum number of subsets added to the tree
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: PhyloXML tree updated with subset information, as string

    """
    if verbose:
        sys.stderr.write("[Message] Update PhyloXML tree with subset information. \n")
    # A list of 'colorblindness-safe' colors (Okabe-Ito). If we want to display more subsets we'll need more colors!
    subset_colors = ["0xe69f00", "0x56b4e9", "0x009e73", "0xf0e442", "0x0072b2", "0xd55e00", "0xcc79a7"]
    subset_trs = {}    # subset->transcript mapping
    clade_idx = 0      # index to give to clades in the tree
    tr_clade_idx = {}  # clade_idx->transcript mapping
    updated_tree_xml = etree.XML(phyloxml_tree)
    # Get reverse transcript-subset mapping
    for tr, subsets in tr_subsets.items():
        for subset in subsets:
            if subset not in subset_trs:
                subset_trs[subset] = set([tr])
            else:
                subset_trs[subset].add(tr)
    # Add 'id' node to clades (needed to link clades to presence/absence of a subset)
    for elmt in updated_tree_xml[0][0].iter():
        if etree.QName(elmt).localname == "clade":
            etree.SubElement(elmt, "id").text = str(clade_idx)
            clade_idx += 1
    # Retrieve IDs corresponding to transcripts in that are in subsets
    for elmt in updated_tree_xml[0][0].iter():
        if etree.QName(elmt).localname == "name":
            if elmt.text in tr_subsets:
                parent = elmt.getparent()
                for node in parent:
                    if etree.QName(node).localname == "id":
                        tr_clade_idx[node.text] = elmt.text
    # Add subset information to PhyloXML tree
    # Create legend
    graph_legend = etree.Element("legend", show="1")
    for subset in sorted(subset_trs)[0:max_subsets]:
        subset_field = etree.Element("field")
        etree.SubElement(subset_field, "name").text = subset
        etree.SubElement(subset_field, "color").text = subset_colors[sorted(subset_trs).index(subset)]
        etree.SubElement(subset_field, "shape").text = "circle"
        graph_legend.append(subset_field)
    # Create data
    graph_data = etree.Element("data")
    for clade_idx, tr in sorted(tr_clade_idx.items()):
        clade_values = etree.Element("values", {"for": clade_idx})
        # 1 for subsets the transcript is in, 0 otherwise
        for subset in sorted(subset_trs)[0:max_subsets]:
            if tr in subset_trs[subset]:
                etree.SubElement(clade_values, "value").text = "1"
            else:
                etree.SubElement(clade_values, "value").text = "0"
        graph_data.append(clade_values)
    # Create 'graphs' node
    graphs = etree.Element("graphs")
    graph = etree.Element("graph", type="binary")
    etree.SubElement(graph, "name").text = "Transcript subsets"
    # Append legend/data to `graph`, then append `graphs` to the rest of the tree
    graph.append(graph_legend)
    graph.append(graph_data)
    graphs.append(graph)
    updated_tree_xml.append(graphs)
    # Convert updated PhyloXML tree back to string and return it
    updated_tree = etree.tostring(updated_tree_xml)
    return updated_tree


def add_meta_annotation(phyloxml_tree, tr_meta_annotation, verbose=False):
    """Add meta-annotation to a PhyloXML tree. Meta-annotation information is encoded as color label ('colortag') added
    to nodes corresponding to transcripts.

    :param phyloxml_tree: PhyloXML tree string
    :param tr_meta_annotation: transcript meta-annotation
    :param verbose: whether to be verbose (print extra information to stderr if set to True)
    :return: PhyloXML tree updated with meta-annotation data, as string

    """
    if verbose:
        sys.stderr.write("[Message] Update PhyloXML tree with meta-annotation. \n")
    # Colors for meta-annotation labels range from dark blue ('Partial') to green ('Full Length'), with grey
    # corresponding to 'No Information'.
    meta_annotation_colors = {"Partial": "0x33638d", "Quasi Full Length": "0x1f968b", "Full Length": "0x73d055",
                              "No Information": "0xcccccc"}
    updated_tree_xml = etree.XML(phyloxml_tree)

    # Create 'labels' node -- The way it's done can probably be improved, but it's the first time I am ever using `lxml`
    labels = etree.Element("labels")
    color_tag = etree.Element("label", type="color")
    etree.SubElement(color_tag, "name").text = "Meta-annotation"
    color_tag.append(etree.Element("data", tag="colortag"))
    labels.append(color_tag)
    updated_tree_xml.append(labels)
    # Update nodes corresponding to transcripts with 'colortag' corresponding to their meta-annotation
    for elmt in updated_tree_xml[0][0].iter():
        if etree.QName(elmt).localname == "name":
            if elmt.text in tr_meta_annotation:
                color_value = meta_annotation_colors[tr_meta_annotation[elmt.text]]
                etree.SubElement(elmt.getparent(), "colortag").text = color_value
    updated_tree = etree.tostring(updated_tree_xml)
    return updated_tree


def upload_phyloxml_tree(exp_id, gf_id, phyloxml_tree, trapid_db_data, verbose=False):
    """Upload a PhyloXML tree generated for a gene family to TRAPID db.

    :param exp_id: TRAPID experiment id
    :param gf_id: gene family for which a PhyloXML tree is generated
    :param phyloxml_tree: PhyloXML tree string
    :param trapid_db_data: TRAPID database connection data (parameters for common.db_connect())
    :param verbose: whether to be verbose (print extra information to stderr if set to True)

    """
    if verbose:
        sys.stderr.write("[Message] Upload PhyloXML tree for GF '%s' (experiment '%d'). \n" % (gf_id, exp_id))
    # Minify XML tree string
    minified_tree_xml = etree.XML(phyloxml_tree, parser=etree.XMLParser(remove_blank_text=True))
    minified_tree_str = etree.tostring(minified_tree_xml)
    # Upload minified XML tree
    query_str = "UPDATE `gene_families` SET `xml_tree`='{xml_str}' WHERE `experiment_id`='{exp_id}' AND `gf_id`='{gf_id}';"
    db_conn = common.db_connect(*trapid_db_data)
    cursor = db_conn.cursor()
    cursor.execute(query_str.format(xml_str=minified_tree_str, exp_id=exp_id, gf_id=gf_id))
    db_conn.commit()
    db_conn.close()


def main():
    cmd_args = parse_arguments()
    # Update reference DB data if needed (i.e. replace 'None' values)
    if not cmd_args.ref_db_host:
        cmd_args.ref_db_host = cmd_args.db_host
    if not cmd_args.ref_db_user:
        cmd_args.ref_db_user = cmd_args.db_user
    if not cmd_args.ref_db_pswd:
        cmd_args.ref_db_pswd = cmd_args.db_pswd
    # Check if `tmp_dir` exists
    if not os.path.exists(cmd_args.tmp_dir):
        sys.stderr.write("[Error] Directory '%s' was not found. Exit. \n" % cmd_args.tmp_dir)
        sys.exit(1)
    # List containing all needed parameters for `common.db_connect()` (TRAPID DB)
    trapid_db_data = [cmd_args.db_user, cmd_args.db_pswd, cmd_args.db_host, cmd_args.db_name]
    # Get ref. DB name for the exeperiment
    ref_db_name = get_ref_db(cmd_args.exp_id, trapid_db_data, cmd_args.verbose)
    # List containing all needed parameters for `common.db_connect()` (reference DB)
    reference_db_data = [cmd_args.ref_db_user, cmd_args.ref_db_pswd, cmd_args.ref_db_host, ref_db_name]
    # Retrieve GF data
    gf_data = get_gf_data(cmd_args.exp_id, cmd_args.gf_id, trapid_db_data, cmd_args.verbose)
    # Create initial PhyloXML tree (get string)
    phyloxml_tree = nw_to_phyloxml(gf_data['tree'], cmd_args.verbose)
    # Add taxonomy information/colors
    gene_species = get_gene_species(cmd_args.gf_id, reference_db_data, cmd_args.verbose)
    tax_data = get_tax_data(reference_db_data, cmd_args.verbose)
    phyloxml_tree = add_taxonomy(phyloxml_tree, gene_species, tax_data, cmd_args.verbose)
    # If needed, retrieve and add subset/meta-annotation information
    if cmd_args.include_meta_annotation:
        tr_meta_annotation = get_meta_annotation(cmd_args.exp_id, cmd_args.gf_id, trapid_db_data, cmd_args.verbose)
        if tr_meta_annotation:
            phyloxml_tree = add_meta_annotation(phyloxml_tree, tr_meta_annotation, cmd_args.verbose)
    if cmd_args.include_subsets:
        tr_subsets = get_subsets(cmd_args.exp_id, cmd_args.gf_id, trapid_db_data, cmd_args.verbose)
        if tr_subsets:
            phyloxml_tree = add_subsets(phyloxml_tree, tr_subsets, verbose=cmd_args.verbose)
    # Upload PhyloXML tree to database
    upload_phyloxml_tree(cmd_args.exp_id, cmd_args.gf_id, phyloxml_tree, trapid_db_data, cmd_args.verbose)
    sys.stderr.write("[Message] Created PhyloXML tree for GF '%s'!\n" % cmd_args.gf_id)


if __name__ == '__main__':
    main()
