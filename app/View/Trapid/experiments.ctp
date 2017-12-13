<?php
// phpinfo();
// pr(phpversion());
?>
<!-- TODO: move inline style to separate stylesheet -->
<style>
.center {
    margin-top:50px;
}

.modal-header {
	padding-bottom: 5px;
}

.modal-footer {
    	padding: 0;
	}

.modal-footer .btn-group button {
	height:40px;
	border-top-left-radius : 0;
	border-top-right-radius : 0;
	border: none;
	border-right: 1px solid #ddd;
}

.modal-footer .btn-group:last-child > button {
	border-right: 0;
}

.form-control {
  position: relative;
  font-size: 16px;
  height: auto;
  padding: 10px;
  @include box-sizing(border-box);
}
</style>

<div class="container">
<!-- No need for this information anymore? -->
<div class="page-header">
<h1 class="text-primary">Experiments overview</h1>
</div>
<p class="text-justify">Current experiments for <strong><?php echo $user_email['Authentication']['email'];?></strong>: </p>
<div class="subdiv">

	<dl class="standard">
	<dt></dt>
	<dd>
	<div>
		<!--table cellspacing="0" cellpadding="0" style="width:900px;"-->
		<table class="table table-hover table-striped">
		<thead>
			<tr>
			<th>Name</th>
			<th>#Transcripts</th>
			<th>Status</th>
			<th>Last edit</th>
			<!--th>PLAZA version</th-->
			<th>Reference database</th>
			<th>Log</th>
			<th>Jobs</th>
<!--			<th>Empty</th>-->
			<th>Reset</th>
			<th>Delete</th>
		</tr>
	</thead>
	<tbody>
		<?php

		//pr($experiments);

		if(count($experiments)==0){
			echo "<p class='text-justify lead'>No experiments... Create one?</p>";
			// echo "<tr class='disabled'>";
			// echo "<td>Unavailable</td><td>0</td><td>Unavailable</td>";
			// echo "<td>Unavailable</td><td>Unavailable</td><td></td><td></td>";
			// echo "<td></tdreplay>";
			// echo "</tr>\n";
		}
		else{
			foreach($experiments as $experiment){
			$e	= $experiment['Experiments'];
			if($e['process_state']=="error"){
				echo "<tr class='error_state'>";
			    	echo "<td>".$e['title']."</td>";
				echo "<td><span id='exp_count_".$e['experiment_id']."'>".$experiment['count']."</span></td>";
				echo "<td>".$this->Html->link($e['process_state'],array("controller"=>"trapid","action"=>"change_status",$e['experiment_id']),array("style"=>"color:red;text-decoration:underline;"))."</td>";
				echo "<td>".$e['last_edit_date']."</td>";
				if($experiment['DataSources']['URL']){
				    echo "<td>".$this->Html->link($experiment['DataSources']['name'],$experiment['DataSources']['URL'])."</td>";
			    	}
			    	else{
				    echo "<td>".$experiment['DataSources']['name']."</td>";
			    	}
				echo "<td></td>";
				echo "<td></td>";
				echo "<td>".$this->Html->link("View log",array("controller"=>"trapid","action"=>"view_log",$e['experiment_id']))."</td>\n";
				if(count($experiment['experiment_jobs'])==0){echo "<td>NA</td>";}
				else{echo "<td>".$this->Html->link(count($experiment['experiment_jobs'])." jobs",array("controller"=>"trapid","action"=>"manage_jobs",$e['experiment_id']))."</td>";}
			    	echo "</tr>\n";
			}
			else if ($e['process_state']=="loading_db"){
				echo "<tr class='processing_state'>";
			    	echo "<td>".$e['title']."</td>";
				//echo "<td>".$experiment['count']."</td>";
				echo "<td><span id='exp_count_".$e['experiment_id']."'>".$experiment['count']."</span></td>";
				// echo "<td>".$this->Html->link($e['process_state'],array("controller"=>"trapid","action"=>"change_status",$e['experiment_id']),array("style"=>"color:blue;text-decoration:underline;"))."</td>";
				echo "<td>".$this->Html->link($e['process_state'],array("controller"=>"trapid","action"=>"change_status",$e['experiment_id']))."</td>";
				echo "<td>".$e['last_edit_date']."</td>";
				if($experiment['DataSources']['URL']){
				    echo "<td>".$this->Html->link($experiment['DataSources']['name'],$experiment['DataSources']['URL'])."</td>";
			    	}
			    	else{
				    echo "<td>".$experiment['DataSources']['name']."</td>";
			    	}
				echo "<td></td>";
				echo "<td></td>";
				echo "<td>".$this->Html->link("View log",array("controller"=>"trapid","action"=>"view_log",$e['experiment_id']))."</td>\n";
				if(count($experiment['experiment_jobs'])==0){echo "<td>NA</td>";}
				else{echo "<td>".$this->Html->link(count($experiment['experiment_jobs'])." jobs",array("controller"=>"trapid","action"=>"manage_jobs",$e['experiment_id']))."</td>";}
			    	echo "</tr>\n";
			}
			else if ($e['process_state']=="processing"){
				echo "<tr class='processing_state'>";
			    	echo "<td>".$e['title']."</td>";
				//echo "<td>".$experiment['count']."</td>";
				echo "<td><span id='exp_count_".$e['experiment_id']."'>".$experiment['count']."</span></td>";
				echo "<td>".$this->Html->link($e['process_state'],array("controller"=>"trapid","action"=>"change_status",$e['experiment_id']),array("style"=>"color:blue;text-decoration:underline;"))."</td>";
				echo "<td>".$e['last_edit_date']."</td>";
				if($experiment['DataSources']['URL']){
				    echo "<td>".$this->Html->link($experiment['DataSources']['name'],$experiment['DataSources']['URL'])."</td>";
			    	}
			    	else{
				    echo "<td>".$experiment['DataSources']['name']."</td>";
			    	}
				echo "<td></td>";
				echo "<td></td>";
				echo "<td>".$this->Html->link("View log",array("controller"=>"trapid","action"=>"view_log",$e['experiment_id']))."</td>\n";
				if(count($experiment['experiment_jobs'])==0){echo "<td>NA</td>";}
				else{echo "<td>".$this->Html->link(count($experiment['experiment_jobs'])." jobs",array("controller"=>"trapid","action"=>"manage_jobs",$e['experiment_id']))."</td>";}
			    	echo "</tr>\n";
			}
			else{
			    echo "<tr>";
			    echo "<td>".$this->Html->link($e['title'],array("action"=>"experiment",$e['experiment_id']))."</td>";
			    //echo "<td>".$experiment['count']."</td>";
			    echo "<td><span id='exp_count_".$e['experiment_id']."'>".$experiment['count']."</span></td>";
			    echo "<td>".$e['process_state']."</td>";
			    echo "<td>".$e['last_edit_date']."</td>";
			    if($experiment['DataSources']['URL']){
				    echo "<td>".$this->Html->link($experiment['DataSources']['name'],$experiment['DataSources']['URL'])."</td>";
			    }
			    else{
				    echo "<td>".$experiment['DataSources']['name']."</td>";
			    }
			    echo "<td>".$this->Html->link("View log",array("controller"=>"trapid","action"=>"view_log",$e['experiment_id']))."</td>\n";
			    if(count($experiment['experiment_jobs'])==0){echo "<td>NA</td>";}
				else{echo "<td>".$this->Html->link(count($experiment['experiment_jobs'])." jobs",array("controller"=>"trapid","action"=>"manage_jobs",$e['experiment_id']))."</td>";}
			    echo "<td style=\"text-align:center;\">".$this->Html->link("<span class='material-icons text-info'>replay</span>",
				    array("controller"=>"trapid","action"=>"empty_experiment",$e['experiment_id']),
				    array("style"=>"", "escape"=>false, "title"=>"Empty experiment"),
				    "Are you sure you want to reset this experiment? All its content will be deleted. ")."</td>";
                /* Using Bootstrap's glyphicons */
			    /* echo "<td style=\"text-align:center;\">".$this->Html->link("<span class='glyphicon glyphicon-remove text-danger'></span>",
                        array("controller"=>"trapid","action"=>"delete_experiment",$e['experiment_id']),
                        array("style"=>"", "escape"=>false, "title"=>"Delete experiment"),
                        "Are you sure you want to delete the experiment?")."</td>";*/
			    /* Using Google's Material icons */
                echo "<td style=\"text-align:center;\">".$this->Html->link("<span class='material-icons text-danger'>delete</span>",
                        array("controller"=>"trapid","action"=>"delete_experiment",$e['experiment_id']),
                        array("style"=>"", "escape"=>false, "title"=>"Delete experiment"),
                        "Are you sure you want to delete this experiment?")."</td>";
			    echo "</tr>\n";
			}
		    }
		}
		?>
	</tbody>
		</table>
		<script type='text/javascript' defer="defer">
        var experiments = <?php echo json_encode($experiments); ?>;
        // Updated code to work with jQuery
        function get_exp_num_trancripts(exp_id) {
          var span_id = "#exp_count_" + exp_id;
          var ajax_url = <?php echo "\"".$this->Html->url(array("controller"=>"trapid","action"=>"experiments_num_transcripts"))."\"";?>+"/" + exp_id + "/";
          $.ajax({
              type: "GET",
              url: ajax_url,
              contentType: "application/json;charset=UTF-8",
              success: function(data) {
                  // alert("Success! ");
                  // alert(experiment_id);
                  // $(span_id).hide().html(data).fadeIn();
                  $(span_id).html(data);
              },
              error: function() {
                  // alert("Failure - Unable to retrieve transcripts count. ");
                  console.log("Failure - Unable to retrieve transcripts count. ");
              },
              complete: function() {
                // Debug
                // console.log(experiment_id);
                // console.log(span_id);
                // console.log(ajax_url);
              }
          });
        }

				for(var i=0;i<experiments.length;i++) {
				  var experiment_id = experiments[i]["Experiments"]["experiment_id"];
          get_exp_num_trancripts(experiment_id);
				}
		</script>
	</div>
	</dd>

	<?php if(count($shared_experiments)!=0): ?>
	<dt>Shared experiments</dt>
	<dd>
	<div>
		<table cellspacing="0" cellpadding="0" style="width:900px;">
		<tr>
			<th style="width:30%;">Name</th>
			<th style="width:40%;">Owner</th>
			<th style="width:20%;">PLAZA version</th>
			<th style="width:10%;">Log</th>
		</tr>
		<?php
		foreach($shared_experiments as $experiment){
			$e	= $experiment['Experiments'];
			echo "<tr>";
			echo "<td>".$this->Html->link($e['title'],array("controller"=>"trapid","action"=>"experiment",$e['experiment_id']))."</td>";
			$owner_email	= $all_user_ids[$e['user_id']];
			echo "<td><a href='mailto:".$owner_email."'>".$owner_email."</a></td>";
			if($experiment['DataSources']['URL']){
				echo "<td>".$this->Html->link($experiment['DataSources']['name'],$experiment['DataSources']['URL'])."</td>";
			}
			else{
				echo "<td>".$experiment['DataSources']['name']."</td>";
			}
			 echo "<td>".$this->Html->link("View log",array("controller"=>"trapid","action"=>"view_log",$e['experiment_id']))."</td>\n";
			echo "</tr>\n";
		}
		?>
		</table>
	</div>
	</dd>

	<?php endif; ?>


	<?php if(count($experiments)<$max_user_experiments): ?>
<p class="text-right">
	<button data-toggle="modal" data-target="#squarespaceModal" class="btn btn-primary btn-lg" name="" id="">
	  <span class="glyphicon glyphicon-plus"> </span> Add new experiment
  </button>
</p>


<div class="modal fade" id="squarespaceModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
			<h3 class="modal-title" id="lineModalLabel">New experiment</h3>
		</div>
		<div class="modal-body">

            <!-- content goes here -->
            <?php
                if(isset($error)){
                echo "<span class='error'>".$error."</span><br/>\n";
                }
                echo $this->Form->create("Experiments",array("url"=>array("controller"=>"trapid","action"=>"experiments"),
                            "type"=>"post"));
            ?>
              <div class="form-group">
                <label for=""><strong>Name</strong></label>
                <input type="text" maxlength="50" class="form-control" id="experiment_name" name="experiment_name" placeholder="My experiment">
              </div>
              <div class="form-group">
                <label for=""><strong>Description</strong></label>
								<textarea rows="4" name="experiment_description" id="experiment_description" class="form-control" placeholder="Experiment description... "></textarea>
              </div>
              <div class="form-group">
                <label for=""><strong>Reference database</strong></label>
								<select class="form-control" name="data_source">
								<?php
								foreach($available_sources as $av){
									echo "<option value='".$av['DataSources']['db_name']."'>".$av['DataSources']['name']."</option>\n";
								}
								?>
								</select>
                <p class="help-block"><strong>Note:</strong> GO annotations are only available for the PLAZA reference database</p>
              </div>
							<p class="text-center">
              <button type="submit" class="btn btn-primary">Create experiment</button></p>
            </form>

		</div>
		<!--div class="modal-footer">
			<div class="btn-group btn-group-justified" role="group" aria-label="group button">
				<div class="btn-group" role="group">
					<button type="button" class="btn btn-default" data-dismiss="modal"  role="button">Close</button>
				</div>
				<div class="btn-group btn-delete hidden" role="group">
					<button type="button" id="delImage" class="btn btn-default btn-hover-red" data-dismiss="modal"  role="button">Delete</button>
				</div>
				<div class="btn-group" role="group">
					<button type="button" id="saveImage" class="btn btn-default btn-hover-green" data-action="save" role="button">Save</button>
				</div>
			</div>
		</div-->
	</div>
  </div>
</div>



	<!--dt>Add new experiment</dt-->
	<!--dd>
	<div>
		<?php /*
		    if(isset($error)){
			echo "<span class='error'>".$error."</span><br/>\n";
		    }
		    echo $this->Form->create("Experiments",array("url"=>array("controller"=>"trapid","action"=>"experiments"),
						"type"=>"post")); */
		?>
		<dl class="nb">
			<dt>Name</dt>
			<dd><input type="text" name="experiment_name" maxlength="50" style="width:400px;"/></dd>
			<dt>Description</dt>
			<dd><textarea rows="4" name="experiment_description" style="width:400px;"></textarea></dd>
			<dt>Reference DB</dt>
			<dd>
				<div>
				<select name="data_source" style="width:150px;">
				<?php
				foreach($available_sources as $av){
				echo "<option value='".$av['DataSources']['db_name']."'>".$av['DataSources']['name']."</option>\n";
				}
				?>
				</select>
				<span style='margin-left:20px;font-weight:bold;color:black'>Note: GO annotations are only available for the PLAZA reference database</span>

				</div>
			</dd>
		</dl>
		<input type="submit" value="Create experiment" style="width:150px;margin-top:1em;" />
		</form>
	</div>
	</dd-->
	<?php else: ?>
	<p class="text-right"><span class="text-danger">Maximum number of experiments reached, cannot create more experiments! </span>
		<!--button disabled class="btn btn-primary btn-lg" name="" id=""><span class="glyphicon glyphicon-plus"> </span> Create experiment</button-->
		</p>
	<!--dt>Add new experiment</dt-->
	<dd>
	<div>

	</div>
	</dd>
	<?php endif;?>
	</dl>
</div>

</div>
