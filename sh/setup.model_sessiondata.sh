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
echo "Setting up OM  model_sesssiondata on "
echo "PostgreSQL version: $pgv"
echo "pgpath=$pgpath"
echo "bindir=$bindir"

echo "mkdir $pgpath/sessiondata" 
mkdir $pgpath/sessiondata 
echo "$bindir/initdb -D $pgpath/sessiondata" 
$bindir/initdb -D $pgpath/sessiondata 
echo "cp $pgpath/postgresql.conf $pgpath/sessiondata/" 
cp $pgpath/postgresql.conf $pgpath/sessiondata/ 
echo "cp $pgpath/pg_hba.conf $pgpath/sessiondata/"
cp $pgpath/pg_hba.conf $pgpath/sessiondata/
# edit the postgresql.conf file and set the new port
# to $db_port
nano $pgpath/sessiondata/postgresql.conf
# create it
echo "$bindir/createdb model_sessiondata -p $db_port"
$bindir/createdb model_sessiondata -p $db_port

# start it up
echo "$bindir/pg_ctl -D $pgpath/sessiondata start -l \"logfile.sessiondata\""
$bindir/pg_ctl -D $pgpath/sessiondata start -l "logfile.sessiondata"

echo "CREATE EXTENSION postgis;" | psql model_sessiondata -p $db_port

cat msdef.sql | psql model_sessiondata -p $db_port
#plr_sql="/usr/share/postgresql/$pgv/extension/plr.sql"
#cat $plr_sql | psql --username=$pguser -d model_sessiondata -p $db_port
echo "CREATE EXTENSION plr;" | psql model_sessiondata -p $db_port
echo "cat /opt/model/om/sh/r_functions.sql |  psql --username=$pguser $dbname -p $db_port"
cat /opt/model/om/sh/r_functions.sql |  psql --username=$pguser $dbname -p $db_port

