#!/bin/sh
pgpath="/var/lib/postgresql/$1"
bindir="/usr/lib/postgresql/$1/bin"
# As of 6/6/2019
$bindir/pg_ctl -D $pgpath/scratch/ restart -l logfile.model_scratch
