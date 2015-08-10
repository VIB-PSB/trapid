<div>
<h2>GO enrichment graph</h2>
<div class="subdiv">
	<?php echo $this->element("trapid_experiment");?>
	
	<h3>GO enrichment</h3>
	<div class="subdiv">
		<?php
		echo $html->link("Return to GO enrichment overview",array("controller"=>"tools","action"=>"go_enrichment",$exp_id));
		?>
		<br/><br/>
	</div>	

	<h3>GO enrichment graph</h3>	
	<div class="subdiv">
	<br/><br/>
	<center>
	    <?php
		$max_depth = 0;
		foreach($all_graphs as $go=>$graph){
		    foreach($graph["nodes"] as $depth=>$nodes){
			if($depth>$max_depth){$max_depth = $depth;}
		    }
		}
		$canvas_height = 60*($max_depth+1) + 140;			
	    ?>
            <canvas width="940" height=<?php echo "'".$canvas_height."'";?> id="go_graph_canvas" style="border:1px solid black"></canvas>
	</center>
	</div>
	
	<?php
		$link_url	= $html->url(array("controller"=>"functional_annotation","action"=>"go",$exp_id),true);
	?>
	
	<script type="text/javascript">
	    var ctx           = initCanvas($("go_graph_canvas")).getContext('2d');
	    var canvas_width  = 940;
	    var GLOBAL_MAX_P  = 50;
	    var GLOBAL_MAX_D  = <?php echo $max_depth;?>;
	    var canvas_height = <?php echo $canvas_height;?>;
	    var click_url     = <?php echo "'".$link_url."'" ;?> + "/" ;	    
	    clearCanvas(ctx,canvas_width,canvas_height);
	    var graph_data    = <?php echo $javascript->object($all_graphs); ?>;
	    var enrich_data   = <?php echo $javascript->object($data); ?>;
	    var all_gos	      = <?php echo $javascript->object($accepted_gos); ?>;

	    var merged_graph  = mergeGraph(all_gos,graph_data);
	    var parsed_data   = initData(merged_graph,enrich_data);
	    var button_array  = parsed_data[0];
	    var line_array    = parsed_data[1];    
	    var legend_info   = parsed_data[2];
		   	       
	    drawLines(ctx,line_array);
	    drawButtons(ctx,button_array,-1); 	    
	    drawLegend(ctx,legend_info);



	    /*
	     * Function which will attempt to merge various graphs into 1 graph, by removing double nodes,
	     * and reconfiguring edges. Must be done only once, in the initialisation of the function.
	     * @param all_gos : all active gos for the graph, in an array
	     * @param graph_data : graph_data, hashed per present go
	     * @return object : hash of merged graph information
	     */
	    function mergeGraph(all_gos,graph_data){
		var m_edges = new Object();
		var m_nodes = new Object();
		var m_desc  = new Object();	
		var done_nodes = new Object();	
		for(var i=0; i < all_gos.length;i++){
		//for(var i=0;i < 4;i++){
		    if(i==0){
			var temp = $H($H(graph_data).get(all_gos[i]));			
			m_edges  = temp.get("edges");		
			m_nodes  = temp.get("nodes");
			m_desc   = temp.get("desc");			
		    }
		    else{
			var temp    = $H($H(graph_data).get(all_gos[i]));
			var t_edges = temp.get("edges");
			var t_nodes = temp.get("nodes");
			var t_desc  = temp.get("desc");			
			m_nodes     = mergeNodes(m_nodes,t_nodes,done_nodes);
			m_edges	    = mergeEdges(m_edges,t_edges);
			m_desc	    = mergeDesc(m_desc,t_desc);								
		    }
		    $H(m_nodes).keys().each(function(depth){
		        if(!isNaN(depth)){
			    for(var i=0;i< m_nodes[depth].length;i++){
				done_nodes[m_nodes[depth][i]] = true;
			    }
			}
		    });
		}		
		var result  = new Object();
		result["edges"] = m_edges;
		result["nodes"] = m_nodes;
		result["desc"]  = m_desc;
		return result;
	    }


	    /**
	     * Function which will merge descriptions
	     */
	    function mergeDesc(m_desc,t_desc){
		if(!t_desc || t_desc==undefined){return m_desc;}
		var t_node_keys = $H(t_desc).keys();
		t_node_keys.each(function(go_id){		    
		    if(m_desc[go_id]==undefined){m_desc[go_id] = t_desc[go_id];}	
		});
		return m_desc;
	    }




	    /*
             * Function designed to merge nodes per depth level
	     * @param m_nodes : list of already merged nodes
	     * @param t_nodes : list of nodes to be merged with m_nodes
	     * @return updated m_nodes.
	     */
	    function mergeNodes(m_nodes,t_nodes,done_nodes){
		var m_node_keys   = $H(m_nodes).keys();
		//start from m_nodes : go over each depth, and add new data to each depth
		m_node_keys.each(function(depth){
		    if(!isNaN(depth)){		     		    		     		   
			var gos = $A(m_nodes[depth]);
			if(t_nodes[depth]!=undefined){
			    var gos2 = $A(t_nodes[depth]);
			    for(var i=0;i< gos2.length;i++){    							
				if(done_nodes[gos2[i]]==undefined && !arrayHasValue(gos2[i],gos)){ gos.push(gos2[i]);}
			    }
			}
			m_nodes[depth] = gos;
		    }
		});

		//it is possible that t_nodes has a greater max depth : therefore we do another iteration but starting from t_nodes
		var t_node_keys   = $H(t_nodes).keys();
		t_node_keys.each(function(depth){
		    if(!isNaN(depth)){
			if(m_nodes[depth] == undefined){
			    var gos = $A(t_nodes[depth]);
    			    var nd  = new Array();
    			    for(var i=0;i < gos.length;i++){	
    				if(done_nodes[gos[i]]==undefined){nd.push(gos[i]);}			       
    			    }
    			    m_nodes[depth] = nd;
			}
		    }
		});
		return m_nodes;
	    }




	    /*
	     * Function designed to merge edges per depth level
	     * @param m_edges = list of already merged edges
	     * @param t_edges = list of edges to be merged wtih m_edges
	     * @return update m_edges
	     */
	    function mergeEdges(m_edges,t_edges){
		for(var i=0;i< t_edges.length;i++){
		    var present  = false;
		    var t_child  = $H(t_edges[i]).get("child");
		    var t_parent = $H(t_edges[i]).get("parent");
		    for(var j=0;j< m_edges.length;j++){
			var m_child  = $H(m_edges[j]).get("child");
			var m_parent = $H(m_edges[j]).get("parent");
			if(t_child==m_child && t_parent==m_parent){
			    present = true;
			    break;
			}
		    }
		    if(!present){
			m_edges.push(t_edges[i]);
		    }		  
		}
		return m_edges;
	    }

	

	    /*
	     *  Function which checks whether an array contains a certain value
	     * @param t_value : value to be checked for
	     * @param t_array : array which could contain the value
	     * @return boolean : whether the value is present in the array
	     */
	    function arrayHasValue(t_value,t_array){	
		for(var i=0;i< t_array.length;i++){		    
		    if(t_array[i]==t_value){ return true;}
		}		
		return false;
	    }




	    /*
	     * function to initiate canvas drawing.
	     */
	    function initCanvas(canvas) {
		if (window.G_vmlCanvasManager && window.attachEvent && !window.opera) {
		    canvas = window.G_vmlCanvasManager.initElement(canvas);
		}
		return canvas;
	    }





	    /*
	     * function to observe mouse motion movement on the canvas : namely go identification tags and mouse cursor changes.
	     */
	    $("go_graph_canvas").observe("mousemove",function(event){
	        var element   = event.element();			
		var x         = event.pageX - element.offsetLeft;			
		var y         = event.pageY - element.offsetTop;
		var button_id = find_button(x,y,button_array);
		if(button_id >= 0){
		    $("go_graph_canvas").style.cursor = "pointer";
		    clearCanvas(ctx,canvas_width,canvas_height);
		    drawLines(ctx,line_array);
		    drawButtons(ctx,button_array,button_id);
		    drawLegend(ctx,legend_info);
		    button_array[button_id].drawInfoBox(ctx,canvas_width,canvas_height,230,"#D2D2D2",2);
		}
		else{
		    $("go_graph_canvas").style.cursor = "default";
		    clearCanvas(ctx,canvas_width,canvas_height);
		    drawLines(ctx,line_array);
		    drawButtons(ctx,button_array,-1);
		    drawLegend(ctx,legend_info);
		}
	    });




	    /*
 	     * function to observe mouse click events : redirection to URL of choice.
	     */
	    $("go_graph_canvas").observe("click",function(event){
	        var element        = event.element();
	        var x = event.pageX - element.offsetLeft;
		var y = event.pageY - element.offsetTop;
		var button_id = find_button(x,y,button_array);
		if(button_id >= 0){
		    button_array[button_id].makeHyperLink();
		}
	    });




	    /*
	     * Function to detect whether there is a button (node on the graph) on a certain x/y spot on the canvas.
	     * @param x : x position on canvas
	     * @param y : y position on canvas
	     * @param button_array : array which contains all the buttons present on the canvas
	     * @return integer: index of the button in the given button_array, or -1 when there is no button on the indicated
	     *   x,y position.	
	     */
	    function find_button(x,y,button_array){
	        var result = -1;
		for(var i=0;i<button_array.length;i++){
		    var b = button_array[i];
		    if(x >= b.x && x <= (b.x+b.width) && y >= b.y && y <= (b.y+b.height)){
		        result = i;
			return result;
		    }
		}
		return result;
	    }





	    /*
	     * Function to clear the canvas, and fill it with a white background.
	     * @param canvas : the canvas to clear.
	     * @param width  : width of the canvas
	     * @param height : height of the canvas
	     */
	    function clearCanvas(canvas,width,height){
	        canvas.clearRect(0,0,width,height);
		canvas.fillStyle="#FFFFFF";
		canvas.fillRect(0,0,width,height);
	    }




	 
	    /**
	     * Function to determine which color a button (node in the graph) should have,
	     * based on the given GO enrichment information.
	     * @param enrich : enrichment data. This data is structured as a hash, with GO-id's as keys. 
	     * @param go_id  : the go_id (also functions as button_id) of the current button-node.
	     * @return string : string representation of a color (eg #FFFFFF or rgb(255,255,255) )
	     */
	    function get_enrich_color(enrich,go_id,max_log_val){
		var undef_color = "#FFFFFF";
		if(enrich.get(go_id)){
		    var go_data = $H(enrich.get(go_id));
		    var log_val = Math.ceil(-1*Math.log(go_data.get("p_value"))/Math.log(10));	
		    if(log_val < 0){log_val = 0;}
		    if(log_val > GLOBAL_MAX_P){log_val = GLOBAL_MAX_P;}			   
		    return get_enrichment_color(log_val,max_log_val); 	
		}
	        else{
		    return undef_color;
		}	
	    }

	    function get_enrichment_color(log_val,max_log_val){
		    
		var color = "rgb(255,255,"+Math.ceil(255-(255/max_log_val)*log_val)+")";
		return color; 
	    }






	    /*
            * Check whether a node is valid or not. Only used during construction of the graph
	    * @param go_id : the node we investigate, indicated by the go_id which is its identifier
	    * @param is_enriched : indicates whether there is enrichment information for the node
 	    * @param edges : all the edges-information (parent-child relationships between nodes)
	    * @param invalid_nodes : nodes which have been investigated and deemed to be invalid
	    * @param depth : current depth of the node
   	    * @param row_nodes : all the nodes which are indicated of being on the same y-position within the graph
	    * @return boolean : whether or not the node is valid.		
	    */
	    function is_valid_node(go_id,is_enriched,edges,invalid_nodes,depth,row_nodes){
		//if is enriched : return true
		if(is_enriched){return true;}

		//if depth 0 (root node) : always return true
		if(depth == 0){return true;}

		//check whether there is only 1 node on the current y-level, or only 1 remaining non-valid node on the current y-level
		if(row_nodes.length==1){return true;} //at least 1 node on each level	
		var num_invalid_siblings = 0;
		for(var i=0;i< row_nodes.length;i++){
		    if(invalid_nodes[row_nodes[i]]!=undefined && invalid_nodes[row_nodes[i]]==true){ num_invalid_siblings++;}		   
		}
		if((row_nodes.length-1)==num_invalid_siblings){return true;} //keep 1 node on each level
		
		
		//first heavy check: dangling nodes, which are nodes with no child nodes and not enriched
		var has_children = false;
		var num_children = 0;
		var num_invalid_children = 0;
		for(var i=0;i< edges.length;i++){
		    var parent_node = edges[i]["parent"];		    		    		    
		    if(parent_node == go_id){ //is parent, but perhaps all its children are also invalid nodes => redundancy
			num_children++;
			has_children = true;
			if(invalid_nodes[edges[i]["child"]] != undefined && invalid_nodes[edges[i]["child"]] == true){
			    num_invalid_children++;
			}					
		    }			
		}
		if(!has_children && !is_enriched){ //doesn't have children, and is not enriched
		    return false;
		}
		if(num_children==num_invalid_children && !is_enriched){ //does have children, but they are all not valid
		    return false;
		}

		//second heavy check: non-enriched nodes which can be removed without `breaking` the graph in multiple pieces		
		var all_children_abandoned = false;
		for(var i=0;i< edges.length;i++){		   
		    if(edges[i]["parent"] == go_id){			
			var num_parents = 0;
			var num_disabled_parents = 0;
			//now, check whether or not the child has any parents left after potential removing of the current node	
			for(var j=0;j< edges.length;j++){
			    if(edges[j]["child"] == edges[i]["child"]){
				//check parent for the current edge, and whether this parent is ok.
				num_parents++;
				if(invalid_nodes[edges[j]["parent"]] != undefined && invalid_nodes[edges[j]["parent"]] == true){
				    num_disabled_parents++;
				}
			    }
			}
			if(num_parents == (num_disabled_parents+1)){all_children_abandoned = true; break;}
		    }
		}

		if(!is_enriched && !all_children_abandoned){
		    return false;
		}

		return true;	
	    }	







		
	    /*
	     * Function which initiates all data, by collecting the necessary information, and then creating the buttons
	     * and lines. These are then returned. This makes it so that all this data needs to be computed only once, and the coordinates
 	     * are stored for future reference (redrawing of screen,...).
	     * Quite a complex function actually : first off, all redundant and unncessary nodes are removed in order to save space. 
	     * Then the actual computation of locations is done for the buttons. Following this is the computation of the location
	     * of the edges.	
	     * @param graph_data : JSON object which contains all information on graphs for a certain visible GO
	     * @param enrich_data : JSON object which contains all information on current GO enrichment
	     * @param current_buttons : Array with already assigned buttons
	     * @param current_lines : Array with already assigned lines
	     * @return array : 1st item in array is an array of buttons, 2nd item in array is an array of lines
	     */
	    function initData(graph_data,enrich_data){
		var edges  = $H(graph_data).get("edges");
		var nodes  = $H(graph_data).get("nodes");
		var descr  = $H(graph_data).get("desc");						
			
		var enrich = $H(enrich_data);
		var button_height = 30;
		var button_y_dist = 30;
		var button_width  = 100;
		var button_x_dist = 10;
		var collapsed_bh  = 30;
		var collapsed_yd  = 30;
		var collapsed_bw  = 10;
		var collapsed_xd  = 10;				

		var result       = new Array();
		var button_array = new Array();
		var line_array   = new Array();

		var node_keys   = $H(nodes).keys();		

		var descript    = $H(descr);
		var y_positions = new Object();
		var b_positions = new Object();
		//create buttons
		var global_bc = 0;
		var global_lc = 0;

							
		//first: check the graph, and remove unnecessary nodes, through a series of iterations.
		//this is done here, and not on the webserver in order to reduce server load
		var invalid_nodes  = new Object();			
		var added_invalids = false;
		var first_run      = true;	
		var num_iterations = 0;			
		while(first_run || added_invalids){ 
		    first_run      = false;
		    added_invalids = false;
		    node_keys.each(function(depth){
			if(!isNaN(depth)){			  
			    var gos = $A(nodes[depth]);
			    for(var i=0;i< gos.length;i++){
				if(invalid_nodes[gos[i]]== undefined || invalid_nodes[gos[i]] == false){
				    if(!is_valid_node(gos[i],enrich.get(gos[i])!=undefined,edges,invalid_nodes,depth,gos)){
					added_invalids = true;
					invalid_nodes[gos[i]] = true;				    				
				    }
				    else{
					invalid_nodes[gos[i]] = false;
				    }
				}
			    }
			}
		    });
		   // if(num_iterations++ > 5){alert(first_run+" "+added_invalids); break;}
		}

		
		var max_p_value  = 0;
		var max_depth    = 0;
		var legend_info = new Object();
		//determine max p_value, and max_depth
		node_keys.each(function(depth){	
		    if(!isNaN(depth)){
			if(depth>max_depth){max_depth=depth;}
			var gos = $A(nodes[depth]);
			for(var i=0;i< gos.length;i++){
			    if(invalid_nodes[gos[i]]==undefined || invalid_nodes[gos[i]]==false){
				var p_value = $H(enrich.get(gos[i])).get("p_value");
				var log_val = Math.ceil(-1*Math.log(p_value)/Math.log(10));
				if(log_val > max_p_value){max_p_value = log_val;}				
			    }
			}
		    }	
		});
		if(max_p_value>GLOBAL_MAX_P){max_p_value=GLOBAL_MAX_P;}
		legend_info["max_p_value"] = max_p_value;
		legend_info["max_depth"]   = max_depth;		
		

		//actually create the nodes, after the previous full filtering operation
		node_keys.each(function(depth){	
		     if(!isNaN(depth)){				     		    		     		   
			 var gos = $A(nodes[depth]);
			 var collapse_non_enriched  = false;
			 var collapse_non_shown     = false;
			 var collapse_all	    = false;
							    
			 var j = 0;		//variable used for horizontal positioning. Can't use i-var because of invalid nodes
			 var count_row = 0;     //variable used for horizontal positioning. Can't use gos.length because of invalid nodes
			 var count_ne  = 0;	//number of non-enriched GO's (neither enriched or depleted)
			 var count_ns  = 0;     //number of non-shown GO's (marked as hidden).
			 for(var i=0;i< gos.length;i++){
			    if(invalid_nodes[gos[i]]==undefined || invalid_nodes[gos[i]]==false){ 
			        count_row++;
				if(enrich.get(gos[i])){if($H(enrich.get(gos[i])).get("hidden")==1){count_ns++;}}
				else{count_ne++;}				
  			    }			    
			 }		     
						 
			 //this one is used to determine which buttons to collapse into a very small form, in order to have all buttons
			 //on screen. 
			 var full_length_row = 0;
			 if(count_row > 6){ //too many buttons: attempt to collapse the ones which are 
			    //first attempt to collapse those that do not have a p_value (which are neither enriched or depleted).
			    collapse_non_enriched = true;
			    full_length_row = (count_row-count_ne)*(button_width+button_x_dist)+count_ne*(collapsed_bw+collapsed_xd);
			    //second attempt to collapse those that do have a p_value, but are indicated as being hidden
			    if(full_length_row > canvas_width){ 
				collapse_non_shown = true;
				full_length_row=(count_row-count_ne-count_ns)*(button_width+button_x_dist)+(count_ne+count_ns)*(collapsed_bw+collapsed_xd);
			    }
			    //third attempt : collapse all buttons
			    if(full_length_row > canvas_width ){
				collapse_all = true;
				full_length_row=count_row*(collapsed_bw+collapsed_xd);
			    }			    		    
			 } 
			 else{
			     full_length_row = count_row*(button_width+button_x_dist);
			 }  
						   		

			 var x_prev = Math.ceil(canvas_width/2-full_length_row/2);

			 for(var i=0;i< gos.length;i++){			
			    //do not draw node if node is designated as being invalid
			    if(invalid_nodes[gos[i]] == undefined || invalid_nodes[gos[i]] == false){
				var small_button = false;
				//in case of necessesity : collapse (indicated by collapse_* variables)
				var local_bw = button_width;
				var local_xd = button_x_dist;
				if(!enrich.get(gos[i])){if(collapse_non_enriched){
				        local_bw=collapsed_bw;local_xd=collapsed_xd;small_button=true;
				    }
				}
				else{
				    if($H(enrich.get(gos[i])).get("hidden")==1){
					if(collapse_non_shown){local_bw=collapsed_bw;local_xd=collapsed_xd;small_button=true;}
				    }
				    else{
					if(collapse_all){local_bw=collapsed_bw;local_xd=collapsed_xd;small_button=true;}
				    }
				}								
			    	//compute the necessary coordinates
				var xb  = x_prev;		
				x_prev = Math.ceil(xb + local_bw+local_xd);
				var yb = Math.ceil(button_y_dist+depth*(button_height+button_y_dist));
				var db = descript.get(gos[i]);			    		
				var ub = click_url+gos[i].replace(":","-");			   			   
				var cb = get_enrich_color(enrich,gos[i],max_p_value);	
				var ge = ""; if(enrich.get(gos[i])){ge = $H(enrich.get(gos[i])).get("p_value");}
				var is_shown = false;				
				var is_enriched = true;
				if($H(enrich.get(gos[i])).get("hidden")==0){is_shown = true;}				
				if($H(enrich.get(gos[i])).get("ratio_log") < 0){is_enriched = false;}
				var b  = new RoundButton(xb,yb,local_bw,button_height,5,cb,is_enriched,is_shown,gos[i],db,ub,ge,small_button);
				button_array[global_bc] =  b;
				y_positions[gos[i]] = depth;
				b_positions[gos[i]] = global_bc;
				global_bc++;
				j++;
			    }
			 }			 
		     }						
		});				



		//create lines. Take into account
		for(var i=0;i<edges.length;i++){
			var child_go      = edges[i]["child"];
			var parent_go     = edges[i]["parent"];
			var child_depth	  = y_positions[child_go];
			var parent_depth  = y_positions[parent_go];
			//define better colors
			var l_color		= "#000000";					
			if(parent_depth == (child_depth-2)){l_color = "#525252";}
			if(parent_depth < (child_depth-2)){l_color = "#A2A2A2";}
			if(b_positions[child_go]!=undefined && b_positions[parent_go]!=undefined){
			    var child_button  = button_array[b_positions[child_go]];
			    var parent_button = button_array[b_positions[parent_go]];
			    //now, take these data into account, and generate the correct coordinates.
			    var x1 = child_button.x + Math.ceil(child_button.width/2); //middle of child go
			    var y1 = child_button.y; //top of child go
			    var x2 = parent_button.x + Math.ceil(parent_button.width/2); //middle of parent go
			    var y2 = parent_button.y + parent_button.height; //bottom of parent go
			    var l   = new ConnectionLine(x1,y1,x2,y2,l_color);
			    line_array[global_lc] = l;					
			    global_lc++;
			}
		}		
	
		
		 result[0]          = button_array;
		 result[1]          = line_array;
		 result[2]	    = legend_info;
		 return result;
	    }

	    

	    function drawLegend(canvas,legend_information){
		canvas.font             = "0.8em arial";	 
		canvas.fillStyle	= "#000000";
		var start_x		= 30;
		var max_depth		= parseInt(legend_information["max_depth"]);
		if(max_depth< GLOBAL_MAX_D){max_depth = GLOBAL_MAX_D;}
		var max_p_value		= parseInt(legend_information["max_p_value"]);			
		var start_y		= (max_depth+1)*60+30;			
		strokeRoundedRect(canvas,"#00AA55",start_x,start_y,20,6,2);
		fillTriangle(canvas,"#00AA55",start_x,start_y,20,6,2,3); 
		canvas.fillText("Enriched GO term",start_x+30,start_y+6);			
		start_y			= start_y+10;	
		strokeRoundedRect(canvas,"#000000",start_x,start_y,20,6,2);
		canvas.fillText("Neither enriched or depleted GO term",start_x+30,start_y+6);
		start_y			= start_y+10;
		strokeRoundedRect(canvas,"#00AA55",start_x,start_y,20,6,2);				
		canvas.fillText("Enriched GO term (partially redundant)",start_x+30,start_y+6);	
		start_y			= start_y+25;
		canvas.fillText("Enrichment = -log(p-value) : ",start_x,start_y+6);			
		var num_steps		= 10;
		var bls			= 20;
		var i			= 0;
		var x2_dist		= 160;		
		for(i=0;i<= num_steps;i++){
		    var p_value		= Math.ceil(max_p_value/num_steps*i);
		    canvas.fillStyle	= get_enrichment_color(p_value,max_p_value);
		    canvas.fillRect(start_x+x2_dist+i*bls,start_y,bls,10);	
		    canvas.fillStyle	= "#000000";
		    canvas.fillText(p_value,start_x+x2_dist+i*bls,start_y+20);
		}					
		canvas.fillStyle	= get_enrichment_color(max_p_value,max_p_value);
		canvas.fillRect(start_x+x2_dist+i*bls,start_y,bls,10);
		canvas.fillStyle	= "#000000";
		canvas.fillText(">"+max_p_value,start_x+x2_dist+i*bls,start_y+20);
		canvas.strokeStyle	= "#A2A2A2";
		canvas.strokeRect(start_x+x2_dist,start_y,(i+1)*bls,10);			
	    }



	    function strokeRoundedRect(canvas,color,x,y,w,h,r){
		var temp = canvas.strokeStyle;
		canvas.strokeStyle = color;
	     	canvas.beginPath();
		canvas.moveTo(x,y+r);
		canvas.lineTo(x,y+h-r);
		canvas.quadraticCurveTo(x,y+h,x+r,y+h);
		canvas.lineTo(x+w-r,y+h);
		canvas.quadraticCurveTo(x+w,y+h,x+w,y+h-r);
		canvas.lineTo(x+w,y+r);
		canvas.quadraticCurveTo(x+w,y,x+w-r,y);
		canvas.lineTo(x+r,y);
		canvas.quadraticCurveTo(x,y,x,y+r);
		canvas.stroke();
		canvas.strokeStyle = temp;	
	    }

	    function fillTriangle(canvas,color,x,y,w,h,r,i){
		var temp = canvas.fillStyle;
		canvas.fillStyle = color;
		canvas.beginPath();
		canvas.moveTo(x+w-i*r,y);
		canvas.lineTo(x+w,y+i*r);
		canvas.lineTo(x+w,y+r);
		canvas.quadraticCurveTo(x+w,y,x+w-r,y);
		canvas.lineTo(x+w-i*r,y);
		canvas.fill();
		canvas.fillStyle = temp;
	    }



	    /**
	     * Function which checks whether or not a certain button in the given button_array has as id the go_id
	     * @param go_id : Id against which to check in the button_array
	     * @param button_array : array of buttons
	     * @return boolean : indicates whether or not a button in the button array has - as id - the given go_id.
	     */
	    function goIsPresent(go_id,button_array){
		var result = false;
		for(var i=0;i < button_array.length;i++){
		    if(button_array[i].bc == go_id){
		 	result = true;
			break;
		    }
		}
		return result;
	    }





	    /*
	     * Function which, from a certain array of buttons, retrieves all those buttons that are present at a certain
             * depth. 
             * @param depth : this should equal the y-coordinate of the buttons
	     * @param buttons : array with buttons
             * @return array : Subset array of the buttons array, with only those buttons at a certain depth.
	     */
	    function getButtonsAtDepth(depth,buttons){
		var result_array = new Array();
		for(var i=0;i< buttons.length;i++){
		    var y = buttons[i].y;
		    if(y == depth){ result_array.push(buttons[i]);}			
	 	}
		return result_array;
	    }



	    /*
	     * Function which draws all buttons on the screen
	     * @param ctx : Canvas on which to draw
	     * @param button_array : array which contains all buttons
	     * @param indic : indicator of which button is highlighted (should have a different color)	     
  	     */ 
	    function drawButtons(ctx,button_array,indic){
		for(var i=0;i<button_array.length;i++){
		    if(i==indic){button_array[i].draw(ctx,true);}
		    else{button_array[i].draw(ctx,false);}
		}
	    }

	    /*
	     * Function which draws all the lines on the screen (should be called before drawButtons function,
	     * in order not to have lines on top of buttons).
	     * @param ctx : Canvas on which to draw
	     * @param line_array : array which contains all lines
	     */
	    function drawLines(ctx,line_array){
		    for(var i=0;i<line_array.length;i++){
			    line_array[i].draw(ctx);
		    }
	    }



	    /*
	     * line Object
	     */
	    function ConnectionLine(x1,y1,x2,y2,color){
		    this.x1                = x1;
		    this.y1                = y1;
		    this.x2                = x2;
		    this.y2                = y2;
		    //this.color                = color;
		    this.color             = "#C2C2C2";
		    this.draw                = function(canvas){
			    canvas.strokeStyle        = this.color;
			    canvas.lineWidth                = 1;
			    canvas.beginPath();
			    canvas.moveTo(this.x1,this.y1);
			    canvas.lineTo(this.x2,this.y2);
			    canvas.stroke();
		    }
	    }

	    function RoundButton(x,y,width,height,radius,color1,is_enriched,is_shown,button_content,info_content,hyperlink,p_value,small_buttons){
		    this.x            = x;
		    this.y            = y;		    
		    this.line_width   = 2;
		    this.width        = width;
		    this.height       = height;
		    this.radius       = radius;
		    this.color1       = color1;	
		    this.is_enriched  = is_enriched;	    
		    this.border_color = "#00AA55"; if(!is_enriched){this.border_color = "#AA0055";}		    
		    this.hover_color  = "#FFFFFF";
		    this.is_shown     = is_shown;
		    this.bc           = button_content;
		    this.ic           = info_content;
		    this.link         = hyperlink;
		    this.small_button = small_buttons;
		
		    //creating correct p_value to display
		    var tmp_p_value   = new String(p_value);
		    var tmp_p_b       = "";
		    var tmp_p_e	      = "";
		    if(tmp_p_value.indexOf(".")!= -1){tmp_p_b = tmp_p_value.substring(0,tmp_p_value.indexOf(".")+4);}
		    if(tmp_p_b=="0.000"){tmp_p_b = tmp_p_value.substring(0,tmp_p_value.indexOf(".")+7);}
		    if(tmp_p_value.indexOf("e")!= -1){tmp_p_e = tmp_p_value.substring(tmp_p_value.indexOf("e"));}
		    var tmp_p_value   = tmp_p_b+""+tmp_p_e;
		    if(tmp_p_value == ""){ tmp_p_value = "undefined"; this.border_color = "#000000";}
		    this.p_value      = tmp_p_value;
		    this.button_content = "";
		     		    
	
		    this.draw         = function(canvas,is_indic){
			    //setup for drawing the box
			    canvas.fillStyle = this.color1;
			    if(is_indic){canvas.fillStyle = this.hover_color;}
			    canvas.strokeStyle = this.border_color;			    
			    canvas.lineWidth = this.line_width;
			    canvas.beginPath();
			    canvas.moveTo(this.x,this.y+this.radius);
			    canvas.lineTo(this.x,this.y+this.height-this.radius);
			    canvas.quadraticCurveTo(this.x,this.y+this.height,this.x+this.radius,this.y+this.height);
			    canvas.lineTo(this.x+this.width-this.radius,this.y+this.height);
			    canvas.quadraticCurveTo(this.x+this.width,this.y+this.height,this.x+this.width,this.y+this.height-this.radius);
			    canvas.lineTo(this.x+this.width,this.y+this.radius);
			    canvas.quadraticCurveTo(this.x+this.width,this.y,this.x+this.width-this.radius,this.y);
			    canvas.lineTo(this.x+this.radius,this.y);
			    canvas.quadraticCurveTo(this.x,this.y,this.x,this.y+this.radius);
			    canvas.fill();
			    canvas.stroke();
				
			    if(this.is_shown){ //indicate that the node in question has been selected to be 'special'
				canvas.fillStyle = this.border_color;
				canvas.beginPath();
				canvas.moveTo(this.x+this.width-2*this.radius,this.y);
				canvas.lineTo(this.x+this.width,this.y+2*this.radius);
				canvas.lineTo(this.x+this.width,this.y+this.radius);
				canvas.quadraticCurveTo(this.x+this.width,this.y,this.x+this.width-this.radius,this.y);
				canvas.lineTo(this.x+this.width-2*this.radius,this.y);
				canvas.fill();
			    }

			    //setup for drawing the text
			    if(!this.small_button){
			        canvas.font             = "0.8em arial";
			        canvas.fillStyle        = "rgba(0,0,0,1)";
				if(this.button_content ==""){this.button_content = this.wordwrap(this.ic,15).split("\n");}
				canvas.fillText(this.button_content[0],this.x+5,this.y+12);
				if(this.button_content.length>1){canvas.fillText(this.button_content[1]+"...",this.x+5,this.y+24);}
			    }

		    }
		    this.drawInfoBox        = function(canvas,cv_width,cv_height,info_width,info_color,line_width){
			    var mbd           = 10;        //mbd = minimum_border_distance
			    //if(this.ic.length*5 < info_width){info_width = this.ic.length * 6;}
			    var info_x        = this.x + this.width/2 - info_width/2;
			    var info_y        = this.y + this.height/2;
			    //second height is for the p_value and title
			    var info_h        = Math.ceil(this.ic.length/40)*this.height + this.height;  
			    if(info_x < mbd){info_x = mbd;}//check for border left
			    if((info_x+info_width)>(cv_width-mbd)){info_x = cv_width-mbd-info_width;}//check for border right
			    if(info_y > (cv_height-mbd-info_h)){info_y = this.y - this.height/2-info_h;} //check bottom
			    //draw info box
			    canvas.globalAlpha        = 0.9;
			    canvas.fillStyle          = this.color1;//info_color;
			    canvas.strokeStyle        = this.border_color;
			    canvas.lineWidth          = line_width;
			    canvas.beginPath();
			    canvas.moveTo(info_x,info_y+this.radius);
			    canvas.lineTo(info_x,info_y+info_h-this.radius);
			    canvas.quadraticCurveTo(info_x,info_y+info_h,info_x+this.radius,info_y+info_h);
			    canvas.lineTo(info_x+info_width-this.radius,info_y+info_h);
			    canvas.quadraticCurveTo(info_x+info_width,info_y+info_h,info_x+info_width,info_y+info_h-this.radius);
			    canvas.lineTo(info_x+info_width,info_y+this.radius);
			    canvas.quadraticCurveTo(info_x+info_width,info_y,info_x+info_width-this.radius,info_y);
			    canvas.lineTo(info_x+this.radius,info_y);
			    canvas.quadraticCurveTo(info_x,info_y,info_x,info_y+this.radius);
			    canvas.fill();
			    canvas.stroke();
			    if(this.is_shown){ //indicate that the node in question has been selected to be 'special'
				canvas.fillStyle = this.border_color;
				canvas.beginPath();
				canvas.moveTo(info_x+info_width-3*this.radius,info_y);
				canvas.lineTo(info_x+info_width,info_y+3*this.radius);
				canvas.lineTo(info_x+info_width,info_y+this.radius);
				canvas.quadraticCurveTo(info_x+info_width,info_y,info_x+info_width-this.radius,info_y);
				canvas.lineTo(info_x+info_width-3*this.radius,info_y);
				canvas.fill();
			    }
				
			    canvas.globalAlpha        = 1.0;
			    canvas.font               = "1.0em arial";
			    canvas.fillStyle          = "rgba(0,0,0,1)";
			    var data                  = this.wordwrap(this.ic,40).split("\n");
			    canvas.fillText(this.bc,info_x+5,info_y+12);
			    var i = 0;						    
			    for(i=0;i<data.length;i++){
				    canvas.fillText(data[i],info_x+5,info_y+12+(i+1)*12);
			    }			    
			    canvas.fillText("p_value : "+this.p_value,info_x+5,info_y+12+(i+1)*12);
			    if(this.p_value!="undefined"){
				var ei = "";
				if(this.is_enriched){if(this.is_shown){ei="Significantly enriched GO term";}else{ei="Enriched GO term";}}
				else{if(this.is_shown){ei="Significantly depleted GO term";}else{ei="Depleted GO term";}}
				canvas.fillText(ei,info_x+5,info_y+12+(i+2)*12);				
			    }
		    }
		    this.wordwrap = function(str, int_width, str_break, cut) {
				 var m = ((arguments.length >= 2) ? arguments[1] : 75   );
				var b = ((arguments.length >= 3) ? arguments[2] : "\n" );
			    var c = ((arguments.length >= 4) ? arguments[3] : false);
				var i, j, l, s, r;
				str += '';
				if (m < 1) {
				    return str;
				}
				 for (i = -1, l = (r = str.split(/\r\n|\n|\r/)).length; ++i < l; r[i] += s) {
				    for (s = r[i], r[i] = ""; s.length > m; r[i] += s.slice(0, j) + ((s = s.slice(j)).length ? b : "")){
					j = c == 2 || (j = s.slice(0, m + 1).match(/\S*(\s)?$/))[1] ? m : j.input.length - j[0].length || c == 1 && m || j.input.length + (j =s.slice(m).match(/^\S*/)).input.length;
				    }
				}
				
				var temp = r[0].split(" ");
				var first_word = temp[0];
				if(first_word.length > int_width && temp.length>1 && r[0].indexOf("\n")==-1){
				    return temp.join("\n");	
				}				
				else{
				    return r.join("\n");
				}
		    }

		    this.makeHyperLink = function(){
			//alert(this.link);
			document.location = this.link;
		    }
	    }

         </script>

	
</div>
</div>	