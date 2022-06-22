library('hydrotools')
library('openmi.om')
library('zoo')
basepath='/var/www/R';
source("/var/www/R/config.R")


# standard watershed test element
# UT Rockfish on Mount Alto
elid <- 352161
runid = 802
ro_elid = 352163

dat <- om_get_rundata(elid, runid, site=omsite)
rdat <- om_get_rundata(ro_elid, runid, site=omsite)

quantile(dat$local_channel_its)

