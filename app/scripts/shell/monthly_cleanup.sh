module load perl
#$1 is the path of the perl-script
#$2 is the db_server
#$3 is the db_name
#$4 is the db_port
#$5 is the db_username
#$6 is the db_password
#$7 is the tmp_dir
#$8 is the current year
#$9 is the current month
#$10 is the last_access cutoff (normally 3)
#$11 is the deletion timeout (normally 2)
perl $1 $2 $3 $4 $5 $6 $7 $8 $9 $10 $11
