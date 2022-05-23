#!/bin/bash

hydrocode=$1
version=$2
template=75275

if [ $# -lt 3 ]; then
  echo 1>&2 "Usage: om_qa_met.sh hydrocode model_version(cbp6,cbp532)" >&2
  exit 2
fi 

ftype="$version_landseg"

# get the feature hydroid for the given landseg code and model version ftype
hydroid=`drush scr modules/om/src/dh_search_feature.php $hydrocode landunit $ftype`
# find the model 
pid=`drush scr modules/om/src/om_getpid.php dh_feature $hydroid NULL $version`


# create a new model if one does not exist for this version and land segment
if [ -z "$pid" ]; then 
  drush scr modules/om/src/om_copy_subcomp.php cmd dh_feature $template dh_feature $hydroid "Land Segment Model CBP"
fi 

# Call php script to look for model_scratch table for this land segment 
  # this should be a simple OM script that is non-destructive, read-only
  # do basic analysis of table contents.
  # return JSON
  
# Call R script with model pid and analysis JSON 
  # push summary info back to database 
  # single most improtant thing: did the import work and yield something that looks OK in terms of date range?