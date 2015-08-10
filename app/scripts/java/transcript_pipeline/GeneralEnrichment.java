package transcript_pipeline;


import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;



/**
 * Class to compute the general functional enrichment of a subset of genes, 
 * through an input file of genes.
 * 
 * Connection for functional data itself is still done through the reference database (if necessary).
 * 
 * @author Michiel Van Bel
 *
 */
public class GeneralEnrichment {

	
	public static void main(String[] args){
		
		GeneralEnrichment	gge		= new GeneralEnrichment();
		try{
			//IMPORTANT!!!
			//DATABASE VARIABLES HERE ARE NOT STORED IN JAVA CODE - DUE TO SECURITY CONSTRAINTS - 
			//BUT PASSED THROUGH PHP AND PERL!!
			
			//database variables, necessary for retrieving GO parent/child information (if necessary).
			String plaza_database_server		= args[0];	//normally psbsql03.psb.ugent.be
			String plaza_database_name			= args[1];
			String plaza_database_login			= args[2];
			String plaza_database_password		= args[3];
			
			String data_type							= args[4];
			String background_funcannot_mapping_file	= args[5];
			String test_funcannot_mapping_file			= args[6];
			String output_file							= args[7];						
			double cutoff								= Double.parseDouble(args[8]);
			
			//expand each GO-mapping into parental GO mapping as well.
			//not necessary for TRAPID, as total raw data is stored in there, with all parent/child relations.
			//only used when data_type is go
			boolean expand_go_mapping_input				= Boolean.parseBoolean(args[9]);
																	
			Map<String,Set<String>> background_gene_funcannot	= gge.readDataFile(background_funcannot_mapping_file);
			Map<String,Set<String>> test_gene_funcannot			= gge.readDataFile(test_funcannot_mapping_file);
			System.out.println(" - retrieved and read gene-funcannot files");
			
			if(data_type.equalsIgnoreCase("go")){
				Class.forName("com.mysql.jdbc.Driver");	
				Connection plaza_db_connection		= gge.createDbConnection(plaza_database_server,plaza_database_name,plaza_database_login,plaza_database_password);
				Map<String,GoData> extended_go					= gge.loadGoData(plaza_db_connection);
				System.out.println(" - loaded extended-go data into memory");
				Map<String,Map<String,Set<String>>> go_graph	= gge.loadGOGraph(plaza_db_connection,extended_go);
				System.out.println(" - read GO graph from database");
				if(expand_go_mapping_input){
					background_gene_funcannot				= gge.expandGeneGoData(background_gene_funcannot,go_graph.get("child2parents"));
					test_gene_funcannot						= gge.expandGeneGoData(test_gene_funcannot,go_graph.get("child2parents"));
					System.out.println(" - expanded gene-go data from GO graph");
				}
				EnrichmentResult er					= gge.performEnrichment(extended_go, background_gene_funcannot, test_gene_funcannot);
				System.out.println(" - performed initial GO enrichment ");
				//now, hide the parental terms of results in case of better enrichmentscore
				er									= gge.hideParents(er,go_graph.get("child2parents"),go_graph.get("parent2children"),cutoff,test_gene_funcannot.keySet(),background_gene_funcannot);
				System.out.println(" - hiding enriched parent/childs based on GO graph");				
				gge.writeOutput(er,cutoff,output_file);
				plaza_db_connection.close();
			}
			else if(data_type.equalsIgnoreCase("ipr")){
				EnrichmentResult er					= gge.performEnrichment(background_gene_funcannot, test_gene_funcannot);
				System.out.println(" - performed initial enrichment ");
				gge.writeOutput(er,cutoff,output_file);
			}
			else{
				throw new Exception("Unknown functional data type");
			}
			
			
			
			
			
			
			
			
			
			
		}
		catch(Exception exc){
			exc.printStackTrace();
			System.exit(1);
		}		
	}
	
	
	
	private void writeOutput(EnrichmentResult er,double cutoff,String output_file) throws Exception{		

		BufferedWriter writer	= new BufferedWriter(new FileWriter(new File(output_file),false));
		for(String go : er.enrichmentFactorDec.keySet()){		
			//double scoreLog = er.enrichmentFactorLog.get(go);	
			double scoreLog = er.enrichmentFactorLog.get(go);
			double pmf 		= er.enrichmentHypergeometric.get(go);
			double ratio	= er.subsetRatio.get(go);
			//long ratioGf	= er.gfRatio.get(go);			
			if(pmf<=cutoff){
				StringBuffer buffer	= new StringBuffer();
				buffer.append(go+"\t");
				if(er.hiddenParents!=null &&er.hiddenParents.containsKey(go) && er.hiddenParents.get(go)){buffer.append("1\t");}
				else{buffer.append("0\t");}
				buffer.append(pmf+"\t");
				buffer.append(scoreLog+"\t");
				buffer.append(ratio+"\n");
				writer.write(buffer.toString());
			}
		}
		writer.close();
	}
	
	
	private EnrichmentResult hideParents(
			EnrichmentResult er, 
			Map<String,Set<String>> go_child2parents,
			Map<String,Set<String>> go_parent2children,
			double cutoff,
			Set<String>genes,
			Map<String,Set<String>> gene_go_parents){
		
		EnrichmentResult result			= er;
		
		HashSet<String> unique_gos		= new HashSet<String>();
		for(String gene_id:genes){
			if(gene_go_parents.containsKey(gene_id)){
				for(String go : gene_go_parents.get(gene_id)){
					unique_gos.add(go);						
				}
			}
		}
		
		
		Map<String,Boolean> hiddenParents	= new HashMap<String,Boolean>();		
		//first iteration: hiden the parents
		for(String go : unique_gos ){
			if(er.enrichmentHypergeometric.containsKey(go)){
				double pmfChild			= er.enrichmentHypergeometric.get(go);
				double scoreLogChild	= Math.abs(er.enrichmentFactorLog.get(go));
				if(pmfChild<=cutoff){
					if(go_child2parents.get(go)!=null){					
						Set<String> parents	= go_child2parents.get(go);
						for(String parent_go : parents){
							if(er.enrichmentHypergeometric.containsKey(parent_go)){
								double pmfParent		= er.enrichmentHypergeometric.get(parent_go);
								double scoreLogParent	= Math.abs(er.enrichmentFactorLog.get(parent_go));
								if(pmfChild < pmfParent && scoreLogChild > scoreLogParent){
									hiddenParents.put(parent_go,true);
								}
							}
						}
					}
				}
			}
		}
		
		
		//second iteration: hide the children
		for(String go:unique_gos){
			if(er.enrichmentHypergeometric.containsKey(go)){
				double pmfParent			= er.enrichmentHypergeometric.get(go);
				double scoreLogParent		= Math.abs(er.enrichmentFactorLog.get(go));
				if(pmfParent<=cutoff && ((hiddenParents.containsKey(go) && !hiddenParents.get(go)) || !hiddenParents.containsKey(go))){
					if(go_parent2children.get(go)!=null){
						Set<String> children	=	 go_parent2children.get(go);
						for(String child_go : children ){
							if(er.enrichmentHypergeometric.containsKey(child_go)){
								double pmfChild			= er.enrichmentHypergeometric.get(child_go);
								double scoreLogChild	= Math.abs(er.enrichmentFactorLog.get(child_go));
								if( pmfChild > pmfParent && scoreLogChild < scoreLogParent){
									hiddenParents.put(child_go,true);
								}
							}
						}					
					}
				}
			}
		}		
		result.hiddenParents = hiddenParents;				
		return result;
	}
	
	
	
	/**
	 * Deduct the number of associated genes per GO term, based on the gene-go data
	 * @param gene_go genes to sets of gos
	 * @return Mapping from GO to #genes
	 */
	private Map<String,Integer> getGoCounts(Map<String,Set<String>> gene_go,Map<String,GoData> extended_go){
		Map<String,Integer> result	= new HashMap<String,Integer>();
		for(String gene_id:gene_go.keySet()){
			for(String go:gene_go.get(gene_id)){
				if(extended_go.containsKey(go)){
					if(!result.containsKey(go)){result.put(go,0);}
					result.put(go,result.get(go)+1);
				}
			}
		}		
		return result;
	}
	
	private Map<String,Integer> getFuncAnnotCounts(Map<String,Set<String>> gene_funcannot){
		Map<String,Integer> result	= new HashMap<String,Integer>();
		for(String gene_id:gene_funcannot.keySet()){
			for(String funcannot:gene_funcannot.get(gene_id)){				
				if(!result.containsKey(funcannot)){result.put(funcannot,0);}
				result.put(funcannot,result.get(funcannot)+1);
			}
		}		
		return result;
	}
	
	
	
	
	private Map<String,Integer> getGoTypes(Map<String,Integer> go_counts,Map<String,GoData> extended_go){
		Map<String,Integer> result	= new HashMap<String,Integer>();
		for(String go:go_counts.keySet()){
			if(extended_go.containsKey(go)){
				String type			= extended_go.get(go).type;
				if(!result.containsKey(type)){result.put(type, 0);}
				result.put(type, result.get(type)+1);
			}
		}
		return result;
	}
	
	private Map<String,Double> getRatios(Map<String,Integer> go_counts,int full_size){
		Map<String,Double> result	= new HashMap<String,Double>();
		double full			= full_size;
		for(String go:go_counts.keySet()){
			double set_count	= go_counts.get(go);			
			double res			= set_count/full;
			result.put(go,res);
		}
		return result;
	}
	
	
	
	private EnrichmentResult performEnrichment(
			Map<String,Set<String>> background_gene_funcannot,
			Map<String,Set<String>> test_gene_funcannot){
				
		//step 1: get a mapping from gos to counts of genes for the subset and the background
		Map<String,Integer> background_funcannot_counts	= this.getFuncAnnotCounts(background_gene_funcannot);
		Map<String,Integer> test_funcannot_counts		= this.getFuncAnnotCounts(test_gene_funcannot);
			
				
		//step 3: get background frequency counts as well
		Map<String,Double> background_funcannot_ratios		= this.getRatios(background_funcannot_counts,background_gene_funcannot.size());
		Map<String,Double> test_funcannot_ratios			= this.getRatios(test_funcannot_counts, test_gene_funcannot.size());	
				
		//step 4. We now have the necessary items to perform the 
		//hypergeometric evaluation. 	
		Map<String,Double> enrichmentFactorDec		= new HashMap<String,Double>();
		Map<String,Double> enrichmentFactorLog		= new HashMap<String,Double>();
		Map<String,Double> enrichmentHypergeometric	= new HashMap<String,Double>();
		Map<String,Double> testRatioLong			= new HashMap<String,Double>();
		
		//System.out.println("DEBUG : # GO LABELS : "+gf_go_counts.size());
		
		//HyperGeometric hyper_geometric	= new ApproximateHypergeometric();
		HyperGeometric hyper_geometric	= new DefaultHyperGeometric();
		
		for(String funcannot:test_funcannot_counts.keySet()){
			if(background_funcannot_ratios.containsKey(funcannot)){	//should always happen, but better play it save.
				double background_ratio				= background_funcannot_ratios.get(funcannot);
				double test_ratio					= test_funcannot_ratios.get(funcannot);
				//System.out.println("\t"+go+"\t"+background_ratio+"\t"+test_ratio);
				int background_funcannot_count		= background_funcannot_counts.get(funcannot);		//variable m for hypergeometric distribution
				int test_funcannot_count			= test_funcannot_counts.get(funcannot);			//variable k for hypergeometric distribution				
				int background_num_genes			= background_gene_funcannot.size();		//variable N for hypergeometric distribution
				int test_num_genes					= test_gene_funcannot.size();				//variable n for hypergeometric distribution

				double enrichmentScoreDec			= test_ratio/background_ratio;
				double enrichmentScoreLog			= Math.log10(enrichmentScoreDec) / Math.log10(2);
				double hypergeometricScore			= hyper_geometric.hypergeometric(test_funcannot_count,test_num_genes,background_funcannot_count,background_num_genes);
								
				int type_count						= test_funcannot_counts.keySet().size();
				hypergeometricScore 				*= (double) type_count; //Bonferoni correction
					
				
				enrichmentFactorDec.put(funcannot, enrichmentScoreDec);
				enrichmentFactorLog.put(funcannot, enrichmentScoreLog);
				enrichmentHypergeometric.put(funcannot, hypergeometricScore);
				testRatioLong.put(funcannot,test_ratio*100.0);				
			}
			else{
				System.err.println(funcannot+" is not in background?");
			}
		}
		EnrichmentResult result	= new EnrichmentResult(
				enrichmentFactorDec,
				enrichmentFactorLog,
				enrichmentHypergeometric,
				testRatioLong);
		return result;
		
		
	}
	
	
	/**
	 * Perform enrichment procedure for the subset
	 * 
	 * @param go_data Full GO data from database. 
	 * @param background_gene_go Background gene-go mapping
	 * @param test_gene_go Test set gene-go mapping
	 * @return Enrichment result.
	 */
	private EnrichmentResult performEnrichment(
			Map<String,GoData> go_data,
			Map<String,Set<String>> background_gene_go,
			Map<String,Set<String>> test_gene_go			
			){
			
		//step 1: get a mapping from gos to counts of genes for the subset and the background
		Map<String,Integer> background_go_counts	= this.getGoCounts(background_gene_go, go_data);
		Map<String,Integer> test_go_counts			= this.getGoCounts(test_gene_go, go_data);
			
		//step 2: get a mapping from go-types (MF/BP/CC) to GO term counts,
		//but only for those used. Necessary for multiple testing hypothesis
		//Map<String,Integer> background_go_types		= this.getGoTypes(background_go_counts,go_data);
		Map<String,Integer> test_go_types			= this.getGoTypes(test_go_counts, go_data);
		
		//debug step
		/*
		for(String bgt:background_go_types.keySet()){
			System.out.println(bgt+"\t"+background_go_types.get(bgt));
		}*/
		
		
		//step 3: get background frequency counts as well
		Map<String,Double> background_go_ratios		= this.getRatios(background_go_counts,background_gene_go.size());
		Map<String,Double> test_go_ratios			= this.getRatios(test_go_counts, test_gene_go.size());	
				
		//step 4. We now have the necessary items to perform the 
		//hypergeometric evaluation. 	
		Map<String,Double> enrichmentFactorDec		= new HashMap<String,Double>();
		Map<String,Double> enrichmentFactorLog		= new HashMap<String,Double>();
		Map<String,Double> enrichmentHypergeometric	= new HashMap<String,Double>();
		Map<String,Double> testRatioLong			= new HashMap<String,Double>();
		
		//System.out.println("DEBUG : # GO LABELS : "+gf_go_counts.size());
		
		//HyperGeometric hyper_geometric	= new ApproximateHypergeometric();
		HyperGeometric hyper_geometric	= new DefaultHyperGeometric();
		
		for(String go:test_go_counts.keySet()){
			if(background_go_ratios.containsKey(go)){	//should always happen, but better play it save.
				double background_ratio				= background_go_ratios.get(go);
				double test_ratio					= test_go_ratios.get(go);
				//System.out.println("\t"+go+"\t"+background_ratio+"\t"+test_ratio);
				int background_go_count				= background_go_counts.get(go);		//variable m for hypergeometric distribution
				int test_go_count					= test_go_counts.get(go);			//variable k for hypergeometric distribution				
				int background_num_genes			= background_gene_go.size();		//variable N for hypergeometric distribution
				int test_num_genes					= test_gene_go.size();				//variable n for hypergeometric distribution

				double enrichmentScoreDec			= test_ratio/background_ratio;
				double enrichmentScoreLog			= Math.log10(enrichmentScoreDec) / Math.log10(2);
				double hypergeometricScore			= hyper_geometric.hypergeometric(test_go_count,test_num_genes,background_go_count,background_num_genes);
				
				String type							= go_data.get(go).type;
				int type_count						= test_go_types.get(type);
				hypergeometricScore 				*= (double) type_count; //Bonferoni correction
					
				
				enrichmentFactorDec.put(go, enrichmentScoreDec);
				enrichmentFactorLog.put(go, enrichmentScoreLog);
				enrichmentHypergeometric.put(go, hypergeometricScore);
				testRatioLong.put(go,test_ratio*100.0);
				//if(go.equals("GO:0012505")){
				//	System.out.println(test_ratio+"\t"+test_ratio*100.0);
				//}
			}
			else{
				System.err.println(go+" is not in background?");
			}
		}
		
		
				
		EnrichmentResult result	= new EnrichmentResult(
				enrichmentFactorDec,
				enrichmentFactorLog,
				enrichmentHypergeometric,
				testRatioLong);
		return result;
	}
	
	
	
	
	private class EnrichmentResult{
		public Map<String,Double> enrichmentFactorDec 		= null;
		public Map<String,Double> enrichmentFactorLog		= null;
		public Map<String,Double> enrichmentHypergeometric 	= null;
		public Map<String,Double> subsetRatio				= null;
		
		public Map<String,Boolean> hiddenParents			= null;
		
		public EnrichmentResult(Map<String,Double> enDec,Map<String,Double> enLog, 
				Map<String,Double>enH,Map<String,Double>gr){
			this.enrichmentFactorDec		= enDec;
			this.enrichmentFactorLog		= enLog;
			this.enrichmentHypergeometric	= enH;
			this.subsetRatio				= gr;
		}
		public String toString(){
			String result	= "# enriched GO's : "+enrichmentFactorDec.keySet().size();			
			return result;
		}
	}
	
	
	public interface HyperGeometric{
		public double hypergeometric(int k,int n,int m, int N);
	}
	
	/**
	 * Class for doing approximative and very fast hypergeometric computations.
	 * @author Michiel Van Bel
	 */
	public class ApproximateHypergeometric implements HyperGeometric{
		public ApproximateHypergeometric(){}		
		
		//see http://en.wikipedia.org/wiki/Hypergeometric_distribution
		//and http://en.wikipedia.org/wiki/Factorial
		public double hypergeometric(int k, int n, int m, int N){
			//( m ) ( N-m )
			//( k ) ( n-k )
			//-------------
			//    ( N )
			//    ( n )
			
			//log ->   log[ ( m ) ] + log[ ( N-m ) ] - log[ ( N ) ]		
			// 				( k ) 		   ( n-k )          ( n ) 			
			double log_hypergeometric	= log_approx(m,k) + log_approx(N-m,n-k) - log_approx(N,n);
			double result				= Math.exp(log_hypergeometric);
			return result;
		}		
		//returns the log value of
		// ( a )
		// ( b )
		// The normal result is
		//   a!
		// --------
		// b!(a-b)!
		//
		//The log result should be
		// log(a!) - [ log(b!) + log[(a-b)!] ]
		private double log_approx(int a,int b){
			double log_a	= log_approx(a);
			double log_b	= log_approx(b);
			double log_ab	= log_approx(a-b);
			double result	= log_a - log_b - log_ab;
			return result;
		}		
		// n! ~= sqrt(2*PI*n) * (n/2)^n
		//however, better approximation for log(n!)
		//log(n!) ~= n*log(n) - n + log(n(1+4n*(1+2n)))/6 + log(PI)/2
		private double log_approx(int a){
			if(a==0 || a==1){
				return Math.log(1);
			}
			double n			= a;
			double result		= n * Math.log(n) - n + Math.log(n+4*n*n+8*n*n*n)/6 + Math.log(Math.PI)/2;
			return result;
		}
	}
	
	
	/*
	 * Standard hypergeometric test. Kinda slow, but kept in here for debug purposes
	 */
	public class DefaultHyperGeometric implements HyperGeometric{
		
		public DefaultHyperGeometric(){}
		
		
		// return integer nearest to x
		/*private long nint(double x) {
			if (x < 0.0) return (long) Math.ceil(x - 0.5);
			return (long) Math.floor(x + 0.5);
		}*/

		// return log n!
		private double logFactorial(int n) {
			double ans = 0.0;
			for (int i = 1; i <= n; i++){ans += Math.log(i);}
		    return ans;
		}
		/* return the binomial coefficient n choose k.
		private long binomial(int n, int k) {
			long result = nint(Math.exp(logFactorial(n) - logFactorial(k) - logFactorial(n-k)));			  
		    return result;
		}*/
		private double logBinomial(int n, int k) {
			double result = logFactorial(n) - logFactorial(k) - logFactorial(n-k);				
			return result;
		}
		
		public double hypergeometric(int k, int n, int m, int N){
			double result = Math.exp(logBinomial(m,k) + logBinomial((N-m),(n-k)) - logBinomial(N,n));
			return result;
		}		
	}
	
	
	
	
	
	/**
	 * Expand data structure in order for the genes to be mapped to the parental GO terms as well
	 * @param data Read from input file
	 * @param go_child2parents  From the database connection
	 * @return updated Data structure
	 */
	private Map<String,Set<String>>expandGeneGoData(Map<String,Set<String>> data,Map<String,Set<String>>go_child2parents){
		Map<String,Set<String>> new_data	= new HashMap<String,Set<String>>();
		for(String gene:data.keySet()){
			Set<String> gene_go				= data.get(gene);
			Set<String> new_gene_go			= new HashSet<String>();
			for(String go:gene_go){
				new_gene_go.add(go);
				if(go_child2parents.containsKey(go)){
					for(String parent_go:go_child2parents.get(go)){
						new_gene_go.add(parent_go);
					}
				}
			}
			new_data.put(gene,new_gene_go);
		}
		return new_data;
	}
	
	
	/**
	 * Read data mapping from genes to GO terms in this format:
	 * gene1<tab>go1
	 * gene1<tab>go2
	 * ...
	 * gene2<tab>go5
	 * gene2<tab>go9
	 * ...		
	 * @param file_path Path containing mapping file
	 * @return Mapping from gene to set of GO terms
	 * @throws Exception
	 */
	private Map<String,Set<String>> readDataFile(String file_path) throws Exception{
		Map<String,Set<String>> result	= new HashMap<String,Set<String>>();
		BufferedReader reader			= new BufferedReader(new FileReader(new File(file_path)));
		String s						= reader.readLine();
		while(s!=null){
			s							= s.trim();
			if(!s.equals("")){
				String[] split			= s.split("\t");
				if(split.length>=2){
					String gene_id		= split[0];
					String go			= split[1];
					if(!result.containsKey(gene_id)){result.put(gene_id, new HashSet<String>());}
					result.get(gene_id).add(go);
				}
			}	
			s							= reader.readLine();
		}		
		reader.close();		
		return result;
	}
	
	
	
	private Map<String,GoData> loadGoData(Connection conn)throws Exception{
		Map<String,GoData> result	= new HashMap<String,GoData>();
		String sql				= "SELECT `go`,`type`,`is_obsolete` FROM `extended_go` ";
		Statement stmt			= conn.createStatement();
		ResultSet set			= stmt.executeQuery(sql);
		while(set.next()){
			String go	= set.getString("go");
			GoData gd	= new GoData(go,set.getString("type"),Boolean.parseBoolean(set.getString("is_obsolete")));
			result.put(go,gd);
		}
		set.close();
		stmt.close();
		return result;
	}
	
	
	private class GoData{
	
		public String type				= null;
		public boolean is_obsolete		= false;
		public GoData(String go_id,String type,boolean is_obsolete){
			this.type			= type;
			this.is_obsolete	= is_obsolete;
		}
	}

	
	
	/**
	 * Preload GO graph data (typically 65MB in database table with indices, so memory shouldn't be a problem).
	 * This is done in order to speed up processing dramatically, by preventing a large number of SQL queries.
	 * @param conn Database connection to PLAZA database
	 * @param extended_go	Information on obsolete GO terms
	 * @return Mapping of GO terms to their parent GO terms and mapping of GO terms to their children GO terms
	 * @throws Exception Database failure
	 */
	private Map<String,Map<String,Set<String>>> loadGOGraph(Connection conn,Map<String,GoData> extended_go) throws Exception{		
		Map<String,Map<String,Set<String>>> result	= new HashMap<String,Map<String,Set<String>>>();		
		Map<String,Set<String>> go_child2parents	= new HashMap<String,Set<String>>(); 	//mapping from child go to parent go
		Map<String,Set<String>> go_parent2children	= new HashMap<String,Set<String>>();	//mapping of 
		String query					= "SELECT `child_go`,`parent_go` FROM `go_parents` ";
		Statement stmt					= conn.createStatement();
		ResultSet set					= stmt.executeQuery(query);
		while(set.next()){
			String child_go				= set.getString(1);
			String parent_go			= set.getString(2);
			GoData child_go_data		= extended_go.get(child_go);
			GoData parent_go_data		= extended_go.get(parent_go);			
			if(!child_go_data.is_obsolete && !parent_go_data.is_obsolete){			
				if(!go_child2parents.containsKey(child_go)){go_child2parents.put(child_go, new HashSet<String>());}
				go_child2parents.get(child_go).add(parent_go);			
				if(!go_parent2children.containsKey(parent_go)){go_parent2children.put(parent_go, new HashSet<String>());}
				go_parent2children.get(parent_go).add(child_go);
			}
		}		
		set.close();
		stmt.close();
		
		result.put("child2parents", go_child2parents);
		result.put("parent2children",go_parent2children);
		
		return result;
	}

	
	
	
	/**
	 * Create a database connection, based on given parameters
	 * @param server
	 * @param database
	 * @param login
	 * @param password
	 * @return
	 * @throws Exception
	 */
	private Connection createDbConnection(String server,String database,String login,String password) throws Exception{
		String url		= "jdbc:mysql://"+server+"/"+database;
		Connection conn	= DriverManager.getConnection(url,login,password);
		return conn;
	}
	
	
}
