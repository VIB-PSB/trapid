#!/usr/bin/python
"""
A quick script I used to produce HTML tables with missing core GFs and linkout to (pico)PLAZA to allow easy exploration.
"""

# Usage: ./plaza_linkout_table.py <core_gf_analysis_results.tsv> > <output>
# This is just an example, modify the prefix used for links and the content of the generated HTML to suit your needs.

import pandas as pd
import sys

if len(sys.argv[1:]) < 1:
    sys.stderr.write(
        "Please provide an input file. Usage: ./plaza_linkout_table.py <core_gf_analysis_results.tsv> > <output>\n")
    sys.exit(1)

pd.set_option('display.max_colwidth', -1)
# Prefix used to build links in the output table. Don't forget the trailing slash.
PLAZA_LINK = "http://bioinformatics.psb.ugent.be/plaza/versions/pico-plaza/gene_families/view/"


def results_to_html_table(results_file, link_prefix):
    """Read core gf analysis results and return corresponding HTML table. """
    results_df = pd.read_csv(results_file, sep='\t', comment='#')
    # print results_df['Gene Family'][0]  # Debug
    results_df = results_df.rename(
        columns={'missing_gf': 'Gene Family', 'n_genes': '# genes', 'n_species': '# species', 'gf_weight': 'GF weight'})
    results_df = results_df.sort_values(by=['GF weight'], ascending=[True])
    results_df['Gene Family'] = [
        '<a href=\'' + link_prefix + gf + '\' target=\'_blank\' title=\'See this GF on pico-PLAZA 02\'>' + gf + '</a>'
        for gf in results_df['Gene Family']]
    html_results = results_df.to_html(index=False, classes=['table', 'table-striped', 'table-hover', 'table-bordered'],
                                      escape=False, border=0)
    return html_results


def output_html_page(html_results):
    """Print the full output HTML page (i.e. table + everything around it). """
    print """<html><head>
    <title>Missing Chlorophyta gene families | pse3</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
    <!-- JS libs insert (at the end would be better) -->
    <!-- JQuery -->
    <script src="http://code.jquery.com/jquery-1.11.3.min.js" type="text/javascript"></script>
    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js" type="text/javascript"></script>
    <div class="container"><div class="page-header">
    <h1>Missing <em>Chlorophyta</em> core GFs <small>Picochlorum sp. SENEW3 (SE3) [pse3]</small></h1>
    </div>
    <p class="text-justify">Clicking on a GF identifier will open the corresponding GF page on pico-PLAZA. </p>
    """
    print html_results
    print """</div></body></html>"""
    return None


### Script execution
if __name__ == '__main__':
    core_gfs_analysis_results = sys.argv[1]
    html_results=results_to_html_table(results_file=core_gfs_analysis_results, link_prefix=PLAZA_LINK)
    output_html_page(html_results=html_results)
