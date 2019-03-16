<div class="page-header">
    <div class='btn-toolbar pull-right'>
        <!-- This line return is an ugly fix to position the export button -->
        <br>
        <button onclick="export_log();" class='btn btn-sm btn-default'>
            <span class="glyphicon glyphicon-download-alt"></span>
            Export
        </button>
    </div>
    <h1 class="text-primary">Log history</h1>
</div>


<div class="well" style='font-family:monospace; font-size:88%;' id="exp-log-well">
            <?php
            $colors = array(0 => "#000000", 1 => "#202020", 2 => "#404040", 3 => "#606060");
            foreach ($log_info as $li) {
                $l = $li['ExperimentLog'];
                $date = $l['date'];
                $action = $l['action'];
                $param = $l['parameters'];
                $depth = $l['depth'];
                echo "+ <span style='color:" . $colors[$depth] . "'>";
                for ($i = 0; $i < $depth; $i++) {
                    echo "&nbsp;&nbsp;";
                }
                echo $date . "\t" . $action . "\t" . $param;
                echo "</span><br/>\n";

            }
            ?>
        </div>

        <br/><br/>

        <?php
        /*
        $num_rows	= count($log_info)+4;
        if($num_rows>20){$num_rows=20;}
        echo "<textarea rows='".$num_rows."' style='width:700px;'>";
        foreach($log_info as $li){
            $l	= $li['ExperimentLog'];
            $date	= $l['date'];
            $action	= $l['action'];
            $param	= $l['parameters'];
            $depth	= $l['depth'];
            for($i=0;$i<=$depth;$i++){echo " * ";}
            echo $date."\t".$action."\t".$param."\n";
        }
        echo "</textarea>\n";
        */
        ?>

<script type="text/javascript">

    // function copied from
    function download(filename, text) {
        var element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }

    function export_log() {
        var log = document.getElementById("exp-log-well");
        var log_html = log.outerHTML;
        var striped_html = $("<div>").html(log_html).text();
        download("trapid_experiment_log_" + <?php echo $exp_id; ?> + ".txt", striped_html);
    }
</script>