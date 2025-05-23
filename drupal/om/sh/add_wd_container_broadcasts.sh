#!/bin/sh
# This adds a set of broadcasts to the river model to take local_impoundment variables from child facility models to send to parent model from 1. Withdraws and Discharges
if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: add_local_impoundment_listenRules.sh pid [template=default=$template] [overwrite=0] [debug=0]"
  exit 2
fi
pid=$1
template=6974282 #Template for Withdrawals & Discharges  for river models

if [ $# -gt 1 ]; then
  if [ "$2" != "default" ]; then
    template=$2
  fi
fi  
overwrite=0
if [ $# -gt 2 ]; then
  overwrite=$3
fi 
debug=0
if [ $# -gt 3 ]; then
  debug=$4
fi 


om_copy_property dh_properties $template dh_properties $pid "Listen on Child" 1 $overwrite $debug
om_copy_property dh_properties $template dh_properties $pid "Broadcast to Parent" 1 $overwrite $debug
om_copy_property dh_properties $template dh_properties $pid "Broadcast to Child" 1 $overwrite $debug
om_copy_property dh_properties $template dh_properties $pid "Listen on Parent" 1 $overwrite $debug
