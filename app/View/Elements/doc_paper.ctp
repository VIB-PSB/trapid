<?php

// Print correctly-formatted paper information (used in diverse documentation pages)
// $title: the paper title
// $authors: the paper author list
// $url: a URL for the paper (best is pubmed or DOI link)
// $journal: journal (+ date) information string

if (isset($title, $authors, $url, $journal)): ?>
    <blockquote class='paper-blockquote'>
        <p class='text-justify'>
            <strong><?php echo $title; ?></strong><br>
            <?php echo $authors; ?><br>
            <a title='View article in a new tab' target='_blank' href='<?php echo $url; ?>'>
                <span class='glyphicon glyphicon-link'></span></a>
            <em><?php echo $journal; ?></em>
        </p>
    </blockquote>
<?php endif; ?>
