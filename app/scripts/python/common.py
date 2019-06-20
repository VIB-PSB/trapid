"""
This module contains a collection of variables/functions used in other modules
"""

import MySQLdb as MS
import time
import smtplib
import sys
from ConfigParser import ConfigParser
from email.MIMEMultipart import MIMEMultipart
from email.MIMEText import MIMEText


TRAPID_BASE_URL = "http://bioinformatics.psb.ugent.be/trapid_dev_migration"


def load_config(ini_file_initial, needed_sections):
    """Read initial processing configuration file and check if all needed sections are there. Return it as dictionary. """
    config = ConfigParser()
    config.read(ini_file_initial)
    config_dict = {section: dict(config.items(section)) for section in config.sections()}
    config_sections = set(config_dict.keys())
    if len(needed_sections & config_sections) < len(needed_sections):
        missing_sections = needed_sections - config_sections
        sys.stderr.write("[Error] Not all required sections were found in the INI file ('%s')\n" % ini_file_initial)
        sys.stderr.write("[Error] Missing section(s): %s\n" % ", ".join(list(missing_sections)))
        sys.exit(1)
    return config_dict


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
    values_str = "\'{exp_id}\', {date_time}, \'{action}\', \'{params}\', {depth}"
    # Get current time
    current_time = "NOW()" # time.strftime('%Y-%m-%d %H:%M:%S')
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


def send_mail(to, subject, text, fro="TRAPID <no-reply@psb.vib-ugent.be>", server="smtp.psb.ugent.be"):
    """
    Send an email to recipients `to`, from `fro` with message subject `subject` and content `text`, sent from `server`.
    Inspired by this example: http://masnun.com/2010/01/01/sending-mail-via-postfix-a-perfect-python-example.html
    """
    assert type(to)==list

    msg = MIMEMultipart()
    msg['From'] = fro
    msg['To'] = ', '.join(to)
    msg['Subject'] = subject
    msg.attach( MIMEText(text) )

    smtp = smtplib.SMTP(server)
    smtp.sendmail(fro, to, msg.as_string() )
    smtp.close()


def ResultIter(db_cursor, arraysize=1000):
    """An iterator that uses `fetchmany` (keep memory usage down, faster than `fetchall`). """
    while True:
        results = db_cursor.fetchmany(arraysize)
        if not results:
            break
        for result in results:
            yield result
