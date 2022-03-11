#!/bin/bash
llist="A5110 A51137 A5117"
version="p532c-sova"
for i in $llist; do
  echo create_landseg_table.sh $i CFBASE30Y20180615 $version
  ./create_landseg_table.sh $i CFBASE30Y20180615 $version
done 

for i in $llist; do
   echo create_landseg_table.sh $i CBASE1808L55CY55R45P50R45P50Y $version;   
   ./create_landseg_table.sh $i CBASE1808L55CY55R45P50R45P50Y $version; 
done
