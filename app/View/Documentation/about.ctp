<div class="container">
    <div class="page-header">
        <h1 class="text-primary">About</h1>
    </div>
    <section class="page-section">
        <h2>Licensing</h2>
        <p class="text-justify">The TRAPID platform is currently only freely accessible for academic use.
            If you have a commercial interest in the platform, or would like to use TRAPID for commercial purposes,
            please contact <a href='http://bioinformatics.psb.ugent.be/cnb/people' target="_blank" class="linkout">Klaas Vandepoele</a>.
    </section>

    <section class="page-section">
        <h2>Citing TRAPID</h2>
        <p class="text-justify">In case you publish results generated using TRAPID, please cite this paper:</p>

        <?php echo $this->element("doc_paper",  array("title"=>"TRAPID 2.0: a web application for taxonomic and functional analysis of <em>de novo</em> transcriptomes", "authors"=>"Francois Bucchini, Andrea Del Cortona, Łukasz Kreft, Alexander Botzki, Michiel Van Bel, Klaas Vandepoele", "url"=>"https://doi.org/10.1093/nar/gkab565", "journal"=>"Nucleic Acids Research, 01 July, 2021")); ?>

    </section>

    <section class="page-section">
        <h2>Authors</h2>
        <p class="text-justify">This tool is developed and maintained by François Bucchini, Michiel Van Bel, and Klaas Vandepoele.</p>
    </section>

    <section class="page-section">
        <h2>The CNB research group</h2>
        <p class="text-justify">The <strong>Comparative Network Biology (CNB) group </strong> (led by Klaas
            Vandepoele and located at VIB-Ghent) is interested in extracting biological knowledge from large-scale experimental data sets
            using data integration, comparative sequence & expression analysis, and network biology, in plants, green algae and diatoms.
        </p>
        <p class="text-justify">If you wish to learn more, you may be interested by taking a look at the <a
                    href="http://bioinformatics.psb.ugent.be/cnb/" class='linkout' target="about:blank">group's website</a>.</p>
    </section>

    <section class="page-section">
        <h2>TRAPID privacy policy</h2>
        <section class="page-section-sm">
            <p class='text-justify'>See <a href='https://en.wikipedia.org/wiki/General_Data_Protection_Regulation' target='_blank' class="linkout">Wikipedia</a> and the <a href='https://gdpr.eu/' target='_blank' class="linkout">website from the EU</a>
                    for more information about what the General Data Protection Regulation (GDPR) is, and how it may affect you.
            </p>
            <p class="text-justify">TRAPID is a platform developed within <a href='https://www.ugent.be/en' target='_blank' class='linkout'>Ghent University</a> and <a href='http://www.vib.be/' target='_blank' class='linkout'>VIB</a>, aiming to assist the analysis and exploration of <em>de novo</em> transcriptomes. The below privacy policy explains how TRAPID uses the personal data we collect from you when you use the web application. Please click any topic to display the corresponding details. </p>
        </section>
        <section class="page-section-sm">
            <?php echo $this->element("privacy_policy"); ?>
        </section>
    </section>
</div>
