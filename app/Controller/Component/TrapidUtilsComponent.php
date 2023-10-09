<?php
App::uses('Component', 'Controller');
class TrapidUtilsComponent extends Component {
    var $components = ['Email', 'Cookie', 'Session'];
    var $controller;

    function startup(Controller $c) {
        $this->controller = &$c;
    }

    function getMsaLength($msa) {
        $explode = explode('>', $msa);
        $explode2 = explode(';', $explode[1]);
        $length = strlen($explode2[1]);
        return $length;
    }

    function dirToArray($dir) {
        $result = [];
        $cdir = scandir($dir);
        foreach ($cdir as $key => $value) {
            if (!in_array($value, ['.', '..'])) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $result[$value] = $this->dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                } else {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }

    function formatBytes($size, $precision = 2) {
        $base = log($size) / log(1024);
        $suffixes = ['bytes', 'Kb', 'Mb', 'Gb', 'Tb'];
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }

    function readDir($dir) {
        $files = scandir($dir);
        $result = [];
        foreach ($files as $f) {
            if ($f != '.' && $f != '..') {
                $file_path = $dir . '/' . $f;
                $file_size = filesize($file_path);
                $result[] = ['filename' => $f, 'filepath' => $file_path, 'filesize' => $this->formatBytes($file_size)];
            }
        }
        return $result;
    }

    /**
     * Initiate experiment data export: create export script, cleanup previous export files, and submit job.
     *
     * @param $plaza_db TRAPID reference database
     * @param $email experiment's owner email, used to append a hash to the export zip archive name
     * @param $exp_id experiment identifier
     * @param $export_key export key (parameter for 'ExportManager' Java program indicating the data type to export)
     * @param $filename export file name
     * @param null $filter export data filter that can either indicate columns to include in the export file (in the
     * case of structural data export) or a transcript subset (in the case of sequence or subset export)
     * @return array[
     *  'jobId' => string,
     *  'zipName' => string,
     *  'status' => null
     * ]
     * Export job data (id, zip archive name, status placeholder). Return `null` if the job could not be submitted.
     */
    function initiateExport($plaza_db, $email, $exp_id, $export_key, $filename, $filter = null) {
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $base_scripts_location = APP . 'scripts/';
        $java_location = $base_scripts_location . 'java/';
        // Translation table json file location is needed if exporting protein sequences
        $transl_tables_file = $base_scripts_location . 'cfg/all_translation_tables.json';
        $salt = $exp_id;
        $timestamp = date('Ymd_Gis', time());
        $hash = hash('sha256', $email . $salt);
        $internal_file = $tmp_dir . $filename;
        // Give a meaningful file name to the zip archive
        $zip_name = implode('_', [strtolower($export_key), $exp_id, $timestamp, substr($hash, 0, 14)]) . '.zip';
        $internal_zip = $tmp_dir . $zip_name;
        $java_cmd = 'java';
        $java_program = 'transcript_pipeline.ExportManager';
        $java_params = [
            TRAPID_DB_SERVER,
            $plaza_db,
            TRAPID_DB_USER,
            TRAPID_DB_PASSWORD,
            TRAPID_DB_SERVER,
            TRAPID_DB_NAME,
            TRAPID_DB_USER,
            TRAPID_DB_PASSWORD,
            $transl_tables_file,
            $exp_id,
            $export_key,
            $internal_file
        ];
        if ($filter != null) {
            $java_params[] = $filter;
        }
        $export_basename = 'export_data';
        $shell_file = $tmp_dir . $export_basename . '.sh';
        $error_file = $tmp_dir . $export_basename . '.err';
        $output_file = $tmp_dir . $export_basename . '.out';

        // Cleanup previous export files
        // Command to remove previous export shell script and stderr/stdout files
        $export_rm = implode(' ', ['rm -f', $shell_file, $error_file, $output_file]);
        // Zip archives to remove (pattern for 'rm' command) when a new zip archive is generated.
        // This should match all export files for the experiment.
        $zip_rm = $tmp_dir . '*_' . substr($hash, 0, 14) . '.zip';
        shell_exec($export_rm);
        shell_exec('rm -f ' . $zip_rm);

        // Create export shell script
        $fh = fopen($shell_file, 'w');
        fwrite($fh, "#!/bin/bash \n");
        fwrite($fh, "module load java\n\n");
        fwrite($fh, "hostname\n");
        fwrite($fh, "date\n");
        fwrite(
            $fh,
            $java_cmd .
                ' -cp ' .
                $java_location .
                '.:' .
                $java_location .
                '..:' .
                $java_location .
                'lib/* ' .
                $java_program .
                ' ' .
                implode(' ', $java_params) .
                "\n"
        );
        fwrite($fh, "if [ $? -eq 0 ]\nthen\necho \"Zip export file\"\n");
        fwrite($fh, 'rm -f ' . $zip_rm . "\n");
        fwrite($fh, 'zip -j ' . $internal_zip . ' ' . $internal_file . "\n");
        fwrite($fh, 'rm -f ' . $internal_file . "\n");
        fwrite($fh, "echo \"Export finished successfully\"\ndate\nexit 0\n");
        fwrite($fh, "else\necho \"Export finished with an error\"\ndate\nexit 1\n");
        fwrite($fh, "fi\n");
        fclose($fh);
        shell_exec('chmod a+x ' . $shell_file);

        // Run export shell script on the web cluster
        $cluster_cmd =
            '. /etc/profile.d/settings.sh && qsub -q medium -e ' .
            $error_file .
            ' -o ' .
            $output_file .
            ' ' .
            $shell_file;
        $cluster_output = null;
        exec($cluster_cmd, $cluster_output);
        if (count($cluster_output) == 0) {
            return null;
        }
        $job_id = $this->getClusterJobId($cluster_output);
        return ['jobId' => $job_id, 'zipName' => $zip_name, 'status' => null];
    }

    /**
     * Check and return experiment data export job status.
     *
     * @param $exp_id experiment identifier.
     * @param $job_id export cluster job identifier.
     * @return string export job status: 'running', 'error', or 'ready'.
     */
    function checkExportJobStatus($exp_id, $job_id) {
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $export_basename = 'export_data';
        $output_file = $tmp_dir . $export_basename . '.out';
        // Assert `$output_file` existence because of latency between job completion and file vibility.
        if ($this->cluster_job_exists($exp_id, $job_id) || !file_exists($output_file)) {
            return 'running';
        }
        // Note: asserting thr job finished with an error based on the content of `$output_file` is ok, but it would be
        // better to check the cluster job's exit status instead.
        $error_str = 'Export finished with an error';
        if (strpos(file_get_contents($output_file), $error_str) !== false) {
            return 'error';
        }
        return 'ready';
    }

    /**
     * Perform experiment data export: create export script, run it, compress exported file and return archive file path.
     * Note: this is not used anymore, see `initiateExport()` instead.
     *
     * @param $plaza_db TRAPID reference database
     * @param $email experiment's owner email, used to append a hash to the export zip archive name
     * @param $exp_id experiment identifier
     * @param $export_key export key (parameter for 'ExportManager' Java program indicating the data type to export)
     * @param $filename export file name
     * @param null $filter export data filter that can either indicate columns to include in the export file (in the
     * case of structural data export) or a transcript subset (in the case of sequence or subset export)
     * @return string path of the export zip archive file, set to null if an error was encountered.
     */
    function performExport($plaza_db, $email, $exp_id, $export_key, $filename, $filter = null) {
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $ext_dir = TMP_WEB . 'experiment_data/' . $exp_id . '/';
        $base_scripts_location = APP . 'scripts/';
        $java_location = $base_scripts_location . 'java/';
        // Translation table json file location is needed if exporting protein sequences
        $transl_tables_file = $base_scripts_location . 'cfg/all_translation_tables.json';
        $salt = $exp_id;
        $timestamp = date('Ymd_Gis', time());
        $hash = hash('sha256', $email . $salt);
        $internal_file = $tmp_dir . $filename;
        // Give a meaningful file name to the zip archive
        $zip_name = implode('_', [strtolower($export_key), $exp_id, $timestamp, substr($hash, 0, 14)]) . '.zip';
        $internal_zip = $tmp_dir . $zip_name;
        $java_cmd = 'java';
        $java_program = 'transcript_pipeline.ExportManager';
        $java_params = [
            TRAPID_DB_SERVER,
            $plaza_db,
            TRAPID_DB_USER,
            TRAPID_DB_PASSWORD,
            TRAPID_DB_SERVER,
            TRAPID_DB_NAME,
            TRAPID_DB_USER,
            TRAPID_DB_PASSWORD,
            $transl_tables_file,
            $exp_id,
            $export_key,
            $internal_file
        ];
        if ($filter != null) {
            $java_params[] = $filter;
        }
        $export_basename = 'export_data';
        $shell_file = $tmp_dir . $export_basename . '.sh';
        $error_file = $tmp_dir . $export_basename . '.err';
        $output_file = $tmp_dir . $export_basename . '.out';
        // Command to remove previous export shell script and stderr/stdout files
        $export_rm = implode(' ', ['rm -f', $shell_file, $error_file, $output_file]);
        // Zip archives to remove (pattern for 'rm' command) when a new zip archive is generated
        $zip_rm = $tmp_dir . '*_' . substr($hash, 0, 14) . '.zip';

        // Create export shell script
        shell_exec($export_rm);
        $fh = fopen($shell_file, 'w');
        fwrite($fh, "#!/bin/bash \n");
        fwrite($fh, "module load java\n\n");
        fwrite($fh, "hostname\n");
        fwrite($fh, "date\n");
        fwrite(
            $fh,
            $java_cmd .
                ' -cp ' .
                $java_location .
                '.:' .
                $java_location .
                '..:' .
                $java_location .
                'lib/* ' .
                $java_program .
                ' ' .
                implode(' ', $java_params) .
                "\n"
        );
        fwrite($fh, "if [ $? -eq 0 ]\nthen\necho \"Export finished successfully\"\ndate\nexit 0\n");
        fwrite($fh, "else\necho \"Export finished with an error\"\ndate\nexit 1\n");
        fwrite($fh, "fi\n");
        fclose($fh);
        shell_exec('chmod a+x ' . $shell_file);

        // Run export shell script on the web cluster
        // 2021-05-05: switched to medium queue (instead of short) to allow export jobs to run longer
        $cluster_cmd =
            '. /etc/profile.d/settings.sh && qsub -q medium -sync y -e ' .
            $error_file .
            ' -o ' .
            $output_file .
            ' ' .
            $shell_file;
        // $cluster_output = shell_exec($cluster_cmd);
        $cluster_output = null;
        $cluster_exit_status = 0;
        exec($cluster_cmd, $cluster_output, $cluster_exit_status);
        // If the job finished with an exit status other than zero, return `null`
        if ($cluster_exit_status != 0) {
            return null;
        }

        // Zip result file and cleanup previous export files
        // Now export files are generated with different names. Should every zip archive be removed?
        shell_exec('rm -f ' . $zip_rm);
        shell_exec('zip -j ' . $internal_zip . ' ' . $internal_file);
        shell_exec('rm -f ' . $internal_file);
        $result = $ext_dir . $zip_name;
        return $result;
    }

    function checkPageAccess($exp_name, $process_state, $allowed_states) {
        if (in_array($process_state, $allowed_states)) {
            return;
        } else {
            //$this->Session->setFlash("Experiment '".$exp_name."' is in wrong state (".$process_state.") for web-access");
            $this->controller->redirect(['controller' => 'trapid', 'action' => 'experiments']);
        }
    }

    function indexToGoTypes($go_data, $table_name, $column_name_go, $column_name_type) {
        $result = [];
        foreach ($go_data as $gd) {
            $go = $gd[$table_name][$column_name_go];
            $type = $gd[$table_name][$column_name_type];
            if (!array_key_exists($type, $result)) {
                $result[$type] = [];
            }
            $result[$type][] = $go;
        }
        return $result;
    }

    function unify($data) {
        $result = [];
        foreach ($data as $d) {
            $res = [];
            foreach ($d as $table => $columns) {
                $res = array_merge($res, $columns);
            }
            $result[] = $res;
        }
        return $result;
    }

    function reduceArray($model_data, $table_name, $column_name) {
        $result = [];
        foreach ($model_data as $md) {
            $result[] = $md[$table_name][$column_name];
        }
        return $result;
    }

    function valueToIndexArray($data) {
        $result = [];
        foreach ($data as $d) {
            $result[$d] = $d;
        }
        return $result;
    }

    function indexArrayMulti($data, $table_name, $column_key, $columns_val) {
        $result = [];
        foreach ($data as $d) {
            $d1 = $d[$table_name][$column_key];
            $res = [];
            foreach ($columns_val as $cv) {
                $res[$cv] = $d[$table_name][$cv];
            }
            $result[$d1] = $res;
        }
        return $result;
    }

    function indexArraySimple($data, $table_name, $column_key, $column_val) {
        $result = [];
        foreach ($data as $d) {
            $d1 = $d[$table_name][$column_key];
            $d2 = $d[$table_name][$column_val];
            $result[$d1] = $d2;
        }
        return $result;
    }

    function indexArray($data, $table_name, $column_key, $column_val) {
        $result = [];
        foreach ($data as $d) {
            $d1 = $d[$table_name][$column_key];
            $d2 = $d[$table_name][$column_val];
            if (!array_key_exists($d1, $result)) {
                $result[$d1] = [];
            }
            $result[$d1][] = $d2;
        }
        return $result;
    }

    function checkJobStatus($exp_id, $jobs_data) {
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $qstat_script = $this->create_qstat_script($exp_id);
        $result = [];

        //get overview of all jobs on webcluster?
        $shell_output_all = [];
        $command_all = "sh $qstat_script -u apache 2>&1";
        exec($command_all, $shell_output_all);
        $job_details = [];
        for ($i = 2; $i < count($shell_output_all); $i++) {
            $job_det = explode(' ', $shell_output_all[$i]);
            $jd = [];
            foreach ($job_det as $jde) {
                if ($jde) {
                    $jd[] = $jde;
                }
            }
            $job_details[$jd[0]] = $jd[4];
        }
        foreach ($jobs_data as $t => $jd) {
            $job_id = $jd['job_id'];
            $job_status = 'done'; //default: job does not exists anymore, or wrongfully inserted data
            if (array_key_exists($job_id, $job_details)) {
                $js = $job_details[$job_id];
                if ($js == 'Eqw') {
                    $job_status = 'error';
                } elseif ($js == 'qw') {
                    $job_status = 'queued';
                } elseif ($js == 'r') {
                    $job_status = 'running';
                } else {
                    $job_status = 'unknown';
                }
            }
            $result[$job_id] = $jd;
            $result[$job_id]['status'] = $job_status;
        }
        return $result;
    }

    function getFinishedJobIds($exp_id, $jobs_data) {
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $finished_jobs = [];
        // Experiment directory does not exist = we did not do anything with it yet.
        // So no need to check for finished jobs
        if (!file_exists($tmp_dir)) {
            return [];
        }
        $qstat_script = $this->create_qstat_script($exp_id);
        $result = [];
        //get overview of all jobs on webcluster?
        $shell_output_all = [];
        $command_all = "sh $qstat_script -u apache 2>&1";
        exec($command_all, $shell_output_all);
        $job_details = [];
        for ($i = 2; $i < count($shell_output_all); $i++) {
            $job_det = explode(' ', $shell_output_all[$i]);
            $jd = [];
            foreach ($job_det as $jde) {
                if ($jde) {
                    $jd[] = $jde;
                }
            }
            $job_details[$jd[0]] = $jd[4];
        }
        foreach ($jobs_data as $t => $jd) {
            $job_id = $jd['job_id'];
            if (!array_key_exists($job_id, $job_details)) {
                array_push($finished_jobs, $job_id);
            }
        }
        return $finished_jobs;
    }

    // Get the status of the webcluster's queues `$queues`
    // If the fraction of running jobs is more than `$busy`, status of the queue is set to 'busy'
    // If the fraction of running jobs is more than `$full`, status of the queue is set to 'full'
    function check_cluster_status($busy = 0.5, $full = 1.0, $queues = ['long', 'medium', 'short']) {
        $cluster_status = [];
        //    $qhost_cmd = ". /opt/sge/default/common/settings.sh && qhost -q";
        $qhost_cmd = '. /etc/profile.d/settings.sh && qhost -q';
        $qhost_out = [];
        $exit_status = 0;
        exec($qhost_cmd, $qhost_out, $exit_status);
        if ($exit_status == 0) {
            // Parse qhost output
            foreach ($qhost_out as $line) {
                $split = preg_split('/\s+/', preg_replace('/^\s+/', '', $line));
                if (in_array($split[0], $queues)) {
                    $queue_data = explode('/', end($split));
                    $queue_load = $queue_data[1] / $queue_data[2];
                    if ($queue_load >= $full) {
                        $cluster_status[$split[0]] = 'full';
                    } elseif ($queue_load >= $busy) {
                        $cluster_status[$split[0]] = 'busy';
                    } else {
                        $cluster_status[$split[0]] = 'ok';
                    }
                }
            }
        }
        return $cluster_status;
    }

    function checkAvailableDiamondDB($plaza_db, $data) {
        $result = [];
        $final_blast_dir = BLAST_DB_DIR . '' . $plaza_db . '/';
        foreach ($data as $k => $v) {
            //check whether file with necessary name exists in the directory. If so, add to result.
            $blast_db = $final_blast_dir . '' . $k . '.dmnd';
            if (file_exists($blast_db)) {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    function waitfor_cluster($exp_id, $job_id, $max_time = 60, $interval = 4) {
        //first: remove all files older than X days in the folder?
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        shell_exec("find $tmp_dir -maxdepth 1 -atime +8 -type f -exec rm -f {} \\;");

        $result = [];
        $qstat_script = $this->create_qstat_script($exp_id);
        $qdel_script = $this->create_qdel_script($exp_id);
        $cont = true;
        $total_counter = 0;
        $max_counter = $max_time;
        $command = "sh $qstat_script -j $job_id 2>&1";
        while ($cont) {
            //ok, check using qstat whether the job has finished yet. Maximum seconds counter added
            //to prevent eternal loop.
            $out = [];
            exec($command, $out);
            if ($out[0] == 'Following jobs do not exist:') {
                $cont = false;
            }
            sleep($interval);
            $total_counter += $interval;
            if ($total_counter > $max_counter) {
                //perform kill command for job, so - even if it is stuck in the queue - it gets killed.
                $result['error'] = 'Job was not finished in appropiate amount of time.';
                exec("sh $qdel_script $job_id");
                return $result;
            }
        }
        $result['success'] = 'ok';
        return $result;
    }

    function cluster_job_exists($exp_id, $job_id, $should_cleanup_files = false) {
        // Remove all files older than X days in the experiment's folder
        if ($should_cleanup_files) {
            $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
            shell_exec("find $tmp_dir -maxdepth 1 -atime +8 -type f -exec rm -f {} \\;");
        }
        $qstat_script = $this->create_qstat_script($exp_id);
        $command = "sh $qstat_script -j $job_id 2>&1";
        $out = [];
        exec($command, $out);
        return $out[0] != 'Following jobs do not exist:';
    }

    function sync_file($file, $max_sync_time = 10) {
        $cont = true;
        $sync_time = 0;
        $sync_time_interval = 1;
        while ($cont) {
            if (file_exists($file)) {
                return $file;
            }
            sleep($sync_time_interval);
            $sync_time += $sync_time_interval;
            if ($sync_time > $max_sync_time) {
                $cont = false;
            }
        }
        return false;
    }

    function getClusterJobId($qsub_output) {
        if (count($qsub_output) == 0) {
            return null;
        }
        $qs = explode(' ', $qsub_output[0]);
        $job_id = $qs[2];
        if (is_numeric($job_id)) {
            return $job_id;
        }
        return null;
    }

    function get_all_processing_experiments() {
        $result = [];
        $base_scripts_location = APP . 'scripts/';
        $running_jobs = shell_exec('sh ' . $base_scripts_location . 'shell/get_all_jobids.sh ');
        foreach (explode("\n", $running_jobs) as $job_id) {
            if (is_numeric($job_id)) {
                //get shell-script name for this job-id
                $job_info = shell_exec('sh ' . $base_scripts_location . 'shell/check_job_id.sh ' . $job_id);
                $job_data = explode("\n", $job_info);
                foreach ($job_data as $jd) {
                    $jd2 = explode("\t", $jd);
                    if (
                        count($jd2) == 2 &&
                        $jd2[0] == 'script_file:' &&
                        strpos($jd2[1], 'initial_processing') !== false
                    ) {
                        $start = strpos($jd2[1], 'initial_processing') + 19;
                        $exp_id = substr($jd2[1], $start);
                        $exp_id = substr($exp_id, 0, strpos($exp_id, '.sh'));
                        $result[$exp_id] = $job_id;
                    }
                }
            }
        }
        return $result;
    }

    function delete_job($job_id) {
        $base_scripts_location = APP . 'scripts/';
        shell_exec('sh ' . $base_scripts_location . 'shell/delete_job.sh ' . $job_id);
    }

    function deleteClusterJob($exp_id, $job_id) {
        $qdel_script = $this->create_qdel_script($exp_id);
        shell_exec('sh ' . $qdel_script . ' ' . $job_id);
    }

    function create_qstat_script($exp_id) {
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $qstat_file = $tmp_dir . 'qstat.sh';
        $fh = fopen($qstat_file, 'w');
        fwrite($fh, "#!/bin/bash \n");
        //	 fwrite($fh,". /etc/profile.d/settings.sh\n");
        // Update settings (tanith webcluster workshop)
        //	fwrite($fh,". /opt/sge/default/common/settings.sh\n");
        fwrite($fh, ". /etc/profile.d/settings.sh\n");
        fwrite($fh, "qstat $* \n");
        fclose($fh);
        shell_exec('chmod a+x ' . $qstat_file);
        return $qstat_file;
    }

    function create_qdel_script($exp_id) {
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $qdel_file = $tmp_dir . 'qdel.sh';
        $fh = fopen($qdel_file, 'w');
        fwrite($fh, "#!/bin/bash \n");
        //     fwrite($fh,". /etc/profile.d/settings.sh\n");
        // Update settings (tanith webcluster workshop)
        //    fwrite($fh,". /opt/sge/default/common/settings.sh\n");
        fwrite($fh, ". /etc/profile.d/settings.sh\n");
        fwrite($fh, "qdel $* \n");
        fclose($fh);
        shell_exec('chmod a+x ' . $qdel_file);
        return $qdel_file;
    }

    function create_qsub_script_general() {
        $tmp_dir = TMP . 'experiment_data/';
        $qsub_file = $tmp_dir . 'qsub.sh';
        if (!file_exists($qsub_file)) {
            $fh = fopen($qsub_file, 'w');
            fwrite($fh, "#!/bin/bash \n");
            //         fwrite($fh,". /etc/profile.d/settings.sh\n");
            // Update settings (tanith webcluster workshop)
            fwrite($fh, ". /etc/profile.d/settings.sh\n");
            //        fwrite($fh,". /opt/sge/default/common/settings.sh\n");
            fwrite($fh, "qsub $* \n");
            fclose($fh);
            shell_exec('chmod a+x ' . $qsub_file);
        }
        return $qsub_file;
    }

    function create_qsub_script($exp_id) {
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        //remove all the files older than 3 days in this directory, to save disk space.
        // shell_exec("find $tmp_dir".'.'." -maxdepth 2 -atime +3 -type f -exec rm -f {} \\;");
        // Quick fix to avoid deleting tax. binning visualizations (in the future we'll store them in TRAPID's db)
        shell_exec(
            "find $tmp_dir" . '.' . " -maxdepth 2 -atime +3 -type f -not -path \"*/kaiju/*\" -exec rm -f {} \\;"
        );
        $qsub_file = $tmp_dir . 'qsub.sh';
        if (!file_exists($qsub_file)) {
            $fh = fopen($qsub_file, 'w');
            fwrite($fh, "#!/bin/bash \n");
            //         fwrite($fh,". /etc/profile.d/settings.sh\n");
            // Update settings (tanith webcluster workshop)
            //        fwrite($fh,". /opt/sge/default/common/settings.sh\n");
            fwrite($fh, ". /etc/profile.d/settings.sh\n");
            fwrite($fh, "qsub $* \n");
            fclose($fh);
            shell_exec('chmod a+x ' . $qsub_file);
        }
        return $qsub_file;
    }

    function create_monthly_cleanup_script($year, $month, $cleanup_warning, $cleanup_delete) {
        $tmp_dir = TMP . 'experiment_data/';
        $shell_script = $tmp_dir . 'cleanup_' . $year . '_' . $month . '.sh';
        $perl_script = APP . 'scripts/perl/monthly_cleanup.pl';
        $necessary_modules = ['perl/x86_64/5.14.1'];
        $params = [
            TRAPID_DB_SERVER,
            TRAPID_DB_NAME,
            TRAPID_DB_PORT,
            TRAPID_DB_USER,
            TRAPID_DB_PASSWORD,
            TMP . 'experiment_data',
            $year,
            $month,
            $cleanup_warning,
            $cleanup_delete
        ];

        $fh = fopen($shell_script, 'w');
        fwrite($fh, "#Loading necessary modules\n");
        foreach ($necessary_modules as $nm) {
            fwrite($fh, 'module load ' . $nm . " \n");
        }
        fwrite($fh, "\n#Launching cleanup program\n");
        fwrite($fh, 'perl ' . $perl_script . ' ' . implode(' ', $params) . "\n");
        fclose($fh);
        shell_exec('chmod a+x ' . $shell_script);
        return $shell_script;
    }

    function create_shell_script_data_update_gf($exp_id, $plaza_db, $gf_id, $transcript_id, $new_gf = false) {
        $base_scripts_location = APP . 'scripts/';
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $necessary_modules = ['java'];
        //create actual shell script file
        $shell_file = $tmp_dir . 'gf_change_' . $exp_id . '_' . $transcript_id . '_' . $gf_id . '.sh';
        $fh = fopen($shell_file, 'w');
        fwrite($fh, "#Loading necessary modules\n");
        foreach ($necessary_modules as $nm) {
            fwrite($fh, 'module load ' . $nm . " \n");
        }
        $parameters = [];
        if ($new_gf) {
            $parameters = [
                'GF_ASSOC_NEW',
                PLAZA_DB_SERVER,
                $plaza_db,
                PLAZA_DB_USER,
                PLAZA_DB_PASSWORD,
                TRAPID_DB_SERVER,
                TRAPID_DB_NAME,
                TRAPID_DB_USER,
                TRAPID_DB_PASSWORD,
                $exp_id,
                $transcript_id,
                $gf_id
            ];
        } else {
            $parameters = [
                'GF_ASSOC_EXIST',
                TRAPID_DB_SERVER,
                TRAPID_DB_NAME,
                TRAPID_DB_USER,
                TRAPID_DB_PASSWORD,
                $exp_id,
                $transcript_id,
                $gf_id
            ];
        }

        $java_location = $base_scripts_location . 'java/';
        $java_program = 'transcript_pipeline.UpdateData';

        fwrite($fh, "\n#Launching java program\n");
        fwrite(
            $fh,
            'java -cp ' .
                $java_location .
                '.:..:' .
                $java_location .
                'lib/* ' .
                $java_program .
                ' ' .
                implode(' ', $parameters) .
                "\n"
        );
        fclose($fh);
        shell_exec('chmod a+x ' . $shell_file);
        return $shell_file;
    }

    // Create shell script to create GF phylogenetic tree, return file name
    function create_shell_script_tree(
        $exp_id,
        $gf_id,
        $msa_program,
        $editing_mode,
        $tree_program,
        $include_subsets,
        $include_meta
    ) {
        $inc_sub = 0;
        if ($include_subsets) {
            $inc_sub = 1;
        }
        $inc_met = 0;
        if ($include_meta) {
            $inc_met = 1;
        }
        $base_scripts_location = APP . 'scripts/';
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $necessary_modules = ['perl/x86_64/5.14.1', 'python/x86_64/2.7.14'];
        // Add modules for MSA/tree program -- program versions are consistent with PLAZA 4.5
        $msa_tree_modules = [
            'muscle' => 'muscle/x86_64/3.8.31',
            'mafft' => 'mafft/x86_64/7.187',
            'phyml' => 'phyml/x86_64/20150219',
            'fasttree' => 'fasttree/x86_64/2.1.7',
            'raxml' => 'raxml/x86_64/8.2.8',
            'iqtree' => 'iqtree/x86_64/1.7.0b7'
        ];
        $necessary_modules[] = $msa_tree_modules[$msa_program];
        $necessary_modules[] = $msa_tree_modules[$tree_program];

        //create actual shell script file
        $shell_file = $tmp_dir . 'create_tree_' . $gf_id . '.sh';
        $fh = fopen($shell_file, 'w');
        fwrite($fh, "#Loading necessary modules\n");
        foreach ($necessary_modules as $nm) {
            fwrite($fh, 'module load ' . $nm . " \n");
        }

        $parameters_msa_tree = [
            $exp_id,
            $gf_id,
            $tmp_dir,
            $base_scripts_location,
            TRAPID_DB_NAME,
            TRAPID_DB_SERVER,
            TRAPID_DB_USER,
            TRAPID_DB_PASSWORD,
            '--tree_program',
            $tree_program,
            '--msa_program',
            $msa_program,
            '--msa_editing',
            $editing_mode,
            '--verbose'
        ];

        $parameters_phyloxml = [
            $exp_id,
            $gf_id,
            TRAPID_DB_NAME,
            TRAPID_DB_SERVER,
            TRAPID_DB_USER,
            TRAPID_DB_PASSWORD,
            $tmp_dir
        ];
        if ($inc_sub) {
            array_push($parameters_phyloxml, '-s');
        }
        if ($inc_met) {
            array_push($parameters_phyloxml, '-m');
        }

        fwrite($fh, "\n#Launching wrapper script for creating necessary files, then MSA/tree \n");
        $program_location_msa_tree = $base_scripts_location . 'python/run_msa_tree.py';
        $command_line_msa_tree = 'python ' . $program_location_msa_tree . ' ' . implode(' ', $parameters_msa_tree);
        $program_location_phyloxml = $base_scripts_location . 'python/create_phyloxml.py';
        $command_line_phyloxml =
            'python ' . $program_location_phyloxml . ' ' . implode(' ', $parameters_phyloxml) . "\n";

        fwrite($fh, $command_line_msa_tree . "\n");
        fwrite($fh, $command_line_phyloxml . "\n");
        fclose($fh);
        shell_exec('chmod a+x ' . $shell_file);
        return $shell_file;
    }

    // Create shell script to create GF MSA, return file name
    function create_shell_script_msa($exp_id, $gf_id, $msa_program) {
        $base_scripts_location = APP . 'scripts/';
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $necessary_modules = ['perl/x86_64/5.14.1', 'python/x86_64/2.7.14'];
        $necessary_modules[] = $msa_program;

        // Create actual shell script file
        $shell_file = $tmp_dir . 'create_msa_' . $gf_id . '.sh';
        $fh = fopen($shell_file, 'w');
        fwrite($fh, "#Loading necessary modules\n");
        foreach ($necessary_modules as $nm) {
            fwrite($fh, 'module load ' . $nm . " \n");
        }
        $parameters_msa_tree = [
            $exp_id,
            $gf_id,
            $tmp_dir,
            $base_scripts_location,
            TRAPID_DB_NAME,
            TRAPID_DB_SERVER,
            TRAPID_DB_USER,
            TRAPID_DB_PASSWORD,
            '--msa_program',
            $msa_program,
            '--msa_only',
            '--verbose'
        ];
        fwrite($fh, "\n#Launching wrapper script for files + MSA creation \n");
        $program_location_msa_tree = $base_scripts_location . 'python/run_msa_tree.py';
        $command_line_msa_tree = 'python ' . $program_location_msa_tree . ' ' . implode(' ', $parameters_msa_tree);
        fwrite($fh, $command_line_msa_tree . "\n");
        fclose($fh);
        // Chmod and return
        shell_exec('chmod a+x ' . $shell_file);
        return $shell_file;
    }

    // Create functional enrichment configuration file and return file name.
    // This function uses the template INI file found in `<INI>/func_enrichment_settings.ini`
    // Not all values necessary to the enrichment wrapper scripts are provided, so the same ini file can be used for
    // enrichment preprocessing (i.e. all subsets) or single enrichment jobs.
    function create_ini_file_enrichment($exp_id, $plaza_db) {
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        // Read base ini file
        $initial_processing_ini_file = INI . 'funct_enrichment_settings.ini';
        $initial_processing_ini_data = parse_ini_file($initial_processing_ini_file, true);
        // Replace values with experiment-specific values
        $initial_processing_ini_data['experiment']['tmp_exp_dir'] = $tmp_dir;
        $initial_processing_ini_data['experiment']['exp_id'] = $exp_id;
        $initial_processing_ini_data['reference_db']['reference_db_name'] = $plaza_db;
        // Create and populate new ini file for experiment
        $funct_enrichment_ini_file = $tmp_dir . 'funct_enrichment_settings_' . $exp_id . '.ini';
        $fh = fopen($funct_enrichment_ini_file, 'w');
        foreach ($initial_processing_ini_data as $section => $parameters) {
            fwrite($fh, '[' . $section . "]\n");
            foreach ($parameters as $param => $value) {
                $param_str = $param . ' = ' . $value . "\n";
                fwrite($fh, $param_str);
            }
            fwrite($fh, "\n");
        }
        fclose($fh);
        // Return INI file name
        return $funct_enrichment_ini_file;
    }

    /**
     * Create shell file for go enrichment preprocessing (i.e. generate enrichment for all subsets)
     *
     * This function will generate the GO enrichments for all subsets, for a given very low p-value.
     * These results can thus be stored in the database.
     *
     * @param int $exp_id The experiment identifier
     * @param string $data_type GO/InterPro
     * @param float|array $pvalue The p-value(s) which is/are used. Can be either a float or an array of floats.
     * @param array $all_subsets Array containing all subsets present in the experiment
     * @param string $selected_subset Optional, when we need to reprocess only for a given subset.
     * @return string shell file path
     */
    function create_shell_file_enrichment_preprocessing(
        $exp_id,
        $ini_file,
        $data_type,
        $pvalue,
        $all_subsets,
        $selected_subset = null
    ) {
        $base_scripts_location = APP . 'scripts/';
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';

        //define filepaths which will be used.
        $background_frequency_file_path =
            TMP . 'experiment_data/' . $exp_id . '/' . $data_type . '_transcript_' . $exp_id . '_all.txt';
        $subset_filepaths = [];
        foreach ($all_subsets as $subset => $subset_count) {
            if (!$selected_subset || $subset === $selected_subset) {
                $subset_file_path =
                    TMP .
                    'experiment_data/' .
                    $exp_id .
                    '/' .
                    $data_type .
                    '_transcript_' .
                    $exp_id .
                    '_' .
                    $subset .
                    '.txt';
                $enrich_file_path_base =
                    TMP . 'experiment_data/' . $exp_id . '/' . $data_type . '_enrichment_' . $exp_id . '_' . $subset;
                $enrich_file_paths = [];
                if (is_array($pvalue)) {
                    foreach ($pvalue as $pval) {
                        $enrich_file_paths['' . $pval] = $enrich_file_path_base . '_' . $pval . '.txt';
                    }
                } else {
                    $enrich_file_paths['' . $pvalue] = $enrich_file_path_base . '_' . $pvalue . '.txt';
                }
                $subset_filepaths[] = [
                    'subset' => $subset,
                    'data' => $subset_file_path,
                    'result' => $enrich_file_paths
                ];
            }
        }

        //create the shell file
        $shell_file = $tmp_dir . $data_type . '_enrichment_preprocessing_' . $exp_id . '.sh';
        if ($selected_subset) {
            $shell_file =
                $tmp_dir . $data_type . '_enrichment_preprocessing_' . $exp_id . '_' . $selected_subset . '.sh';
        }
        $fh = fopen($shell_file, 'w');
        $necessary_modules = ['perl/x86_64/5.14.1', 'python/x86_64/2.7.2', 'gcc/x86_64/6.3'];
        fwrite($fh, "#Loading necessary modules\n");
        foreach ($necessary_modules as $nm) {
            fwrite($fh, 'module load ' . $nm . " \n");
        }

        $py_location = $base_scripts_location . 'python/';
        $py_program = 'run_funct_enrichment_preprocess.py';
        $py_params = [
            $ini_file,
            $data_type,
            '--subsets',
            implode(' ', array_keys($all_subsets)),
            '--max_pvals',
            implode(' ', $pvalue),
            '--verbose',
            '--keep_tmp'
        ];
        fwrite(
            $fh,
            "\n#Launching python wrapper for deletion of previous results, enricher file creation, enrichment analysis, and DB upload\n"
        );
        fwrite($fh, 'python ' . $py_location . $py_program . ' ' . implode(' ', $py_params) . "\n");

        fclose($fh);
        shell_exec('chmod a+x ' . $shell_file);
        return $shell_file;
    }

    // Function to create shell file for functional enrichment analysis
    function create_shell_file_enrichment($exp_id, $ini_file, $type, $subset, $pvalue) {
        $base_scripts_location = APP . 'scripts/';
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $necessary_modules = ['python/x86_64/2.7.2', 'gcc/x86_64/6.3'];
        // Create actual file
        $shell_file = $tmp_dir . $type . '_enrichment_' . $exp_id . '_' . $subset . '.sh';
        $fh = fopen($shell_file, 'w');
        fwrite($fh, "# Loading necessary modules\n");
        foreach ($necessary_modules as $nm) {
            fwrite($fh, 'module load ' . $nm . " \n");
        }
        $py_location = $base_scripts_location . 'python/';
        $py_program = 'run_funct_enrichment.py';
        $py_params = [$ini_file, $type, $subset, $pvalue, '--verbose', '--keep_tmp'];
        fwrite($fh, 'python ' . $py_location . $py_program . ' ' . implode(' ', $py_params) . "\n");
        fclose($fh);
        shell_exec('chmod a+x ' . $shell_file);
        return $shell_file;
    }

    // Create experiment initial processing configuration file and return file name.
    // This function uses the template INI file found in `<INI>/exp_initial_processing_settings.ini.default`
    // Note: it may be better to avoid using a 'base' ini file altogether, as most of the information there is redundant
    // with other ini files / variables.
    function create_ini_file_initial(
        $exp_id,
        $plaza_db,
        $blast_db,
        $gf_type,
        $num_top_hits,
        $evalue,
        $func_annot,
        $tax_binning,
        $tax_scope,
        $rfam_clans,
        $use_cds,
        $transl_table
    ) {
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        // Read base ini file
        $initial_processing_ini_file = INI . 'exp_initial_processing_settings.ini';
        $initial_processing_ini_data = parse_ini_file($initial_processing_ini_file, true);
        // Replace values with experiment-specific values
        $initial_processing_ini_data['experiment']['tmp_exp_dir'] = $tmp_dir;
        $initial_processing_ini_data['experiment']['exp_id'] = $exp_id;
        $initial_processing_ini_data['reference_db']['reference_db_name'] = $plaza_db;
        $initial_processing_ini_data['sim_search']['blast_db_dir'] = BLAST_DB_DIR . $plaza_db . '/';
        $initial_processing_ini_data['sim_search']['blast_db'] = $blast_db;
        // Add a `-` in front of e-value (as we now provide it as -log10)
        $initial_processing_ini_data['sim_search']['e_value'] = '-' . $evalue;
        $initial_processing_ini_data['initial_processing']['gf_type'] = $gf_type;
        $initial_processing_ini_data['initial_processing']['num_top_hits'] = $num_top_hits;
        $initial_processing_ini_data['initial_processing']['func_annot'] = $func_annot;
        // Tax scope -> 'None' if empty. Unclean?
        $initial_processing_ini_data['initial_processing']['tax_scope'] = $tax_scope ? $tax_scope : 'None';
        // Convert tax binning boolean to string
        $initial_processing_ini_data['tax_binning']['perform_tax_binning'] = $tax_binning ? 'true' : 'false';
        // Convert `use_cds` boolean to string
        $initial_processing_ini_data['initial_processing']['use_cds'] = $use_cds ? 'true' : 'false';
        $initial_processing_ini_data['initial_processing']['transl_table'] = $transl_table;
        $initial_processing_ini_data['infernal']['rfam_clans'] = $rfam_clans;
        // Create and populate new ini file for experiment
        $exp_initial_processing_ini_file = $tmp_dir . 'initial_processing_settings_' . $exp_id . '.ini';
        $fh = fopen($exp_initial_processing_ini_file, 'w');
        foreach ($initial_processing_ini_data as $section => $parameters) {
            fwrite($fh, '[' . $section . "]\n");
            foreach ($parameters as $param => $value) {
                if (is_numeric($value)) {
                    $param_str = $param . ' = ' . $value . "\n";
                } else {
                    // Perl does not like quotes in ini files it seems (initial processing script)
                    // Either remove condition here or handle quotes strings from initial processing.
                    // $param_str = $param . " = \"" . $value . "\"\n";
                    $param_str = $param . ' = ' . $value . "\n";
                }
                fwrite($fh, $param_str);
            }
            fwrite($fh, "\n");
        }
        fclose($fh);
        // Return INI file name
        return $exp_initial_processing_ini_file;
    }

    // Function to create the necessary shell files for initial processing
    function create_shell_file_initial($exp_id, $exp_initial_processing_ini_file) {
        $base_scripts_location = SCRIPTS;
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        // kaiju needs to be loaded, and I will write my extra scripts in python 2.7
        // 2017-12-15: add diamond to the module list (time to switch form RapSearch2 to DIAMOND).
        // $necessary_modules	= array("perl","java","framedp",  "python/x86_64/2.7.2", "kaiju");
        $necessary_modules = [
            'KronaTools/x86_64/2.7',
            'java',
            'python/x86_64/2.7.2',
            'gcc',
            'kaiju/x86_64/1.7.3',
            'diamond/x86_64/0.9.18',
            'infernal/x86_64/1.1.2',
            'perl/x86_64/5.14.1'
        ];
        // Create shell file
        $shell_file = $tmp_dir . 'initial_processing_' . $exp_id . '.sh';
        $fh = fopen($shell_file, 'w');
        fwrite($fh, "# Loading necessary modules\n");
        foreach ($necessary_modules as $nm) {
            fwrite($fh, 'module load ' . $nm . " \n");
        }
        fwrite($fh, "\n# Java parameters\nexport _JAVA_OPTIONS=\"-Xmx12g\" \n");
        fwrite($fh, "\n# Launching perl script for initial processing, with necessary configuration file\n");
        $program_location = $base_scripts_location . 'perl/initial_processing.pl';
        $command_line = 'perl ' . $program_location . ' ' . $exp_initial_processing_ini_file;
        fwrite($fh, $command_line . "\n");

        return $shell_file;
    }

    function create_shell_file_upload($exp_id, $upload_dir) {
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $base_scripts_location = APP . 'scripts/';
        $necessary_modules = ['perl'];
        $necessary_parameters = [
            TRAPID_DB_SERVER,
            TRAPID_DB_NAME,
            TRAPID_DB_PORT,
            TRAPID_DB_USER,
            TRAPID_DB_PASSWORD,
            $upload_dir,
            $exp_id,
            $base_scripts_location
        ];
        //create actual file
        $shell_file = $tmp_dir . 'database_upload.sh';
        $fh = fopen($shell_file, 'w');
        fwrite($fh, "#Loading necessary modules\n");
        foreach ($necessary_modules as $nm) {
            fwrite($fh, 'module load ' . $nm . " \n");
        }
        fwrite($fh, "hostname\n");
        fwrite($fh, "date\n");
        fwrite($fh, "\n#Launching perl script for database upload, with necessary parameters\n");
        $program_location = $base_scripts_location . 'perl/database_upload.pl';
        $command_line = 'perl ' . $program_location . ' ' . implode(' ', $necessary_parameters);
        fwrite($fh, $command_line . "\n");
        fwrite($fh, "date\n");
        fclose($fh);
        shell_exec('chmod a+x ' . $shell_file);
        return $shell_file;
    }

    // Tax source not used -- unlikely to ever be used within TRAPID?
    //    function create_shell_script_completeness($clade_tax_id, $exp_id, $label, $species_perc, $tax_source, $top_hits){
    function create_shell_script_completeness(
        $clade_tax_id,
        $exp_id,
        $label,
        $species_perc,
        $top_hits,
        $tax_source,
        $db_type = 'plaza'
    ) {
        $base_scripts_location = APP . 'scripts/';
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $completeness_dir = $tmp_dir . 'completeness/';
        // Wrapper script name (different if working with EggNOG as ref. DB)
        $wrapper_script = 'run_core_gf_analysis_trapid.py';
        if ($db_type == 'eggnog') {
            $wrapper_script = 'run_core_gf_analysis_trapid_eggnog.py';
        }
        if (!(file_exists($completeness_dir) && is_dir($completeness_dir))) {
            mkdir($completeness_dir);
            shell_exec('chmod a+rw ' . $completeness_dir);
        }
        shell_exec('rm -rf ' . $completeness_dir . '*');
        $necessary_modules = ['python/x86_64/2.7.2'];
        // create actual shell script file
        $shell_file =
            $completeness_dir .
            'core_gf_completeness_' .
            $exp_id .
            '_' .
            $clade_tax_id .
            '_sp' .
            $species_perc .
            '_th' .
            $top_hits .
            '.sh';
        $fh = fopen($shell_file, 'w');
        fwrite($fh, "# Print starting date/time \ndate\n\n");
        fwrite($fh, "# Loading necessary modules\n");
        fwrite($fh, "# Loading necessary modules\n");
        fwrite($fh, '# ' . $db_type . "\n");
        foreach ($necessary_modules as $nm) {
            fwrite($fh, 'module load ' . $nm . " \n");
        }
        // Set environment variables used by the core GF completeness script: DB password & ETE NCBI taxonomy file
        $completeness_ini_file = INI . 'core_gf_completeness_settings.ini';
        $completeness_ini_data = parse_ini_file($completeness_ini_file, true);
        fwrite($fh, "\nexport DB_PWD='" . TRAPID_DB_PASSWORD . "'\n");
        fwrite($fh, "export ETE_NCBI_DBFILE='" . $completeness_ini_data['core_gf']['ete_ncbi_dbfile'] . "'\n");
        fwrite($fh, "\n# Launching python wrapper for core GF analysis from TRAPID, with correct parameters. \n");
        $program_location = $base_scripts_location . 'python/' . $wrapper_script;
        $parameters = [
            $clade_tax_id,
            $exp_id,
            '--label',
            $label,
            '--species_perc',
            $species_perc,
            '--top_hits',
            $top_hits,
            '--output_dir',
            $completeness_dir,
            '--trapid_db',
            TRAPID_DB_NAME
        ];
        $command_line = 'python ' . $program_location . ' ' . implode(' ', $parameters);
        fwrite($fh, $command_line . "\n");
        fclose($fh);
        shell_exec('chmod a+x ' . $shell_file);
        return $shell_file;
    }

    function create_shell_script_retranslate_subset($exp_id, $label_id, $transl_table = 1) {
        $base_scripts_location = SCRIPTS;
        $transl_tables_file = $base_scripts_location . 'cfg/all_translation_tables.json';
        $tmp_dir = TMP . 'experiment_data/' . $exp_id . '/';
        $necessary_modules = ['java'];
        // Create shell file
        $shell_file = $tmp_dir . 'retranslate_subset_' . $label_id . '_' . $exp_id . '.sh';
        $fh = fopen($shell_file, 'w');
        fwrite($fh, "# Loading necessary modules\n");
        foreach ($necessary_modules as $nm) {
            fwrite($fh, 'module load ' . $nm . " \n");
        }
        fwrite($fh, "\n# Java parameters\nexport _JAVA_OPTIONS=\"-Xmx8g\" \n");
        $java_program = 'transcript_pipeline.PredictOrfSubset';
        $java_location = $base_scripts_location . 'java/';
        $parameters = [
            TRAPID_DB_SERVER,
            TRAPID_DB_NAME,
            TRAPID_DB_USER,
            TRAPID_DB_PASSWORD,
            $exp_id,
            $label_id,
            $transl_tables_file,
            $transl_table
        ];
        $command_line =
            'java -cp ' .
            $java_location .
            'lib/*:' .
            $java_location .
            '. ' .
            $java_program .
            ' ' .
            implode(' ', $parameters);
        fwrite($fh, $command_line . "\n");
        fclose($fh);
        shell_exec('chmod a+x ' . $shell_file);
        return $shell_file;
    }

    // Create shell script for asynchronous experiment deletion. Return file name.
    function create_shell_script_delete_exp($exp_id) {
        $base_scripts_location = APP . 'scripts/';
        $tmp_dir_root = TMP . 'experiment_data/';
        $tmp_dir_exp = $tmp_dir_root . $exp_id . '/';
        $necessary_modules = ['python/x86_64/2.7.2'];
        $shell_file = $tmp_dir_root . 'delete_exp_' . $exp_id . '.sh';
        $fh = fopen($shell_file, 'w');
        fwrite($fh, "#Loading necessary modules\n");
        foreach ($necessary_modules as $nm) {
            fwrite($fh, 'module load ' . $nm . " \n");
        }
        $deletion_parameters = [
            $exp_id,
            $tmp_dir_exp,
            TRAPID_DB_NAME,
            TRAPID_DB_SERVER,
            TRAPID_DB_USER,
            TRAPID_DB_PASSWORD
        ];

        fwrite($fh, "\n#Launching experiment deletion script\n");
        $deletion_script = $base_scripts_location . 'python/delete_experiment.py';
        $deletion_command_line = 'python ' . $deletion_script . ' ' . implode(' ', $deletion_parameters);
        fwrite($fh, "date\n");
        fwrite($fh, $deletion_command_line . "\n");
        fwrite($fh, "date\n");
        fclose($fh);
        shell_exec('chmod a+x ' . $shell_file);
        return $shell_file;
    }

    // Generate a random character string
    // Added symbols to improve passwords generated with this function
    function rand_str($length = 32, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890') {
        $chars_length = strlen($chars) - 1; // Length of character list
        $string = $chars[rand(0, $chars_length)]; // Start our string
        for ($i = 1; $i < $length; $i = strlen($string)) {
            // Generate random string
            $r = $chars[rand(0, $chars_length)]; // Grab a random character from our list
            if ($r != $string[$i - 1]) {
                $string .= $r;
            } // Make sure the same two characters don't appear next to each other
        }
        return $string;
    }

    function create_password(
        $base_length = 14,
        $suffix_length = 2,
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890',
        $symbols = ',.?!:;/*_-+=#$%@&()[]{}'
    ) {
        $chars_length = strlen($chars) - 1; // Length of character list
        $symbols_length = strlen($symbols) - 1; // Length of character list
        $string = $chars[rand(0, $chars_length)]; // Start our string
        for ($i = 1; $i < $base_length; $i = strlen($string)) {
            // Generate random string with alphanumeric characters
            $r = $chars[rand(0, $chars_length)]; // Grab a random character from our list
            if ($r != $string[$i - 1]) {
                $string .= $r;
            } // Make sure the same two characters don't appear next to each other
        }
        // Append suffix with symbols
        for ($i = 0; $i < $suffix_length; $i++) {
            // Generate random string with alphanumeric characters
            $r = $symbols[rand(0, $symbols_length)]; // Grab a random character from our list
            $string .= $r; // Make sure the same two characters don't appear next to each other
        }
        return $string;
    }

    // Ensure parameters passed to this function are sanitized (e.g. result of `find()`)
    function send_registration_email($email, $password, $password_update = false) {
        $trapid_admins = ['francois.bucchini@protonmail.com', 'mibel@psb.ugent.be', 'klpoe@psb.ugent.be'];
        $subject = 'TRAPID authentication information';
        $message =
            "Welcome to TRAPID 2.0, the web resource for rapid analysis of transcriptome data.\nHere is the required authentication information.\n\nUser email address: " .
            $email .
            "\nPassword: " .
            $password .
            "\n\nThank you for using TRAPID 2.0.";
        if ($password_update) {
            $subject = 'TRAPID password change';
            $message =
                "The password for your TRAPID account has been changed.\n\nThe new password is: " .
                $password .
                "\n\nYou can change it at anytime: log in to TRAPID and select 'Account > Change password'.\n\nThank you for using TRAPID 2.0.\n";
        }

        $Email = new CakeEmail();
        $Email->config([
            'transport' => 'Smtp',
            'from' => ['no-reply@psb.ugent.be' => 'TRAPID'],
            'host' => 'smtp.psb.ugent.be',
            'log' => false
        ]);
        $Email
            ->to($email)
            ->subject($subject)
            ->send($message);

        // Send email to administrators to be warned when a new user is added
        // Run `reset()` before?
        if (!$password_update) {
            $Email
                ->to($trapid_admins)
                ->subject('TRAPID new user')
                ->send("New user added to TRAPID system\n\nUser login: " . $email);
        }
    }
}
