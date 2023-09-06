<div class="page-header" style="margin-top:-15px;">
    <h2 class="text-primary"><?php echo "$col_names[0] - $col_names[1] - $col_names[2] intersection";?></h2>
</div>

<section class="page-section-xs">
    <!-- Sankey controls -->
    <div class="row" id="choices-row">
        <div class="panel panel-default" id="choices">
            <div class="panel-heading">
                Sankey diagram display options
            </div>

            <div class="panel-body">
                <div class="col-md-4">
                    <?php
                    $number_of_choices = 31;
                    ///////////////// Left refinement /////////////////
                    echo $this->Form->create(false, array('id' => 'left_boxes', 'class'=> 'refine_box'));
                    echo '<div class="left_col"></div><div class="right_col"></div><br>';
                    $options = array(
                        'type' => 'button',
                        'id' => 'left_refine',
                        'onclick' => 'draw_sankey()'
                    );
                    // echo $this->Form->button('  Refine  ',$options);
                    echo $this->Form->end();
                    ?>
                </div>
                <div class="col-md-4">
                    <?php
                    ///////////////// Middle refinement /////////////////

                    echo $this->Form->create(false, array('id'=> 'middle_refine_form','class'=> 'refine_box'));
//                    echo $this->Form->input('type: ', array('options' => array('All', 'BP', 'MF', 'CC'), 'id' =>'type','onchange' => 'middle_filter()'));
                    echo $this->Form->input('type: ', array('options' => array('BP', 'MF', 'CC'), 'id' =>'type','onchange' => 'middle_filter()'));
                    echo $this->Form->input('q-value: ', array('options' => array(), 'id' => 'pvalue','onchange' => 'middle_filter()'));
                    // TRAPID currently computes and reports positive enrichment only, so this form option should be hidden
                    echo $this->Form->input('Enrichment: ', array('options' => array('positive', 'negative'), 'id' => 'enrichment','onchange' => 'middle_filter()'));
                    echo $this->Form->input(' show hidden', array('type' => 'checkbox', 'id' => 'hidden','onchange' => 'middle_filter()'));
                    echo $this->Form->input(' normalize links', array('type' => 'checkbox', 'id' => 'normalize'));

                    $options = array(
                        'type' => 'button',
                        'id' => 'middle_refine',
                        'onclick' => 'draw_sankey()'
                    );
                    echo $this->Form->end();

                    ?>
                </div>
                <div class="col-md-4">
                    <?php
                    ///////////////// Right refinement /////////////////
                    echo $this->Form->create(false, array('id'=> 'right_refine_form','class'=> 'refine_box'));
                    echo $this->Form->input("Minimum $col_names[2] size: ", array('options' => array(), 'id' =>'right_min'));
                    echo $this->Form->end();
                    ?>
                </div>
            </div>

            <div class="panel-footer">
                <div class="text-right"> <strong>Export as: </strong>
                    <button id="export_sankey_png" class="btn btn-default btn-xs" title="Export Sankey diagram (PNG)">PNG</button>
                    <button id="export_sankey_svg" class="btn btn-default btn-xs" title="Export Sankey diagram (SVG)">SVG</button> |
                    <button type="submit" class="btn btn-primary btn-sm" onclick="draw_sankey()" title="Redraw Sankey diagram">
                        <span class="glyphicon glyphicon-repeat"></span> Redraw</button>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="page-section-sm">
<div id="sankey">

<?php
    echo '<script type="text/javascript">';
    echo "\nvar enrichedIdents = " . json_encode($enriched_gos) .";";
    echo "\nvar transcriptIdent = " . json_encode($transcriptGO) .";";
    echo "\nvar transcriptLabelGF = " . json_encode($transcriptLabelGF) .";";
    echo "\nvar descriptions = " . json_encode($descriptions) .";";
    echo "\nvar label_counts = " . json_encode($counts) .";";
    echo "\nvar sankeyEnrichmentData = " . json_encode($sankey_enrichment_data) .";";
    echo "\nvar sankeyGfData = " . json_encode($sankey_gf_data) .";";
    echo "\nvar total_count = " .   $exp_info['transcript_count'] .";";
    echo "\nvar dropdown_filter_name = " . json_encode($dropdown_names) .';';
    echo "\nvar urls = " . json_encode($urls) .";";
    echo "\nvar place_holder = '" . $place_holder ."';";
    echo "\nvar GO = '" . $GO ."';";
    echo "\nvar exp_id = '" . $exp_id ."';";
    echo '</script>';

	echo $this->Html->css('sankey');
	echo $this->Html->script(array('d3-3.5.6.min', 'd3-tip', 'sankey', 'sankey_enriched2'));
    echo $this->Html->script(array('https://cdn.jsdelivr.net/gh/eligrey/canvas-toBlob.js@f1a01896135ab378aa5c0118eadd81da55e698d8/canvas-toBlob.js',
        'https://cdn.jsdelivr.net/gh/eligrey/FileSaver.js@v2.0.4/dist/FileSaver.min.js'));



?>
</div>
</section>

<script type="text/javascript">
    /* Export code based on this block: http://bl.ocks.org/Rokotyan/0556f8facbaf344507cdc45dc3622177 */
    // Set-up export buttons
    var export_base_name = "Sankey_Diagram_<?php echo str_replace(' ', '_', $col_names[0]) . '_' .  str_replace(' ', '_', $col_names[1]) . '_' .  str_replace(' ', '_', $col_names[2]);?>";
    // PNG
    d3.select('#export_sankey_png').on('click', function(){
        var svgString = getSVGString(svg.node());
        svgString2Image( svgString, 2*width, 2*height, 'png', save ); // passes Blob and filesize String to the callback

        function save( dataBlob, filesize ){
            saveAs( dataBlob, export_base_name + '.png' ); // FileSaver.js function
        }
    });

    // SVG
    d3.select('#export_sankey_svg').on('click', function(){
        var svgString = getSVGString(svg.node()).split('\n');
//        var svgBlob = ;
        saveAs( new Blob([svgString],  {type: "image/svg+xml;charset=utf-8"}), export_base_name + '.svg' ); // FileSaver.js function
    });

    // Below are the functions that handle actual exporting:
    // getSVGString ( svgNode ) and svgString2Image( svgString, width, height, format, callback )
    function getSVGString( svgNode ) {
        svgNode.setAttribute('xlink', 'http://www.w3.org/1999/xlink');
        var cssStyleText = getCSSStyles( svgNode );
        appendCSS( cssStyleText, svgNode );

        var serializer = new XMLSerializer();
        var svgString = serializer.serializeToString(svgNode);
        svgString = svgString.replace(/(\w+)?:?xlink=/g, 'xmlns:xlink='); // Fix root xlink without namespace
        svgString = svgString.replace(/NS\d+:href/g, 'xlink:href'); // Safari NS namespace fix

        return svgString;

        function getCSSStyles( parentElement ) {
            var selectorTextArr = [];

            // Add Parent element Id and Classes to the list
            selectorTextArr.push( '#'+parentElement.id );
            for (var c = 0; c < parentElement.classList.length; c++)
                if ( !contains('.'+parentElement.classList[c], selectorTextArr) )
                    selectorTextArr.push( '.'+parentElement.classList[c] );

            // Add Children element Ids and Classes to the list
            var nodes = parentElement.getElementsByTagName("*");
            for (var i = 0; i < nodes.length; i++) {
                var id = nodes[i].id;
                if ( !contains('#'+id, selectorTextArr) )
                    selectorTextArr.push( '#'+id );

                var classes = nodes[i].classList;
                for (var c = 0; c < classes.length; c++)
                    if ( !contains('.'+classes[c], selectorTextArr) )
                        selectorTextArr.push( '.'+classes[c] );
            }

            // Extract CSS Rules
            var extractedCSSText = "";
            for (var i = 0; i < document.styleSheets.length; i++) {
                var s = document.styleSheets[i];

                try {
                    if(!s.cssRules) continue;
                } catch( e ) {
                    if(e.name !== 'SecurityError') throw e; // for Firefox
                    continue;
                }

                var cssRules = s.cssRules;
                for (var r = 0; r < cssRules.length; r++) {
                    if ( contains( cssRules[r].selectorText, selectorTextArr ) )
                        extractedCSSText += cssRules[r].cssText;
                }
            }


            return extractedCSSText;

            function contains(str,arr) {
                return arr.indexOf( str ) === -1 ? false : true;
            }

        }

        function appendCSS( cssText, element ) {
            var styleElement = document.createElement("style");
            styleElement.setAttribute("type","text/css");
            styleElement.innerHTML = cssText;
            var refNode = element.hasChildNodes() ? element.children[0] : null;
            element.insertBefore( styleElement, refNode );
        }
    }


    function svgString2Image( svgString, width, height, format, callback ) {
        var format = format ? format : 'png';

        var imgsrc = 'data:image/svg+xml;base64,'+ btoa( unescape( encodeURIComponent( svgString ) ) ); // Convert SVG string to data URL

        var canvas = document.createElement("canvas");
        var context = canvas.getContext("2d");

        canvas.width = width;
        canvas.height = height;

        var image = new Image();
        image.onload = function() {
            context.clearRect ( 0, 0, width, height );
            context.drawImage(image, 0, 0, width, height);

            canvas.toBlob( function(blob) {
                var filesize = Math.round( blob.length/1024 ) + ' KB';
                if ( callback ) callback( blob, filesize );
            });


        };

        image.src = imgsrc;
    }

</script>
