#!/bin/sh
# shell
pgpath="/var/lib/postgresql/$1"
bindir="/usr/lib/postgresql/$1/bin"
pguser="postgres"
db_port=5433
# Startup As of 6/6/2019 use this:
$bindir/pg_ctl -D $pgpath/sessiondata restart -l logfile
