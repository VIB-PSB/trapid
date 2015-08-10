package transcript_pipeline;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.SortedMap;
import java.util.SortedSet;
import java.util.TreeMap;
import java.util.TreeSet;

import javax.xml.transform.Result;
import javax.xml.transform.Source;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;

import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.traversal.NodeFilter;

import com.sun.org.apache.xerces.internal.dom.DocumentImpl;
import com.sun.org.apache.xerces.internal.dom.TreeWalkerImpl;
import com.sun.org.apache.xerces.internal.parsers.DOMParser;

/**
 * Create phyloxml from the newick tree, for data in the TRAPID database.
 * This is done to color the genes of species differently, 
 * and to also  include subset information if necessary.
 * 
 * @author mibel
 *
 */

/*
 * java -cp ./lib/mysql-connector-java-5.0.4-bin.jar:.:.. transcript_pipeline.CreatePhyloXML psbsql03 db_plaza_public_02_5 plaza_web plaza_web_roxor psbsql03 db_trapid_01 trapid_website trapid_webaccess 39 39_iOrtho_283 1 /www/bioapp/trapid/experiment_data/39/ /www/group/biocomp/extra/bioinformatics_prod/webtools/trapid/app/scripts/java/forester.jar /www/group/biocomp/extra/bioinformatics_prod/webtools/trapid/app/scripts/cfg/atv_config_file.cfg
 */
public class CreatePhyloXML {

	
	public static void main(String[] args){
		String plaza_database_server		= args[0];	//normally psbsql03.psb.ugent.be
		String plaza_database_name			= args[1];
		String plaza_database_login			= args[2];
		String plaza_database_password		= args[3];
		
		//workbench variables, necessary for storing homology/orthology information
		String trapid_server				= args[4];
		String trapid_name					= args[5];
		String trapid_login					= args[6];
		String trapid_password				= args[7];
					
		//taxonomy database --> same location as trapid, password is readonly plaza_web
		String taxonomy_name				= args[8];		
		
		//user experiment id
		String trapid_experiment_id			= args[9];
		
		//Gene family
		String gf_id						= args[10];
		
		//include subset
		String inc_sub						= args[11];
		
		//include meta_annot
		String inc_meta						= args[12];
				
		//Tmp dir 
		String tmp_dir						= args[13];

		//Forester jar file location
		String forester_jar_location		= args[14];		
		
		//Location basic configuration file
		String basic_config_location		= args[15];
		
		
		
		
		boolean include_subsets				= false; 
		if(inc_sub.equals("1")){include_subsets = true;}
		
		boolean include_meta				= false;
		if(inc_meta.equals("1")){include_meta = true;}
		
				
		try{		
			CreatePhyloXML cpx					= new CreatePhyloXML();
			Class.forName("com.mysql.jdbc.Driver");	
			Connection plaza_db_connection		= cpx.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
			Connection trapid_db_connection		= cpx.createDbConnection(trapid_server,trapid_name,trapid_login,trapid_password);
			Connection taxonomy_db_connection	= cpx.createDbConnection(trapid_server,taxonomy_name,plaza_database_login,plaza_database_password);
			
			//retrieve the information from the trapid database for this gene family.
			Map<String,String> gf_information	= cpx.getGeneFamilyInfo(trapid_db_connection,trapid_experiment_id,gf_id);
			if(gf_information==null){
				plaza_db_connection.close();
				trapid_db_connection.close();
				throw new Exception("Unable to retrieve gene family information for gf "+gf_id+" in  exp "+trapid_experiment_id);
			}
			
			//step1. Create a phyloxml version of the newick tree. We use the ATV code for this.
			String phyloxml_file_path			= cpx.newick2phyloxml(gf_information.get("tree"),gf_id,tmp_dir,forester_jar_location);
			
			//step2a. Retrieve mapping of species to taxids
			Map<String,String> species_taxids	= cpx.species2taxid(plaza_db_connection);			
			//step2b. Retrieve taxonomy information for the taxids, through species (species->taxinformation)
			Map<String,String> species_taxinfo	= cpx.species2taxinfo(taxonomy_db_connection,species_taxids);
			//step2c. Already close the taxonomy db connection, not needed anymore
			taxonomy_db_connection.close();
						
			//step3. We generate a color for all species 
			Map<String,String> species_colors	= cpx.speciestax2color(species_taxids,species_taxinfo);
			
			//step4. Get the subsets for this experiment, and create colors for them
			List<String> available_subsets			= cpx.getSubsets(trapid_db_connection,trapid_experiment_id);
			Map<String,String> subset_colors		= cpx.subset2color(available_subsets);
				
			//step5. Retrieve species information for all species in the gene family.
			Map<String,String> gene2species		= cpx.gene2species(plaza_db_connection,gf_information);
						
			//step6. Generate a configuration file containing all the necessary species color information and subset color information as well
			//String configuration_file_location	= cpx.createConfigFile(basic_config_location,species_colors,subset_colors,tmp_dir);
			cpx.createConfigFile(basic_config_location,species_colors,subset_colors,tmp_dir);
			
			//step7. Gather subset information
			Map<String,Set<String>> transript2subsets	= null;
			if(include_subsets){
				transript2subsets			= cpx.getTranscriptSubsets(trapid_db_connection,trapid_experiment_id,gf_id);
			}
			
			//step7b. Gather meta_annotation information for the transcripts.
			//this data is automatically added			
			Map<String,String> transcript2meta	= null;
			if(include_meta){
				transcript2meta 			= cpx.getTranscriptTypes(trapid_db_connection,trapid_experiment_id,gf_id);
			}
			
						
			//step8. Adapt the phyloxml data
			String phyloxml_data	= cpx.adaptPhyloXmlFile(gf_id,phyloxml_file_path,gene2species,transript2subsets,transcript2meta,include_subsets,include_meta,tmp_dir);
				
			//step9. Write phyloxml data to the database
			cpx.storeXML(trapid_db_connection, trapid_experiment_id, gf_id, phyloxml_data);
			
			//step10. Remove newick and old xml file
			File newick_file	= new File(tmp_dir+gf_id+".newick");
			File xml_file		= new File(tmp_dir+gf_id+".xml");
			File xml_tmp_file	= new File(tmp_dir+gf_id+".tmp.xml");
			newick_file.delete();
			xml_file.delete();
			xml_tmp_file.delete();

		}
		catch(Exception exc){
			exc.printStackTrace();
		}
	}
	
	private void storeXML(Connection conn,String exp_id,String gf_id,String phyloxml) throws Exception{
		Statement stmt	= conn.createStatement();
		String sql		= "UPDATE `gene_families` SET `xml_tree`='"+phyloxml+"' WHERE `experiment_id`='"+exp_id+"' AND `gf_id`='"+gf_id+"' ";
		stmt.execute(sql);		
		stmt.close();
	}
	
	
	private String adaptPhyloXmlFile(String gf_id,String phyloxml_file_path,Map<String,String>gene2species,
			Map<String,Set<String>> transcript2subsets,Map<String,String> transcript2meta,
			boolean include_subsets, boolean include_meta,
			String tmp_dir) throws Exception{
		DOMParser parser = new DOMParser();
		parser.parse(phyloxml_file_path);
		DocumentImpl doc = (DocumentImpl)parser.getDocument();
		Node root = doc.getLastChild();			
		AllElements filter = new AllElements();
		TreeWalkerImpl tw =(TreeWalkerImpl)doc.createTreeWalker(root,NodeFilter.SHOW_ALL, (NodeFilter)filter, true);
		
		//mapping from subset to location
		//only use the "used" subsets, as the image is otherwise distorted.
		Map<String,Integer> subset_location		= new HashMap<String,Integer>();
		if(transcript2subsets!=null){
			SortedSet<String> all_subsets_set	= new TreeSet<String>();
			for(String transcript:transcript2subsets.keySet()){
				all_subsets_set.addAll(transcript2subsets.get(transcript));
			}
			List<String> all_subsets			= new ArrayList<String>(all_subsets_set);
			for(int i=0;i<all_subsets.size();i++){
				String subset		= all_subsets.get(i);
				subset_location.put(subset, i);				
			}
		}
		
		//mapping from gene type to location
		Map<String,Integer> meta_location	= new HashMap<String,Integer>();
		if(transcript2meta!=null){
			SortedSet<String> all_meta_set	= new TreeSet<String>();
			for(String transcript:transcript2meta.keySet()){
				all_meta_set.add(transcript2meta.get(transcript));
			}
			List<String> all_meta	= new ArrayList<String>(all_meta_set);
			for(int i=0;i<all_meta.size();i++){
				String meta			= all_meta.get(i);
				meta_location.put(meta,i);
			}
		}
		
		
		adaptPhyloXmlData(tw,doc,gene2species,
				transcript2subsets,subset_location,include_subsets,
				transcript2meta,meta_location,include_meta
				);
		
		doc.normalizeDocument();
		Source source 		= new DOMSource(doc);
		Transformer xformer = TransformerFactory.newInstance().newTransformer();
		
		String tmp_xml_outputpath	= tmp_dir+gf_id+".tmp.xml";
		File tmp_xml_output			= new File(tmp_xml_outputpath);
		
		Result result		= new StreamResult(tmp_xml_output);
		xformer.transform(source, result);
		
		StringBuffer buff		= new StringBuffer();
		BufferedReader reader	= new BufferedReader(new FileReader(tmp_xml_output));
		String s				= reader.readLine();
		while(s!=null){
			buff.append(s.trim());
			s					= reader.readLine();
		}
		reader.close();
		String xml_data			= buff.toString().trim();						
		return xml_data;
	}
	
	private void adaptPhyloXmlData(TreeWalkerImpl tw,DocumentImpl doc,Map<String,String>gene2species,
			Map<String,Set<String>> transcript2subsets,Map<String,Integer> subset_location,boolean include_subsets,
			Map<String,String> transcript2meta,Map<String,Integer>meta_location,boolean include_meta
			)throws Exception{
		Node n = tw.getCurrentNode();
		if(n.hasChildNodes()){
			for(Node child=tw.firstChild();child!=null;child=tw.nextSibling()){	
				adaptPhyloXmlData(tw,doc,gene2species,transcript2subsets,subset_location,include_subsets,transcript2meta,meta_location,include_meta);
			}
		}
		else{
			String parentName = n.getParentNode().getNodeName();
			if(parentName.equals("name")){
				String nodeText = n.getTextContent();
				nodeText = nodeText.trim();
				if(nodeText!=null && !nodeText.equals("")){
					//add species info to gene
					if(gene2species.containsKey(nodeText)){
						Node greatParent = n.getParentNode().getParentNode();
						if(greatParent!=null){
							this.addSpeciesData(greatParent,doc,nodeText,gene2species);
						}
					}
					//add subset info to transcript
					else if(include_subsets && transcript2subsets!=null && transcript2subsets.containsKey(nodeText)){
						Node greatParent = n.getParentNode().getParentNode();
						if(greatParent!=null){
							this.addSubsetData(greatParent,doc,nodeText,transcript2subsets,subset_location);
						}
					}
					else if(include_meta && transcript2meta!=null && transcript2meta.containsKey(nodeText)){
						Node greatParent = n.getParentNode().getParentNode();
						if(greatParent!=null){
							this.addMetaData(greatParent,doc,nodeText,transcript2meta,meta_location);
						}
					}
				}					
			}
		}
		tw.setCurrentNode(n);
	}
	
	
	private void addSpeciesData(Node n,DocumentImpl doc,String gene_id,Map<String,String>gene2species){		
		if(n==null ||  doc==null){
			return;
		}
		Element taxonomyElement = doc.createElement("taxonomy");
		Element codeElement = doc.createElement("code");
		codeElement.setTextContent(gene2species.get(gene_id));
		taxonomyElement.appendChild(codeElement);
		n.appendChild(taxonomyElement);
	}
	
	private void addSubsetData(Node n,DocumentImpl doc,String transcript_id,Map<String,Set<String>> transcript2subsets,Map<String,Integer> subset_location){		
		if(n==null || doc==null || subset_location.size()==0){
			return;
		}
		if(!transcript2subsets.containsKey(transcript_id) || transcript2subsets.get(transcript_id).size()==0){
			return;
		}
		Element sequenceElement = doc.createElement("sequence");
		Element domainElement 	= doc.createElement("domain_architecture");		
		//domainElement.setAttribute("length",""+(subset_location.size()+1));
		domainElement.setAttribute("length",""+(subset_location.size()*100));
		
		for(String subset:transcript2subsets.get(transcript_id)){
			int location_start	= subset_location.get(subset);
			int location_stop	= location_start+1;			
			Element iprElement = doc.createElement("domain");
			iprElement.setAttribute("from",""+(location_start*100));
			iprElement.setAttribute("to",""+(location_stop*100));
			iprElement.setAttribute("confidence","1.0");
			iprElement.setTextContent(subset);
			domainElement.appendChild(iprElement);
		}
		
		sequenceElement.appendChild(domainElement);
		n.appendChild(sequenceElement);		
	}
	
	private void addMetaData(Node n,DocumentImpl doc,String transcript_id,Map<String,String>transcript2meta,Map<String,Integer>meta_location){
		if(n==null || doc==null || meta_location.size()==0){
			return;
		}
		if(!transcript2meta.containsKey(transcript_id) || transcript2meta.get(transcript_id).equals("")){
			return;
		}
		Element sequenceElement = doc.createElement("sequence");
		Element domainElement 	= doc.createElement("domain_architecture");	
		domainElement.setAttribute("length",""+(meta_location.size()*100));
		String meta				= transcript2meta.get(transcript_id);
		int location_start		= meta_location.get(meta);
		int location_stop		= location_start+1;			
		Element iprElement = doc.createElement("domain");
		iprElement.setAttribute("from",""+(location_start*100));
		iprElement.setAttribute("to",""+(location_stop*100));
		iprElement.setAttribute("confidence","1.0");
		iprElement.setTextContent(meta);
		domainElement.appendChild(iprElement);
		sequenceElement.appendChild(domainElement);
		n.appendChild(sequenceElement);		
	}
	
	
	
	class AllElements implements NodeFilter{
		public short acceptNode (Node n){
			if (n.getNodeType() == Node.ELEMENT_NODE){
				return FILTER_ACCEPT;
			}
		    return FILTER_ACCEPT;
		}
	}
	
	
	private Map<String,String> getTranscriptTypes(Connection conn,String exp_id,String gf_id) throws Exception{
		Map<String,String> result			= new HashMap<String,String>();
		//get transcripts and their associated types		
		String sql	= "SELECT `transcript_id`,`meta_annotation` FROM `transcripts` WHERE `experiment_id`='"+exp_id+"' AND `gf_id`='"+gf_id+"' ";
		Statement stmt = conn.createStatement();
		ResultSet set	= stmt.executeQuery(sql);
		while(set.next()){
			String transcript	= set.getString(1);
			String meta_annot	= set.getString(2);
			result.put(transcript, meta_annot);
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	private Map<String,Set<String>> getTranscriptSubsets(Connection conn,String exp_id,String gf_id) throws Exception{
		Map<String,Set<String>> result		= new HashMap<String,Set<String>>();
		StringBuffer buff					= new StringBuffer();
		//get transcripts in gene family
		String sql1	= "SELECT `transcript_id` FROM `transcripts` WHERE `experiment_id`='"+exp_id+"' AND `gf_id`='"+gf_id+"' ";
		Statement stmt1 = conn.createStatement();
		ResultSet set1	= stmt1.executeQuery(sql1);
		while(set1.next()){
			String transcript	= set1.getString(1);
			buff.append(transcript+" ");
		}
		set1.close();
		stmt1.close();
		String transcript_string		= "('"+buff.toString().trim().replaceAll(" ","','")+"')";
		//get the labels for these transcripts
		String sql2	= "SELECT `transcript_id`,`label` FROM `transcripts_labels` WHERE `experiment_id`='"+exp_id+"' AND `transcript_id` IN "+transcript_string;
		Statement stmt2	= conn.createStatement();
		ResultSet set2	= stmt2.executeQuery(sql2);
		while(set2.next()){
			String transcript_id	= set2.getString(1);
			String label			= set2.getString(2);
			if(!result.containsKey(transcript_id)){
				result.put(transcript_id, new HashSet<String>());
			}
			result.get(transcript_id).add(label);
		}
		set2.close();
		stmt2.close();		
		return result;
	}
	

	private String createConfigFile(String basic_config_location,Map<String,String> species_colors,Map<String,String> subset_colors,String tmp_dir) throws Exception{
		String config_location		= tmp_dir+"atv_config.cfg";
		File config_file			= new File(config_location);
		File basic_config_file		= new File(basic_config_location);		
		if(config_file.exists()){config_file.delete();}
		
		//copy basic configuration file 
		BufferedReader reader		= new BufferedReader(new FileReader(basic_config_file));
		BufferedWriter writer		= new BufferedWriter(new FileWriter(config_file));
		String s					= reader.readLine();
		while(s!=null){
			writer.write(s+"\n");
			s						= reader.readLine();
		}
		reader.close();		
		writer.write("\n\n");
		
		//append color information for species and subsets
		for(String species:species_colors.keySet()){
			String color	= species_colors.get(species);
			writer.write("species_color: "+species+"    0x"+color+"\n");
		}
		writer.write("\n\n");
		
		for(String subset:subset_colors.keySet()){
			String color	= subset_colors.get(subset);
			writer.write("domain_color: "+subset+"    0x"+color+"\n");
		}
		writer.write("\n");
		writer.close();
				
		return config_location;
	}
	
	
	private Map<String,String> gene2species(Connection conn,Map<String,String> gf_information) throws Exception{
		Map<String,String> result		= new HashMap<String,String>();
		String plaza_gf_id				= gf_information.get("plaza_gf_id");
		String gf_content				= gf_information.get("gf_content");
		if(plaza_gf_id==null && gf_content==null){throw new Exception("No Gene Family information present");}
		if(plaza_gf_id!=null){ //comes from hom
			String sql		= "SELECT a.`gene_id`,b.`species` FROM `gf_data` a,`annotation` b WHERE a.`gf_id`='"+plaza_gf_id+"' AND b.`gene_id`=a.`gene_id` ";
			Statement stmt	= conn.createStatement();
			ResultSet set	= stmt.executeQuery(sql);
			while(set.next()){
				String gene_id	= set.getString(1);
				String species	= set.getString(2);
				result.put(gene_id,species);
			}
			set.close();
			stmt.close();
		}
		else if(gf_content!=null){ //integrative orthology
			//System.out.println(gf_content);
			String gf_content_string	= "('"+gf_content.trim().replaceAll(" ","','")+"')";
			//System.out.println(gf_content_string);
			String sql					= "SELECT `gene_id`,`species` FROM `annotation` WHERE `gene_id` IN "+gf_content_string;
			Statement stmt	= conn.createStatement();
			ResultSet set	= stmt.executeQuery(sql);
			while(set.next()){
				String gene_id	= set.getString(1);
				String species	= set.getString(2);
				result.put(gene_id,species);
			}
			set.close();
			stmt.close();
		}		
		return result;
	}
	
	
	private Map<String,String> subset2color(List<String> subsets){
		String[] colors	= {"FF0000","00FF00","0000FF","FF9900","FF0099","0099FF",
				"9900FF","99FF00","00FF99","33FF33"};
		
		Map<String,String> result	= new HashMap<String,String>();
		if(subsets.size()==0){return result;}
		else if(subsets.size()<=10){
			for(int i=0;i<subsets.size();i++){
				String subset	= subsets.get(i);
				String color	= colors[i];
				result.put(subset, color);
			}
			return result;
		}
		else{
			for(int i=0;i<10;i++){
				String subset	= subsets.get(i);
				String color	= colors[i];
				result.put(subset, color);
			}
			int num_subsets		= subsets.size();
			int num_parts_red	= num_subsets;
			int num_parts_green	= num_subsets+1;
			int num_parts_blue	= num_subsets+2;
			int inc_red			= 255/num_parts_red;
			int inc_green		= 255/num_parts_green;
			int inc_blue		= 255/num_parts_blue;
			int counter			= 0;
			int start_red		= 60;
			int start_green		= 120;
			int start_blue		= 240;	
			
			for(int i=10;i<subsets.size();i++){
				String subset	= subsets.get(i);
				int new_red		= Math.abs((start_red+counter*inc_red)%255);
				int new_green	= Math.abs((start_green+counter*inc_green)%255);
				int new_blue	= Math.abs((start_blue+counter*inc_blue)%255);
				counter++;
				int rgb					= new_red*65536+new_green*256+new_blue;
				String color			= ""+Integer.toHexString(rgb).toUpperCase();
				while(color.length()<6){color = "0"+color;}
				result.put(subset, color);
			}						
			return result;
		}
	}
	
	
	
	private List<String> getSubsets(Connection conn,String exp_id) throws Exception{
		SortedSet<String> result		= new TreeSet<String>();
		Statement stmt					= conn.createStatement();
		String sql						= "SELECT DISTINCT(`label`) FROM `transcripts_labels` WHERE `experiment_id`=' "+exp_id+"' ";		
		ResultSet set					= stmt.executeQuery(sql);
		while(set.next()){
			String label				= set.getString(1);
			result.add(label);
		}
		set.close();
		stmt.close();	
		List<String> res	= new ArrayList<String>(result);
		return res;
	}
	
	
	//species to tax id mapping.
	private Map<String,String> species2taxid(Connection conn) throws Exception{
		Map<String,String> result		= new HashMap<String,String>();
		Statement stmt					= conn.createStatement();
		String sql						= "SELECT `species`,`tax_id` FROM `annot_sources` ";
		ResultSet set					= stmt.executeQuery(sql);
		while(set.next()){
			String species				= set.getString("species");
			String tax_id				= set.getString("tax_id");
			result.put(species, tax_id);
		}
		set.close();
		stmt.close();		
		return result;
	}
	
	
	//species to tax info through taxonomy database 
	private Map<String,String> species2taxinfo(Connection conn,Map<String,String> species2taxids) throws Exception{
		Map<String,String> result			= new HashMap<String,String>();
		PreparedStatement stmt				= conn.prepareStatement("SELECT `tax` FROM `full_taxonomy` WHERE `txid`=?");
		for(String species:species2taxids.keySet()){
			String taxid					= species2taxids.get(species);
			stmt.setString(1,taxid);
			ResultSet set					= stmt.executeQuery();
			if(set.next()){
				String taxonomy_information	= set.getString(1);
				result.put(species, taxonomy_information);
			}
			else{
				throw new Exception("Unable to retrieve taxonomy information for species "+species+" ("+taxid+")");
			}
			set.close();
		}
		stmt.close();
		return result;		
	}
	
	
	//Maps each tax id to a color 
	private Map<String,String> speciestax2color(
			Map<String,String> species2taxids,
			Map<String,String> species2taxinfo
			){
		
		Map<String,String> result	= new HashMap<String,String>();
		
		//parse the taxonomy information.
		//depth->parent->children
		SortedMap<Integer,Map<String,Set<String>>> parsed_taxonomy	= new TreeMap<Integer,Map<String,Set<String>>>();
		Map<String,Integer> full_taxonomy_count					= new HashMap<String,Integer>();

		for(String species: species2taxinfo.keySet()){
			String taxinfo			= species2taxinfo.get(species);
			String new_taxinfo		= taxinfo+";"+species;
			String[] split			= new_taxinfo.split(";");
			for(int i=0;i<split.length;i++){
				String s			= split[i].trim();
				if(!parsed_taxonomy.containsKey(i)){parsed_taxonomy.put(i,new HashMap<String,Set<String>>());}
				if(!parsed_taxonomy.get(i).containsKey(s)){parsed_taxonomy.get(i).put(s,new HashSet<String>());}
				if(i!=(split.length-1)){
					String child	= split[i+1].trim();
					parsed_taxonomy.get(i).get(s).add(child);
				}		
				if(!full_taxonomy_count.containsKey(s)){full_taxonomy_count.put(s,0);}
				full_taxonomy_count.put(s,(full_taxonomy_count.get(s)+1));
			}			
		}
			
		//now, use the taxonomy information to split up the color ranges.
		//By doing so, species from the same clades will be displayed with similar schemes
		int[] min_max_colors = {20,230,20,230,20,230};
		Map<String,int[]> color_ranges	= getColorRanges(parsed_taxonomy,full_taxonomy_count,0,"cellular organisms",min_max_colors,new HashMap<String,int[]>());
		for(String species:species2taxinfo.keySet()){
			int[] min_max_color			= color_ranges.get(species);
			/*int c						= (min_max_color[0]+min_max_color[1])/2;
			int red						= c;
			int green					= 255-c;
			int blue					= 128-c/2;*/
			int red						= (min_max_color[0]+min_max_color[1])/2;
			int green					= (min_max_color[2]+min_max_color[3])/2;
			int blue					= (min_max_color[4]+min_max_color[5])/2;
			int rgb						= red*65536+green*256+blue;
			String color = ""+Integer.toHexString(rgb).toUpperCase();
			result.put(species, color);
		}
		
		/*
		System.out.println("<html><head></head><body>");
		for(String species:result.keySet()){
			System.out.println("<div style='width:400px;height:50px;color:#"+result.get(species)+"'>"+species+"</div>");			
		}
		System.out.println("</body></html>");
		*/
	
		return result;
	}
	
	
	private Map<String,int[]> getColorRanges(
			SortedMap<Integer,Map<String,Set<String>>> parsed_taxonomy,
			Map<String,Integer> full_taxonomy_count,
			int current_depth,
			String current_clade,
			int[] min_max_colors,
			Map<String,int[]> current
			){		
		//first check: is this an species, if so, return the current min/max combination
		if(parsed_taxonomy.get(current_depth).get(current_clade).size() == 0){			
			current.put(current_clade, min_max_colors);
			return current;
		}
		//second check: is this not an end species, but it has only 1 child -> do not split and continue on to its children
		if(parsed_taxonomy.get(current_depth).get(current_clade).size()==1){
			int next_depth  = current_depth+1;
			List<String> child_clades	=  new ArrayList<String>(parsed_taxonomy.get(current_depth).get(current_clade));
			String next_clade			= child_clades.get(0);
			return this.getColorRanges(parsed_taxonomy, full_taxonomy_count, next_depth, next_clade, min_max_colors, current);
		}
		
		//ok, there are multiple child clades present
		//split the available color range according to the number of species which can be present in the clade		
		int total_species	= full_taxonomy_count.get(current_clade);		
		
		int min_color_red	= min_max_colors[0]; int max_color_red	= min_max_colors[1];
		int min_color_green	= min_max_colors[2]; int max_color_green= min_max_colors[3];
		int min_color_blue	= min_max_colors[4]; int max_color_blue	= min_max_colors[5];
		
		int current_low_red		= min_color_red;
		int current_low_green	= min_color_green;
		int current_low_blue	= min_color_blue;
		
		List<String> child_clades	= new ArrayList<String>(parsed_taxonomy.get(current_depth).get(current_clade));
		int start_red				= (current_depth%child_clades.size());
		int start_green				= ((current_depth+1)%child_clades.size());
		int start_blue				= ((current_depth+2)%child_clades.size());
		
		Map<String,int[]> color_maps	= new HashMap<String,int[]>();
		for(String child_clade:child_clades){
			int[] tmp				= new int[6];
			color_maps.put(child_clade, tmp);
		}
		//red		
		for(int i=start_red;i<(child_clades.size()+start_red);i++){
			int index				= i%(child_clades.size());
			String child_clade		= child_clades.get(index);
			int num_species			= full_taxonomy_count.get(child_clade);			
			int new_min_color_red	= current_low_red;
			int new_max_color_red	= current_low_red + (max_color_red-min_color_red)*num_species/total_species;
			current_low_red			= new_max_color_red;
			int[] val				= color_maps.get(child_clade);
			val[0]					= new_min_color_red;
			val[1]					= new_max_color_red;
			color_maps.put(child_clade, val);
		}
		
		//green
		for(int i=start_green;i<(child_clades.size()+start_green);i++){
			int index				= i%(child_clades.size());
			String child_clade		= child_clades.get(index);
			int num_species			= full_taxonomy_count.get(child_clade);			
			int new_min_color_green	= current_low_green;
			int new_max_color_green	= current_low_green + (max_color_green-min_color_green)*num_species/total_species;
			current_low_green		= new_max_color_green;
			int[] val				= color_maps.get(child_clade);
			val[2]					= new_min_color_green;
			val[3]					= new_max_color_green;
			color_maps.put(child_clade, val);
		}
		
		//blue
		for(int i=start_blue;i<(child_clades.size()+start_blue);i++){
			int index				= i%(child_clades.size());
			String child_clade		= child_clades.get(index);
			int num_species			= full_taxonomy_count.get(child_clade);			
			int new_min_color_blue	= current_low_blue;
			int new_max_color_blue	= current_low_blue + (max_color_blue-min_color_blue)*num_species/total_species;
			current_low_blue		= new_max_color_blue;
			int[] val				= color_maps.get(child_clade);
			val[4]					= new_min_color_blue;
			val[5]					= new_max_color_blue;
			color_maps.put(child_clade, val);
		}
		
		for(String child_clade:child_clades){
			int next_depth				= current_depth+1;
			int[] new_min_max_colors	= color_maps.get(child_clade);
			Map<String,int[]> res		= this.getColorRanges(parsed_taxonomy, full_taxonomy_count, next_depth, child_clade, new_min_max_colors, current);
			current.putAll(res);
		}
				
		return current;
	}
	
	/*
	private Map<String,int[]> getColorRanges(
			SortedMap<Integer,Map<String,Set<String>>> parsed_taxonomy,
			Map<String,Integer> full_taxonomy_count,
			int current_depth,
			String current_clade,
			int min_color,
			int max_color,
			Map<String,int[]> current
			){		
		//first check: is this an species, if so, return the current min/max combination
		if(parsed_taxonomy.get(current_depth).get(current_clade).size() == 0){
			int[] res	= {min_color,max_color};
			current.put(current_clade, res);
			return current;
		}
		//second check: is this not an end species, but it has only 1 child -> do not split and continue on to its children
		if(parsed_taxonomy.get(current_depth).get(current_clade).size()==1){
			int next_depth  = current_depth+1;
			List<String> child_clades	=  new ArrayList<String>(parsed_taxonomy.get(current_depth).get(current_clade));
			String next_clade			= child_clades.get(0);
			return this.getColorRanges(parsed_taxonomy, full_taxonomy_count, next_depth, next_clade, min_color, max_color, current);
		}
		
		//ok, there are multiple child clades present
		//split the available color range according to the number of species which can be present in the clade		
		int total_species	= full_taxonomy_count.get(current_clade);		
		int current_low		= min_color;
		for(String child_clade:parsed_taxonomy.get(current_depth).get(current_clade)){
			int num_species				= full_taxonomy_count.get(child_clade);
			int next_depth				= current_depth+1;
			int new_min_color			= current_low;
			int new_max_color			= current_low + (max_color-min_color)*num_species/total_species;
			current_low					= new_max_color;			
			Map<String,int[]> res		= this.getColorRanges(parsed_taxonomy, full_taxonomy_count, next_depth, child_clade, new_min_color, new_max_color, current);
			current.putAll(res);
		}
		return current;
	}*/
	
	
	/*
	 * Create phyloxml file from newick
	 */
	private String newick2phyloxml(String newick,String gf_id,String tmp_dir,String forester_jar_location) throws Exception{
		if(newick==null || newick.length()==0){
			throw new Exception("No newick tree present");
		}
		
		String newick_file_path		= tmp_dir+gf_id+".newick";
		String phyloxml_file_path	= tmp_dir+gf_id+".xml"; 
		
		//write the content of the newick tree to the file
		BufferedWriter newick_writer	= new BufferedWriter(new FileWriter(new File(newick_file_path)));
		newick_writer.write(newick+"\n");
		newick_writer.close();
		
		//execute forester jar file in order to create a basic XML file
		Runtime rt			= Runtime.getRuntime();
		String java_exec 	= "java -Djava.awt.headless=true -cp "+forester_jar_location+" org.forester.application.phyloxml_converter ";
		String java_params	= "-f=nn -i -m "+newick_file_path+" "+phyloxml_file_path+" ";
		Process p			= rt.exec(java_exec+" "+java_params);
		p.waitFor();
		
		//ok, check whether or not the phyloxml file has actually been generated
		File phyloxml_file	= new File(phyloxml_file_path);
		if(!phyloxml_file.exists()){throw new Exception("Phyloxml file has not been generated!");}
				
		return phyloxml_file_path;
	}
	
	
	/**
	 * 
	 * @param conn
	 * @param exp_id
	 * @param gf_id
	 * @return
	 * @throws Exception
	 */
	private Map<String,String> getGeneFamilyInfo(Connection conn,String exp_id,String gf_id) throws Exception{
		Map<String,String> result		= null; 
		Statement stmt					= conn.createStatement();
		String sql						= "SELECT * FROM `gene_families` WHERE `experiment_id`='"+exp_id+"' AND `gf_id`='"+gf_id+"' ";
		ResultSet set					= stmt.executeQuery(sql);
		if(set.next()){
			result						= new HashMap<String,String>();
			String plaza_gf_id			= set.getString("plaza_gf_id");
			String gf_content			= set.getString("gf_content");
			String used_species			= set.getString("used_species");
			String tree					= set.getString("tree");
			result.put("plaza_gf_id",plaza_gf_id);
			result.put("gf_content",gf_content);
			result.put("used_species",used_species);
			result.put("tree",tree);
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	
	private Connection createDbConnection(String server,String database,String login,String password) throws Exception{
		String url		= "jdbc:mysql://"+server+"/"+database;
		Connection conn	= DriverManager.getConnection(url,login,password);
		return conn;
	}
	
	
}
