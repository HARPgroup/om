#!/bin/sh
# shell
pgbase="/data/postgres"
libbase="/usr/lib/postgresql"
pguser="postgres"
db_port=5433
if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: setup.model_scratch.sh PG_VERSION pgpath[$pgbase] libbase[$libbase]"
  echo 1>&2 "Example: setup.model_scratch.sh 9.5 /data/postgres"
  exit 2
fi
pgv=$1
if [ $# -gt 1 ]; then
  pgbase=$2
fi
if [ $# -gt 2 ]; then
  libbase=$3
fi
pgpath="${pgbase}/$pgv"
bindir="${libbase}/$pgv/bin"
echo "Setting up OM  model_scratch on "
echo "PostgreSQL version: $pgv"
echo "pgpath=$pgpath"
echo "bindir=$bindir"


db_port=5444

mkdir $pgpath/scratch
$bindir/initdb -D $pgpath/scratch
cp $pgpath/main/postgresql.conf $pgpath/scratch/
cp $pgpath/main/pg_hba.conf $pgpath/scratch/
# edit the postgresql.conf file and set the new port
# port 5444
nano $pgpath/scratch/postgresql.conf
# start it up
$bindir/pg_ctl -D $pgpath/scratch start -l "logfile.scratch"

$bindir/createdb model_scratch -p $db_port
echo "CREATE EXTENSION postgis;" | psql model_scratch -p $db_port

# Set up runoff tempalte db
cat /opt/model/om/sh/cbp_p6_lseg_runoff_template.sql | psql model_scratch -p $db_port
cat /opt/model/om/sh/cbp_p5_lseg_runoff_template.sql | psql model_scratch -p $db_port

