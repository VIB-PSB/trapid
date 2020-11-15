"""
This module contains a collection of constants and functions common to multiple TRAPID scripts.
"""

import os
import signal
import smtplib
import sys

from ConfigParser import ConfigParser
from email.MIMEMultipart import MIMEMultipart
from email.MIMEText import MIMEText

import MySQLdb as MS


# Base URL of the TRAPID instance used in processing emails
TRAPID_BASE_URL = "http://bioinformatics.psb.ugent.be/trapid_02"


def load_config(ini_file, required_sections=set()):
    """Read an INI file (configuration file), and optionally check if a set of required sections are present.

    :param ini_file: the INI file to read data from
    :param required_sections: required sections to check (set). If not present in the INI file, exit.
    :return: parsed configuration as a dictionary

    """
    config = ConfigParser()
    config.read(ini_file)
    config_dict = {section: dict(config.items(section)) for section in config.sections()}
    config_sections = set(config_dict.keys())
    if (required_sections & config_sections) != required_sections:
        missing_sections = required_sections - config_sections
        sys.stderr.write("[Error] Not all required sections were found in the INI file ('%s')\n" % ini_file)
        sys.stderr.write("[Error] Missing section(s): %s\n" % ", ".join(list(missing_sections)))
        sys.exit(1)
    return config_dict


def get_db_connection_data(config, db_type):
    """Get database connection data from parsed configuration, for either the TRAPID database or the experiment's
    reference database.

    :param config: parsed configuration as returned by load_config()
    :param db_type: DB type for which to get connection data (either 'trapid_db' or 'reference_db')
    :return: database connection data (list of parameters for common.db_connect())

    """
    valid_db_types = {'trapid_db', 'reference_db'}
    if db_type not in valid_db_types:
        raise ValueError("Invalid database type (must be one of %r). " % valid_db_types)
    # Prepend `db_type` to `db_suffixes` suffixes to get the appropriate configuration keys
    db_suffixes = ["username", "password", "server", "name"]
    db_keys = ["_".join([db_type, suffix]) for suffix in db_suffixes]
    return [config[db_type][db_key] for db_key in db_keys]


def db_connect(username, password, host, db_name):
    """Connect to a database and return a connection object.

    :param username: username to connect to the database
    :param password: password to connect to the database
    :param host: database host
    :param db_name: database name
    :return: database connection (as returned by db_connect())

    """
    try:
        db_connection = MS.connect(user=username, passwd=password, host=host, db=db_name)
    except MS.OperationalError as e:
        sys.stderr.write("[Error] Impossible to connect to the database. Check host/username/password (see error message below)\n")
        raise e
    return db_connection


def update_experiment_log(experiment_id, action, params, depth, db_conn):
    """Update experiment log (i.e. insert a new record in `experiment_log` table).

    :param experiment_id: TRAPID experiment id for which to update the log
    :param action: value for the `action` column
    :param params: value for the `params` column
    :param depth: value for the `depth` column
    :param db_conn: database connection (as returned by db_connect())

    """
    sql_str = "INSERT INTO `experiment_log`(`experiment_id`,`date`,`action`,`parameters`,`depth`) VALUES ({values_str});"
    values_str = "\'{exp_id}\', {date_time}, \'{action}\', \'{params}\', {depth}"
    # Get current time
    current_time = "NOW()"  # time.strftime('%Y-%m-%d %H:%M:%S')
    # Format value string, then try to update the experiment log
    current_values = values_str.format(exp_id=experiment_id, date_time=current_time, action=action, params=params,
                                       depth=depth)
    # print sql_str.format(values_str=current_values)
    try:
        cursor = db_conn.cursor()
        cursor.execute(sql_str.format(values_str=current_values))
        db_conn.commit()
    except Exception as e:
        print >> sys.stderr, e
        sys.stderr.write("[Error] Unable to update experiment log!\n")


def set_experiment_status(experiment_id, status, db_conn):
    """Set the processing status of TRAPID experiment.

    :param experiment_id: TRAPID experiment id
    :param status: the status to set ('empty', 'loading_db', 'upload', 'processing', 'finished', or 'error')
    :param db_conn: database connection (as returned by db_connect())

    """
    valid_statuses = {'empty', 'loading_db', 'upload', 'processing', 'finished', 'error'}
    if status not in valid_statuses:
        raise ValueError("Invalid experiment status (must be one of %r). " % valid_statuses)
    update_status_query = "UPDATE `experiments` SET `process_state` = '{status}' WHERE `experiment_id`='{exp_id}';"
    # print sql_str.format(values_str=current_values)
    try:
        cursor = db_conn.cursor()
        cursor.execute(update_status_query.format(status=status, exp_id=experiment_id))
        db_conn.commit()
    except Exception as e:
        print >> sys.stderr, e
        sys.stderr.write("[Error] Unable to update experiment status\n")


# TODO: while the current solution works, it would make more sense to change to `sys.exit` and let the master perl
# script to take care of terminating the initial processing
def stop_initial_processing_error(experiment_id, trapid_db_data):
    """Handle initial processing error: update experiment's log to state that initial processing stopped, set
    experiment's status to 'error', and terminate the initial processing.

    :param experiment_id: TRAPID experiment id
    :param trapid_db_data: TRAPID db connection data (parameters for db_connect())

    """
    sys.stderr.write("[Error] An error was encountered, terminate initial processing\n")
    db_connection = db_connect(*trapid_db_data)
    update_experiment_log(experiment_id, 'initial_processing', 'stop', 1, db_connection)
    set_experiment_status(experiment_id, 'error', db_connection)
    db_connection.close()
    # Kill parent process
    os.kill(os.getppid(), signal.SIGTERM)


def delete_experiment_job(experiment_id, job_name, db_conn):
    """Delete an experiment job (i.e. delete a record from `experiment_jobs`).

    :param experiment_id: TRAPID experiment id
    :param job_name: name of the job to delete
    :param db_conn: database connection (as returned by db_connect())

    """
    sql_str = "DELETE FROM `experiment_jobs` WHERE `experiment_id`='{exp_id}' AND `comment`='{job_name}'"
    try:
        cursor = db_conn.cursor()
        cursor.execute(sql_str.format(exp_id=experiment_id, job_name=job_name))
        db_conn.commit()
    except Exception as e:
        print >> sys.stderr, e
        sys.stderr.write("[Error] Unable to delete job!\n")


def send_mail(to, subject, text, fro="TRAPID <no-reply@psb.vib-ugent.be>", server="smtp.psb.ugent.be"):
    """Send an email via postfix. Function used to send processing emails and inspired by this example:
    http://masnun.com/2010/01/01/sending-mail-via-postfix-a-perfect-python-example.html

    :param to: email recipients (list)
    :param subject: email subject
    :param text: email content
    :param fro: email sender ('fro' to avoid using the reserved word 'from')
    :param server: server to send the email from

    """
    assert type(to) == list

    msg = MIMEMultipart()
    msg['From'] = fro
    msg['To'] = ', '.join(to)
    msg['Subject'] = subject
    msg.attach(MIMEText(text))

    try:
        smtp = smtplib.SMTP(server)
        smtp.sendmail(fro, to, msg.as_string())
        smtp.close()
    except Exception as e:
        print >> sys.stderr, e
        sys.stderr.write("[Error] Unable to send email!\n")


def ResultIter(db_cursor, arraysize=1000):
    """An iterator that uses `fetchmany` to retrieve records (keep memory usage down, faster than `fetchall`).

    :param db_cursor: a database cursor
    :param arraysize: the number of records to fetch

    """
    while True:
        results = db_cursor.fetchmany(arraysize)
        if not results:
            break
        for result in results:
            yield result
