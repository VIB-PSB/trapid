[![Bio.tools](https://img.shields.io/badge/Bio.tools-TRAPID-orange.svg)](https://bio.tools/trapid)

# TRAPID 2.0

TRAPID 2.0 is a web application for the annotation and exploration of *de novo* transcriptomes, available [here](http://bioinformatics.psb.ugent.be/trapid). 


## Documentation

* [General documentation](http://bioinformatics.psb.ugent.be/trapid_02/documentation/general) 
* [Tutorials](http://bioinformatics.psb.ugent.be/trapid_02/documentation/tutorial)
* [FAQ](http://bioinformatics.psb.ugent.be/trapid_02/documentation/faq)
* [Overview of all used third-party tools](http://bioinformatics.psb.ugent.be/trapid_02/documentation/tools_parameters)
    

## Requirements 

All you need to use TRAPID 2.0 is any modern web browser with JavaScript enabled. 

TRAPID 2.0 is freely accessible for academic use.  If you have a commercial interest in the platform, or would like to use TRAPID for commercial purposes, please [contact us](http://bioinformatics.psb.ugent.be/trapid_02/documentation/contact).


## Support 

If you experience a bug, or if you have any question, remark or suggestion, please [contact us](http://bioinformatics.psb.ugent.be/trapid_02/documentation/contact).


## Installation 

The public instance of TRAPID 2.0 is available [here](http://bioinformatics.psb.ugent.be/trapid). Follow the below instructions in case you want to install TRAPID 2.0. 


### Hardware requirements 

1. MySQL server (5.7.17 or higher) with sufficient storage 
2. PHP web server  (e.g. Apache)
3. Sufficient storage for resources (e.g. Kaiju and DIAMOND indices, NCBI taxonomy files, Rfam data) and experiment temporary data. 


### Software requirements

1. General:
    * PHP (7 or higher)
    * Perl (5.14 or higher), with the [Inifiles](https://metacpan.org/pod/Config::IniFiles) module installed. 
    * Python 2.7 (for now!). Python module dependencies are listed in `app/scripts/python/requirements.txt` and can be installed via pip (e.g. `pip install -r requirements.txt`). 
    * SunGridEngine (SGE) for the computing cluster on which TRAPID jobs run
    * Java (1.6 or higher)

2. Initial processing: 
    * [Kaiju](https://github.com/bioinformatics-centre/kaiju)
    * [DIAMOND](https://github.com/bbuchfink/diamond)
    * [Infernal](https://github.com/EddyRivasLab/infernal)
    * [eggNOG-mapper](https://github.com/eggnogdb/eggnog-mapper) version 1

3. Multiple sequence alignments: 
    * [MAFFT](https://mafft.cbrc.jp/alignment/software/source.html)
    * [MUSCLE](https://www.drive5.com/muscle/)

4. Phylogeny: 
    * [FastTree2](http://www.microbesonline.org/fasttree/#Install)
    * [IQ-TREE](https://github.com/Cibiv/IQ-TREE)
    * [PhyML](https://github.com/stamatak/standard-RAxML)
    * [RaxML](http://www.atgc-montpellier.fr/phyml/)

**Note:** the exact versions of the third-party tools used within TRAPID can be found in the [documentation](http://bioinformatics.psb.ugent.be/trapid_02/documentation/tools_parameters). 


### Databases

This repository contains the web application and the data processing code (see `app/scripts/`). TRAPID additionally requires at least two MySQL databases to run: 

1. The TRAPID database, that stores experiment data (e.g. transcripts, functional annotation, taxonomy, gene/RNA families, ...), taxonomy data, and other configuration data. 
2. A reference database (or more), that stores biological sequences,  functional annotation, and gene family information for a set of reference species. The reference database is used throughout the web application as well as during the initial processing phase to derive gene family and functional annotations for the processed transcripts (in the case of eggNOG, eggNOG-mapper is used for these two last steps instead). 

SQL schemes and example data for the required databases can be found [here](https://ftp.psb.ugent.be/pub/trapid/src/trapid_02_db_examples.tar.gz) (TRAPID FTP).

### Installation steps 

1. Install all the programs listed in the `Software requirements` section.
2. Create the TRAPID and the reference database (here corresponding to eggNOG 4.5), and use the SQL dumps to create and populate the tables. 
    * `trapid_db_dump.sql`: TRAPID database. Note that the value in `db_name` (`data_sources` table) must match the name given to the reference database. 
    * `reference_db_eggnog_dump.sql`: reference database (eggNOG 4.5 data).
    * Create database account(s) used to access the databases. 
3. Create or download necessary files/resources: 
    * DIAMOND indices, based on the `annotation` table of the reference database. 
    * Kaiju index and accompanying NCBI taxonomy data.
    * Rfam [library of CMs](https://ftp.ebi.ac.uk/pub/databases/Rfam/14.1/Rfam.cm.gz) and [clan information](https://ftp.ebi.ac.uk/pub/databases/Rfam/14.1/Rfam.clanin) files (links for version 14.1). 
4. Clone or download the repository. 
5. Change configuration: 
    * Create and edit all necessary INI files in the `app/scripts/ini_files`, starting from the available templates (`*.default`, to rename as `*.ini`). 
    * Change the database configuration in `app/config/database.php`. 
6. Edit the `.htaccess` file in the root directory as appropriate.
