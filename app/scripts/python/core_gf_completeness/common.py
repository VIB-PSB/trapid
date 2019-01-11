"""
Collection of functions used in various parts of this project
"""

import MySQLdb as MS
import getpass
import os
import sys
import time
from contextlib import contextmanager


TERM_COLORS = {
    "blue": '\033[34m',
    "cyan": '\033[36m',
    "green": '\033[32m',
    "magenta": "\033[35m",
    "orange": '\033[33m',
    "red": '\033[31m',
    "white": "\033[0m"
}


@contextmanager
def file_or_stdout(file_name):
    """Print to STDOUT when no file name (`file_name`) is provided. Print to `file_name` otherwise. """
    if not file_name:
        yield sys.stdout
    else:
        with open(file_name, 'w') as out_file:
            yield out_file


def connect_to_db(db_user, db_host, db_name):
    """"Connection to the database. Return a database connection. """
    try:
        # If password is not set as environment variable, prompt the user for it
        if os.environ.get('DB_PWD') is not None:
            print_log_msg("Password provided as environment variable (be careful with that).", color="orange")
            db_pwd = os.environ.get('DB_PWD')
        else:
            print_log_msg("Password not provided as environment variable (set $DB_PWD to avoid typing it each time). ", color="orange")
            db_pwd = getpass.getpass(prompt='Password for user ' + db_user + '@' + db_host + ':')
        db_conn = MS.connect(host=db_host, user=db_user, passwd=db_pwd, db=db_name)
        print_log_msg('Connected to database ' + db_name + ' (host: ' + db_host + ')', color="green")
    except Exception as e:
        print_log_msg('Error: impossible to connect to database ' + db_name + ' (host: ' + db_host + ')', color="red")
        sys.stderr.write(str(e) + '\n')
        sys.exit(1)
    return db_conn


# Handy iterator to fetch large amounts of data without bloating memory uselessly
def ResultIter(db_cursor, arraysize=1000):
    """An iterator that uses `fetchmany` (keep memory usage down, faster than `fetchall`)."""
    while True:
        # sys.stderr.write('['+time.strftime("%H:%M:%S")+'] Fetching '+str(arraysize)+' more...\n')
        results = db_cursor.fetchmany(arraysize)
        if not results:
            break
        for result in results:
            yield result


def print_log_msg(log_str, timestamp=True, color=None):
    """Print `log_str` to STDERR (a log message), optionally with time stamp and in a certain color. """
    log_msg = [log_str]
    if timestamp:
        time_str = time.strftime('%H:%M:%S')
        log_msg.insert(0, "[%s] " % time_str)
    if color and color in TERM_COLORS:
        log_msg.insert(0, TERM_COLORS[color])
        log_msg.append("\033[0m")
    log_msg.append("\n")
    sys.stderr.write("".join(log_msg))
