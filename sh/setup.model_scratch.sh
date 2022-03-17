#!/bin/sh
# shell
if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: setup.model_scratch.sh PG_VERSION"
  echo 1>&2 "Example: setup.model_scratch.sh 9.5"
  exit 2
fi
pgv=$1
echo "PostgreSQL version: $pgv"

pgpath="/data/postgres/$pgv"
bindir="/usr/lib/postgresql/$pgv/bin"
db_port=5444

mkdir $pgpath/scratch
$bindir/initdb -D $pgpath/scratch
cp $pgpath/postgresql.conf $pgpath/scratch/
cp $pgpath/pg_hba.conf $pgpath/scratch/
# edit the postgresql.conf file and set the new port
# port 5444
nano $pgpath/scratch/postgresql.conf
# start it up
$bindir/pg_ctl -D $pgpath/scratch start -l "logfile.scratch"

$bindir/createdb model_scratch -p $db_port
echo "CREATE EXTENSION postgis;" | psql model_scratch -p $db_port

# Set up runoff tempalte db
cat cbp_p6_lseg_runoff_template.sql | psql model_scratch -p $db_port
cat cbp_p5_lseg_runoff_template.sql | psql model_scratch -p $db_port

