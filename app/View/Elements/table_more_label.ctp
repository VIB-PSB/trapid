<?php
    $data_count = count($trs_data);
    $more_data = $data_count - $data_offset;
    $plural = null;
    if($more_data > 1) {
        $plural = "s";
    }
    $data_title = array("go"=>"GO term", "ipr"=>"InterPro domain", "ko"=>"KO term", "subset"=>"subset");

    $object_type = "transcript";
    if(isset($override_object_type)) {
        $object_type = $override_object_type;
    }

    $label_title = $more_data . " more " . $data_title[$data_type] . $plural . " associated to this " . $object_type;

    $base_link = array(
        "go"=>$this->Html->url(array("controller"=>"functional_annotation","action"=>"go",$exp_id)),
        "ipr"=>$this->Html->url(array("controller"=>"functional_annotation","action"=>"go",$exp_id)),
        "ko"=>$this->Html->url(array("controller"=>"functional_annotation","action"=>"go",$exp_id)),
        "subset"=>$this->Html->url(array("controller"=>"labels","action"=>"view",$exp_id))
    );

    $popover_data = [];

    switch($data_type) {
        case "go":
            for ($i = $data_offset; $i < $data_count; $i++) {
                $go_web = str_replace(":", "-", $trs_data[$i]);
                $popover_data[] = "<li>";
                $popover_data[] = "<a href='" . $base_link['go'] . "/" . $go_web . "'>" . $data_desc[$trs_data[$i]]['desc'] . "</a>";
                $popover_data[] = " " . $this->element("go_category_badge", array("go_category" => $data_desc[$trs_data[$i]]['type'], "small_badge" => true, "no_color" => false));
                $popover_data[] = "</li>";
            }
            break;
        case "subset":
            for ($i = $data_offset; $i < $data_count; $i++) {
                $popover_data[] = "<li><a href='" . $base_link[$data_type] . "/" . urlencode($trs_data[$i]) . "'>" . $trs_data[$i] . "</a></li>";
            }
            break;
        default:
            for ($i = $data_offset; $i < $data_count; $i++) {
                $popover_data[] = "<li><a href='" . $base_link[$data_type] . "/" . urlencode($trs_data[$i]) . "'>" . $data_desc[$trs_data[$i]]['desc'] . "</a></li>";
            }
            break;
    }


?>
<span title="<?php echo $label_title; ?>" class="pull-right label label-dark" tabindex="0" data-toggle="popover" data-trigger="focus" data-content="" data-selector="true">
    ...
    <span class='badge-light'><?php echo $more_data; ?></span>
    <span class="hidden table-more-content">
        <ul class="table-items">
            <?php echo implode('', $popover_data); ?>
        </ul>
    </span>
</span>
