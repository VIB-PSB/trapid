"""
This module contains a collection of variables/functions used in other modules
"""

import MySQLdb as MS
import time
import sys


def db_connect(username, password, host, db_name):
    """Connect to database. Return a database connection. """
    try:
        db_connection = MS.connect(host=host,
            user=username,
            passwd=password,
            db=db_name)
    except:
        sys.stderr.write("[Error] Impossible to connect to the database. Check host/username/password (see error message below)\n")
        raise
    return db_connection


def update_experiment_log(experiment_id, action, params, depth, db_conn):
    """Update experiment log (i.e. insert a new record in `experiment_log` table). """
    sql_str = "INSERT INTO `experiment_log`(`experiment_id`,`date`,`action`,`parameters`,`depth`) VALUES ({values_str});"
    values_str = "\'{exp_id}\', \'{date_time}\', \'{action}\', \'{params}\', {depth}"
    # Get current time
    current_time = time.strftime('%Y-%m-%d %H:%M:%S')
    # Format value string, then try to update the experiment log
    current_values = values_str.format(exp_id=experiment_id, date_time=current_time, action=action, params=params, depth=depth)
    # print sql_str.format(values_str=current_values)
    try:
        cursor = db_conn.cursor()
        cursor.execute(sql_str.format(values_str=current_values))
        db_conn.commit()  # Necessary?
    except Exception as e:
        print e
        sys.stderr.write("[Error] Unable to update experiment log!\n")
