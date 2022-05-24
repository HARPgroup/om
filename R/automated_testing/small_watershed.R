library('hydrotools')
library('openmi.om')
library('zoo')
basepath='/var/www/R';
source("/var/www/R/config.R")


# standard watershed test element
# UT Rockfish on Mount Alto
elid <- 352161
runid = 201

dat <- om_get_rundata(elid, runid, site=omsite)

